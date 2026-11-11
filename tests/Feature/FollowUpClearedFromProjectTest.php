<?php

namespace Tests\Feature;

use App\Livewire\Project\EnhancedFilesSection;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\ProjectDocumentFollowUp;
use App\Models\ProjectFile;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UserType;
use App\Services\DocumentFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A follow up that holds a project can be cleared from the project page - the
 * Utility Bills / Fire Approval Documents sections take the document while the
 * chase is open - not only from the dashboard card, which only the owning
 * department's assignees see. See docs/follow-ups.md.
 */
class FollowUpClearedFromProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Department::create(['id' => 1, 'name' => 'Deal Review']);
        Department::create(['id' => 4, 'name' => 'Permitting']);
        Department::create(['id' => 6, 'name' => 'Inspection']);
        Department::create(['id' => 8, 'name' => 'PTO']);

        SubDepartment::create(['id' => 1, 'department_id' => 1, 'name' => 'Deal Review', 'show_in_move_list' => 1]);
        SubDepartment::create(['id' => 18, 'department_id' => 8, 'name' => 'PTO', 'show_in_move_list' => 1]);
        SubDepartment::create(['id' => 32, 'department_id' => 8, 'name' => 'PTO Pending Document', 'show_in_move_list' => 0]);
    }

    private function project(array $attributes = []): Project
    {
        $salesPartner = SalesPartner::create(['name' => 'Follow Up Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Page',
            'last_name' => 'Upload',
            'street' => '1 Lane St',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-111-2222',
            'email' => 'page.upload@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        $project = Project::create(array_merge([
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'project_name' => 'Follow Up From Project',
            'code' => '9300',
            'budget' => 25000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'utility_bill_required' => 'no',
        ], $attributes));

        // Every real project has the task of the lane it sits in; the chase's
        // documents are filed against it.
        Task::create([
            'project_id' => $project->id,
            'department_id' => $project->department_id,
            'sub_department_id' => $project->sub_department_id,
            'status' => 'In-Progress',
        ]);

        app(DocumentFollowUpService::class)->sync($project);

        return $project->refresh();
    }

    /** Not assigned to any department: never sees the dashboard card. */
    private function userWithoutTheCard(): User
    {
        UserType::firstOrCreate(['id' => 1], ['name' => 'Admin']);
        $user = User::factory()->create(['user_type_id' => 1]);
        $user->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));

        return $user;
    }

    private function section(User $user, Project $project, string $category, int $departmentId)
    {
        return Livewire::actingAs($user)->test(EnhancedFilesSection::class, [
            'projectId' => $project->id,
            'taskId' => 0,
            'departmentId' => $departmentId,
            'projectDepartmentId' => $project->department_id,
            'viewSource' => 'crm',
            'category' => $category,
            'allowUpload' => false,
        ]);
    }

    public function test_the_section_offers_the_upload_while_the_follow_up_is_open(): void
    {
        $user = $this->userWithoutTheCard();
        $project = $this->project();

        $this->assertFalse(app(DocumentFollowUpService::class)->visibleTo($user, DocumentFollowUpService::TYPE_UTILITY_BILL));

        $this->section($user, $project, ProjectFile::CATEGORY_UTILITY_BILL, 1)
            ->assertSee('Upload Document')
            ->assertSee('Utility Bill Follow Up is open');
    }

    public function test_uploading_from_the_project_releases_a_parked_project(): void
    {
        $user = $this->userWithoutTheCard();
        $project = $this->project(['department_id' => 8, 'sub_department_id' => 32]);
        $service = app(DocumentFollowUpService::class);

        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));

        $this->section($user, $project, ProjectFile::CATEGORY_UTILITY_BILL, 1)
            ->set('files', [UploadedFile::fake()->create('June Bill.pdf', 20, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('document-follow-up-released')
            ->assertSee('The project moved to PTO');

        $project->refresh();
        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
        $this->assertSame(18, (int) $project->sub_department_id);
        $this->assertSame('yes', $project->utility_bill_required);
        $this->assertSame(
            ProjectDocumentFollowUp::REASON_DOCUMENT_RECEIVED,
            ProjectDocumentFollowUp::where('project_id', $project->id)
                ->ofType(DocumentFollowUpService::TYPE_UTILITY_BILL)
                ->latest('id')
                ->value('resolved_reason')
        );

        // Filed exactly as the dashboard card files it.
        $file = ProjectFile::where('project_id', $project->id)->sole();
        $this->assertSame(ProjectFile::CATEGORY_UTILITY_BILL, $file->category);
        $this->assertSame(1, (int) $file->department_id);
        $this->assertSame('June Bill.pdf', $file->header_text);
        Storage::disk('public')->assertExists('projects/'.$file->filename);
    }

    public function test_the_fire_approval_files_under_permitting_from_the_inspection_tab(): void
    {
        $user = $this->userWithoutTheCard();
        $project = $this->project(['utility_bill_required' => 'yes', 'fire_review_required' => 1]);
        $service = app(DocumentFollowUpService::class);

        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_FIRE_REVIEW));

        $this->section($user, $project, ProjectFile::CATEGORY_FIRE_REVIEW, 6)
            ->set('files', [UploadedFile::fake()->create('fire.pdf', 20, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotDispatched('document-follow-up-released')
            ->assertSee('Fire Review Follow Up cleared.');

        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_FIRE_REVIEW));
        $this->assertSame(4, (int) ProjectFile::where('project_id', $project->id)->sole()->department_id);
        $this->assertSame(1, (int) $project->refresh()->fire_review_required);
    }

    public function test_with_no_open_follow_up_the_section_stays_read_only(): void
    {
        $user = $this->userWithoutTheCard();
        $project = $this->project(['utility_bill_required' => 'yes']);

        $this->assertFalse(app(DocumentFollowUpService::class)->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));

        $this->section($user, $project, ProjectFile::CATEGORY_UTILITY_BILL, 1)
            ->assertDontSee('Upload Document')
            ->set('files', [UploadedFile::fake()->create('late.pdf', 20, 'application/pdf')])
            ->call('save')
            ->assertHasErrors('files');

        $this->assertSame(0, ProjectFile::where('project_id', $project->id)->count());
    }
}
