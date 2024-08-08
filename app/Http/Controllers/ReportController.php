<?php

namespace App\Http\Controllers;

use App\Exports\ForecastReportExport;
use App\Exports\ProfitReportExport;
use App\Models\Customer;
use App\Models\OfficeCost;
use App\Models\User;
use Illuminate\Http\Request;
use Excel;

class ReportController extends Controller
{
    public function profitabilityReport(Request $request)
    {
        return view("reports.profitable.profitable", [
            "partners" => User::filterByRole('Sales Person')->get(),
        ]);
    }

    public function getProfitabilityReport(Request $request)
    {
        $customer = Customer::with("salespartner", "finances", "project")
            ->whereHas("project", function ($query) use ($request) {
                $query->whereBetween("solar_install_date", [$request->from, $request->to]);
            });
        if ($request->sales_partner_id != "") {
            $customer->where("sales_partner_id", $request->sales_partner_id);
        }

        return view("reports.profitable.table", [
            "customers" => $customer->get(),
            "officeCost" => OfficeCost::first(),
        ]);
    }

    public function getProfitableReportExport(Request $request)
    {
        $customer = Customer::with("salespartner", "finances", "project")
            ->whereHas("project", function ($query) use ($request) {
                $query->whereBetween("solar_install_date", [$request->from, $request->to]);
            });
        if ($request->sales_partner_id != "") {
            $customer->where("sales_partner_id", $request->sales_partner_id);
        }
        $customers = $customer->get();
        $officeCost = OfficeCost::first();

        return Excel::download(new ProfitReportExport($customers, $officeCost, $request->from, $request->to), 'Profitable Report.xlsx');
    }

    public function getProfitableReportPdfExport(Request $request)
    {
        $customer = Customer::with("salespartner", "finances", "project")
            ->whereHas("project", function ($query) use ($request) {
                $query->whereBetween("solar_install_date", [$request->from, $request->to]);
            });
        if ($request->sales_partner_id != "") {
            $customer->where("sales_partner_id", $request->sales_partner_id);
        }
        $customers = $customer->get();
        $officeCost = OfficeCost::first();

        $export = new ProfitReportExport($customers, $officeCost, $request->from, $request->to);

        // Export to Excel file first
        $filePath = storage_path('app/public/yourfile.xlsx');
        Excel::store($export, 'public/yourfile.xlsx');

        // Load the Excel file and convert to PDF
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, \Maatwebsite\Excel\Excel::DOMPDF);
        $writer->save(storage_path('app/public/yourfile.pdf'));

        // Return the PDF file as download
        return response()->download(storage_path('app/public/yourfile.pdf'));

        // return Excel::download(new ProfitReportExport($customers, $officeCost, $request->from, $request->to), 'Profitable Report.pdf', \Maatwebsite\Excel\Excel::DOMPDF);
    }

    public function forecastReport()
    {
        return view("reports.forecast.forecast");
    }

    public function getForecastReport(Request $request)
    {
        $customer = Customer::with("salespartner", "finances", "project")->whereBetween("sold_date", [$request->from, $request->to])->orderBy("sold_date", "ASC");

        return view("reports.forecast.forecast_table", [
            "customers" => $customer->get(),
        ]);
    }


    public function getForecastReportExport(Request $request)
    {
        $customer = Customer::with("salespartner", "finances", "project")->whereBetween("sold_date", [$request->from, $request->to])->orderBy("sold_date", "ASC")->get();
        return Excel::download(new ForecastReportExport($customer, $request->from, $request->to), 'Forecast Report.xlsx');
    }

    public function getForecastReportPdfExport(Request $request)
    {
        $customer = Customer::with("salespartner", "finances", "project")->whereBetween("sold_date", [$request->from, $request->to])->orderBy("sold_date", "ASC")->get();
        return Excel::download(new ForecastReportExport($customer, $request->from, $request->to), 'Forecast Report.pdf', \Maatwebsite\Excel\Excel::DOMPDF);
    }
}
