<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Adder;
use App\Models\AdderSubType;
use App\Models\AdderType;
use App\Models\AdderUnit;
use App\Models\BatteryType;
use App\Models\Customer;
use App\Models\CustomerAdder;
use App\Models\CustomerFinance;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\LoanApr;
use App\Models\LoanTerm;
use App\Models\ModuleType;
use App\Models\OfficeCost;
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Models\UtilityCompany;
use App\Traits\MediaTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class IntakeFormController extends Controller
{
    use MediaTrait;

    public function __construct()
    {
        $this->middleware(['role:Sales Person|Sales Manager']);
    }

    public function index()
    {
        return view("intake-form.index", [
            "customers" => Customer::getCustomersBySalesUser()->latest()->get(),
        ]);
    }

    public function create()
    {
        return view("intake-form.create", [
            "financeoptions" => FinanceOption::all(),
            "partners" => SalesPartner::where("id", auth()->user()->salesPartner->id)->get(),
            "inverter_types" => InverterType::all(),
            "battery_types" => BatteryType::all(),
            "modules" => ModuleType::all(),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
            "contractors" => SubContractor::all(),
            "utilityCompanies" => UtilityCompany::all(),
            "salesPartnerUsers" => User::where("id", auth()->user()->id)->where("user_type_id",3)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $officeCost = OfficeCost::first();
        $validated = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'street' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zipcode' => 'required',
            'panel_qty' => 'required',
            'sold_date' => 'required',
            'sales_partner_id' => 'required',
            'finance_option_id' => 'required',
            'contract_amount' => 'required',
            'redline_costs' => 'required',
            'commission' => 'required',
            'dealer_fee' => 'required',
            'sales_partner_user_id' => 'required',
            'loanId' => [
                Rule::requiredIf(function () use ($request) {
                    return !in_array((int) $request->finance_option_id, [1, 5]);
                }),
            ],
        ]);

        try {
            DB::beginTransaction();
            $inverterBaseCost = InverterTypeRate::where("inverter_type_id", $request->inverter_type_id)->first();
            $moduleCost = ModuleType::where("inverter_type_id", $request->inverter_type_id)->where("id", $request->module_type_id)->first();
            $customer = Customer::create([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "street" => $request->street,
                "city" => $request->city,
                "state" => $request->state,
                "zipcode" => $request->zipcode,
                "phone" => $request->phone,
                "email" => $request->email,
                "preferred_language" => $request->preferred_language,
                "sales_partner_id" => $request->sales_partner_id,
                "sold_date" => $request->sold_date,
                "panel_qty" => $request->panel_qty,
                "inverter_type_id" => $request->inverter_type_id,
                "module_type_id" => $request->module_type_id,
                "inverter_qty" => $request->inverter_qty,
                "module_value" => $request->module_qty,
                "notes" => $request->notes,
                "is_adu" => $request->adu,
                "loan_id" => $request->loanId,
                "sold_production_value" => $request->sold_production_value,
            ]);
            $holdBackAmount = 0;
            $financeOption = FinanceOption::where("id", $request->finance_option_id)->first();
            if ($financeOption->holdback == 1) {
                $holdBackAmount = ($request->module_qty * $financeOption->dollar_watt_value);
            }
            CustomerFinance::create([
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
                "total_overwrite_base_price" => $request->overwrite_base_price,
                "total_overwrite_panel_price" => ($request->overwrite_panel_price * $request->panel_qty),
                "module_type_cost" => $moduleCost->amount,
                "inverter_base_cost" => $inverterBaseCost->base_cost,
                "holdback_amount" => $holdBackAmount,
            ]);
            if (!empty($request->uom)) {
                $count = count($request->uom);
                if ($count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        CustomerAdder::create([
                            "customer_id" => $customer->id,
                            "adder_type_id" => $request->adders[$i],
                            "adder_unit_id" => $request->uom[$i],
                            "amount" => $request->amount[$i],
                        ]);
                    }
                }
            }

            $subdepartment = SubDepartment::query();
            if ($request->adu == 1) {
                $subdepartment->where("name", "ADU");
            } else {
                $subdepartment->where("department_id", 1);
            }
            $subdepartment = $subdepartment->first();

            $avgPermitFee = DB::table('projects')
                ->selectRaw('AVG(actual_permit_fee) as avg_permit_fee')
                ->whereNotNull('actual_permit_fee')
                ->first();

            // Determine department based on schedule_survey checkbox
            $departmentId = 1;
            $subDepartmentId = $subdepartment->id;

            if ($request->has('schedule_survey') && $request->schedule_survey == 1) {
                $departmentId = 2; // Site Survey department
                $subDepartmentId = 3; // Site Survey subdepartment
            }

            $projectData = [
                "customer_id" => $customer->id,
                "project_name" => $request->first_name . "-" . $request->last_name,
                "department_id" => $departmentId,
                "sub_department_id" => $subDepartmentId,
                "description" =>  $request->notes,
                "office_cost" => (!empty($officeCost) ? $officeCost->cost : ""),
                "sales_partner_user_id" => $request->sales_partner_user_id,
                "code" => $this->generateProjectCode(),
                "overwrite_base_price" =>  $request->overwrite_base_price,
                "overwrite_panel_price" =>  $request->overwrite_panel_price,
                "pre_estimated_permit_costs" =>  $avgPermitFee->avg_permit_fee,
            ];

            // Add department review fields if schedule_survey is checked
            if ($request->has('schedule_survey') && $request->schedule_survey == 1) {
                $projectData['utility_company'] = $request->utility_company;
                $projectData['ntp_approval_date'] = $request->ntp_approval_date;
                $projectData['hoa'] = $request->hoa;
                $projectData['hoa_phone_number'] = $request->hoa_phone_number;
            }

            $project = Project::create($projectData);
            $username = auth()->user()->name;
            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->setEvent("update")
                ->log("{$username} created the project.");
            Task::create([
                "project_id" => $project->id,
                "employee_id" => 1,
                "department_id" => $departmentId,
                "sub_department_id" => $subDepartmentId,
            ]);

            // Handle file uploads if schedule_survey is checked
            if ($request->has('schedule_survey') && $request->schedule_survey == 1) {
                $fileFields = ['contract_pdf', 'cpuc_pdf', 'disclosure_document', 'electronic_signature', 'utility_bill'];

                foreach ($fileFields as $field) {
                    if ($request->hasFile($field)) {
                        $result = $this->uploads($request->file($field), 'projects/');
                        \App\Models\ProjectFile::create([
                            "project_id" => $project->id,
                            "task_id" => $project->task->first()->id,
                            "department_id" => $departmentId,
                            "filename" => $result["fileName"],
                            "header_text" => ucfirst(str_replace('_', ' ', $field)),
                        ]);
                    }
                }
            }
            DB::commit();

            if ($request->has('schedule_survey') && $request->schedule_survey == 1) {
                return redirect()->to('/site-surveys/schedule/' . $project->id);
            }

            return redirect()->route("intake-form.index");
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route("intake-form.create")->with('error', $th->getMessage());
        }
    }

    public function generateProjectCode()
    {
        $project = Project::orderBy("id", "DESC")->first("code");

        if (empty($project)) {
            $code = "1001";
            return $code;
        } else {
            return $project->code + 1;
        }
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view("intake-form.edit", [
            "customer" => $customer,
            "financeoptions" => FinanceOption::all(),
            "partners" =>  SalesPartner::all(),
            "inverter_types" => InverterType::all(),
            "battery_types" => BatteryType::all(),
            "modules" => ModuleType::all(),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
            "users" => User::where("sales_partner_id", $customer->sales_partner_id)->get(),
            "contractors" => SubContractor::all(),
            "utilityCompanies" => UtilityCompany::all(),
            "salesPartnerUsers" => User::where("id", auth()->user()->id)->where("user_type_id",3)->get(),
        ]);
    }

    public function update(Request $request, $id)
    // {
    {
        try {
            DB::beginTransaction();
            $customer = Customer::findOrFail($id);
            $inverterBaseCost = InverterTypeRate::where("inverter_type_id", $request->inverter_type_id)->first();
            $moduleCost = ModuleType::where("inverter_type_id", $request->inverter_type_id)->where("id", $request->module_type_id)->first();
            $customer->update([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "street" => $request->street,
                "city" => $request->city,
                "state" => $request->state,
                "zipcode" => $request->zipcode,
                "phone" => $request->phone,
                "email" => $request->email,
                "preferred_language" => $request->preferred_language,
                "sales_partner_id" => $request->sales_partner_id,
                "sold_date" => $request->sold_date,
                "panel_qty" => $request->panel_qty,
                "inverter_type_id" => $request->inverter_type_id,
                "module_type_id" => $request->module_type_id,
                "inverter_qty" => $request->inverter_qty,
                "module_value" => $request->module_qty,
                "notes" => $request->notes,
                "is_adu" => $request->adu,
                "loan_id" => $request->loanId,
                "sold_production_value" => $request->sold_production_value,
            ]);

            $customer->project->update([
                "project_name" => $request->first_name . "-" . $request->last_name,
            ]);

            if (!empty($request->uom)) {
                $customer->adders()->delete();
                $count = count($request->uom);
                if ($count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        $customer->adders()->create([
                            "customer_id" => $customer->id,
                            "adder_type_id" => $request->adders[$i],
                            "adder_unit_id" => $request->uom[$i],
                            "amount" => $request->amount[$i],
                        ]);
                    }
                }
            }
            $holdBackAmount = 0;
            $financeOption = FinanceOption::where("id", $request->finance_option_id)->first();
            if ($financeOption->holdback == 1) {
                $holdBackAmount = ($request->module_qty * $financeOption->dollar_watt_value);
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
                "module_type_cost" => $moduleCost->amount,
                "inverter_base_cost" => $inverterBaseCost->base_cost,
                "holdback_amount" => $holdBackAmount,
            ]);
            if ($request->sales_partner_user_id != "") {
                $customer->project()->update([
                    "sales_partner_user_id" => $request->sales_partner_user_id,
                    "sub_contractor_user_id" => $request->sub_contractor_user_id,
                ]);
            }
            DB::commit();
            return redirect()->route("intake-form.index");
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }

    public function destroy(Request $request)
    {
        try {
            DB::beginTransaction();
            Project::where("customer_id", $request->id)->delete();
            Customer::where("id", $request->id)->delete();
            DB::commit();
            return response()->json(["status" => 200, "message" => "Customer Deleted Successfully."]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "message" => "Error. " . $th->getMessage()]);
        }
    }
}
