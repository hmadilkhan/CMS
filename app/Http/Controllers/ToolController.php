<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Tool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Traits\MediaTrait;

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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required'],
            'file' => ['required'],
        ]);
        $result = $this->uploads($request->file, 'tools/',"");
        $user = Tool::create([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'description' => $request->description,
            'file' => (!empty($result) ? $result["fileName"] : ""),
        ]);
        return redirect()->route("tools.index");
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
        $result = $this->uploads($request->file, 'tools/', $request->previous_logo);
        Tool::where("id", $request->id)->update([
            'name' => $request->name,
            'department_id' => $request->department_id,
            'description' => $request->description,
            "file" => (!empty($result) ? $result["fileName"] : $request->previous_logo),
        ]);
        return redirect()->route("tools.index");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tool $tool)
    {
        //
    }
}
