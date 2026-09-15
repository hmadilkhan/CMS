<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReturnsToOperationsConsole;
use App\Models\InverterType;
use App\Services\Operations\InverterTypesPanel;
use Illuminate\Http\Request;

class InverterTypeController extends Controller
{
    use ReturnsToOperationsConsole;

    protected function normalizeTags(?string $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        $decoded = json_decode($tags, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', $decoded))));
    }

    public function inverterTypeIndex(Request $request)
    {
        return view("operations.invertertype.index", app(InverterTypesPanel::class)->data($request));
    }

    public function inverterTypeStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'inverter_efficiency_rating' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $count = InverterType::where("name", $request->name)->count();
            if ($count == 0) {
                InverterType::create([
                    "name" => $validated["name"],
                    "tags" => $this->normalizeTags($request->tags),
                    "inverter_efficiency_rating" => $validated["inverter_efficiency_rating"] ?? null,
                ]);
                return $this->backToOperations($request, "view-inverter-type")->with("success", "Data Saved Successfully");
            } else {
                return $this->backToOperations($request, "view-inverter-type")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function inverterTypeUpdate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'inverter_efficiency_rating' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $inverterType = InverterType::find($request->id);
            $inverterType->name = $validated["name"];
            $inverterType->tags = $this->normalizeTags($request->tags);
            $inverterType->inverter_efficiency_rating = $validated["inverter_efficiency_rating"] ?? null;
            $inverterType->save();
            return $this->backToOperations($request, "view-inverter-type");
        } catch (\Throwable $th) {
            return $this->backToOperations($request, "view-inverter-type")->with('error', $th->getMessage());
        }
    }

    public function inverterTypeDelete(Request $request)
    {
        try {
            InverterType::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }
}
