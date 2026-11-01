<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SalesPartner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormatCustomerPhonesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $phone, bool $deleted = false): Customer
    {
        $customer = Customer::create([
            'first_name' => 'Phone',
            'last_name' => 'Test',
            'street' => '1 Main St',
            'city' => 'Mesa',
            'state' => 'AZ',
            'zipcode' => '85201',
            'phone' => $phone,
            'email' => uniqid().'@example.com',
            'sales_partner_id' => SalesPartner::firstOrCreate(['name' => 'Phone Partner'])->id,
            'sold_date' => now()->toDateString(),
            'panel_qty' => 1,
            'inverter_type_id' => 1,
            'module_type_id' => 1,
            'inverter_qty' => 1,
            'module_value' => 1,
        ]);

        if ($deleted) {
            $customer->delete();
        }

        return $customer;
    }

    private function phoneOf(Customer $customer): string
    {
        return (string) Customer::withTrashed()->find($customer->id)->phone;
    }

    public function test_it_formats_old_phones_and_leaves_unreadable_ones_alone(): void
    {
        $dashes = $this->customer('909-630-2052');
        $country = $this->customer('+1 916 756 6600');
        $deleted = $this->customer('6268727757', deleted: true);
        $already = $this->customer('(562) 546-2145');
        $short = $this->customer('62-320-4940');

        $this->artisan('customers:format-phones')->assertSuccessful();

        $this->assertSame('(909) 630-2052', $this->phoneOf($dashes));
        $this->assertSame('(916) 756-6600', $this->phoneOf($country));
        $this->assertSame('(626) 872-7757', $this->phoneOf($deleted));
        $this->assertSame('(562) 546-2145', $this->phoneOf($already));
        $this->assertSame('62-320-4940', $this->phoneOf($short));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $customer = $this->customer('909-630-2052');

        $this->artisan('customers:format-phones', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('909-630-2052', $this->phoneOf($customer));
    }
}
