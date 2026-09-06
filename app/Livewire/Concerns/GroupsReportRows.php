<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Grouping and totals, for both report screens.
 *
 * A grouped report is read as "how much per finance option / department /
 * sales partner", so the subtotals have to be the truth about the whole
 * result, not about the rows that happen to be on screen: the detail rows are
 * capped and paged, while the counts and sums come from one GROUP BY over
 * everything the report matches.
 */
trait GroupsReportRows
{
    /** The alias the group's value is selected under, beside the report's own columns. */
    public const GROUP_ALIAS = 'report_group_value';

    /** Columns worth adding up: the numeric ones, in the order they are shown. */
    protected function summableFields(array $fields): array
    {
        $summable = [];

        foreach ($fields as $field) {
            if ($this->getFieldType($field) === 'number') {
                $summable[$field] = $this->fieldAlias($field);
            }
        }

        return $summable;
    }

    /**
     * One row per group: its value, how many records it holds and the sum of
     * every numeric column. Ordered by the biggest total first when there is
     * something to add up, else by the group's own name.
     *
     * @param  callable():\Illuminate\Database\Eloquent\Builder  $base  a fresh, filtered, joined query
     * @return array<int, array{value: string|null, count: int, sums: array<string, float>}>
     */
    protected function groupSummaries(callable $base, string $groupField, array $summable): array
    {
        $select = [
            DB::raw($groupField.' as '.self::GROUP_ALIAS),
            DB::raw('count(*) as group_count'),
        ];

        foreach ($summable as $field => $alias) {
            $select[] = DB::raw('sum('.$field.') as sum_'.$alias);
        }

        $rows = $base()
            ->select($select)
            ->groupBy(DB::raw($groupField))
            ->get();

        $summaries = [];

        foreach ($rows as $row) {
            $sums = [];

            foreach ($summable as $alias) {
                $sums[$alias] = (float) ($row->{'sum_'.$alias} ?? 0);
            }

            $summaries[] = [
                'value' => $row->{self::GROUP_ALIAS},
                'count' => (int) $row->group_count,
                'sums' => $sums,
            ];
        }

        usort($summaries, fn ($a, $b) => strcmp((string) $a['value'], (string) $b['value']));

        return $summaries;
    }

    /**
     * The report's own bottom line: every record it matches, and the sum of
     * each numeric column across all of them.
     *
     * @return array{count: int, sums: array<string, float>}
     */
    protected function grandTotals(array $summaries): array
    {
        $totals = ['count' => 0, 'sums' => []];

        foreach ($summaries as $summary) {
            $totals['count'] += $summary['count'];

            foreach ($summary['sums'] as $alias => $value) {
                $totals['sums'][$alias] = ($totals['sums'][$alias] ?? 0) + $value;
            }
        }

        return $totals;
    }

    /**
     * The fetched rows split into their groups, in the summaries' order, each
     * carrying the group's true count and sums. Groups whose detail rows are
     * all beyond the row cap still appear, with no rows under them.
     *
     * @return array<int, array{value: string|null, count: int, sums: array<string, float>, rows: array}>
     */
    protected function groupRows($rows, array $summaries): array
    {
        $byValue = [];

        foreach ($rows as $row) {
            $byValue[(string) ($row->{self::GROUP_ALIAS} ?? '')][] = $row;
        }

        $groups = [];

        foreach ($summaries as $summary) {
            $groups[] = $summary + ['rows' => $byValue[(string) $summary['value']] ?? []];
        }

        return $groups;
    }

    /** The label a group is shown under; a missing value is not a blank row. */
    protected function groupLabel($value): string
    {
        return $value === null || $value === '' ? 'Not set' : (string) $value;
    }
}
