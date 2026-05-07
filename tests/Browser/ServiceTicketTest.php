<?php

namespace Tests\Browser;

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
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class ServiceTicketTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function setupUsers()
    {
        UserType::create(['name' => 'Admin']);
        
        $admin = User::factory()->create(['user_type_id' => 1]);
        Role::create(['name' => 'Super Admin']);
        $admin->assignRole('Super Admin');

        $serviceManager = User::factory()->create(['user_type_id' => 1]);
        Role::create(['name' => 'Service Manager']);
        $serviceManager->assignRole('Service Manager');

        return compact('admin', 'serviceManager');
    }

    private function setupProject()
    {
        $department = Department::create(['id' => 1, 'name' => 'Deal Review']);
        $subDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $department->id,
            'name' => 'New Deals',
        ]);

        $salesPartner = SalesPartner::create(['name' => 'Ticket Partner']);
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

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        Role::create(['name' => 'Employee']);
        $employeeUser->assignRole('Employee');
        
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
            'project_name' => 'Ticket Test Project',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'budget' => 22000,
            'code' => 'TICKET-1001',
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

    public function test_admin_can_create_service_ticket()
    {
        $users = $this->setupUsers();
        $project = $this->setupProject();

        $this->browse(function (Browser $browser) use ($users, $project) {
            $browser->loginAs($users['admin'])
                    ->visit("/projects/{$project->id}")
                    ->pause(1000)
                    
                    // Create Service Ticket
                    ->clickLink('Create Ticket')
                    ->pause(1000)
                    ->type('subject', 'Roof leak inspection needed')
                    ->select('assigned_to', $users['serviceManager']->id)
                    ->select('priority', 'High')
                    ->type('notes', 'Customer reported a leak near the solar array')
                    
                    ->press('Create Ticket')
                    ->pause(2000)
                    ->assertSee('Ticket created successfully');
        });
    }

    public function test_service_manager_can_add_comment_to_ticket()
    {
        $users = $this->setupUsers();
        $project = $this->setupProject();

        $ticket = ServiceTicket::create([
            'project_id' => $project->id,
            'user_id' => $users['admin']->id,
            'assigned_to' => $users['serviceManager']->id,
            'subject' => 'Test Ticket',
            'priority' => 'Medium',
            'status' => 'Pending',
            'notes' => 'Initial notes',
        ]);

        $this->browse(function (Browser $browser) use ($users, $ticket) {
            $browser->loginAs($users['serviceManager'])
                    ->visit("/service-tickets/{$ticket->id}")
                    ->pause(1000)
                    
                    ->type('comment', 'Technician will inspect tomorrow morning')
                    ->press('Add Comment')
                    ->pause(2000)
                    ->assertSee('Comment added successfully')
                    ->assertSee('Technician will inspect tomorrow morning');
        });
    }

    public function test_service_manager_can_resolve_ticket()
    {
        $users = $this->setupUsers();
        $project = $this->setupProject();

        $ticket = ServiceTicket::create([
            'project_id' => $project->id,
            'user_id' => $users['admin']->id,
            'assigned_to' => $users['serviceManager']->id,
            'subject' => 'Resolve Test Ticket',
            'priority' => 'High',
            'status' => 'Pending',
            'notes' => 'Needs resolution',
        ]);

        $this->browse(function (Browser $browser) use ($users, $ticket) {
            $browser->loginAs($users['serviceManager'])
                    ->visit("/service-tickets/{$ticket->id}")
                    ->pause(1000)
                    
                    ->type('notes', 'Issue resolved and roof sealed')
                    ->select('status', 'Resolved')
                    ->press('Update Ticket')
                    ->pause(2000)
                    ->assertSee('Ticket updated successfully')
                    ->assertSee('Resolved');
        });
    }

    public function test_service_dashboard_shows_all_tickets()
    {
        $users = $this->setupUsers();
        $project = $this->setupProject();

        ServiceTicket::create([
            'project_id' => $project->id,
            'user_id' => $users['admin']->id,
            'assigned_to' => $users['serviceManager']->id,
            'subject' => 'Dashboard Test Ticket',
            'priority' => 'Low',
            'status' => 'Pending',
        ]);

        $this->browse(function (Browser $browser) use ($users) {
            $browser->loginAs($users['serviceManager'])
                    ->visit('/service/dashboard')
                    ->pause(1000)
                    ->assertSee('Dashboard Test Ticket');
        });
    }

    public function test_ticket_form_validation_requires_fields()
    {
        $users = $this->setupUsers();
        $project = $this->setupProject();

        $this->browse(function (Browser $browser) use ($users, $project) {
            $browser->loginAs($users['admin'])
                    ->visit("/projects/{$project->id}")
                    ->pause(1000)
                    ->clickLink('Create Ticket')
                    ->pause(1000)
                    ->press('Create Ticket')
                    ->pause(1000)
                    ->assertPresent('.error, .invalid-feedback, .alert-danger');
        });
    }
}
