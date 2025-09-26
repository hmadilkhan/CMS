<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionReportExport implements FromCollection,WithHeadings, WithMapping,ShouldAutoSize
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            'Project Name',
            'Payee',
            'Milestone',
            'Amount',
            'Deduction Amount',
            'Remitted Amount',
            'Transaction Date',
            'Description'
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->project->project_name ?? '',
            $transaction->payee_label ?? '',
            $transaction->milestone ?? '',
            $transaction->formatted_amount,
            $transaction->formatted_deduction_amount,
            $transaction->formatted_remitted_amount,
            $transaction->formatted_transaction_date,
            $transaction->transaction_details
        ];
    }

    public function title(): string
    {
        return 'Transaction Report ';
    }
}
