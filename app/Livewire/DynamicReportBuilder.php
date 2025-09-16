<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Models\SalesPartner;
use App\Models\OfficeCost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicReportExport;
use App\Models\SavedReport;
use Illuminate\Support\Str;

class DynamicReportBuilder extends Component
{
    #[Title('Dynamic Report Builder')]

    public $reportType = '';
    public $selectedFields = [];
    public $filters = [];
    public $calculatedFields = [];
    public $reportData = [];
    public $reportColumns = [];
    public $showResults = false;

    public $reportName = '';

    // Filter form fields
    public $filterField = '';
    public $filterOperator = '=';
    public $filterValue = '';

    // Calculated field form
    public $calcFieldName = '';
    public $calcFieldExpression = '';

    // Calculated field builder UI
    public $calcInitialField = '';
    public $builderOperator = '+';
    public $builderField2 = '';
    public $builderValue2 = '';
    public $calcExpressionPreview = '';
    public $calcExpressionBuilder = [];

    // Available report types
    public $reportTypes = [
        'profitability' => 'Profitability Report',
        'forecast' => 'Forecast Report',
        'override' => 'Override Report'
    ];

    // Available operators
    public $operators = [
        '=' => 'Equals',
        '!=' => 'Not Equals',
        '>' => 'Greater Than',
        '>=' => 'Greater Than or Equal',
        '<' => 'Less Than',
        '<=' => 'Less Than or Equal',
        'LIKE' => 'Contains',
        'NOT LIKE' => 'Does Not Contain',
        'IN' => 'In List',
        'NOT IN' => 'Not In List',
        'BETWEEN' => 'Between',
        'IS NULL' => 'Is Empty',
        'IS NOT NULL' => 'Is Not Empty'
    ];

    public function mount()
    {
        $this->reportType = 'profitability';

        // Set default fields based on report type
        $this->setDefaultFields();
    }

    public function getAvailableFieldsProperty()
    {
        $baseFields = [
            // Customer fields
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

            // Project fields (all fields from all migrations)
            'projects.id' => 'Project ID',
            'projects.customer_id' => 'Project Customer ID',
            'projects.department_id' => 'Department ID',
            'projects.sub_department_id' => 'Sub Department ID',
            'projects.project_name' => 'Project Name',
            'projects.start_date' => 'Start Date',
            'projects.end_date' => 'End Date',
            'projects.completion_date' => 'Completion Date',
            'projects.budget' => 'Budget',
            'projects.description' => 'Description',
            'projects.created_at' => 'Project Created Date',
            'projects.updated_at' => 'Project Updated Date',
            'projects.utility_company' => 'Utility Company',
            'projects.ntp_approval_date' => 'NTP Approval Date',
            'projects.site_survey_link' => 'Site Survey Link',
            'projects.hoa' => 'HOA',
            'projects.hoa_phone_number' => 'HOA Phone Number',
            'projects.adders_approve_checkbox' => 'Adders Approve Checkbox',
            'projects.mpu_required' => 'MPU Required',
            'projects.meter_spot_requestd_date' => 'Meter Spot Request Date',
            'projects.meter_spot_requestd_number' => 'Meter Spot Request Number',
            'projects.meter_spot_result' => 'Meter Spot Result',
            'projects.permitting_submittion_date' => 'Permitting Submission Date',
            'projects.permitting_approval_date' => 'Permitting Approval Date',
            'projects.hoa_approval_request_date' => 'HOA Approval Request Date',
            'projects.hoa_approval_date' => 'HOA Approval Date',
            'projects.solar_install_date' => 'Solar Install Date',
            'projects.battery_install_date' => 'Battery Install Date',
            'projects.mpu_install_date' => 'MPU Install Date',
            'projects.rough_inspection_date' => 'Rough Inspection Date',
            'projects.final_inspection_date' => 'Final Inspection Date',
            'projects.pto_submission_date' => 'PTO Submission Date',
            'projects.pto_approval_date' => 'PTO Approval Date',
            'projects.coc_packet_mailed_out_date' => 'COC Packet Mailed Out Date',
            'projects.sales_partner_user_id' => 'Sales Partner User ID',
            'projects.overwrite_base_price' => 'Overwrite Base Price',
            'projects.overwrite_panel_price' => 'Overwrite Panel Price',
            'projects.placards_ordered' => 'Placards Ordered',
            'projects.placards_note' => 'Placards Note',
            'projects.fire_review_required' => 'Fire Review Required',
            'projects.fire_inspection_date' => 'Fire Inspection Date',
            'projects.actual_material_cost' => 'Actual Material Cost',
            'projects.actual_labor_cost' => 'Actual Labor Cost',
            'projects.actual_permit_fee' => 'Actual Permit Fee',
            'projects.actual_office_cost' => 'Actual Office Cost',

            // Sales Partner fields
            'sales_partners.name' => 'Sales Partner Name',
            'sales_partners.commission_rate' => 'Commission Rate',

            // Department fields
            'departments.name' => 'Department Name',
            'sub_departments.name' => 'Sub Department Name',

            // Module & Inverter Types
            'module_types.name' => 'Module Type',
            'module_types.wattage' => 'Module Wattage',
            'inverter_types.name' => 'Inverter Type',
            'inverter_types.wattage' => 'Inverter Wattage',

            // CustomerFinance fields (all fields from migration)
            'customer_finances.id' => 'Customer Finance ID',
            'customer_finances.customer_id' => 'Customer Finance Customer ID',
            'customer_finances.finance_option_id' => 'Finance Option',
            'customer_finances.loan_term_id' => 'Loan Term',
            'customer_finances.loan_apr_id' => 'Loan APR',
            'customer_finances.contract_amount' => 'Contract Amount',
            'customer_finances.redline_costs' => 'Redline Costs',
            'customer_finances.adders' => 'Adders',
            'customer_finances.commission' => 'Commission',
            'customer_finances.dealer_fee' => 'Dealer Fee',
            'customer_finances.dealer_fee_amount' => 'Dealer Fee Amount',
            'customer_finances.created_at' => 'Customer Finance Created Date',
            'customer_finances.updated_at' => 'Customer Finance Updated Date',
        ];

        // Add finance fields for profitability report
        if ($this->reportType === 'profitability') {
            // $baseFields = array_merge($baseFields, [
            //     'customer_finances.total_contract_value' => 'Total Contract Value',
            //     'customer_finances.adder_total' => 'Adder Total',
            //     'customer_finances.gross_profit' => 'Gross Profit',
            //     'customer_finances.net_profit' => 'Net Profit',
            //     'customer_finances.cost_per_watt' => 'Cost Per Watt',
            // ]);
        }

        return $baseFields;
    }

    public function getSelectedFieldsCountProperty()
    {
        return count($this->selectedFields);
    }

    public function getSelectedFieldsDebugProperty()
    {
        return [
            'count' => count($this->selectedFields),
            'fields' => $this->selectedFields,
            'reportType' => $this->reportType
        ];
    }

    public function updatedReportType()
    {
        $this->selectedFields = [];
        $this->filters = [];
        $this->calculatedFields = [];
        $this->reportData = [];
        $this->showResults = false;

        // Set default fields based on report type
        $this->setDefaultFields();

        // Reset calculated fields builder
        $this->clearCalcBuilder();
    }

    private function setDefaultFields()
    {
        switch ($this->reportType) {
            case 'profitability':
                $this->selectedFields = [
                    'customers.first_name',
                    'customers.last_name',
                    'sales_partners.name',
                    'projects.solar_install_date',
                    'customer_finances.contract_amount',
                    'customer_finances.dealer_fee',
                    'customer_finances.commission',
                    'customer_finances.adders',
                    'customer_finances.redline_costs',
                    "projects.actual_material_cost",
                    "projects.actual_labor_cost",
                ];
                break;
            case 'forecast':
                $this->selectedFields = [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.sold_date',
                    'projects.project_name',
                    'sales_partners.name'
                ];
                break;
            case 'override':
                $this->selectedFields = [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.sold_date',
                    'sales_partners.name',
                    'projects.project_name'
                ];
                break;
        }
    }

    public function addField($field)
    {
        if (!in_array($field, $this->selectedFields)) {
            $this->selectedFields[] = $field;
        }
    }

    public function removeField($index)
    {
        unset($this->selectedFields[$index]);
        $this->selectedFields = array_values($this->selectedFields);
    }

    public function toggleField($field)
    {
        if (in_array($field, $this->selectedFields)) {
            // Remove field
            $this->selectedFields = array_values(array_filter($this->selectedFields, function ($f) use ($field) {
                return $f !== $field;
            }));
        } else {
            // Add field
            $this->selectedFields[] = $field;
        }

        // Reset report data when fields change
        $this->reportData = [];
        $this->showResults = false;
    }

    public function addFilter()
    {
        $this->validate([
            'filterField' => 'required',
            'filterOperator' => 'required',
            'filterValue' => 'required_unless:filterOperator,IS NULL,IS NOT NULL'
        ]);

        $this->filters[] = [
            'field' => $this->filterField,
            'operator' => $this->filterOperator,
            'value' => $this->filterValue,
            'field_name' => $this->availableFields[$this->filterField] ?? $this->filterField
        ];

        $this->reset(['filterField', 'filterOperator', 'filterValue']);
    }

    public function removeFilter($index)
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
    }

    public function addToCalcBuilder()
    {
        // First operation: require initial field, operator, and right side
        if (empty($this->calcExpressionBuilder)) {
            if ($this->calcInitialField && $this->builderOperator && (($this->builderField2 && !$this->builderValue2) || (!$this->builderField2 && $this->builderValue2 !== ''))) {
                $part2 = $this->builderField2 ? '{' . $this->builderField2 . '}' : $this->builderValue2;
                $expression = ' ' . $this->builderOperator . ' ' . $part2;
                $this->calcExpressionBuilder[] = $expression;
                $this->updateCalcExpressionPreview();
                // Reset for next operation
                $this->builderOperator = '+';
                $this->builderField2 = '';
                $this->builderValue2 = '';
            }
        } else {
            // Subsequent operations: only operator and right side
            if ($this->builderOperator && (($this->builderField2 && !$this->builderValue2) || (!$this->builderField2 && $this->builderValue2 !== ''))) {
                $part2 = $this->builderField2 ? '{' . $this->builderField2 . '}' : $this->builderValue2;
                $expression = ' ' . $this->builderOperator . ' ' . $part2;
                $this->calcExpressionBuilder[] = $expression;
                $this->updateCalcExpressionPreview();
                // Reset for next operation
                $this->builderOperator = '+';
                $this->builderField2 = '';
                $this->builderValue2 = '';
            }
        }
    }

    private function updateCalcExpressionPreview()
    {
        $this->calcExpressionPreview = $this->calcInitialField ? '{' . $this->calcInitialField . '}' : '';
        foreach ($this->calcExpressionBuilder as $part) {
            $this->calcExpressionPreview .= $part;
        }
    }

    public function removeLastCalcBuilder()
    {
        array_pop($this->calcExpressionBuilder);
        $this->updateCalcExpressionPreview();
    }

    public function clearCalcBuilder()
    {
        $this->calcInitialField = '';
        $this->calcExpressionBuilder = [];
        $this->calcExpressionPreview = '';
        $this->builderOperator = '+';
        $this->builderField2 = '';
        $this->builderValue2 = '';
    }

    public function useCalcBuilder()
    {
        $this->calcFieldExpression = $this->calcExpressionPreview;
    }

    public function addCalculatedField()
    {
        $this->validate([
            'calcFieldName' => 'required|string|max:255',
            'calcFieldExpression' => 'required|string'
        ]);

        $this->calculatedFields[] = [
            'name' => $this->calcFieldName,
            'expression' => $this->calcFieldExpression
        ];

        $this->reset(['calcFieldName', 'calcFieldExpression']);
        $this->clearCalcBuilder();
    }

    public function removeCalculatedField($index)
    {
        unset($this->calculatedFields[$index]);
        $this->calculatedFields = array_values($this->calculatedFields);
    }

    public function saveReport()
    {
        try {
            $this->validate([
                'reportName' => 'required|string|max:255',
                'selectedFields' => 'required|array|min:1'
            ], [
                'reportName.required' => 'Report Name field is required.',
                'selectedFields.required' => 'Please select at least one field.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors.');
            throw $e;
        }

        try {
            // Build query to save the SQL for later execution
            $query = $this->buildQuery();
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            
            // Combine SQL and bindings for storage
            $queryWithBindings = [
                'sql' => $sql,
                'bindings' => $bindings
            ];
            
            
            // Save the report
            SavedReport::create([
                'name' => $this->reportName,
                'report_type' => $this->reportName,
                'selected_fields' => $this->selectedFields,
                'filters' => $this->filters,
                'calculated_fields' => $this->calculatedFields,
                'query' => json_encode($queryWithBindings),
                'user_id' => auth()->user()->id
            ]);
    
            session()->flash('success', 'Report saved successfully!');
        } catch (\Throwable $th) {
            Log::error('Error saving report: ' . $th->getMessage());
            session()->flash('error', 'Failed to save report. Please try again.');
        }
        
        // Reset form
        $this->reset(['reportName']);
    }

    public function generateReport()
    {
        try {
            $this->validate([
                'selectedFields' => 'required|array|min:1'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please select at least one field.');
            throw $e;
        }

        $query = $this->buildQuery();
        $this->reportData = $query->get();
        $this->reportColumns = $this->buildColumns();


        $this->showResults = true;

        // Process calculated fields
        $this->processCalculatedFields();
    }

    private function buildQuery()
    {
        $query = Customer::query();

        // Add joins based on report type and selected fields
        $this->addJoins($query);

        // Add filters
        foreach ($this->filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        // Add date filters based on report type
        $this->addDateFilters($query);

        // Select fields with proper aliasing
        $selectFields = [];
        foreach ($this->selectedFields as $field) {
            // Create alias to match the column field names (without table prefix)
            $fieldName = str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field;
            // Special case for customer_finances.adders
            if ($field === 'customer_finances.adders') {
                $selectFields[] = DB::raw('customer_finances.adders as adders_amount');
            } else {
                $selectFields[] = DB::raw("{$field} as {$fieldName}");
            }
        }

        // Add customer ID for calculated fields processing
        if (!in_array('customers.id', $this->selectedFields)) {
            $selectFields[] = DB::raw('customers.id as id');
        }

        // Debug: Log the query details
        Log::info('Build Query Details', [
            'selectedFields' => $this->selectedFields,
            'selectFields' => $selectFields,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $query->select($selectFields);

        // Load necessary relations for the selected fields
        $relationsToLoad = [];
        $fieldsString = implode(',', $this->selectedFields);

        if (str_contains($fieldsString, 'sales_partners.')) {
            $relationsToLoad[] = 'salespartner';
        }
        if (str_contains($fieldsString, 'projects.')) {
            $relationsToLoad[] = 'project';
        }
        if (str_contains($fieldsString, 'customer_finances.')) {
            $relationsToLoad[] = 'finances';
        }
        if (str_contains($fieldsString, 'departments.')) {
            $relationsToLoad[] = 'project.department';
        }
        if (str_contains($fieldsString, 'sub_departments.')) {
            $relationsToLoad[] = 'project.subdepartment';
        }
        if (str_contains($fieldsString, 'module_types.')) {
            $relationsToLoad[] = 'module';
        }
        if (str_contains($fieldsString, 'inverter_types.')) {
            $relationsToLoad[] = 'inverter';
        }

        if (!empty($relationsToLoad)) {
            $query->with($relationsToLoad);
        }

        return $query;
    }

    private function addJoins($query)
    {
        $fieldsString = implode(',', $this->selectedFields);

        // Always join projects if project fields are selected
        if (str_contains($fieldsString, 'projects.')) {
            $query->leftJoin('projects', 'customers.id', '=', 'projects.customer_id');
        }

        // Join sales partners
        if (str_contains($fieldsString, 'sales_partners.')) {
            $query->leftJoin('sales_partners', 'customers.sales_partner_id', '=', 'sales_partners.id');
        }

        // Join departments
        if (str_contains($fieldsString, 'departments.')) {
            $query->leftJoin('projects', 'customers.id', '=', 'projects.customer_id')
                ->leftJoin('departments', 'projects.department_id', '=', 'departments.id');
        }

        if (str_contains($fieldsString, 'sub_departments.')) {
            $query->leftJoin('projects', 'customers.id', '=', 'projects.customer_id')
                ->leftJoin('sub_departments', 'projects.sub_department_id', '=', 'sub_departments.id');
        }

        // Join module and inverter types
        if (str_contains($fieldsString, 'module_types.')) {
            $query->leftJoin('module_types', 'customers.module_type_id', '=', 'module_types.id');
        }

        if (str_contains($fieldsString, 'inverter_types.')) {
            $query->leftJoin('inverter_types', 'customers.inverter_type_id', '=', 'inverter_types.id');
        }

        // Join customer finances for profitability report
        if ($this->reportType === 'profitability' || str_contains($fieldsString, 'customer_finances.')) {
            $query->leftJoin('customer_finances', 'customers.id', '=', 'customer_finances.customer_id');
        }

        // Debug: Log the joins being applied
        Log::info('Applied Joins:', [
            'fieldsString' => $fieldsString,
            'hasProjects' => str_contains($fieldsString, 'projects.'),
            'hasSalesPartners' => str_contains($fieldsString, 'sales_partners.'),
            'hasCustomerFinances' => str_contains($fieldsString, 'customer_finances.'),
            'reportType' => $this->reportType
        ]);
    }

    private function applyFilter($query, $filter)
    {
        switch ($filter['operator']) {
            case 'LIKE':
                $query->where($filter['field'], 'LIKE', '%' . $filter['value'] . '%');
                break;
            case 'NOT LIKE':
                $query->where($filter['field'], 'NOT LIKE', '%' . $filter['value'] . '%');
                break;
            case 'IN':
                $values = explode(',', $filter['value']);
                $query->whereIn($filter['field'], array_map('trim', $values));
                break;
            case 'NOT IN':
                $values = explode(',', $filter['value']);
                $query->whereNotIn($filter['field'], array_map('trim', $values));
                break;
            case 'BETWEEN':
                $values = explode(',', $filter['value']);
                if (count($values) === 2) {
                    $query->whereBetween($filter['field'], [trim($values[0]), trim($values[1])]);
                }
                break;
            case 'IS NULL':
                $query->whereNull($filter['field']);
                break;
            case 'IS NOT NULL':
                $query->whereNotNull($filter['field']);
                break;
            default:
                $query->where($filter['field'], $filter['operator'], $filter['value']);
        }
    }

    private function addDateFilters($query)
    {
        // You can add default date filters based on report type here
        // For now, leaving this flexible for user-defined filters
    }

    private function buildColumns()
    {
        $columns = [];

        foreach ($this->selectedFields as $field) {
            if ($field !== 'customers.id') { // Skip ID column used for calculations
                // Extract the field name without table prefix for better data mapping
                $fieldName = str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field;
                // Special case for customer_finances.adders
                if ($field === 'customer_finances.adders') {
                    $fieldName = 'adders_amount';
                }
                $columns[] = [
                    'field' => $fieldName, // Use field name without table prefix
                    'name' => $this->availableFields[$field] ?? $field,
                    'type' => 'data'
                ];
            }
        }

        // Add calculated field columns
        foreach ($this->calculatedFields as $calcField) {
            $columns[] = [
                'field' => 'calc_' . Str::slug($calcField['name'], '_'),
                'name' => $calcField['name'],
                'type' => 'calculated'
            ];
        }

        return $columns;
    }

    private function processCalculatedFields()
    {
        if (empty($this->calculatedFields)) {
            return;
        }

        foreach ($this->reportData as $row) {
            foreach ($this->calculatedFields as $calcField) {
                $fieldKey = 'calc_' . Str::slug($calcField['name'], '_');
                $row->{$fieldKey} = $this->evaluateExpression($calcField['expression'], $row);
            }
        }
    }

    private function evaluateExpression($expression, $row)
    {
        // Simple expression evaluator
        // Replace field names with actual values
        $processedExpression = $expression;

        foreach ($this->availableFields as $field => $name) {
            $fieldValue = $this->getNestedProperty($row, $field);
            $processedExpression = str_replace(
                '{' . $field . '}',
                is_numeric($fieldValue) ? $fieldValue : 0,
                $processedExpression
            );
        }

        // Basic safety check - only allow basic math operations
        if (preg_match('/^[0-9+\-*\/.() ]+$/', $processedExpression)) {
            try {
                return eval("return $processedExpression;");
            } catch (\Exception $e) {
                return 'Error';
            }
        }

        return 'Invalid Expression';
    }

    private function getNestedProperty($object, $property)
    {
        // Try direct match (array or object)
        if (is_array($object) && isset($object[$property])) {
            return $this->formatValue($object[$property]);
        }
        if (is_object($object) && isset($object->{$property})) {
            return $this->formatValue($object->{$property});
        }
        // Special case for adders_amount alias
        if ($property === 'adders_amount' && isset($object->adders_amount)) {
            return $this->formatValue($object->adders_amount);
        }
        // Handle relations for specific field types
        if ($property === 'name' && isset($object->salespartner)) {
            return $this->formatValue($object->salespartner->name ?? null);
        }
        if (str_starts_with($property, 'solar_install_date') && isset($object->project)) {
            return $this->formatValue($object->project->solar_install_date ?? null);
        }
        if (str_starts_with($property, 'contract_amount') && isset($object->finances)) {
            return $this->formatValue($object->finances->contract_amount ?? null);
        }
        if (str_starts_with($property, 'dealer_fee') && isset($object->finances)) {
            return $this->formatValue($object->finances->dealer_fee ?? null);
        }
        if (str_starts_with($property, 'commission') && isset($object->finances)) {
            return $this->formatValue($object->finances->commission ?? null);
        }
        if (str_starts_with($property, 'redline_costs') && isset($object->finances)) {
            return $this->formatValue($object->finances->redline_costs ?? null);
        }
        if (str_starts_with($property, 'actual_material_cost') && isset($object->project)) {
            return $this->formatValue($object->project->actual_material_cost ?? null);
        }
        if (str_starts_with($property, 'actual_labor_cost') && isset($object->project)) {
            return $this->formatValue($object->project->actual_labor_cost ?? null);
        }

        // Try last segment if property contains dot
        if (strpos($property, '.') !== false) {
            $parts = explode('.', $property);
            $last = end($parts);
            if (is_array($object) && isset($object[$last])) {
                return $this->formatValue($object[$last]);
            }
            if (is_object($object) && isset($object->{$last})) {
                return $this->formatValue($object->{$last});
            }
        }

        // Fallback to original nested logic
        $parts = explode('.', $property);
        $value = $object;
        foreach ($parts as $part) {
            if (is_object($value) && isset($value->{$part})) {
                $value = $value->{$part};
            } elseif (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }
        return $this->formatValue($value);
    }

    private function formatValue($value)
    {
        if (is_null($value)) {
            return '';
        }

        if (is_string($value)) {
            // Check if it's JSON
            if ($this->isJson($value)) {
                return $this->formatJsonValue($value);
            }
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return $this->formatComplexValue($value);
        }

        return (string) $value;
    }

    private function isJson($string)
    {
        if (!is_string($string)) {
            return false;
        }

        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function formatJsonValue($jsonString)
    {
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $jsonString; // Return original if not valid JSON
        }

        return $this->formatComplexValue($data);
    }

    private function formatComplexValue($value)
    {
        if (is_array($value)) {
            if (empty($value)) {
                return '';
            }

            // If it's a sequential array, format as list
            if (array_keys($value) === range(0, count($value) - 1)) {
                $formatted = [];
                foreach ($value as $item) {
                    if (is_array($item) || is_object($item)) {
                        $formatted[] = $this->formatComplexValue($item);
                    } else {
                        $formatted[] = (string) $item;
                    }
                }
                return implode(', ', $formatted);
            }

            // If it's an associative array, format as key-value pairs
            $formatted = [];
            foreach ($value as $key => $item) {
                if (is_array($item) || is_object($item)) {
                    $formatted[] = $key . ': ' . $this->formatComplexValue($item);
                } else {
                    $formatted[] = $key . ': ' . (string) $item;
                }
            }
            return implode('; ', $formatted);
        }

        if (is_object($value)) {
            // Handle Eloquent models and other objects
            if (method_exists($value, 'toArray')) {
                return $this->formatComplexValue($value->toArray());
            }

            // Convert object to array
            $array = (array) $value;
            return $this->formatComplexValue($array);
        }

        return (string) $value;
    }

    public function exportExcel()
    {
        if (empty($this->reportData)) {
            session()->flash('error', 'No data to export. Please generate a report first.');
            return;
        }

        $results = [];
        foreach ($this->reportData as $row) {
            $rowData = [];
            foreach ($this->reportColumns as $column) {
                $value = $this->getNestedProperty($row, $column['field']);
                if ($column['field'] === 'adders_amount') {
                    // Always flatten adders_amount from finances
                    if (is_array($row) && isset($row['finances']['adders'])) {
                        $value = $row['finances']['adders'];
                    } elseif (is_object($row) && isset($row->finances) && isset($row->finances->adders)) {
                        $value = $row->finances->adders;
                    }
                }
                if ($column['type'] === 'calculated') {
                    $value = is_object($row) ? ($row->{$column['field']} ?? 'N/A') : (isset($row[$column['field']]) ? $row[$column['field']] : 'N/A');
                }
                if (is_numeric($value) && !is_string($value)) {
                    $value = number_format($value, (is_float($value + 0) && floor($value + 0) != ($value + 0)) ? 2 : 0);
                }
                // If value is null, empty string, or only whitespace, show '-'
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $value = '-';
                }
                $rowData[] = $value;
            }
            $results[] = $rowData;
        }

        $filename = $this->reportTypes[$this->reportType] . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new DynamicReportExport($results, $this->reportColumns),
            $filename
        );
    }

    public function exportPdf()
    {
        if (empty($this->reportData)) {
            session()->flash('error', 'No data to export. Please generate a report first.');
            return;
        }

        $results = [];
        foreach ($this->reportData as $row) {
            $rowData = [];
            foreach ($this->reportColumns as $column) {
                $value = $this->getNestedProperty($row, $column['field']);
                if ($column['field'] === 'adders_amount') {
                    // Always flatten adders_amount from finances
                    if (is_array($row) && isset($row['finances']['adders'])) {
                        $value = $row['finances']['adders'];
                    } elseif (is_object($row) && isset($row->finances) && isset($row->finances->adders)) {
                        $value = $row->finances->adders;
                    }
                }
                if ($column['type'] === 'calculated') {
                    $value = is_object($row) ? ($row->{$column['field']} ?? 'N/A') : (isset($row[$column['field']]) ? $row[$column['field']] : 'N/A');
                }
                if (is_numeric($value) && !is_string($value)) {
                    $value = number_format($value, (is_float($value + 0) && floor($value + 0) != ($value + 0)) ? 2 : 0);
                }
                // If value is null, empty string, or only whitespace, show '-'
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $value = '-';
                }
                $rowData[] = $value;
            }
            $results[] = $rowData;
        }

        $filename = $this->reportTypes[$this->reportType] . '_' . date('Y-m-d_H-i-s') . '.pdf';

        return Excel::download(
            new DynamicReportExport($results, $this->reportColumns),
            $filename,
            \Maatwebsite\Excel\Excel::DOMPDF
        );
    }

    public function clearAll()
    {
        $this->selectedFields = [];
        $this->filters = [];
        $this->calculatedFields = [];
        $this->reportData = [];
        $this->showResults = false;
        $this->clearCalcBuilder();
        $this->setDefaultFields();
    }

    public function render()
    {
        return view('livewire.dynamic-report-builder');
    }
}
