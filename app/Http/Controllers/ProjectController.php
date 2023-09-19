<?php

namespace App\Http\Controllers;

use App\Models\AdderType;
use App\Models\AdderUnit;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDepartment;
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
        // return EmployeeDepartment::whereIn("employee_id",Employee::where("user_id",auth()->user()->id)->pluck("id"))->pluck("department_id");
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
        $task = Task::whereIn("status", ["In-Progress","Hold"])->where("project_id", $project->id)->first();
        return view("projects.show", [
            "project" => Project::with("task", "customer", "department", "subdepartment", "assignedPerson", "assignedPerson.employee")->where("id", $project->id)->first(),
            "task" => $task,
            "backdepartments" => Department::where("id", "<", $task->department_id)->get(),
            "forwarddepartments" => Department::whereIn("id", Task::where("project_id", $project->id)->pluck("department_id"))->get(),
            "filesCount" => ProjectFile::where("project_id", $project->id)->where("department_id", $project->department_id)->get(),
            "departments" => Department::all(),
            "employees" => $this->getEmployees($project->department_id),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
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
        $result =  $this->projectQuery($request);
        return view("projects.project-list", [
            "projects" => $result["projects"],
            "subdepartments" => $result["subdepartments"],
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
            // 'notes' => ['required'],
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

    public function assignTaskToEmployee(Request $request)
    { 
        DB::beginTransaction();
        try {
            Task::where("id", $request->task_id)->update(["status" => "Completed", "notes" => "Task Assigned to Employee"]);
            Task::create([
                "project_id" => $request->project_id,
                "employee_id" => $request->employee,
                "department_id" => $request->department_id,
                "sub_department_id" => $request->sub_department,
                "assign_to_notes" => $request->notes,
                "status" => "In-Progress"
            ]);
            DB::commit();
            return redirect()->route("projects.show", $request->project_id);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
            return redirect()->route("projects.show", $request->project_id);
        }
    }

    public function projectStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required',
            'reason' => 'required_if:status,Cancelled|integer',
        ]);
        try {
            Task::where("project_id", $request->project_id)->where("status", "In-Progress")->update(["status" => $request->status, "notes" => $request->reason]);
            return redirect()->route("projects.show", $request->project_id);
        } catch (\Throwable $th) {
            return redirect()->route("projects.show", $request->project_id);
        }
    }

    public function projectQuery(Request $request)
    {
        $query = Project::with("customer", "customer.salespartner", "department", "subdepartment", "assignedPerson", "assignedPerson.employee","task");
        $subdepartmentsQuery = SubDepartment::with("department");
        if (auth()->user()->getRoleNames()[0] == "Sales Person") {
            $query->whereHas("customer", function ($query) {
                $query->where("sales_partner_id", auth()->user()->id);
            });
        } else if (auth()->user()->getRoleNames()[0] == "Manager") {
            $query->whereIn("department_id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->pluck("department_id"));
        } else if (auth()->user()->getRoleNames()[0] == "Employee") {
            $query->whereIn("id", Task::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->whereIn("status",["In-Progress","Hold","Cancelled"])->pluck("project_id"));
        }
        if ($request->id != "" && $request->id != "all") {
            $query->where("department_id", $request->id);
            $subdepartmentsQuery->where("department_id", $request->id);
        }
        return [
            "projects" => $query->get(),
            "subdepartments" => $subdepartmentsQuery->get(),
        ];
    }

    public function getEmployees($departmentId)
    {
        $employees = Employee::with("user")
            ->whereHas("user.roles", function ($query) {
                $query->whereIn("name", ["Employee"]);
            })
            ->whereHas("department", function ($query) use ($departmentId) {
                $query->whereIn("department_id", [$departmentId]);
            })
            ->get();
        return $employees;
    }

    public function getProjects(Request $request)
    {
        $result = $this->projectQuery($request);
        return view("projects.list", [
            "projects" => $result["projects"],
        ]);
    }

    public function projectAdders(Request $request)
    {
        $customer = Customer::findOrFail($request->customer_id);
        DB::beginTransaction();
        try {
            if (!empty($request->uom)) {
                $customer->adders()->delete();
                $count = count($request->uom);
                if ($count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        $customer->adders()->create([
                            "customer_id" => $customer->id,
                            "adder_type_id" => $request->adders[$i],
                            "adder_sub_type_id" => $request->subadders[$i],
                            "adder_unit_id" => $request->uom[$i],
                            "amount" => $request->amount[$i],
                        ]);
                    }
                }
            }
            $customer->finances()->update([
                "customer_id" => $customer->id,
                "finance_option_id" => $request->finance_option_id,
                "loan_term_id" => $request->loan_term_id,
                "loan_apr_id" => $request->loan_apr_id,
                "contract_amount" => $request->contract_amount,
                "redline_costs" => $request->redline_costs,
                "adders" => $request->adders_amount,
                "commission" => $request->commission,
                "dealer_fee" => $request->dealer_fee,
                "dealer_fee_amount" => $request->dealer_fee_amount,
            ]);
            DB::commit();
            return redirect()->route("projects.show", $request->project_id);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
            return redirect()->route("projects.show", $request->project_id);
        }
    }
}
