<?php

namespace App\Livewire;

use App\Models\SavedReport;
use App\Models\Customer;
use App\Exports\DynamicReportExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Title;
use Maatwebsite\Excel\Facades\Excel;

class ReportRunner extends Component
{
    #[Title('Run Saved Reports')]

    public $selectedReportId = '';
    public $selectedReport = null;
    public $filterValues = [];
    public $filterStartDate = [];
    public $filterEndDate = [];
    public $reportData = [];
    public $reportColumns = [];
    public $showResults = false;

    // Available operators (same as DynamicReportBuilder)
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

    public function getUserReportsProperty()
    {
        return SavedReport::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updatedSelectedReportId()
    {
        if ($this->selectedReportId) {
            $this->selectedReport = SavedReport::find($this->selectedReportId);
            $this->filterValues = [];
            $this->reportData = [];
            $this->showResults = false;

            // Initialize filter values for each filter in the saved report
            if ($this->selectedReport && !empty($this->selectedReport->filters)) {
                foreach ($this->selectedReport->filters as $index => $filter) {
                    if (!in_array($filter['operator'], ['IS NULL', 'IS NOT NULL'])) {
                        $this->filterValues[$index] = '';
                        $this->filterStartDate[$index] = '';
                        $this->filterEndDate[$index] = '';
                    }
                }
            }
        }
    }

    public function runReport()
    {
        if (!$this->selectedReport) {
            session()->flash('error', 'Please select a report first.');
            return;
        }

        try {
            $this->getReportData();
            $this->showResults = true;
            session()->flash('success', 'Report executed successfully!');
        } catch (\Exception $e) {
            Log::error('Report execution failed', [
                'report_id' => $this->selectedReport->id,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Failed to execute report. Please try again.');
        }
    }

    private function getReportData()
    {
        $query = $this->buildQueryFromSavedReport();
        $this->reportData = $query->get();
        $this->reportColumns = $this->buildColumnsFromSavedReport();
        $this->processCalculatedFields();
    }

    private function buildQueryFromSavedReport()
    {
        $query = Customer::query();

        // Add joins based on selected fields
        $this->addJoins($query);

        // Apply user-provided filter values to the saved filters (only if value is provided)
        if (!empty($this->selectedReport->filters)) {
            foreach ($this->selectedReport->filters as $index => $filter) {
                // Skip filters that don't have values (except IS NULL/IS NOT NULL)
                if (in_array($filter['operator'], ['IS NULL', 'IS NOT NULL']) || 
                    !empty($this->filterValues[$index]) ||
                    (!empty($this->filterStartDate[$index]) && !empty($this->filterEndDate[$index]))) {
                    
                    // For BETWEEN operator with date fields, combine start and end dates
                    if (in_array($filter['operator'], ['BETWEEN', 'NOT BETWEEN']) && 
                        !empty($this->filterStartDate[$index]) && !empty($this->filterEndDate[$index])) {
                        $filterValue = $this->filterStartDate[$index] . ',' . $this->filterEndDate[$index];
                    } else {
                        $filterValue = $this->filterValues[$index] ?? $filter['value'];
                    }
                    
                    $this->applyFilter($query, $filter, $filterValue);
                }
            }
        }

        // Select fields with proper aliasing
        $selectFields = [];
        foreach ($this->selectedReport->selected_fields as $field) {
            $fieldName = str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field;
            if ($field === 'customer_finances.adders') {
                $selectFields[] = DB::raw('customer_finances.adders as adders_amount');
            } else {
                $selectFields[] = DB::raw("{$field} as {$fieldName}");
            }
        }

        // Add customer ID for calculated fields processing
        if (!in_array('customers.id', $this->selectedReport->selected_fields)) {
            $selectFields[] = DB::raw('customers.id as id');
        }

        $query->select($selectFields);
        
        return $query;
    }

    private function addJoins($query)
    {
        $fieldsString = implode(',', $this->selectedReport->selected_fields);
        
        // Include filter fields to ensure proper joins
        if (!empty($this->selectedReport->filters)) {
            $filterFields = array_column($this->selectedReport->filters, 'field');
            $fieldsString .= ',' . implode(',', $filterFields);
        }

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

        // Join customer finances
        if (str_contains($fieldsString, 'customer_finances.')) {
            $query->leftJoin('customer_finances', 'customers.id', '=', 'customer_finances.customer_id');
        }
    }

    private function applyFilter($query, $filter, $value)
    {
        switch ($filter['operator']) {
            case 'LIKE':
                $query->where($filter['field'], 'LIKE', '%' . $value . '%');
                break;
            case 'NOT LIKE':
                $query->where($filter['field'], 'NOT LIKE', '%' . $value . '%');
                break;
            case 'IN':
                $values = explode(',', $value);
                $query->whereIn($filter['field'], array_map('trim', $values));
                break;
            case 'NOT IN':
                $values = explode(',', $value);
                $query->whereNotIn($filter['field'], array_map('trim', $values));
                break;
            case 'BETWEEN':
                $values = explode(',', $value);
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
                $query->where($filter['field'], $filter['operator'], $value);
        }
    }

    private function buildColumnsFromSavedReport()
    {
        $columns = [];

        // Get available fields (same logic as DynamicReportBuilder)
        $availableFields = $this->getAvailableFields();

        foreach ($this->selectedReport->selected_fields as $field) {
            if ($field !== 'customers.id') {
                $fieldName = str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field;
                if ($field === 'customer_finances.adders') {
                    $fieldName = 'adders_amount';
                }
                $columns[] = [
                    'field' => $fieldName,
                    'name' => $availableFields[$field] ?? $field,
                    'type' => 'data'
                ];
            }
        }

        // Add calculated field columns
        if (!empty($this->selectedReport->calculated_fields)) {
            foreach ($this->selectedReport->calculated_fields as $calcField) {
                $columns[] = [
                    'field' => 'calc_' . Str::slug($calcField['name'], '_'),
                    'name' => $calcField['name'],
                    'type' => 'calculated'
                ];
            }
        }

        return $columns;
    }

    private function processCalculatedFields()
    {
        if (empty($this->selectedReport->calculated_fields)) {
            return;
        }

        $availableFields = $this->getAvailableFields();

        foreach ($this->reportData as $row) {
            foreach ($this->selectedReport->calculated_fields as $calcField) {
                $fieldKey = 'calc_' . Str::slug($calcField['name'], '_');
                $row->{$fieldKey} = $this->evaluateExpression($calcField['expression'], $row, $availableFields);
            }
        }
    }

    private function evaluateExpression($expression, $row, $availableFields)
    {
        $processedExpression = $expression;

        foreach ($availableFields as $field => $name) {
            $fieldValue = $this->getNestedProperty($row, $field);
            $processedExpression = str_replace(
                '{' . $field . '}',
                is_numeric($fieldValue) ? $fieldValue : 0,
                $processedExpression
            );
        }

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
        // Same logic as DynamicReportBuilder
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

        return null;
    }

    private function formatValue($value)
    {
        if (is_null($value)) {
            return '';
        }
        return (string) $value;
    }

    private function getAvailableFields()
    {
        // Same available fields as DynamicReportBuilder
        return [
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

            // Project fields
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

            // CustomerFinance fields
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
    }

    public function getFieldType($field)
    {
        $fieldTypes = [
            // Date fields
            'customers.created_at' => 'date',
            'customers.sold_date' => 'date',
            'projects.start_date' => 'date',
            'projects.end_date' => 'date',
            'projects.completion_date' => 'date',
            'projects.created_at' => 'date',
            'projects.updated_at' => 'date',
            'projects.ntp_approval_date' => 'date',
            'projects.meter_spot_requestd_date' => 'date',
            'projects.permitting_submittion_date' => 'date',
            'projects.permitting_approval_date' => 'date',
            'projects.hoa_approval_request_date' => 'date',
            'projects.hoa_approval_date' => 'date',
            'projects.solar_install_date' => 'date',
            'projects.battery_install_date' => 'date',
            'projects.mpu_install_date' => 'date',
            'projects.rough_inspection_date' => 'date',
            'projects.final_inspection_date' => 'date',
            'projects.pto_submission_date' => 'date',
            'projects.pto_approval_date' => 'date',
            'projects.coc_packet_mailed_out_date' => 'date',
            'projects.fire_inspection_date' => 'date',
            'customer_finances.created_at' => 'date',
            'customer_finances.updated_at' => 'date',
            
            // Number fields
            'customers.panel_qty' => 'number',
            'customers.inverter_qty' => 'number',
            'projects.budget' => 'number',
            'sales_partners.commission_rate' => 'number',
            'module_types.wattage' => 'number',
            'inverter_types.wattage' => 'number',
            'customer_finances.contract_amount' => 'number',
            'customer_finances.redline_costs' => 'number',
            'customer_finances.adders' => 'number',
            'customer_finances.commission' => 'number',
            'customer_finances.dealer_fee_amount' => 'number',
            'projects.actual_material_cost' => 'number',
            'projects.actual_labor_cost' => 'number',
            'projects.actual_permit_fee' => 'number',
            'projects.actual_office_cost' => 'number',
            
            // Dropdown fields
            // 'customers.state' => 'dropdown',
            'departments.name' => 'dropdown',
            'projects.department_id' => 'dropdown',
            'projects.sub_department_id' => 'dropdown',
            'sub_departments.name' => 'dropdown',
            'sales_partners.name' => 'dropdown',
            'module_types.name' => 'dropdown',
            'inverter_types.name' => 'dropdown',
        ];
        
        return $fieldTypes[$field] ?? 'text';
    }
    
    public function getDropdownOptions($field)
    {
        switch($field) {

            case 'projects.department_id':
            case 'departments.name':
                return \App\Models\Department::pluck('name', 'id')->toArray();
            case 'projects.sub_department_id':
            case 'sub_departments.name' :
                return \App\Models\SubDepartment::pluck('name', 'name')->toArray();
            case 'sales_partners.name':
                return \App\Models\SalesPartner::pluck('name', 'name')->toArray();
            case 'module_types.name':
                return \App\Models\ModuleType::pluck('name', 'name')->toArray();
            case 'inverter_types.name':
                return \App\Models\InverterType::pluck('name', 'name')->toArray();
            default:
                return [];
        }
    }

    public function exportExcel()
    {
        if (!$this->selectedReport) {
            session()->flash('error', 'Please select a report first.');
            return;
        }

        $this->getReportData();
        
        $results = [];
        foreach ($this->reportData as $row) {
            $rowData = [];
            foreach ($this->reportColumns as $column) {
                $value = $this->getNestedProperty($row, $column['field']);
                if ($column['type'] === 'calculated') {
                    $value = is_object($row) ? ($row->{$column['field']} ?? 'N/A') : (isset($row[$column['field']]) ? $row[$column['field']] : 'N/A');
                }
                if (is_numeric($value) && !is_string($value)) {
                    $value = number_format($value, (is_float($value + 0) && floor($value + 0) != ($value + 0)) ? 2 : 0);
                }
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $value = '-';
                }
                $rowData[] = $value;
            }
            $results[] = $rowData;
        }

        $filename = ($this->selectedReport->name ?? 'Report') . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new DynamicReportExport($results, $this->reportColumns),
            $filename
        );
    }

    public function exportPdf()
    {
        if (!$this->selectedReport) {
            session()->flash('error', 'Please select a report first.');
            return;
        }

        $this->getReportData();

        $results = [];
        foreach ($this->reportData as $row) {
            $rowData = [];
            foreach ($this->reportColumns as $column) {
                $value = $this->getNestedProperty($row, $column['field']);
                if ($column['type'] === 'calculated') {
                    $value = is_object($row) ? ($row->{$column['field']} ?? 'N/A') : (isset($row[$column['field']]) ? $row[$column['field']] : 'N/A');
                }
                if (is_numeric($value) && !is_string($value)) {
                    $value = number_format($value, (is_float($value + 0) && floor($value + 0) != ($value + 0)) ? 2 : 0);
                }
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    $value = '-';
                }
                $rowData[] = $value;
            }
            $results[] = $rowData;
        }

        $filename = ($this->selectedReport->name ?? 'Report') . '_' . date('Y-m-d_H-i-s') . '.pdf';

        return Excel::download(
            new DynamicReportExport($results, $this->reportColumns),
            $filename,
            \Maatwebsite\Excel\Excel::DOMPDF
        );
    }

    public function deleteReport($reportId)
    {
        $report = SavedReport::where('id', $reportId)
            ->where('user_id', auth()->id())
            ->first();

        if ($report) {
            $report->delete();
            session()->flash('success', 'Report deleted successfully!');

            // Reset if the deleted report was selected
            if ($this->selectedReportId == $reportId) {
                $this->reset(['selectedReportId', 'selectedReport', 'filterValues', 'reportData', 'showResults']);
            }
        }
    }

    public function render()
    {
        return view('livewire.report-runner');
    }
}
