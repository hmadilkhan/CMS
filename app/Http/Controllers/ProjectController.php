<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Traits\MediaTrait;
use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use MediaTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return Project::with("department","subdepartment","assignedPerson","assignedPerson.employee")->get();
        return view("projects.index", [
            "customers" => Customer::all(),
            "departments" => Department::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("projects.form", [
            "project" => [],
            "customers" => Customer::all(),
            "employees" => Employee::getUser(1, ["Manager", "Employee"])->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name' => 'required',
            'budget' => 'required',
            'customer_id' => ['required'],
            'start_date' => ['required'],
            'end_date' => ['required'],
        ]);
        try {
            DB::beginTransaction();
            $subdepartment = SubDepartment::where("department_id", 1)->first();
            $project = Project::create(array_merge(
                $request->except(["assigntask"]),
                [
                    "department_id" => 1,
                    "sub_department_id" => $subdepartment->id,
                ]
            ));
            Task::create([
                "project_id" => $project->id,
                "employee_id" => $request->assigntask,
                "department_id" => 1,
                "sub_department_id" => $subdepartment->id,
            ]);
            DB::commit();
            return response()->json(["status" => 200, "messsage" => "Project created successfully"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "messsage" => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        // return $project->task;
        // return Project::with("customer","department","subdepartment","assignedPerson","assignedPerson.employee")->first();
        $task = Task::where("status", "In-Progress")->where("project_id", $project->id)->first();
        return view("projects.show", [
            "project" => Project::with("customer", "department", "subdepartment", "assignedPerson", "assignedPerson.employee")->where("id", $project->id)->first(),
            "task" => $task,
            "backdepartments" => Department::where("id", "<", $task->department_id)->get(),
            "forwarddepartments" => Department::where("id", ">", $task->department_id)->take(1)->get(),
            "filesCount" => ProjectFile::where("project_id",$project->id)->where("department_id",$project->department_id)->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        return view("projects.form", [
            "project" => $project,
            "customers" => Customer::all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        try {
            $project->update($request->toArray());
            return response()->json(["status" => 200, "messsage" => "Project updated successfully"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "messsage" => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        //
    }

    public function getProjectList(Request $request)
    {
        $query = Project::with("department", "subdepartment", "assignedPerson", "assignedPerson.employee");
        return view("projects.project-list", [
            "projects" => $query->get(),
        ]);
    }

    public function getSubDepartments(Request $request)
    {
        try {
            $subdepartments = SubDepartment::where("department_id", $request->id)->get();
            return response()->json(["status" => 200, "subdepartments" => $subdepartments]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function projectMove(Request $request)
    {
        $filesArray = [];
        $validated = $request->validate([
            'stage' => 'required',
            'forward' => 'required_if:stage,forward|integer',
            'back' => 'required_if:stage,back|integer',
            'sub_department' => 'required',
            // 'file' => 'required_if:stage,forward|integer',
            'file' => Rule::requiredIf(function () use ($request) {
                return $request->stage == "forward" && $request->alreadyuploaded == 0;
            }),
            'notes' => ['required'],
        ]);
        if ($request->stage == "forward" && $request->alreadyuploaded == 0) {
            foreach ($request->file as $key => $file) {
                $result = $this->uploads($file, 'projects/');
                array_push($filesArray, $result);
            }
        }
        try {
            DB::beginTransaction();
            if ($request->stage == "forward" && $request->alreadyuploaded == 0) {
                $project = Project::findOrFail($request->id);
                $task = Task::findOrFail($request->taskid);
                foreach ($filesArray as $key => $file) {
                    ProjectFile::create([
                        "project_id" => $project->id,
                        "task_id" => $task->id,
                        "department_id" => $project->department_id,
                        "filename" => $file["fileName"],
                    ]);
                }
            }
            Project::where("id", $request->id)->update([
                "department_id" => ($request->stage == "forward" ? $request->forward : $request->back),
                "sub_department_id" => $request->sub_department,
            ]);
            $emp =  Employee::with("department")->whereHas("department", function ($query) use ($request) {
                $query->whereIn("department_id", [($request->stage == "forward" ? $request->forward : $request->back)]);
            })->first();
            Task::where("id", $request->taskid)->update(["status" => "Completed", "notes" => $request->notes]);
            Task::create([
                "project_id" => $request->id,
                "employee_id" => $emp->id,
                "department_id" => ($request->stage == "forward" ? $request->forward : $request->back),
                "sub_department_id" => $request->sub_department,
            ]);

            DB::commit();
            return redirect()->route("projects.index");
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }
}
