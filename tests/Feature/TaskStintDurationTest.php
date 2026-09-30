<?php

namespace Tests\Feature;

use App\Livewire\DepartmentTimeChart;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
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
        $this->assertSame(0.0, Task::stintDays('2026-08-06 13:11:41', $exit));
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
            5.0,
            Task::stintDays(now()->subDays(5)->toDateTimeString(), Task::exitDate('In-Progress', null, null))
        );
    }

    /**
     * @dataProvider stintLabels
     */
    public function test_a_stint_reads_as_days_and_the_hours_left_over(string $entry, string $exit, string $expected): void
    {
        $this->assertSame($expected, Task::stintLabel($entry, Carbon::parse($exit)));
    }

    public static function stintLabels(): array
    {
        return [
            // The hour part is what is LEFT OVER after whole days, so it never
            // reaches 24: that span is simply one more day.
            '23h59m short of four days' => ['2026-08-06 00:00:00', '2026-08-09 23:59:59', '3 days 23 hours'],
            'exactly four days' => ['2026-08-06 00:00:00', '2026-08-10 00:00:00', '4 days'],
            'a minute past four days' => ['2026-08-06 00:00:00', '2026-08-10 00:01:00', '4 days'],

            // Half a day is TWELVE hours. Reading the ".5" of 3.5 days as an
            // hour count would print "3 days 5 hours" for this.
            'three and a half days' => ['2026-08-06 00:00:00', '2026-08-09 12:00:00', '3 days 12 hours'],
            'three and a quarter days' => ['2026-08-06 00:00:00', '2026-08-09 06:00:00', '3 days 6 hours'],

            'under an hour' => ['2026-08-06 13:11:41', '2026-08-06 13:12:25', '< 1 hour'],
            'instant' => ['2026-08-06 13:11:41', '2026-08-06 13:11:41', '< 1 hour'],
            'one hour' => ['2026-08-06 09:00:00', '2026-08-06 10:00:53', '1 hour'],
            'some hours' => ['2026-08-06 09:00:00', '2026-08-06 15:20:00', '6 hours'],
            'nearly a day' => ['2026-08-06 09:00:00', '2026-08-07 08:59:00', '23 hours'],
            'one day' => ['2026-08-06 09:00:00', '2026-08-07 09:00:00', '1 day'],
            'one day one hour' => ['2026-08-06 09:00:00', '2026-08-07 10:30:00', '1 day 1 hour'],
            'the reported site survey stint' => ['2026-08-06 13:12:25', '2026-08-10 09:00:53', '3 days 19 hours'],
        ];
    }

    public function test_an_open_stint_reads_up_to_now(): void
    {
        $this->assertSame('2 days 3 hours', Task::stintLabel(now()->subDays(2)->subHours(3)->subMinute(), null));
    }

    public function test_a_backwards_stint_reads_as_instant_rather_than_negative(): void
    {
        $this->assertSame('< 1 hour', Task::stintLabel('2026-08-10 10:00:00', Carbon::parse('2026-08-01 09:00:00')));
        $this->assertSame(0.0, Task::stintDays('2026-08-10 10:00:00', Carbon::parse('2026-08-01 09:00:00')));
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
        $this->assertSame(37.6, $inflated, 'the pre-fix reading of the reported project');

        $this->runBackfill();

        // Both Deal Review stints really lasted well under a minute - 44 and 15
        // seconds - so the department rounds to nothing at all.
        $this->assertSame(0.0, $this->dealReviewDays($project));
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
        $this->assertSame(0.0, Task::stintDays($first->created_at, $first->completed_at));
    }

    public function test_cancelling_a_project_closes_only_the_open_stint(): void
    {
        $project = $this->project();

        $closed = $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-06 13:12:25');
        DB::table('tasks')->where('id', $closed->id)->update(['completed_at' => '2026-08-06 13:12:25']);

        $open = $this->task($project, 2, '2026-08-06 13:12:25', '2026-08-06 13:12:25', 'In-Progress');

        $this->actingAs($this->superAdmin())->post(route('projects.status'), [
            'project_id' => $project->id,
            'status' => 'Cancelled',
            'reason' => 'Customer backed out.',
        ])->assertOk();

        // The stint the project left in August keeps its own exit rather than
        // being dragged to now.
        $this->assertSame('2026-08-06 13:12:25', $closed->refresh()->completed_at->toDateTimeString());
        $this->assertNotNull($open->refresh()->completed_at);
        $this->assertTrue($open->completed_at->isToday());
    }

    public function test_a_status_change_leaves_the_notes_of_past_stints_alone(): void
    {
        $project = $this->project();

        // Each move records its own note on the task it closes. A later status
        // change used to write every row of the project, overwriting all of
        // them with its one reason and losing the history for good.
        $first = $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-06 13:12:25');
        $first->update(['notes' => 'Deal approved, sending to site survey.']);

        $second = $this->task($project, 2, '2026-08-06 13:12:25', '2026-08-10 09:00:53');
        $second->update(['notes' => 'Survey booked for the 10th.']);

        $open = $this->task($project, 1, '2026-08-10 09:00:53', '2026-08-10 09:00:53', 'In-Progress');

        $this->actingAs($this->superAdmin())->post(route('projects.status'), [
            'project_id' => $project->id,
            'status' => 'Hold',
            'reason' => 'Waiting on the utility.',
        ])->assertOk();

        $this->assertSame('Deal approved, sending to site survey.', $first->refresh()->notes);
        $this->assertSame('Survey booked for the 10th.', $second->refresh()->notes);
        $this->assertSame('Waiting on the utility.', $open->refresh()->notes);
    }

    public function test_a_status_change_leaves_the_status_of_past_stints_alone(): void
    {
        $project = $this->project();

        $completed = $this->task($project, 1, '2026-08-06 13:11:41', '2026-08-06 13:12:25');
        $open = $this->task($project, 2, '2026-08-06 13:12:25', '2026-08-06 13:12:25', 'In-Progress');

        $this->actingAs($this->superAdmin())->post(route('projects.status'), [
            'project_id' => $project->id,
            'status' => 'Hold',
            'reason' => 'Waiting on the utility.',
        ])->assertOk();

        // A lane the project left months ago is not on hold - only the one it
        // is sitting in is. Putting a project on hold used to flip every
        // historical row to Hold, which also left them with no exit at all in
        // the Department Logs tab.
        $this->assertSame('Completed', $completed->refresh()->status);
        $this->assertSame('Hold', $open->refresh()->status);
    }

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['user_type_id' => 1]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));

        return $admin;
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

    private function dealReviewDays(Project $project): float
    {
        return Task::daysFromSeconds((int) Task::where('project_id', $project->id)
            ->where('department_id', 1)
            ->get()
            ->sum(fn (Task $task) => Task::stintSeconds(
                $task->created_at,
                Task::exitDate($task->status, $task->completed_at, $task->updated_at)
            )));
    }
}
