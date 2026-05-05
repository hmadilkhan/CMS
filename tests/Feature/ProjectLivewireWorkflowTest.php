<?php

namespace Tests\Feature;

use App\Livewire\Project\NotesSection;
use App\Livewire\Project\ProjectFields\EditFields;
use App\Models\Customer;
use App\Models\CustomerFinance;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\FinanceOption;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectLivewireWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        Permission::firstOrCreate(['name' => 'Notes Section']);
        $user->assignRole('Super Admin');
        $user->givePermissionTo('Notes Section');

        return $user;
    }

    private function projectFixture(int $departmentId = 1): array
    {
        $department = Department::create([
            'id' => $departmentId,
            'name' => 'Livewire Department ' . $departmentId,
        ]);
        $subDepartment = SubDepartment::create([
            'id' => $departmentId,
            'department_id' => $department->id,
            'name' => 'Livewire Sub Department ' . $departmentId,
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $employee = Employee::create([
            'name' => 'Mention Employee',
            'code' => 'EMP-LIVEWIRE',
            'email' => 'mention.employee@example.com',
            'phone' => '555-444-4444',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($department->id);

        $salesPartner = SalesPartner::create(['name' => 'Livewire Sales Partner']);
        $financeOption = FinanceOption::create([
            'name' => 'Livewire Finance',
            'loan_id' => 0,
            'production_requirements' => 1,
            'positive_variance' => 10,
            'negative_variance' => 10,
            'dealer_fee' => 0,
            'pto_restriction' => 0,
            'no_of_days' => 0,
            'holdback' => 0,
            'dollar_watt_value' => 0,
        ]);

        $customer = Customer::create([
            'first_name' => 'Livewire',
            'last_name' => 'Customer',
            'street' => '101 Component St',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85001',
            'phone' => '555-555-5555',
            'email' => 'livewire.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 10,
            'inverter_type_id' => 1,
            'module_type_id' => 1,
            'inverter_qty' => 1,
            'module_value' => 4000,
            'sold_production_value' => 10000,
        ]);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $financeOption->id,
            'contract_amount' => 25000,
            'redline_costs' => 18000,
            'adders' => 0,
            'commission' => 1500,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'project_name' => 'Livewire Project',
            'budget' => 25000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'user_id' => $employeeUser->id,
        ]);

        return compact('department', 'subDepartment', 'employee', 'project', 'task');
    }

    public function test_notes_section_can_save_note_clean_mention_and_create_mention_record(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = $this->superAdmin();
        $fixture = $this->projectFixture();

        Livewire::actingAs($admin)
            ->test(NotesSection::class, [
                'projectId' => $fixture['project']->id,
                'taskId' => $fixture['task']->id,
                'departmentId' => $fixture['department']->id,
                'projectDepartmentId' => $fixture['project']->department_id,
                'viewSource' => 'crm',
            ])
            ->set('departmentNote', 'Please review @' . $fixture['employee']->id . ':Mention Employee today.')
            ->set('showToCustomer', 1)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('department_notes', [
            'project_id' => $fixture['project']->id,
            'task_id' => $fixture['task']->id,
            'department_id' => $fixture['department']->id,
            'notes' => 'Please review @Mention Employee today.',
            'user_id' => $admin->id,
            'show_to_customer' => 1,
        ]);
        $this->assertDatabaseHas('notes_mentions', [
            'project_id' => $fixture['project']->id,
            'department_id' => $fixture['department']->id,
            'employee_id' => $fixture['employee']->id,
        ]);
    }

    public function test_notes_section_can_edit_cancel_and_delete_note(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = $this->superAdmin();
        $fixture = $this->projectFixture();

        $note = DepartmentNote::create([
            'project_id' => $fixture['project']->id,
            'task_id' => $fixture['task']->id,
            'department_id' => $fixture['department']->id,
            'notes' => 'Original note',
            'user_id' => $admin->id,
            'show_to_customer' => 0,
        ]);

        $component = Livewire::actingAs($admin)
            ->test(NotesSection::class, [
                'projectId' => $fixture['project']->id,
                'taskId' => $fixture['task']->id,
                'departmentId' => $fixture['department']->id,
                'projectDepartmentId' => $fixture['project']->department_id,
                'viewSource' => 'crm',
            ]);

        $component
            ->call('editNote', $note->id)
            ->assertSet('editingNoteId', $note->id)
            ->set('departmentNote', 'Updated note')
            ->set('showToCustomer', 1)
            ->call('updateNote')
            ->assertHasNoErrors()
            ->assertSet('editingNoteId', null);

        $this->assertDatabaseHas('department_notes', [
            'id' => $note->id,
            'notes' => 'Updated note',
            'show_to_customer' => 1,
        ]);

        $component
            ->call('editNote', $note->id)
            ->call('cancelEdit')
            ->assertSet('editingNoteId', null)
            ->call('deleteNote', $note->id);

        $this->assertDatabaseMissing('department_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_edit_fields_updates_department_one_project_fields(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->projectFixture();

        Livewire::actingAs($admin)
            ->test(EditFields::class, ['project' => $fixture['project']])
            ->set('utility_company', 'APS')
            ->set('ntp_approval_date', '2026-05-06')
            ->set('hoa', 'yes')
            ->set('hoa_phone_number', '555-666-7777')
            ->call('updateProjectFields')
            ->assertSet('messageType', 'success')
            ->assertSet('message', 'Data updated successfully!');

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'utility_company' => 'APS',
            'ntp_approval_date' => '2026-05-06',
            'hoa' => 'yes',
            'hoa_phone_number' => '555-666-7777',
        ]);
    }

    public function test_edit_fields_blocks_production_value_outside_allowed_variance(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->projectFixture(departmentId: 3);

        Livewire::actingAs($admin)
            ->test(EditFields::class, ['project' => $fixture['project']])
            ->set('adders_approve_checkbox', 'yes')
            ->set('mpu_required', 'no')
            ->set('production_value_achieved', 5000)
            ->call('updateProjectFields')
            ->assertHasErrors(['production_value_achieved']);

        $this->assertDatabaseMissing('projects', [
            'id' => $fixture['project']->id,
            'production_value_achieved' => 5000,
        ]);
    }
}
