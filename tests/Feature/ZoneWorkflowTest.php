<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\FinanceOption;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectZoneMovement;
use App\Models\ProjectZoneNote;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use App\Models\Zone;
use App\Services\ZoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The Zones module: the funding-side pipeline that runs beside the department
 * pipeline. See docs/zones.md.
 */
class ZoneWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fundingManager(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Funding Manager']);

        foreach (['View Zones', 'Notes Section', 'Files Section', 'View Project'] as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        $user->assignRole($role);

        return $user;
    }

    private function projectFixture(int $departmentId = 1): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        Department::firstOrCreate(['id' => 2], ['name' => 'Site Survey']);
        SubDepartment::firstOrCreate(['id' => 1], ['department_id' => 1, 'name' => 'New Deals']);
        // ProjectController::show() looks this role up for the ticket panel.
        Role::firstOrCreate(['name' => 'Service Manager']);

        $salesPartner = SalesPartner::create(['name' => 'Zone Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Zone',
            'last_name' => 'Customer',
            'street' => '12 Zone Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-444-4444',
            'email' => 'zone.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        // The project page's department-fields component reads the customer's
        // finance option, so the fixture needs one to render.
        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => FinanceOption::firstOrCreate(
                ['name' => 'Cash'],
                ['loan_id' => 0, 'holdback' => 0, 'dollar_watt_value' => 0, 'pto_restriction' => 0, 'no_of_days' => 0]
            )->id,
            'contract_amount' => 30000,
            'redline_costs' => 20000,
            'adders' => 0,
            'commission' => 2000,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $departmentId,
            'sub_department_id' => 1,
            'project_name' => 'Zone Project',
            'code' => '9001',
            'budget' => 30000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        // ProjectController::show() reads the project's latest active task.
        Task::create([
            'project_id' => $project->id,
            'department_id' => $departmentId,
            'sub_department_id' => 1,
            'status' => 'In-Progress',
        ]);

        return $project;
    }

    public function test_the_five_zones_are_seeded_with_archived_kept_off_the_board(): void
    {
        $zones = app(ZoneService::class);

        $this->assertSame(
            ['Pre NTP', 'NTP', 'M1', 'M2', 'Archived'],
            Zone::orderBy('order')->pluck('name')->all()
        );

        $this->assertSame(['Pre NTP', 'NTP', 'M1', 'M2'], $zones->boardZones()->pluck('name')->all());
        $this->assertSame('Archived', $zones->archiveZone()->name);
        $this->assertCount(5, $zones->movableZones(), 'The archive stays a valid move destination.');
    }

    public function test_reaching_deal_review_enrolls_the_project_at_pre_ntp(): void
    {
        $project = $this->projectFixture();

        app(ZoneService::class)->handleDepartmentArrival($project, 1);

        $this->assertSame('Pre NTP', $project->refresh()->zone->name);
        $this->assertDatabaseHas('project_zone_movements', [
            'project_id' => $project->id,
            'from_zone_id' => null,
            'is_auto' => true,
        ]);
    }

    public function test_reaching_site_survey_promotes_a_pre_ntp_project_to_ntp(): void
    {
        $project = $this->projectFixture();
        $zones = app(ZoneService::class);

        $zones->handleDepartmentArrival($project, 1);
        $zones->handleDepartmentArrival($project->refresh(), 2);

        $this->assertSame('NTP', $project->refresh()->zone->name);
    }

    public function test_a_department_move_never_pulls_a_manually_moved_zone_backwards(): void
    {
        $project = $this->projectFixture();
        $zones = app(ZoneService::class);
        $user = $this->fundingManager();
        $this->actingAs($user);

        $zones->handleDepartmentArrival($project, 1);
        $zones->move($project->refresh(), Zone::where('slug', 'm1')->value('id'), 'Funding approved.');

        // The project bounces back into Site Survey: its zone must not follow.
        $zones->handleDepartmentArrival($project->refresh(), 2);

        $this->assertSame('M1', $project->refresh()->zone->name);
    }

    public function test_a_project_that_never_reached_an_entry_department_stays_out_of_zones(): void
    {
        $project = $this->projectFixture(5);

        app(ZoneService::class)->handleDepartmentArrival($project, 5);

        $this->assertNull($project->refresh()->zone_id);
    }

    public function test_an_enrolled_project_page_shows_a_tab_for_every_zone(): void
    {
        $project = $this->projectFixture();
        app(ZoneService::class)->handleDepartmentArrival($project, 1);

        $response = $this->actingAs($this->fundingManager())
            ->get(route('projects.show', $project->id));

        $response->assertOk()->assertSee('id="zoneDetailTabs"', false);

        // Every zone gets a tab, not only the one the project has reached.
        foreach (Zone::orderBy('order')->get() as $zone) {
            $response->assertSee('id="zone-detail-tab-'.$zone->id.'"', false);
        }
    }

    public function test_the_page_opens_on_the_tab_of_the_zone_the_project_is_in(): void
    {
        $project = $this->projectFixture();
        $zones = app(ZoneService::class);
        $this->actingAs($this->fundingManager());

        $zones->handleDepartmentArrival($project, 1);
        $zones->move($project->refresh(), Zone::where('slug', 'm1')->value('id'));

        $html = $this->actingAs($this->fundingManager())
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->getContent();

        foreach (Zone::orderBy('order')->get() as $zone) {
            // The button markup runs class -> id, so read back from the id.
            $position = strpos($html, 'id="zone-detail-tab-'.$zone->id.'"');
            $button = substr($html, max(0, $position - 150), 200);

            $this->assertSame(
                $zone->slug === 'm1',
                str_contains($button, 'nav-link active'),
                "Zone {$zone->name} has the wrong active state."
            );
        }
    }

    public function test_a_project_with_no_zone_shows_no_zones_section(): void
    {
        // Created straight into Site Survey and never enrolled.
        $project = $this->projectFixture(2);

        $this->actingAs($this->fundingManager())
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertDontSee('id="zoneDetailTabs"', false);
    }

    public function test_the_board_is_gated_by_the_view_zones_permission(): void
    {
        $outsider = User::factory()->create(['user_type_id' => 1]);
        $outsider->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $this->actingAs($outsider)->get(route('zones.board'))->assertForbidden();
        $this->actingAs($this->fundingManager())->get(route('zones.board'))->assertOk();
    }

    public function test_the_old_zones_url_hands_the_user_to_the_projects_page_tab(): void
    {
        $this->actingAs($this->fundingManager())
            ->get(route('zones.index'))
            ->assertRedirect(route('projects.index', ['tab' => 'zones']));
    }

    public function test_the_projects_page_carries_the_operational_and_zones_tabs(): void
    {
        $this->projectFixture();

        $this->actingAs($this->fundingManager())
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('id="workspace-tab-operational"', false)
            ->assertSee('id="workspace-tab-zones"', false)
            ->assertSee('id="zoneBoardContainer"', false);
    }

    public function test_the_board_lists_enrolled_projects_as_kanban_columns(): void
    {
        $project = $this->projectFixture();
        $zones = app(ZoneService::class);
        $zones->handleDepartmentArrival($project, 1);

        $archived = $this->projectFixture();
        $zones->handleDepartmentArrival($archived, 1);
        $this->actingAs($this->fundingManager());
        $zones->move($archived->refresh(), $zones->archiveZone()->id);

        $board = $this->actingAs($this->fundingManager())
            ->get(route('zones.board'))
            ->assertOk()
            ->getContent();

        // One column per open zone, the archive kept off the board. Matched on
        // the column header itself: "Archived" is also a department, so it
        // legitimately appears in the board's department filter strip.
        $columnHeader = fn (string $zone) => 'icofont-layers me-2"></i>'.$zone;

        $this->assertSame(4, substr_count($board, 'class="zone-column"'));
        $this->assertStringContainsString($columnHeader('Pre NTP'), $board);
        $this->assertStringNotContainsString($columnHeader('Archived'), $board);

        // The card is the projects page card.
        $this->assertStringContainsString('class="card project-card border-0"', $board);
        $this->assertStringContainsString('progress-modern', $board);

        // The archived one is read through the same fragment's archive view.
        $archiveBoard = $this->actingAs($this->fundingManager())
            ->get(route('zones.board', ['archived' => 1]))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($archiveBoard, 'class="zone-column"'));
        $this->assertStringContainsString($columnHeader('Archived'), $archiveBoard);
    }

    public function test_the_move_endpoint_records_the_move_and_rejects_a_no_op(): void
    {
        $project = $this->projectFixture();
        app(ZoneService::class)->handleDepartmentArrival($project, 1);
        $user = $this->fundingManager();
        $m2 = Zone::where('slug', 'm2')->first();

        $this->actingAs($user)->postJson(route('zones.move'), [
            'project_id' => $project->id,
            'zone_id' => $m2->id,
            'note' => 'Milestone 2 funded.',
        ])->assertOk()->assertJson(['status' => 200]);

        $this->assertSame('M2', $project->refresh()->zone->name);
        $this->assertDatabaseHas('project_zone_movements', [
            'project_id' => $project->id,
            'to_zone_id' => $m2->id,
            'user_id' => $user->id,
            'note' => 'Milestone 2 funded.',
            'is_auto' => false,
        ]);

        // Moving it where it already is changes nothing.
        $this->actingAs($user)->postJson(route('zones.move'), [
            'project_id' => $project->id,
            'zone_id' => $m2->id,
        ])->assertStatus(422);

        $this->assertSame(1, ProjectZoneMovement::where('to_zone_id', $m2->id)->count());
    }

    public function test_zone_notes_are_stored_apart_from_department_notes(): void
    {
        $project = $this->projectFixture();
        app(ZoneService::class)->handleDepartmentArrival($project, 1);
        $project->refresh();

        $this->actingAs($this->fundingManager());

        Livewire::test(\App\Livewire\Project\NotesSection::class, [
            'projectId' => $project->id,
            'departmentId' => $project->department_id,
            'projectDepartmentId' => $project->department_id,
            'zoneId' => $project->zone_id,
            'projectZoneId' => $project->zone_id,
        ])
            ->set('departmentNote', 'Funding paperwork received.')
            ->call('save');

        $this->assertDatabaseHas('project_zone_notes', [
            'project_id' => $project->id,
            'zone_id' => $project->zone_id,
            'notes' => 'Funding paperwork received.',
        ]);
        $this->assertSame(0, DepartmentNote::where('project_id', $project->id)->count());
        $this->assertSame(1, ProjectZoneNote::where('project_id', $project->id)->count());
    }

    public function test_only_the_current_zone_tab_is_editable(): void
    {
        $project = $this->projectFixture();
        app(ZoneService::class)->handleDepartmentArrival($project, 1);
        $project->refresh();

        $this->actingAs($this->fundingManager());

        $otherZoneId = Zone::where('slug', 'm2')->value('id');

        $params = fn ($zoneId) => [
            'projectId' => $project->id,
            'departmentId' => $project->department_id,
            'projectDepartmentId' => $project->department_id,
            'zoneId' => $zoneId,
            'projectZoneId' => $project->zone_id,
        ];

        // The zone the project is in right now: the editor is there.
        Livewire::test(\App\Livewire\Project\NotesSection::class, $params($project->zone_id))
            ->assertSee('Add New Notes');
        Livewire::test(\App\Livewire\Project\EnhancedFilesSection::class, $params($project->zone_id))
            ->assertSee('Upload Files');

        // Any other zone: read-only.
        Livewire::test(\App\Livewire\Project\NotesSection::class, $params($otherZoneId))
            ->assertDontSee('Add New Notes');
        Livewire::test(\App\Livewire\Project\EnhancedFilesSection::class, $params($otherZoneId))
            ->assertDontSee('Upload Files');
    }

    public function test_a_department_note_still_goes_to_department_notes(): void
    {
        $project = $this->projectFixture();
        $this->actingAs($this->fundingManager());

        Livewire::test(\App\Livewire\Project\NotesSection::class, [
            'projectId' => $project->id,
            'departmentId' => $project->department_id,
            'projectDepartmentId' => $project->department_id,
        ])
            ->set('departmentNote', 'Deal review note.')
            ->call('save');

        $this->assertSame(1, DepartmentNote::where('project_id', $project->id)->count());
        $this->assertSame(0, ProjectZoneNote::where('project_id', $project->id)->count());
        $this->assertSame(0, ProjectFile::where('project_id', $project->id)->count());
    }

    /* ---------------------------------------------------------------------
     | Zone fields: the NTP tab collects the NTP Approval Date. No zone move is
     | gated on it - Operations asks for it on Permitting -> Installation.
     --------------------------------------------------------------------- */

    /** A project sitting in the NTP zone, the way the promotion rule leaves it. */
    private function projectInNtpZone(): Project
    {
        $project = $this->projectFixture();
        $zones = app(ZoneService::class);

        $zones->handleDepartmentArrival($project, 1);
        $zones->handleDepartmentArrival($project->refresh(), 2);

        return $project->refresh();
    }

    public function test_the_ntp_tab_carries_the_field_and_only_the_current_zone_may_edit_it(): void
    {
        $project = $this->projectInNtpZone();
        $ntpZoneId = Zone::where('slug', 'ntp')->value('id');
        $input = 'id="zone-field-'.$ntpZoneId.'-ntp_approval_date"';

        $html = $this->actingAs($this->fundingManager())
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertSee($input, false)
            ->getContent();

        $this->assertStringNotContainsString(
            'disabled',
            substr($html, strpos($html, $input), 200),
            'The project is in NTP, so its own tab must be editable.'
        );

        // Moved on to M1: the NTP tab keeps showing the date, read-only.
        app(ZoneService::class)->move($project, Zone::where('slug', 'm1')->value('id'));

        $html = $this->actingAs($this->fundingManager())
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertSee($input, false)
            ->getContent();

        $this->assertStringContainsString(
            'disabled',
            substr($html, strpos($html, $input), 200),
            'A zone the project has left must be read-only.'
        );
    }

    public function test_the_zone_fields_endpoint_writes_only_the_zone_the_project_is_in(): void
    {
        $project = $this->projectInNtpZone();
        $user = $this->fundingManager();

        $this->actingAs($user)
            ->postJson(route('zones.fields'), [
                'project_id' => $project->id,
                'zone_id' => $project->zone_id,
                'ntp_approval_date' => '2026-08-11',
            ])
            ->assertOk();

        $this->assertSame('2026-08-11', (string) $project->refresh()->ntp_approval_date);

        // The same field, asked for through a zone the project is not in.
        $this->actingAs($user)
            ->postJson(route('zones.fields'), [
                'project_id' => $project->id,
                'zone_id' => Zone::where('slug', 'm2')->value('id'),
                'ntp_approval_date' => '2026-09-09',
            ])
            ->assertStatus(422);

        $this->assertSame('2026-08-11', (string) $project->refresh()->ntp_approval_date);
    }

    public function test_a_zone_with_no_fields_of_its_own_saves_nothing(): void
    {
        $project = $this->projectFixture();
        app(ZoneService::class)->handleDepartmentArrival($project, 1);

        // Pre NTP declares no fields, so its tab has nothing to write.
        $this->actingAs($this->fundingManager())
            ->postJson(route('zones.fields'), [
                'project_id' => $project->id,
                'zone_id' => $project->refresh()->zone_id,
                'ntp_approval_date' => '2026-08-11',
            ])
            ->assertStatus(422);

        $this->assertNull($project->refresh()->ntp_approval_date);
    }

    public function test_deal_review_department_fields_no_longer_carry_the_ntp_approval_date(): void
    {
        $project = $this->projectFixture();
        $this->actingAs($this->fundingManager());

        Livewire::test(\App\Livewire\Project\ProjectFields\EditFields::class, [
            'project' => $project,
            'departmentId' => 1,
            'ghost' => null,
        ])
            ->assertSee('Utility Company')
            ->assertDontSee('NTP Approval Date');

        Livewire::test(\App\Livewire\Project\ProjectFields\ViewFields::class, [
            'project' => $project,
            'departmentId' => 1,
        ])
            ->assertSee('Utility Company')
            ->assertDontSee('NTP Approval Date');
    }
}
