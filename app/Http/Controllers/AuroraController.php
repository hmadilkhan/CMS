<?php

namespace App\Http\Controllers;

use App\Services\AuroraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuroraController extends Controller
{
    public function index(AuroraService $auroraService)
    {
        return $auroraService->test();
    }

    public function saveProject(Request $request)
    {
        Log::info("Aurora Request",$request->toArray());
    }
}
