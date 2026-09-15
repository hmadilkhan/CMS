<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FinanceMilestoneEmailRecipient;
use App\Models\ModuleType;
use App\Models\User;
use App\Models\UserType;
use App\Services\Operations\OperationsPanel;
use App\Services\OperationsConsoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The Operations console: one window listing the Operations screens and opening
 * the one that was asked for. It adds no access of its own - every screen keeps
 * its own route and its own permission.
 */
class OperationsConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function user(string ...$permissions): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $user->syncRoles([$role]);

        return $user->fresh();
    }

    public function test_the_console_needs_the_same_permission_the_screens_do(): void
    {
        $this->actingAs($this->user())->get(route('operations.console'))->assertForbidden();
        $this->actingAs($this->user('User Management'))->get(route('operations.console'))->assertOk();
    }

    public function test_it_lists_every_operations_screen_in_its_group(): void
    {
        $response = $this->actingAs($this->user('User Management'))
            ->get(route('operations.console'))
            ->assertOk();

        // a section from each group, and the groups themselves
        foreach (['Pipeline', 'Equipment', 'Pricing', 'Finance', 'Partners', 'Scripts'] as $group) {
            $response->assertSee($group);
        }

        foreach (['Departments', 'Module Types', 'Adders', 'Finance Options', 'Sales Partners', 'Call Scripts'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_every_configured_section_points_at_a_real_route(): void
    {
        $sections = app(OperationsConsoleService::class)->sectionsFor($this->user('User Management'));

        // Nothing in the config silently disappears because a route was renamed.
        $this->assertCount(
            collect(config('operations_console.groups'))->flatMap(fn ($group) => $group['sections'])->count(),
            $sections,
            'A configured section names a route that does not exist.'
        );

        foreach ($sections as $section) {
            $this->assertNotEmpty($section['url']);
        }
    }

    public function test_it_opens_the_section_the_url_asks_for(): void
    {
        $this->actingAs($this->user('User Management'))
            ->get(route('operations.console', ['section' => 'sub-contractors']))
            ->assertOk()
            ->assertSee(route('sub.contractor').'?embedded=1', false);
    }

    public function test_an_unknown_section_falls_back_to_the_first_one(): void
    {
        $this->actingAs($this->user('User Management'))
            ->get(route('operations.console', ['section' => 'no-such-screen']))
            ->assertOk()
            ->assertSee('Maintain operational departments and their document length settings.');
    }

    public function test_an_embedded_page_leaves_out_the_sidebar_and_header(): void
    {
        $user = $this->user('User Management');

        $full = $this->actingAs($user)->get(route('departments.list'))->assertOk()->getContent();
        $embedded = $this->actingAs($user)->get(route('departments.list', ['embedded' => 1]))->assertOk()->getContent();

        // The page itself is still there …
        $this->assertStringContainsString('Departments', $embedded);

        // … without the chrome the console already draws around it.
        $this->assertStringContainsString('id="mytask-layout"', $full);
        $this->assertStringContainsString('is-embedded', $embedded);
        $this->assertLessThan(
            substr_count($full, 'ms-link'),
            substr_count($embedded, 'ms-link'),
            'An embedded page should not render the sidebar menu.'
        );
    }

    public function test_a_section_with_a_panel_is_drawn_in_the_page(): void
    {
        Department::create(['name' => 'Permitting', 'document_length' => 3]);

        $response = $this->actingAs($this->user('User Management'))
            ->get(route('operations.console', ['section' => 'departments']))
            ->assertOk();

        // The screen itself, in the console's own page …
        $response->assertSee('Add Department')
            ->assertSee('Permitting')
            ->assertSee('data-ops-panel', false);

        // … not its page in a frame.
        $response->assertDontSee('<iframe', false);
    }

    public function test_a_section_without_a_panel_is_still_opened_in_a_frame(): void
    {
        $this->actingAs($this->user('User Management'))
            ->get(route('operations.console', ['section' => 'sales-partners']))
            ->assertOk()
            ->assertSee('data-ops-frame', false)
            ->assertSee(route('sales.partner.types').'?embedded=1', false);
    }

    public function test_a_panel_keeps_its_links_inside_the_console(): void
    {
        $department = Department::create(['name' => 'Permitting', 'document_length' => 3]);

        $this->actingAs($this->user('User Management'))
            ->get(route('operations.console', ['section' => 'departments']))
            ->assertOk()
            ->assertSee(route('operations.console', ['section' => 'departments', 'id' => $department->id]))
            ->assertDontSee('href="'.route('departments.list', $department->id).'"', false);
    }

    public function test_a_panel_edits_the_record_the_url_names(): void
    {
        $department = Department::create(['name' => 'Permitting', 'document_length' => 3]);

        $this->actingAs($this->user('User Management'))
            ->get(route('operations.console', ['section' => 'departments', 'id' => $department->id]))
            ->assertOk()
            ->assertSee('Update Department')
            ->assertSee(route('department.update', $department->id), false);
    }

    public function test_every_panel_draws_in_the_console_and_on_its_own_page(): void
    {
        $user = $this->user('User Management');
        Department::create(['name' => 'Permitting', 'document_length' => 3]);

        $panelled = collect(app(OperationsConsoleService::class)->sectionsFor($user))
            ->filter(fn ($section) => ! empty($section['panel']));

        $this->assertNotEmpty($panelled, 'No section names a panel.');

        foreach ($panelled as $section) {
            // In the console: drawn in the page, never in a frame.
            $this->actingAs($user)
                ->get(route('operations.console', ['section' => $section['key']]))
                ->assertOk()
                ->assertSee('data-ops-panel', false)
                ->assertDontSee('<iframe', false);

            // And still its own page, on its own URL.
            $this->actingAs($user)->get($section['url'])->assertOk();
        }
    }

    public function test_a_screens_own_page_links_to_itself_not_into_the_console(): void
    {
        $user = $this->user('User Management');
        Department::create(['name' => 'Permitting', 'document_length' => 3]);

        $this->actingAs($user)
            ->get(route('departments.list'))
            ->assertOk()
            ->assertDontSee(route('operations.console', ['section' => 'departments']), false);
    }

    public function test_a_screen_that_edits_by_path_still_edits_by_id_in_the_console(): void
    {
        $user = $this->user('User Management');
        $type = ModuleType::create(['name' => 'REC 400AA', 'internal_module_cost' => 120]);

        // Module Types is the one screen whose own edit route carries the record
        // in the path; in the console it arrives as `?id=` like every other.
        $this->actingAs($user)
            ->get(route('operations.console', ['section' => 'module-types', 'id' => $type->id]))
            ->assertOk()
            ->assertSee('REC 400AA')
            ->assertSee(route('module-types.update', $type->id), false);

        $this->actingAs($user)
            ->get(route('module-types.edit', $type->id))
            ->assertOk()
            ->assertSee(route('module-types.update', $type->id), false);
    }

    public function test_a_panels_scripts_still_run_after_jquery(): void
    {
        $user = $this->user('User Management');

        // A screen's scripts used to sit in @section('scripts'), which the
        // layout yields AFTER jQuery. Inlining them in the panel body put them
        // BEFORE it, and any that touch `$` at load time died - taking the rest
        // of that block with them. A panel keeps them in the section.
        foreach (app(OperationsConsoleService::class)->sectionsFor($user) as $section) {
            if (empty($section['panel'])) {
                continue;
            }

            $view = app(OperationsConsoleService::class)->panelFor($section)->view();
            $source = file_get_contents(app('view')->getFinder()->find($view));

            if (! str_contains($source, '<script')) {
                continue;
            }

            $this->assertStringContainsString(
                "@section('scripts')",
                $source,
                $section['key'].": a panel's scripts belong in @section('scripts')."
            );
            $this->assertLessThan(
                strpos($source, '<script'),
                strpos($source, "@section('scripts')"),
                $section['key'].": the scripts section must open before the first <script>."
            );
        }

        // And prove the layout really does put them in that order.
        $html = $this->actingAs($user)
            ->get(route('operations.console', ['section' => 'finance-options']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'function deleteDealerFee'),
            strpos($html, 'jquery.min.js'),
            'A panel script ran before jQuery was loaded.'
        );
    }

    public function test_every_form_on_a_panel_comes_back_to_the_console(): void
    {
        // Finance Options is the one panel with more than one form on it: the
        // finance option itself plus the three milestone-email ones. A save from
        // any of them has to come back to the console, not just the first.
        $console = route('operations.console', ['section' => 'finance-options']);

        // The per-recipient form only renders when there is a recipient.
        FinanceMilestoneEmailRecipient::create(['email' => 'billing@example.com', 'mode' => 'test', 'is_active' => true]);

        $this->actingAs($this->user('User Management'))
            ->get($console)
            ->assertOk()
            ->assertSeeInOrder(array_fill(0, 4, 'name="ops_section" value="finance-options"'), false);

        $this->actingAs($this->user('User Management'))
            ->from($console)
            ->post(route('finance.milestone.email.mode.update'), [
                'email_mode' => 'production',
                'ops_section' => 'finance-options',
            ])
            ->assertRedirect($console);

        $this->assertDatabaseHas('finance_milestone_settings', ['key' => 'email_mode', 'value' => 'production']);
    }

    public function test_a_save_from_a_pricing_panel_comes_back_to_the_console(): void
    {
        $console = route('operations.console', ['section' => 'office-costs']);

        $this->actingAs($this->user('User Management'))
            ->from($console)
            ->post(route('office-costs.store'), ['cost' => 125.5, 'ops_section' => 'office-costs'])
            ->assertRedirect($console);

        $this->assertDatabaseHas('office_costs', ['cost' => 125.5]);

        // …and without the field it stays on the screen, as it always did.
        $this->actingAs($this->user('User Management'))
            ->post(route('office-costs.store'), ['cost' => 130])
            ->assertRedirect(route('office-costs.index'));
    }

    public function test_a_save_made_in_the_console_comes_back_to_the_console(): void
    {
        $this->actingAs($this->user('User Management'))
            ->post(route('department.store'), [
                'name' => 'Deal Review',
                'document_length' => 2,
                'ops_section' => 'departments',
            ])
            ->assertRedirect(route('operations.console', ['section' => 'departments']));

        $this->assertDatabaseHas('departments', ['name' => 'Deal Review']);
    }

    public function test_a_save_made_on_the_screens_own_page_stays_on_the_screen(): void
    {
        $this->actingAs($this->user('User Management'))
            ->post(route('department.store'), ['name' => 'Deal Review', 'document_length' => 2])
            ->assertRedirect(route('departments.list'));
    }

    public function test_a_rejected_save_comes_back_to_the_console_with_its_error(): void
    {
        Department::create(['name' => 'Permitting', 'document_length' => 3]);

        $user = $this->user('User Management');
        $console = route('operations.console', ['section' => 'departments']);

        // Back where it was typed, not out on the screen's own page …
        $this->actingAs($user)
            ->from($console)
            ->post(route('department.store'), [
                'name' => 'Permitting',
                'document_length' => 2,
                'ops_section' => 'departments',
            ])
            ->assertRedirect($console)
            ->assertSessionHasErrors('name');

        // … and the panel shows why.
        $this->actingAs($user)
            ->get($console)
            ->assertOk()
            ->assertSee('The record already exists.');
    }

    public function test_a_forged_section_cannot_redirect_anywhere_else(): void
    {
        $this->actingAs($this->user('User Management'))
            ->post(route('department.store'), [
                'name' => 'Deal Review',
                'document_length' => 2,
                'ops_section' => 'https://example.com/steal',
            ])
            ->assertRedirect(route('departments.list'));
    }

    public function test_every_configured_panel_is_a_panel_the_console_can_draw(): void
    {
        $console = app(OperationsConsoleService::class);

        foreach ($console->sectionsFor($this->user('User Management')) as $section) {
            if (empty($section['panel'])) {
                continue;
            }

            $panel = $console->panelFor($section);

            $this->assertInstanceOf(OperationsPanel::class, $panel);
            $this->assertTrue(
                view()->exists($panel->view()),
                $section['key'].' names a panel view that does not exist.'
            );
        }
    }

    public function test_the_screens_still_work_on_their_own_urls(): void
    {
        $this->actingAs($this->user('User Management'))
            ->get(route('departments.list'))
            ->assertOk();
    }
}
