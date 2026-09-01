<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Deleting a customer takes every project of theirs with it, off an id read
 * straight from the request. These tests hold that endpoint — and the customer
 * edit screens — to the list the user is actually shown.
 */
class CustomerAccessScopeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?int $salesPartnerId = null, string ...$permissions): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create([
            'user_type_id' => 1,
            'sales_partner_id' => $salesPartnerId,
        ]);

        $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);

        foreach ($permissions as $permission) {
            $roleModel->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        $user->syncRoles([$roleModel]);

        return $user->fresh();
    }

    private function customerWithProject(?int $salesPartnerId = null): Customer
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sales_partner_id' => $salesPartnerId ?? SalesPartner::create(['name' => 'House Partner'])->id,
        ]);

        Project::create([
            'project_name' => 'Live Project',
            'code' => 'P-'.random_int(1000, 9999),
            'customer_id' => $customer->id,
            'department_id' => 1,
        ]);

        return $customer;
    }

    public function test_deleting_a_customer_needs_an_id_that_exists(): void
    {
        $this->actingAs($this->user('Super Admin'))
            ->postJson(route('delete.customer'), ['id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    public function test_a_sales_person_cannot_delete_another_partners_customer(): void
    {
        $partner = SalesPartner::create(['name' => 'Their Partner']);
        $otherPartner = SalesPartner::create(['name' => 'Someone Elses Partner']);
        $customer = $this->customerWithProject($otherPartner->id);

        $this->actingAs($this->user('Sales Person', $partner->id, 'Delete Customer'))
            ->postJson(route('delete.customer'), ['id' => $customer->id])
            ->assertForbidden();

        $this->assertNotNull(Customer::find($customer->id));
        $this->assertSame(1, Project::where('customer_id', $customer->id)->count());
    }

    public function test_a_sales_person_cannot_open_another_partners_customer(): void
    {
        $partner = SalesPartner::create(['name' => 'Their Partner']);
        $customer = $this->customerWithProject(SalesPartner::create(['name' => 'Other'])->id);

        $this->actingAs($this->user('Sales Person', $partner->id, 'Edit Customer'))
            ->get(route('customers.edit', $customer->id))
            ->assertForbidden();
    }

    public function test_a_sales_person_can_still_reach_their_own_customer(): void
    {
        $partner = SalesPartner::create(['name' => 'Their Partner']);
        $customer = $this->customerWithProject($partner->id);

        $this->actingAs($this->user('Sales Person', $partner->id, 'Edit Customer'))
            ->get(route('customers.edit', $customer->id))
            ->assertOk();
    }

    /** Every other role is shown the whole customer list, so the gate must not narrow them. */
    public function test_a_role_with_an_unnarrowed_list_is_not_blocked(): void
    {
        $customer = $this->customerWithProject();

        $this->actingAs($this->user('Manager', null, 'Edit Customer'))
            ->get(route('customers.edit', $customer->id))
            ->assertOk();
    }

    /**
     * The index view only offers Delete to a permission nobody's role currently
     * holds, so the endpoint that wipes a customer and their projects must ask
     * for it too.
     */
    public function test_deleting_a_customer_needs_the_permission_the_button_is_gated_with(): void
    {
        $customer = $this->customerWithProject();

        $this->actingAs($this->user('Manager', null, 'Create Customer', 'Edit Customer'))
            ->postJson(route('delete.customer'), ['id' => $customer->id])
            ->assertForbidden();

        $this->assertNotNull(Customer::find($customer->id));
    }

    public function test_creating_a_customer_needs_the_permission_the_button_is_gated_with(): void
    {
        $this->actingAs($this->user('Employee'))
            ->get(route('customers.create'))
            ->assertForbidden();
    }

    /**
     * The redline page is Super Admin only now, but the endpoint behind it hands
     * the same base-cost figure back as JSON. It has to ask for the permissions
     * the customer forms ask for.
     */
    public function test_internal_pricing_endpoints_are_not_open_to_every_signed_in_user(): void
    {
        foreach (['get.redline.cost', 'get.dealer.fee', 'get.loan.aprs', 'get.loan.terms', 'get.module.types'] as $route) {
            $this->actingAs($this->user('Employee'))
                ->postJson(route($route), ['inverterType' => 1, 'id' => 1])
                ->assertForbidden();
        }
    }

    public function test_someone_who_can_work_the_customer_form_still_reaches_them(): void
    {
        $manager = $this->user('Manager', null, 'Edit Customer');

        // 404 is the endpoint's own "no rate for this inverter" answer — the point
        // is that it is reached at all.
        $this->actingAs($manager)
            ->postJson(route('get.redline.cost'), ['inverterType' => 1])
            ->assertStatus(404);
    }
}
