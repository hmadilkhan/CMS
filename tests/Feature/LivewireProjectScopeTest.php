<?php

namespace Tests\Feature;

use App\Livewire\Project\NotesSection;
use App\Models\Customer;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * $projectId is a public Livewire property, so the browser owns it. Without a
 * check of its own, a user holding one project's page could point the notes,
 * files or invoice components at another project — the controller gate never
 * sees it, because the round trip goes to livewire/update.
 */
class LivewireProjectScopeTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $user->syncRoles([Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web'])]);

        return $user->fresh();
    }

    private function project(string $name): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);
        SubDepartment::firstOrCreate(['id' => 1], ['name' => 'New Deals', 'department_id' => 1]);

        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sales_partner_id' => SalesPartner::create(['name' => 'Partner '.$name])->id,
        ]);

        return Project::create([
            'project_name' => $name,
            'code' => 'P-'.random_int(1000, 9999),
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
        ]);
    }

    private function assignTo(User $user, Project $project): void
    {
        $employee = Employee::create(['name' => $user->name, 'user_id' => $user->id]);

        Task::create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'status' => 'In-Progress',
        ]);
    }

    public function test_the_notes_component_cannot_be_pointed_at_another_project(): void
    {
        $mine = $this->project('My Project');
        $theirs = $this->project('Their Project');

        DepartmentNote::create([
            'project_id' => $theirs->id,
            'department_id' => 1,
            'notes' => 'PRIVATE customer complaint',
        ]);

        $user = $this->employee();
        $this->assignTo($user, $mine);

        // The component I was legitimately served.
        $component = Livewire::actingAs($user)
            ->test(NotesSection::class, ['projectId' => $mine->id, 'departmentId' => 1]);

        $component->assertOk();

        // Now aim it somewhere else, the way the browser can.
        Livewire::actingAs($user)
            ->test(NotesSection::class, ['projectId' => $theirs->id, 'departmentId' => 1])
            ->assertForbidden();
    }

    public function test_the_notes_component_still_works_on_my_own_project(): void
    {
        $mine = $this->project('My Project');

        $user = $this->employee();
        $this->assignTo($user, $mine);

        Livewire::actingAs($user)
            ->test(NotesSection::class, ['projectId' => $mine->id, 'departmentId' => 1])
            ->assertOk();
    }
}
