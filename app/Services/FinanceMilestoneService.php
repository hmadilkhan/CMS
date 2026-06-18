<?php

namespace App\Services;

use App\Jobs\SendHtmlEmailJob;
use App\Models\AccountTransaction;
use App\Models\FinanceMilestoneEmailRecipient;
use App\Models\FinanceMilestoneSetting;
use App\Models\FinanceOption;
use App\Models\FinanceOptionMilestone;
use App\Models\Project;
use App\Models\ProjectFinanceMilestoneEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceMilestoneService
{
    public const MODE_TEST = 'test';
    public const MODE_PRODUCTION = 'production';

    public function syncDefaultMilestones(FinanceOption $financeOption): void
    {
        foreach ($this->defaultMilestones() as $milestone) {
            FinanceOptionMilestone::updateOrCreate(
                [
                    'finance_option_id' => $financeOption->id,
                    'key' => $milestone['key'],
                ],
                $milestone
            );
        }
    }

    public function triggerProjectCreated(Project $project): void
    {
        $this->triggerEligibleMilestones($project, ['project_created']);
    }

    public function triggerDateMilestones(Project $project, array $fields): void
    {
        $this->triggerEligibleMilestones($project, $fields);
    }

    private function triggerEligibleMilestones(Project $project, array $triggers): void
    {
        if (empty($triggers)) {
            return;
        }

        try {
            $project->loadMissing('customer.finances.finance');
            $financeOption = $project->customer?->finances?->finance;

            if (!$financeOption || !(bool) $financeOption->milestone_enabled) {
                return;
            }

            $this->syncDefaultMilestones($financeOption);
            $baseAmount = $this->paymentBaseAmount($project, $financeOption);

            if ($baseAmount <= 0) {
                return;
            }

            $milestones = $financeOption->milestones()
                ->where('is_active', true)
                ->where(function ($query) use ($triggers) {
                    if (in_array('project_created', $triggers, true)) {
                        $query->orWhere('trigger_type', 'project_created');
                    }

                    $dateFields = array_values(array_filter($triggers, fn ($trigger) => $trigger !== 'project_created'));
                    if (!empty($dateFields)) {
                        $query->orWhereIn('trigger_field', $dateFields);
                    }
                })
                ->get();

            foreach ($milestones as $milestone) {
                if (!$this->milestoneIsReady($project, $milestone)) {
                    continue;
                }

                $this->triggerMilestone($project, $financeOption, $milestone, $baseAmount);
            }
        } catch (\Throwable $throwable) {
            Log::error('Finance milestone trigger failed', [
                'project_id' => $project->id ?? null,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    private function triggerMilestone(Project $project, FinanceOption $financeOption, FinanceOptionMilestone $milestone, float $baseAmount): void
    {
        if (ProjectFinanceMilestoneEvent::where('project_id', $project->id)->where('key', $milestone->key)->exists()) {
            return;
        }

        DB::transaction(function () use ($project, $financeOption, $milestone, $baseAmount) {
            if (ProjectFinanceMilestoneEvent::where('project_id', $project->id)->where('key', $milestone->key)->exists()) {
                return;
            }

            $amount = $this->calculateAmount($project, $milestone, $baseAmount);
            if ($amount <= 0) {
                return;
            }

            $transaction = AccountTransaction::create([
                'project_id' => $project->id,
                'payee' => 'customer',
                'milestone' => $milestone->label,
                'amount' => $amount,
                'deduction_amount' => 0,
                'transaction_date' => $this->triggerDate($project, $milestone),
                'transaction_details' => 'Finance milestone payment: ' . $milestone->label,
            ]);

            $recipients = $this->activeRecipientEmails();

            ProjectFinanceMilestoneEvent::create([
                'project_id' => $project->id,
                'finance_option_milestone_id' => $milestone->id,
                'key' => $milestone->key,
                'base_amount' => $baseAmount,
                'amount' => $amount,
                'triggered_at' => now(),
                'account_transaction_id' => $transaction->id,
                'email_recipients' => $recipients,
            ]);

            foreach ($recipients as $recipient) {
                SendHtmlEmailJob::dispatch(
                    $recipient,
                    'Finance Milestone Triggered: ' . $project->project_name . ' - ' . $milestone->label,
                    $this->emailBody($project, $amount)
                );
            }
        });
    }

    private function calculateAmount(Project $project, FinanceOptionMilestone $milestone, float $baseAmount): float
    {
        if ($milestone->amount_type === 'fixed') {
            return round((float) $milestone->amount_value, 2);
        }

        if ($milestone->amount_type === 'percent') {
            return round($baseAmount * ((float) $milestone->amount_value / 100), 2);
        }

        $alreadyTriggeredAmount = ProjectFinanceMilestoneEvent::where('project_id', $project->id)->sum('amount');

        return round(max($baseAmount - (float) $alreadyTriggeredAmount, 0), 2);
    }

    private function paymentBaseAmount(Project $project, FinanceOption $financeOption): float
    {
        $finance = $project->customer?->finances;
        $source = $this->defaultAmountSource($financeOption) === 'customer_portion'
            ? 'customer_portion'
            : ($financeOption->milestone_amount_source ?: 'contract_amount');

        if ($source === 'customer_portion') {
            return (float) ($finance?->customer_portion ?? 0);
        }

        return (float) ($finance?->contract_amount ?? 0);
    }

    public function defaultAmountSource(FinanceOption $financeOption): string
    {
        return strcasecmp(trim((string) $financeOption->name), 'Prepaid PPA') === 0
            ? 'customer_portion'
            : 'contract_amount';
    }

    private function milestoneIsReady(Project $project, FinanceOptionMilestone $milestone): bool
    {
        if ($milestone->trigger_type === 'project_created') {
            return true;
        }

        return !empty($project->{$milestone->trigger_field});
    }

    private function triggerDate(Project $project, FinanceOptionMilestone $milestone): string
    {
        if (!empty($milestone->trigger_field) && !empty($project->{$milestone->trigger_field})) {
            return $project->{$milestone->trigger_field};
        }

        return now()->toDateString();
    }

    private function activeRecipientEmails(): array
    {
        $mode = FinanceMilestoneSetting::where('key', 'email_mode')->value('value') ?: self::MODE_TEST;

        return FinanceMilestoneEmailRecipient::where('mode', $mode)
            ->where('is_active', true)
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function emailBody(Project $project, float $amount): string
    {
        $customerName = trim(($project->customer?->first_name ?? '') . ' ' . ($project->customer?->last_name ?? ''));
        $customerName = $customerName !== '' ? $customerName : $project->project_name;
        $projectUrl = route('projects.show', $project->id);

        return 'Dear Accounting Team, Please collect $' . number_format($amount, 2)
            . ' from <a href="' . e($projectUrl) . '">' . e($customerName) . '</a>. Thank you';
    }

    private function defaultMilestones(): array
    {
        return [
            [
                'key' => 'deal_review',
                'label' => 'Deal Review',
                'trigger_type' => 'project_created',
                'trigger_field' => null,
                'amount_type' => 'fixed',
                'amount_value' => 1000,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'key' => 'permit_approval',
                'label' => 'Permit Approval Date',
                'trigger_type' => 'project_date',
                'trigger_field' => 'permitting_approval_date',
                'amount_type' => 'percent',
                'amount_value' => 50,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'key' => 'solar_install',
                'label' => 'Solar Install Date',
                'trigger_type' => 'project_date',
                'trigger_field' => 'solar_install_date',
                'amount_type' => 'percent',
                'amount_value' => 35,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'key' => 'inspection_approval',
                'label' => 'Inspection Approval Date',
                'trigger_type' => 'project_date',
                'trigger_field' => 'inspection_approval_date',
                'amount_type' => 'remaining',
                'amount_value' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];
    }
}
