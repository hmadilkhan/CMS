<?php

namespace App\Http\Controllers;

use App\Models\AdderType;
use App\Models\AdderUnit;
use App\Models\Customer;
use App\Models\CustomerAdder;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\Project;
use App\Models\ProjectCallLog;
use App\Models\ProjectFile;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\Tool;
use App\Traits\MediaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use MediaTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view("projects.index", [
            "customers" => Customer::all(),
            "departments" => $this->departmentQuery(),
        ]);
    }

    public function departmentQuery()
    {
        $query = Department::query();
        if (auth()->user()->getRoleNames()[0] == "Manager" || auth()->user()->getRoleNames()[0] == "Employee") {
            $query->whereIn("id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->pluck("department_id"));
        }
        $query->where("id", "!=", 9);
        return $query->get();
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
            $code = Project::orderBy("id","DESC")->first("code");
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
        $task = Task::whereIn("status", ["In-Progress", "Hold", "Cancelled"])->where("project_id", $project->id)->first();
        $departments = Department::whereIn("id", Task::where("project_id", $project->id)->whereNotIn("department_id", Department::where("id", ">", $task->department_id)->take(1)->pluck("id"))->groupBy("department_id")->orderBy("department_id")->pluck("department_id"))->get();
        $fwdDepartments =  array_merge($departments->toArray(), Department::where("id", ">", $task->department_id)->take(1)->get()->toArray());
        // return Project::with("task", "customer", "department","logs", "subdepartment", "assignedPerson", "assignedPerson.employee","departmentnotes")->where("id", $project->id)->first();
        return view("projects.show", [
            "project" => Project::with("task", "customer", "department", "logs", "subdepartment", "assignedPerson", "assignedPerson.employee","departmentnotes")->where("id", $project->id)->first(),
            "task" => $task,
            "backdepartments" => Department::where("id", "<", $task->department_id)->get(),
            "forwarddepartments" => (object)$fwdDepartments, //Department::whereIn("id", Task::where("project_id", $project->id)->pluck("department_id"))->get(),
            "filesCount" => ProjectFile::where("project_id", $project->id)->where("department_id", $project->department_id)->get(),
            "departments" => Department::all(),
            "employees" => $this->getEmployees($project->department_id),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
            "tools" => Tool::where("department_id", $project->department_id)->get()
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
        $project = Project::findOrFail($request->id);
        $filesArray = [];
        $validationArray = [
            'stage' => 'required',
            'forward' => 'required_if:stage,forward|integer',
            'back' => 'required_if:stage,back|integer',
            'sub_department' => 'required',
        ];

        if ($request->stage == "forward" && $request->forward != $project->department_id) {

            $validationArray = array_merge($validationArray, [
                'utility_company' => 'required_if:forward,2',
                'ntp_approval_date' => 'required_if:forward,2',
                'site_survey_link' => 'required_if:forward,3',
                'hoa' => 'required_if:forward,3',
                'hoa_phone_number' => Rule::requiredIf(function () use ($request) {
                    return $request->forward == 3 && !$request->hoa == "yes";
                }), //'required_if:hoa,yes',
                'adders_approve_checkbox' => 'required_if:forward,4',
                'mpu_required' => 'required_if:forward,4',
                'meter_spot_request_date' => 'required_if:mpu_required,yes',
                'meter_spot_request_number' => 'required_if:mpu_required,yes',
                'meter_spot_result' => 'required_if:forward,4',
                'permitting_submittion_date' => 'required_if:forward,5',
                'permitting_approval_date' => 'required_if:forward,5',
                'hoa_approval_request_date' => 'required_if:projecthoa,yes',
                'hoa_approval_date' => 'required_if:projecthoa,yes',
                'solar_install_date' => 'required_if:forward,6',
                'battery_install_date' => 'required_if:forward,6',
                'mpu_install_date' =>   Rule::requiredIf(function () use ($request) {
                    return $request->forward == 6 && !$request->projectmpu == "yes";
                }),
                'rough_inspection_date' => 'required_if:forward,7',
                'final_inspection_date' => 'required_if:forward,7',
                'pto_submission_date' => 'required_if:forward,8',
                'pto_approval_date' => 'required_if:forward,8',
                'coc_packet_mailed_out_date' => 'required_if:forward,9',
            ]);
        }

        $validated = $request->validate($validationArray);

        // $validated = $request->validate([
        //     'stage' => 'required',
        //     'forward' => 'required_if:stage,forward|integer',
        //     'back' => 'required_if:stage,back|integer',
        //     'sub_department' => 'required',
        //     'utility_company' => 'required_if:forward,2',
        //     'ntp_approval_date' => 'required_if:forward,2',
        //     'site_survey_link' => 'required_if:forward,3',
        //     'hoa' => 'required_if:forward,3',
        //     'hoa_phone_number' => Rule::requiredIf(function () use ($request) {
        //         return $request->forward == 3 && !$request->hoa == "yes";
        //     }), //'required_if:hoa,yes',
        //     'adders_approve_checkbox' => 'required_if:forward,4',
        //     'mpu_required' => 'required_if:forward,4',
        //     'meter_spot_request_date' => 'required_if:mpu_required,yes',
        //     'meter_spot_request_number' => 'required_if:mpu_required,yes',
        //     'meter_spot_result' => 'required_if:forward,4',
        //     'permitting_submittion_date' => 'required_if:forward,5',
        //     'permitting_approval_date' => 'required_if:forward,5',
        //     'hoa_approval_request_date' => 'required_if:projecthoa,yes',
        //     'hoa_approval_date' => 'required_if:projecthoa,yes',
        //     'solar_install_date' => 'required_if:forward,6',
        //     'battery_install_date' => 'required_if:forward,6',
        //     'mpu_install_date' =>   Rule::requiredIf(function () use ($request) {
        //         return $request->forward == 6 && !$request->projectmpu == "yes";
        //     }),
        //     'rough_inspection_date' => 'required_if:forward,7',
        //     'final_inspection_date' => 'required_if:forward,7',
        //     'pto_submission_date' => 'required_if:forward,8',
        //     'pto_approval_date' => 'required_if:forward,8',
        //     'coc_packet_mailed_out_date' => 'required_if:forward,9',
        // ]);
        try {
            // $project = Project::findOrFail($request->id);

            // if ($request->stage == "forward" && $request->alreadyuploaded == 0 && ($project->department_id != $request->forward)) {
            //     if (!empty($request->file)) {
            //         foreach ($request->file as $key => $file) {
            //             $result = $this->uploads($file, 'projects/');
            //             array_push($filesArray, $result);
            //         }
            //     }
            // }
            DB::beginTransaction();
            if ($request->stage == "forward" && $request->forward == $project->department_id) {
                $project->department_id = $request->forward;
                $project->sub_department_id = $request->sub_department;
                $project->save();
                $task = Task::findOrFail($request->taskid);
                Task::where("id", $request->taskid)->update(["status" => "Completed", "notes" => $request->notes]);
                Task::create([
                    "project_id" => $request->id,
                    "employee_id" => $task->employee_id,
                    "department_id" => $request->forward,
                    "sub_department_id" => $request->sub_department,
                    "assign_to_notes" => $request->notes,
                    "status" => "In-Progress"
                ]);
                DB::commit();
                return redirect()->route("projects.index");
            }
            // if ($request->stage == "forward" && $request->alreadyuploaded == 0) {

                // $task = Task::findOrFail($request->taskid);
                // if (!empty($request->file)) {
                //     foreach ($filesArray as $key => $file) {
                //         ProjectFile::create([
                //             "project_id" => $project->id,
                //             "task_id" => $task->id,
                //             "department_id" => $project->department_id,
                //             "filename" => $file["fileName"],
                //         ]);
                //     }
                // }
                /* THIS CODE IS COMMENTED HERE BECAUSE WE MAKE IT INDEPENDENT IN saveCallLogs FUNCTION */

                // $logsCount = ProjectCallLog::where("project_id", $project->id)->where("department_id", $request->forward)->count();
                // if ($request->forward != 1 && $request->forward != 8 && $logsCount == 0) {
                //     ProjectCallLog::create([
                //         "project_id" => $project->id,
                //         "department_id" => $project->department_id,
                //         "call_no" => $request->call_no_1,
                //         "notes" => $request->notes_1,
                //     ]);
                //     ProjectCallLog::create([
                //         "project_id" => $project->id,
                //         "department_id" => $project->department_id,
                //         "call_no" => $request->call_no_2,
                //         "notes" => $request->notes_2,
                //     ]);
                // }
            // }
            $updateItems = [
                "department_id" => ($request->stage == "forward" ? $request->forward : $request->back),
                "sub_department_id" => $request->sub_department,
            ];
            if ($request->forward == 2) {
                $updateItems = array_merge($updateItems, [
                    "utility_company" => $request->utility_company,
                    "ntp_approval_date" => $request->ntp_approval_date,
                ]);
            }
            if ($request->forward == 3) {
                $updateItems = array_merge($updateItems, [
                    "site_survey_link" => $request->site_survey_link,
                    "hoa" => $request->hoa,
                    "hoa_phone_number" => $request->hoa_phone_number,
                ]);
            }

            if ($request->forward == 4) {
                $updateItems = array_merge($updateItems, [
                    "adders_approve_checkbox" => $request->adders_approve_checkbox,
                    "mpu_required" => $request->mpu_required,
                    "meter_spot_request_date" => $request->meter_spot_request_date,
                    "meter_spot_request_number" => $request->meter_spot_request_number,
                    "meter_spot_result" => $request->meter_spot_result,
                ]);
            }

            if ($request->forward == 5) {
                $updateItems = array_merge($updateItems, [
                    "permitting_submittion_date" => $request->permitting_submittion_date,
                    "actual_permit_fee" => $request->actual_permit_fee,
                    "permitting_approval_date" => $request->permitting_approval_date,
                    "hoa_approval_request_date" => $request->hoa_approval_request_date,
                    "hoa_approval_date" => $request->hoa_approval_date,
                ]);
            }

            if ($request->forward == 6) {
                $updateItems = array_merge($updateItems, [
                    "solar_install_date" => $request->solar_install_date,
                    "actual_labor_cost" => $request->actual_labor_cost,
                    "actual_material_cost" => $request->actual_material_cost,
                    "battery_install_date" => $request->battery_install_date,
                    "mpu_install_date" => $request->mpu_install_date,
                ]);
            }

            if ($request->forward == 7) {
                $updateItems = array_merge($updateItems, [
                    "rough_inspection_date" => $request->rough_inspection_date,
                    "final_inspection_date" => $request->final_inspection_date,
                ]);
            }

            if ($request->forward == 8) {
                $updateItems = array_merge($updateItems, [
                    "pto_submission_date" => $request->pto_submission_date,
                    "pto_approval_date" => $request->pto_approval_date,
                ]);
            }

            if ($request->forward == 9) {
                $updateItems = array_merge($updateItems, [
                    "coc_packet_mailed_out_date" => $request->coc_packet_mailed_out_date,
                ]);
            }

            Project::where("id", $request->id)->update($updateItems);
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

    public function saveCallLogs(Request $request)
    {
        try {
            DB::beginTransaction();
            $project = Project::findOrFail($request->id);
            $logsCount = ProjectCallLog::where("project_id", $project->id)->where("department_id", $project->department_id)->count();
            // if ($request->forward != 1 && $request->forward != 8 && $logsCount == 0) {
            ProjectCallLog::create([
                "project_id" => $project->id,
                "department_id" => $project->department_id,
                "call_no" => $request->call_no_1,
                "notes" => $request->notes_1,
            ]);
            // }
            DB::commit();
            return redirect()->route("projects.show", $project->id);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }

    public function saveProjectFiles(Request $request)
    {
        $filesArray = [];
        try {
            DB::beginTransaction();
            $project = Project::findOrFail($request->id);
            if (!empty($request->file)) {
                foreach ($request->file as $key => $file) {
                    $result = $this->uploads($file, 'projects/');
                    array_push($filesArray, $result);
                }
            }
            $task = Task::findOrFail($request->taskid);
            if (!empty($request->file)) {
                foreach ($filesArray as $key => $file) {
                    ProjectFile::create([
                        "project_id" => $project->id,
                        "task_id" => $task->id,
                        "department_id" => $project->department_id,
                        "filename" => $file["fileName"],
                    ]);
                }
            }
            DB::commit();
            return redirect()->route("projects.show", $project->id);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }

    public function assignTaskToEmployee(Request $request)
    {

        DB::beginTransaction();
        try {
            if ($request->employee != "") {
                Task::where("id", $request->task_id)->update(["status" => "Completed", "notes" => "Task Assigned to Employee"]);
                Task::create([
                    "project_id" => $request->project_id,
                    "employee_id" => $request->employee,
                    "department_id" => $request->department_id,
                    "sub_department_id" => $request->sub_department,
                    "assign_to_notes" => $request->notes,
                    "status" => "In-Progress"
                ]);
            } else {
                Task::where("id", $request->task_id)->update(["status" => "Completed", "notes" => "New assign to notes added"]);
                $task = Task::findOrFail($request->task_id);
                Task::create([
                    "project_id" => $task->project_id,
                    "employee_id" => $task->employee_id,
                    "department_id" => $task->department_id,
                    "sub_department_id" => $task->sub_department,
                    "assign_to_notes" => $request->notes,
                    "status" => "In-Progress"
                ]);
            }
            DB::commit();
            return redirect()->route("projects.show", $request->project_id);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
            return redirect()->route("projects.show", $request->project_id)->with("error", $th->getMessage());
        }
    }

    public function projectStatus(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required',
            'reason' => 'required_if:status,Cancelled',
        ]);
        try {
            Task::where("project_id", $request->project_id)
                // ->where("status", "In-Progress")
                ->update(["status" => $request->status, "notes" => $request->reason]);
            return redirect()->route("projects.show", $request->project_id);
        } catch (\Throwable $th) {
            return redirect()->route("projects.show", $request->project_id);
        }
    }

    public function projectQuery(Request $request)
    {
        $query = Project::with("customer", "customer.salespartner", "department", "subdepartment", "assignedPerson", "assignedPerson.employee", "task", "notes");
        $subdepartmentsQuery = SubDepartment::with("department");
        if (auth()->user()->getRoleNames()[0] == "Sales Person") {
            $query->where("sales_partner_user_id",auth()->user()->id);
            // $query->whereHas("customer", function ($query) {
            //     $query->where("sales_partner_id", auth()->user()->sales_partner_id);
            // });
        } else if (auth()->user()->getRoleNames()[0] == "Manager") {
            $query->whereIn("department_id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->pluck("department_id"));
            $subdepartmentsQuery->whereIn("department_id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->pluck("department_id"));
        } else if (auth()->user()->getRoleNames()[0] == "Employee") {
            $query->whereIn("id", Task::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->whereIn("status", ["In-Progress", "Hold", "Cancelled"])->pluck("project_id"));
            $subdepartmentsQuery->whereIn("department_id", EmployeeDepartment::whereIn("employee_id", Employee::where("user_id", auth()->user()->id)->pluck("id"))->pluck("department_id"));
        }
        if ($request->id != "" && $request->id != "all") {
            $query->where("department_id", $request->id);
            $subdepartmentsQuery->where("department_id", $request->id);
        }
        if ($request->id == "all") {
            $subdepartmentsQuery->groupBy("department_id");
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

    public function getDepartmentFields(Request $request)
    {
        try {
            //code...
            if ($request->id) {
                $project = Project::findOrFail($request->projectId);
                return view("projects.partial.department-fields", [
                    "department" => $request->id,
                    "project" => $project,
                ]);
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function getWebsiteProject(Request $request)
    {
        $project = Project::findOrFail($request->project_id);
        $task = Task::whereIn("status", ["In-Progress", "Hold"])->where("project_id", $project->id)->first();
        $departments = Department::whereIn("id", Task::where("project_id", $project->id)->whereNotIn("department_id", Department::where("id", ">", $task->department_id)->take(1)->pluck("id"))->groupBy("department_id")->orderBy("department_id")->pluck("department_id"))->get();
        $fwdDepartments =  array_merge($departments->toArray(), Department::where("id", ">", $task->department_id)->take(1)->get()->toArray());
        try {
            if ($request->project_id) {
                return view("projects.partial.website-project-details", [
                    "project" => Project::with("task", "customer", "department", "logs", "subdepartment", "assignedPerson", "assignedPerson.employee")->where("id", $project->id)->first(),
                    "task" => $task,
                    "backdepartments" => Department::where("id", "<", $task->department_id)->get(),
                    "forwarddepartments" => (object)$fwdDepartments, //Department::whereIn("id", Task::where("project_id", $project->id)->pluck("department_id"))->get(),
                    "filesCount" => ProjectFile::where("project_id", $project->id)->where("department_id", $project->department_id)->get(),
                    "departments" => Department::all(),
                    "employees" => $this->getEmployees($project->department_id),
                    "adders" => AdderType::all(),
                    "uoms" => AdderUnit::all(),
                    "tools" => Tool::where("department_id", $project->department_id)->get()
                ]);
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function saveDepartmentNotes(Request $request)
    {
        $validated = $request->validate([
            'department_notes' => 'required',
        ]);
        try {
            DepartmentNote::create([
                "project_id" => $request->project_id, 
                "task_id" => $request->taskid, 
                "department_id" => $request->department_id,
                "notes" => $request->department_notes
            ]);
            return redirect()->route("projects.show", $request->project_id);
        } catch (\Throwable $th) {
            return $th->getMessage();
            return redirect()->route("projects.show", $request->project_id);
        }
    }
}
