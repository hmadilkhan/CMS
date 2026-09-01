<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Each report is offered by one sidebar link wrapped in its own @can. The routes
 * behind those links, and the exports inside the report pages, carried nothing —
 * profitability, override and transaction figures were a URL away.
 */
class ReportAccessTest extends TestCase
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

    public static function reportProvider(): array
    {
        return [
            'profitability' => ['reports.profit', 'Profitability Report'],
            'forecast' => ['forecast.report', 'Forecast Report'],
            'override' => ['override.report', 'Override Report'],
            'transaction' => ['get.transaction.report', 'Transaction Report'],
        ];
    }

    /** @dataProvider reportProvider */
    public function test_a_report_needs_its_own_permission(string $route, string $permission): void
    {
        $this->actingAs($this->user())->get(route($route))->assertForbidden();
    }

    /** @dataProvider reportProvider */
    public function test_the_permission_holder_reaches_the_report(string $route, string $permission): void
    {
        $this->actingAs($this->user($permission))->get(route($route))->assertOk();
    }

    public function test_one_report_permission_does_not_open_the_others(): void
    {
        $user = $this->user('Forecast Report');

        $this->actingAs($user)->get(route('forecast.report'))->assertOk();
        $this->actingAs($user)->get(route('reports.profit'))->assertForbidden();
        $this->actingAs($user)->get(route('override.report'))->assertForbidden();
        $this->actingAs($user)->get(route('get.transaction.report'))->assertForbidden();
    }

    public function test_the_exports_are_gated_with_their_report(): void
    {
        $this->actingAs($this->user())
            ->get(route('profitable.report.excel.export', ['from' => '2026-01-01', 'to' => '2026-02-01']))
            ->assertForbidden();

        $this->actingAs($this->user())
            ->get(route('transaction.report.pdf.export', ['start_date' => '2026-01-01', 'end_date' => '2026-02-01']))
            ->assertForbidden();
    }
}
