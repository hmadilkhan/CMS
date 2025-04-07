<?php

namespace App\Http\Controllers;

use App\Models\Adder;
use App\Models\AdderType;
use App\Models\CustomerAdder;
use App\Models\CustomerFinance;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $project = Project::where("customer_id",$request->customer_id)->first();
            $username = auth()->user()->name;
            $exist  = CustomerAdder::where(["customer_id" => $request->customer_id, "adder_type_id" => $request->adder_type_id])->count();
            if ($exist == 0) {
                CustomerAdder::create([
                    "customer_id" => $request->customer_id,
                    "adder_type_id" => $request->adder_type_id,
                    "adder_unit_id" => $request->adder_unit_id,
                    "amount" => $request->amount,
                ]);
                $adder = AdderType::where("id",$request->adder_type_id)->first();
                activity('project')
                    ->performedOn($project)
                    ->causedBy(auth()->user()) // Log who did the action
                    ->setEvent("move")
                    ->log("{$username} created the ".$adder->name." of amount $".$request->amount." . ");
                $finance = CustomerFinance::where("customer_id", $request->customer_id)->first();
                $addersAmount = CustomerAdder::where("customer_id", $request->customer_id)->sum("amount");
                $commission = $finance->contract_amount - $finance->dealer_fee_amount -  $finance->redline_costs - $addersAmount;
                CustomerFinance::where("customer_id", $request->customer_id)->update([
                    "adders" => $addersAmount,
                    "commission" => $commission,
                ]);
                $addersList = CustomerAdder::with("type","unit")->where("customer_id", $request->customer_id)->get();
            }
            DB::commit();
            return response()->json(["status" => 200, "message" => "Adder Added","adders" => $addersList]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "message" => "Errors : ".$th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Adder $adder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Adder $adder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Adder $adder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        DB::beginTransaction();
        try {
            $project = Project::where("customer_id",$request->customer_id)->first();
            $username = auth()->user()->name;
            $adder = CustomerAdder::with('type')->where("id", $request->id)->first();
            // CustomerAdder::where("id", $request->id)->delete();
            $adder->delete();
            $finance = CustomerFinance::where("customer_id", $request->customer_id)->first();
            $addersAmount = CustomerAdder::where("customer_id", $request->customer_id)->sum("amount");
            $commission = $finance->contract_amount - $finance->dealer_fee_amount -  $finance->redline_costs - $addersAmount;
            CustomerFinance::where("customer_id", $request->customer_id)->update([
                "adders" => $addersAmount,
                "commission" => $commission,
            ]);
            $addersList = CustomerAdder::with("type","unit")->where("customer_id", $request->customer_id)->get();
            activity('project')
                    ->performedOn($project)
                    ->causedBy(auth()->user()) // Log who did the action
                    ->setEvent("move")
                    ->log("{$username} deleted the adder : ".$adder->type->name." of amount $".$adder->amount." . ");
            DB::commit();
            return response()->json(["status" => 200, "message" => "Adder Deleted","adders" => $addersList]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(["status" => 500, "message" => "Error in deleting"]);
        }
    }
}
