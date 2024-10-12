<?php

namespace App\Http\Controllers;

use App\Jobs\AcceptanceEmailJob;
use App\Mail\AcceptanceEmail;
use App\Models\AdderType;
use App\Models\AdderUnit;
use App\Models\Call;
use App\Models\CallScript;
use App\Models\Customer;
use App\Models\Department;
use App\Models\DepartmentNote;
use App\Models\Email;
use App\Models\EmailScript;
use App\Models\EmailType;
use App\Models\Employee;
use App\Models\EmployeeDepartment;
use App\Models\Project;
use App\Models\ProjectAcceptance;
use App\Models\ProjectCallLog;
use App\Models\ProjectFile;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\Tool;
use App\Traits\MediaTrait;
use FPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use MediaTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // return $this->projectQuery($request);
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
            $code = Project::orderBy("id", "DESC")->first("code");
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
        $project = Project::with("task", "customer", "department", "logs", "logs.call", "subdepartment", "assignedPerson", "assignedPerson.employee", "departmentnotes", "departmentnotes.user", "salesPartnerUser")->where("id", $project->id)->first();
        $task = Task::whereIn("status", ["In-Progress", "Hold", "Cancelled"])->where("project_id", $project->id)->first();
        $departments = Department::whereIn("id", Task::where("project_id", $project->id)->whereNotIn("department_id", Department::where("id", ">", $task->department_id)->take(1)->pluck("id"))->groupBy("department_id")->orderBy("department_id")->pluck("department_id"))->get();
        $fwdDepartments =  array_merge($departments->toArray(), Department::where("id", ">", $task->department_id)->take(1)->get()->toArray());
        Email::where("project_id", $project->id)->where("department_id", $project->department_id)->update(["is_view" => 0]);
        return view("projects.show", [
            "project" => $project,
            "task" => $task,
            "backdepartments" => Department::where("id", "<", $task->department_id)->get(),
            "forwarddepartments" => (object)$fwdDepartments, //Department::whereIn("id", Task::where("project_id", $project->id)->pluck("department_id"))->get(),
            "filesCount" => ProjectFile::where("project_id", $project->id)->where("department_id", $project->department_id)->get(),
            "departments" => Department::all(),
            "employees" => $this->getEmployees($project->department_id),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
            "tools" => Tool::where("department_id", $project->department_id)->get(),
            "calls" => Call::all(),
            "emailTypes" => EmailType::all(),
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
                'hoa' => 'required_if:forward,2',
                'hoa_phone_number' => Rule::requiredIf(function () use ($request) {
                    return $request->forward == 2 && !$request->hoa == "yes";
                }),
                'site_survey_link' => 'required_if:forward,3',
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
                    "hoa" => $request->hoa,
                    "hoa_phone_number" => $request->hoa_phone_number,
                ]);
            }
            if ($request->forward == 3) {
                $updateItems = array_merge($updateItems, [
                    "site_survey_link" => $request->site_survey_link,
                    // "hoa" => $request->hoa,
                    // "hoa_phone_number" => $request->hoa_phone_number,
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
                "call_no" => $request->call_no,
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
        if (in_array("Sales Manager", auth()->user()->getRoleNames()->toArray())) {
            $query->whereHas("customer", function ($q) {
                return $q->where("sales_partner_id", auth()->user()->sales_partner_id);
            });
        } else if (in_array("Sales Person", auth()->user()->getRoleNames()->toArray())) {
            $query->where("sales_partner_user_id", auth()->user()->id);
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

    public function checkWebsiteProject(Request $request)
    {
        try {
            $project = Project::with("customer")
                ->whereHas("customer", function ($query) use ($request) {
                    $query->where("email", $request->email);
                })
                ->where('code', $request->code)->first();
            $url = URL::to('/track-your-project/' . Crypt::encrypt($project->code));
            return response()->json(["status" => 200, "url" => $url]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "error" => $th->getMessage()]);
        }
    }

    public function trackYourProject(Request $request)
    {
        $request->project_id = Crypt::decrypt($request->project_id);
        $project = Project::where('code', $request->project_id)->first();
        $task = Task::whereIn("status", ["In-Progress", "Hold"])->where("project_id", $project->id)->first();
        $departments = Department::whereIn("id", Task::where("project_id", $project->id)->whereNotIn("department_id", Department::where("id", ">", $task->department_id)->take(1)->pluck("id"))->groupBy("department_id")->orderBy("department_id")->pluck("department_id"))->get();
        $fwdDepartments =  array_merge($departments->toArray(), Department::where("id", ">", $task->department_id)->take(1)->get()->toArray());
        try {
            if ($request->project_id) {
                return view("projects.partial.website-project-details", [
                    "project" => Project::with("task", "customer", "department", "logs", "subdepartment", "assignedPerson", "assignedPerson.employee")->where('code', $request->project_id)->first(),
                    "task" => $task,
                    "backdepartments" => Department::where("id", "<", $task->department_id)->get(),
                    "forwarddepartments" => (object)$fwdDepartments,
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
                "notes" => $request->department_notes,
                "user_id" => auth()->user()->id,
            ]);
            return redirect()->route("projects.show", $request->project_id);
        } catch (\Throwable $th) {
            return $th->getMessage();
            return redirect()->route("projects.show", $request->project_id);
        }
    }

    public function getCallScript(Request $request)
    {
        $project =  Project::with("task", "customer", "customer.salespartner", "department", "logs", "subdepartment", "assignedPerson", "assignedPerson.employee")->where("id", $request->project)->first();

        $count = CallScript::where("call_id", $request->call)->where("department_id", $request->department)->where("extra_filter", "hoa")->count();
        $script = CallScript::query();
        $script->where("call_id", $request->call)->where("department_id", $request->department);
        if ($project->hoa == "yes" && $count > 0) {
            $script->where("extra_filter", "hoa");
        }

        return view("projects.partial.call_script", [
            "callScript" => $script->first(),
            "department" => $request->department,
            "callId" => $request->call,
            "project" => $project
        ]);
    }

    public function getEmailScript(Request $request)
    {
        $project =  Project::where("id", $request->project)->first();

        $count = EmailScript::where("email_type_id", $request->emailType)->where("department_id", $request->department)->where("extra_filter", "hoa")->count();
        $script = EmailScript::query();
        $script->where("email_type_id", $request->emailType)->where("department_id", $request->department);
        if ($project->hoa == "yes" && $count > 0) {
            $script->where("extra_filter", "hoa");
        }

        return view("projects.partial.email_script", [
            "emailScript" => $script->first(),
            // "department" => $request->department,
            // "emailTypeId" => $request->email_type,
            // "project" => $project
        ]);
    }

    public function deleteFile(Request $request)
    {
        if ($request->id != "") {
            try {
                $file = ProjectFile::findOrFail($request->id);
                $this->removeImage("", $file->filename);
                $file->delete();
                return response()->json(["status" => 200, "message" => "File delete successfully"]);
            } catch (\Throwable $th) {
                return response()->json(["status" => 500, "message" => "File not found"]);
            }
        } else {
            return response()->json(["status" => 500, "message" => "File not found"]);
        }
    }

    public function projectAcceptance(Request $request)
    {
        if ($request->mode == "post") {
            $request->validate([
                "file" => "required"
            ]);
            $result = $this->uploads($request->file, 'project-acceptance/');
            if (!empty($result)) {
                $project = Project::with("task", "customer", "customer.salespartner", "customer.adders","salesPartnerUser")->where("id", $request->project_id)->first();
                $projectAcceptance = ProjectAcceptance::create([
                    "project_id" => $request->project_id,
                    "sales_partner_id" => $request->sales_partner_id,
                    "image" => $result["fileName"],
                ]);
                $emailText = "<p>Hi ".$project->assignedPerson[0]->employee->name."</p><p>The Project Acceptance Review for the project ".$project->customer->first_name." ".$project->customer->last_name." is ready to be approved.</p><p>Please login to the CRM and navigate to the “Acceptance” tab within the project to approve or dispute the commission amount.</p><p>We look forward to getting a reply within the next 24 hours, after which we will assume the commission as approved.</p><p>If you have any questions, please reach out to us at engineering@solenenergyco.com</p><p>Thank you for your continued support!</p><p>The Solen Energy Construction Engineering Team</p>";
                $this->sendEmailForProjectAcceptance($project,"Project Acceptance Review",$emailText,$project->salesPartnerUser->email);
                if (!empty($projectAcceptance)) {
                    return view("projects.project-acceptance", [
                        "image" => $result["fileName"],
                        "project" => $project,
                        "mode" => "view",
                    ]);
                }
            }
        } else {
            $projectAcceptance = ProjectAcceptance::with("user")->where("project_id", $request->project_id)->latest()->first();
            if (!empty($projectAcceptance)) {
                return view("projects.project-acceptance", [
                    "projectAcceptance" => $projectAcceptance,
                    "project" => Project::with("task", "customer", "customer.salespartner", "customer.adders")->where("id", $request->project_id)->first(),
                    "mode" => "view",
                ]);
            }
        }
    }

    public function generatePDF(Request $request)
    {
        $image = ProjectAcceptance::with("user")->where("project_id", $request->id)->first();
        $project = Project::with("task", "customer", "customer.salespartner", "customer.adders")->where("id", $request->id)->first();

        $modulesAmount = $project->customer->panel_qty * $project->customer->module->amount;

        // Initialize FPDF
        $pdf = new FPDF();
        $pdf->AddPage();

        $pdf->ln(5);
        // Add the Solen logo at the top (50x40 image dimensions)
        $pdf->Image(public_path('storage/solen_logo.png'), 80, -10, 50, 40); // X: 10, Y: 10, width: 50, height: 40

        // Move the cursor down for the title
        $pdf->Ln(5); // Adjust the line break to give enough space after the image

        // Set font for the title
        $pdf->SetFont('Arial', 'B', 16);

        // Add project title
        $pdf->Cell(190, 10, 'Project Acceptance Review', 0, 1, 'C');

        // Add line break
        $pdf->Ln(5);

        // Homeowner details
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Homeowner Name: ' . $project->customer->first_name . ' ' . $project->customer->last_name, 0, 1);
        $pdf->Cell(0, 8, 'Address: ' . $project->customer->address, 0, 1);
        $pdf->Cell(0, 8, 'Phone: ' . $project->customer->phone, 0, 1);

        // Add Image
        if (!empty($image)) {
            $pdf->Image(public_path('storage/project-acceptance/' . $image->image), 10, 60, 190, 100);
        }

        // Line break
        $pdf->Ln(105);

        // Total Adder Cost Title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Total Adder Cost', 0, 1, 'C');

        // Add table for the financial details
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(70, 10, 'Base Price', 1);
        $pdf->Cell(70, 10, $project->customer->inverter->name, 1);
        $pdf->Cell(50, 10, number_format($project->customer->inverter->invertertyperates->base_cost, 2), 1, 1);

        $pdf->Cell(70, 10, 'Module Price', 1);
        $pdf->Cell(70, 10, $project->customer->panel_qty . ' x ' . $project->customer->module->amount, 1);
        $pdf->Cell(50, 10, number_format($modulesAmount, 2), 1, 1);

        $pdf->Cell(70, 10, 'System Cost', 1);
        $pdf->Cell(70, 10, '-', 1);
        $pdf->Cell(50, 10, number_format($project->customer->finances->redline_costs, 2), 1, 1);

        $pdf->Cell(70, 10, 'Adder Total', 1);
        $pdf->Cell(70, 10, '-', 1);
        $pdf->Cell(50, 10, number_format($project->customer->finances->adders, 2), 1, 1);

        $pdf->Cell(70, 10, 'Dealer Fee', 1);
        $pdf->Cell(70, 10, '-', 1);
        $pdf->Cell(50, 10, number_format($project->customer->finances->dealer_fee_amount, 2), 1, 1);

        $pdf->Cell(70, 10, 'Commission', 1);
        $pdf->Cell(70, 10, '-', 1);
        $pdf->Cell(50, 10, number_format($project->customer->finances->commission, 2), 1, 1);

        $pdf->Cell(70, 10, 'Contract Price', 1);
        $pdf->Cell(70, 10, '-', 1);
        $pdf->Cell(50, 10, number_format($project->customer->finances->contract_amount, 2), 1, 1);
        $addersName = "";

        foreach ($project->customer->adders as $adders) {
            $addersName .= $adders->type->name . ",";
        }

        $pdf->ln(5);
        // Total Adder Cost Title
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Adders : ', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, $addersName, 0, 1, 'L');

        // Set the file path where you want to save the PDF in the 'storage/app/public/pdfs' folder
        $filePath = storage_path('app/public/pdfs/project_acceptance_review-' . $project->id . '.pdf');

        // Ensure the 'pdfs' folder exists, if not, create it
        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0777, true);
        }

        // Save the PDF to the specified path
        $pdf->Output('F', $filePath); // 'F' option saves the file to the given pat

        // Output the PDF
        // $pdf->Output('I', 'project_acceptance_review.pdf'); // 'D' for download, 'I' for inline
        $ccEmails = "";
        $attachments = [];
        $details = [
            "subject" => "Project Acceptance Review",
            "body" => "Hi, Project Acceptance Review PDf is attached. Please check the attachment. ",
            "project_id" => $project->id,
            "department_id" => 3,
            "customer_id" => $project->customer_id,
            "customer_email" => "hmadilkhan@gmail.com",
        ];
        array_push($attachments,  'project_acceptance_review-' . $project->id . '.pdf');
        // dispatch(new AcceptanceEmailJob($details, $attachments, $ccEmails));
        // Mail::mailer("dealreview")->to($details['customer_email'])->send(new AcceptanceEmail($details, $attachments,$ccEmails));
        // return Mail::mailer("dealreview")->to($details['customer_email'])->send(new AcceptanceEmail($details, $attachments, $ccEmails));
        return response()->json(["status" => 200, "message" => "Email has been sent"]);
        exit;
    }

    public function actionProjectAcceptance(Request $request)
    {
        try {
            $projectAcceptance = ProjectAcceptance::with("user")->where("project_id", $request->projectId)->latest()->first();
            $project = Project::with( "assignedPerson", "assignedPerson.employee")->where("id", $projectAcceptance->project_id)->first();
            ProjectAcceptance::where("id", $request->id)->update([
                "action_by" => auth()->user()->id,
                "status" => $request->mode,
                "approved_date" => date("Y-m-d H:i:s"),
            ]);
            $this->sendEmailForProjectAcceptance($project,"Project Acceptance Review Status","<p>Dear ".$project->assignedPerson[0]->employee->name.", </br> Project Acceptance document has been ".( $request->mode == 1 ? 'approved' : 'rejected').". Please login to CRM and take necessary action !</br>Best Regards.</br> Solen Energy Co. Team</p>","engineering@solenenergyco.com");
            return response()->json(["status" => 200, "message" => "Project Acceptance Approved"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => "Error: " . $th->getMessage()]);
        }
    }

    public function sendEmailForProjectAcceptance($project, $subject, $body, $emailTo)
    {
        $ccEmails = "";
        $attachments = [];
        $details = [
            "subject" => $subject,
            "body" => $body,
            "project_id" => $project->id,
            "department_id" => 3,
            "customer_id" => $project->customer_id,
            "customer_email" => $emailTo,
        ];
        dispatch(new AcceptanceEmailJob($details, $attachments, $ccEmails));
    }
}
