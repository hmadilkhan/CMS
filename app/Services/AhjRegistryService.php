<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AhjRegistryService
{
    public function assignToProject(Project $project, ?Customer $customer = null, bool $overwrite = false): bool
    {
        $customer ??= $project->customer;

        if (! $customer || (! $overwrite && $project->ahj && $project->ahj_website_url)) {
            return false;
        }

        $ahj = $this->lookup($customer);

        if (! $ahj) {
            return false;
        }

        $updates = [];

        if ($overwrite || empty($project->ahj)) {
            $updates['ahj'] = $ahj['name'];
        }

        if ($overwrite || empty($project->ahj_website_url)) {
            $updates['ahj_website_url'] = $ahj['url'];
        }

        $updates = array_filter($updates, fn ($value) => filled($value));

        if (! $updates) {
            return false;
        }

        $project->forceFill($updates)->save();

        return true;
    }

    public function lookup(Customer $customer): ?array
    {
        $token = config('services.ahj_registry.token');

        if (blank($token) || ! $this->hasAddress($customer)) {
            return null;
        }

        try {
            $response = Http::withToken($token, 'Token')
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.ahj_registry.timeout', 10))
                ->post(config('services.ahj_registry.url'), [
                    'Address' => [
                        'AddrLine1' => ['Value' => $customer->street],
                        'City' => ['Value' => $customer->city],
                        'StateProvince' => ['Value' => $customer->state],
                        'ZipPostalCode' => ['Value' => $customer->zipcode],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('AHJ Registry lookup failed.', [
                    'customer_id' => $customer->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            return $this->firstAhj($response->json());
        } catch (\Throwable $th) {
            report($th);

            return null;
        }
    }

    protected function hasAddress(Customer $customer): bool
    {
        return filled($customer->street)
            && filled($customer->city)
            && filled($customer->state)
            && filled($customer->zipcode);
    }

    protected function firstAhj(?array $payload): ?array
    {
        $record = data_get($payload, 'AuthorityHavingJurisdictions.0');

        if (! $record) {
            return null;
        }

        $name = data_get($record, 'AHJName.Value');
        $url = data_get($record, 'URL.Value');

        if (blank($name) && blank($url)) {
            return null;
        }

        return [
            'name' => $name,
            'url' => $url,
        ];
    }
}
