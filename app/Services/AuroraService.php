<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AuroraService
{
    public function getProjectList()
    {
        return 
        Http::withHeaders([
            'Authorization' => 'Bearer ' . getenv('TOKEN'),
        ])
        ->get("https://api.aurorasolar.com/tenants/04310f24-7edd-4f58-bc7a-60a90532afda/projects");
    }

    public function getProjectAgreements()
    {
        return 
        Http::withHeaders([
            'Authorization' => 'Bearer ' . getenv('TOKEN'),
        ])
        ->get("https://api.aurorasolar.com/tenants/04310f24-7edd-4f58-bc7a-60a90532afda/projects/f2aac222-a7cb-434f-8936-97f894fca42f/agreements");
    }
}
