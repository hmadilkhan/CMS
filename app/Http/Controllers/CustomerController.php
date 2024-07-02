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
use App\Models\SubDepartment;
use App\Models\Task;
use App\Models\User;
use App\Traits\MediaTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CustomerController extends Controller
{
    use MediaTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("customer.index", [
            "customers" => Customer::getCustomers()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("customer.create", [
            "financeoptions" => FinanceOption::all(),
            "partners" => SalesPartner::all(), //User::filterByRole('Sales Person')->get(),
            "inverter_types" => InverterType::all(),
            "battery_types" => BatteryType::all(),
            "modules" => ModuleType::all(),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
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
            // 'adders' => 'required',
            'commission' => 'required',
            'dealer_fee' => 'required',
            'sales_partner_user_id' => 'required',
        ]);

        try {
            DB::beginTransaction();
            // Customer::create($request->except(["finance_option_id", "contract_amount", "redline_costs", "adders", "commission", "dealer_fee","loan_term_id","loan_apr_id","dealer_fee_amount"]));
            $customer = Customer::create([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "street" => $request->street,
                "city" => $request->city,
                "state" => $request->state,
                "zipcode" => $request->zipcode,
                "phone" => $request->phone,
                "email" => $request->email,
                "sales_partner_id" => $request->sales_partner_id,
                "sold_date" => $request->sold_date,
                "panel_qty" => $request->panel_qty,
                "inverter_type_id" => $request->inverter_type_id,
                "module_type_id" => $request->module_type_id,
                "inverter_qty" => $request->inverter_qty,
                "module_value" => $request->module_qty,
                "notes" => $request->notes,
            ]);

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
            ]);
            if (!empty($request->uom)) {
                $count = count($request->uom);
                if ($count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        CustomerAdder::create([
                            "customer_id" => $customer->id,
                            "adder_type_id" => $request->adders[$i],
                            // "adder_sub_type_id" => $request->subadders[$i],
                            "adder_unit_id" => $request->uom[$i],
                            "amount" => $request->amount[$i],
                        ]);
                    }
                }
            }
            $subdepartment = SubDepartment::where("department_id", 1)->first();
            $project = Project::create([
                "customer_id" => $customer->id,
                "project_name" => $request->first_name . "-" . $request->last_name,
                "department_id" => 1,
                "sub_department_id" => $subdepartment->id,
                "description" =>  $request->notes,
                "office_cost" => (!empty($officeCost) ? $officeCost->cost : ""),
                "sales_partner_user_id" => $request->sales_partner_user_id,
                "code" => $this->generateProjectCode(),
            ]);
            Task::create([
                "project_id" => $project->id,
                "employee_id" => 1,
                "department_id" => 1,
                "sub_department_id" => $subdepartment->id,
            ]);
            DB::commit();
            return redirect()->route("customers.index");
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
            return redirect()->route("customers.create");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view("customer.edit", [
            "customer" => $customer,
            "financeoptions" => FinanceOption::all(),
            "partners" =>  SalesPartner::all(),
            "inverter_types" => InverterType::all(),
            "battery_types" => BatteryType::all(),
            "modules" => ModuleType::all(),
            "adders" => AdderType::all(),
            "uoms" => AdderUnit::all(),
            "users" => User::where("sales_partner_id", $customer->sales_partner_id)->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        try {
            DB::beginTransaction();
            $customer->update([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "street" => $request->street,
                "city" => $request->city,
                "state" => $request->state,
                "zipcode" => $request->zipcode,
                "phone" => $request->phone,
                "email" => $request->email,
                "sales_partner_id" => $request->sales_partner_id,
                "sold_date" => $request->sold_date,
                "panel_qty" => $request->panel_qty,
                "inverter_type_id" => $request->inverter_type_id,
                "module_type_id" => $request->module_type_id,
                "inverter_qty" => $request->inverter_qty,
                "module_value" => $request->module_qty,
                "notes" => $request->notes,
            ]);
            if (!empty($request->uom)) {
                $customer->adders()->delete();
                $count = count($request->uom);
                if ($count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        $customer->adders()->create([
                            "customer_id" => $customer->id,
                            "adder_type_id" => $request->adders[$i],
                            // "adder_sub_type_id" => $request->subadders[$i],
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
            if ($request->sales_partner_user_id != "") {
                $customer->project()->update([
                    "sales_partner_user_id" => $request->sales_partner_user_id
                ]);
            }
            DB::commit();
            return redirect()->route("customers.index");
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
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

    public function getLoanTerms(Request $request)
    {
        try {
            $terms = LoanTerm::where("finance_option_id", $request->id)->get();
            return response()->json(["status" => 200, "terms" => $terms]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getLoanAprs(Request $request)
    {
        try {
            $aprs = LoanApr::where("loan_term_id", $request->id)->where("finance_option_id", $request->finance_option_id)->get();
            return response()->json(["status" => 200, "aprs" => $aprs]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getDealerFee(Request $request)
    {
        try {
            $dealer_fee = LoanApr::where("id", $request->id)->first("dealer_fee");
            return response()->json(["status" => 200, "dealerfee" => $dealer_fee->dealer_fee]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getRedlineCost(Request $request)
    {
        try {
            // $cost = InverterTypeRate::where("inverter_type_id", $request->inverterType)->where("panels_qty", $request->qty)->first("redline_cost");
            $cost = InverterTypeRate::where("inverter_type_id", $request->inverterType)->first("base_cost");
            $modules = ModuleType::where("inverter_type_id", $request->inverterType)->get();
            return response()->json(["status" => 200, "redlinecost" => $cost->base_cost, "modules" => $modules]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getSubAdders(Request $request)
    {
        try {
            $subadders = AdderSubType::where("adder_type_id", $request->id)->get();
            return response()->json(["status" => 200, "subadders" => $subadders]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getAdderDetails(Request $request)
    {
        try {
            $adders = Adder::where("adder_type_id", $request->adder)
                //  ->where("adder_sub_type_id", $request->subadder)
                ->first();
            return response()->json(["status" => 200, "adders" => $adders]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getModulTypevalue(Request $request)
    {
        try {
            $types = ModuleType::where("id", $request->id)->where("inverter_type_id", $request->inverterTypeId)->first();
            return response()->json(["status" => 200, "types" => $types]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getSalesPartnerUsers(Request $request)
    {
        try {
            $users = User::where("sales_partner_id", $request->id)->get();
            return response()->json(["status" => 200, "users" => $users]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function sendEmail(Request $request)
    {
        // config(['mail.mailers.info.username' => 'dealreview@testsolencrm.com']);
        // config(['mail.mailers.info.password' => 'Deal@247']);
        // config(['mail.mailers.info.from.address' => 'dealreview@testsolencrm.com']);
        // config(['mail.mailers.info.from.name' => 'Solen Energy Co. - Deal Review']);

        // config(['mail.mailers.info.username' => 'sitesurvey@testsolencrm.com']);
        // config(['mail.mailers.info.password' => 'Site@247']);
        // config(['mail.mailers.info.from.address' => 'sitesurvey@testsolencrm.com']);
        // config(['mail.mailers.info.from.name' => 'Solen Energy Co. - Site Survey']);
        
        // dump(config('mail.mailers.info'));
        $attachments = [];
        $details = [
            "subject" => $request->subject,
            "body" => $request->content,
            "project_id" => $request->project_id,
            "department_id" => $request->department_id,
            "customer_id" => $request->customer_id,
        ];
        if (!empty($request->images) && count($request->images) > 0) {
            foreach ($request->images  as $file) {
                $savedFile = $this->uploads($file, 'emails/');
                array_push($attachments,$savedFile['fileName']);
            }
        }
        dispatch(new SendEmailJob($details,$attachments));
    }
}
