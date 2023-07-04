<?php

namespace App\Http\Controllers;

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
use App\Models\Project;
use App\Models\SalesPartner;
use App\Models\SubDepartment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("customer.index", [
            "customers" => Customer::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("customer.create", [
            "financeoptions" => FinanceOption::all(),
            "partners" => SalesPartner::all(),
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
            'adders' => 'required',
            'commission' => 'required',
            'dealer_fee' => 'required',
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
                "battery_type_id" => $request->battery_type_id,
                "inverter_qty" => $request->inverter_qty,
                "module_qty" => $request->module_qty,
                "battery_qty" => $request->battery_qty,
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
            $count = count($request->uom);
            if ($count > 0) {
                for ($i = 0; $i < $count; $i++) {
                    CustomerAdder::create([
                        "customer_id" => $customer->id,
                        "adder_type_id" => $request->adders[$i],
                        "adder_sub_type_id" => $request->subadders[$i],
                        "adder_unit_id" => $request->uom[$i],
                        "amount" => $request->amount[$i],
                    ]);
                }
            }
            $subdepartment = SubDepartment::where("department_id",1)->first();
            $project = Project::create([
                "customer_id" => $customer->id,
                "project_name" => $request->first_name.$request->last_name,
                "department_id" => 1,
                "sub_department_id" => $subdepartment->id,
                "description" =>  $request->notes,
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view("customer.edit", [
            "customer" => $customer,
            "partners" => SalesPartner::all(),
            "financeoptions" => FinanceOption::all(),
            "inverter_types" => InverterType::all(),
            "battery_types" => BatteryType::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        try {
            DB::beginTransaction();
            $customer->update($request->except(["finance_option_id", "contract_amount", "redline_costs", "adders", "commission", "dealer_fee"]));
            $customer->finances()->update($request->only(["finance_option_id", "contract_amount", "redline_costs", "adders", "commission", "dealer_fee"]));
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
    public function destroy(Customer $customer)
    {
        //
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
            $aprs = LoanApr::where("loan_term_id", $request->id)->get();
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
            $cost = InverterTypeRate::where("inverter_type_id", $request->inverterType)->where("panels_qty", $request->qty)->first("redline_cost");
            return response()->json(["status" => 200, "redlinecost" => $cost->redline_cost]);
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
            $adders = Adder::where("adder_type_id", $request->adder)->where("adder_sub_type_id", $request->subadder)->first();
            return response()->json(["status" => 200, "adders" => $adders]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }

    public function getModulTypevalue(Request $request)
    {
        try {
            $types = ModuleType::where("id", $request->id)->first();
            return response()->json(["status" => 200, "types" => $types]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 200, "message" => $th->getMessage()]);
        }
    }
}
