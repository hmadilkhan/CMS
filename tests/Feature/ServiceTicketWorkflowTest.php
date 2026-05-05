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
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketComment;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\ServiceTicketCommentAdded;
use App\Notifications\ServiceTicketCreated;
use App\Notifications\ServiceTicketResolved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        UserType::firstOrCreate(['name' => $roleName]);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => $roleName]);
        $user->assignRole($roleName);

        return $user;
    }

    private function project(): Project
    {
        $department = Department::create([
            'id' => 1,
            'name' => 'Deal Review',
        ]);
        $subDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $department->id,
            'name' => 'New Deals',
        ]);

        $salesPartner = SalesPartner::create(['name' => 'Ticket Sales Partner']);
        $financeOption = FinanceOption::create(['name' => 'Cash']);
        $inverter = InverterType::create(['name' => 'Ticket Inverter']);
        InverterTypeRate::create([
            'inverter_type_id' => $inverter->id,
            'base_cost' => 1000,
        ]);
        $module = ModuleType::create([
            'inverter_type_id' => $inverter->id,
            'name' => 'Ticket Module',
            'value' => 400,
            'amount' => 120,
            'internal_module_cost' => 80,
        ]);

        $customer = Customer::create([
            'first_name' => 'Ticket',
            'last_name' => 'Customer',
            'street' => '789 Ticket Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-400-4000',
            'email' => 'ticket.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 8,
            'inverter_type_id' => $inverter->id,
            'module_type_id' => $module->id,
            'inverter_qty' => 1,
            'module_value' => 3200,
        ]);

        CustomerFinance::create([
            'customer_id' => $customer->id,
            'finance_option_id' => $financeOption->id,
            'contract_amount' => 22000,
            'redline_costs' => 15000,
            'adders' => 0,
            'commission' => 1000,
            'dealer_fee' => 0,
            'dealer_fee_amount' => 0,
            'module_type_cost' => 120,
            'inverter_base_cost' => 1000,
        ]);

        $employeeUser = User::factory()->create();
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));
        $employee = Employee::create([
            'id' => 1,
            'name' => 'Ticket Employee',
            'code' => 'TICKET-EMP-001',
            'email' => 'ticket.employee@example.com',
            'phone' => '555-500-5000',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($department->id);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'project_name' => 'Ticket Workflow Project',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'budget' => 22000,
            'code' => 'TICKET-1001',
            'pre_estimated_permit_costs' => 0,
        ]);

        Task::create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'user_id' => $employeeUser->id,
        ]);

        return $project;
    }

    public function test_service_ticket_can_be_created_assigned_commented_resolved_and_viewed(): void
    {
        Notification::fake();

        $admin = $this->userWithRole('Super Admin');
        $assignedUser = $this->userWithRole('Service Manager');
        $project = $this->project();

        $createResponse = $this->actingAs($admin)->from(route('projects.show', $project))->post(route('service-tickets.store'), [
            'project_id' => $project->id,
            'subject' => 'Roof leak inspection needed',
            'assigned_to' => $assignedUser->id,
            'priority' => 'High',
            'notes' => 'Customer reported a leak near the array.',
        ]);

        $createResponse
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHas('success', 'Ticket created successfully');

        $ticket = ServiceTicket::where('subject', 'Roof leak inspection needed')->first();
        $this->assertNotNull($ticket);
        $this->assertSame($admin->id, $ticket->user_id);
        $this->assertSame($assignedUser->id, $ticket->assigned_to);
        $this->assertSame('Pending', $ticket->status);

        Notification::assertSentTo($assignedUser, ServiceTicketCreated::class);

        $commentResponse = $this->actingAs($assignedUser)->from(route('service-tickets.details', $ticket))->post(route('service-tickets.comment', $ticket), [
            'comment' => 'Technician will inspect tomorrow morning.',
        ]);

        $commentResponse
            ->assertRedirect(route('service-tickets.details', $ticket))
            ->assertSessionHas('success', 'Comment added successfully');

        $comment = ServiceTicketComment::where('service_ticket_id', $ticket->id)->first();
        $this->assertNotNull($comment);
        $this->assertSame($assignedUser->id, $comment->user_id);

        Notification::assertSentTo($admin, ServiceTicketCommentAdded::class);

        $updateResponse = $this->actingAs($assignedUser)->from(route('service-tickets.details', $ticket))->put(route('service-tickets.update', $ticket), [
            'notes' => 'Leak resolved and roof sealed.',
            'status' => 'Resolved',
        ]);

        $updateResponse
            ->assertRedirect(route('service-tickets.details', $ticket))
            ->assertSessionHas('success', 'Ticket updated successfully');

        $ticket->refresh();
        $this->assertSame('Resolved', $ticket->status);
        $this->assertSame('Leak resolved and roof sealed.', $ticket->notes);

        Notification::assertSentTo($admin, ServiceTicketResolved::class);

        $this->actingAs($assignedUser)->get(route('service.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('service.admin.dashboard'))->assertOk();
        $this->actingAs($assignedUser)->get(route('service-tickets.details', $ticket))->assertOk();
        $this->actingAs($admin)->get(route('service-tickets.admin-details', $ticket))->assertOk();
    }

    public function test_service_ticket_creation_requires_core_fields(): void
    {
        $admin = $this->userWithRole('Super Admin');

        $response = $this->actingAs($admin)->post(route('service-tickets.store'), []);

        $response->assertSessionHasErrors([
            'project_id',
            'subject',
            'priority',
        ]);
    }
}
