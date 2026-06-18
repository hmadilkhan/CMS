<?php

namespace App\Http\Controllers;

use App\Models\InverterType;
use App\Models\ModuleType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ModuleTypeController extends Controller
{
    private const MAX_DECIMAL_VALUE = 99999999.99;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("module-types.index",[
            "types" => ModuleType::with("inverter")->latest()->get(),
            "inverterTypes" => InverterType::orderBy("name")->get(),
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
            ModuleType::create($this->moduleTypeData($validated));
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
            "types" => ModuleType::with("inverter")->latest()->get(),
            "inverterTypes" => InverterType::orderBy("name")->get(),
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
            $moduleType->update($this->moduleTypeData($validated));
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
            ->where(fn ($query) => $query
                ->where("inverter_type_id", $request->input("inverter_type_id"))
                ->whereNull("deleted_at"));

        if (!empty($ignoreId)) {
            $uniqueNameForInverter->ignore($ignoreId);
        }

        return $request->validate([
            "name" => ["bail", "required", "string", "max:255", $uniqueNameForInverter],
            "inverter_type_id" => [
                "bail",
                "required",
                Rule::exists("inverter_types", "id")->whereNull("deleted_at"),
            ],
            "value" => ["bail", "required", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
            "amount" => ["bail", "required", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
            "internal_module_cost" => ["nullable", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
            "ptc_rating" => ["nullable", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
            "voc_rating" => ["nullable", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
            "isc_rating" => ["nullable", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
            "weight" => ["nullable", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
            "square_footage" => ["nullable", "numeric", "min:0", "max:" . self::MAX_DECIMAL_VALUE],
        ], [], [
            "inverter_type_id" => "inverter type",
            "value" => "watt",
            "internal_module_cost" => "internal module cost",
            "ptc_rating" => "module PTC rating",
            "voc_rating" => "module VOC rating",
            "isc_rating" => "module ISC rating",
            "square_footage" => "module square footage",
        ]);
    }

    private function moduleTypeData(array $validated): array
    {
        return [
            "name" => trim($validated["name"]),
            "inverter_type_id" => $validated["inverter_type_id"],
            "value" => $validated["value"],
            "amount" => $validated["amount"],
            "internal_module_cost" => $validated["internal_module_cost"] ?? 0,
            "ptc_rating" => $validated["ptc_rating"] ?? null,
            "voc_rating" => $validated["voc_rating"] ?? null,
            "isc_rating" => $validated["isc_rating"] ?? null,
            "weight" => $validated["weight"] ?? null,
            "square_footage" => $validated["square_footage"] ?? null,
        ];
    }
}
