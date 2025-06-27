<?php

namespace App\Http\Controllers;

use App\Models\Adder;
use App\Models\AdderSubType;
use App\Models\AdderType;
use App\Models\AdderUnit;
use App\Models\Call;
use App\Models\CallScript;
use App\Models\Department;
use App\Models\EmailScript;
use App\Models\EmailType;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\LoanApr;
use App\Models\LoanTerm;
use App\Models\ModuleType;
use App\Models\SalesPartner;
use App\Models\User;
use App\Models\UtilityCompany;
use App\Traits\MediaTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationController extends Controller
{
    use MediaTrait;
    public function changeRedlineCostView(Request $request)
    {
        return view("operations/redline/redlinecostchange", [
            "redlinelist" => InverterTypeRate::with("inverter")->get(),
            "inverters" => InverterType::all(),
            "redline" => ($request->id != "" ? InverterTypeRate::find($request->id) : []),
        ]);
    }

    public function getRedlineCostByInverter(Request $request)
    {
        if ($request->inverter_type_id != "") {
            $inverters =  InverterTypeRate::with("inverter")->where("inverter_type_id", $request->inverter_type_id)->get();
            return response()->json(["redlinecostlist" => $inverters]);
        }
    }

    public function redlineUpdate(Request $request)
    {
        try {
            $inverterTypeRate = InverterTypeRate::find($request->id);
            $inverterTypeRate->base_cost = $request->base_cost;
            $inverterTypeRate->internal_base_cost = $request->internal_base_cost;
            $inverterTypeRate->internal_labor_cost = $request->internal_labor_cost;
            $inverterTypeRate->save();
            return redirect()->route("view-redline-cost");
        } catch (\Throwable $th) {
            return redirect()->route("view-redline-cost")->with('error', $th->getMessage());
        }
    }


    public function redlineStore(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required_if:status,Cancelled|integer',
        ]);
        try {
            $count = InverterTypeRate::where("inverter_type_id", $request->inverter_type_id)->count();
            if ($count == 0) {
                InverterTypeRate::create([
                    "inverter_type_id" => $request->inverter_type_id,
                    "base_cost" => $request->base_cost,
                    "internal_base_cost" => $request->internal_base_cost,
                    "internal_labor_cost" => $request->internal_labor_cost,
                ]);
                return redirect()->route("view-redline-cost")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("view-redline-cost")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            return redirect()->route("view-redline-cost")->with("error", $th->getMessage());
        }
    }

    public function redlineDelete(Request $request)
    {
        try {
            InverterTypeRate::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function dealerFeeView(Request $request)
    {
        if ($request->id != "") {
            $loan = LoanApr::with("loan", "loan.finance")->where("id", $request->id)->first();
        }
        return view("operations/dealerfee/index", [
            "dealerfeelist" => LoanApr::with("loan", "finance")->get(),
            "terms" => LoanTerm::groupBy("year")->orderBy("id", "asc")->get(),
            // "financing" => ($request->id != "" ? FinanceOption::whereIn("id", LoanTerm::where("year", $loan->loan->year)->pluck("finance_option_id"))->get() : [] ),
            "financing" => FinanceOption::all(),
            "loan" => ($request->id != "" ? $loan : []),
        ]);
    }

    public function dealerFeeUpdate(Request $request)
    {
        try {
            $year = LoanTerm::where("id", $request->loan_term_id)->first();
            $loan = LoanTerm::where("finance_option_id", $request->finance_option_id)->where("year", $year->year)->first();
            $loanApr = LoanApr::find($request->id);
            // $loanApr->loan_term_id = $request->loan_term_id;
            $loanApr->loan_term_id = $loan->id;
            $loanApr->apr = $request->apr;
            $loanApr->dealer_fee = $request->dealer_fee;
            $loanApr->save();
            return redirect()->route("view-dealer-fee");
        } catch (\Throwable $th) {
            return redirect()->route("view-dealer-fee")->with('error', $th->getMessage());
        }
    }

    public function dealerFeeStore(Request $request)
    {
        $validated = $request->validate([
            'loan_term_id' => 'required',
            'finance_option_id' => 'required',
            'apr' => 'required',
            'dealer_fee' => 'required',
        ]);
        try {
            $count = LoanApr::where("loan_term_id", $request->loan_term_id)->where("finance_option_id", $request->finance_option_id)->count();
            if ($count == 0) {
                LoanApr::create([
                    "loan_term_id" => $request->loan_term_id,
                    "finance_option_id" => $request->finance_option_id,
                    "apr" => $request->apr,
                    "dealer_fee" => $request->dealer_fee,
                ]);
                return redirect()->route("view-dealer-fee")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("view-dealer-fee")->with("error", "Loan term already exists. Please update");
            }
        } catch (\Throwable $th) {
            return redirect()->route("view-dealer-fee")->with("error", $th->getMessage());
        }
    }

    public function dealerFeeDelete(Request $request)
    {
        try {
            LoanApr::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function getFinanceOption(Request $request)
    {
        if ($request->id != "") {
            $year = LoanTerm::findOrFail($request->id);
            $finances = FinanceOption::whereIn("id", LoanTerm::where("year", $year->year)->pluck("finance_option_id"))->get();
            return response()->json(["status" => 200, "finances" => $finances]);
        }
    }

    public function addersView(Request $request)
    {
        if ($request->id != "") {
            $adder = Adder::with("type", "unit")->where("id", $request->id)->first();
        }
        return view("operations/adders/index", [
            "adders" => Adder::with("type", "unit")->get(),
            "types" => AdderType::all(),
            "units" => AdderUnit::all(),
            "adder" => ($request->id != "" ? $adder : []),
        ]);
    }

    public function addersStore(Request $request)
    {
        try {
            $count = Adder::where("adder_type_id", $request->adder_type_id)->where("price", $request->price)->count();
            if ($count == 0) {
                Adder::create([
                    "adder_type_id" => $request->adder_type_id,
                    // "adder_sub_type_id" => $request->adder_sub_type_id,
                    "adder_unit_id" => $request->adder_unit_id,
                    "price" => $request->price,
                ]);
                return redirect()->route("view-adders")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("view-adders")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            return redirect()->route("view-adders")->with("error", $th->getMessage());
        }
    }

    public function addersUpdate(Request $request)
    {
        try {
            $adder = Adder::find($request->id);
            $adder->adder_type_id = $request->adder_type_id;
            // $adder->adder_sub_type_id = $request->adder_sub_type_id;
            $adder->adder_unit_id = $request->adder_unit_id;
            $adder->price = $request->price;
            $adder->save();
            return redirect()->route("view-adders");
        } catch (\Throwable $th) {
            return redirect()->route("view-adders")->with('error', $th->getMessage());
        }
    }

    public function addersDelete(Request $request)
    {
        try {
            Adder::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function financeOptionView(Request $request)
    {
        if ($request->id != "") {
            $finance = FinanceOption::where("id", $request->id)->first();
        }
        return view("operations/finance-options/index", [
            "financeOptions" => FinanceOption::all(),
            "finance" => ($request->id != "" ? $finance : []),
        ]);
    }

    public function financeOptionStore(Request $request)
    {
        try {
            $count = FinanceOption::where("name", $request->name)->count();

            if ($count == 0) {
                DB::beginTransaction();
                $finance = FinanceOption::create([
                    "name" => $request->name,
                    "loan_id" => $request->loan_id,
                    "production_requirements" => $request->production_requirements,
                    "positive_variance" => $request->positive_variance,
                    "negative_variance" => $request->negative_variance,
                ]);
                LoanTerm::create([
                    "finance_option_id" => $finance->id,
                    "year" => '10 Years',
                ]);
                LoanTerm::create([
                    "finance_option_id" => $finance->id,
                    "year" => '25 Years',
                ]);
                DB::commit();
                return redirect()->route("finance.option.types")->with("success", "Data Saved Successfully");
            } else {
                DB::rollBack();
                return redirect()->route("finance.option.types")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {

            return redirect()->route("finance.option.types")->with("error", $th->getMessage());
        }
    }

    public function financeOptionUpdate(Request $request)
    {
        try {
            $adder = FinanceOption::find($request->id);
            $adder->name = $request->name;
            $adder->loan_id = $request->loan_id;
            $adder->production_requirements = $request->production_requirements;
            $adder->positive_variance = $request->positive_variance;
            $adder->negative_variance = $request->negative_variance;
            $adder->save();
            return redirect()->route("finance.option.types");
        } catch (\Throwable $th) {
            return redirect()->route("finance.option.types")->with('error', $th->getMessage());
        }
    }

    public function financeOptionDelete(Request $request)
    {
        try {
            DB::beginTransaction();
            LoanTerm::where("finance_option_id", $request->id)->delete();
            FinanceOption::where("id", $request->id)->delete();
            DB::commit();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500]);
        }
    }

    public function addersTypeView(Request $request)
    {
        if ($request->id != "") {
            $adder = AdderType::where("id", $request->id)->first();
        }
        return view("operations/adder-type/index", [
            "adders" => AdderType::all(),
            "adder" => ($request->id != "" ? $adder : []),
        ]);
    }

    public function addersTypeStore(Request $request)
    {
        try {
            $count = AdderType::where("name", $request->name)->count();
            if ($count == 0) {
                AdderType::create([
                    "name" => $request->name,
                ]);
                return redirect()->route("view.adder.types")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("view.adder.types")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            return redirect()->route("view.adder.types")->with("error", $th->getMessage());
        }
    }

    public function addersTypeUpdate(Request $request)
    {
        try {
            $adder = AdderType::find($request->id);
            $adder->name = $request->name;
            $adder->save();
            return redirect()->route("view.adder.types");
        } catch (\Throwable $th) {
            return redirect()->route("view.adder.types")->with('error', $th->getMessage());
        }
    }

    public function addersTypeDelete(Request $request)
    {
        try {
            AdderType::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function getSubTypes(Request $request)
    {
        if ($request->id != "") {
            $subtypes = AdderSubType::where("adder_type_id", $request->id)->get();
            return response()->json(["status" => 200, "subtypes" => $subtypes]);
        }
    }


    public function salesPartnerView(Request $request)
    {
        if ($request->id != "") {
            $partner = SalesPartner::where("id", $request->id)->first();
        }
        return view("operations/sales-partner/index", [
            "partners" => SalesPartner::all(),
            "partner" => ($request->id != "" ? $partner : []),
        ]);
    }

    public function salesPartnerStore(Request $request)
    {
        try {
            $count = SalesPartner::where("name", $request->name)->count();
            if ($count == 0) {
                $result = $this->uploads($request->file, 'salespartners/', "");
                SalesPartner::create([
                    "name" => $request->name,
                    'image' => (!empty($result) ? $result["fileName"] : ""),
                    "email" => $request->email,
                    "phone" => $request->phone,
                ]);
                return redirect()->route("sales.partner.types")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("sales.partner.types")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            return redirect()->route("sales.partner.types")->with("error", $th->getMessage());
        }
    }

    public function salesPartnerUpdate(Request $request)
    {
        try {
            $result = $this->uploads($request->file, 'salespartners/', $request->previous_logo);
            $salesPartner = SalesPartner::find($request->id);
            $salesPartner->name = $request->name;
            $salesPartner->email = $request->email;
            $salesPartner->phone = $request->phone;
            $salesPartner->image = (!empty($result) ? $result["fileName"] : $request->previous_logo);
            $salesPartner->save();
            return redirect()->route("sales.partner.types");
        } catch (\Throwable $th) {
            return redirect()->route("sales.partner.types")->with('error', $th->getMessage());
        }
    }

    public function salesPartnerDelete(Request $request)
    {
        try {
            SalesPartner::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function salesPartnerOverwriteCost(Request $request)
    {
        $overwrites = [];
        try {

            $salesPartnerUser = User::where("id", $request->id)->first();

            if (!empty($salesPartnerUser)) {
                $overwrites = [
                    "overwrite_base_price" => $salesPartnerUser->overwrite_base_price,
                    "overwrite_panel_price" => $salesPartnerUser->overwrite_panel_price,
                ];
            }
            return response()->json(["overwrites" => $overwrites, "status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "error" => $th->getMessage()]);
        }
    }

    /************************************CALL SCRIPTS STARTS **************************************************************/

    public function callScriptList(Request $request)
    {
        if ($request->id != "") {
            $script = CallScript::with("call", "department")->where("id", $request->id)->first();
        }
        return view("operations/call-scripts/index", [
            "calls" => Call::all(),
            "departments" => Department::all(),
            "callScripts" => CallScript::with("call", "department")->get(),
            "script" => ($request->id != "" ? $script : []),
        ]);
    }

    public function callScriptStore(Request $request)
    {
        try {
            $count = CallScript::where("call_id", $request->call)->where("department_id", $request->department)->count();
            if ($count == 0) {
                CallScript::create([
                    "call_id" => $request->call,
                    "department_id" => $request->department,
                    "extra_filter" => $request->extra,
                    "script" => $request->script,
                ]);
                return redirect()->route("call.scripts.list")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("call.scripts.list")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            return redirect()->route("call.scripts.list")->with("error", $th->getMessage());
        }
    }

    public function callScriptUpdate(Request $request)
    {
        try {
            $callScript = CallScript::find($request->id);
            $callScript->call_id = $request->call;
            $callScript->department_id = $request->department;
            $callScript->extra_filter = $request->extra;
            $callScript->script = $request->script;
            $callScript->save();
            return redirect()->route("call.scripts.list");
        } catch (\Throwable $th) {
            return redirect()->route("call.scripts.list")->with('error', $th->getMessage());
        }
    }

    public function callScriptDelete(Request $request)
    {
        try {
            CallScript::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    /************************************ CALL SCRIPTS ENDS **************************************************************/

    /************************************ EMAIL SCRIPTS STARTS **************************************************************/

    public function emailScriptList(Request $request)
    {
        if ($request->id != "") {
            $script = EmailScript::with("email", "department")->where("id", $request->id)->first();
        }
        return view("operations/email-scripts/index", [
            "emailTypes" => EmailType::all(),
            "departments" => Department::all(),
            "emailScripts" => EmailScript::with("email", "department")->get(),
            "script" => ($request->id != "" ? $script : []),
        ]);
    }

    public function emailScriptStore(Request $request)
    {
        try {
            $count = EmailScript::where("email_type_id", $request->email_type_id)->where("department_id", $request->department)->count();
            if ($count == 0) {
                EmailScript::create([
                    "email_type_id" => $request->email_type_id,
                    "department_id" => $request->department,
                    "extra_filter" => $request->extra,
                    "script" => $request->script,
                ]);
                return redirect()->route("email.scripts.list")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("email.scripts.list")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            return redirect()->route("email.scripts.list")->with("error", $th->getMessage());
        }
    }

    public function emailScriptUpdate(Request $request)
    {
        try {
            $emailScript = EmailScript::find($request->id);
            $emailScript->email_type_id = $request->email_type_id;
            $emailScript->department_id = $request->department;
            $emailScript->extra_filter = $request->extra;
            $emailScript->script = $request->script;
            $emailScript->save();
            return redirect()->route("email.scripts.list");
        } catch (\Throwable $th) {
            return redirect()->route("email.scripts.list")->with('error', $th->getMessage());
        }
    }

    public function emailScriptDelete(Request $request)
    {
        try {
            EmailScript::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    /************************************ EMAIL SCRIPTS ENDS **************************************************************/

    public function loanTermView(Request $request)
    {
        if ($request->id != "") {
            $loanTerm = LoanTerm::where("id", $request->id)->first();
        }
        return view("operations/loan-term/index", [
            "financeOptions" => FinanceOption::all(),
            "loanTerms" => LoanTerm::with('finance')->get(),
            "loanTerm" => ($request->id != "" ? $loanTerm : []),
        ]);
    }

    public function loanTermStore(Request $request)
    {
        try {
            $count = LoanTerm::where("finance_option_id", $request->finance_option_id)->where("year", $request->year)->count();

            if ($count == 0) {
                DB::beginTransaction();
                LoanTerm::create([
                    "finance_option_id" => $request->finance_option_id,
                    "year" => $request->year,
                ]);
                DB::commit();
                return redirect()->route("loan.term")->with("success", "Data Saved Successfully");
            } else {
                DB::rollBack();
                return redirect()->route("loan.term")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {

            return redirect()->route("loan.term")->with("error", $th->getMessage());
        }
    }

    public function loanTermUpdate(Request $request)
    {
        try {
            $loanTerm = LoanTerm::find($request->id);
            $loanTerm->finance_option_id = $request->finance_option_id;
            $loanTerm->year = $request->year;
            $loanTerm->save();
            return redirect()->route("loan.term");
        } catch (\Throwable $th) {
            return redirect()->route("loan.term")->with('error', $th->getMessage());
        }
    }

    public function loanTermDelete(Request $request)
    {
        try {
            DB::beginTransaction();
            LoanTerm::where("id", $request->id)->delete();
            DB::commit();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500]);
        }
    }

    public function utilityCompanyView(Request $request)
    {
        if ($request->id != "") {
            $utility = UtilityCompany::where("id", $request->id)->first();
        }
        return view("operations/utility-company/index", [
            "utilityCompanies" => UtilityCompany::all(),
            "utility" => ($request->id != "" ? $utility : []),
        ]);
    }

    public function utilityCompanyStore(Request $request)
    {
        try {
            $count = UtilityCompany::where("name", $request->name)->count();
            if ($count == 0) {
                UtilityCompany::create([
                    "name" => $request->name,
                ]);
                return redirect()->route("view.utility.types")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("view.utility.types")->with("error", "Data already exists");
            }
        } catch (\Throwable $th) {
            return redirect()->route("view.utility.types")->with("error", $th->getMessage());
        }
    }

    public function utilityCompanyUpdate(Request $request)
    {
        try {
            $adder = UtilityCompany::find($request->id);
            $adder->name = $request->name;
            $adder->save();
            return redirect()->route("view.utility.types");
        } catch (\Throwable $th) {
            return redirect()->route("view.utility.types")->with('error', $th->getMessage());
        }
    }

    public function utilityCompanyDelete(Request $request)
    {
        try {
            UtilityCompany::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }
}
