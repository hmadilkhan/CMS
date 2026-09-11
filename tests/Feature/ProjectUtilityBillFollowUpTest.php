<?php

namespace Tests\Feature;

use App\Livewire\Project\EnhancedFilesSection;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Project;
use App\Models\ProjectDocumentFollowUp;
use App\Models\ProjectFile;
use App\Models\SalesPartner;
use App\Models\User;
use App\Models\UserType;
use App\Services\DocumentFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The Utility Bill chase is the one whose department field asks whether the
 * document is already in ("Utility Bill Uploaded"), not whether it is needed -
 * so uploading the bill answers the field as well as closing the chase. See
 * docs/follow-ups.md.
 */
class ProjectUtilityBillFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private function project(array $attributes = []): Project
    {
        Department::firstOrCreate(['id' => 1], ['name' => 'Deal Review']);

        $salesPartner = SalesPartner::create(['name' => 'Utility Bill Sales Partner']);
        $customer = Customer::create([
            'first_name' => 'Bill',
            'last_name' => 'Payer',
            'street' => '9 Meter Ln',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-444-4444',
            'email' => 'bill.payer@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        return Project::create(array_merge([
            'customer_id' => $customer->id,
            'department_id' => 1,
            'sub_department_id' => 1,
            'project_name' => 'Utility Bill Project',
            'code' => '9200',
            'budget' => 25000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'utility_bill_required' => 'no',
        ], $attributes));
    }

    private function uploadTheBill(Project $project, string $filename = 'utility_bill.pdf'): ProjectFile
    {
        return ProjectFile::create([
            'project_id' => $project->id,
            'task_id' => 0,
            'department_id' => 1,
            'filename' => $filename,
            'header_text' => $filename,
            'category' => ProjectFile::CATEGORY_UTILITY_BILL,
        ]);
    }

    public function test_uploading_the_bill_clears_the_chase_and_answers_the_field(): void
    {
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        $service->sync($project);

        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
        $this->assertSame('no', $project->refresh()->utility_bill_required, 'Nothing is uploaded yet, so the field still says no.');

        $this->uploadTheBill($project);
        $service->sync($project->refresh());

        $this->assertSame('yes', $project->refresh()->utility_bill_required);
        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
        $this->assertSame(
            ProjectDocumentFollowUp::REASON_DOCUMENT_RECEIVED,
            ProjectDocumentFollowUp::where('project_id', $project->id)
                ->ofType(DocumentFollowUpService::TYPE_UTILITY_BILL)
                ->latest('id')
                ->value('resolved_reason')
        );
    }

    public function test_the_answer_is_written_even_when_no_chase_is_open(): void
    {
        // A project the chase never looks at (its bill predates the feature) is
        // still contradicting itself while the field says no.
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        ProjectDocumentFollowUp::create([
            'project_id' => $project->id,
            'type' => DocumentFollowUpService::TYPE_UTILITY_BILL,
            'status' => ProjectDocumentFollowUp::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_reason' => ProjectDocumentFollowUp::REASON_PRE_EXISTING,
        ]);

        $this->uploadTheBill($project);
        $service->sync($project->refresh());

        $this->assertSame('yes', $project->refresh()->utility_bill_required);
        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_a_project_still_owing_its_bill_keeps_the_no_answer(): void
    {
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        $service->sync($project);
        $service->sync($project->refresh());

        $this->assertSame('no', $project->refresh()->utility_bill_required);
        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_deleting_the_only_bill_puts_the_answer_back_to_no_and_re_opens_the_chase(): void
    {
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        $service->sync($project);
        $bill = $this->uploadTheBill($project);
        $service->sync($project->refresh());

        $this->assertSame('yes', $project->refresh()->utility_bill_required);

        $bill->delete();
        $service->sync($project->refresh());

        $this->assertSame('no', $project->refresh()->utility_bill_required);
        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_one_bill_left_keeps_the_answer_at_yes(): void
    {
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        $service->sync($project);
        $first = $this->uploadTheBill($project, 'bill_one.pdf');
        $this->uploadTheBill($project, 'bill_two.pdf');
        $service->sync($project->refresh());

        $first->delete();
        $service->sync($project->refresh());

        $this->assertSame('yes', $project->refresh()->utility_bill_required);
        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_an_answer_given_by_hand_is_never_overruled_by_the_empty_section(): void
    {
        // "yes" with no file of its own is a real answer - the bill came in on
        // paper, or lives among the ordinary department files. Nothing to chase
        // and nothing to rewrite.
        $service = app(DocumentFollowUpService::class);
        $project = $this->project(['utility_bill_required' => 'yes']);

        $service->sync($project);
        $service->sync($project->refresh());

        $this->assertSame('yes', $project->refresh()->utility_bill_required);
        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_retracting_the_question_by_hand_is_not_flipped_back(): void
    {
        // Answering "yes" on the project page closes the chase as no longer
        // required. No bill was ever uploaded, so the empty section must not
        // drag the answer back to "no" on the next sync.
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        $service->sync($project);
        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));

        $project->forceFill(['utility_bill_required' => 'yes'])->save();
        $service->sync($project->refresh());
        $service->sync($project->refresh());

        $this->assertSame('yes', $project->refresh()->utility_bill_required);
        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_a_deleted_bill_is_picked_up_by_the_dashboard_reconcile(): void
    {
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        $service->sync($project);
        $bill = $this->uploadTheBill($project);
        $service->sync($project->refresh());
        $this->assertSame('yes', $project->refresh()->utility_bill_required);

        // Deleted through a path nobody hooked: syncAll() still has to find it,
        // even though "yes" keeps the project out of the owing-a-document query.
        $bill->delete();
        $service->syncAll();

        $this->assertSame('no', $project->refresh()->utility_bill_required);
        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_deleting_the_bill_from_the_utility_bills_section_answers_the_field_straight_away(): void
    {
        // The section is read-only for uploads, but a user with File Delete can
        // still remove a bill from it - that is the path this has to cover, not
        // just the reconcile.
        $service = app(DocumentFollowUpService::class);
        $project = $this->project();

        $service->sync($project);
        $bill = $this->uploadTheBill($project);
        $service->sync($project->refresh());
        $this->assertSame('yes', $project->refresh()->utility_bill_required);

        UserType::firstOrCreate(['id' => 1], ['name' => 'Admin']);
        $admin = User::factory()->create(['user_type_id' => 1]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));

        Livewire::actingAs($admin)
            ->test(EnhancedFilesSection::class, [
                'projectId' => $project->id,
                'taskId' => 0,
                'departmentId' => 1,
                'projectDepartmentId' => $project->department_id,
                'viewSource' => 'crm',
                'category' => ProjectFile::CATEGORY_UTILITY_BILL,
                'allowUpload' => false,
            ])
            ->call('deleteConfirmation', $bill->id)
            ->call('deleteFile');

        $this->assertSame('no', $project->refresh()->utility_bill_required);
        $this->assertTrue($service->hasPending($project->id, DocumentFollowUpService::TYPE_UTILITY_BILL));
    }

    public function test_a_fire_review_document_does_not_touch_its_requirement_field(): void
    {
        // Fire Review Required asks whether the review is needed, and it still
        // was after the approval lands - only the utility bill field flips.
        $service = app(DocumentFollowUpService::class);
        Department::firstOrCreate(['id' => 4], ['name' => 'Permitting']);
        $project = $this->project(['utility_bill_required' => 'yes', 'fire_review_required' => 1]);

        $service->sync($project);

        ProjectFile::create([
            'project_id' => $project->id,
            'task_id' => 0,
            'department_id' => 4,
            'filename' => 'fire_approval.pdf',
            'header_text' => 'Fire Approval.pdf',
            'category' => ProjectFile::CATEGORY_FIRE_REVIEW,
        ]);

        $service->sync($project->refresh());

        $this->assertSame(1, (int) $project->refresh()->fire_review_required);
        $this->assertFalse($service->hasPending($project->id, DocumentFollowUpService::TYPE_FIRE_REVIEW));
    }
}
