<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OfficeCost;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function profitabilityReport(Request $request)
    {
        return view("reports.profitable",[
            "partners" => User::filterByRole('Sales Person')->get(),
        ]);
    }

    public function getProfitabilityReport(Request $request)
    {
        $customer = Customer::with("salespartner","finances","project")->whereBetween("solar_install_date",[$request->from,$request->to]);
        if ($request->sales_partner_id != "") {
            $customer->where("sales_partner_id",$request->sales_partner_id);
        }
        
        return view("reports.table",[
            "customers" => $customer->get(),
            "officeCost" => OfficeCost::first(),
        ]);
    }


}
