<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\Project;
use App\Models\ProjectDocumentFollowUp;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Services\DocumentFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 2026_11_11_000004 deletes the NTP chases a sync() bug opened before the
 * project reached Installation, with the history lines written when they
 * opened - and nothing else.
 */
class DeleteEarlyNtpFollowUpsMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Department::create(['id' => 1, 'name' => 'Deal Review', 'sort_order' => 1]);
        Department::create(['id' => 4, 'name' => 'Permitting', 'sort_order' => 4]);
        Department::create(['id' => 5, 'name' => 'Installation', 'sort_order' => 5]);
        Department::create(['id' => 8, 'name' => 'PTO', 'sort_order' => 8]);

        SubDepartment::create(['id' => 1, 'department_id' => 1, 'name' => 'Deal Review', 'show_in_move_list' => 1]);
        SubDepartment::create(['id' => 9, 'department_id' => 4, 'name' => 'Permitting', 'show_in_move_list' => 1]);
        SubDepartment::create(['id' => 31, 'department_id' => 5, 'name' => 'Install Pending Document', 'show_in_move_list' => 0]);
    }

    private function project(string $code, int $departmentId, int $subDepartmentId, array $attributes = []): Project
    {
        $salesPartner = SalesPartner::create(['name' => 'Partner '.$code]);
        $customer = Customer::create([
            'first_name' => 'Early',
            'last_name' => 'Ntp'.$code,
            'street' => '1 Lane St',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-111-2222',
            'email' => 'early.ntp'.$code.'@example.com',
            'sales_partner_id' => $salesPartner->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 12,
            'inverter_qty' => 1,
        ]);

        $project = Project::create(array_merge([
            'customer_id' => $customer->id,
            'department_id' => $departmentId,
            'sub_department_id' => $subDepartmentId,
            'project_name' => 'Early NTP '.$code,
            'code' => $code,
            'budget' => 25000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ], $attributes));

        Task::create([
            'project_id' => $project->id,
            'department_id' => $departmentId,
            'sub_department_id' => $subDepartmentId,
            'status' => 'In-Progress',
        ]);

        return $project;
    }

    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_11_11_000004_delete_ntp_follow_ups_opened_before_installation.php');
        $migration->up();
    }

    public function test_only_the_ntp_chases_opened_before_installation_are_deleted_with_their_history(): void
    {
        $service = app(DocumentFollowUpService::class);

        $early = $this->project('9501', 4, 9);
        $earlyChase = $service->open($early, DocumentFollowUpService::TYPE_NTP_APPROVAL);
        $earlyNote = DepartmentNote::where('project_id', $early->id)->first();
        // A note the user wrote on the same project stays.
        $userNote = DepartmentNote::create(['project_id' => $early->id, 'task_id' => 0, 'department_id' => 4, 'notes' => 'Call the utility']);

        $parked = $this->project('9502', 5, 31);
        $parkedChase = $service->open($parked, DocumentFollowUpService::TYPE_NTP_APPROVAL);

        $billChase = $service->open($early, DocumentFollowUpService::TYPE_UTILITY_BILL);

        $this->assertNotNull($earlyNote);
        $this->assertSame(1, $this->openedActivities($earlyChase->id));

        $this->runMigration();

        $this->assertNull(ProjectDocumentFollowUp::find($earlyChase->id));
        $this->assertSame(0, $this->openedActivities($earlyChase->id));
        $this->assertNull(DepartmentNote::find($earlyNote->id));

        $this->assertNotNull(DepartmentNote::find($userNote->id));
        $this->assertNotNull(ProjectDocumentFollowUp::find($parkedChase->id));
        $this->assertSame(1, $this->openedActivities($parkedChase->id));
        $this->assertNotNull(ProjectDocumentFollowUp::find($billChase->id));
        $this->assertSame(1, $this->openedActivities($billChase->id));
    }

    private function openedActivities(int $followUpId): int
    {
        return DB::table('activity_log')
            ->where('event', 'document_follow_up_opened')
            ->get(['properties'])
            ->filter(fn ($row) => (int) (json_decode((string) $row->properties, true)['document_follow_up_id'] ?? 0) === $followUpId)
            ->count();
    }
}
