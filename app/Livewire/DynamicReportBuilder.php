<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Models\SalesPartner;
use App\Models\OfficeCost;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DynamicReportExport;
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
    
    // Filter form fields
    public $filterField = '';
    public $filterOperator = '=';
    public $filterValue = '';
    
    // Calculated field form
    public $calcFieldName = '';
    public $calcFieldExpression = '';
    
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
            $baseFields = array_merge($baseFields, [
                'customer_finances.total_contract_value' => 'Total Contract Value',
                'customer_finances.adder_total' => 'Adder Total',
                'customer_finances.gross_profit' => 'Gross Profit',
                'customer_finances.net_profit' => 'Net Profit',
                'customer_finances.cost_per_watt' => 'Cost Per Watt',
            ]);
        }

        return $baseFields;
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
                    'customer_finances.total_contract_value',
                    'customer_finances.gross_profit'
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
    }
    
    public function removeCalculatedField($index)
    {
        unset($this->calculatedFields[$index]);
        $this->calculatedFields = array_values($this->calculatedFields);
    }
    
    public function generateReport()
    {
        $this->validate([
            'selectedFields' => 'required|array|min:1'
        ]);
        
        $query = $this->buildQuery();
        $this->reportData = $query->get();
        $this->reportColumns = $this->buildColumns();
        // \Log::info($this->reportData);
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
        
        // Select fields
        $selectFields = $this->selectedFields;
        
        // Add customer ID for calculated fields processing
        if (!in_array('customers.id', $selectFields)) {
            $selectFields[] = 'customers.id';
        }
        
        $query->select($selectFields);
        // \Log::info($query->toSql());
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
                $columns[] = [
                    'field' => $field,
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
            } catch (Exception $e) {
                return 'Error';
            }
        }
        
        return 'Invalid Expression';
    }
    
    private function getNestedProperty($object, $property)
    {
        // Try direct match (array or object)
        if (is_array($object) && isset($object[$property])) {
            return $object[$property];
        }
        if (is_object($object) && isset($object->{$property})) {
            return $object->{$property};
        }
        // Try last segment if property contains dot
        if (strpos($property, '.') !== false) {
            $parts = explode('.', $property);
            $last = end($parts);
            if (is_array($object) && isset($object[$last])) {
                return $object[$last];
            }
            if (is_object($object) && isset($object->{$last})) {
                return $object->{$last};
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
        return $value;
    }
    
    public function exportExcel()
    {
        if (empty($this->reportData)) {
            session()->flash('error', 'No data to export. Please generate a report first.');
            return;
        }
        
        $filename = $this->reportTypes[$this->reportType] . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(
            new DynamicReportExport($this->reportData, $this->reportColumns),
            $filename
        );
    }
    
    public function exportPdf()
    {
        if (empty($this->reportData)) {
            session()->flash('error', 'No data to export. Please generate a report first.');
            return;
        }
        
        $filename = $this->reportTypes[$this->reportType] . '_' . date('Y-m-d_H-i-s') . '.pdf';
        
        return Excel::download(
            new DynamicReportExport($this->reportData, $this->reportColumns),
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
        $this->setDefaultFields();
    }
    
    public function render()
    {
        return view('livewire.dynamic-report-builder');
    }
}
