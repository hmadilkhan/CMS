<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Grouping and column summaries, for both report screens.
 *
 * Two rules hold the numbers together:
 *
 *  - A summary describes every record it covers, never the rows on screen.
 *    The detail rows are capped and paged; each level's counts and figures
 *    come from its own aggregate query over everything the report matches.
 *  - Every level is measured by its own query rather than folded up from the
 *    level below. Sums would fold correctly, but an average of averages is not
 *    an average, and a report that quietly did that would be wrong in the one
 *    place people trust it most.
 */
trait GroupsReportRows
{
    /** Aliases the group values are selected under, one per level. */
    public const GROUP_ALIASES = ['report_group_1', 'report_group_2'];

    /** How many fields a report may group by. */
    public const MAX_GROUP_LEVELS = 2;

    /** What a numeric column can be summarised by. */
    public static function summaryFunctions(): array
    {
        return [
            'sum' => 'Sum',
            'avg' => 'Average',
            'min' => 'Lowest',
            'max' => 'Highest',
            'count' => 'Count',
        ];
    }

    /**
     * The columns being summarised, as alias => [field, function]. A numeric
     * column is summed unless it was given another function; 'none' drops it.
     *
     * @param  array<int, string>  $fields  the report's columns
     * @param  array<string, string>  $chosen  field => function, as saved
     */
    protected function aggregatedColumns(array $fields, array $chosen = []): array
    {
        $aggregates = [];

        foreach ($fields as $field) {
            if ($this->getFieldType($field) !== 'number') {
                continue;
            }

            $function = $chosen[$field] ?? 'sum';

            if (! array_key_exists($function, static::summaryFunctions())) {
                continue;
            }

            $aggregates[$this->fieldAlias($field)] = ['field' => $field, 'function' => $function];
        }

        return $aggregates;
    }

    /**
     * One aggregate query per grouping level, nested into a tree. Each node
     * carries its group's value, its record count and its summary figures.
     *
     * @param  callable():\Illuminate\Database\Eloquent\Builder  $base  a fresh, filtered, joined query
     * @param  array<int, string>  $groupFields  one or two qualified field names
     */
    protected function groupTree(callable $base, array $groupFields, array $aggregates): array
    {
        $groupFields = array_slice(array_values(array_filter($groupFields)), 0, self::MAX_GROUP_LEVELS);

        if ($groupFields === []) {
            return [];
        }

        $levels = [];

        foreach ($groupFields as $depth => $_) {
            $levels[$depth] = $this->aggregateRows($base, array_slice($groupFields, 0, $depth + 1), $aggregates);
        }

        $tree = [];

        foreach ($levels[0] as $node) {
            if (isset($levels[1])) {
                $node['children'] = array_values(array_filter(
                    $levels[1],
                    fn ($child) => $this->sameValue($child['path'][0], $node['path'][0])
                ));
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * The report's own bottom line, measured over every matching record - not
     * folded up from the groups, so an average stays an average.
     *
     * @param  callable():\Illuminate\Database\Eloquent\Builder  $base
     * @return array{count: int, aggregates: array<string, float|null>}
     */
    protected function reportTotals(callable $base, array $aggregates): array
    {
        $row = $this->aggregateRows($base, [], $aggregates)[0] ?? null;

        return [
            'count' => $row['count'] ?? 0,
            'aggregates' => $row['aggregates'] ?? [],
        ];
    }

    /**
     * One aggregate query: count and every summary figure, grouped by the
     * given fields (or over the whole report when there are none).
     */
    private function aggregateRows(callable $base, array $groupFields, array $aggregates): array
    {
        $select = [DB::raw('count(*) as group_count')];

        foreach ($groupFields as $depth => $field) {
            $select[] = DB::raw($field.' as '.self::GROUP_ALIASES[$depth]);
        }

        foreach ($aggregates as $alias => $aggregate) {
            $select[] = DB::raw($aggregate['function'].'('.$aggregate['field'].') as agg_'.$alias);
        }

        $query = $base()->select($select);

        foreach ($groupFields as $field) {
            $query->groupBy(DB::raw($field));
        }

        $rows = $query->get();
        $nodes = [];

        foreach ($rows as $row) {
            $path = [];

            foreach ($groupFields as $depth => $_) {
                $path[] = $row->{self::GROUP_ALIASES[$depth]};
            }

            $figures = [];

            foreach ($aggregates as $alias => $aggregate) {
                $value = $row->{'agg_'.$alias};
                $figures[$alias] = $value === null ? null : (float) $value;
            }

            $nodes[] = [
                'path' => $path,
                'value' => $path === [] ? null : end($path),
                'count' => (int) $row->group_count,
                'aggregates' => $figures,
                'children' => [],
                'rows' => [],
            ];
        }

        usort($nodes, fn ($a, $b) => strcmp($this->pathKey($a['path']), $this->pathKey($b['path'])));

        return $nodes;
    }

    /**
     * Hang the fetched rows on the deepest node they belong to. A group whose
     * rows are all beyond the row cap still appears, with its figures and no
     * rows under it.
     */
    protected function attachRows(array $tree, $rows, int $depth): array
    {
        $byPath = [];

        foreach ($rows as $row) {
            $path = [];

            for ($level = 0; $level < $depth; $level++) {
                $path[] = $row->{self::GROUP_ALIASES[$level]} ?? null;
            }

            $byPath[$this->pathKey($path)][] = $row;
        }

        return $this->fillRows($tree, $byPath, $depth);
    }

    /**
     * A group path as one key. A missing value and an empty one are different
     * groups - sharing a key would print every row of both under each.
     */
    private function pathKey(array $path): string
    {
        return implode('|', array_map(
            fn ($value) => $value === null ? "\0null" : (string) $value,
            $path
        ));
    }

    private function fillRows(array $nodes, array $byPath, int $depth): array
    {
        foreach ($nodes as $index => $node) {
            if (count($node['path']) === $depth) {
                $nodes[$index]['rows'] = $byPath[$this->pathKey($node['path'])] ?? [];
            }

            if (! empty($node['children'])) {
                $nodes[$index]['children'] = $this->fillRows($node['children'], $byPath, $depth);
            }
        }

        return $nodes;
    }

    /**
     * The export as a table of cells. A grouped export carries the same shape
     * the screen does - group headings, detail rows, subtotals and one total -
     * because an export without its subtotals is the half people re-do by hand.
     *
     * @param  callable(mixed, array): string  $cellFor  formats one row's cell
     * @return array<int, array<int, string>>
     */
    protected function exportTable(array $columns, $rows, array $groups, array $totals, callable $cellFor): array
    {
        if ($groups === []) {
            $table = [];

            foreach ($rows as $row) {
                $table[] = $this->exportRow($columns, $row, $cellFor);
            }

            return $table;
        }

        $table = [];

        foreach ($groups as $group) {
            $table = array_merge($table, $this->exportGroup($columns, $group, 0, $cellFor));
        }

        $table[] = $this->exportSummaryRow(
            $columns,
            'Total · '.number_format($totals['count']).' records',
            $totals['aggregates'] ?? []
        );

        return $table;
    }

    private function exportGroup(array $columns, array $group, int $depth, callable $cellFor): array
    {
        $indent = str_repeat('    ', $depth);
        $heading = $indent.$this->groupLabel($group['value']).' ('.number_format($group['count']).' records)';

        $table = [array_merge([$heading], array_fill(0, max(0, count($columns) - 1), ''))];

        if (! empty($group['children'])) {
            foreach ($group['children'] as $child) {
                $table = array_merge($table, $this->exportGroup($columns, $child, $depth + 1, $cellFor));
            }
        } else {
            foreach ($group['rows'] as $row) {
                $table[] = $this->exportRow($columns, $row, $cellFor);
            }
        }

        $table[] = $this->exportSummaryRow(
            $columns,
            $indent.'Subtotal · '.$this->groupLabel($group['value']),
            $group['aggregates'] ?? []
        );

        return $table;
    }

    private function exportRow(array $columns, $row, callable $cellFor): array
    {
        $cells = [];

        foreach ($columns as $column) {
            $cells[] = $cellFor($row, $column);
        }

        return $cells;
    }

    private function exportSummaryRow(array $columns, string $label, array $aggregates): array
    {
        return $this->summaryRowCells($columns, $aggregates, $label);
    }

    /**
     * A subtotal or total line, cell by cell: every summarised column carries
     * its figure, and the label takes the first column that has none. When
     * every column is summarised the label is dropped rather than a figure -
     * the group's own heading above it already says which group this is.
     *
     * @return array<int, string>
     */
    protected function summaryRowCells(array $columns, array $aggregates, string $label): array
    {
        $labelIndex = null;

        foreach ($columns as $index => $column) {
            if (! array_key_exists($column['field'], $aggregates)) {
                $labelIndex = $index;

                break;
            }
        }

        $cells = [];

        foreach ($columns as $index => $column) {
            if ($index === $labelIndex) {
                $cells[] = $label;

                continue;
            }

            $value = $aggregates[$column['field']] ?? null;
            $cells[] = $value === null ? '' : number_format((float) $value, 2);
        }

        return $cells;
    }

    /** The label a group is shown under; a missing value is not a blank row. */
    protected function groupLabel($value): string
    {
        return $value === null || $value === '' ? 'Not set' : (string) $value;
    }

    /**
     * Group values come back from the database, so compare them as text - but
     * a missing value is its own group, never the same as an empty one.
     */
    private function sameValue($a, $b): bool
    {
        if ($a === null || $b === null) {
            return $a === null && $b === null;
        }

        return (string) $a === (string) $b;
    }
}
