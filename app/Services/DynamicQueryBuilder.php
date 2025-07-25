<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DynamicQueryBuilder
{
    protected $query;
    protected $joins = [];

    public function __construct()
    {
        $this->query = Customer::query();
    }

    public function buildQuery(array $selectedFields, array $filters = [], string $reportType = 'profitability')
    {
        // Reset query
        $this->query = Customer::query();
        $this->joins = [];

        // Add necessary joins based on selected fields
        $this->addJoinsForFields($selectedFields, $reportType);

        // Apply filters
        foreach ($filters as $filter) {
            $this->applyFilter($filter);
        }

        // Add report-specific constraints
        $this->addReportConstraints($reportType);

        // Select the required fields
        $this->query->select(array_unique($selectedFields));

        return $this->query;
    }

    protected function addJoinsForFields(array $fields, string $reportType)
    {
        $fieldsString = implode(',', $fields);

        // Always join projects if project fields are selected or it's required for the report
        if (str_contains($fieldsString, 'projects.') || in_array($reportType, ['profitability', 'forecast', 'override'])) {
            $this->addJoin('projects', 'customers.id', '=', 'projects.customer_id');
        }

        // Join sales partners
        if (str_contains($fieldsString, 'sales_partners.')) {
            $this->addJoin('sales_partners', 'customers.sales_partner_id', '=', 'sales_partners.id');
        }

        // Join departments
        if (str_contains($fieldsString, 'departments.')) {
            $this->addJoin('projects', 'customers.id', '=', 'projects.customer_id');
            $this->addJoin('departments', 'projects.department_id', '=', 'departments.id');
        }

        if (str_contains($fieldsString, 'sub_departments.')) {
            $this->addJoin('projects', 'customers.id', '=', 'projects.customer_id');
            $this->addJoin('sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id');
        }

        // Join module and inverter types
        if (str_contains($fieldsString, 'module_types.')) {
            $this->addJoin('module_types', 'customers.module_type_id', '=', 'module_types.id');
        }

        if (str_contains($fieldsString, 'inverter_types.')) {
            $this->addJoin('inverter_types', 'customers.inverter_type_id', '=', 'inverter_types.id');
        }

        // Join customer finances for profitability report
        if ($reportType === 'profitability' || str_contains($fieldsString, 'customer_finances.')) {
            $this->addJoin('customer_finances', 'customers.id', '=', 'customer_finances.customer_id');
        }

        // Join users for sales partner user information
        if (str_contains($fieldsString, 'users.') || $reportType === 'override') {
            $this->addJoin('projects', 'customers.id', '=', 'projects.customer_id');
            $this->addJoin('users', 'projects.sales_partner_user_id', '=', 'users.id');
        }
    }

    protected function addJoin(string $table, string $first, string $operator, string $second, string $type = 'left')
    {
        $joinKey = "{$table}_{$first}_{$second}";
        
        if (!in_array($joinKey, $this->joins)) {
            $this->query->leftJoin($table, $first, $operator, $second);
            $this->joins[] = $joinKey;
        }
    }

    protected function applyFilter(array $filter)
    {
        switch ($filter['operator']) {
            case 'LIKE':
                $this->query->where($filter['field'], 'LIKE', '%' . $filter['value'] . '%');
                break;
            case 'NOT LIKE':
                $this->query->where($filter['field'], 'NOT LIKE', '%' . $filter['value'] . '%');
                break;
            case 'IN':
                $values = array_map('trim', explode(',', $filter['value']));
                $this->query->whereIn($filter['field'], $values);
                break;
            case 'NOT IN':
                $values = array_map('trim', explode(',', $filter['value']));
                $this->query->whereNotIn($filter['field'], $values);
                break;
            case 'BETWEEN':
                $values = array_map('trim', explode(',', $filter['value']));
                if (count($values) === 2) {
                    $this->query->whereBetween($filter['field'], [$values[0], $values[1]]);
                }
                break;
            case 'IS NULL':
                $this->query->whereNull($filter['field']);
                break;
            case 'IS NOT NULL':
                $this->query->whereNotNull($filter['field']);
                break;
            default:
                $this->query->where($filter['field'], $filter['operator'], $filter['value']);
        }
    }

    protected function addReportConstraints(string $reportType)
    {
        switch ($reportType) {
            case 'profitability':
                // Add any default constraints for profitability report
                $this->query->whereHas('project', function ($q) {
                    $q->whereNotNull('solar_install_date');
                });
                break;
            case 'forecast':
                // Add constraints for forecast report
                $this->query->whereNotNull('sold_date')
                           ->orderBy('sold_date', 'ASC');
                break;
            case 'override':
                // Add constraints for override report
                $this->query->whereNotNull('sold_date')
                           ->orderBy('sold_date', 'ASC');
                break;
        }
    }

    public function getFieldGroups()
    {
        return [
            'Customer Fields' => [
                'customers.id' => 'Customer ID',
                'customers.first_name' => 'Customer First Name',
                'customers.last_name' => 'Customer Last Name',
                'customers.email' => 'Customer Email',
                'customers.phone' => 'Customer Phone',
                'customers.city' => 'Customer City',
                'customers.state' => 'Customer State',
                'customers.zipcode' => 'Customer Zip Code',
                'customers.sold_date' => 'Sold Date',
                'customers.panel_qty' => 'Panel Quantity',
                'customers.inverter_qty' => 'Inverter Quantity',
                'customers.created_at' => 'Customer Created Date',
            ],
            'Project Fields' => [
                'projects.id' => 'Project ID',
                'projects.project_name' => 'Project Name',
                'projects.solar_install_date' => 'Solar Install Date',
                'projects.created_at' => 'Project Created Date',
            ],
            'Sales Partner Fields' => [
                'sales_partners.name' => 'Sales Partner Name',
                'sales_partners.commission_rate' => 'Commission Rate',
            ],
            'Department Fields' => [
                'departments.name' => 'Department Name',
                'sub_departments.name' => 'Sub Department Name',
            ],
            'Type Fields' => [
                'module_types.name' => 'Module Type',
                'module_types.wattage' => 'Module Wattage',
                'inverter_types.name' => 'Inverter Type',
                'inverter_types.wattage' => 'Inverter Wattage',
            ],
            'Finance Fields' => [
                'customer_finances.total_contract_value' => 'Total Contract Value',
                'customer_finances.adder_total' => 'Adder Total',
                'customer_finances.gross_profit' => 'Gross Profit',
                'customer_finances.net_profit' => 'Net Profit',
                'customer_finances.cost_per_watt' => 'Cost Per Watt',
            ],
        ];
    }

    public function getDefaultFieldsForReportType(string $reportType)
    {
        switch ($reportType) {
            case 'profitability':
                return [
                    'customers.first_name',
                    'customers.last_name',
                    'sales_partners.name',
                    'projects.solar_install_date',
                    'customer_finances.total_contract_value',
                    'customer_finances.gross_profit'
                ];
            case 'forecast':
                return [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.sold_date',
                    'projects.project_name',
                    'sales_partners.name'
                ];
            case 'override':
                return [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.sold_date',
                    'sales_partners.name',
                    'projects.project_name'
                ];
            default:
                return [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.email'
                ];
        }
    }
}
