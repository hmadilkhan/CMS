<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReturnsToOperationsConsole;
use App\Models\OfficeCost;
use App\Services\Operations\OfficeCostPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfficeCostController extends Controller
{
    use ReturnsToOperationsConsole;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view("office-costs.index", app(OfficeCostPanel::class)->data($request));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                OfficeCost::query()->delete();
                OfficeCost::create([
                    "cost" => $validated["cost"],
                ]);
            });

            return $this->backToOperations($request, "office-costs.index")->with("success", "Office cost saved successfully");
        } catch (\Throwable $th) {
            return $this->backToOperations($request, "office-costs.index")->with("error", $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OfficeCost $officeCost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OfficeCost $officeCost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OfficeCost $officeCost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OfficeCost $officeCost)
    {
        //
    }
}
