<?php

namespace App\Http\Controllers;

use App\Models\InverterType;
use App\Models\ModuleType;
use Illuminate\Http\Request;

class ModuleTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("module-types.index",[
            "types" => ModuleType::with("inverter")->get(),
            "inverterTypes" => InverterType::all(),
            "type" => [],
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
            'name' => 'required',
            'inverter_type_id' => 'required',
            'value' => 'required',
        ]);
        try {
            ModuleType::create($request->except(["id"]));
            return redirect()->route("module-types.index");
        } catch (\Throwable $th) {
            return $th->getMessage();
            return redirect()->route("module-types.index");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ModuleType $moduleType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ModuleType $moduleType)
    {
        return view("module-types.index",[
            "types" => ModuleType::all(),
            "inverterTypes" => InverterType::all(),
            "type" => $moduleType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ModuleType $moduleType)
    {
        $validated = $request->validate([
            'name' => 'required',
            'value' => 'required',
        ]);
        try {
            $moduleType->name = $request->name;
            $moduleType->value = $request->value;
            $moduleType->amount = $request->amount;
            $moduleType->inverter_type_id = $request->inverter_type_id;
            $moduleType->save();
            return redirect()->route("module-types.index");
        } catch (\Throwable $th) {
            return $th->getMessage();
            return redirect()->route("module-types.index");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ModuleType $moduleType)
    {
        try {
            $moduleType->delete();
            return response()->json(["status" => 200,"message" => "Module Type Deleted"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500,"message" => "Module Type not deleted","error" => $th->getMessage()]);
        }
    }
}
