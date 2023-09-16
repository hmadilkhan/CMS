<?php

namespace App\Http\Controllers;

use App\Models\InverterType;
use App\Models\InverterTypeRate;
use App\Models\LoanApr;
use App\Models\LoanTerm;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OperationController extends Controller
{

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
            $inverterTypeRate->panels_qty = $request->panel_qty;
            $inverterTypeRate->redline_cost = $request->redline_cost;
            $inverterTypeRate->save();
            return redirect()->route("view-redline-cost");
        } catch (\Throwable $th) {
            return redirect()->route("view-redline-cost")->with('error', $th->getMessage());
        }
    }


    public function redlineStore(Request $request)
    {
        $validated = $request->validate([
            'panel_qty' => 'required',
            'reason' => 'required_if:status,Cancelled|integer',
        ]);
        try {
            $count = InverterTypeRate::where("inverter_type_id", $request->inverter_type_id)->where("panels_qty", $request->panel_qty)->count();
            if ($count == 0) {
                InverterTypeRate::create([
                    "inverter_type_id" => $request->inverter_type_id,
                    "panels_qty" => $request->panel_qty,
                    "redline_cost" => $request->redline_cost,
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
        // return LoanApr::with("loan","loan.finance")->get();
        return view("operations/dealerfee/index", [
            "dealerfeelist" => LoanApr::with("loan","loan.finance")->get(),
            "terms" => LoanTerm::all(),
            "loan" => ($request->id != "" ? LoanApr::find($request->id) : []),
        ]);
    }

    public function dealerFeeUpdate(Request $request)
    {
        try {
            $loanApr = LoanApr::find($request->id);
            $loanApr->loan_term_id = $request->loan_term_id;
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
            $count = LoanApr::where("loan_term_id", $request->loan_term_id)->where("apr", $request->apr)->where("apr", $request->dealer_fee)->count();
            if ($count == 0) {
                LoanApr::create([
                    "loan_term_id" => $request->loan_term_id,
                    "apr" => $request->apr,
                    "dealer_fee" => $request->dealer_fee,
                ]);
                return redirect()->route("view-dealer-fee")->with("success", "Data Saved Successfully");
            } else {
                return redirect()->route("view-dealer-fee")->with("error", "Data already exists");
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
}
