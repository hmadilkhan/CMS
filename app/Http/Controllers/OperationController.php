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
use App\Models\SalesPartner;
use App\Models\SubContractor;
use App\Models\SubDepartment;
use App\Models\User;
use App\Models\UtilityCompany;
use App\Traits\MediaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $validated = $this->validateRedline($request, $request->id);

        try {
            $inverterTypeRate = InverterTypeRate::findOrFail($validated["id"]);
            $inverterTypeRate->inverter_type_id = $validated["inverter_type_id"];
            $inverterTypeRate->base_cost = $validated["base_cost"];
            $inverterTypeRate->internal_base_cost = $validated["internal_base_cost"];
            $inverterTypeRate->internal_labor_cost = $validated["internal_labor_cost"];
            $inverterTypeRate->save();
            return redirect()->route("view-redline-cost");
        } catch (\Throwable $th) {
            return redirect()->route("view-redline-cost")->with('error', $th->getMessage());
        }
    }


    public function redlineStore(Request $request)
    {
        $validated = $this->validateRedline($request);

        try {
            InverterTypeRate::create([
                "inverter_type_id" => $validated["inverter_type_id"],
                "base_cost" => $validated["base_cost"],
                "internal_base_cost" => $validated["internal_base_cost"],
                "internal_labor_cost" => $validated["internal_labor_cost"],
            ]);
            return redirect()->route("view-redline-cost")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("view-redline-cost")->with("error", $th->getMessage());
        }
    }

    public function redlineDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:inverter_type_rates,id"],
        ]);

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
        $validated = $this->validateDealerFee($request, $request->id);

        try {
            $loanTerm = $this->resolveDealerFeeLoanTerm($validated["loan_term_id"], $validated["finance_option_id"]);
            if (empty($loanTerm)) {
                return redirect()->route("view-dealer-fee")->with("error", "Selected finance option does not have this loan term.");
            }

            if ($this->dealerFeeExists($loanTerm->id, $validated["finance_option_id"], $validated["id"])) {
                return redirect()->route("view-dealer-fee")->with("error", "Loan term already exists. Please update");
            }

            $loanApr = LoanApr::findOrFail($validated["id"]);
            $loanApr->loan_term_id = $loanTerm->id;
            $loanApr->finance_option_id = $validated["finance_option_id"];
            $loanApr->apr = $validated["apr"];
            $loanApr->dealer_fee = $validated["dealer_fee"];
            $loanApr->save();
            return redirect()->route("view-dealer-fee");
        } catch (\Throwable $th) {
            return redirect()->route("view-dealer-fee")->with('error', $th->getMessage());
        }
    }

    public function dealerFeeStore(Request $request)
    {
        $validated = $this->validateDealerFee($request);

        try {
            $loanTerm = $this->resolveDealerFeeLoanTerm($validated["loan_term_id"], $validated["finance_option_id"]);
            if (empty($loanTerm)) {
                return redirect()->route("view-dealer-fee")->with("error", "Selected finance option does not have this loan term.");
            }

            if ($this->dealerFeeExists($loanTerm->id, $validated["finance_option_id"])) {
                return redirect()->route("view-dealer-fee")->with("error", "Loan term already exists. Please update");
            }

            LoanApr::create([
                "loan_term_id" => $loanTerm->id,
                "finance_option_id" => $validated["finance_option_id"],
                "apr" => $validated["apr"],
                "dealer_fee" => $validated["dealer_fee"],
            ]);
            return redirect()->route("view-dealer-fee")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("view-dealer-fee")->with("error", $th->getMessage());
        }
    }

    public function dealerFeeDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:loan_aprs,id"],
        ]);

        try {
            LoanApr::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function getFinanceOption(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:loan_terms,id"],
        ]);

        $year = LoanTerm::findOrFail($validated["id"]);
        $finances = FinanceOption::whereIn("id", LoanTerm::where("year", $year->year)->pluck("finance_option_id"))->get();
        return response()->json(["status" => 200, "finances" => $finances]);
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
        $validated = $this->validateAdder($request);

        try {
            $count = Adder::where("adder_type_id", $validated["adder_type_id"])
                ->where("adder_unit_id", $validated["adder_unit_id"])
                ->where("price", $validated["price"])
                ->count();
            if ($count == 0) {
                Adder::create([
                    "adder_type_id" => $validated["adder_type_id"],
                    "adder_unit_id" => $validated["adder_unit_id"],
                    "price" => $validated["price"],
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
        $validated = $this->validateAdder($request, $request->id);

        try {
            $count = Adder::where("adder_type_id", $validated["adder_type_id"])
                ->where("adder_unit_id", $validated["adder_unit_id"])
                ->where("price", $validated["price"])
                ->where("id", "!=", $validated["id"])
                ->count();
            if ($count > 0) {
                return redirect()->route("view-adders")->with("error", "Data already exists");
            }

            $adder = Adder::findOrFail($validated["id"]);
            $adder->adder_type_id = $validated["adder_type_id"];
            $adder->adder_unit_id = $validated["adder_unit_id"];
            $adder->price = $validated["price"];
            $adder->save();
            return redirect()->route("view-adders");
        } catch (\Throwable $th) {
            return redirect()->route("view-adders")->with('error', $th->getMessage());
        }
    }

    public function addersDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:adders,id"],
        ]);

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
        $validated = $this->validateFinanceOption($request);

        try {
            DB::transaction(function () use ($validated) {
                $finance = FinanceOption::create([
                    "name" => $validated["name"],
                    "loan_id" => $validated["loan_id"],
                    "production_requirements" => $validated["production_requirements"],
                    "positive_variance" => ($validated["production_requirements"] == 0 ? 0 : $validated["positive_variance"]),
                    "negative_variance" => ($validated["production_requirements"] == 0 ? 0 : $validated["negative_variance"]),
                    "dealer_fee" => $validated["dealer_fee"],
                    "pto_restriction" => $validated["pto_restriction"],
                    "no_of_days" => ($validated["pto_restriction"] == 0 ? 0 : $validated["no_of_days"]),
                    "holdback" => $validated["holdback"],
                    "dollar_watt_value" => ($validated["holdback"] == 0 ? 0 : $validated["dollar_watt_value"]),
                ]);
                LoanTerm::create([
                    "finance_option_id" => $finance->id,
                    "year" => '10 Years',
                ]);
                LoanTerm::create([
                    "finance_option_id" => $finance->id,
                    "year" => '25 Years',
                ]);
            });

            return redirect()->route("finance.option.types")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("finance.option.types")->with("error", $th->getMessage());
        }
    }

    public function financeOptionUpdate(Request $request)
    {
        $validated = $this->validateFinanceOption($request, $request->id);

        try {
            $adder = FinanceOption::findOrFail($validated["id"]);
            $adder->name = $validated["name"];
            $adder->loan_id = $validated["loan_id"];
            $adder->production_requirements = $validated["production_requirements"];
            $adder->positive_variance = ($validated["production_requirements"] == 0 ? 0 : $validated["positive_variance"]);
            $adder->negative_variance = ($validated["production_requirements"] == 0 ? 0 : $validated["negative_variance"]);
            $adder->dealer_fee = $validated["dealer_fee"];
            $adder->pto_restriction = $validated["pto_restriction"];
            $adder->no_of_days = ($validated["pto_restriction"] == 0 ? 0 : $validated["no_of_days"]);
            $adder->holdback = $validated["holdback"];
            $adder->dollar_watt_value = ($validated["holdback"] == 0 ? 0 : $validated["dollar_watt_value"]);
            $adder->save();
            return redirect()->route("finance.option.types");
        } catch (\Throwable $th) {
            return redirect()->route("finance.option.types")->with('error', $th->getMessage());
        }
    }

    public function financeOptionDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:finance_options,id"],
        ]);

        try {
            DB::beginTransaction();
            LoanApr::where("finance_option_id", $request->id)->delete();
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
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", Rule::unique("adder_types", "name")->whereNull("deleted_at")],
            "tag" => ["nullable", "string", "max:255"],
        ]);

        try {
            AdderType::create([
                "name" => $validated["name"],
                "tag" => $validated["tag"] ?? null,
            ]);
            return redirect()->route("view.adder.types")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("view.adder.types")->with("error", $th->getMessage());
        }
    }

    public function addersTypeUpdate(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:adder_types,id"],
            "name" => ["required", "string", "max:255", Rule::unique("adder_types", "name")->ignore($request->id)->whereNull("deleted_at")],
            "tag" => ["nullable", "string", "max:255"],
        ]);

        try {
            $adder = AdderType::findOrFail($validated["id"]);
            $adder->name = $validated["name"];
            $adder->tag = $validated["tag"] ?? null;
            $adder->save();
            return redirect()->route("view.adder.types");
        } catch (\Throwable $th) {
            return redirect()->route("view.adder.types")->with('error', $th->getMessage());
        }
    }

    public function addersTypeDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:adder_types,id"],
        ]);

        try {
            DB::beginTransaction();
            AdderType::where("id", $request->id)->delete();
            Adder::where("adder_type_id", $request->id)->delete();
            DB::commit();
            return response()->json(["status" => 200, "message" => "Adder Type deleted successfully"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "message" => $th->getMessage()]);
        }
    }

    public function getSubTypes(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:adder_types,id"],
        ]);

        $subtypes = AdderSubType::where("adder_type_id", $validated["id"])->get();
        return response()->json(["status" => 200, "subtypes" => $subtypes]);
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
        $validated = $this->validatePartner($request, SalesPartner::class);

        try {
            $result = $this->uploads($request->file, 'salespartners/', "");
            SalesPartner::create([
                "name" => $validated["name"],
                'image' => (!empty($result) ? $result["fileName"] : ""),
                "email" => $validated["email"] ?? null,
                "phone" => $validated["phone"] ?? null,
            ]);
            return redirect()->route("sales.partner.types")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("sales.partner.types")->with("error", $th->getMessage());
        }
    }

    public function salesPartnerUpdate(Request $request)
    {
        $validated = $this->validatePartner($request, SalesPartner::class, $request->id);

        try {
            $result = $this->uploads($request->file, 'salespartners/', $request->previous_logo);
            $salesPartner = SalesPartner::findOrFail($validated["id"]);
            $salesPartner->name = $validated["name"];
            $salesPartner->email = $validated["email"] ?? null;
            $salesPartner->phone = $validated["phone"] ?? null;
            $salesPartner->image = (!empty($result) ? $result["fileName"] : ($validated["previous_logo"] ?? ""));
            $salesPartner->save();
            return redirect()->route("sales.partner.types");
        } catch (\Throwable $th) {
            return redirect()->route("sales.partner.types")->with('error', $th->getMessage());
        }
    }

    public function salesPartnerDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:sales_partners,id"],
        ]);

        try {
            SalesPartner::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function salesPartnerOverwriteCost(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:users,id"],
        ]);

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

    public function callTypeList(Request $request)
    {
        if ($request->id != "") {
            $callType = Call::where("id", $request->id)->first();
        }

        return view("operations/call-types/index", [
            "callTypes" => Call::all(),
            "callType" => ($request->id != "" ? $callType : []),
        ]);
    }

    public function callTypeStore(Request $request)
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", Rule::unique("calls", "name")->whereNull("deleted_at")],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            Call::create([
                "name" => $validated["name"],
            ]);

            return redirect()->route("call.types.list")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("call.types.list")->with("error", $th->getMessage());
        }
    }

    public function callTypeUpdate(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:calls,id"],
            "name" => ["required", "string", "max:255", Rule::unique("calls", "name")->ignore($request->id)->whereNull("deleted_at")],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            $callType = Call::findOrFail($validated["id"]);
            $callType->name = $validated["name"];
            $callType->save();

            return redirect()->route("call.types.list")->with("success", "Data Updated Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("call.types.list")->with("error", $th->getMessage());
        }
    }

    public function callTypeDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:calls,id"],
        ]);

        try {
            Call::where("id", $request->id)->delete();
            return response()->json(["status" => 200, "message" => "Call Type deleted successfully"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => $th->getMessage()]);
        }
    }

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
        $validated = $this->validateCallScript($request);

        try {
            $count = CallScript::where("call_id", $validated["call"])
                ->where("department_id", $validated["department"])
                ->count();
            if ($count == 0) {
                CallScript::create([
                    "call_id" => $validated["call"],
                    "department_id" => $validated["department"],
                    "extra_filter" => $validated["extra"] ?? null,
                    "script" => $validated["script"],
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
        $validated = $this->validateCallScript($request, $request->id);

        try {
            $count = CallScript::where("call_id", $validated["call"])
                ->where("department_id", $validated["department"])
                ->where("id", "!=", $validated["id"])
                ->count();
            if ($count > 0) {
                return redirect()->route("call.scripts.list")->with("error", "Data already exists");
            }

            $callScript = CallScript::findOrFail($validated["id"]);
            $callScript->call_id = $validated["call"];
            $callScript->department_id = $validated["department"];
            $callScript->extra_filter = $validated["extra"] ?? null;
            $callScript->script = $validated["script"];
            $callScript->save();
            return redirect()->route("call.scripts.list");
        } catch (\Throwable $th) {
            return redirect()->route("call.scripts.list")->with('error', $th->getMessage());
        }
    }

    public function callScriptDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:call_scripts,id"],
        ]);

        try {
            CallScript::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    /************************************ CALL SCRIPTS ENDS **************************************************************/

    /************************************ EMAIL SCRIPTS STARTS **************************************************************/

    public function emailTypeList(Request $request)
    {
        if ($request->id != "") {
            $emailType = EmailType::where("id", $request->id)->first();
        }

        return view("operations/email-types/index", [
            "emailTypes" => EmailType::all(),
            "emailType" => ($request->id != "" ? $emailType : []),
        ]);
    }

    public function emailTypeStore(Request $request)
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", Rule::unique("email_types", "name")],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            EmailType::create([
                "name" => $validated["name"],
            ]);

            return redirect()->route("email.types.list")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("email.types.list")->with("error", $th->getMessage());
        }
    }

    public function emailTypeUpdate(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:email_types,id"],
            "name" => ["required", "string", "max:255", Rule::unique("email_types", "name")->ignore($request->id)],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            $emailType = EmailType::findOrFail($validated["id"]);
            $emailType->name = $validated["name"];
            $emailType->save();

            return redirect()->route("email.types.list")->with("success", "Data Updated Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("email.types.list")->with("error", $th->getMessage());
        }
    }

    public function emailTypeDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:email_types,id"],
        ]);

        try {
            EmailType::where("id", $request->id)->delete();
            return response()->json(["status" => 200, "message" => "Email Type deleted successfully"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => $th->getMessage()]);
        }
    }

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
        $validated = $this->validateEmailScript($request);

        try {
            $count = EmailScript::where("email_type_id", $validated["email_type_id"])
                ->where("department_id", $validated["department"])
                ->count();
            if ($count == 0) {
                EmailScript::create([
                    "email_type_id" => $validated["email_type_id"],
                    "department_id" => $validated["department"],
                    "extra_filter" => $validated["extra"] ?? null,
                    "script" => $validated["script"],
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
        $validated = $this->validateEmailScript($request, $request->id);

        try {
            $count = EmailScript::where("email_type_id", $validated["email_type_id"])
                ->where("department_id", $validated["department"])
                ->where("id", "!=", $validated["id"])
                ->count();
            if ($count > 0) {
                return redirect()->route("email.scripts.list")->with("error", "Data already exists");
            }

            $emailScript = EmailScript::findOrFail($validated["id"]);
            $emailScript->email_type_id = $validated["email_type_id"];
            $emailScript->department_id = $validated["department"];
            $emailScript->extra_filter = $validated["extra"] ?? null;
            $emailScript->script = $validated["script"];
            $emailScript->save();
            return redirect()->route("email.scripts.list");
        } catch (\Throwable $th) {
            return redirect()->route("email.scripts.list")->with('error', $th->getMessage());
        }
    }

    public function emailScriptDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:email_scripts,id"],
        ]);

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
        $validated = $this->validateLoanTerm($request);

        try {
            LoanTerm::create([
                "finance_option_id" => $validated["finance_option_id"],
                "year" => $validated["year"],
            ]);
            return redirect()->route("loan.term")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {

            return redirect()->route("loan.term")->with("error", $th->getMessage());
        }
    }

    public function loanTermUpdate(Request $request)
    {
        $validated = $this->validateLoanTerm($request, $request->id);

        try {
            $loanTerm = LoanTerm::findOrFail($validated["id"]);
            $loanTerm->finance_option_id = $validated["finance_option_id"];
            $loanTerm->year = $validated["year"];
            $loanTerm->save();
            return redirect()->route("loan.term");
        } catch (\Throwable $th) {
            return redirect()->route("loan.term")->with('error', $th->getMessage());
        }
    }

    public function loanTermDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:loan_terms,id"],
        ]);

        try {
            DB::beginTransaction();
            LoanApr::where("loan_term_id", $request->id)->delete();
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
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", Rule::unique("utility_companies", "name")->whereNull("deleted_at")],
        ]);

        try {
            UtilityCompany::create([
                "name" => $validated["name"],
            ]);
            return redirect()->route("view.utility.types")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("view.utility.types")->with("error", $th->getMessage());
        }
    }

    public function utilityCompanyUpdate(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:utility_companies,id"],
            "name" => ["required", "string", "max:255", Rule::unique("utility_companies", "name")->ignore($request->id)->whereNull("deleted_at")],
        ]);

        try {
            $adder = UtilityCompany::findOrFail($validated["id"]);
            $adder->name = $validated["name"];
            $adder->save();
            return redirect()->route("view.utility.types");
        } catch (\Throwable $th) {
            return redirect()->route("view.utility.types")->with('error', $th->getMessage());
        }
    }

    public function utilityCompanyDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:utility_companies,id"],
        ]);

        try {
            UtilityCompany::where("id", $request->id)->delete();
            return response()->json(["status" => 200]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500]);
        }
    }

    public function departmentList(Request $request)
    {
        if ($request->id != "") {
            $department = Department::where("id", $request->id)->first();
        }

        return view("operations/departments/index", [
            "departments" => Department::all(),
            "department" => ($request->id != "" ? $department : []),
        ]);
    }

    public function departmentStore(Request $request)
    {
        $validated = $request->validate([
            "name" => ["required", "string", "max:255", Rule::unique("departments", "name")->whereNull("deleted_at")],
            "document_length" => ["required", "integer", "min:0"],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            Department::create([
                "name" => $validated["name"],
                "document_length" => $validated["document_length"],
            ]);

            return redirect()->route("departments.list")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("departments.list")->with("error", $th->getMessage());
        }
    }

    public function departmentUpdate(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:departments,id"],
            "name" => ["required", "string", "max:255", Rule::unique("departments", "name")->ignore($request->id)->whereNull("deleted_at")],
            "document_length" => ["required", "integer", "min:0"],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            $department = Department::findOrFail($validated["id"]);
            $department->name = $validated["name"];
            $department->document_length = $validated["document_length"];
            $department->save();

            return redirect()->route("departments.list")->with("success", "Data Updated Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("departments.list")->with("error", $th->getMessage());
        }
    }

    public function departmentDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:departments,id"],
        ]);

        try {
            DB::beginTransaction();
            SubDepartment::where("department_id", $request->id)->delete();
            Department::where("id", $request->id)->delete();
            DB::commit();

            return response()->json(["status" => 200, "message" => "Department deleted successfully"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "message" => $th->getMessage()]);
        }
    }

    public function subDepartmentList(Request $request)
    {
        if ($request->id != "") {
            $subDepartment = SubDepartment::with("department")->where("id", $request->id)->first();
        }

        return view("operations/sub-departments/index", [
            "departments" => Department::all(),
            "subDepartments" => SubDepartment::with("department")->orderBy("department_id")->orderBy("order")->get(),
            "subDepartment" => ($request->id != "" ? $subDepartment : []),
        ]);
    }

    public function subDepartmentStore(Request $request)
    {
        $validated = $request->validate([
            "department_id" => ["required", "exists:departments,id"],
            "name" => ["required", "string", "max:255", Rule::unique("sub_departments", "name")->where("department_id", $request->department_id)->whereNull("deleted_at")],
            "order" => ["required", "integer", "min:0"],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            SubDepartment::create([
                "department_id" => $validated["department_id"],
                "name" => $validated["name"],
                "order" => $validated["order"],
            ]);

            return redirect()->route("sub.departments.list")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("sub.departments.list")->with("error", $th->getMessage());
        }
    }

    public function subDepartmentUpdate(Request $request)
    {
        $validated = $request->validate([
            "id" => ["required", "exists:sub_departments,id"],
            "department_id" => ["required", "exists:departments,id"],
            "name" => ["required", "string", "max:255", Rule::unique("sub_departments", "name")->ignore($request->id)->where("department_id", $request->department_id)->whereNull("deleted_at")],
            "order" => ["required", "integer", "min:0"],
        ], [
            "name.unique" => "The record already exists.",
        ]);

        try {
            $subDepartment = SubDepartment::findOrFail($validated["id"]);
            $subDepartment->department_id = $validated["department_id"];
            $subDepartment->name = $validated["name"];
            $subDepartment->order = $validated["order"];
            $subDepartment->save();

            return redirect()->route("sub.departments.list")->with("success", "Data Updated Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("sub.departments.list")->with("error", $th->getMessage());
        }
    }

    public function subDepartmentDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:sub_departments,id"],
        ]);

        try {
            SubDepartment::where("id", $request->id)->delete();
            return response()->json(["status" => 200, "message" => "Sub Department deleted successfully"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => $th->getMessage()]);
        }
    }

    public function subContractorView(Request $request)
    {
        if ($request->id != "") {
            $contractor = SubContractor::where("id", $request->id)->first();
        }
        return view("operations/sub-contractor/index", [
            "contractors" => SubContractor::all(),
            "contractor" => ($request->id != "" ? $contractor : []),
        ]);
    }

    public function subContractorStore(Request $request)
    {
        $validated = $this->validatePartner($request, SubContractor::class);

        try {
            $result = $this->uploads($request->file, 'subcontractors/', "");
            SubContractor::create([
                "name" => $validated["name"],
                'image' => (!empty($result) ? $result["fileName"] : ""),
                "email" => $validated["email"] ?? null,
                "phone" => $validated["phone"] ?? null,
            ]);
            return redirect()->route("sub.contractor")->with("success", "Data Saved Successfully");
        } catch (\Throwable $th) {
            return redirect()->route("sub.contractor")->with("error", $th->getMessage());
        }
    }

    public function subContractorUpdate(Request $request)
    {
        $validated = $this->validatePartner($request, SubContractor::class, $request->id);

        try {
            $result = $this->uploads($request->file, 'subcontractors/', $request->previous_logo);
            $subContractor = SubContractor::findOrFail($validated["id"]);
            $subContractor->name = $validated["name"];
            $subContractor->email = $validated["email"] ?? null;
            $subContractor->phone = $validated["phone"] ?? null;
            $subContractor->image = (!empty($result) ? $result["fileName"] : ($validated["previous_logo"] ?? ""));
            $subContractor->save();
            return redirect()->route("sub.contractor");
        } catch (\Throwable $th) {
            return redirect()->route("sub.contractor")->with('error', $th->getMessage());
        }
    }

    public function subContractorDelete(Request $request)
    {
        $request->validate([
            "id" => ["required", "exists:sub_contractors,id"],
        ]);

        try {
            SubContractor::where("id", $request->id)->delete();
            return response()->json(["status" => 200, "message" => "Sub Contractor deleted successfully"]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => $th->getMessage()]);
        }
    }

    private function validateRedline(Request $request, $ignoreId = null): array
    {
        $uniqueInverterType = Rule::unique("inverter_type_rates", "inverter_type_id")->whereNull("deleted_at");
        if (!empty($ignoreId)) {
            $uniqueInverterType->ignore($ignoreId);
        }

        $rules = [
            "inverter_type_id" => [
                "required",
                "exists:inverter_types,id",
                $uniqueInverterType,
            ],
            "base_cost" => ["required", "numeric", "min:0"],
            "internal_base_cost" => ["required", "numeric", "min:0"],
            "internal_labor_cost" => ["required", "numeric", "min:0"],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:inverter_type_rates,id"];
        }

        return $request->validate($rules);
    }

    private function validateDealerFee(Request $request, $ignoreId = null): array
    {
        $rules = [
            "loan_term_id" => ["required", "exists:loan_terms,id"],
            "finance_option_id" => ["required", "exists:finance_options,id"],
            "apr" => ["required", "numeric", "min:0"],
            "dealer_fee" => ["required", "numeric", "min:0"],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:loan_aprs,id"];
        }

        return $request->validate($rules);
    }

    private function resolveDealerFeeLoanTerm($loanTermId, $financeOptionId): ?LoanTerm
    {
        $selectedTerm = LoanTerm::find($loanTermId);
        if (empty($selectedTerm)) {
            return null;
        }

        return LoanTerm::where("finance_option_id", $financeOptionId)
            ->where("year", $selectedTerm->year)
            ->first();
    }

    private function dealerFeeExists($loanTermId, $financeOptionId, $ignoreId = null): bool
    {
        return LoanApr::where("loan_term_id", $loanTermId)
            ->where("finance_option_id", $financeOptionId)
            ->when($ignoreId, function ($query) use ($ignoreId) {
                $query->where("id", "!=", $ignoreId);
            })
            ->exists();
    }

    private function validateAdder(Request $request, $ignoreId = null): array
    {
        $rules = [
            "adder_type_id" => ["required", "exists:adder_types,id"],
            "adder_unit_id" => ["required", "exists:adder_units,id"],
            "price" => ["required", "numeric", "min:0"],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:adders,id"];
        }

        return $request->validate($rules);
    }

    private function validateFinanceOption(Request $request, $ignoreId = null): array
    {
        $uniqueName = Rule::unique("finance_options", "name")->whereNull("deleted_at");
        if (!empty($ignoreId)) {
            $uniqueName->ignore($ignoreId);
        }

        $rules = [
            "name" => ["required", "string", "max:255", $uniqueName],
            "loan_id" => ["required", Rule::in([0, 1])],
            "production_requirements" => ["required", Rule::in([0, 1])],
            "positive_variance" => ["required_if:production_requirements,1", "nullable", "numeric", "min:0"],
            "negative_variance" => ["required_if:production_requirements,1", "nullable", "numeric", "min:0"],
            "dealer_fee" => ["required", Rule::in([0, 1])],
            "pto_restriction" => ["required", Rule::in([0, 1])],
            "no_of_days" => ["required_if:pto_restriction,1", "nullable", "integer", "min:0"],
            "holdback" => ["required", Rule::in([0, 1])],
            "dollar_watt_value" => ["required_if:holdback,1", "nullable", "numeric", "min:0"],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:finance_options,id"];
        }

        return $request->validate($rules);
    }

    private function validatePartner(Request $request, string $modelClass, $ignoreId = null): array
    {
        $model = new $modelClass;
        $uniqueName = Rule::unique($model->getTable(), "name")->whereNull("deleted_at");
        if (!empty($ignoreId)) {
            $uniqueName->ignore($ignoreId);
        }

        $rules = [
            "name" => ["required", "string", "max:255", $uniqueName],
            "email" => ["nullable", "email", "max:255"],
            "phone" => ["nullable", "string", "max:50"],
            "file" => ["nullable", "image", "max:2048"],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:" . $model->getTable() . ",id"];
            $rules["previous_logo"] = ["nullable", "string", "max:255"];
        }

        return $request->validate($rules);
    }

    private function validateCallScript(Request $request, $ignoreId = null): array
    {
        $rules = [
            "call" => ["required", "exists:calls,id"],
            "department" => ["required", "exists:departments,id"],
            "extra" => ["nullable", "string", "max:255"],
            "script" => ["required", "string"],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:call_scripts,id"];
        }

        return $request->validate($rules);
    }

    private function validateEmailScript(Request $request, $ignoreId = null): array
    {
        $rules = [
            "email_type_id" => ["required", "exists:email_types,id"],
            "department" => ["required", "exists:departments,id"],
            "extra" => ["nullable", "string", "max:255"],
            "script" => ["required", "string"],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:email_scripts,id"];
        }

        return $request->validate($rules);
    }

    private function validateLoanTerm(Request $request, $ignoreId = null): array
    {
        $uniqueFinanceOption = Rule::unique("loan_terms", "finance_option_id")
            ->whereNull("deleted_at");
        if (!empty($ignoreId)) {
            $uniqueFinanceOption->ignore($ignoreId);
        }

        $rules = [
            "finance_option_id" => ["required", "exists:finance_options,id", $uniqueFinanceOption],
            "year" => [
                "required",
                "string",
                "max:255",
            ],
        ];

        if (!empty($ignoreId)) {
            $rules["id"] = ["required", "exists:loan_terms,id"];
        }

        return $request->validate($rules, [
            "finance_option_id.unique" => "The record already exists.",
        ]);
    }
}
