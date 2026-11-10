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
        // Actual AR lists the payment itself (p<id>), tied to its transaction.
        $this->assertTrue($rows->contains(fn ($r) => $r['b'] === 'aar' && $r['id'] === 'p'.$payment->payments()->sole()->id && $r['transactionId'] === $payment->id));
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

    public function test_a_past_week_shows_only_the_payables_paid_in_it(): void
    {
        $project = $this->project();
        // Last week is 10/25 - 10/31 (today is 11/04).
        $openLabor = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560, 'transaction_date' => '2026-10-27']);
        $openCommission = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -2063.08, 'transaction_date' => '2026-10-20', 'due_date' => '2026-10-30']);
        $carriedCommission = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -300, 'transaction_date' => '2026-10-01', 'due_date' => '2026-10-09']);
        $openMaterial = $this->invoice($project, 'material', 18320.75, '2026-09-28'); // due 10/28
        $paidLabor = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -900, 'transaction_date' => '2026-10-20', 'status' => 'Closed', 'settled_amount' => 900, 'settled_date' => '2026-10-29']);
        $paidMaterial = $this->invoice($project, 'material', 700, '2026-09-01');
        $paidMaterial->update(['status' => 'Closed', 'settled_amount' => 700, 'settled_date' => '2026-10-26']);
        $expected = $this->transaction($project, ['transaction_date' => '2026-10-28']);

        $user = $this->finance();
        $lastWeek = collect($this->actingAs($user)->getJson(route('transaction-portal.data', ['start' => '2026-10-25', 'end' => '2026-10-31']))->json('rows'))->pluck('id');

        $this->assertEqualsCanonicalizing(
            ['t'.$paidLabor->id, 'i'.$paidMaterial->id, 't'.$expected->id],
            $lastWeek->all()
        );

        // The open ones are carried into the current week instead.
        $thisWeek = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->pluck('id');

        foreach (['t'.$openLabor->id, 't'.$openCommission->id, 't'.$carriedCommission->id, 'i'.$openMaterial->id] as $id) {
            $this->assertTrue($thisWeek->contains($id), "$id should be carried into this week");
        }

        // A past month follows the same rule.
        $october = collect($this->actingAs($user)->getJson(route('transaction-portal.data', ['start' => '2026-10-01', 'end' => '2026-10-31']))->json('rows'))->pluck('id');

        $this->assertFalse($october->contains('t'.$openCommission->id));
        $this->assertFalse($october->contains('i'.$openMaterial->id));
        $this->assertTrue($october->contains('t'.$paidLabor->id));
    }

    public function test_a_batch_hides_and_moves_nothing(): void
    {
        $project = $this->project();
        // Today is Wednesday 11/04; the batch is for Friday 11/06.
        $labor = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560, 'transaction_date' => '2026-11-02']);
        $carried = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -300, 'transaction_date' => '2026-10-01', 'due_date' => '2026-10-09']);
        $commission = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -2063.08, 'transaction_date' => '2026-10-27', 'due_date' => '2026-11-05']);
        $material = $this->invoice($project, 'material', 18320.75, '2026-10-04'); // due 11/03
        $paid = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -900, 'transaction_date' => '2026-10-20', 'status' => 'Closed', 'settled_amount' => 900, 'settled_date' => '2026-11-03']);
        $expected = $this->transaction($project, ['transaction_date' => '2026-11-05']);
        // Due after this week, but put in this week's batch.
        $later = $this->transaction($project, ['payee' => 'supplier', 'amount' => -150, 'transaction_date' => '2026-11-20']);

        $user = $this->finance();
        $all = ['t'.$labor->id, 't'.$carried->id, 't'.$commission->id, 'i'.$material->id, 't'.$paid->id, 't'.$expected->id];
        $before = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->pluck('id');
        $this->assertEqualsCanonicalizing($all, $before->all());

        $this->actingAs($user)
            ->postJson(route('transaction-portal.pay-run'), ['items' => ['t'.$labor->id, 't'.$carried->id, 't'.$later->id], 'date' => '2026-11-06'])
            ->assertOk()
            ->assertJsonPath('updated', 3);

        $this->assertSame('2026-11-06', substr((string) $labor->fresh()->pay_run_date, 0, 10));
        $this->assertDatabaseHas('activity_log', ['subject_id' => $labor->id, 'description' => $user->name.' put the account transaction in the 11/06/2026 batch from the Transaction Portal.']);

        // Every lane keeps every row; the batched one due later is listed too, so the Batch filter finds it.
        $thisWeek = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->keyBy('id');
        $this->assertEqualsCanonicalizing(array_merge($all, ['t'.$later->id]), $thisWeek->keys()->all());
        $this->assertSame('2026-11-06', $thisWeek['t'.$labor->id]['payRun']);
        $this->assertNull($thisWeek['t'.$commission->id]['payRun']);

        // Nothing is pushed into next week either: it shows there only as it always would (still open, carried).
        $nextWeek = collect($this->actingAs($user)->getJson(route('transaction-portal.data', ['start' => '2026-11-08', 'end' => '2026-11-14']))->json('rows'))->pluck('id');
        $this->assertTrue($nextWeek->contains('t'.$commission->id));

        // Taking them out changes nothing else.
        $this->actingAs($user)
            ->postJson(route('transaction-portal.pay-run'), ['items' => ['t'.$labor->id, 't'.$carried->id, 't'.$later->id], 'date' => null])
            ->assertOk();

        $thisWeek = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->pluck('id');
        $this->assertEqualsCanonicalizing($all, $thisWeek->all());
        $this->assertNull($labor->fresh()->pay_run_date);
    }

    public function test_a_material_invoice_can_be_put_in_a_batch(): void
    {
        $project = $this->project();
        $picked = $this->invoice($project, 'material', 500, '2026-10-04'); // due 11/03
        $left = $this->invoice($project, 'material', 700, '2026-10-05'); // due 11/04

        $user = $this->finance();
        $this->actingAs($user)
            ->postJson(route('transaction-portal.pay-run'), ['items' => ['i'.$picked->id], 'date' => '2026-11-06'])
            ->assertOk();

        $rows = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->keyBy('id');

        $this->assertSame('2026-11-06', $rows['i'.$picked->id]['payRun']);
        $this->assertTrue($rows->has('i'.$left->id), 'The invoice left out stays where it was.');
        $this->assertNull($rows['i'.$left->id]['payRun']);
    }

    public function test_a_batch_takes_only_payables_on_a_day_still_to_come(): void
    {
        $project = $this->project();
        $labor = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560]);
        $expected = $this->transaction($project, []);

        $this->actingAs($this->finance())
            ->postJson(route('transaction-portal.pay-run'), ['items' => ['t'.$labor->id], 'date' => '2026-11-03'])
            ->assertStatus(422);

        $this->actingAs($this->finance())
            ->postJson(route('transaction-portal.pay-run'), ['items' => ['t'.$expected->id], 'date' => '2026-11-06'])
            ->assertStatus(422);

        $this->actingAs($this->fundingManager())
            ->postJson(route('transaction-portal.pay-run'), ['items' => ['t'.$labor->id], 'date' => '2026-11-06'])
            ->assertForbidden();

        $this->assertNull($labor->fresh()->pay_run_date);
        $this->assertNull($expected->fresh()->pay_run_date);
    }

    public function test_several_expected_payments_can_be_received_at_once(): void
    {
        $project = $this->project();
        $this->invoice($project, 'labor', 1000);
        $this->invoice($project, 'material', 2000);
        $first = $this->transaction($project, ['amount' => 5000]);
        $second = $this->transaction($project, ['amount' => 3000, 'deduction_amount' => 500, 'transaction_date' => '2026-10-20']);
        $other = $this->transaction($this->project('Paul Wilhite'), ['amount' => 700]);

        // The Funding Manager owns Expected AR, so the bulk bar works for them too.
        $this->actingAs($this->fundingManager())
            ->postJson(route('transaction-portal.settle'), ['items' => ['t'.$first->id, 't'.$second->id, 't'.$other->id], 'date' => '2026-11-03'])
            ->assertOk()
            ->assertJsonPath('closed', 3);

        foreach ([[$first, 5000], [$second, 2500], [$other, 700]] as [$t, $received]) {
            $t->refresh();
            $this->assertSame('Closed', $t->status);
            $this->assertEquals($received, (float) $t->settled_amount); // amount less its deduction
            $this->assertSame('2026-11-03', substr((string) $t->settled_date, 0, 10));
        }

        // Each payment is received the way a single one is: the commission follows.
        $commission = AccountTransaction::where('project_id', $project->id)->where('payee', 'sales_partner')->sum('amount');
        $this->assertEquals(-4500, (float) $commission); // 5000 + 2500 - 1000 - 2000

        // They now show in Actual AR for the week received.
        $aar = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->where('b', 'aar')->pluck('transactionId');
        $this->assertEqualsCanonicalizing([$first->id, $second->id, $other->id], $aar->all());
    }

    public function test_received_payments_can_be_deleted_with_the_edit_permission(): void
    {
        $project = $this->project();
        $handEntered = $this->transaction($project, ['amount' => 16500, 'milestone' => 'Permit Approval Date', 'status' => 'Closed', 'settled_amount' => 16500, 'settled_date' => '2026-11-02']);
        $imported = $this->transaction($project, ['amount' => 16500, 'milestone' => 'Permit', 'transaction_details' => 'Imported from Untitled spreadsheet.xlsx', 'status' => 'Closed', 'settled_amount' => 16500, 'settled_date' => '2026-11-03']);
        $expected = $this->transaction($project, ['amount' => 700]);
        // A commission raised after the duplicate was received.
        $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -900, 'transaction_date' => '2026-11-03']);

        // Hints on the Actual AR rows.
        $rows = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->keyBy('id');
        $this->assertTrue($rows['t'.$imported->id]['imported']);
        $this->assertFalse($rows['t'.$handEntered->id]['imported']);
        $this->assertTrue($rows['t'.$imported->id]['twin']);
        $this->assertTrue($rows['t'.$handEntered->id]['twin']);
        $this->assertTrue($rows['t'.$imported->id]['commissionAfter']);

        // The portal permissions alone are not enough to delete.
        $finance = $this->finance();
        $this->actingAs($finance)->get(route('transaction-portal.index'))->assertSee('"canDelete":false', false);
        $this->actingAs($finance)->postJson(route('transaction-portal.delete-received'), ['items' => ['t'.$imported->id]])->assertForbidden();

        $editor = $this->user('Finance Editor', [
            TransactionPortalService::PERMISSION_PAGE,
            TransactionPortalService::PERMISSION_EXPECTED,
            TransactionPortalService::PERMISSION_OTHER,
            TransactionPortalService::PERMISSION_DELETE,
        ]);
        $this->actingAs($editor)->get(route('transaction-portal.index'))->assertSee('"canDelete":true', false);

        // Only received payments: one other row in the batch stops the whole batch.
        $this->actingAs($editor)->postJson(route('transaction-portal.delete-received'), ['items' => ['t'.$imported->id, 't'.$expected->id]])->assertStatus(422);
        $this->assertNotSoftDeleted($imported);

        $this->actingAs($editor)->postJson(route('transaction-portal.delete-received'), ['items' => ['t'.$imported->id]])
            ->assertOk()->assertJsonPath('deleted', 1);

        $this->assertSoftDeleted($imported);
        $this->assertNotSoftDeleted($handEntered);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $imported->id, 'event' => 'deleted', 'description' => $editor->name.' deleted the received payment from the Transaction Portal.']);

        $ids = collect($this->actingAs($editor)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->keyBy('id');
        $this->assertFalse($ids->has('t'.$imported->id));
        $this->assertFalse($ids['t'.$handEntered->id]['twin'], 'With the copy gone, no twin is left.');

        // A Funding Manager (no Actual AR section) cannot, even with the edit permission.
        $fm = $this->user('Funding Manager', [TransactionPortalService::PERMISSION_PAGE, TransactionPortalService::PERMISSION_EXPECTED, TransactionPortalService::PERMISSION_DELETE]);
        $this->actingAs($fm)->postJson(route('transaction-portal.delete-received'), ['items' => ['t'.$handEntered->id]])->assertForbidden();
        $this->assertNotSoftDeleted($handEntered);
    }

    public function test_payables_to_every_other_payee_go_to_other_payables(): void
    {
        $project = $this->project();
        \App\Models\Payee::create(['key' => 'maxwell_power', 'label' => 'Maxwell Power']);

        $supplier = $this->transaction($project, ['payee' => 'supplier', 'amount' => -9072, 'milestone' => 'Panels', 'transaction_date' => '2026-11-05']);
        $maxwell = $this->transaction($project, ['payee' => 'maxwell_power', 'amount' => -1200, 'transaction_date' => '2026-10-20']); // overdue, carried
        $refund = $this->transaction($project, ['payee' => 'customer', 'amount' => -75.50, 'transaction_date' => '2026-11-03']);
        $noPayee = $this->transaction($project, ['payee' => null, 'amount' => -40, 'transaction_date' => '2026-11-02']);
        $nextWeek = $this->transaction($project, ['payee' => 'others', 'amount' => -10, 'transaction_date' => '2026-11-12']);
        $labor = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560]);
        $commission = $this->transaction($project, ['payee' => 'sales_partner', 'amount' => -300]);

        $user = $this->finance();
        $rows = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->keyBy('id');
        $other = $rows->where('b', 'oth');

        $this->assertEqualsCanonicalizing(
            ['t'.$supplier->id, 't'.$maxwell->id, 't'.$refund->id, 't'.$noPayee->id],
            $other->keys()->all()
        );
        $this->assertSame('lab', $rows['t'.$labor->id]['b']);
        $this->assertSame('com', $rows['t'.$commission->id]['b']);
        $this->assertFalse($rows->has('t'.$nextWeek->id));

        $row = $other['t'.$supplier->id];
        $this->assertSame('Supplier', $row['payee']);
        $this->assertSame('Panels', $row['milestone']);
        $this->assertEquals(9072, $row['amount']);
        $this->assertSame('2026-11-05', $row['due']);
        $this->assertSame('Maxwell Power', $other['t'.$maxwell->id]['payee']);

        // The Funding Manager's view stays Expected AR only.
        $this->assertSame([], collect($this->actingAs($this->fundingManager())->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->where('b', 'oth')->all());

        // It is paid, reopened and put in a batch like any payable.
        $this->actingAs($user)->postJson(route('transaction-portal.settle'), ['items' => ['t'.$refund->id], 'date' => '2026-11-04'])->assertOk()->assertJsonPath('closed', 1);
        $this->assertEquals(75.50, (float) $refund->fresh()->settled_amount);
        $this->actingAs($user)->postJson(route('transaction-portal.reopen'), ['item' => 't'.$refund->id])->assertOk();
        $this->assertSame('Open', $refund->fresh()->status);

        // Batching an Other Payable hides nothing, in its lane or any other.
        $this->actingAs($user)->postJson(route('transaction-portal.pay-run'), ['items' => ['t'.$supplier->id], 'date' => '2026-11-06'])->assertOk()->assertJsonPath('updated', 1);
        $rows = collect($this->actingAs($user)->getJson(route('transaction-portal.data', $this->week()))->json('rows'))->keyBy('id');
        $this->assertSame('2026-11-06', $rows['t'.$supplier->id]['payRun']);
        $this->assertTrue($rows->has('t'.$maxwell->id));
        $this->assertTrue($rows->has('t'.$labor->id));
    }

    public function test_all_time_search_finds_rows_from_any_week(): void
    {
        $sunrise = $this->project('Sunrise Ridge');
        $other = $this->project('Yunjiao Guan'); // same customer name (Rito Guzman) in the fixture
        // Today is 11/04: these sit in other weeks and months, open and paid.
        $paidLongAgo = $this->transaction($sunrise, ['payee' => 'sales_partner', 'amount' => -2063.08, 'transaction_date' => '2026-06-01', 'status' => 'Closed', 'settled_amount' => 2063.08, 'settled_date' => '2026-06-12']);
        $receivedLongAgo = $this->transaction($sunrise, ['amount' => 5000, 'transaction_date' => '2026-05-01', 'status' => 'Closed', 'settled_amount' => 5000, 'settled_date' => '2026-05-03']);
        $nextMonth = $this->transaction($sunrise, ['payee' => 'sub_contractor', 'amount' => -4560, 'transaction_date' => '2026-12-15']);
        $expected = $this->transaction($sunrise, ['transaction_date' => '2027-01-10']);
        $material = $this->invoice($sunrise, 'material', 700, '2026-03-01');
        $notRito = $this->transaction($other, ['payee' => 'sales_partner', 'amount' => -10, 'transaction_date' => '2026-06-01']);

        $rows = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.search', ['q' => 'sunrise']))->assertOk()->json('rows'))->keyBy('id');

        $this->assertEqualsCanonicalizing(
            ['t'.$paidLongAgo->id, 't'.$receivedLongAgo->id, 't'.$nextMonth->id, 't'.$expected->id, 'i'.$material->id],
            $rows->keys()->all()
        );
        $this->assertSame('com', $rows['t'.$paidLongAgo->id]['b']);
        $this->assertSame('aar', $rows['t'.$receivedLongAgo->id]['b']);
        $this->assertSame('lab', $rows['t'.$nextMonth->id]['b']);
        $this->assertSame('ear', $rows['t'.$expected->id]['b']);
        $this->assertSame('mat', $rows['i'.$material->id]['b']);
        // Newest first.
        $this->assertSame('t'.$expected->id, $rows->keys()->first());

        // By amount, by sales partner.
        $byAmount = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.search', ['q' => '$4,560']))->json('rows'))->pluck('id');
        $this->assertEquals(['t'.$nextMonth->id], $byAmount->all());

        $byPartner = collect($this->actingAs($this->finance())->getJson(route('transaction-portal.search', ['q' => 'Artemis']))->json('rows'))->pluck('id');
        $this->assertTrue($byPartner->contains('t'.$notRito->id));
        $this->assertTrue($byPartner->contains('t'.$paidLongAgo->id));
    }

    public function test_all_time_search_keeps_to_the_sections_the_user_may_see(): void
    {
        $project = $this->project();
        $expected = $this->transaction($project, ['transaction_date' => '2026-07-01']);
        $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560, 'transaction_date' => '2026-07-01']);
        $this->invoice($project, 'material', 700, '2026-07-01');

        $ids = collect($this->actingAs($this->fundingManager())->getJson(route('transaction-portal.search', ['q' => 'Rito']))->assertOk()->json('rows'))->pluck('id');

        $this->assertEquals(['t'.$expected->id], $ids->all());

        $this->actingAs($this->finance())->getJson(route('transaction-portal.search', ['q' => 'R']))->assertStatus(422);
    }

    public function test_export_downloads_the_week_as_csv(): void
    {
        $this->transaction($this->project(), ['amount' => 5000]);
        $this->transaction($this->project('Paul Wilhite'), ['payee' => 'supplier', 'amount' => -900]);

        $response = $this->actingAs($this->finance())->get(route('transaction-portal.export', $this->week()));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Expected Accounts Receivable', $csv);
        $this->assertStringContainsString('Other Payables', $csv);
        $this->assertStringContainsString(',Supplier', $csv);
    }

    private function rows(User $user, array $period = []): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($user)->getJson(route('transaction-portal.data', $period ?: $this->week()))->json('rows'))->keyBy('id');
    }

    public function test_a_part_payment_keeps_the_row_open_with_its_balance_and_payments_add_up(): void
    {
        $project = $this->project();
        $this->invoice($project, 'labor', 1000);
        $this->invoice($project, 'material', 2000);
        $payment = $this->transaction($project, ['amount' => 20000, 'transaction_date' => '2026-11-02']);
        $finance = $this->finance();

        $this->actingAs($finance)
            ->postJson(route('transaction-portal.settle'), ['items' => ['t'.$payment->id], 'date' => '2026-11-03', 'amount' => 5000])
            ->assertOk()
            ->assertJsonPath('closed', 0)
            ->assertJsonPath('partial.0.paid', 5000)
            ->assertJsonPath('partial.0.balance', 15000);

        $payment->refresh();
        $this->assertSame('Open', $payment->status);
        $this->assertEquals(5000, (float) $payment->settled_amount);

        // Still expected for the balance, and the money received is in Actual AR.
        $rows = $this->rows($finance);
        $expected = $rows['t'.$payment->id];
        $this->assertSame('ear', $expected['b']);
        $this->assertEquals(15000, $expected['amount']);
        $this->assertEquals(20000, $expected['total']);
        $this->assertEquals(5000, $expected['paid']);
        $this->assertCount(1, $expected['payments']);
        $this->assertSame('2026-11-03', $expected['payments'][0]['date']);
        $first = $payment->payments()->sole();
        $this->assertSame('aar', $rows['p'.$first->id]['b']);
        $this->assertEquals(5000, $rows['p'.$first->id]['amount']);
        $this->assertTrue($rows['p'.$first->id]['partOpen']);

        // The commission counts the part payment: 5000 - 1000 - 2000.
        $this->assertEquals(-2000, (float) AccountTransaction::where('project_id', $project->id)->where('payee', 'sales_partner')->sum('amount'));

        // The rest arrives later: it adds up and closes the row.
        $this->actingAs($finance)
            ->postJson(route('transaction-portal.settle'), ['items' => ['t'.$payment->id], 'date' => '2026-11-05', 'amount' => 15000])
            ->assertOk()
            ->assertJsonPath('closed', 1);

        $payment->refresh();
        $this->assertSame('Closed', $payment->status);
        $this->assertEquals(20000, (float) $payment->settled_amount);
        $this->assertSame('2026-11-05', substr((string) $payment->settled_date, 0, 10));
        $this->assertEquals(-17000, (float) AccountTransaction::where('project_id', $project->id)->where('payee', 'sales_partner')->sum('amount'));

        $rows = $this->rows($finance);
        $this->assertFalse($rows->has('t'.$payment->id));
        $received = $rows->where('b', 'aar')->where('transactionId', $payment->id);
        $this->assertEqualsCanonicalizing([5000, 15000], $received->pluck('amount')->all());
        $this->assertCount(2, $received->first()['payments']);

        // Each payment sits in the week it arrived: none of them next week.
        $this->assertTrue($this->rows($finance, ['start' => '2026-11-08', 'end' => '2026-11-14'])->where('b', 'aar')->isEmpty());

        $this->assertDatabaseHas('activity_log', ['subject_id' => $payment->id, 'event' => 'payment', 'description' => $finance->name.' recorded a part payment of $5,000.00 on 11/03/2026 ($5,000.00 of $20,000.00 paid) on the account transaction.']);
    }

    public function test_a_payable_can_be_paid_in_parts_or_closed_short(): void
    {
        $project = $this->project();
        $labor = $this->transaction($project, ['payee' => 'sub_contractor', 'amount' => -4560, 'transaction_date' => '2026-11-02']);
        $material = $this->invoice($project, 'material', 9000, '2026-10-01');
        $finance = $this->finance();

        $this->actingAs($finance)->postJson(route('transaction-portal.settle'), ['items' => ['i'.$material->id], 'date' => '2026-11-03', 'amount' => 4000])->assertOk();
        $this->assertSame('Open', $material->fresh()->status);
        $this->assertEquals(5000, $this->rows($finance)['i'.$material->id]['amount'], 'An open payable shows what is left to pay.');

        // The bulk bar pays the balance, not the whole invoice again.
        $this->actingAs($finance)->postJson(route('transaction-portal.settle'), ['items' => ['i'.$material->id], 'date' => '2026-11-04'])->assertJsonPath('closed', 1);
        $this->assertEquals(9000, (float) $material->fresh()->settled_amount);
        $this->assertSame(2, $material->payments()->count());

        // Less than the balance, marked as the last payment: closed short.
        $this->actingAs($finance)->postJson(route('transaction-portal.settle'), ['items' => ['t'.$labor->id], 'date' => '2026-11-03', 'amount' => 4000, 'final' => true])
            ->assertJsonPath('closed', 1);
        $labor->refresh();
        $this->assertSame('Closed', $labor->status);
        $this->assertEquals(4000, (float) $labor->settled_amount);

        // Reopen undoes every payment.
        $this->actingAs($finance)->postJson(route('transaction-portal.reopen'), ['item' => 'i'.$material->id])->assertOk();
        $material->refresh();
        $this->assertSame('Open', $material->status);
        $this->assertNull($material->settled_amount);
        $this->assertSame(0, $material->payments()->count());
    }

    public function test_one_payment_can_be_removed_from_the_history(): void
    {
        $project = $this->project();
        $payment = $this->transaction($project, ['amount' => 20000]);
        $finance = $this->finance();

        foreach ([['2026-11-02', 5000], ['2026-11-03', 15000]] as [$date, $amount]) {
            $this->actingAs($finance)->postJson(route('transaction-portal.settle'), ['items' => ['t'.$payment->id], 'date' => $date, 'amount' => $amount])->assertOk();
        }
        $this->assertSame('Closed', $payment->fresh()->status);
        $second = $payment->payments()->get()->last();

        // A Funding Manager has no Actual AR section, and the row is closed (received).
        $this->actingAs($this->fundingManager())->postJson(route('transaction-portal.remove-payment'), ['payment' => $second->id])->assertForbidden();

        $this->actingAs($finance)->postJson(route('transaction-portal.remove-payment'), ['payment' => $second->id])->assertOk();

        $payment->refresh();
        $this->assertSame('Open', $payment->status);
        $this->assertEquals(5000, (float) $payment->settled_amount);
        $this->assertSame('2026-11-02', substr((string) $payment->settled_date, 0, 10));
        $this->assertSoftDeleted($second);
        $this->assertEquals(15000, $this->rows($finance)['t'.$payment->id]['amount']);

        // Open again, so the Funding Manager (Expected AR) may undo the one left.
        $first = $payment->payments()->sole();
        $this->actingAs($this->fundingManager())->postJson(route('transaction-portal.remove-payment'), ['payment' => $first->id])->assertOk();
        $this->assertNull($payment->fresh()->settled_amount);
    }

    public function test_deleting_one_of_several_received_payments_keeps_the_row(): void
    {
        $project = $this->project();
        $payment = $this->transaction($project, ['amount' => 20000]);
        $single = $this->transaction($project, ['amount' => 700]);
        $editor = $this->user('Finance Editor', [
            TransactionPortalService::PERMISSION_PAGE,
            TransactionPortalService::PERMISSION_EXPECTED,
            TransactionPortalService::PERMISSION_OTHER,
            TransactionPortalService::PERMISSION_DELETE,
        ]);

        foreach ([['t'.$payment->id, 5000], ['t'.$payment->id, 5000], ['t'.$single->id, 700]] as [$item, $amount]) {
            $this->actingAs($editor)->postJson(route('transaction-portal.settle'), ['items' => [$item], 'date' => '2026-11-03', 'amount' => $amount])->assertOk();
        }
        $duplicate = $payment->payments()->get()->last();

        $this->actingAs($editor)->postJson(route('transaction-portal.delete-received'), ['items' => ['p'.$duplicate->id]])->assertOk();
        $this->assertNotSoftDeleted($payment);
        $this->assertEquals(5000, (float) $payment->fresh()->settled_amount);

        // A row's only payment: the whole received transaction goes, as before.
        $this->actingAs($editor)->postJson(route('transaction-portal.delete-received'), ['items' => ['p'.$single->payments()->sole()->id]])->assertOk();
        $this->assertSoftDeleted($single);
    }

    private function creator(array $extra = [TransactionPortalService::PERMISSION_CREATE, TransactionPortalService::PERMISSION_INVOICE]): User
    {
        return $this->user('Finance Creator', array_merge([
            TransactionPortalService::PERMISSION_PAGE,
            TransactionPortalService::PERMISSION_EXPECTED,
            TransactionPortalService::PERMISSION_OTHER,
        ], $extra));
    }

    public function test_each_section_can_be_added_to_from_the_portal(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $project = $this->project();
        $this->invoice($project, 'labor', 500);
        $this->invoice($project, 'material', 700);
        $user = $this->creator();
        $store = fn (array $data) => $this->actingAs($user)->post(route('transaction-portal.store'), $data + ['project_id' => $project->id], ['Accept' => 'application/json']);
        $base = ['milestone' => 'M1', 'transaction_date' => '2026-11-03', 'transaction_details' => 'Added in the portal'];

        $this->actingAs($user)->get(route('transaction-portal.index'))->assertSee('id="new-btn"', false)->assertSee('"creatable":["ear","aar","com","lab","mat","oth"]', false);
        $this->assertSame([$project->id], collect($this->actingAs($user)->getJson(route('transaction-portal.projects'))->assertOk()->json())->pluck('id')->all());

        // Expected AR: positive and Open; the board shows it less its deduction.
        $ear = $store($base + ['section' => 'ear', 'payee' => 'customer', 'amount' => 5000, 'deduction_amount' => 200])->assertOk()->json('item');
        $expected = AccountTransaction::findOrFail((int) substr($ear, 1));
        $this->assertSame('Open', $expected->status);
        $this->assertEquals(5000, (float) $expected->amount);
        $this->assertEquals(4800, $this->rows($user)[$ear]['amount']);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $expected->id, 'event' => 'created', 'description' => $user->name.' created the account transaction from the Transaction Portal.']);

        // Actual AR: added and received at once - in part here, so it is still expected for the rest.
        $aar = $store($base + ['section' => 'aar', 'payee' => 'customer', 'amount' => 3000, 'received_amount' => 1000, 'received_date' => '2026-11-03'])
            ->assertOk()->assertJsonPath('commission', null)->json('item');
        $received = AccountTransaction::findOrFail((int) substr($aar, 1));
        $this->assertSame('Open', $received->status);
        $this->assertEquals(1000, (float) $received->settled_amount);
        $this->assertTrue($this->rows($user)->contains(fn ($r) => $r['b'] === 'aar' && $r['transactionId'] === $received->id && $r['amount'] == 1000));

        // Commission: negative, to the sales partner, due the Friday of the week after.
        $com = AccountTransaction::findOrFail((int) substr($store($base + ['section' => 'com', 'amount' => 900])->assertOk()->json('item'), 1));
        $this->assertSame('sales_partner', $com->payee);
        $this->assertEquals(-900, (float) $com->amount);
        $this->assertSame('2026-11-13', substr((string) $com->due_date, 0, 10));

        // Labor: negative, to the sub-contractor.
        $lab = AccountTransaction::findOrFail((int) substr($store($base + ['section' => 'lab', 'amount' => 4560])->assertOk()->json('item'), 1));
        $this->assertSame('sub_contractor', $lab->payee);
        $this->assertEquals(-4560, (float) $lab->amount);

        // Other: any payee without a section of its own.
        $store($base + ['section' => 'oth', 'payee' => 'sales_partner', 'amount' => 75])->assertStatus(422)->assertJsonValidationErrors('payee');
        $oth = AccountTransaction::findOrFail((int) substr($store($base + ['section' => 'oth', 'payee' => 'supplier', 'amount' => 75, 'due_date' => '2026-11-06'])->assertOk()->json('item'), 1));
        $this->assertSame('oth', $this->rows($user)['t'.$oth->id]['b']);

        // Material: an invoice with its file, which also updates the project's cost.
        $mat = $store(['section' => 'mat', 'amount' => 1300, 'invoice_date' => '2026-10-20', 'file' => \Illuminate\Http\UploadedFile::fake()->create('invoice.pdf', 20, 'application/pdf')])->assertOk()->json('item');
        $invoice = ProjectInvoiceDetail::findOrFail((int) substr($mat, 1));
        $this->assertSame('material', $invoice->invoice_type);
        $this->assertSame('Open', $invoice->status);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($invoice->file_path);
        $this->assertEquals(2000, (float) $project->fresh()->actual_material_cost);

        // The fields each section needs are asked for.
        $store(['section' => 'com'])->assertStatus(422)->assertJsonValidationErrors(['amount', 'milestone', 'transaction_date', 'transaction_details']);
        $store(['section' => 'mat', 'amount' => 10, 'invoice_date' => '2026-10-20'])->assertStatus(422)->assertJsonValidationErrors('file');
        $store($base + ['section' => 'aar', 'payee' => 'customer', 'amount' => 10])->assertStatus(422)->assertJsonValidationErrors(['received_amount', 'received_date']);
    }

    public function test_adding_needs_the_permission_the_project_page_needs(): void
    {
        $project = $this->project();
        $data = ['section' => 'ear', 'project_id' => $project->id, 'payee' => 'customer', 'milestone' => 'M1', 'amount' => 10, 'transaction_date' => '2026-11-03', 'transaction_details' => 'x'];

        // The portal permissions alone: no "+ New".
        $finance = $this->finance();
        $this->actingAs($finance)->get(route('transaction-portal.index'))->assertDontSee('id="new-btn"', false);
        $this->actingAs($finance)->postJson(route('transaction-portal.store'), $data)->assertForbidden();
        $this->actingAs($finance)->getJson(route('transaction-portal.projects'))->assertForbidden();

        // Account Transactions Edit without Invoice Details: everything but Material.
        $noInvoices = $this->creator([TransactionPortalService::PERMISSION_CREATE]);
        $this->assertSame(['ear', 'aar', 'com', 'lab', 'oth'], app(TransactionPortalService::class)->creatableSections($noInvoices));
        $this->actingAs($noInvoices)->postJson(route('transaction-portal.store'), ['section' => 'mat', 'project_id' => $project->id])->assertForbidden();

        // A Funding Manager adds only to the section they see.
        $fm = $this->user('Funding Manager', [TransactionPortalService::PERMISSION_PAGE, TransactionPortalService::PERMISSION_EXPECTED, TransactionPortalService::PERMISSION_CREATE]);
        $this->actingAs($fm)->postJson(route('transaction-portal.store'), ['section' => 'com'] + $data)->assertForbidden();
        $this->actingAs($fm)->postJson(route('transaction-portal.store'), $data)->assertOk();

        // A project that does not exist (or was deleted) is refused.
        $this->actingAs($fm)->postJson(route('transaction-portal.store'), ['project_id' => 999999] + $data)->assertStatus(422);
    }

    public function test_search_finds_a_part_paid_payment_as_expected_and_received(): void
    {
        $project = $this->project('Sunrise Solar');
        $payment = $this->transaction($project, ['amount' => 20000, 'transaction_date' => '2026-06-01']);
        $finance = $this->finance();
        $this->actingAs($finance)->postJson(route('transaction-portal.settle'), ['items' => ['t'.$payment->id], 'date' => '2026-06-03', 'amount' => 5000])->assertOk();

        $rows = collect($this->actingAs($finance)->getJson(route('transaction-portal.search', ['q' => 'sunrise']))->json('rows'));
        $this->assertSame(['aar', 'ear'], $rows->where('projectId', $project->id)->pluck('b')->sort()->values()->all());

        // By the amount of one payment.
        $byAmount = collect($this->actingAs($finance)->getJson(route('transaction-portal.search', ['q' => '5,000']))->json('rows'));
        $this->assertTrue($byAmount->contains('transactionId', $payment->id));
    }
}
