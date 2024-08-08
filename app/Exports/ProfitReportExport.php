<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ProfitReportExport implements FromView, WithColumnWidths, ShouldAutoSize
{
    protected $queryRecord;
    protected $officeCost;
    protected $from;
    protected $to;

    public function __construct(object $queryRecord, object $officeCost, string $from, string $to)
    {
        $this->queryRecord = $queryRecord;
        $this->officeCost = $officeCost;
        $this->from = $from;
        $this->to = $to;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 8,
            'C' => 8,
            'D' => 8,
            'E' => 8,
            'F' => 8,
            'G' => 8,
            'H' => 8,
            'I' => 8,
            'J' => 8,
            'K' => 8,
            'L' => 8,
        ];
    }

    // public static function beforeExport(BeforeExport $event)
    // {
    //     $event->writer->getDelegate()->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    // }

    // public function registerEvents(): array
    // {
    //     return [
    //         BeforeExport::class => [self::class, 'beforeExport'],
    //         AfterSheet::class => function (AfterSheet $event) {
    //             $event->sheet->getDelegate()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    //         },
    //     ];
    // }

    public function view(): View
    {
        $customers = $this->queryRecord;
        $officeCost = $this->officeCost;
        $from = $this->from;
        $to = $this->to;
        return view("reports.profitable.export_table", compact("customers", "officeCost", "from", "to"));
    }

    public function title(): string
    {
        return 'Profitable Report ';
    }
}
