<?php

namespace App\Http\Controllers;

use App\Models\LaborCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaborCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("labor-costs.index", [
            "costs" => LaborCost::all(),
        ]);
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
                LaborCost::query()->delete();
                LaborCost::create([
                    "cost" => $validated["cost"],
                ]);
            });

            return redirect()->route("labor-costs.index")->with("success", "Labor cost saved successfully");
        } catch (\Throwable $th) {
            return redirect()->route("labor-costs.index")->with("error", $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LaborCost $laborCost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LaborCost $laborCost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LaborCost $laborCost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LaborCost $laborCost)
    {
        //
    }
}
