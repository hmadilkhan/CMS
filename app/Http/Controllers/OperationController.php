<?php

namespace App\Http\Controllers;

use App\Models\Adder;
use App\Models\AdderSubType;
use App\Models\AdderType;
use App\Models\AdderUnit;
use App\Models\FinanceOption;
use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\LoanApr;
use App\Models\LoanTerm;
use App\Models\ModuleType;
use App\Models\SalesPartner;
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
            "terms" => LoanTerm::groupBy("year")->orderBy("id","asc")->get(),
            // "financing" => ($request->id != "" ? FinanceOption::whereIn("id", LoanTerm::where("year", $loan->loan->year)->pluck("finance_option_id"))->get() : [] ),
            "financing" => FinanceOption::all(),
            "loan" => ($request->id != "" ? $loan : []),
        ]);
    }

    public function dealerFeeUpdate(Request $request)
    {
        try {
            $year = LoanTerm::where("id",$request->loan_term_id)->first();
            $loan = LoanTerm::where("finance_option_id",$request->finance_option_id)->where("year",$year->year)->first();
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
            LoanTerm::where("finance_option_id",$request->id)->delete();
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
            $subtypes = AdderSubType::where("adder_type_id",$request->id)->get();
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
                $result = $this->uploads($request->file, 'salespartners/',"");
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
}
