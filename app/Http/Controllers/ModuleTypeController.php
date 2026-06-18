<?php

namespace App\Http\Controllers;

use App\Models\InverterType;
use App\Models\ModuleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $validated = $this->validateModuleType($request);

        try {
            $validated["internal_module_cost"] = $validated["internal_module_cost"] ?? 0;
            ModuleType::create($validated);
            return redirect()->route("module-types.index")->with("success", "Module type saved successfully");
        } catch (\Throwable $th) {
            return redirect()->route("module-types.index")->with("error", $th->getMessage());
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
            "types" => ModuleType::with("inverter")->get(),
            "inverterTypes" => InverterType::all(),
            "type" => $moduleType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ModuleType $moduleType)
    {
        $validated = $this->validateModuleType($request, $moduleType->id);

        try {
            $moduleType->name = $validated["name"];
            $moduleType->value = $validated["value"];
            $moduleType->amount = $validated["amount"];
            $moduleType->inverter_type_id = $validated["inverter_type_id"];
            $moduleType->internal_module_cost = $validated["internal_module_cost"] ?? 0;
            $moduleType->ptc_rating = $validated["ptc_rating"] ?? null;
            $moduleType->voc_rating = $validated["voc_rating"] ?? null;
            $moduleType->isc_rating = $validated["isc_rating"] ?? null;
            $moduleType->weight = $validated["weight"] ?? null;
            $moduleType->square_footage = $validated["square_footage"] ?? null;
            $moduleType->save();
            return redirect()->route("module-types.index")->with("success", "Module type updated successfully");
        } catch (\Throwable $th) {
            return redirect()->route("module-types.index")->with("error", $th->getMessage());
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

    private function validateModuleType(Request $request, $ignoreId = null): array
    {
        $uniqueNameForInverter = Rule::unique("module_types", "name")
            ->where("inverter_type_id", $request->inverter_type_id)
            ->whereNull("deleted_at");

        if (!empty($ignoreId)) {
            $uniqueNameForInverter->ignore($ignoreId);
        }

        return $request->validate([
            "name" => ["required", "string", "max:255", $uniqueNameForInverter],
            "inverter_type_id" => ["required", "exists:inverter_types,id"],
            "value" => ["required", "numeric", "min:0"],
            "amount" => ["required", "numeric", "min:0"],
            "internal_module_cost" => ["nullable", "numeric", "min:0"],
            "ptc_rating" => ["nullable", "numeric", "min:0"],
            "voc_rating" => ["nullable", "numeric", "min:0"],
            "isc_rating" => ["nullable", "numeric", "min:0"],
            "weight" => ["nullable", "numeric", "min:0"],
            "square_footage" => ["nullable", "numeric", "min:0"],
        ]);
    }
}
