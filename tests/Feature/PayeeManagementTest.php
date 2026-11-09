<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Payee;
use App\Models\Project;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Operations -> Payees: add, rename, switch off and delete user-added payees;
 * the five built-ins are locked.
 */
class PayeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'User Management', 'guard_name' => 'web']));
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    private function custom(string $label = 'Solar Fund LLC'): Payee
    {
        return Payee::create(['key' => str($label)->slug('_'), 'label' => $label, 'is_system' => false]);
    }

    public function test_the_page_needs_operations_access(): void
    {
        UserType::firstOrCreate(['name' => 'Admin']);
        $outsider = User::factory()->create(['user_type_id' => 1]);

        $this->actingAs($outsider)->get(route('payees.index'))->assertForbidden();
        $this->actingAs($outsider)->post(route('payees.store'), ['label' => 'X'])->assertForbidden();
    }

    public function test_it_lists_the_built_ins_as_locked_and_is_in_the_console(): void
    {
        $this->custom();

        $this->actingAs($this->admin())
            ->get(route('operations.console', ['section' => 'payees']))
            ->assertOk()
            ->assertSee('data-ops-panel', false)
            ->assertSee('Sales Partner')
            ->assertSee('Sub-Contractor')
            ->assertSee('Solar Fund LLC')
            ->assertSee('Locked');
    }

    public function test_a_payee_can_be_added_once(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('payees.store'), ['label' => '  Solar   Fund LLC '])->assertRedirect(route('payees.index'));
        $this->assertDatabaseHas('payees', ['key' => 'solar_fund_llc', 'label' => 'Solar Fund LLC', 'is_system' => false, 'is_active' => true]);

        $this->actingAs($admin)->post(route('payees.store'), ['label' => 'solar fund llc'])->assertSessionHasErrors('label');
        $this->actingAs($admin)->post(route('payees.store'), ['label' => 'others'])->assertSessionHasErrors('label');
        $this->assertSame(1, Payee::where('is_system', false)->count());
    }

    public function test_a_save_from_the_console_comes_back_to_the_console(): void
    {
        $this->actingAs($this->admin())
            ->post(route('payees.store'), ['label' => 'Solar Fund LLC', 'ops_section' => 'payees'])
            ->assertRedirect(route('operations.console', ['section' => 'payees']));
    }

    public function test_renaming_keeps_the_key_so_stored_rows_follow(): void
    {
        $payee = $this->custom();

        $this->actingAs($this->admin())
            ->put(route('payees.update', $payee), ['label' => 'Solar Fund II'])
            ->assertRedirect();

        $payee->refresh();
        $this->assertSame('solar_fund_llc', $payee->key);
        $this->assertSame('Solar Fund II', $payee->label);
        $this->assertSame('Solar Fund II', AccountTransaction::payeeOptions()['solar_fund_llc']);
    }

    public function test_a_built_in_payee_cannot_be_changed(): void
    {
        $admin = $this->admin();
        $partner = Payee::where('key', 'sales_partner')->firstOrFail();

        $this->actingAs($admin)->put(route('payees.update', $partner), ['label' => 'Partner'])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('payees.toggle', $partner))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('payees.destroy', $partner))->assertSessionHas('error');

        $partner->refresh();
        $this->assertSame('Sales Partner', $partner->label);
        $this->assertTrue($partner->is_active);
    }

    public function test_a_switched_off_payee_is_not_offered_but_still_labels_its_rows(): void
    {
        $payee = $this->custom();

        $this->actingAs($this->admin())->post(route('payees.toggle', $payee))->assertRedirect();
        $this->assertFalse($payee->fresh()->is_active);

        AccountTransaction::forgetPayeeOptions();
        $this->assertArrayNotHasKey('solar_fund_llc', AccountTransaction::payeeChoices());
        $this->assertArrayHasKey('solar_fund_llc', AccountTransaction::payeeChoices('solar_fund_llc'));
        $this->assertSame('Solar Fund LLC', AccountTransaction::payeeOptions()['solar_fund_llc']);

        // Asking for it again by name (milestone editor) switches it back on.
        Payee::findOrCreateByLabel('Solar Fund LLC');
        $this->assertTrue($payee->fresh()->is_active);
    }

    public function test_only_an_unused_payee_can_be_deleted(): void
    {
        $admin = $this->admin();
        $used = $this->custom('Used Fund');
        $unused = $this->custom('Unused Fund');

        $customer = Customer::create([
            'first_name' => 'Rito',
            'last_name' => 'Guzman',
            'street' => '1 Portal Rd',
            'city' => 'Fontana',
            'state' => 'CA',
            'zipcode' => '92335',
            'phone' => '(555) 444-4444',
            'email' => uniqid('payee').'@example.com',
            'sold_date' => '2026-09-01',
            'panel_qty' => 14,
            'inverter_qty' => 1,
        ]);
        $project = Project::create([
            'customer_id' => $customer->id,
            'project_name' => 'Rito Guzman',
            'code' => (string) random_int(9000, 9999),
            'budget' => 40000,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-01',
        ]);

        $transaction = AccountTransaction::create([
            'project_id' => $project->id,
            'payee' => $used->key,
            'milestone' => 'M1',
            'amount' => 100,
            'deduction_amount' => 0,
            'transaction_date' => '2026-11-01',
            'transaction_details' => 'x',
        ]);
        $transaction->delete(); // a deleted row still holds the key

        $this->actingAs($admin)->delete(route('payees.destroy', $used))->assertSessionHas('error');
        $this->assertDatabaseHas('payees', ['id' => $used->id]);

        $this->actingAs($admin)->delete(route('payees.destroy', $unused))->assertSessionHas('success');
        $this->assertDatabaseMissing('payees', ['id' => $unused->id]);
    }
}
