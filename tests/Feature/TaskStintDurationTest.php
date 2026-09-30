<?php

namespace Tests\Feature;

use App\Livewire\DepartmentTimeChart;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The Department Logs tab reports how long a project sat in each department.
 * Before `tasks.completed_at` existed it read `updated_at`, which is the row's
 * last write rather than its exit - one customer edit mass-updating every
 * Deal Review task re-stamped a whole history and turned two sub-minute stints
 * into 36 reported days.
 */
class TaskStintDurationTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        Department::firstOrCreate(['id' => 2], ['name' => 'Site Survey']);

        $customer = Customer::create([
            'first_name' => 'Stint',
            'last_name' => 'Customer',
            'sold_date' => '2026-08-06',
        ]);

        return Project::create([
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'project_name' => 'Stint Project',
        ]);
    }

    private function task(Project $project, int $departmentId, string $createdAt, string $updatedAt, string $status = 'Completed'): Task
    {
        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => 1,
            'department_id' => $departmentId,
            'status' => $status,
        ]);

        // Timestamps are what this is all about, so they are written straight
        // to the row rather than through the model's own clock.
        DB::table('tasks')->where('id', $task->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'completed_at' => null,
        ]);

        return $task->refresh();
    }

    private function runBackfill(): void
    {
        $migration = require database_path('migrations/2026_09_30_000002_backfill_tasks_completed_at.php');
        $migration->up();
    }

    public function test_exit_date_prefers_completed_at_over_a_restamped_updated_at(): void
    {
        $exit = Task::exitDate('Completed', '2026-08-06 13:12:25', '2026-08-27 08:09:18');

        $this->assertSame('2026-08-06 13:12:25', $exit->toDateTimeString());
        $this->assertSame(1, Task::stintDays('2026-08-06 13:11:41', $exit));
    }

    public function test_exit_date_falls_back_to_updated_at_only_when_completed_at_is_missing(): void
    {
        $exit = Task::exitDate('Completed', null, '2026-08-27 08:09:18');

        $this->assertSame('2026-08-27 08:09:18', $exit->toDateTimeString());
    }

    public function test_an_open_stint_has_no_exit_date_and_is_measured_up_to_now(): void
    {
        $this->assertNull(Task::exitDate('In-Progress', null, '2026-08-27 08:09:18'));
        $this->assertSame(
            5,
            Task::stintDays(now()->subDays(5)->toDateTimeString(), Task::exitDate('In-Progress', null, null))
        );
    }

    public function test_backfill_reconstructs_the_exit_date_from_the_next_stint(): void
    {
        $project = $this->project();

        // The real shape of the reported bug: two Deal Review stints that each
        // ended within a minute, both re-stamped to the same later customer edit.
        $dealReview = $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-27 08:09:18');
        $siteSurvey = $this->task($project, 2, '2026-08-06 13:12:25', '2026-08-10 09:00:53');
        $dealReviewAgain = $this->task($project, 1, '2026-08-10 13:26:18', '2026-08-27 08:09:18');
        $open = $this->task($project, 2, '2026-08-10 13:26:33', '2026-08-27 08:09:18', 'In-Progress');

        $this->runBackfill();

        $this->assertSame('2026-08-06 13:12:25', $dealReview->refresh()->completed_at->toDateTimeString());
        $this->assertSame('2026-08-10 13:26:18', $siteSurvey->refresh()->completed_at->toDateTimeString());
        $this->assertSame('2026-08-10 13:26:33', $dealReviewAgain->refresh()->completed_at->toDateTimeString());

        // The last row of the chain has no successor to read, and it is still
        // open, so it keeps no exit at all.
        $this->assertNull($open->refresh()->completed_at);
    }

    public function test_backfill_collapses_the_inflated_department_total(): void
    {
        $project = $this->project();

        $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-27 08:09:18');
        $this->task($project, 2, '2026-08-06 13:12:25', '2026-08-10 09:00:53');
        $this->task($project, 1, '2026-08-10 13:26:18', '2026-08-27 08:09:18');
        $this->task($project, 2, '2026-08-10 13:26:33', '2026-08-11 08:59:33', 'Completed');

        $inflated = $this->dealReviewDays($project);
        $this->assertSame(36, $inflated, 'the pre-fix reading of the reported project');

        $this->runBackfill();

        // Both Deal Review stints really lasted well under a minute, and the
        // 1-day floor is all that is left of them.
        $this->assertSame(2, $this->dealReviewDays($project));
    }

    public function test_backfill_never_overwrites_a_stamped_exit_date(): void
    {
        $project = $this->project();

        $first = $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-27 08:09:18');
        $this->task($project, 2, '2026-08-20 10:00:00', '2026-08-20 10:00:00');

        DB::table('tasks')->where('id', $first->id)->update(['completed_at' => '2026-08-07 09:00:00']);

        $this->runBackfill();

        $this->assertSame('2026-08-07 09:00:00', $first->refresh()->completed_at->toDateTimeString());
    }

    public function test_backfill_never_produces_a_negative_stint(): void
    {
        $project = $this->project();

        // A successor created before its predecessor (clock skew, a hand-edited
        // row) must read as an instant stint, not as time travel.
        $first = $this->task($project, 1, '2026-08-10 10:00:00', '2026-08-10 10:00:00');
        $this->task($project, 2, '2026-08-01 09:00:00', '2026-08-01 09:00:00');

        $this->runBackfill();

        $this->assertSame('2026-08-10 10:00:00', $first->refresh()->completed_at->toDateTimeString());
        $this->assertSame(1, Task::stintDays($first->created_at, $first->completed_at));
    }

    public function test_cancelling_a_project_closes_only_the_open_stint(): void
    {
        $project = $this->project();

        $closed = $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-06 13:12:25');
        DB::table('tasks')->where('id', $closed->id)->update(['completed_at' => '2026-08-06 13:12:25']);

        $open = $this->task($project, 2, '2026-08-06 13:12:25', '2026-08-06 13:12:25', 'In-Progress');

        UserType::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['user_type_id' => 1]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));

        $this->actingAs($admin)->post(route('projects.status'), [
            'project_id' => $project->id,
            'status' => 'Cancelled',
            'reason' => 'Customer backed out.',
        ])->assertOk();

        // The mass update touches history too, so the stint the project left in
        // August must keep its own exit rather than being dragged to now.
        $this->assertSame('2026-08-06 13:12:25', $closed->refresh()->completed_at->toDateTimeString());
        $this->assertNotNull($open->refresh()->completed_at);
        $this->assertTrue($open->completed_at->isToday());
    }

    public function test_the_department_time_chart_measures_a_stint_to_its_exit_not_its_last_write(): void
    {
        $project = $this->project();

        // One real Deal Review stint of three days, whose row was later
        // re-stamped by an unrelated write three weeks on.
        $stint = $this->task($project, 1, '2026-08-06 09:00:00', '2026-08-27 08:09:18');
        DB::table('tasks')->where('id', $stint->id)->update(['completed_at' => '2026-08-09 09:00:00']);

        $average = $this->chartAverageFor('Deal Review');

        $this->assertNotNull($average, 'the stint should appear in the chart');
        $this->assertSame(3.0, $average);
    }

    public function test_the_department_time_chart_still_discards_an_instant_stint(): void
    {
        $project = $this->project();

        // The stint that produced the reported bug: it lasted 44 seconds, but
        // its `updated_at` was dragged three weeks forward. Reading `updated_at`
        // let it through the "instant step" filter and counted it as 20 days.
        $stint = $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-27 08:09:18');
        DB::table('tasks')->where('id', $stint->id)->update(['completed_at' => '2026-08-06 13:12:25']);

        $this->assertNull($this->chartAverageFor('Deal Review'));
    }

    /**
     * The average stage length the dashboard chart reports for a department,
     * or null when the department is not in the chart at all.
     */
    private function chartAverageFor(string $department): ?float
    {
        $chart = Livewire::test(DepartmentTimeChart::class, [
            'startDate' => '2026-08-01',
            'endDate' => '2026-08-31',
        ])->get('departmentChartData');

        $index = array_search($department, $chart['labels'], true);

        return $index === false ? null : (float) $chart['data'][$index];
    }

    private function dealReviewDays(Project $project): int
    {
        return Task::where('project_id', $project->id)
            ->where('department_id', 1)
            ->get()
            ->sum(fn (Task $task) => Task::stintDays(
                $task->created_at,
                Task::exitDate($task->status, $task->completed_at, $task->updated_at)
            ));
    }
}
