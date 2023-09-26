<?php

namespace App\Http\Controllers;

use App\Models\OfficeCost;
use Illuminate\Http\Request;

class OfficeCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("office-costs.index",[
            "costs" => OfficeCost::all(),
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
            OfficeCost::orderBy("id","desc")->delete();
            OfficeCost::create($request->except(["id"]));
            return redirect()->route("office-costs.index");
        } catch (\Throwable $th) {
            return $th->getMessage();
            return redirect()->route("office-costs.index");
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
