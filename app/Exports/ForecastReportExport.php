<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class ForecastReportExport implements FromView, WithColumnWidths, ShouldAutoSize
{
    protected $queryRecord;
    protected $from;
    protected $to;

    public function __construct(object $queryRecord,string $from,string $to)
    {
        $this->queryRecord = $queryRecord;
        $this->from = $from;
        $this->to = $to;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 12,
            'C' => 15,
            'D' => 20,
            'E' => 18,
            'F' => 10,
            'G' => 10,
            'H' => 10,
        ];
    }

    public function view(): View
    {
        $customers = $this->queryRecord;
        $from = $this->from;
        $to = $this->to;
        return view("reports.forecast.forecast_export_table", compact("customers","from","to"));
    }

    public function title(): string
    {
        return 'Forecast Report ';
    }
}
