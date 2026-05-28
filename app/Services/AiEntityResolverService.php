<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiEntityResolverService
{
    public function __construct(private readonly AiSchemaService $schemaService)
    {
    }

    public function resolve(array $plan): array
    {
        if (($plan['intent'] ?? null) === 'unknown' || in_array(($plan['mode'] ?? null), ['unsupported', 'clarification_required'], true)) {
            return ['status' => 'skipped', 'plan' => $plan, 'message' => null, 'options' => []];
        }

        $filters = $plan['filters'] ?? [];

        foreach ($filters as $index => $filter) {
            if (! is_array($filter) || strtolower((string) ($filter['operator'] ?? '=')) !== '=' || ! is_scalar($filter['value'] ?? null)) {
                continue;
            }

            $table = $filter['table'] ?? $this->tableForColumn($plan, (string) ($filter['column'] ?? ''));
            $column = (string) ($filter['column'] ?? '');

            if (! $table || ! $this->shouldResolve($table, $column)) {
                continue;
            }

            $result = $this->resolveName($table, $column, (string) $filter['value']);

            if ($result['status'] !== 'resolved') {
                return array_merge($result, ['plan' => $plan]);
            }

            $filters[$index]['column'] = 'id';
            $filters[$index]['value'] = $result['id'];
            $filters[$index]['resolved_from'] = [
                'table' => $table,
                'column' => $column,
                'value' => $filter['value'],
            ];
        }

        $plan['filters'] = $filters;

        return ['status' => 'resolved', 'plan' => $plan, 'message' => null, 'options' => []];
    }

    private function resolveName(string $table, string $column, string $value): array
    {
        $actualTable = $this->schemaService->getTableConfig($table)['table'] ?? $table;

        if (! Schema::hasTable($actualTable) || ! Schema::hasColumn($actualTable, $column)) {
            return ['status' => 'skipped', 'id' => null, 'message' => null, 'options' => []];
        }

        $matches = DB::table($actualTable)
            ->select('id', $column)
            ->where($column, 'like', $this->flexiblePattern($value))
            ->limit(6)
            ->get();

        if ($matches->isEmpty()) {
            return [
                'status' => 'not_found',
                'id' => null,
                'message' => 'I could not find a matching CRM record for "' . $value . '".',
                'options' => [],
            ];
        }

        if ($matches->count() > 1) {
            return [
                'status' => 'clarification_required',
                'id' => null,
                'message' => 'Please select which record you mean: ' . $matches->pluck($column)->implode(', '),
                'options' => $matches->map(fn ($row) => ['id' => $row->id, 'label' => $row->{$column}])->values()->all(),
            ];
        }

        return [
            'status' => 'resolved',
            'id' => $matches->first()->id,
            'message' => null,
            'options' => [],
        ];
    }

    private function shouldResolve(string $table, string $column): bool
    {
        return in_array($table, ['customers', 'projects', 'departments', 'sub_departments', 'users', 'employees'], true)
            && in_array($column, ['name', 'project_name', 'first_name', 'last_name'], true);
    }

    private function tableForColumn(array $plan, string $column): ?string
    {
        foreach (($plan['tables'] ?? []) as $table) {
            if ($this->schemaService->isColumnAllowed($table, $column)) {
                return $table;
            }
        }

        return null;
    }

    private function flexiblePattern(string $value): string
    {
        $parts = preg_split('/[\s-]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

        return '%' . implode('%', $parts ?: [$value]) . '%';
    }
}
