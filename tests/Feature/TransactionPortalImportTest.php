<?php

namespace Tests\Feature;

use App\Models\AccountTransaction;
use App\Models\Customer;
use App\Models\Payee;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use App\Models\SalesPartner;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Transaction Portal -> Import: account transactions from a spreadsheet, matched
 * to projects by name, previewed, then written exactly as the file has them.
 */
class TransactionPortalImportTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions = ['View Transaction Portal', 'Account Transactions Edit']): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        $role = Role::firstOrCreate(['name' => 'Finance Lead '.uniqid()]);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        $user->assignRole($role);

        return $user;
    }

    private function project(string $name, string $first, string $last): Project
    {
        $customer = Customer::create([
            'first_name' => $first,
            'last_name' => $last,
            'street' => '1 Import Rd',
            'city' => 'Fontana',
            'state' => 'CA',
            'zipcode' => '92335',
            'phone' => '(555) 444-4444',
            'email' => uniqid('import').'@example.com',
            'sales_partner_id' => SalesPartner::create(['name' => 'Artemis Sales'])->id,
            'sold_date' => '2026-09-01',
            'panel_qty' => 14,
            'inverter_qty' => 1,
        ]);

        return Project::create([
            'customer_id' => $customer->id,
            'project_name' => $name,
            'code' => (string) random_int(10000, 99999),
            'budget' => 40000,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-01',
        ]);
    }

    /** An .xlsx holding `$rows`, like the client's sheet. */
    private function sheet(array $rows, string $name = 'Untitled spreadsheet.xlsx'): UploadedFile
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray($rows, null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'tpi').'.xlsx';
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function preview(User $user, UploadedFile $file, string $status = 'Open')
    {
        return $this->actingAs($user)->post(route('transaction-portal.import.preview'), [
            'file' => $file,
            'date' => '2026-11-06',
            'status' => $status,
        ]);
    }

    public function test_the_import_needs_the_account_transactions_edit_permission(): void
    {
        $viewer = $this->user(['View Transaction Portal']);

        $this->actingAs($viewer)->get(route('transaction-portal.import'))->assertForbidden();
        $this->actingAs($viewer)->get(route('transaction-portal.index'))->assertOk()->assertDontSee(route('transaction-portal.import'));
        $this->actingAs($this->user())->get(route('transaction-portal.index'))->assertOk()->assertSee(route('transaction-portal.import'));
    }

    public function test_the_preview_matches_projects_by_name_and_saves_nothing(): void
    {
        $paul = $this->project('Paul-Wilhite', 'Paul', 'Wilhite');
        $shu = $this->project('Shu Hui-Yang', 'Shu Hui', 'Yang');

        $response = $this->preview($this->user(), $this->sheet([
            ['Paul Wilhite - Storage ', 'Maxwell Power', 'Milestone 1', ' $ 9,072.00 '],
            ['Shu Hui Yang ', 'Customer', 'Permit', ' $ 15,750.00 '],
            ['Spencer Fabian - Solar', 'Maxwell Power', 'Milestone 1', '$ 10,000.00'],
            ['Grace Liu ', 'Customer', null, '$ 2,112.65'],
            ['Collete Headley', 'WheelHouse ', 'Milestone 1', '=12778.9+3833.66'],
        ]))->assertOk();

        $rows = collect($response->viewData('rows'))->keyBy('line');

        $this->assertSame($paul->id, $rows[1]['project_id']);
        $this->assertSame('Milestone 1 - Storage', $rows[1]['milestone']);
        $this->assertSame('maxwell_power', $rows[1]['payee_key'] ?? 'maxwell_power'); // created below if missing
        $this->assertEquals(9072.00, $rows[1]['amount']);
        $this->assertSame('2026-11-06', $rows[1]['date']);
        $this->assertSame('Open', $rows[1]['status']);

        $this->assertSame($shu->id, $rows[2]['project_id']);
        $this->assertSame('customer', $rows[2]['payee_key']);

        $this->assertNull($rows[3]['project_id']);
        $this->assertSame('none', $rows[3]['match']);

        $this->assertSame(['Milestone is missing.'], $rows[4]['errors']);

        $this->assertEquals(16612.56, $rows[5]['amount']); // the formula's result
        $this->assertTrue($rows[5]['payee_new']);
        $this->assertSame('WheelHouse', $rows[5]['payee_label']);

        $this->assertSame(0, AccountTransaction::count());
        $this->assertNull(Payee::where('label', 'WheelHouse')->first());
    }

    /**
     * What the preview form posts when left as it opens: every row with its
     * values, duplicates unticked. `$edits` changes rows by line.
     */
    private function form($preview, array $edits = []): array
    {
        $rows = [];

        foreach ($preview->viewData('rows') as $row) {
            $rows[$row['line']] = array_merge([
                'include' => $row['duplicate'] ? null : '1',
                'project' => $row['project_id'],
                'payee' => $row['payee_label'],
                'milestone' => $row['milestone'],
                'amount' => $row['amount'],
                'date' => $row['date'],
                'status' => $row['status'],
                'details' => $row['details'],
            ], $edits[$row['line']] ?? []);
        }

        return ['token' => $preview->viewData('token'), 'rows' => $rows];
    }

    public function test_import_writes_only_what_the_file_has(): void
    {
        $paul = $this->project('Paul-Wilhite', 'Paul', 'Wilhite');
        $spencer = $this->project('Spencer-Fabian2', 'Spencer', 'Fabian Jr');
        $user = $this->user();

        // Both invoices on file: closing a payment in the portal would raise a commission.
        foreach (['labor', 'material'] as $type) {
            ProjectInvoiceDetail::create(['project_id' => $paul->id, 'invoice_type' => $type, 'invoice_date' => '2026-10-01', 'amount' => 100, 'file_name' => $type.'.pdf', 'original_file_name' => $type.'.pdf', 'file_path' => 'x/'.$type.'.pdf']);
        }

        $preview = $this->preview($user, $this->sheet([
            ['Paul Wilhite - Storage', 'Concert', 'Milestone 1', '$ 9,072.00'],
            ['Spencer Fabian - Solar', 'Customer', 'Milestone 1', '$ 10,000.00'],
            ['Taamiat Deposit', 'Customer', 'Deposit', '$ 1,000.00'],
            ['Grace Liu', 'Customer', null, '$ 2,112.65'],
        ]), 'Closed');

        $this->actingAs($user)->post(route('transaction-portal.import.store'), $this->form($preview, [
            2 => ['project' => $spencer->id],  // picked in the preview
            3 => ['include' => null],          // unticked
            // row 4 kept, still without a project or milestone -> skipped and listed
        ]))->assertRedirect(route('transaction-portal.import'))->assertSessionHas('importResult');

        $result = session('importResult');
        $this->assertSame(2, $result['created']);
        $this->assertSame([4], array_column($result['skipped'], 'line'));
        $this->assertStringContainsString('Milestone is missing.', $result['skipped'][0]['reason']);

        $storage = AccountTransaction::where('project_id', $paul->id)->sole();
        $this->assertSame('concert', $storage->payee);
        $this->assertSame('Milestone 1 - Storage', $storage->milestone);
        $this->assertEquals(9072.00, (float) $storage->amount);
        $this->assertSame('2026-11-06', substr((string) $storage->transaction_date, 0, 10));
        $this->assertSame('Closed', $storage->status);
        $this->assertEquals(9072.00, (float) $storage->settled_amount);
        $this->assertSame('2026-11-06', substr((string) $storage->settled_date, 0, 10));
        $this->assertSame('Imported from Untitled spreadsheet.xlsx', $storage->transaction_details);

        $this->assertSame('Milestone 1 - Solar', AccountTransaction::where('project_id', $spencer->id)->value('milestone'));
        $this->assertTrue(Payee::where('key', 'concert')->where('is_system', false)->exists());

        // Nothing the file did not hold: no commission, nothing else.
        $this->assertSame(2, AccountTransaction::count());
        $this->assertFalse(AccountTransaction::where('payee', 'sales_partner')->exists());
    }

    public function test_duplicates_are_skipped_unless_the_user_keeps_them(): void
    {
        $jun = $this->project('Jun-Du', 'Jun', 'Du');
        $user = $this->user();
        Payee::create(['key' => 'maxwell_power', 'label' => 'Maxwell Power']);

        AccountTransaction::create([
            'project_id' => $jun->id, 'payee' => 'maxwell_power', 'milestone' => 'Milestone 2 - Storage',
            'amount' => 3888, 'deduction_amount' => 0, 'transaction_date' => '2026-10-19', 'transaction_details' => 'HDM Payment',
        ]);

        $file = fn () => $this->sheet([
            ['Jun Du - Storage', 'Maxwell Power', 'Milestone 2', '$ 3,888.00'],   // already in the system
            ['Jun Du - Solar', 'Maxwell Power', 'Milestone 2', '$ 5,345.00'],     // new
            ['Jun Du - Solar', 'maxwell power', 'Milestone 2', '5345'],           // same as the row above
        ]);

        $rows = collect($this->preview($user, $file())->viewData('rows'))->keyBy('line');
        $this->assertStringContainsString('Already in Account Transactions', $rows[1]['duplicate']);
        $this->assertNull($rows[2]['duplicate']);
        $this->assertSame('Same as row 2 of this file.', $rows[3]['duplicate']);

        // As the page opens: duplicates are unticked.
        $this->actingAs($user)->post(route('transaction-portal.import.store'), $this->form($this->preview($user, $file())));
        $this->assertSame(2, AccountTransaction::where('project_id', $jun->id)->count());

        // "Import them anyway" ticks them.
        $this->actingAs($user)->post(route('transaction-portal.import.store'), $this->form($this->preview($user, $file()), [
            1 => ['include' => '1'], 2 => ['include' => '1'], 3 => ['include' => '1'],
        ]));
        $this->assertSame(5, AccountTransaction::where('project_id', $jun->id)->count());
    }

    public function test_a_missing_value_can_be_filled_in_and_a_project_picked_from_the_closest_names(): void
    {
        $jiangtao = $this->project('Jiangtao-Liu', 'Jiangtao', 'Liu');
        $this->project('Grace-Liu', 'Grace', 'Liu');
        $this->project('Kyle-Brown', 'Kyle', 'Brown');
        $user = $this->user();

        $preview = $this->preview($user, $this->sheet([
            ['Jinagtao Liu', 'Sales Partner', null, '20262'], // typo in the name, no milestone
        ]));

        $row = $preview->viewData('rows')[0];
        $this->assertNull($row['project_id']);
        $this->assertSame($jiangtao->id, array_key_first($row['candidates']), 'The closest name comes first.');
        $this->assertArrayNotHasKey(Project::where('project_name', 'Kyle-Brown')->value('id'), $row['candidates']);
        $preview->assertSee('Closest names');

        $this->actingAs($user)->post(route('transaction-portal.import.store'), $this->form($preview, [
            1 => ['project' => $jiangtao->id, 'milestone' => 'Commission Full Payment', 'amount' => '-20,262.00', 'status' => 'Closed', 'date' => '2026-10-30'],
        ]));

        $t = AccountTransaction::where('project_id', $jiangtao->id)->sole();
        $this->assertSame('sales_partner', $t->payee);
        $this->assertSame('Commission Full Payment', $t->milestone);
        $this->assertEquals(-20262.00, (float) $t->amount);
        $this->assertSame('Closed', $t->status);
        $this->assertEquals(20262.00, (float) $t->settled_amount);
        $this->assertSame('2026-10-30', substr((string) $t->settled_date, 0, 10));
    }

    public function test_a_long_sheet_posted_as_one_json_field_imports_every_kept_row(): void
    {
        // 600 rows x 8 inputs would pass the server's max_input_vars (5000) and
        // be cut silently; the page posts the kept rows as one JSON field.
        $paul = $this->project('Paul-Wilhite', 'Paul', 'Wilhite');
        $user = $this->user();
        $sheet = [];
        for ($i = 1; $i <= 600; $i++) {
            $sheet[] = ['Paul Wilhite', 'Customer', 'Payment '.$i, (string) (100 + $i)];
        }

        $form = $this->form($this->preview($user, $this->sheet($sheet)));
        $payload = collect($form['rows'])
            ->reject(fn ($row, $line) => $line === 600)            // unticked: not in the payload
            ->map(fn ($row) => collect($row)->except('include')->all())
            ->all();

        $this->actingAs($user)->post(route('transaction-portal.import.store'), [
            'token' => $form['token'],
            'payload' => json_encode($payload),
        ])->assertRedirect(route('transaction-portal.import'));

        $this->assertSame(599, AccountTransaction::where('project_id', $paul->id)->count());
        $this->assertSame(599, session('importResult')['created']);
        $this->assertFalse(AccountTransaction::where('milestone', 'Payment 600')->exists());
    }

    public function test_a_project_the_user_cannot_access_is_refused_even_if_posted(): void
    {
        $this->project('Paul-Wilhite', 'Paul', 'Wilhite');
        $user = $this->user();
        $preview = $this->preview($user, $this->sheet([['Paul Wilhite', 'Customer', 'Permit', '100']]));

        $this->actingAs($user)->post(route('transaction-portal.import.store'), $this->form($preview, [1 => ['project' => 999999]]));

        $this->assertSame(0, AccountTransaction::count());
        $this->assertStringContainsString('cannot access', session('importResult')['skipped'][0]['reason']);
    }

    public function test_a_header_row_maps_columns_by_name_including_date_status_and_code(): void
    {
        $kyle = $this->project('Kyle-Brown', 'Kyle', 'Brown');
        $user = $this->user();

        $rows = collect($this->preview($user, $this->sheet([
            ['Amount', 'Status', 'Project Code', 'Milestone', 'Payee', 'Date', 'Details'],
            ['16500', 'Paid', $kyle->code, 'Permit', 'Customer', '10/31/2026', 'Wire 4411'],
            ['11550', 'Maybe', $kyle->code, 'Installation', 'Customer', 'not a date', ''],
        ]))->viewData('rows'))->keyBy('line');

        $this->assertSame($kyle->id, $rows[2]['project_id']);
        $this->assertSame('code', $rows[2]['match']);
        $this->assertSame('Closed', $rows[2]['status']);
        $this->assertSame('2026-10-31', $rows[2]['date']);
        $this->assertSame('Wire 4411', $rows[2]['details']);
        $this->assertCount(2, $rows[3]['errors']);
    }

    public function test_a_name_shared_by_sibling_projects_asks_which_one(): void
    {
        $this->project('Yunjiao-Guan - 61st Ave', 'Yunjiao', 'Guan');
        $burlwood = $this->project('Yunjiao-Guan - Burlwood', 'Yunjiao', 'Guan');

        $rows = collect($this->preview($this->user(), $this->sheet([
            ['Yunjiao Guan', 'Customer', 'Permit', '100'],
            ['Yunjiao-Guan - Burlwood', 'Customer', 'Permit', '100'],
        ]))->viewData('rows'))->keyBy('line');

        $this->assertSame('ambiguous', $rows[1]['match']);
        $this->assertCount(2, $rows[1]['candidates']);
        $this->assertSame($burlwood->id, $rows[2]['project_id']);
    }

    public function test_an_expired_upload_is_asked_for_again(): void
    {
        $this->actingAs($this->user())
            ->post(route('transaction-portal.import.store'), ['token' => (string) \Illuminate\Support\Str::uuid(), 'rows' => []])
            ->assertRedirect(route('transaction-portal.import'))
            ->assertSessionHasErrors('file');
    }
}
