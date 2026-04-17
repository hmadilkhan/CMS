<?php

namespace App\Http\Controllers;

use App\Models\InverterType;
use Illuminate\Http\Request;

class InverterTypeController extends Controller
{
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
        return view("operations.invertertype.index",[
            "inverterTypes" => InverterType::all(),
            "inverterType" => ($request->id != "" ? InverterType::find($request->id) : []),
        ]);
    }

    public function inverterTypeStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
        ]);

        try {
            $count = InverterType::where("name", $request->name)->count();
            if ($count == 0) {
                InverterType::create([
                    "name" => $request->name,
                    "tags" => $this->normalizeTags($request->tags),
                ]);
                return redirect()->route("view-inverter-type")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("view-inverter-type")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function inverterTypeUpdate(Request $request)
    {
        try {
            $inverterType = InverterType::find($request->id);
            $inverterType->name = $request->name;
            $inverterType->tags = $this->normalizeTags($request->tags);
            $inverterType->save();
            return redirect()->route("view-inverter-type");
        } catch (\Throwable $th) {
            return redirect()->route("view-inverter-type")->with('error', $th->getMessage());
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
