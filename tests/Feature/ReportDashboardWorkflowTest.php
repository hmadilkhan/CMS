<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\WidgetsCards;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\Email;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\ModuleType;
use App\Models\Project;
use App\Models\ProjectFollowUp;
use App\Models\SalesPartner;
use App\Models\ServiceTicket;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportDashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        return $user;
    }

    private function reportFixture(): array
    {
        $department = Department::create(['id' => 1, 'name' => 'Reports Department']);
        $subDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $department->id,
            'name' => 'Reports Sub Department',
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $employee = Employee::create([
            'name' => 'Reports Employee',
            'code' => 'EMP-REPORTS',
            'email' => 'reports.employee@example.com',
            'phone' => '555-121-2121',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($department->id);

        $partner = SalesPartner::create(['name' => 'Reports Sales Partner']);
        $salesUser = User::factory()->create([
            'name' => 'Reports Sales User',
            'user_type_id' => 3,
            'sales_partner_id' => $partner->id,
        ]);
        $salesUser->assignRole(Role::firstOrCreate(['name' => 'Sales Person']));

        $financeOption = FinanceOption::create([
            'name' => 'Reports Finance',
            'loan_id' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
        ]);

        $inverter = InverterType::create(['name' => 'Reports Inverter']);
        InverterTypeRate::create([
            'inverter_type_id' => $inverter->id,
            'base_cost' => 1000,
            'internal_base_cost' => 800,
            'internal_labor_cost' => 200,
        ]);
        $module = ModuleType::create([
            'inverter_type_id' => $inverter->id,
            'name' => 'Reports Module',
            'value' => 400,
            'amount' => 120,
            'internal_module_cost' => 80,
        ]);

        $customer = Customer::create([
            'first_name' => 'Report',
            'last_name' => 'Customer',
            'street' => '606 Report Rd',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85006',
            'phone' => '555-232-3232',
            'email' => 'report.customer@example.com',
            'sales_partner_id' => $partner->id,
            'sold_date' => '2026-05-01',
            'panel_qty' => 10,
            'inverter_type_id' => $inverter->id,
            'module_type_id' => $module->id,
            'inverter_qty' => 1,
            'module_value' => 4000,
        ]);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $financeOption->id,
            'contract_amount' => 25000,
            'redline_costs' => 18000,
            'adders' => 1000,
            'commission' => 1500,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 500,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'sales_partner_user_id' => $salesUser->id,
            'project_name' => 'Report Project',
            'budget' => 25000,
            'solar_install_date' => '2026-05-03',
            'actual_permit_fee' => 500,
            'actual_labor_cost' => 2000,
            'actual_material_cost' => 3000,
            'office_cost' => 1000,
            'overwrite_base_price' => 100,
            'overwrite_panel_price' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'user_id' => $employeeUser->id,
        ]);

        $outsideCustomer = Customer::create([
            'first_name' => 'Outside',
            'last_name' => 'Customer',
            'street' => '999 Outside Rd',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85007',
            'phone' => '555-343-4343',
            'email' => 'outside.customer@example.com',
            'sales_partner_id' => $partner->id,
            'sold_date' => '2026-01-01',
            'panel_qty' => 8,
            'inverter_type_id' => $inverter->id,
            'module_type_id' => $module->id,
            'inverter_qty' => 1,
            'module_value' => 3200,
        ]);
        CustomerFinance::create([
            'customer_id' => $outsideCustomer->id,
            'finance_option_id' => $financeOption->id,
            'contract_amount' => 9999,
            'redline_costs' => 7000,
            'adders' => 0,
            'commission' => 999,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);
        Project::create([
            'customer_id' => $outsideCustomer->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'sales_partner_user_id' => $salesUser->id,
            'project_name' => 'Outside Report Project',
            'solar_install_date' => '2026-01-05',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        return compact('department', 'subDepartment', 'employeeUser', 'employee', 'partner', 'salesUser', 'customer', 'project', 'task');
    }

    public function test_forecast_profitability_and_override_reports_filter_and_calculate_seeded_data(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->reportFixture();

        $this->actingAs($admin)->post('/forecast-report', [
            'from' => '2026-05-01',
            'to' => '2026-05-31',
        ])
            ->assertOk()
            ->assertSee('Report Customer')
            ->assertSee('$ 25,000.00')
            ->assertSee('$ 23,000.00')
            ->assertDontSee('Outside Customer');

        $this->actingAs($admin)->post('/reports-profilt', [
            'from' => '2026-05-01',
            'to' => '2026-05-31',
            'sales_partner_id' => '',
        ])
            ->assertOk()
            ->assertSee('Report Customer')
            ->assertSee('$ 12,500.00')
            ->assertSee('65.79%')
            ->assertDontSee('Outside Customer');

        $this->actingAs($admin)->post('/override-report', [
            'from' => '2026-05-01',
            'to' => '2026-05-31',
            'sales_partner_id' => $fixture['partner']->id,
        ])
            ->assertOk()
            ->assertSee('Reports Sales User')
            ->assertSee('$ 150')
            ->assertSee('$ 17850')
            ->assertDontSee('Outside Customer');
    }

    public function test_transaction_report_filters_by_date_and_sales_person_role(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->reportFixture();

        AccountTransaction::create([
            'project_id' => $fixture['project']->id,
            'payee' => 'sales_partner',
            'milestone' => 'NTP',
            'amount' => 1000,
            'deduction_amount' => 100,
            'transaction_date' => '2026-05-05',
            'transaction_details' => 'Sales partner payment.',
        ]);
        AccountTransaction::create([
            'project_id' => $fixture['project']->id,
            'payee' => 'others',
            'milestone' => 'Other',
            'amount' => 500,
            'deduction_amount' => 0,
            'transaction_date' => '2026-05-06',
            'transaction_details' => 'Other payment.',
        ]);
        AccountTransaction::create([
            'project_id' => $fixture['project']->id,
            'payee' => 'sales_partner',
            'milestone' => 'Old',
            'amount' => 999,
            'deduction_amount' => 0,
            'transaction_date' => '2026-01-01',
            'transaction_details' => 'Outside date payment.',
        ]);

        $this->actingAs($admin)->post('/transaction-report', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ])
            ->assertOk()
            ->assertSee('Sales partner payment.')
            ->assertSee('Other payment.')
            ->assertSee('$ 1,500.00')
            ->assertDontSee('Outside date payment.');

        $this->actingAs($fixture['salesUser'])->post('/transaction-report', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ])
            ->assertOk()
            ->assertSee('Sales partner payment.')
            ->assertSee('$ 900.00')
            ->assertDontSee('Other payment.')
            ->assertDontSee('Outside date payment.');
    }

    public function test_employee_dashboard_shows_assigned_email_followups_and_service_tickets(): void
    {
        $fixture = $this->reportFixture();

        Email::create([
            'project_id' => $fixture['project']->id,
            'department_id' => $fixture['department']->id,
            'customer_id' => $fixture['customer']->id,
            'subject' => 'Unread customer email',
            'body' => 'Customer reply body',
            'is_view' => 1,
        ]);
        ProjectFollowUp::create([
            'project_id' => $fixture['project']->id,
            'employee_id' => $fixture['employee']->id,
            'created_by' => $fixture['employeeUser']->id,
            'department_id' => $fixture['department']->id,
            'sub_department_id' => $fixture['subDepartment']->id,
            'follow_up_date' => '2026-05-07',
            'notes' => 'Call customer for report follow-up.',
            'status' => 'Pending',
        ]);
        ServiceTicket::create([
            'project_id' => $fixture['project']->id,
            'user_id' => $fixture['employeeUser']->id,
            'subject' => 'Dashboard service ticket',
            'assigned_to' => $fixture['employeeUser']->id,
            'priority' => 'High',
            'notes' => 'Ticket should appear on dashboard.',
            'status' => 'Pending',
        ]);

        $this->actingAs($fixture['employeeUser'])->get(route('dashboard'))
            ->assertOk()
            ->assertSee('report.customer@example.com')
            ->assertSee('Call customer for report follow-up.')
            ->assertSee('Dashboard service ticket');
    }

    public function test_dashboard_widgets_calculate_revenue_and_commission_totals(): void
    {
        $this->reportFixture();

        Livewire::test(WidgetsCards::class)
            ->assertSee('999')
            ->assertSee('1,250');
    }
}
