<?php

namespace App\Livewire\Concerns;

/**
 * The report query starts from customers and joins in whatever the selected
 * fields and filters need. Both report screens build that query, so the joins
 * live here once - they were two copies of the same code, with the same two
 * bugs:
 *
 *  - a table was joined once per reason to join it. 'departments.' also matches
 *    the tail of 'sub_departments.', so a report with a project field and a
 *    department (or sub-department) field joined projects two or three times
 *    and MySQL refused the whole query: "Not unique table/alias: 'projects'".
 *  - every one of these tables soft-deletes, and nothing filtered deleted rows,
 *    so a customer whose finance row had been replaced was reported twice, once
 *    with the old figures. The condition belongs in the ON clause rather than a
 *    WHERE: a customer whose only project is deleted keeps their row with empty
 *    project columns instead of dropping out of the report.
 */
trait JoinsReportTables
{
    /**
     * @param  array<int, string>  $fields  qualified "table.column" names
     * @param  array<int, string>  $extraTables  tables to join regardless
     */
    protected function applyReportJoins($query, array $fields, array $extraTables = []): void
    {
        $tables = $extraTables;

        foreach ($fields as $field) {
            if (is_string($field) && str_contains($field, '.')) {
                $tables[] = substr($field, 0, strpos($field, '.'));
            }
        }

        $needs = fn (string $table) => in_array($table, $tables, true);

        $joined = [];
        $join = function (string $table, string $first, string $second) use ($query, &$joined) {
            if (isset($joined[$table])) {
                return;
            }

            $joined[$table] = true;

            $query->leftJoin($table, function ($join) use ($table, $first, $second) {
                $join->on($first, '=', $second)->whereNull($table.'.deleted_at');
            });
        };

        // Departments and sub-departments hang off the project, so the project
        // join comes first and is shared with them.
        if ($needs('projects') || $needs('departments') || $needs('sub_departments')) {
            $join('projects', 'customers.id', 'projects.customer_id');
        }

        if ($needs('departments')) {
            $join('departments', 'projects.department_id', 'departments.id');
        }

        if ($needs('sub_departments')) {
            $join('sub_departments', 'projects.sub_department_id', 'sub_departments.id');
        }

        if ($needs('sales_partners')) {
            $join('sales_partners', 'customers.sales_partner_id', 'sales_partners.id');
        }

        if ($needs('module_types')) {
            $join('module_types', 'customers.module_type_id', 'module_types.id');
        }

        if ($needs('inverter_types')) {
            $join('inverter_types', 'customers.inverter_type_id', 'inverter_types.id');
        }

        if ($needs('customer_finances')) {
            $join('customer_finances', 'customers.id', 'customer_finances.customer_id');
        }
    }

    /** The tables a report touches: its columns plus the ones it filters on. */
    protected function reportJoinFields(array $fields, array $filters): array
    {
        foreach ($filters as $filter) {
            if (is_array($filter) && ! empty($filter['field'])) {
                $fields[] = $filter['field'];
            }
        }

        return $fields;
    }
}
