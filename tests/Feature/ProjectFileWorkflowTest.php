<?php

namespace Tests\Feature;

use App\Livewire\Project\EnhancedFilesSection;
use App\Livewire\Project\FilesSection;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
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
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectFileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        Permission::firstOrCreate(['name' => 'Files Section']);
        Permission::firstOrCreate(['name' => 'File Delete']);
        $user->assignRole('Super Admin');
        $user->givePermissionTo(['Files Section', 'File Delete']);

        return $user;
    }

    private function projectFixture(): array
    {
        $department = Department::create(['id' => 1, 'name' => 'Files Department']);
        $subDepartment = SubDepartment::create([
            'id' => 1,
            'department_id' => $department->id,
            'name' => 'Files Sub Department',
        ]);

        $employeeUser = User::factory()->create(['user_type_id' => 2]);
        $employeeUser->assignRole(Role::firstOrCreate(['name' => 'Employee']));

        $employee = Employee::create([
            'name' => 'Files Employee',
            'code' => 'EMP-FILES',
            'email' => 'files.employee@example.com',
            'phone' => '555-777-7777',
            'user_id' => $employeeUser->id,
        ]);
        $employee->department()->attach($department->id);

        $salesPartner = SalesPartner::create(['name' => 'Files Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Files',
            'last_name' => 'Customer',
            'street' => '202 Storage Ave',
            'city' => 'Phoenix',
            'state' => 'AZ',
            'zipcode' => '85002',
            'phone' => '555-888-8888',
            'email' => 'files.customer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_type_id' => 1,
            'module_type_id' => 1,
            'inverter_qty' => 1,
            'module_value' => 4800,
        ]);

        $project = Project::create([
            'customer_id' => $customer->id,
            'department_id' => $department->id,
            'sub_department_id' => $subDepartment->id,
            'project_name' => 'Files Project',
            'budget' => 26000,
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

    public function test_files_section_uploads_project_file_to_public_storage_and_database(): void
    {
        Storage::fake('public');

        $admin = $this->superAdmin();
        $fixture = $this->projectFixture();
        $upload = UploadedFile::fake()->create('plan set.pdf', 100, 'application/pdf');

        Livewire::actingAs($admin)
            ->test(FilesSection::class, [
                'projectId' => $fixture['project']->id,
                'taskId' => $fixture['task']->id,
                'departmentId' => $fixture['department']->id,
                'projectDepartmentId' => $fixture['project']->department_id,
            ])
            ->set('files', [$upload])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('files', []);

        $file = ProjectFile::first();
        $this->assertNotNull($file);
        $this->assertSame($fixture['project']->id, $file->project_id);
        $this->assertSame($fixture['task']->id, $file->task_id);
        $this->assertSame($fixture['department']->id, $file->department_id);
        $this->assertStringEndsWith('_plan_set.pdf', $file->filename);
        Storage::disk('public')->assertExists('projects/' . $file->filename);
    }

    public function test_enhanced_files_section_previews_saves_titles_and_deletes_project_file(): void
    {
        Storage::fake('public');

        $admin = $this->superAdmin();
        $fixture = $this->projectFixture();
        $upload = UploadedFile::fake()->create('engineering packet.pdf', 100, 'application/pdf');

        $component = Livewire::actingAs($admin)
            ->test(EnhancedFilesSection::class, [
                'projectId' => $fixture['project']->id,
                'taskId' => $fixture['task']->id,
                'departmentId' => $fixture['department']->id,
                'projectDepartmentId' => $fixture['project']->department_id,
                'viewSource' => 'crm',
            ])
            ->call('openModal')
            ->assertSet('showModal', true)
            ->set('files', [$upload])
            ->assertCount('uploadedFiles', 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $file = ProjectFile::first();
        $this->assertNotNull($file);
        $this->assertSame('Untitled', $file->header_text);
        Storage::disk('public')->assertExists('projects/' . $file->filename);

        $component
            ->call('updateTitle', $file->id, 'Signed engineering packet')
            ->call('deleteConfirmation', $file->id)
            ->assertSet('deleteId', $file->id)
            ->call('deleteFile');

        $this->assertSoftDeleted($file);
        $this->assertDatabaseHas('project_files', [
            'id' => $file->id,
            'header_text' => 'Signed engineering packet',
        ]);
        Storage::disk('public')->assertMissing('projects/' . $file->filename);
    }

    public function test_project_file_controller_routes_upload_and_delete_storage_file(): void
    {
        Storage::fake('public');

        $admin = $this->superAdmin();
        $fixture = $this->projectFixture();
        $upload = UploadedFile::fake()->create('legacy upload.pdf', 75, 'application/pdf');

        $this->actingAs($admin)->post(route('projects.files'), [
            'id' => $fixture['project']->id,
            'taskid' => $fixture['task']->id,
            'file' => [$upload],
        ])->assertRedirect(route('projects.show', $fixture['project']->id));

        $file = ProjectFile::first();
        $this->assertNotNull($file);
        Storage::disk('public')->assertExists('projects/' . $file->filename);

        $this->actingAs($admin)->post(route('delete.file'), [
            'id' => $file->id,
        ])
            ->assertOk()
            ->assertJson([
                'status' => 200,
                'message' => 'File delete successfully',
            ]);

        $this->assertSoftDeleted($file);
        Storage::disk('public')->assertMissing('projects/' . $file->filename);
    }

    public function test_enhanced_files_section_rejects_unsupported_file_types(): void
    {
        Storage::fake('public');

        $admin = $this->superAdmin();
        $fixture = $this->projectFixture();
        $upload = UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload');

        Livewire::actingAs($admin)
            ->test(EnhancedFilesSection::class, [
                'projectId' => $fixture['project']->id,
                'taskId' => $fixture['task']->id,
                'departmentId' => $fixture['department']->id,
                'projectDepartmentId' => $fixture['project']->department_id,
            ])
            ->set('files', [$upload])
            ->assertHasErrors(['files.0']);

        $this->assertSame(0, ProjectFile::count());
    }
}
