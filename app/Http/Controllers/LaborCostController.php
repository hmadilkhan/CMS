<?php

namespace App\Http\Controllers;

use App\Models\LaborCost;
use Illuminate\Http\Request;

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
            'cost' => 'required',
        ]);
        try {
            LaborCost::orderBy("id", "desc")->delete();
            LaborCost::create($request->except(["id"]));
            return redirect()->route("labor-costs.index");
        } catch (\Throwable $th) {
            return $th->getMessage();
            return redirect()->route("labor-costs.index");
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
