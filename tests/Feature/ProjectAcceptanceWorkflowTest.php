<?php

namespace Tests\Feature;

use App\Jobs\AcceptanceEmailJob;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\ModuleType;
use App\Models\Project;
use App\Models\ProjectAcceptance;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectAcceptanceWorkflowTest extends TestCase
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

    private function acceptanceFixture(): array
    {
        $department = Department::create(['id' => 3, 'name' => 'Engineering']);
        $subDepartment = SubDepartment::create([
            'id' => 3,
            'department_id' => $department->id,
            'name' => 'Acceptance Review',
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $employee = Employee::create([
            'name' => 'Acceptance Employee',
            'code' => 'EMP-ACCEPT',
            'email' => 'acceptance.employee@example.com',
            'phone' => '555-404-4040',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($department->id);

        $salesPartner = SalesPartner::create([
            'name' => 'Acceptance Sales Partner',
            'email' => 'acceptance.partner@example.com',
            'phone' => '555-505-5050',
        ]);

        $salesUser = User::factory()->create([
            'name' => 'Acceptance Sales User',
            'user_type_id' => 3,
            'sales_partner_id' => $salesPartner->id,
            'email' => 'acceptance.sales@example.com',
        ]);
        $salesUser->assignRole(Role::firstOrCreate(['name' => 'Sales Person']));

        $financeOption = FinanceOption::create([
            'name' => 'Acceptance Finance',
            'loan_id' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
        ]);

        $inverter = InverterType::create(['name' => 'Acceptance Inverter']);
        InverterTypeRate::create([
            'inverter_type_id' => $inverter->id,
            'base_cost' => 1000,
            'internal_base_cost' => 800,
            'internal_labor_cost' => 200,
        ]);

        $module = ModuleType::create([
            'inverter_type_id' => $inverter->id,
            'name' => 'Acceptance Module',
            'value' => 400,
            'amount' => 125,
            'internal_module_cost' => 90,
        ]);

        $customer = Customer::create([
            'first_name' => 'Acceptance',
            'last_name' => 'Customer',
            'street' => '404 Review Way',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85004',
            'phone' => '555-606-6060',
            'email' => 'acceptance.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
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
            'adders' => 500,
            'commission' => 1500,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 250,
            'module_type_cost' => 125,
            'inverter_base_cost' => 1000,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'sales_partner_user_id' => $salesUser->id,
            'project_name' => 'Acceptance Project',
            'budget' => 25000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'overwrite_base_price' => 100,
            'overwrite_panel_price' => 5,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'user_id' => $employeeUser->id,
        ]);

        return compact('department', 'employee', 'salesPartner', 'salesUser', 'customer', 'project', 'task');
    }

    public function test_project_acceptance_upload_creates_pending_financial_snapshot_and_queues_email(): void
    {
        Storage::fake('public');
        Queue::fake();

        $admin = $this->superAdmin();
        $fixture = $this->acceptanceFixture();

        $this->actingAs($admin)->post(route('project.accept.file'), [
            'mode' => 'post',
            'project_id' => $fixture['project']->id,
            'sales_partner_id' => $fixture['salesPartner']->id,
            'notes' => 'Please review commission details.',
            'file' => UploadedFile::fake()->image('acceptance.png', 600, 400),
        ])
            ->assertOk()
            ->assertSee('Please review commission details.');

        $acceptance = ProjectAcceptance::first();
        $this->assertNotNull($acceptance);
        $this->assertSame(0, $acceptance->action_by);
        $this->assertSame(0, $acceptance->status);
        $this->assertSame('1100.00', $acceptance->inverter_base_price);
        $this->assertSame('130.00', $acceptance->module_qty_price);
        $this->assertSame('1300.00', $acceptance->modules_amount);
        $this->assertSame('Acceptance Inverter', $acceptance->inverter_name);
        Storage::disk('public')->assertExists('project-acceptance/' . $acceptance->image);
        Queue::assertPushed(AcceptanceEmailJob::class);
    }

    public function test_project_acceptance_action_approves_and_locks_adders(): void
    {
        Queue::fake();

        $admin = $this->superAdmin();
        $fixture = $this->acceptanceFixture();

        $acceptance = ProjectAcceptance::create([
            'project_id' => $fixture['project']->id,
            'sales_partner_id' => $fixture['salesPartner']->id,
            'image' => '1727015364-design.jpg',
            'action_by' => 0,
            'status' => 0,
        ]);

        $this->actingAs($admin)->post(route('action.project.acceptance'), [
            'id' => $acceptance->id,
            'projectId' => $fixture['project']->id,
            'mode' => 1,
            'reason' => 'Approved by QA test.',
        ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Project Acceptance Approved',
            ]);

        $this->assertDatabaseHas('project_acceptances', [
            'id' => $acceptance->id,
            'action_by' => $admin->id,
            'status' => 1,
            'reason' => 'Approved by QA test.',
        ]);
        $this->assertDatabaseHas('project_adders_locks', [
            'project_id' => $fixture['project']->id,
            'user_id' => $admin->id,
            'status' => 'locked',
        ]);
        Queue::assertPushed(AcceptanceEmailJob::class);
    }

    public function test_project_acceptance_pdf_generates_file_and_returns_success(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->acceptanceFixture();

        ProjectAcceptance::create([
            'project_id' => $fixture['project']->id,
            'sales_partner_id' => $fixture['salesPartner']->id,
            'image' => '1727015364-design.jpg',
            'action_by' => 0,
            'status' => 0,
        ]);

        $this->actingAs($admin)->get(route('generate.pdf.file', $fixture['project']->id))
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Email has been sent',
            ]);

        $this->assertFileExists(storage_path('app/public/pdfs/project_acceptance_review-' . $fixture['project']->id . '.pdf'));
    }

    public function test_adders_lock_can_be_toggled_manually(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->acceptanceFixture();

        $this->actingAs($admin)->post(route('toggle.adders.lock'), [
            'project_id' => $fixture['project']->id,
            'status' => 'unlocked',
        ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Adders unlocked successfully',
            ]);

        $this->assertDatabaseHas('project_adders_locks', [
            'project_id' => $fixture['project']->id,
            'user_id' => $admin->id,
            'status' => 'unlocked',
        ]);
    }
}
