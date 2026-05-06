<?php

namespace Tests\Feature;

use App\Jobs\SendEmailJob;
use App\Models\Call;
use App\Models\CallScript;
use App\Models\Customer;
use App\Models\Department;
use App\Models\EmailScript;
use App\Models\EmailType;
use App\Models\Employee;
use App\Models\Project;
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

class ProjectCommunicationWorkflowTest extends TestCase
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

    private function communicationFixture(): array
    {
        $department = Department::create(['id' => 1, 'name' => 'Communication Department']);
        $subDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $department->id,
            'name' => 'Communication Sub Department',
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $employee = Employee::create([
            'name' => 'Communication Employee',
            'code' => 'EMP-COMMS',
            'email' => 'communication.employee@example.com',
            'phone' => '555-101-1010',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($department->id);

        $salesPartner = SalesPartner::create([
            'name' => 'Communication Sales Partner',
            'email' => 'partner@example.com',
            'phone' => '555-202-2020',
        ]);

        $salesUser = User::factory()->create([
            'user_type_id' => 3,
            'sales_partner_id' => $salesPartner->id,
            'email' => 'sales.person@example.com',
        ]);
        $salesUser->assignRole(Role::firstOrCreate(['name' => 'Sales Person']));

        $customer = Customer::create([
            'first_name' => 'Communication',
            'last_name' => 'Customer',
            'street' => '303 Contact Ln',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85003',
            'phone' => '555-303-3030',
            'email' => 'communication.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 11,
            'inverter_type_id' => 1,
            'module_type_id' => 1,
            'inverter_qty' => 1,
            'module_value' => 4400,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'sales_partner_user_id' => $salesUser->id,
            'project_name' => 'Communication Project',
            'budget' => 24000,
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

        return compact('department', 'subDepartment', 'employee', 'salesUser', 'customer', 'project', 'task');
    }

    public function test_project_call_log_can_be_saved_for_current_department(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->communicationFixture();
        $call = new Call();
        $call->name = 'First Call';
        $call->save();

        $this->actingAs($admin)->post(route('projects.call.logs'), [
            'id' => $fixture['project']->id,
            'call_no' => $call->id,
            'notes_1' => 'Spoke with homeowner about next step.',
        ])->assertRedirect(route('projects.show', $fixture['project']->id));

        $this->assertDatabaseHas('project_call_logs', [
            'project_id' => $fixture['project']->id,
            'department_id' => $fixture['department']->id,
            'call_no' => (string) $call->id,
            'notes' => 'Spoke with homeowner about next step.',
            'user_id' => $admin->id,
        ]);
    }

    public function test_call_and_email_script_lookup_returns_matching_department_template(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->communicationFixture();

        $call = new Call();
        $call->name = 'Welcome Call';
        $call->save();
        CallScript::create([
            'call_id' => $call->id,
            'department_id' => $fixture['department']->id,
            'script' => '<p>Welcome call script</p>',
        ]);

        $emailType = new EmailType();
        $emailType->name = 'Welcome Email';
        $emailType->save();
        EmailScript::create([
            'email_type_id' => $emailType->id,
            'department_id' => $fixture['department']->id,
            'script' => '<p>Welcome email script</p>',
        ]);

        $this->actingAs($admin)->post(route('projects.call.script'), [
            'project' => $fixture['project']->id,
            'call' => $call->id,
            'department' => $fixture['department']->id,
        ])
            ->assertOk()
            ->assertSee('Welcome call script', false);

        $this->actingAs($admin)->post(route('projects.email.script'), [
            'project' => $fixture['project']->id,
            'emailType' => $emailType->id,
            'department' => $fixture['department']->id,
        ])
            ->assertOk()
            ->assertSee('Welcome email script', false);
    }

    public function test_send_email_route_stores_attachments_and_queues_email_job_with_sales_person_cc(): void
    {
        Storage::fake('public');
        Queue::fake();

        $admin = $this->superAdmin();
        $fixture = $this->communicationFixture();
        $attachment = UploadedFile::fake()->create('customer doc.pdf', 25, 'application/pdf');

        $this->actingAs($admin)->post(route('send.email'), [
            'subject' => 'Project update',
            'content' => '<p>Your project is moving forward.</p>',
            'project_id' => $fixture['project']->id,
            'department_id' => $fixture['department']->id,
            'customer_id' => $fixture['customer']->id,
            'customer_email' => $fixture['customer']->email,
            'ccEmails' => 'office@example.com, support@example.com',
            'images' => [$attachment],
        ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Email has been sent',
                'ccEmails' => [
                    'office@example.com',
                    'support@example.com',
                    $fixture['salesUser']->email,
                ],
            ]);

        Queue::assertPushed(SendEmailJob::class);

        $files = Storage::disk('public')->files('emails');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('customerdoc.pdf', $files[0]);
    }
}
