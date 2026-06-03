<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Explains the CRM's structure to users in plain language.
 *
 * Backed by config/ai_field_dictionary.php, this service answers "what does
 * field X mean?", "what values can status have?", "what is PTO?" style
 * questions deterministically (no OpenAI call, always accurate), and produces
 * grounding context + guidance the chat uses when a user asks about something
 * vague or that does not exist.
 *
 * It is permission-aware: it never explains a field the user's role cannot
 * access — instead it tells them the field is restricted.
 */
class AiFieldDictionaryService
{
    /**
     * Words that signal the user wants an EXPLANATION of a field/term rather
     * than the data itself.
     */
    private const EXPLAIN_CUES = [
        'kya hai', 'kya hota', 'kya hoti', 'kya matlab', 'iska matlab', 'ka matlab',
        'ki matlab', 'matlab kya', 'matlab', 'kis liye', 'kis kaam', 'kaam kya',
        'meaning', 'what is', 'what does', 'what are', 'explain', 'define',
        'definition', 'describe', 'kya darshata', 'kya batata', 'samjhao', 'samjha',
        'possible values', 'what values', 'kya values', 'values kya', 'konsi values',
        'field kya', 'column kya', 'is field', 'this field', 'ye field', 'yeh field',
    ];

    /**
     * Curated synonyms → concrete table.column targets. Lets users ask in
     * natural language ("notice to proceed", "deal amount") and still land on
     * the right field. Mirrors the planner's domain mappings.
     *
     * @var array<string, array<int, string>>
     */
    private const SYNONYMS = [
        'notice to proceed' => ['projects.ntp_approval_date'],
        'ntp' => ['projects.ntp_approval_date'],
        'permission to operate' => ['projects.pto_submission_date', 'projects.pto_approval_date'],
        'pto' => ['projects.pto_submission_date', 'projects.pto_approval_date'],
        'homeowners association' => ['projects.hoa', 'projects.hoa_approval_date'],
        'hoa' => ['projects.hoa', 'projects.hoa_approval_date', 'projects.hoa_approval_request_date'],
        'authority having jurisdiction' => ['projects.ahj'],
        'ahj' => ['projects.ahj'],
        'main panel upgrade' => ['projects.mpu_required', 'projects.mpu_install_date'],
        'mpu' => ['projects.mpu_required', 'projects.mpu_install_date'],
        'certificate of completion' => ['projects.coc_packet_mailed_out_date'],
        'coc' => ['projects.coc_packet_mailed_out_date'],
        'meter spot' => ['projects.meter_spot_requestd_date', 'projects.meter_spot_result'],
        'solar install' => ['projects.solar_install_date'],
        'solar installation' => ['projects.solar_install_date'],
        'battery install' => ['projects.battery_install_date'],
        'permit' => ['projects.permitting_submittion_date', 'projects.permitting_approval_date'],
        'permitting' => ['projects.permitting_submittion_date', 'projects.permitting_approval_date'],
        'rough inspection' => ['projects.rough_inspection_date'],
        'final inspection' => ['projects.final_inspection_date'],
        'fire inspection' => ['projects.fire_inspection_date', 'projects.fire_review_required'],
        'contract amount' => ['customer_finances.contract_amount'],
        'deal amount' => ['customer_finances.contract_amount'],
        'dealer fee' => ['customer_finances.dealer_fee_amount', 'customer_finances.dealer_fee'],
        'commission' => ['customer_finances.commission'],
        'redline' => ['customer_finances.redline_costs'],
        'holdback' => ['customer_finances.holdback_amount'],
        'adders' => ['customer_finances.adders', 'customer_adders.amount'],
        'priority' => ['service_tickets.priority'],
        'ticket status' => ['service_tickets.status'],
        'task status' => ['tasks.status'],
        'acceptance status' => ['project_acceptances.status'],
        'sold date' => ['customers.sold_date'],
        'sale date' => ['customers.sold_date'],
        'panel quantity' => ['customers.panel_qty'],
        'system size' => ['customers.panel_qty', 'project_design_details.kw_rating'],
    ];

    private const GENERIC_COLUMNS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function __construct(private readonly AiPermissionService $aiPermissionService)
    {
    }

    /**
     * True when the message reads like a request to explain a field/term/concept
     * rather than fetch data. Conservative: requires an explain cue and the
     * absence of a concrete record reference (a project code/quoted name), so it
     * never hijacks a real data question like "what is the status of 1001?".
     */
    public function isExplanationRequest(string $message): bool
    {
        if ($this->hasConcreteRecordReference($message)) {
            return false;
        }

        $lower = mb_strtolower($message);

        foreach (self::EXPLAIN_CUES as $cue) {
            if (str_contains($lower, $cue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attempt a deterministic explanation of whatever field/term the message
     * references. Returns ['handled' => true, 'message' => ...] on a confident
     * match, or ['handled' => false] so the caller can fall back to general AI
     * chat (which is grounded with contextFor()).
     *
     * @return array{handled:bool, message?:string, matched?:array}
     */
    public function explain(string $message, User $user): array
    {
        $glossary = $this->matchGlossary($message);
        $columns  = $this->matchColumns($message, $user);

        // Nothing concrete resolved → let the AI handle it with grounding.
        if ($glossary === [] && $columns['accessible'] === [] && $columns['restricted'] === []) {
            return ['handled' => false];
        }

        // Only restricted fields matched → guide the user honestly instead of
        // pretending the field does not exist or leaking its meaning.
        if ($columns['accessible'] === [] && $columns['restricted'] !== [] && $glossary === []) {
            $names = collect($columns['restricted'])->pluck('column')->unique()->implode(', ');

            return [
                'handled' => true,
                'message' => "The field **{$names}** is part of restricted financial data, so it isn't available for your role. "
                    . 'If you believe you should have access, please contact your administrator. '
                    . 'I can still help you with project, customer, task and ticket information.',
            ];
        }

        $lines = [];

        foreach ($glossary as $term => $definition) {
            $lines[] = "**{$term}** — {$definition}";
        }

        if ($columns['accessible'] !== []) {
            if ($lines !== []) {
                $lines[] = '';
            }

            foreach ($columns['accessible'] as $match) {
                $lines[] = $this->formatColumnExplanation($match);
            }
        }

        $matchedNames = array_merge(
            array_keys($glossary),
            collect($columns['accessible'])->map(fn ($m) => $m['table'] . '.' . $m['column'])->all()
        );

        return [
            'handled' => true,
            'message' => implode("\n", $lines),
            'matched' => $matchedNames,
        ];
    }

    /**
     * Compact dictionary snippets relevant to the message, used to ground the
     * general-chat OpenAI call so it explains structure accurately instead of
     * guessing. Returns '' when nothing relevant is found.
     */
    public function contextFor(string $message, User $user): string
    {
        $glossary = $this->matchGlossary($message);
        $columns  = $this->matchColumns($message, $user)['accessible'];

        if ($glossary === [] && $columns === []) {
            return '';
        }

        $lines = ['[CRM data dictionary — use ONLY this to explain fields/terms; do not invent fields]'];

        foreach ($glossary as $term => $definition) {
            $lines[] = "{$term}: {$definition}";
        }

        foreach ($columns as $match) {
            $tableLabel = $match['table_label'];
            $line = "{$match['column']} ({$tableLabel}): {$match['description']}";

            if ($match['values'] !== []) {
                $pairs = collect($match['values'])->map(fn ($v, $k) => "{$k} = {$v}")->implode('; ');
                $line .= " Values: {$pairs}.";
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Friendly guidance for when a data question could not be resolved. Lists
     * the modules the user can actually query plus a couple of example
     * questions, and points at the closest matching fields when there are any.
     */
    public function guidanceFor(string $message, User $user): string
    {
        $modules = $this->accessibleModuleLabels($user);
        $lines = [];

        $closest = $this->matchColumns($message, $user)['accessible'];
        if ($closest !== []) {
            $fields = collect($closest)->take(4)->map(fn ($m) => $m['column'] . ' (' . $m['table_label'] . ')')->implode(', ');
            $lines[] = "Did you mean one of these fields? {$fields}.";
            $lines[] = '';
        }

        if ($modules !== []) {
            $lines[] = 'You can ask me about: ' . collect($modules)->take(10)->implode(', ') . '.';
        }

        $lines[] = 'For example: "show my active projects", "tickets pending by priority", or "what does the field meter_spot_result mean?".';

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // Matching internals
    // -------------------------------------------------------------------------

    /**
     * @return array<string, string> matched glossary term => definition
     */
    private function matchGlossary(string $message): array
    {
        $lower = ' ' . mb_strtolower($message) . ' ';
        $hits = [];

        foreach ((array) config('ai_field_dictionary.glossary', []) as $term => $definition) {
            $needle = mb_strtolower((string) $term);

            // Word-boundary-ish match so "pto" doesn't match inside "option".
            if (preg_match('/(?<![a-z])' . preg_quote($needle, '/') . '(?![a-z])/u', $lower)) {
                $hits[$term] = $definition;
            }
        }

        return $hits;
    }

    /**
     * Resolve which documented columns the message refers to, split by whether
     * the user may access each one.
     *
     * @return array{accessible:array<int,array>, restricted:array<int,array>}
     */
    private function matchColumns(string $message, User $user): array
    {
        $lower = ' ' . mb_strtolower($message) . ' ';
        $tables = (array) config('ai_field_dictionary.tables', []);

        $targets = [];      // "table.column" => true (dedupe)
        $accessible = [];
        $restricted = [];

        // 1. Synonym hits → explicit targets.
        foreach (self::SYNONYMS as $phrase => $cols) {
            if (str_contains($lower, ' ' . $phrase . ' ') || str_contains($lower, ' ' . $phrase)) {
                foreach ($cols as $target) {
                    $targets[$target] = true;
                }
            }
        }

        // 2. Direct column-name hits across documented tables.
        foreach ($tables as $table => $meta) {
            foreach (array_keys((array) ($meta['columns'] ?? [])) as $column) {
                if (in_array($column, self::GENERIC_COLUMNS, true)) {
                    continue;
                }

                foreach ($this->columnVariants($column) as $variant) {
                    if (preg_match('/(?<![a-z])' . preg_quote($variant, '/') . '(?![a-z])/u', $lower)) {
                        $targets["{$table}.{$column}"] = true;
                        break;
                    }
                }
            }
        }

        foreach (array_keys($targets) as $target) {
            [$table, $column] = explode('.', $target, 2);
            $meta = $tables[$table] ?? null;

            if (! $meta || ! isset($meta['columns'][$column])) {
                continue;
            }

            $entry = [
                'table'       => $table,
                'table_label' => $meta['label'] ?? Str::headline($table),
                'column'      => $column,
                'description' => $meta['columns'][$column],
                'values'      => (array) ($meta['value_maps'][$column] ?? []),
            ];

            if ($this->aiPermissionService->canAccessColumn($user, $table, $column)) {
                $accessible[] = $entry;
            } else {
                $restricted[] = $entry;
            }
        }

        // Keep the response focused.
        return [
            'accessible' => array_slice($accessible, 0, 8),
            'restricted' => $restricted,
        ];
    }

    /**
     * Name variants used to spot a column inside free text: the raw name and a
     * spaced version ("meter_spot_result" → "meter spot result").
     *
     * @return array<int,string>
     */
    private function columnVariants(string $column): array
    {
        $spaced = str_replace('_', ' ', $column);

        return array_values(array_unique([$column, $spaced]));
    }

    private function formatColumnExplanation(array $match): string
    {
        $line = "**{$match['column']}** ({$match['table_label']}) — {$match['description']}";

        if ($match['values'] !== []) {
            $pairs = collect($match['values'])->map(fn ($v, $k) => "`{$k}` = {$v}")->implode(', ');
            $line .= "\n   Possible values: {$pairs}.";
        }

        return $line;
    }

    /**
     * Human labels for the modules the user can query, for help/guidance text.
     *
     * @return array<int,string>
     */
    private function accessibleModuleLabels(User $user): array
    {
        $labels = [];

        foreach ((array) config('ai_field_dictionary.tables', []) as $table => $meta) {
            if ($this->aiPermissionService->canAccessTable($user, $table)) {
                $labels[] = $meta['label'] ?? Str::headline($table);
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * Whether the message names a specific record (project code, hyphenated
     * proper name, or quoted name) — a sign it wants data, not a definition.
     */
    private function hasConcreteRecordReference(string $message): bool
    {
        return (bool) preg_match('/\b([A-Z]{1,4}-\d{3,6}|\d{4,6})\b/', $message)
            || (bool) preg_match('/\b[A-Z][a-zA-Z]+-[A-Z][a-zA-Z]+\b/', $message)
            || (bool) preg_match('/["\'][A-Za-z][A-Za-z\s\-]+["\']/', $message);
    }
}
