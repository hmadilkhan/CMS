<?php

namespace Tests\Feature;

use App\Models\SalesPartner;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrmModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        UserType::firstOrCreate(['name' => 'Admin']);

        $user = User::factory()->create(['user_type_id' => 1]);
        Role::firstOrCreate(['name' => 'Super Admin']);
        $user->assignRole('Super Admin');

        return $user;
    }

    private function salesPerson(): User
    {
        UserType::firstOrCreate(['id' => 3], ['name' => 'Sales Person']);
        $partner = SalesPartner::create(['name' => 'Test Partner']);

        $user = User::factory()->create([
            'user_type_id' => 3,
            'sales_partner_id' => $partner->id,
        ]);

        Role::firstOrCreate(['name' => 'Sales Person']);
        $user->assignRole('Sales Person');

        return $user;
    }

    public function test_super_admin_can_open_people_and_project_module_pages(): void
    {
        $user = $this->superAdmin();

        $urls = [
            '/employees',
            '/customers',
            '/customers/create',
            '/projects',
            '/projects/create',
            '/projects-list',
        ];

        foreach ($urls as $url) {
            $bufferLevel = ob_get_level();
            $response = $this->actingAs($user)->get($url);

            $this->assertTrue(
                $response->isOk(),
                "{$url} expected 200 but returned {$response->getStatusCode()}."
            );

            if (ob_get_level() > $bufferLevel) {
                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }

                $this->fail("{$url} left an output buffer open.");
            }
        }
    }

    public function test_sales_person_can_open_intake_form_pages(): void
    {
        $user = $this->salesPerson();

        foreach (['/intake-form', '/intake-form/create'] as $url) {
            $response = $this->actingAs($user)->get($url);

            $this->assertTrue(
                $response->isOk(),
                "{$url} expected 200 but returned {$response->getStatusCode()}."
            );
        }
    }

    public function test_super_admin_can_open_operations_module_pages(): void
    {
        $user = $this->superAdmin();

        $urls = [
            '/module-types',
            '/office-costs',
            '/labor-costs',
            '/tools',
            '/view-redline-cost',
            '/view-dealer-fee',
            '/view-adder',
            '/view-adder-type',
            '/view-inverter-type',
            '/sales-partner-type',
            '/sub-contractor-type',
            '/view-finance-option',
            '/view-loan-term',
            '/view-call-scripts',
            '/view-email-scripts',
            '/view-utility-company',
            '/assign-department',
        ];

        foreach ($urls as $url) {
            $bufferLevel = ob_get_level();
            $response = $this->actingAs($user)->get($url);

            $this->assertTrue(
                $response->isOk(),
                "{$url} expected 200 but returned {$response->getStatusCode()}."
            );

            if (ob_get_level() > $bufferLevel) {
                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }

                $this->fail("{$url} left an output buffer open.");
            }
        }
    }

    public function test_super_admin_can_open_reporting_and_ticket_module_pages(): void
    {
        $user = $this->superAdmin();

        $urls = [
            '/reports-profilt',
            '/forecast-report',
            '/override-report',
            '/transaction-report',
            '/report-builder',
            '/report-runner',
            '/tickets',
            '/service-dashboard',
            '/service-admin-dashboard',
            '/notifications',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);

            $this->assertTrue(
                $response->isOk(),
                "{$url} expected 200 but returned {$response->getStatusCode()}."
            );
        }
    }

    public function test_removed_debug_routes_are_not_registered(): void
    {
        $this->get('/storage-link')->assertNotFound();
        $this->actingAs($this->superAdmin())->get('/test-google-calendar')->assertNotFound();
    }
}
