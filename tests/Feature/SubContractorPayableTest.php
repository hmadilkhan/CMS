<?php

namespace Tests\Feature;

use App\Livewire\AccountTransactions;
use App\Livewire\Project\InvoiceDetails;
use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\User;
use App\Models\UserType;
use App\Services\SubContractorPayableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Entering a project's Inspection Approval Date raises the sub-contractor's
 * Labor Payable in Account Transactions, once per Labor invoice.
 */
class SubContractorPayableTest extends TestCase
{
    use RefreshDatabase;

    private function project(bool $withSubContractor = true, ?string $inspectionDate = '2026-08-27'): Project
    {
        $subContractor = $withSubContractor ? SubContractor::create(['name' => 'Electrical Best Services']) : null;

        $customer = Customer::create([
            'first_name' => 'Payable',
            'last_name' => 'Customer',
            'street' => '1 Labor Rd',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => '555-333-3333',
            'email' => 'payable.customer@example.com',
            'sales_partner_id' => SalesPartner::create(['name' => 'Payable Partner'])->id,
            'sub_contractor_id' => $subContractor?->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 14,
            'inverter_qty' => 1,
        ]);

        return Project::create([
            'customer_id' => $customer->id,
            'project_name' => 'Payable Project',
            'code' => '9300',
            'budget' => 28000,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'inspection_approval_date' => $inspectionDate,
        ]);
    }

    private function invoice(Project $project, string $type, float $amount, string $file): ProjectInvoiceDetail
    {
        return ProjectInvoiceDetail::create([
            'project_id' => $project->id,
            'invoice_type' => $type,
            'invoice_date' => '2026-08-14',
            'amount' => $amount,
            'file_name' => '1786747749_'.$file,
            'original_file_name' => $file,
            'file_path' => 'invoices/'.$file,
        ]);
    }

    private function service(): SubContractorPayableService
    {
        return app(SubContractorPayableService::class);
    }

    public function test_the_labor_payable_is_created_like_the_manual_one(): void
    {
        $project = $this->project();
        $invoice = $this->invoice($project, 'labor', 6020, 'INVOICE_2207_from_Electrical best services,INC_.pdf');

        $this->assertSame(1, $this->service()->createForInspectionApproval($project));

        $transaction = AccountTransaction::where('project_id', $project->id)->sole();

        $this->assertSame('sub_contractor', $transaction->payee);
        $this->assertSame('Full Payment - Invoice #2207', $transaction->milestone);
        $this->assertEquals(-6020.00, (float) $transaction->amount);
        $this->assertEquals(0, (float) $transaction->deduction_amount);
        $this->assertSame('2026-08-27', substr((string) $transaction->transaction_date, 0, 10));
        $this->assertSame('Labor Payable - Electrical Best Services', $transaction->transaction_details);
        $this->assertSame('Open', $transaction->status);
        $this->assertSame($invoice->id, (int) $transaction->project_invoice_detail_id);
    }

    public function test_the_same_invoice_never_gets_a_second_transaction(): void
    {
        $project = $this->project();
        $this->invoice($project, 'labor', 6020, 'INVOICE_2207_from_Electrical best services,INC_.pdf');

        $this->service()->createForInspectionApproval($project);
        $this->assertSame(0, $this->service()->createForInspectionApproval($project));

        // Not even after Finance deleted the first one by hand.
        AccountTransaction::where('project_id', $project->id)->first()->delete();
        $this->assertSame(0, $this->service()->createForInspectionApproval($project));
        $this->assertSame(0, AccountTransaction::where('project_id', $project->id)->count());
    }

    public function test_every_labor_invoice_gets_its_own_transaction_and_material_is_ignored(): void
    {
        $project = $this->project();
        $this->invoice($project, 'labor', 4000, 'INVOICE_2300_from_Electrical best services,INC_.pdf');
        $this->invoice($project, 'labor', 1500, 'INVOICE_2301_from_Electrical best services,INC_.pdf');
        $this->invoice($project, 'material', 9000, 'MATERIAL_77.pdf');

        $this->assertSame(2, $this->service()->createForInspectionApproval($project));

        $this->assertEqualsCanonicalizing(
            ['Full Payment - Invoice #2300', 'Full Payment - Invoice #2301'],
            AccountTransaction::where('project_id', $project->id)->pluck('milestone')->all()
        );
    }

    public function test_an_invoice_whose_file_has_no_number_is_still_raised(): void
    {
        $project = $this->project();
        $this->invoice($project, 'labor', 800, 'scan.pdf');

        $this->service()->createForInspectionApproval($project);

        $this->assertSame('Full Payment - Invoice', AccountTransaction::where('project_id', $project->id)->value('milestone'));
    }

    public function test_nothing_is_created_without_a_sub_contractor_or_labor_invoice_or_date(): void
    {
        $noSub = $this->project(false);
        $this->invoice($noSub, 'labor', 6020, 'INVOICE_2207_x.pdf');
        $this->assertSame(0, $this->service()->createForInspectionApproval($noSub));

        $noInvoice = $this->project();
        $this->invoice($noInvoice, 'material', 100, 'm.pdf');
        $this->assertSame(0, $this->service()->createForInspectionApproval($noInvoice));

        $noDate = $this->project(true, null);
        $this->invoice($noDate, 'labor', 6020, 'INVOICE_2207_x.pdf');
        $this->assertSame(0, $this->service()->createForInspectionApproval($noDate));

        $this->assertSame(0, AccountTransaction::count());
    }

    public function test_a_labor_invoice_uploaded_after_inspection_approval_raises_the_payable(): void
    {
        // Labor is only needed to reach PTO, so it can come in after the date.
        Storage::fake('public');
        $project = $this->project();
        UserType::firstOrCreate(['name' => 'Admin']);
        $admin = User::factory()->create(['user_type_id' => 1]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));

        $upload = fn (string $type, string $file) => Livewire::actingAs($admin)
            ->test(InvoiceDetails::class, ['projectId' => $project->id])
            ->set('invoiceType', $type)
            ->set('invoiceDate', '2026-09-02')
            ->set('amount', 6020)
            ->set('file', UploadedFile::fake()->create($file, 10, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $upload('material', 'MATERIAL_77.pdf');
        $this->assertSame(0, AccountTransaction::count());

        $upload('labor', 'INVOICE_2400_from_Electrical best services,INC_.pdf');

        $transaction = AccountTransaction::where('project_id', $project->id)->sole();
        $this->assertSame('Full Payment - Invoice #2400', $transaction->milestone);
        $this->assertEquals(-6020.00, (float) $transaction->amount);
        $this->assertSame('2026-08-27', substr((string) $transaction->transaction_date, 0, 10));

        // Before the date, an upload raises nothing - the date will.
        $early = $this->project(true, null);
        Livewire::actingAs($admin)
            ->test(InvoiceDetails::class, ['projectId' => $early->id])
            ->set('invoiceType', 'labor')
            ->set('invoiceDate', '2026-09-02')
            ->set('amount', 500)
            ->set('file', UploadedFile::fake()->create('INVOICE_2401_x.pdf', 10, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(0, AccountTransaction::where('project_id', $early->id)->count());
    }

    public function test_a_transaction_added_by_hand_starts_open_and_can_be_closed(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['user_type_id' => 1]));

        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->set('payee', 'others')
            ->set('milestone', 'M1')
            ->set('amount', 100)
            ->set('deduction_amount', 0)
            ->set('transaction_date', '2026-08-28')
            ->set('transaction_details', 'By hand')
            ->call('save')
            ->assertHasNoErrors();

        $transaction = AccountTransaction::where('project_id', $project->id)->sole();
        $this->assertSame('Open', $transaction->status);

        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('status', 'Closed')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Closed', $transaction->refresh()->status);
    }
}
