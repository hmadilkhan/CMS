<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserType;
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
            ->get(route('operations.console', ['section' => 'finance-options']))
            ->assertOk()
            ->assertSee(route('finance.option.types').'?embedded=1', false);
    }

    public function test_an_unknown_section_falls_back_to_the_first_one(): void
    {
        $this->actingAs($this->user('User Management'))
            ->get(route('operations.console', ['section' => 'no-such-screen']))
            ->assertOk()
            ->assertSee(route('departments.list').'?embedded=1', false);
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

    public function test_the_screens_still_work_on_their_own_urls(): void
    {
        $this->actingAs($this->user('User Management'))
            ->get(route('departments.list'))
            ->assertOk();
    }
}
