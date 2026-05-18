<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Tool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Traits\MediaTrait;
use Illuminate\Validation\Rule;

class ToolController extends Controller
{
    use MediaTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) : View
    {
        return view("tools.index",[
            "tools" => Tool::with("department")->get(),
            "departments" => Department::all(),
            "tool" => ($request->id != "" ? Tool::find($request->id) : []),
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
            'name' => ['required', 'string', 'max:255', Rule::unique('tools', 'name')->where('department_id', $request->department_id)->whereNull('deleted_at')],
            'department_id' => ['required', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:409600'],
        ], [
            'name.unique' => 'The record already exists.',
        ]);

        try {
            $result = $this->uploads($request->file, 'tools/',"");
            Tool::create([
                'name' => $validated['name'],
                'department_id' => $validated['department_id'],
                'description' => $validated['description'] ?? null,
                'file' => (!empty($result) ? $result["fileName"] : ""),
            ]);
            return redirect()->route("tools.manage")->with("success", "Tool saved successfully");
        } catch (\Throwable $th) {
            return redirect()->route("tools.manage")->with("error", $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tool $tool)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tool $tool)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tool $tool)
    {
        $validated = $request->validate([
            'id' => ['required', 'exists:tools,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('tools', 'name')->ignore($request->id)->where('department_id', $request->department_id)->whereNull('deleted_at')],
            'department_id' => ['required', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:5120'],
            'previous_logo' => ['nullable', 'string', 'max:255'],
        ], [
            'name.unique' => 'The record already exists.',
        ]);

        try {
            $result = $this->uploads($request->file, 'tools/', $request->previous_logo);
            Tool::where("id", $validated["id"])->update([
                'name' => $validated['name'],
                'department_id' => $validated['department_id'],
                'description' => $validated['description'] ?? null,
                "file" => (!empty($result) ? $result["fileName"] : ($validated["previous_logo"] ?? "")),
            ]);
            return redirect()->route("tools.manage")->with("success", "Tool updated successfully");
        } catch (\Throwable $th) {
            return redirect()->route("tools.manage")->with("error", $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function toolDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:tools,id"],
        ]);

        try {
            Tool::where("id",$request->id)->delete();
            return response()->json(["message" => "Tool Deleted Successfully", "status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Error:".$th->getMessage(), "status" => 500]);
        }
    }
}
