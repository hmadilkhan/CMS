<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Project;
use App\Models\ProjectInvoiceDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Raises the sub-contractor's Labor Payable in Account Transactions the moment a
 * project's Inspection Approval Date is entered - one transaction per Labor
 * invoice, the way Finance used to key them in by hand:
 *
 *   Sub-Contractor | Full Payment - Invoice #2207 | -6,020.00 | Labor Payable - <name>
 *
 * The amount is the invoice amount, negative (money owed out). The date is the
 * inspection approval date. Nothing is created without a sub-contractor on the
 * customer or without a Labor invoice, and an invoice never gets a second one.
 */
class SubContractorPayableService
{
    /**
     * @return int how many transactions were created
     */
    public function createForInspectionApproval(Project $project): int
    {
        try {
            if (empty($project->inspection_approval_date)) {
                return 0;
            }

            $project->loadMissing('customer.subContractor');
            $subContractor = $project->customer?->subContractor;

            if (! $subContractor) {
                Log::info('Labor Payable not created: the project has no sub-contractor.', [
                    'project_id' => $project->id,
                ]);

                return 0;
            }

            $created = 0;
            $invoices = $project->invoiceDetails()->where('invoice_type', 'labor')->orderBy('id')->get();

            foreach ($invoices as $invoice) {
                if ($this->createForInvoice($project, $invoice, $subContractor->name)) {
                    $created++;
                }
            }

            return $created;
        } catch (\Throwable $throwable) {
            // The date has already been saved; a failure here must not undo that.
            Log::error('Labor Payable creation failed', [
                'project_id' => $project->id ?? null,
                'error' => $throwable->getMessage(),
            ]);

            return 0;
        }
    }

    private function createForInvoice(Project $project, ProjectInvoiceDetail $invoice, string $subContractorName): bool
    {
        $exists = fn () => AccountTransaction::withTrashed()
            ->where('project_invoice_detail_id', $invoice->id)
            ->exists();

        if ($exists()) {
            return false;
        }

        return DB::transaction(function () use ($project, $invoice, $subContractorName, $exists) {
            if ($exists()) {
                return false;
            }

            $number = $this->invoiceNumber($invoice);

            $items = [
                'project_id' => $project->id,
                'project_invoice_detail_id' => $invoice->id,
                'payee' => 'sub_contractor',
                'milestone' => 'Full Payment - Invoice'.($number ? ' #'.$number : ''),
                'amount' => -abs((float) $invoice->amount),
                'deduction_amount' => 0,
                'transaction_date' => Carbon::parse($project->inspection_approval_date)->toDateString(),
                'transaction_details' => 'Labor Payable - '.$subContractorName,
                'status' => AccountTransaction::STATUS_OPEN,
            ];

            $transaction = AccountTransaction::create($items);

            $activity = activity('AccountTransaction')
                ->performedOn($transaction)
                ->withProperties($items)
                ->setEvent('created');

            if (auth()->check()) {
                $activity->causedBy(auth()->user());
            }

            $activity->log(($this->actor()).' created the account transaction for the inspection approval: Labor Payable.');

            return true;
        });
    }

    /**
     * The sub-contractor's own invoice number. The invoice record has no number
     * field, so it is read from the uploaded file's name, which is how the
     * sub-contractor numbers it ("INVOICE_2207_from_...pdf" -> 2207).
     */
    public function invoiceNumber(ProjectInvoiceDetail $invoice): ?string
    {
        $name = (string) ($invoice->original_file_name ?: $invoice->file_name);

        return preg_match('/invoice[\s_#\-]*(\d+)/i', $name, $match) ? $match[1] : null;
    }

    private function actor(): string
    {
        return auth()->check() ? auth()->user()->name : 'System';
    }
}
