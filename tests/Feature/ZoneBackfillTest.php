<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\ProjectZoneMovement;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Services\ZoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The one-off catch-up that puts the existing backlog on the funding board.
 *
 * Two things it must never do: overrule a placement the Funding Manager made
 * by hand, and change how projects are enrolled from now on.
 */
class ZoneBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function departments(): void
    {
        foreach ([
            1 => 'Deal Review',
            2 => 'Site Survey',
            3 => 'Engineering',
            4 => 'Permitting',
            5 => 'Installation',
            6 => 'Inspection',
            7 => 'PTO',
            8 => 'Certificate of Completion',
            9 => 'Archive',
        ] as $id => $name) {
            Department::firstOrCreate(['id' => $id], ['name' => $name]);
        }

        SubDepartment::firstOrCreate(['id' => 1], ['department_id' => 1, 'name' => 'New Deals']);
    }

    private function project(int $departmentId, ?string $taskCreatedAt = null): Project
    {
        $customer = Customer::create(['first_name' => 'Dept '.$departmentId, 'last_name' => 'Doe']);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $departmentId,
            'sub_department_id' => 1,
            'project_name' => 'Project in '.$departmentId,
            'code' => 'P-'.$departmentId.random_int(100, 999),
        ]);

        Task::create([
            'project_id' => $project->id,
            'department_id' => $departmentId,
            'sub_department_id' => 1,
            'status' => 'In-Progress',
            'created_at' => $taskCreatedAt ?? now(),
        ]);

        return $project;
    }

    private function zoneOf(Project $project): ?string
    {
        return $project->fresh()->zone?->name;
    }

    public function test_every_active_project_lands_in_the_zone_its_department_says(): void
    {
        $this->departments();

        $dealReview = $this->project(1);
        $siteSurvey = $this->project(2);
        $engineering = $this->project(3);
        $permitting = $this->project(4);
        $installation = $this->project(5);
        $inspection = $this->project(6);
        $pto = $this->project(7);
        $completion = $this->project(8);

        $this->artisan('zones:backfill')->assertSuccessful();

        $this->assertSame('Pre NTP', $this->zoneOf($dealReview));
        $this->assertSame('NTP', $this->zoneOf($siteSurvey));
        $this->assertSame('NTP', $this->zoneOf($engineering));
        $this->assertSame('NTP', $this->zoneOf($permitting));
        $this->assertSame('M1', $this->zoneOf($installation));
        $this->assertSame('M1', $this->zoneOf($inspection));
        $this->assertSame('M2', $this->zoneOf($pto));
        $this->assertSame('M2', $this->zoneOf($completion));
    }

    public function test_an_archived_project_is_left_off_the_board(): void
    {
        $this->departments();

        $archived = $this->project(9);

        $this->artisan('zones:backfill')->assertSuccessful();

        $this->assertNull($archived->fresh()->zone_id);
    }

    public function test_a_placement_the_funding_manager_made_is_never_overruled(): void
    {
        $this->departments();

        $project = $this->project(5);          // Installation, so the catch-up would say M1
        $zones = app(ZoneService::class);
        $zones->move($project, $zones->zoneBySlug('m2')->id, 'Funded early.');

        $this->artisan('zones:backfill')->assertSuccessful();

        $this->assertSame('M2', $this->zoneOf($project));
    }

    public function test_the_dry_run_writes_nothing(): void
    {
        $this->departments();

        $project = $this->project(4);

        $this->artisan('zones:backfill', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($project->fresh()->zone_id);
    }

    public function test_running_it_twice_places_nothing_the_second_time(): void
    {
        $this->departments();

        $project = $this->project(6);

        $this->artisan('zones:backfill')->assertSuccessful();
        $this->artisan('zones:backfill')->assertSuccessful();

        $this->assertSame(1, ProjectZoneMovement::where('project_id', $project->id)->count());
    }

    public function test_the_zone_clock_starts_when_the_project_reached_its_stage(): void
    {
        $this->departments();

        $project = $this->project(5, now()->subDays(40)->toDateTimeString());

        $this->artisan('zones:backfill')->assertSuccessful();

        $this->assertSame(40, app(ZoneService::class)->daysInZone($project->fresh()));
    }

    public function test_the_history_records_it_as_an_automatic_move(): void
    {
        $this->departments();

        $project = $this->project(7);

        $this->artisan('zones:backfill')->assertSuccessful();

        $movement = ProjectZoneMovement::where('project_id', $project->id)->firstOrFail();

        $this->assertTrue((bool) $movement->is_auto);
        $this->assertNull($movement->user_id);
        $this->assertNull($movement->from_zone_id);
    }

    public function test_new_projects_still_follow_the_existing_rules(): void
    {
        $this->departments();

        $zones = app(ZoneService::class);

        // Deal Review enrols at Pre NTP …
        $project = $this->project(1);
        $zones->handleDepartmentArrival($project, 1);
        $this->assertSame('Pre NTP', $this->zoneOf($project));

        // … and Site Survey promotes it to NTP, exactly as before.
        $zones->handleDepartmentArrival($project->fresh(), 2);
        $this->assertSame('NTP', $this->zoneOf($project));

        // A project reaching Permitting is NOT pulled anywhere by the catch-up
        // mapping: that only runs from the command.
        $permitting = $this->project(4);
        $zones->handleDepartmentArrival($permitting, 4);
        $this->assertNull($permitting->fresh()->zone_id);
    }
}
