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
use App\Models\ProjectZoneDate;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Finance option milestones: the payments a finance option raises as a project
 * moves along. The rows are data, edited from Operations -> Finance Options;
 * this class fires them and keeps the editor honest.
 *
 * A milestone fires at most once per project (matched on its `key`), and an
 * account transaction it raised is never rewritten - editing a row only
 * changes what happens next.
 */
class FinanceMilestoneService
{
    public const MODE_TEST = 'test';
    public const MODE_PRODUCTION = 'production';

    public const TRIGGER_PROJECT_CREATED = 'project_created';
    public const TRIGGER_PROJECT_DATE = 'project_date';
    public const TRIGGER_ZONE_DATE = 'zone_date';
    public const TRIGGER_ZONE_ENTERED = 'zone_entered';

    public const AMOUNT_TYPES = [
        'fixed' => 'Fixed Amount',
        'percent' => 'Percent',
        'remaining' => 'Remaining Amount',
    ];

    /**
     * Give a brand-new finance option the four standard milestones. Only ever
     * for an option that has never had any: once the user has edited (or
     * deleted) its rows, those are the rows.
     */
    public function seedDefaultMilestones(FinanceOption $financeOption): void
    {
        if ($financeOption->milestones()->withTrashed()->exists()) {
            return;
        }

        foreach ($this->defaultMilestones() as $milestone) {
            $financeOption->milestones()->create($milestone);
        }
    }

    /**
     * Replace a finance option's milestones with the rows the editor posted.
     * A posted row with an id updates that row (its key - and so its fired
     * history - is kept); one without becomes a new row; a row no longer posted
     * is soft deleted, which leaves the transactions it raised alone.
     *
     * @param  array<int, array<string, mixed>>  $rows  already validated
     */
    public function syncMilestones(FinanceOption $financeOption, array $rows): void
    {
        DB::transaction(function () use ($financeOption, $rows) {
            $existing = $financeOption->milestones()->get()->keyBy('id');
            $kept = [];

            foreach (array_values($rows) as $index => $row) {
                $attributes = array_merge($this->parseTrigger((string) $row['trigger']) ?? [], [
                    'label' => trim((string) $row['label']),
                    'amount_type' => $row['amount_type'],
                    'amount_value' => $row['amount_type'] === 'remaining' ? null : (float) $row['amount_value'],
                    'amount_base' => $row['amount_base'],
                    'payee' => $row['payee'],
                    'sort_order' => $index + 1,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ]);

                $milestone = ! empty($row['id']) ? $existing->get((int) $row['id']) : null;

                if ($milestone) {
                    $milestone->update($attributes);
                    $kept[] = $milestone->id;

                    continue;
                }

                $kept[] = $financeOption->milestones()->create($attributes + [
                    'key' => $this->newKey($financeOption, $attributes['label']),
                ])->id;
            }

            $existing->except($kept)->each->delete();
        });
    }

    /**
     * What the field-by-field rules cannot see: the rows taken together. Keyed
     * like validator errors so the form can show them.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, string>
     */
    public function rowErrors(array $rows): array
    {
        $errors = [];
        $bases = config('finance_milestones.amount_bases', []);
        $remaining = [];
        $percent = [];

        foreach ($rows as $index => $row) {
            if (! (bool) ($row['is_active'] ?? true)) {
                continue;
            }

            $base = (string) ($row['amount_base'] ?? '');
            $baseLabel = $bases[$base] ?? $base;

            if (($row['amount_type'] ?? null) === 'remaining') {
                if (isset($remaining[$base])) {
                    $errors["milestones.{$index}.amount_type"] = "Only one active Remaining Amount milestone is allowed on {$baseLabel}.";
                }

                $remaining[$base] = true;
            }

            if (($row['amount_type'] ?? null) === 'percent') {
                $percent[$base] = ($percent[$base] ?? 0) + (float) ($row['amount_value'] ?? 0);
            }
        }

        foreach ($percent as $base => $total) {
            if ($total > 100) {
                $errors['milestones'] = 'Percent milestones on '.($bases[$base] ?? $base).' add up to '.round($total, 2).'%, more than 100%.';
            }
        }

        return $errors;
    }

    /**
     * Every trigger a milestone may use, grouped for the dropdown and keyed by
     * the value the form posts. Zone triggers come from the `zones` table, so a
     * new zone shows up here without a code change.
     *
     * @return array<string, array<string, string>>
     */
    public function triggerOptions(): array
    {
        $zones = Zone::ordered()->get();

        return array_filter([
            'General' => [self::TRIGGER_PROJECT_CREATED => 'Project Create'],
            'Project Dates' => collect(config('finance_milestones.project_dates', []))
                ->mapWithKeys(fn ($label, $column) => [self::TRIGGER_PROJECT_DATE.':'.$column => $label])
                ->all(),
            'Zone Dates' => $zones->filter(fn (Zone $zone) => $zone->collectsApprovalDate())
                ->mapWithKeys(fn (Zone $zone) => [self::TRIGGER_ZONE_DATE.':'.$zone->id => $zone->approvalDateLabel()])
                ->all(),
            'Zone Moves' => $zones->reject(fn (Zone $zone) => $zone->isArchive())
                ->mapWithKeys(fn (Zone $zone) => [self::TRIGGER_ZONE_ENTERED.':'.$zone->id => 'Moved into '.$zone->name])
                ->all(),
        ]);
    }

    /** @return array<int, string> */
    public function triggerValues(): array
    {
        return collect($this->triggerOptions())->flatMap(fn ($group) => array_keys($group))->values()->all();
    }

    /**
     * A posted trigger value as the columns it is stored in, or null when it is
     * not one of the offered triggers.
     *
     * @return array{trigger_type: string, trigger_field: ?string, trigger_zone_id: ?int}|null
     */
    public function parseTrigger(string $value): ?array
    {
        if (! in_array($value, $this->triggerValues(), true)) {
            return null;
        }

        [$type, $target] = array_pad(explode(':', $value, 2), 2, null);

        return [
            'trigger_type' => $type,
            'trigger_field' => $type === self::TRIGGER_PROJECT_DATE ? $target : null,
            'trigger_zone_id' => in_array($type, [self::TRIGGER_ZONE_DATE, self::TRIGGER_ZONE_ENTERED], true) ? (int) $target : null,
        ];
    }

    /**
     * A milestone (stored row or default) as the editor's form row.
     *
     * @param  FinanceOptionMilestone|array<string, mixed>  $milestone
     * @return array<string, mixed>
     */
    public function formRow($milestone): array
    {
        $milestone = $milestone instanceof FinanceOptionMilestone ? $milestone->getAttributes() + ['id' => $milestone->id] : $milestone;

        $trigger = match ($milestone['trigger_type'] ?? null) {
            self::TRIGGER_PROJECT_DATE => self::TRIGGER_PROJECT_DATE.':'.$milestone['trigger_field'],
            self::TRIGGER_ZONE_DATE, self::TRIGGER_ZONE_ENTERED => $milestone['trigger_type'].':'.$milestone['trigger_zone_id'],
            default => self::TRIGGER_PROJECT_CREATED,
        };

        return [
            'id' => $milestone['id'] ?? null,
            'label' => $milestone['label'] ?? '',
            'trigger' => $trigger,
            'amount_type' => $milestone['amount_type'] ?? 'percent',
            'amount_value' => $milestone['amount_value'] ?? null,
            'amount_base' => $milestone['amount_base'] ?? 'contract_amount',
            'payee' => $milestone['payee'] ?? 'customer',
            'is_active' => (bool) ($milestone['is_active'] ?? true),
        ];
    }

    /**
     * The rows the editor opens with: the option's own, or the standard four
     * for an option that has never had any.
     *
     * @return array<int, array<string, mixed>>
     */
    public function editorRows(?FinanceOption $financeOption): array
    {
        if ($financeOption && $financeOption->milestones()->withTrashed()->exists()) {
            return $financeOption->milestones()->get()->map(fn ($milestone) => $this->formRow($milestone))->all();
        }

        return array_map(fn ($milestone) => $this->formRow($milestone), $this->defaultMilestones());
    }

    /**
     * A new project: its Project Create milestones, and any date milestone whose
     * date the project was created with already filled in.
     */
    public function triggerProjectCreated(Project $project): void
    {
        $this->fireMatching($project, self::TRIGGER_PROJECT_CREATED);
        $this->triggerDateMilestones($project, array_keys(config('finance_milestones.project_dates', [])));
    }

    /**
     * Project date columns were just saved. Callers pass every column they
     * wrote; only the ones a milestone may watch are looked at.
     */
    public function triggerDateMilestones(Project $project, array $fields): void
    {
        $fields = array_values(array_intersect($fields, array_keys(config('finance_milestones.project_dates', []))));

        if ($fields !== []) {
            $this->fireMatching($project, self::TRIGGER_PROJECT_DATE, $fields);
        }
    }

    /** A zone's approval date was just filed. */
    public function triggerZoneDate(Project $project, Zone $zone): void
    {
        $this->fireMatching($project, self::TRIGGER_ZONE_DATE, [], $zone->id);
    }

    /** The project just entered a zone, by hand or automatically. */
    public function triggerZoneEntered(Project $project, Zone $zone): void
    {
        $this->fireMatching($project, self::TRIGGER_ZONE_ENTERED, [], $zone->id);
    }

    private function fireMatching(Project $project, string $triggerType, array $fields = [], ?int $zoneId = null): void
    {
        try {
            $project->loadMissing('customer.finances.finance');
            $financeOption = $project->customer?->finances?->finance;

            if (! $financeOption || ! (bool) $financeOption->milestone_enabled) {
                return;
            }

            $milestones = $financeOption->milestones()
                ->where('is_active', true)
                ->where('trigger_type', $triggerType)
                ->when($triggerType === self::TRIGGER_PROJECT_DATE, fn ($query) => $query->whereIn('trigger_field', $fields))
                ->when($zoneId !== null, fn ($query) => $query->where('trigger_zone_id', $zoneId))
                ->get();

            foreach ($milestones as $milestone) {
                $date = $this->triggerDate($project, $milestone);

                if ($date === null) {
                    continue;
                }

                $this->triggerMilestone($project, $milestone, $date);
            }
        } catch (\Throwable $throwable) {
            Log::error('Finance milestone trigger failed', [
                'project_id' => $project->id ?? null,
                'trigger_type' => $triggerType,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    private function triggerMilestone(Project $project, FinanceOptionMilestone $milestone, string $date): void
    {
        if (ProjectFinanceMilestoneEvent::where('project_id', $project->id)->where('key', $milestone->key)->exists()) {
            return;
        }

        DB::transaction(function () use ($project, $milestone, $date) {
            if (ProjectFinanceMilestoneEvent::where('project_id', $project->id)->where('key', $milestone->key)->exists()) {
                return;
            }

            $base = $milestone->amount_base ?: 'contract_amount';
            $baseAmount = $this->baseAmount($project, $base);

            // Nothing to take a share of - e.g. a TPO credit milestone on a
            // customer saved before the credit was split. Not recorded, so it
            // fires once the figure is there and its trigger comes round again.
            if ($baseAmount <= 0) {
                return;
            }

            $amount = $this->calculateAmount($project, $milestone, $base, $baseAmount);
            if ($amount <= 0) {
                return;
            }

            $transaction = AccountTransaction::create([
                'project_id' => $project->id,
                'payee' => $milestone->payee ?: 'customer',
                'milestone' => $milestone->label,
                'amount' => $amount,
                'deduction_amount' => 0,
                'transaction_date' => $date,
                'transaction_details' => 'Finance milestone payment: ' . $milestone->label,
            ]);

            $recipients = $this->activeRecipientEmails();

            ProjectFinanceMilestoneEvent::create([
                'project_id' => $project->id,
                'finance_option_milestone_id' => $milestone->id,
                'key' => $milestone->key,
                'amount_base' => $base,
                'base_amount' => $baseAmount,
                'amount' => $amount,
                'triggered_at' => now(),
                'account_transaction_id' => $transaction->id,
                'email_recipients' => $recipients,
            ]);

            $mail = app(NotificationTemplateService::class)->render('finance_milestone_triggered', [
                'project_name' => $project->project_name,
                'milestone_label' => $milestone->label,
                'customer_name' => $this->customerName($project),
                'amount' => number_format($amount, 2),
                'project_url' => route('projects.show', $project->id),
            ], ['project' => $project]);

            foreach ($recipients as $recipient) {
                SendHtmlEmailJob::dispatch($recipient, $mail['subject'], $mail['body']);
            }
        });
    }

    /**
     * "Remaining" is the base minus what this project's milestones on the SAME
     * base already raised - a TPO credit payment never comes off the customer's
     * last instalment.
     */
    private function calculateAmount(Project $project, FinanceOptionMilestone $milestone, string $base, float $baseAmount): float
    {
        if ($milestone->amount_type === 'fixed') {
            return round((float) $milestone->amount_value, 2);
        }

        if ($milestone->amount_type === 'percent') {
            return round($baseAmount * ((float) $milestone->amount_value / 100), 2);
        }

        $alreadyRaised = ProjectFinanceMilestoneEvent::where('project_id', $project->id)
            ->where('amount_base', $base)
            ->sum('amount');

        return round(max($baseAmount - (float) $alreadyRaised, 0), 2);
    }

    private function baseAmount(Project $project, string $base): float
    {
        if (! array_key_exists($base, config('finance_milestones.amount_bases', []))) {
            return 0;
        }

        return (float) ($project->customer?->finances?->{$base} ?? 0);
    }

    /**
     * The date the transaction is booked on, or null when the milestone's
     * trigger has not actually happened (its date is still empty).
     */
    private function triggerDate(Project $project, FinanceOptionMilestone $milestone): ?string
    {
        return match ($milestone->trigger_type) {
            self::TRIGGER_PROJECT_DATE => ! empty($milestone->trigger_field) && ! empty($project->{$milestone->trigger_field})
                ? (string) $project->{$milestone->trigger_field}
                : null,
            self::TRIGGER_ZONE_DATE => ProjectZoneDate::where('project_id', $project->id)
                ->where('zone_id', $milestone->trigger_zone_id)
                ->whereNotNull('approval_date')
                ->value('approval_date'),
            default => now()->toDateString(),
        };
    }

    private function newKey(FinanceOption $financeOption, string $label): string
    {
        $stem = Str::limit(Str::slug($label, '_') ?: 'milestone', 40, '');

        do {
            $key = $stem.'_'.Str::lower(Str::random(6));
        } while ($financeOption->milestones()->withTrashed()->where('key', $key)->exists());

        return $key;
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

    private function customerName(Project $project): string
    {
        $customerName = trim(($project->customer?->first_name ?? '') . ' ' . ($project->customer?->last_name ?? ''));

        return $customerName !== '' ? $customerName : $project->project_name;
    }

    private function defaultMilestones(): array
    {
        return [
            [
                'key' => 'deal_review',
                'label' => 'Deal Review',
                'trigger_type' => self::TRIGGER_PROJECT_CREATED,
                'trigger_field' => null,
                'trigger_zone_id' => null,
                'amount_type' => 'fixed',
                'amount_value' => 1000,
                'amount_base' => 'contract_amount',
                'payee' => 'customer',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'key' => 'permit_approval',
                'label' => 'Permit Approval Date',
                'trigger_type' => self::TRIGGER_PROJECT_DATE,
                'trigger_field' => 'permitting_approval_date',
                'trigger_zone_id' => null,
                'amount_type' => 'percent',
                'amount_value' => 50,
                'amount_base' => 'contract_amount',
                'payee' => 'customer',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'key' => 'solar_install',
                'label' => 'Solar Install Date',
                'trigger_type' => self::TRIGGER_PROJECT_DATE,
                'trigger_field' => 'solar_install_date',
                'trigger_zone_id' => null,
                'amount_type' => 'percent',
                'amount_value' => 35,
                'amount_base' => 'contract_amount',
                'payee' => 'customer',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'key' => 'inspection_approval',
                'label' => 'Inspection Approval Date',
                'trigger_type' => self::TRIGGER_PROJECT_DATE,
                'trigger_field' => 'inspection_approval_date',
                'trigger_zone_id' => null,
                'amount_type' => 'remaining',
                'amount_value' => null,
                'amount_base' => 'contract_amount',
                'payee' => 'customer',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];
    }
}
