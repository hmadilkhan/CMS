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

    private function closedPayment(Project $project, array $overrides = []): AccountTransaction
    {
        return AccountTransaction::create($overrides + [
            'project_id' => $project->id,
            'payee' => 'customer',
            'milestone' => 'Permit & Install',
            'amount' => 19050,
            'deduction_amount' => 0,
            'transaction_date' => '2026-11-05',
            'transaction_details' => 'Imported',
            'status' => 'Closed',
            'settled_amount' => 19050,
            'settled_date' => '2026-11-05',
        ]);
    }

    /** The reported case: the amount was fixed on the project page, the portal kept the old one. */
    public function test_changing_the_amount_of_a_payment_closed_in_full_moves_what_was_received(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['user_type_id' => 1]));
        $transaction = $this->closedPayment($project);

        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('amount', 28050)
            ->call('update')
            ->assertHasNoErrors();

        $transaction->refresh();
        $this->assertEquals(28050, $transaction->amount);
        $this->assertEquals(28050, $transaction->settled_amount);
        $this->assertSame('2026-11-05', $transaction->settled_date);
    }

    public function test_the_received_amount_and_date_of_a_closed_row_can_be_edited(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['user_type_id' => 1]));
        $transaction = $this->closedPayment($project);

        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('settled_amount', 15000)
            ->set('settled_date', '2026-11-07')
            ->call('update')
            ->assertHasNoErrors();

        $transaction->refresh();
        $this->assertEquals(19050, $transaction->amount);
        $this->assertEquals(15000, $transaction->settled_amount);
        $this->assertSame('2026-11-07', $transaction->settled_date);
    }

    public function test_a_part_payment_keeps_its_received_amount_when_the_amount_changes(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['user_type_id' => 1]));
        $transaction = $this->closedPayment($project, ['settled_amount' => 10000]);

        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('amount', 28050)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertEquals(10000, $transaction->refresh()->settled_amount);
    }

    public function test_a_row_closed_before_settlement_existed_is_left_without_one(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['user_type_id' => 1]));
        $transaction = $this->closedPayment($project, ['settled_amount' => null, 'settled_date' => null]);

        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('milestone', 'Permit')
            ->call('update')
            ->assertHasNoErrors();

        $transaction->refresh();
        $this->assertSame('Permit', $transaction->milestone);
        $this->assertNull($transaction->settled_amount);
        $this->assertNull($transaction->settled_date);
    }

    public function test_editing_a_part_paid_row_keeps_its_payments_and_closing_adds_only_the_rest(): void
    {
        $project = $this->project();
        $this->actingAs(User::factory()->create(['user_type_id' => 1]));
        $transaction = $this->closedPayment($project, ['status' => 'Open', 'settled_amount' => null, 'settled_date' => null]);
        app(\App\Services\TransactionPaymentService::class)->record($transaction, 5000, '2026-11-03');

        // An open row edited from the project page keeps its part payment.
        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->assertSet('paidSoFar', 5000.0)
            ->set('milestone', 'Permit')
            ->call('update')
            ->assertHasNoErrors();
        $this->assertEquals(5000, (float) $transaction->fresh()->settled_amount);

        // Closed with the total received: only what was missing is recorded.
        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('status', 'Closed')
            ->assertSet('settled_amount', 19050.0)
            ->set('settled_date', '2026-11-06')
            ->call('update')
            ->assertHasNoErrors();

        $transaction->refresh();
        $this->assertSame('Closed', $transaction->status);
        $this->assertEquals(19050, (float) $transaction->settled_amount);
        $this->assertEquals([5000, 14050], $transaction->payments()->pluck('amount')->map(fn ($a) => (float) $a)->all());

        // Editing the total of a row paid in parts moves its last payment.
        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('settled_amount', 18000)
            ->call('update')
            ->assertHasNoErrors();
        $this->assertEquals([5000, 13000], $transaction->payments()->pluck('amount')->map(fn ($a) => (float) $a)->all());

        // ...but cannot go below the earlier payments.
        Livewire::test(AccountTransactions::class, ['project_id' => $project->id])
            ->call('edit', $transaction->id)
            ->set('settled_amount', 4000)
            ->call('update')
            ->assertHasErrors('settled_amount');
        $this->assertEquals(18000, (float) $transaction->fresh()->settled_amount);
    }
}
