<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\FinanceOption;
use App\Models\FinanceOptionMilestone;
use App\Models\Project;
use App\Models\ProjectFinanceMilestoneEvent;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use App\Models\Zone;
use App\Services\FinanceMilestoneService;
use App\Services\ZoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Finance option milestones as editable rows: any trigger (project date, a
 * zone's approval date, a zone move), any base (contract, customer portion,
 * Solar / Battery TPO credit), any payee.
 */
class FinanceMilestoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        return $user;
    }

    private function fundingManager(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Funding Manager']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'View Zones']));
        $user->assignRole($role);

        return $user;
    }

    private function zone(string $slug): Zone
    {
        return Zone::where('slug', $slug)->firstOrFail();
    }

    private function financeOption(array $milestones): FinanceOption
    {
        $option = FinanceOption::create([
            'name' => 'Wheelhouse Credit Union',
            'loan_id' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
            'milestone_enabled' => 1,
        ]);

        foreach ($milestones as $index => $milestone) {
            $option->milestones()->create($milestone + [
                'key' => 'm_'.$index,
                'sort_order' => $index + 1,
                'is_active' => true,
                'payee' => 'customer',
            ]);
        }

        return $option;
    }

    private function project(FinanceOption $option): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        SubDepartment::firstOrCreate(['id' => 1], ['department_id' => 1, 'name' => 'New Deals']);

        $customer = Customer::create([
            'first_name' => 'Milestone',
            'last_name' => 'Customer',
            'street' => '1 Main St',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '(555) 444-4444',
            'email' => 'milestone.customer@example.com',
            'sales_partner_id' => SalesPartner::create(['name' => 'Milestone Partner'])->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $option->id,
            'contract_amount' => 60000,
            'third_party_credit' => 20000,
            'solar_tpo_credit' => 15000,
            'battery_tpo_credit' => 5000,
            'tpo_split' => true,
            'customer_portion' => 40000,
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
            'department_id' => 1,
            'sub_department_id' => 1,
            'project_name' => 'Milestone Project',
            'code' => '7001',
            'budget' => 60000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        Task::create([
            'project_id' => $project->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'status' => 'In-Progress',
        ]);

        return $project;
    }

    private function financeOptionPayload(array $milestones): array
    {
        return [
            'name' => 'Dynamic Financing',
            'loan_id' => 0,
            'production_requirements' => 0,
            'dealer_fee' => 0,
            'pto_restriction' => 0,
            'holdback' => 0,
            'milestone_enabled' => 1,
            'milestones_submitted' => 1,
            'milestones' => $milestones,
        ];
    }

    private function row(string $label, string $trigger, string $type, $value, string $base = 'contract_amount', string $payee = 'customer', $id = null): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'trigger' => $trigger,
            'amount_type' => $type,
            'amount_value' => $value,
            'amount_base' => $base,
            'payee' => $payee,
            'is_active' => 1,
        ];
    }

    public function test_the_editor_saves_rows_and_keeps_a_rows_key_when_it_is_edited(): void
    {
        $admin = $this->superAdmin();
        $m1 = $this->zone('m1');

        $this->actingAs($admin)->post(route('finance.option.store'), $this->financeOptionPayload([
            $this->row('Deal Review', 'project_created', 'fixed', 1000),
            $this->row('Solar Milestone 1', 'zone_date:'.$m1->id, 'percent', 70, 'solar_tpo_credit', 'others'),
            $this->row('Install', 'project_date:solar_install_date', 'remaining', null),
        ]))->assertSessionHasNoErrors();

        $option = FinanceOption::where('name', 'Dynamic Financing')->firstOrFail();
        $rows = $option->milestones()->get();

        $this->assertSame(['Deal Review', 'Solar Milestone 1', 'Install'], $rows->pluck('label')->all());
        $solar = $rows->firstWhere('label', 'Solar Milestone 1');
        $this->assertSame('zone_date', $solar->trigger_type);
        $this->assertSame($m1->id, (int) $solar->trigger_zone_id);
        $this->assertSame('solar_tpo_credit', $solar->amount_base);
        $this->assertSame('others', $solar->payee);
        $this->assertNull($rows->firstWhere('label', 'Install')->amount_value);

        $dealReview = $rows->firstWhere('label', 'Deal Review');
        $key = $dealReview->key;

        // Edit one, drop one, add one.
        $this->actingAs($admin)->post(route('finance.option.update'), ['id' => $option->id] + $this->financeOptionPayload([
            $this->row('Deal Review Deposit', 'project_created', 'fixed', 1500, 'contract_amount', 'customer', $dealReview->id),
            $this->row('Solar Milestone 1', 'zone_date:'.$m1->id, 'percent', 70, 'solar_tpo_credit', 'others', $solar->id),
            $this->row('Moved into M2', 'zone_entered:'.$this->zone('m2')->id, 'fixed', 250),
        ]))->assertSessionHasNoErrors();

        $dealReview->refresh();
        $this->assertSame($key, $dealReview->key, 'Editing a row must keep its key, or it would fire again.');
        $this->assertSame('Deal Review Deposit', $dealReview->label);
        $this->assertEquals(1500, $dealReview->amount_value);
        $this->assertSoftDeleted(FinanceOptionMilestone::withTrashed()->where('label', 'Install')->first());
        $this->assertSame(3, $option->milestones()->count());
    }

    public function test_the_editor_refuses_a_schedule_that_cannot_add_up(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('finance.option.store'), $this->financeOptionPayload([
            $this->row('A', 'project_created', 'remaining', null),
            $this->row('B', 'project_date:solar_install_date', 'remaining', null),
        ]))->assertSessionHasErrors('milestones.1.amount_type');

        $this->actingAs($admin)->post(route('finance.option.store'), $this->financeOptionPayload([
            $this->row('A', 'project_created', 'percent', 60),
            $this->row('B', 'project_date:solar_install_date', 'percent', 50),
        ]))->assertSessionHasErrors('milestones');

        $this->actingAs($admin)->post(route('finance.option.store'), $this->financeOptionPayload([
            $this->row('A', 'project_date:password', 'fixed', 10),
        ]))->assertSessionHasErrors('milestones.0.trigger');

        $this->actingAs($admin)->post(route('finance.option.store'), $this->financeOptionPayload([
            $this->row('A', 'project_created', 'fixed', null),
        ]))->assertSessionHasErrors('milestones.0.amount_value');

        // The same percent on two DIFFERENT bases is fine.
        $this->actingAs($admin)->post(route('finance.option.store'), $this->financeOptionPayload([
            $this->row('A', 'project_created', 'percent', 70, 'solar_tpo_credit'),
            $this->row('B', 'project_created', 'percent', 70, 'battery_tpo_credit'),
        ]))->assertSessionHasNoErrors();
    }

    public function test_a_new_finance_option_without_a_schedule_gets_the_standard_four(): void
    {
        $this->actingAs($this->superAdmin())->post(route('finance.option.store'), [
            'name' => 'Plain Financing',
            'loan_id' => 0,
            'production_requirements' => 0,
            'dealer_fee' => 0,
            'pto_restriction' => 0,
            'holdback' => 0,
            'milestone_enabled' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['deal_review', 'permit_approval', 'solar_install', 'inspection_approval'],
            FinanceOption::where('name', 'Plain Financing')->firstOrFail()->milestones()->pluck('key')->all()
        );
    }

    public function test_a_tpo_milestone_fires_when_the_m1_approval_date_is_filed(): void
    {
        $m1 = $this->zone('m1');
        $option = $this->financeOption([
            ['label' => 'Solar Milestone 1', 'trigger_type' => 'zone_date', 'trigger_zone_id' => $m1->id, 'amount_type' => 'percent', 'amount_value' => 70, 'amount_base' => 'solar_tpo_credit', 'payee' => 'others'],
            ['label' => 'Battery Milestone 1', 'trigger_type' => 'zone_date', 'trigger_zone_id' => $m1->id, 'amount_type' => 'percent', 'amount_value' => 70, 'amount_base' => 'battery_tpo_credit', 'payee' => 'others'],
            ['label' => 'Solar Milestone 2', 'trigger_type' => 'zone_date', 'trigger_zone_id' => $this->zone('m2')->id, 'amount_type' => 'percent', 'amount_value' => 30, 'amount_base' => 'solar_tpo_credit', 'payee' => 'others'],
        ]);
        $project = $this->project($option);
        app(ZoneService::class)->move($project, $m1->id);

        $this->actingAs($this->fundingManager())
            ->postJson(route('zones.fields'), [
                'project_id' => $project->id,
                'zone_id' => $m1->id,
                'zone_approval_date' => '2026-11-10',
            ])
            ->assertOk();

        $this->assertSame('2026-11-10', app(ZoneService::class)->approvalDate($project, $m1));

        $transactions = AccountTransaction::where('project_id', $project->id)->orderBy('id')->get();
        $this->assertSame(['Solar Milestone 1', 'Battery Milestone 1'], $transactions->pluck('milestone')->all());
        $this->assertEquals([10500, 3500], $transactions->pluck('amount')->map(fn ($a) => (float) $a)->all());
        $this->assertSame(['others', 'others'], $transactions->pluck('payee')->all());
        $this->assertSame('2026-11-10', substr((string) $transactions->first()->transaction_date, 0, 10));

        // Saving the date again raises nothing new.
        $this->actingAs($this->fundingManager())
            ->postJson(route('zones.fields'), [
                'project_id' => $project->id,
                'zone_id' => $m1->id,
                'zone_approval_date' => '2026-11-12',
            ])
            ->assertOk();

        $this->assertSame(2, AccountTransaction::where('project_id', $project->id)->count());
    }

    public function test_the_approval_date_can_only_be_filed_from_the_zone_the_project_is_in(): void
    {
        $option = $this->financeOption([]);
        $project = $this->project($option);
        app(ZoneService::class)->move($project, $this->zone('m1')->id);

        $this->actingAs($this->fundingManager())
            ->postJson(route('zones.fields'), [
                'project_id' => $project->id,
                'zone_id' => $this->zone('m2')->id,
                'zone_approval_date' => '2026-11-10',
            ])
            ->assertStatus(422);

        $this->assertNull(app(ZoneService::class)->approvalDate($project, $this->zone('m2')));
    }

    public function test_remaining_ignores_milestones_paid_on_another_base(): void
    {
        $m1 = $this->zone('m1');
        $option = $this->financeOption([
            ['label' => 'Deal Review', 'trigger_type' => 'project_created', 'amount_type' => 'fixed', 'amount_value' => 1000, 'amount_base' => 'customer_portion'],
            ['label' => 'Solar Milestone 1', 'trigger_type' => 'zone_date', 'trigger_zone_id' => $m1->id, 'amount_type' => 'percent', 'amount_value' => 70, 'amount_base' => 'solar_tpo_credit', 'payee' => 'others'],
            ['label' => 'Inspection Approval Date', 'trigger_type' => 'project_date', 'trigger_field' => 'inspection_approval_date', 'amount_type' => 'remaining', 'amount_value' => null, 'amount_base' => 'customer_portion'],
        ]);
        $project = $this->project($option);
        $service = app(FinanceMilestoneService::class);

        $service->triggerProjectCreated($project);
        app(ZoneService::class)->setApprovalDate($project, $m1, '2026-11-10');

        $project->forceFill(['inspection_approval_date' => '2026-11-20'])->save();
        $service->triggerDateMilestones($project->refresh(), ['inspection_approval_date']);

        $this->assertEquals(
            ['Deal Review' => 1000, 'Solar Milestone 1' => 10500, 'Inspection Approval Date' => 39000],
            AccountTransaction::where('project_id', $project->id)->pluck('amount', 'milestone')->map(fn ($a) => (float) $a)->all(),
            'The customer pays the rest of the customer portion; the TPO credit comes off nothing.'
        );
    }

    public function test_a_moved_into_zone_milestone_fires_once_and_the_backfill_never_fires_it(): void
    {
        $m2 = $this->zone('m2');
        $option = $this->financeOption([
            ['label' => 'Reached M2', 'trigger_type' => 'zone_entered', 'trigger_zone_id' => $m2->id, 'amount_type' => 'fixed', 'amount_value' => 250, 'amount_base' => 'contract_amount'],
        ]);

        $placed = $this->project($option);
        app(ZoneService::class)->backfill($placed, $m2);
        $this->assertSame(0, AccountTransaction::where('project_id', $placed->id)->count());

        $project = $this->project($option);
        $zones = app(ZoneService::class);
        $zones->move($project, $m2->id);
        $zones->move($project->refresh(), $this->zone('m1')->id);
        $zones->move($project->refresh(), $m2->id);

        $this->assertSame(1, AccountTransaction::where('project_id', $project->id)->count());
        $this->assertEquals(250, AccountTransaction::where('project_id', $project->id)->value('amount'));
    }

    public function test_a_tpo_milestone_on_a_customer_with_no_split_credit_raises_nothing(): void
    {
        $m1 = $this->zone('m1');
        $option = $this->financeOption([
            ['label' => 'Solar Milestone 1', 'trigger_type' => 'zone_date', 'trigger_zone_id' => $m1->id, 'amount_type' => 'percent', 'amount_value' => 70, 'amount_base' => 'solar_tpo_credit', 'payee' => 'others'],
        ]);
        $project = $this->project($option);
        $project->customer->finances->update(['solar_tpo_credit' => 0, 'tpo_split' => false]);

        app(ZoneService::class)->setApprovalDate($project->refresh(), $m1, '2026-11-10');

        $this->assertSame(0, AccountTransaction::where('project_id', $project->id)->count());
        $this->assertSame(0, ProjectFinanceMilestoneEvent::where('project_id', $project->id)->count());
    }

    public function test_an_edited_amount_is_what_fires(): void
    {
        $option = $this->financeOption([
            ['label' => 'Permit Approval Date', 'trigger_type' => 'project_date', 'trigger_field' => 'permitting_approval_date', 'amount_type' => 'percent', 'amount_value' => 50, 'amount_base' => 'contract_amount'],
        ]);
        $option->milestones()->update(['amount_value' => 10]);
        $project = $this->project($option);

        $project->forceFill(['permitting_approval_date' => '2026-11-01'])->save();
        app(FinanceMilestoneService::class)->triggerDateMilestones($project->refresh(), ['permitting_approval_date']);

        $this->assertEquals(6000, AccountTransaction::where('project_id', $project->id)->value('amount'));
    }

    public function test_the_trigger_list_follows_the_zones_table(): void
    {
        Zone::create(['slug' => 'm3', 'name' => 'M3', 'order' => 6, 'show_in_list' => true, 'has_approval_date' => true]);

        $options = app(FinanceMilestoneService::class)->triggerOptions();
        $m3 = $this->zone('m3');

        $this->assertSame('M3 Approval Date', $options['Zone Dates']['zone_date:'.$m3->id]);
        $this->assertSame('Moved into M3', $options['Zone Moves']['zone_entered:'.$m3->id]);
        $this->assertArrayNotHasKey('zone_date:'.$this->zone('ntp')->id, $options['Zone Dates']);
        $this->assertArrayNotHasKey('zone_entered:'.$this->zone('archived')->id, $options['Zone Moves']);
    }

    public function test_an_option_seeded_with_only_tpo_rows_gets_the_standard_four_ahead_of_them(): void
    {
        $m1 = $this->zone('m1');
        $tpoOnly = $this->financeOption([]);
        $tpoOnly->update(['name' => 'Prepaid PPA']);
        $tpoOnly->milestones()->create(['key' => 'solar_tpo_m1', 'label' => 'Solar Milestone 1', 'trigger_type' => 'zone_date', 'trigger_zone_id' => $m1->id, 'amount_type' => 'percent', 'amount_value' => 70, 'amount_base' => 'solar_tpo_credit', 'payee' => 'others', 'sort_order' => 1]);

        $complete = FinanceOption::create(['name' => 'Complete', 'loan_id' => 0, 'holdback' => 0, 'dollar_watt_value' => 0, 'pto_restriction' => 0, 'no_of_days' => 0]);
        $complete->milestones()->create(['key' => 'deal_review', 'label' => 'Deal Review', 'trigger_type' => 'project_created', 'amount_type' => 'fixed', 'amount_value' => 1000, 'sort_order' => 1]);
        $complete->milestones()->create(['key' => 'solar_tpo_m1', 'label' => 'Solar Milestone 1', 'trigger_type' => 'zone_date', 'trigger_zone_id' => $m1->id, 'amount_type' => 'percent', 'amount_value' => 70, 'amount_base' => 'solar_tpo_credit', 'payee' => 'others', 'sort_order' => 2]);

        (require database_path('migrations/2026_11_03_000007_seed_default_milestones_beside_tpo_rows.php'))->up();

        $this->assertSame(
            ['deal_review', 'permit_approval', 'solar_install', 'inspection_approval', 'solar_tpo_m1'],
            $tpoOnly->milestones()->pluck('key')->all()
        );
        $this->assertSame('customer_portion', $tpoOnly->milestones()->where('key', 'inspection_approval')->value('amount_base'));
        $this->assertSame(['deal_review', 'solar_tpo_m1'], $complete->milestones()->pluck('key')->all());
    }

    public function test_the_m1_tab_shows_its_approval_date(): void
    {
        $option = $this->financeOption([]);
        $project = $this->project($option);
        $m1 = $this->zone('m1');
        app(ZoneService::class)->move($project, $m1->id);
        app(ZoneService::class)->setApprovalDate($project, $m1, '2026-11-10');

        Role::firstOrCreate(['name' => 'Service Manager']);
        $user = $this->fundingManager();
        $user->roles->first()->givePermissionTo(Permission::firstOrCreate(['name' => 'View Project']));

        $this->actingAs($user)
            ->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertSee('M1 Approval Date')
            ->assertSee('id="zone-field-'.$m1->id.'-zone_approval_date"', false)
            ->assertSee('value="2026-11-10"', false);
    }
}
