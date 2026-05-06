<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\ModuleType;
use App\Models\OfficeCost;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntakeFormWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function salesPerson(SalesPartner $salesPartner): User
    {
        UserType::firstOrCreate(['name' => 'Sales Partner']);

        $user = User::factory()->create([
            'user_type_id' => 3,
            'sales_partner_id' => $salesPartner->id,
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Sales Person']));

        return $user;
    }

    private function seedIntakeBasics(): array
    {
        $dealReview = Department::create(['id' => 1, 'name' => 'Deal Review']);
        $siteSurvey = Department::create(['id' => 2, 'name' => 'Site Survey']);

        $dealSubDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $dealReview->id,
            'name' => 'New Deals',
        ]);
        $siteSurveySubDepartment = SubDepartment::create([
            'id' => 3,
            'department_id' => $siteSurvey->id,
            'name' => 'Site Survey',
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $employee = Employee::create([
            'id' => 1,
            'name' => 'Intake Employee',
            'code' => 'EMP-INTAKE',
            'email' => 'intake.employee@example.com',
            'phone' => '555-707-7070',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($dealReview->id);

        $salesPartner = SalesPartner::create(['name' => 'Intake Sales Partner']);
        $salesUser = $this->salesPerson($salesPartner);

        $financeOption = FinanceOption::create([
            'id' => 1,
            'name' => 'Cash',
            'loan_id' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
        ]);

        $inverter = InverterType::create(['name' => 'Intake Inverter']);
        InverterTypeRate::create([
            'inverter_type_id' => $inverter->id,
            'base_cost' => 1000,
            'internal_base_cost' => 800,
            'internal_labor_cost' => 200,
        ]);

        $module = ModuleType::create([
            'inverter_type_id' => $inverter->id,
            'name' => 'Intake Module',
            'value' => 400,
            'amount' => 120,
            'internal_module_cost' => 80,
        ]);

        OfficeCost::create(['cost' => 1200]);

        return compact(
            'dealReview',
            'siteSurvey',
            'dealSubDepartment',
            'siteSurveySubDepartment',
            'employee',
            'salesPartner',
            'salesUser',
            'financeOption',
            'inverter',
            'module'
        );
    }

    private function validPayload(array $basics): array
    {
        return [
            'first_name' => 'Intake',
            'last_name' => 'Customer',
            'street' => '505 Intake Dr',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85005',
            'phone' => '555-808-8080',
            'email' => 'intake.customer@example.com',
            'panel_qty' => 10,
            'sold_date' => now()->toDateString(),
            'sales_partner_id' => $basics['salesPartner']->id,
            'sales_partner_user_id' => $basics['salesUser']->id,
            'inverter_type_id' => $basics['inverter']->id,
            'module_type_id' => $basics['module']->id,
            'inverter_qty' => 1,
            'module_qty' => 4000,
            'finance_option_id' => $basics['financeOption']->id,
            'contract_amount' => 25000,
            'redline_costs' => 18000,
            'commission' => 1500,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'adders_amount' => 0,
            'overwrite_base_price' => 0,
            'overwrite_panel_price' => 0,
            'sold_production_value' => 5000,
            'adu' => 0,
            'notes' => 'Intake test customer.',
        ];
    }

    public function test_sales_person_can_create_intake_customer_project_finance_and_task(): void
    {
        $basics = $this->seedIntakeBasics();

        $response = $this->actingAs($basics['salesUser'])->post(route('intake-form.store'), $this->validPayload($basics));

        $response->assertRedirect(route('intake-form.index'));

        $customer = Customer::where('email', 'intake.customer@example.com')->first();
        $this->assertNotNull($customer);

        $this->assertDatabaseHas('customer_finances', [
            'customer_id' => $customer->id,
            'finance_option_id' => $basics['financeOption']->id,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);

        $project = Project::where('customer_id', $customer->id)->first();
        $this->assertNotNull($project);
        $this->assertSame('Intake-Customer', $project->project_name);
        $this->assertSame($basics['dealReview']->id, $project->department_id);
        $this->assertSame($basics['dealSubDepartment']->id, $project->sub_department_id);
        $this->assertSame(1200.0, (float) $project->office_cost);
        $this->assertSame('1001', $project->code);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'employee_id' => 1,
            'department_id' => $basics['dealReview']->id,
            'sub_department_id' => $basics['dealSubDepartment']->id,
            'status' => 'In-Progress',
        ]);
    }

    public function test_intake_schedule_survey_routes_project_to_site_survey_and_uploads_documents(): void
    {
        Storage::fake('public');

        $basics = $this->seedIntakeBasics();
        $payload = array_merge($this->validPayload($basics), [
            'email' => 'survey.intake.customer@example.com',
            'schedule_survey' => 1,
            'utility_company' => 'APS',
            'ntp_approval_date' => '2026-05-06',
            'hoa' => 'yes',
            'hoa_phone_number' => '555-909-9090',
            'contract_pdf' => UploadedFile::fake()->create('contract.pdf', 20, 'application/pdf'),
            'cpuc_pdf' => UploadedFile::fake()->create('cpuc.pdf', 20, 'application/pdf'),
            'disclosure_document' => UploadedFile::fake()->create('disclosure.pdf', 20, 'application/pdf'),
            'electronic_signature' => UploadedFile::fake()->create('signature.pdf', 20, 'application/pdf'),
            'utility_bill' => UploadedFile::fake()->create('utility bill.pdf', 20, 'application/pdf'),
        ]);

        $response = $this->actingAs($basics['salesUser'])->post(route('intake-form.store'), $payload);

        $customer = Customer::where('email', 'survey.intake.customer@example.com')->first();
        $this->assertNotNull($customer);

        $project = Project::where('customer_id', $customer->id)->first();
        $this->assertNotNull($project);
        $response->assertRedirect('/site-surveys/schedule/' . $project->id);

        $this->assertSame($basics['siteSurvey']->id, $project->department_id);
        $this->assertSame($basics['siteSurveySubDepartment']->id, $project->sub_department_id);
        $this->assertSame('APS', $project->utility_company);
        $this->assertSame('yes', $project->hoa);
        $this->assertSame('555-909-9090', $project->hoa_phone_number);

        $this->assertSame(5, ProjectFile::where('project_id', $project->id)->count());
        foreach (ProjectFile::where('project_id', $project->id)->get() as $projectFile) {
            Storage::disk('public')->assertExists('projects/' . $projectFile->filename);
        }
    }

    public function test_intake_form_requires_core_customer_finance_fields(): void
    {
        $basics = $this->seedIntakeBasics();

        $response = $this->actingAs($basics['salesUser'])->post(route('intake-form.store'), []);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'street',
            'city',
            'state',
            'zipcode',
            'panel_qty',
            'sold_date',
            'sales_partner_id',
            'finance_option_id',
            'contract_amount',
            'redline_costs',
            'commission',
            'dealer_fee',
            'sales_partner_user_id',
        ]);
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, CustomerFinance::count());
        $this->assertSame(0, Project::count());
        $this->assertSame(0, Task::count());
    }
}
