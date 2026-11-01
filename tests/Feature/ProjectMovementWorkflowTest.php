<?php

namespace Tests\Feature;

use App\Jobs\SendProjectAssignedEmailNotificationJob;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectFollowUp;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use App\Notifications\ProjectAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectMovementWorkflowTest extends TestCase
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

    private function movementFixture(bool $withTargetEmployee = true): array
    {
        $sourceDepartment = Department::create(['id' => 1, 'name' => 'Deal Review']);
        $targetDepartment = Department::create(['id' => 2, 'name' => 'Engineering']);

        $sourceSubDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $sourceDepartment->id,
            'name' => 'New Deals',
        ]);
        $targetSubDepartment = SubDepartment::create([
            'id' => 2,
            'department_id' => $targetDepartment->id,
            'name' => 'Design Review',
        ]);

        $sourceEmployeeUser = User::factory()->create(['user_type_id' => 2]);
        $sourceEmployeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $sourceEmployee = Employee::create([
            'name' => 'Source Employee',
            'code' => 'EMP-SOURCE',
            'email' => 'source.employee@example.com',
            'phone' => '555-111-1111',
            'user_id' => $sourceEmployeeUser->id,
        ]);
        $sourceEmployee->department()->attach($sourceDepartment->id);

        $targetEmployee = null;
        if ($withTargetEmployee) {
            $targetManagerUser = User::factory()->create(['user_type_id' => 2]);
            $targetManagerUser->assignRole(Role::firstOrCreate(['name' => 'Manager']));

            $targetEmployee = Employee::create([
                'name' => 'Target Manager',
                'code' => 'EMP-TARGET',
                'email' => 'target.manager@example.com',
                'phone' => '555-222-2222',
                'user_id' => $targetManagerUser->id,
            ]);
            $targetEmployee->department()->attach($targetDepartment->id);
        }

        $salesPartner = SalesPartner::create(['name' => 'Movement Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Movement',
            'last_name' => 'Customer',
            'street' => '789 Movement Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-333-3333',
            'email' => 'movement.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 14,
            'inverter_type_id' => 1,
            'module_type_id' => 1,
            'inverter_qty' => 1,
            'module_value' => 5600,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $sourceDepartment->id,
            'sub_department_id' => $sourceSubDepartment->id,
            'project_name' => 'Movement Project',
            'budget' => 28000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'ahj' => 'Movement AHJ',
            // A Deal Review required field since the Utility Bill chase; "yes"
            // means the bill is already in, so no chase opens.
            'utility_bill_required' => 'yes',
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'employee_id' => $sourceEmployee->id,
            'department_id' => $sourceDepartment->id,
            'sub_department_id' => $sourceSubDepartment->id,
            'user_id' => $sourceEmployeeUser->id,
        ]);

        return compact(
            'sourceDepartment',
            'targetDepartment',
            'sourceSubDepartment',
            'targetSubDepartment',
            'sourceEmployee',
            'targetEmployee',
            'project',
            'task'
        );
    }

    public function test_project_move_completes_current_task_and_assigns_next_task_to_target_department_manager(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->movementFixture();

        $response = $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
            'notes' => 'Ready for engineering.',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'Project Moved Successfully',
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'department_id' => $fixture['targetDepartment']->id,
            'sub_department_id' => $fixture['targetSubDepartment']->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $fixture['task']->id,
            'status' => 'Completed',
            'notes' => 'Ready for engineering.',
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $fixture['project']->id,
            'employee_id' => $fixture['targetEmployee']->id,
            'department_id' => $fixture['targetDepartment']->id,
            'sub_department_id' => $fixture['targetSubDepartment']->id,
            'status' => 'In-Progress',
            'user_id' => $admin->id,
        ]);
    }

    private function pendingFollowUp(array $fixture): ProjectFollowUp
    {
        return ProjectFollowUp::create([
            'project_id' => $fixture['project']->id,
            'employee_id' => $fixture['sourceEmployee']->id,
            'created_by' => $fixture['sourceEmployee']->user_id,
            'department_id' => $fixture['sourceDepartment']->id,
            'sub_department_id' => $fixture['sourceSubDepartment']->id,
            'follow_up_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Call the customer',
            'status' => 'Pending',
        ]);
    }

    public function test_moving_to_another_lane_of_the_same_department_keeps_the_employee_and_note_and_tells_nobody(): void
    {
        Notification::fake();
        Queue::fake();

        $admin = $this->superAdmin();
        $fixture = $this->movementFixture();
        $otherLane = SubDepartment::create([
            'id' => 3,
            'department_id' => $fixture['sourceDepartment']->id,
            'name' => 'Site Survey Scheduled',
        ]);
        $fixture['task']->update(['assign_to_notes' => 'Homeowner wants to wait until November']);
        $followUp = $this->pendingFollowUp($fixture);

        $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['sourceDepartment']->id,
            'subDepartmentId' => $otherLane->id,
            'taskId' => $fixture['task']->id,
        ])->assertOk()->assertJson(['status' => 200]);

        // The reminder stays Pending and moves to the new lane with the project.
        $followUp->refresh();
        $this->assertSame('Pending', $followUp->status);
        $this->assertNull($followUp->resolved_date);
        $this->assertSame($otherLane->id, (int) $followUp->sub_department_id);

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'department_id' => $fixture['sourceDepartment']->id,
            'sub_department_id' => $otherLane->id,
        ]);

        // The lane change is still a new stint, but it is the same employee and the same note.
        $this->assertSame('Completed', $fixture['task']->fresh()->status);
        $this->assertDatabaseHas('tasks', [
            'project_id' => $fixture['project']->id,
            'employee_id' => $fixture['sourceEmployee']->id,
            'department_id' => $fixture['sourceDepartment']->id,
            'sub_department_id' => $otherLane->id,
            'assign_to_notes' => 'Homeowner wants to wait until November',
            'status' => 'In-Progress',
        ]);

        // Neither the bell nor the email: nobody was assigned anything.
        Notification::assertNothingSent();
        Queue::assertNotPushed(SendProjectAssignedEmailNotificationJob::class);
    }

    public function test_moving_to_another_department_still_assigns_notifies_and_starts_with_no_note(): void
    {
        Notification::fake();
        Queue::fake();

        $admin = $this->superAdmin();
        $fixture = $this->movementFixture();
        $fixture['task']->update(['assign_to_notes' => 'Homeowner wants to wait until November']);
        $followUp = $this->pendingFollowUp($fixture);

        $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
        ])->assertOk()->assertJson(['status' => 200]);

        // Leaving the department closes the reminder, as before.
        $this->assertSame('Resolved', $followUp->fresh()->status);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $fixture['project']->id,
            'employee_id' => $fixture['targetEmployee']->id,
            'department_id' => $fixture['targetDepartment']->id,
            'assign_to_notes' => null,
            'status' => 'In-Progress',
        ]);

        Notification::assertSentTo($fixture['targetEmployee']->user, ProjectAssignedNotification::class);
        Queue::assertPushed(SendProjectAssignedEmailNotificationJob::class);
    }

    public function test_project_move_stamps_the_exit_date_on_the_task_it_closes(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->movementFixture();

        $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
            'notes' => 'Ready for engineering.',
        ])->assertOk();

        // The closed stint gets a real exit date, and the one the project moved
        // into is still open. Without this, the Department Logs tab has to read
        // `updated_at`, which any later write to the row drags forward.
        $closed = Task::findOrFail($fixture['task']->id);
        $this->assertNotNull($closed->completed_at);

        $opened = Task::where('project_id', $fixture['project']->id)
            ->where('department_id', $fixture['targetDepartment']->id)
            ->firstOrFail();
        $this->assertNull($opened->completed_at);
    }

    public function test_project_move_blocks_forward_move_when_required_department_fields_are_missing(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->movementFixture();

        DB::table('project_department_fields')->insert([
            'department_id' => $fixture['sourceDepartment']->id,
            'field_name' => 'site_survey_link',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
            'notes' => 'Try move without required data.',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'error' => 'This project cannot leave Deal Review until these fields are filled in: Site Survey Link.',
                'requires' => 'department_fields',
                'department_name' => 'Deal Review',
                'missing_fields' => ['site_survey_link'],
                'missing_field_labels' => ['Site Survey Link'],
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'department_id' => $fixture['sourceDepartment']->id,
            'sub_department_id' => $fixture['sourceSubDepartment']->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $fixture['task']->id,
            'status' => 'In-Progress',
        ]);
    }

    public function test_project_move_rolls_back_when_target_department_has_no_employee(): void
    {
        $admin = $this->superAdmin();
        $fixture = $this->movementFixture(withTargetEmployee: false);

        $response = $this->actingAs($admin)->post(route('move.project'), [
            'projectId' => $fixture['project']->id,
            'departmentId' => $fixture['targetDepartment']->id,
            'subDepartmentId' => $fixture['targetSubDepartment']->id,
            'taskId' => $fixture['task']->id,
            'notes' => 'No assignee exists.',
        ]);

        $response
            ->assertOk()
            ->assertJson(['status' => 500]);

        $this->assertDatabaseHas('projects', [
            'id' => $fixture['project']->id,
            'department_id' => $fixture['sourceDepartment']->id,
            'sub_department_id' => $fixture['sourceSubDepartment']->id,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $fixture['task']->id,
            'status' => 'In-Progress',
            'notes' => null,
        ]);
        $this->assertSame(1, Task::where('project_id', $fixture['project']->id)->count());
    }
}
