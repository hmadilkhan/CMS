<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\User;
use App\Models\UserType;
use App\Services\CommissionPayableService;
use App\Services\TransactionPortalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Finance -> Transaction Portal: the five sections, their permissions, the week
 * filter, closing rows, and the commission raised on a payment received.
 */
class TransactionPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-04 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function user(string $roleName, array $permissions): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => $roleName]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        $user->assignRole($role);

        return $user;
    }

    private function fundingManager(): User
    {
        return $this->user('Funding Manager', [TransactionPortalService::PERMISSION_PAGE, TransactionPortalService::PERMISSION_EXPECTED]);
    }

    private function finance(): User
    {
        return $this->user('Finance Lead', [
            TransactionPortalService::PERMISSION_PAGE,
            TransactionPortalService::PERMISSION_EXPECTED,
            TransactionPortalService::PERMISSION_OTHER,
        ]);
    }

    private function project(string $name = 'Rito Guzman'): Project
    {
        $customer = Customer::create([
            'first_name' => 'Rito',
            'last_name' => 'Guzman',
            'street' => '1 Portal Rd',
            'city' => 'Fontana',
            'state' => 'CA',
            'zipcode' => '92335',
            'phone' => '(555) 444-4444',
            'email' => uniqid('portal').'@example.com',
            'sales_partner_id' => SalesPartner::create(['name' => 'Artemis Sales'])->id,
            'sub_contractor_id' => SubContractor::create(['name' => 'Delta Electric'])->id,
            'sold_date' => '2026-09-01',
            'panel_qty' => 14,
            'inverter_qty' => 1,
        ]);

        return Project::create([
            'customer_id' => $customer->id,
            'project_name' => $name,
            'code' => (string) random_int(9000, 9999),
            'budget' => 40000,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-01',
        ]);
    }

    private function transaction(Project $project, array $attributes): AccountTransaction
    {
        return AccountTransaction::create($attributes + [
            'project_id' => $project->id,
            'payee' => 'customer',
            'milestone' => 'M1',
            'amount' => 1000,
            'deduction_amount' => 0,
            'transaction_date' => '2026-11-03',
            'transaction_details' => 'Finance milestone payment: M1',
            'status' => AccountTransaction::STATUS_OPEN,
        ]);
    }

    private function invoice(Project $project, string $type, float $amount, string $date = '2026-10-10'): ProjectInvoiceDetail
    {
        return ProjectInvoiceDetail::create([
            'project_id' => $project->id,
            'invoice_type' => $type,
            'invoice_date' => $date,
            'amount' => $amount,
            'file_name' => uniqid().'.pdf',
            'original_file_name' => strtoupper($type).'_INVOICE.pdf',
            'file_path' => 'invoices/x.pdf',
        ]);
    }

    private function week(): array
    {
        return ['start' => '2026-11-01', 'end' => '2026-11-07'];
    }

    public function test_commission_is_due_the_friday_of_the_following_week(): void
    {
        // Sunday-to-Saturday weeks: 10/23 (Fri) is in Oct 18-24, so next week's Friday is 10/30.
        $this->assertSame('2026-10-30', CommissionPayableService::dueDate('2026-10-23')->toDateString());
        $this->assertSame('2026-11-06', CommissionPayableService::dueDate('2026-10-25')->toDateString());
        $this->assertSame('2026-11-06', CommissionPayableService::dueDate('2026-10-26')->toDateString());
        $this->assertSame('2026-11-06', CommissionPayableService::dueDate('2026-10-31')->toDateString());
    }

    public function test_the_page_needs_its_permission(): void
    {
        $outsider = $this->user('Employee', []);

        $this->actingAs($outsider)->get(route('transaction-portal.index'))->assertForbidden();
        $this->actingAs($outsider)->getJson(route('transaction-portal.data', $this->week()))->assertForbidden();
        $this->actingAs($this->finance())->get(route('transaction-portal.index'))->assertOk()->assertSee('Transaction Portal');
    }

    public function test_the_page_is_full_screen_with_a_way_back(): void
    {
        $this->actingAs($this->finance())
            ->get(route('transaction-portal.index'))
            ->assertOk()
            ->assertSee('is-embedded', false)
            ->assertDontSee('notificationsBell', false)
            ->assertDontSee('class="sidebar px-4', false)
            ->assertSee('Back to Dashboard');
    }

    public function test_the_funding_manager_sees_only_expected_ar(): void
    {
        $project = $this->project();
        $this->transaction($project, ['amount' => 5000]);
        $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560, 'milestone' => 'Full Payment']);
        $this->invoice($project, 'material', 9000, '2026-10-05');

        $rows = $this->actingAs($this->fundingManager())
            ->getJson(route('transaction-portal.data', $this->week()))
            ->assertOk()
            ->json('rows');

        $this->assertSame(['ear'], array_values(array_unique(array_column($rows, 'b'))));
    }

    public function test_the_funding_manager_cannot_close_a_payable(): void
    {
        $labor = $this->transaction($this->project(), ['payee' => 'sub_contractor', 'amount' => -4560]);

        $this->actingAs($this->fundingManager())
            ->postJson(route('transaction-portal.settle'), ['items' => ['t'.$labor->id], 'date' => '2026-11-04'])
            ->assertForbidden();

        $this->assertSame('Open', $labor->fresh()->status);
    }

    public function test_expected_ar_holds_open_positive_transactions_up_to_the_week_end(): void
    {
        $project = $this->project();
        $inWeek = $this->transaction($project, ['transaction_date' => '2026-11-05']);
        $earlier = $this->transaction($project, ['transaction_date' => '2026-10-20']);
        $this->transaction($project, ['transaction_date' => '2026-11-12']); // next week
        $this->transaction($project, ['status' => 'Closed']); // already received
        $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -100]); // a payable

        $rows = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.data', $this->week()))->json('rows'))
            ->where('b', 'ear');

        $this->assertEqualsCanonicalizing(['t'.$inWeek->id, 't'.$earlier->id], $rows->pluck('id')->all());
    }

    public function test_recording_a_payment_moves_it_to_actual_ar_and_raises_the_commission(): void
    {
        $project = $this->project();
        $this->invoice($project, 'labor', 4560);
        $this->invoice($project, 'material', 21973.19);
        $payment = $this->transaction($project, ['amount' => 32617.50]);

        $this->actingAs($this->finance())
            ->postJson(route('transaction-portal.settle'), ['items' => ['t'.$payment->id], 'date' => '2026-11-03', 'amount' => 32617.50])
            ->assertOk()
            ->assertJsonPath('closed', 1)
            ->assertJsonPath('commissions.0.due', '2026-11-13');

        $payment->refresh();
        $this->assertSame('Closed', $payment->status);
        $this->assertEquals(32617.50, (float) $payment->settled_amount);
        $this->assertSame('2026-11-03', substr((string) $payment->settled_date, 0, 10));

        $commission = AccountTransaction::where('project_id', $project->id)->where('payee', 'sales_partner')->sole();
        $this->assertEquals(-6084.31, (float) $commission->amount); // 32617.50 - 4560 - 21973.19
        $this->assertSame('2026-11-03', substr((string) $commission->transaction_date, 0, 10));
        $this->assertSame('2026-11-13', substr((string) $commission->due_date, 0, 10));
        $this->assertSame('Commission Payable - Artemis Sales', $commission->transaction_details);
        $this->assertSame('Open', $commission->status);

        $rows = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.data', $this->week()))->json('rows'));
        $this->assertFalse($rows->contains(fn ($r) => $r['b'] === 'ear' && $r['id'] === 't'.$payment->id));
        $this->assertTrue($rows->contains(fn ($r) => $r['b'] === 'aar' && $r['id'] === 't'.$payment->id));
    }

    public function test_commission_waits_for_both_invoices_and_is_never_deducted_twice(): void
    {
        $project = $this->project();
        $service = app(TransactionPortalService::class);
        $this->actingAs($this->finance());

        // No invoices yet: the first payment raises nothing.
        $first = $this->transaction($project, ['amount' => 10000]);
        $this->assertNull($service->settleTransaction($first, 10000, '2026-10-20'));

        $this->invoice($project, 'labor', 3000);
        $this->invoice($project, 'material', 5000);

        // Second payment: 10000 + 6000 received - 8000 invoices = 8000.
        $second = $this->transaction($project, ['amount' => 6000]);
        $this->assertEquals(-8000, (float) $service->settleTransaction($second, 6000, '2026-10-27')->amount);

        // Third payment: only the new money, the invoices were already taken off.
        $third = $this->transaction($project, ['amount' => 2500]);
        $this->assertEquals(-2500, (float) $service->settleTransaction($third, 2500, '2026-11-02')->amount);
    }

    public function test_a_material_invoice_is_due_after_thirty_days_and_can_be_paid(): void
    {
        $project = $this->project();
        $dueThisWeek = $this->invoice($project, 'material', 18320.75, '2026-10-04'); // due 11/03
        $overdue = $this->invoice($project, 'material', 21973.19, '2026-09-21'); // due 10/21, still open
        $paidLongAgo = $this->invoice($project, 'material', 500, '2026-09-01');
        $paidLongAgo->update(['status' => 'Closed']);
        $this->invoice($project, 'labor', 4000, '2026-10-04'); // labor is paid through its payable

        $user = $this->finance();
        $rows = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->where('b', 'mat');

        $this->assertEqualsCanonicalizing(['i'.$dueThisWeek->id, 'i'.$overdue->id], $rows->pluck('id')->all());
        $this->assertSame('2026-11-03', $rows->firstWhere('id', 'i'.$dueThisWeek->id)['due']);

        $this->actingAs($user)
            ->postJson(route('transaction-portal.settle'), ['items' => ['i'.$overdue->id], 'date' => '2026-11-04'])
            ->assertOk();

        $overdue->refresh();
        $this->assertSame('Closed', $overdue->status);
        $this->assertEquals(21973.19, (float) $overdue->settled_amount);

        $this->actingAs($user)->postJson(route('transaction-portal.reopen'), ['item' => 'i'.$overdue->id])->assertOk();
        $this->assertSame('Open', $overdue->fresh()->status);
        $this->assertNull($overdue->fresh()->settled_amount);
    }

    public function test_labor_and_commission_payables_go_to_their_own_sections(): void
    {
        $project = $this->project();
        $labor = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560, 'transaction_date' => '2026-11-02']);
        $commission = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -2063.08, 'transaction_date' => '2026-10-23', 'due_date' => '2026-10-30']);
        $paidLastMonth = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -50, 'transaction_date' => '2026-09-01', 'status' => 'Closed']);

        $rows = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->keyBy('id');

        $this->assertSame('lab', $rows['t'.$labor->id]['b']);
        $this->assertNull($rows['t'.$labor->id]['due']);
        $this->assertEquals(4560, $rows['t'.$labor->id]['amount']);
        $this->assertSame('com', $rows['t'.$commission->id]['b']); // overdue, carried into this week
        $this->assertFalse($rows->has('t'.$paidLastMonth->id));
    }

    public function test_a_paid_payable_sits_in_the_week_it_was_paid(): void
    {
        $project = $this->project();
        // Hand-entered commission: payment received 10/30, no due date, paid 11/06.
        $paidThisWeek = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -20262, 'transaction_date' => '2026-10-30', 'status' => 'Closed', 'settled_amount' => 20262, 'settled_date' => '2026-11-06']);
        // Falls in this week, but was paid the week before.
        $paidLastWeek = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560, 'transaction_date' => '2026-11-02', 'status' => 'Closed', 'settled_amount' => 4560, 'settled_date' => '2026-10-28']);
        // Closed before the portal existed: no paid date, stays on its own date.
        $legacy = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -900, 'transaction_date' => '2026-11-03', 'status' => 'Closed']);
        $materialPaid = $this->invoice($project, 'material', 7000, '2026-08-01'); // due 08/31, paid 11/05
        $materialPaid->update(['status' => 'Closed', 'settled_amount' => 7000, 'settled_date' => '2026-11-05']);
        $materialPaidEarly = $this->invoice($project, 'material', 800, '2026-10-04'); // due 11/03, paid 10/20
        $materialPaidEarly->update(['status' => 'Closed', 'settled_amount' => 800, 'settled_date' => '2026-10-20']);

        $user = $this->finance();
        $ids = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->pluck('id');

        $this->assertTrue($ids->contains('t'.$paidThisWeek->id));
        $this->assertFalse($ids->contains('t'.$paidLastWeek->id));
        $this->assertTrue($ids->contains('t'.$legacy->id));
        $this->assertTrue($ids->contains('i'.$materialPaid->id));
        $this->assertFalse($ids->contains('i'.$materialPaidEarly->id));

        $lastWeek = collect($this->actingAs($user)->getJson(route('transaction-portal.data', ['start' => '2026-10-25', 'end' => '2026-10-31']))->json('rows'))->pluck('id');

        $this->assertFalse($lastWeek->contains('t'.$paidThisWeek->id));
        $this->assertTrue($lastWeek->contains('t'.$paidLastWeek->id));
    }

    public function test_export_downloads_the_week_as_csv(): void
    {
        $this->transaction($this->project(), ['amount' => 5000]);

        $response = $this->actingAs($this->finance())->get(route('transaction-portal.export', $this->week()));

        $response->assertOk();
        $this->assertStringContainsString('Expected Accounts Receivable', $response->streamedContent());
    }
}
