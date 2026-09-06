<?php

namespace App\Livewire;

use App\Exports\DynamicReportExport;
use App\Livewire\Concerns\DescribesReportFields;
use App\Livewire\Concerns\GroupsReportRows;
use App\Livewire\Concerns\JoinsReportTables;
use App\Models\Customer;
use App\Models\Project;
use App\Models\SavedReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class DynamicReportBuilder extends Component
{
    use DescribesReportFields;
    use GroupsReportRows;
    use JoinsReportTables;

    #[Title('Dynamic Report Builder')]
    public $reportType = '';

    public $selectedFields = [];

    public $filters = [];

    public $calculatedFields = [];

    public $reportData = [];

    public $reportColumns = [];

    public $showResults = false;

    /**
     * The field the report's rows are grouped by, if any. A grouped report
     * shows a header, its records and a subtotal per group, and one total for
     * the report - which is how these numbers are read out loud.
     */
    public $groupBy = '';

    /** The second level, shown inside each first-level group. */
    public $groupBy2 = '';

    /**
     * How each numeric column is summarised: field => sum|avg|min|max|count,
     * or 'none' to leave it out of the subtotals. Summed unless said otherwise.
     *
     * @var array<string, string>
     */
    public $columnSummaries = [];

    /** @var array<int, array> the group tree behind the preview */
    public $previewGroups = [];

    /** @var array{count: int, aggregates: array} */
    public $previewTotals = ['count' => 0, 'aggregates' => []];

    /** Typed into the field list's search box. */
    public $fieldSearch = '';

    /**
     * The preview under the builder is the report itself, run small: the same
     * query, capped, so picking a column or a filter shows what it does to the
     * data instead of describing it.
     */
    #[Locked]
    public $previewLimit = 25;

    public $previewCount = 0;

    public $previewError = '';

    public $reportName = '';

    // Edit functionality
    public $editingReportId = null;

    public $isEditing = false;

    // Filter form fields
    public $filterField = '';

    public $filterOperator = '=';

    public $filterValue = '';

    /**
     * The picks from a lookup dropdown. It is a multi-select - "projects in
     * Permitting or Installation" is one filter, not two reports - so the
     * value arrives as a list and is stored comma separated for IN / NOT IN.
     */
    public $filterValueList = [];

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
        'override' => 'Override Report',
    ];

    // Available operators
    #[Locked]
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
        'IS NOT NULL' => 'Is Not Empty',
    ];

    public function mount()
    {
        // Check if editing a report
        $editId = request()->query('edit');
        if ($editId) {
            $this->loadReportForEdit($editId);
        } else {
            $this->reportType = 'profitability';
            $this->setDefaultFields();
        }

        $this->refreshPreview();
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
            'projects.ahj' => 'AHJ',
            'projects.ahj_website_url' => 'AHJ Website URL',
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
            'inverter_types.inverter_efficiency_rating' => 'Inverter Efficiency Rating (%)',

            // CustomerFinance fields (all fields from migration)
            'customer_finances.id' => 'Customer Finance ID',
            'customer_finances.customer_id' => 'Customer Finance Customer ID',
            'customer_finances.finance_option_id' => 'Finance Option ID',
            'customer_finances.loan_term_id' => 'Loan Term ID',
            'customer_finances.loan_apr_id' => 'Loan APR ID',
            'finance_options.name' => 'Finance Option',
            'loan_terms.year' => 'Loan Term (Years)',
            'loan_aprs.apr' => 'Loan APR (%)',
            'customer_finances.contract_amount' => 'Contract Amount',
            'customer_finances.redline_costs' => 'Redline Costs',
            'customer_finances.adders' => 'Adders',
            'customer_finances.commission' => 'Commission',
            'customer_finances.dealer_fee' => 'Dealer Fee',
            'customer_finances.dealer_fee_amount' => 'Dealer Fee Amount',
            'customer_finances.third_party_credit' => 'Third Party Credit',
            'customer_finances.customer_portion' => 'Customer Portion',
            'customer_finances.module_type_cost' => 'Module Type Cost',
            'customer_finances.inverter_base_cost' => 'Inverter Base Cost',
            'customer_finances.total_overwrite_base_price' => 'Total Overwrite Base Price',
            'customer_finances.total_overwrite_panel_price' => 'Total Overwrite Panel Price',
            'customer_finances.created_at' => 'Customer Finance Created Date',
            'customer_finances.updated_at' => 'Customer Finance Updated Date',
        ];

        // The holdback amount sits behind its own permission on the project
        // page, so the report follows the same rule instead of publishing it
        // to everyone who can run a report.
        if (auth()->user()?->can('Holdback Amount')) {
            $baseFields['customer_finances.holdback_amount'] = 'Holdback Amount';
        }

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
            'reportType' => $this->reportType,
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
        $this->refreshPreview();
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
                    'projects.actual_material_cost',
                    'projects.actual_labor_cost',
                ];
                break;
            case 'forecast':
                $this->selectedFields = [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.sold_date',
                    'projects.project_name',
                    'sales_partners.name',
                ];
                break;
            case 'override':
                $this->selectedFields = [
                    'customers.first_name',
                    'customers.last_name',
                    'customers.sold_date',
                    'sales_partners.name',
                    'projects.project_name',
                ];
                break;
        }
    }

    /**
     * The field list, grouped the way someone looks for a field, and narrowed
     * by the search box. Empty groups drop out so a search shows only hits.
     *
     * @return array<string, array<string, string>>
     */
    public function getFieldGroupsProperty(): array
    {
        $groups = [
            'Customer' => ['customers.'],
            'Project' => ['projects.'],
            'Sales Partner' => ['sales_partners.'],
            'Department & Lane' => ['departments.', 'sub_departments.'],
            'Equipment' => ['module_types.', 'inverter_types.'],
            'Finance' => ['customer_finances.', 'finance_options.', 'loan_terms.', 'loan_aprs.'],
        ];

        $search = trim(mb_strtolower((string) $this->fieldSearch));
        $grouped = [];

        foreach ($groups as $label => $prefixes) {
            foreach ($this->availableFields as $field => $name) {
                foreach ($prefixes as $prefix) {
                    if (! str_starts_with($field, $prefix)) {
                        continue;
                    }

                    if ($search === ''
                        || str_contains(mb_strtolower($name), $search)
                        || str_contains(mb_strtolower($field), $search)) {
                        $grouped[$label][$field] = $name;
                    }

                    break;
                }
            }
        }

        return $grouped;
    }

    /** How many fields the search is choosing from, for the search box's label. */
    public function getFieldCountProperty(): int
    {
        return count($this->availableFields);
    }

    /**
     * Run the report small and show it. Called after every change that alters
     * what the report would return, so the preview is never stale.
     */
    public function refreshPreview(): void
    {
        $this->previewError = '';
        $fields = $this->permittedFields($this->selectedFields);

        if ($fields === []) {
            $this->reportData = [];
            $this->reportColumns = [];
            $this->previewCount = 0;
            $this->showResults = false;

            return;
        }

        try {
            $query = $this->buildQuery();
            $this->previewCount = (clone $query)->count();
            $this->reportData = $query->limit($this->previewLimit)->get();
            $this->reportColumns = $this->buildColumns();
            $this->processCalculatedFields();
            $this->buildGroups($fields);
            $this->showResults = true;
        } catch (\Throwable $th) {
            Log::error('Report preview failed: '.$th->getMessage());
            $this->reportData = [];
            $this->reportColumns = [];
            $this->previewCount = 0;
            $this->previewGroups = [];
            $this->previewTotals = ['count' => 0, 'sums' => []];
            $this->showResults = false;
            $this->previewError = 'This combination of fields could not be previewed.';
        }
    }

    /**
     * Group the fetched rows and work out the subtotals. The sums come from
     * their own GROUP BY over every matching record, not from the capped rows
     * on screen, so a subtotal is never a subtotal of 25.
     */
    private function buildGroups(array $fields): void
    {
        $groupFields = $this->groupFields();

        if ($groupFields === []) {
            $this->previewGroups = [];
            $this->previewTotals = ['count' => 0, 'aggregates' => []];

            return;
        }

        $aggregates = $this->aggregatedColumns($fields, $this->columnSummaries);
        $base = fn () => $this->baseQuery();

        $this->previewGroups = $this->attachRows(
            $this->groupTree($base, $groupFields, $aggregates),
            $this->reportData,
            count($groupFields)
        );
        $this->previewTotals = $this->reportTotals($base, $aggregates);
    }

    /**
     * Change how one numeric column is summarised. 'none' is a choice, not an
     * absence - forgetting it would put the column straight back to summed.
     */
    public function setColumnSummary(string $field, string $function): void
    {
        if (array_key_exists($function, static::summaryFunctions()) || $function === 'none') {
            $this->columnSummaries[$field] = $function;
        } else {
            unset($this->columnSummaries[$field]);
        }

        $this->refreshPreview();
    }

    /** The function a column is summarised by, for the outline's picker. */
    public function summaryFor(string $field): string
    {
        return $this->columnSummaries[$field] ?? 'sum';
    }

    /** The summary functions offered in the outline's per-column picker. */
    public function summaryOptions(): array
    {
        return static::summaryFunctions();
    }

    /** A summary figure as it is shown: two decimals, or a dash for nothing. */
    public function formatSummary($value): string
    {
        return $value === null ? '—' : number_format((float) $value, 2);
    }

    /** A group's value as it is shown, for the subtotal line. */
    public function groupLabelFor($value): string
    {
        return $this->groupLabel($value);
    }

    /** True while this column carries a figure in the subtotal rows. */
    public function isSummarised(string $field): bool
    {
        return $this->getFieldType($field) === 'number'
            && array_key_exists($this->summaryFor($field), static::summaryFunctions());
    }

    /** How a group is headed in the table: the field of its level, then its value. */
    public function groupHeading($value, int $depth = 0): string
    {
        $field = $this->groupFields()[$depth] ?? '';
        $label = $this->availableFields[$field] ?? $field;

        return $label.': '.$this->groupLabel($value);
    }

    public function updatedGroupBy(): void
    {
        // Dropping the first level drops the second with it: a report cannot be
        // grouped by its inner field alone.
        if ($this->groupBy === '') {
            $this->groupBy2 = '';
        }

        $this->refreshPreview();
    }

    public function updatedGroupBy2(): void
    {
        $this->refreshPreview();
    }

    /** True while the preview is showing fewer rows than the report holds. */
    public function getPreviewTruncatedProperty(): bool
    {
        return $this->previewCount > count($this->reportData);
    }

    public function moveFieldUp(int $index): void
    {
        if ($index > 0 && isset($this->selectedFields[$index])) {
            [$this->selectedFields[$index - 1], $this->selectedFields[$index]]
                = [$this->selectedFields[$index], $this->selectedFields[$index - 1]];
            $this->refreshPreview();
        }
    }

    public function moveFieldDown(int $index): void
    {
        if (isset($this->selectedFields[$index + 1])) {
            [$this->selectedFields[$index + 1], $this->selectedFields[$index]]
                = [$this->selectedFields[$index], $this->selectedFields[$index + 1]];
            $this->refreshPreview();
        }
    }

    public function addField($field)
    {
        if (! in_array($field, $this->selectedFields)) {
            $this->selectedFields[] = $field;
        }
    }

    public function removeField($index)
    {
        unset($this->selectedFields[$index]);
        $this->selectedFields = array_values($this->selectedFields);
        $this->refreshPreview();
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

        $this->refreshPreview();
    }

    /** True while the value input for the filter being added is the picker. */
    public function filterFieldUsesPicker(): bool
    {
        return $this->filterUsesPicker([
            'field' => $this->filterField,
            'operator' => $this->filterOperator,
        ]);
    }

    public function addFilter()
    {
        $usesPicker = $this->filterFieldUsesPicker();

        $this->validate([
            'filterField' => 'required',
            'filterOperator' => 'required',
            $usesPicker ? 'filterValueList' : 'filterValue' => 'required_unless:filterOperator,IS NULL,IS NOT NULL',
        ], [
            'filterValueList.required_unless' => 'Please pick at least one value.',
        ]);

        [$operator, $value, $label] = $usesPicker
            ? $this->pickedFilterValue()
            : [$this->filterOperator, $this->filterValue, $this->dropdownLabel($this->filterField, $this->filterValue)];

        $this->filters[] = [
            'field' => $this->filterField,
            'operator' => $operator,
            'value' => $value,
            'field_name' => $this->availableFields[$this->filterField] ?? $this->filterField,
            // A lookup filter files ids; the chip and the runner show the names
            // they were picked by.
            'value_label' => $label,
        ];

        $this->reset(['filterField', 'filterOperator', 'filterValue', 'filterValueList']);
        $this->refreshPreview();
    }

    /**
     * The picked values as one filter: several of them mean IN (or NOT IN),
     * because "= 3,7" matches nothing and would look like missing data.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function pickedFilterValue(): array
    {
        $values = array_values(array_filter(
            (array) $this->filterValueList,
            fn ($value) => $value !== '' && $value !== null
        ));

        $operator = match (true) {
            count($values) > 1 && $this->filterOperator === '=' => 'IN',
            count($values) > 1 && $this->filterOperator === '!=' => 'NOT IN',
            default => $this->filterOperator,
        };

        $labels = array_map(
            fn ($value) => $this->dropdownLabel($this->filterField, $value) ?? $value,
            $values
        );

        return [$operator, implode(',', $values), implode(', ', $labels)];
    }

    /** A new field means the old pick no longer applies. */
    public function updatedFilterField(): void
    {
        $this->reset(['filterValue', 'filterValueList']);
    }

    public function removeFilter($index)
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
        $this->refreshPreview();
    }

    public function addToCalcBuilder()
    {
        // First operation: require initial field, operator, and right side
        if (empty($this->calcExpressionBuilder)) {
            if ($this->calcInitialField && $this->builderOperator && (($this->builderField2 && ! $this->builderValue2) || (! $this->builderField2 && $this->builderValue2 !== ''))) {
                $part2 = $this->builderField2 ? '{'.$this->builderField2.'}' : $this->builderValue2;
                $expression = ' '.$this->builderOperator.' '.$part2;
                $this->calcExpressionBuilder[] = $expression;
                $this->updateCalcExpressionPreview();
                // Reset for next operation
                $this->builderOperator = '+';
                $this->builderField2 = '';
                $this->builderValue2 = '';
            }
        } else {
            // Subsequent operations: only operator and right side
            if ($this->builderOperator && (($this->builderField2 && ! $this->builderValue2) || (! $this->builderField2 && $this->builderValue2 !== ''))) {
                $part2 = $this->builderField2 ? '{'.$this->builderField2.'}' : $this->builderValue2;
                $expression = ' '.$this->builderOperator.' '.$part2;
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
        $this->calcExpressionPreview = $this->calcInitialField ? '{'.$this->calcInitialField.'}' : '';
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

    public function loadReportForEdit($reportId)
    {
        $report = SavedReport::where('id', $reportId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $report) {
            session()->flash('error', 'Report not found or access denied.');

            return redirect()->route('dynamic-report-builder');
        }

        $this->editingReportId = $report->id;
        $this->isEditing = true;
        $this->reportName = $report->name;
        $this->reportType = $report->report_type;
        $this->selectedFields = $report->selected_fields ?? [];
        $this->groupBy = $report->group_by ?? '';
        $this->groupBy2 = $report->group_by_2 ?? '';
        $this->columnSummaries = $report->summaries ?? [];
        $this->filters = $report->filters ?? [];
        $this->calculatedFields = $report->calculated_fields ?? [];
        $this->refreshPreview();
    }

    public function useCalcBuilder()
    {
        $this->calcFieldExpression = $this->calcExpressionPreview;
    }

    public function addCalculatedField()
    {
        $this->validate([
            'calcFieldName' => 'required|string|max:255',
            'calcFieldExpression' => 'required|string',
        ]);

        $this->calculatedFields[] = [
            'name' => $this->calcFieldName,
            'expression' => $this->calcFieldExpression,
        ];

        $this->reset(['calcFieldName', 'calcFieldExpression']);
        $this->clearCalcBuilder();
        $this->refreshPreview();
    }

    public function removeCalculatedField($index)
    {
        unset($this->calculatedFields[$index]);
        $this->calculatedFields = array_values($this->calculatedFields);
        $this->refreshPreview();
    }

    public function saveReport()
    {
        if ($this->isEditing) {
            $this->updateReport();

            return;
        }

        try {
            $this->validate([
                'reportName' => 'required|string|max:255',
                'selectedFields' => 'required|array|min:1',
            ], [
                'reportName.required' => 'Report Name field is required.',
                'selectedFields.required' => 'Please select at least one field.',
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
                'bindings' => $bindings,
            ];

            // Save the report
            SavedReport::create([
                'name' => $this->reportName,
                'report_type' => $this->reportName,
                'selected_fields' => $this->permittedFields($this->selectedFields),
                'group_by' => $this->groupFields()[0] ?? null,
                'group_by_2' => $this->groupFields()[1] ?? null,
                'summaries' => $this->columnSummaries ?: null,
                'filters' => $this->permittedFilters(),
                'calculated_fields' => $this->calculatedFields,
                'query' => json_encode($queryWithBindings),
                'user_id' => auth()->user()->id,
            ]);

            session()->flash('success', 'Report saved successfully!');
        } catch (\Throwable $th) {
            Log::error('Error saving report: '.$th->getMessage());
            session()->flash('error', 'Failed to save report. Please try again.');
        }

        // Reset form
        $this->reset(['reportName']);
    }

    public function updateReport()
    {
        try {
            $this->validate([
                'reportName' => 'required|string|max:255',
                'selectedFields' => 'required|array|min:1',
            ], [
                'reportName.required' => 'Report Name field is required.',
                'selectedFields.required' => 'Please select at least one field.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors.');
            throw $e;
        }

        try {
            $report = SavedReport::where('id', $this->editingReportId)
                ->where('user_id', auth()->id())
                ->first();

            if (! $report) {
                session()->flash('error', 'Report not found or access denied.');

                return;
            }

            // Build query to save the SQL for later execution
            $query = $this->buildQuery();
            $sql = $query->toSql();
            $bindings = $query->getBindings();

            // Combine SQL and bindings for storage
            $queryWithBindings = [
                'sql' => $sql,
                'bindings' => $bindings,
            ];

            // Update the report
            $report->update([
                'name' => $this->reportName,
                'report_type' => $this->reportName,
                'selected_fields' => $this->permittedFields($this->selectedFields),
                'group_by' => $this->groupFields()[0] ?? null,
                'group_by_2' => $this->groupFields()[1] ?? null,
                'summaries' => $this->columnSummaries ?: null,
                'filters' => $this->permittedFilters(),
                'calculated_fields' => $this->calculatedFields,
                'query' => json_encode($queryWithBindings),
            ]);

            session()->flash('success', 'Report updated successfully!');

            return redirect()->route('report-runner');
        } catch (\Throwable $th) {
            Log::error('Error updating report: '.$th->getMessage());
            session()->flash('error', 'Failed to update report. Please try again.');
        }
    }

    public function cancelEdit()
    {
        return redirect()->route('report-runner');
    }

    /**
     * Kept for anything that asks for the report explicitly; it runs the same
     * preview the builder shows, so there is only ever one way rows are built.
     */
    public function generateReport()
    {
        try {
            $this->validate([
                'selectedFields' => 'required|array|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please select at least one field.');
            throw $e;
        }

        $this->refreshPreview();
    }

    /**
     * $selectedFields and $filters are public Livewire properties, so the
     * browser owns them - and a selected field is spliced into the query as
     * raw SQL ("<field> as <alias>"), which is a way into the database for
     * anything that is not a real column. Both are therefore matched against
     * the field list this component itself offers before the query is built;
     * anything else is dropped rather than queried.
     */
    private function permittedFields(array $fields): array
    {
        $allowed = array_keys($this->availableFields);

        return array_values(array_filter(
            $fields,
            fn ($field) => is_string($field) && in_array($field, $allowed, true)
        ));
    }

    private function permittedFilters(): array
    {
        $allowed = array_keys($this->availableFields);
        $operators = array_keys($this->operators);

        return array_values(array_filter(
            $this->filters,
            fn ($filter) => is_array($filter)
                && in_array($filter['field'] ?? null, $allowed, true)
                && in_array($filter['operator'] ?? null, $operators, true)
        ));
    }

    /** Joins and filters, with nothing selected yet - the report's population. */
    private function baseQuery()
    {
        $query = Customer::query();

        // Add joins based on report type and selected fields
        $this->addJoins($query);

        // Add filters
        foreach ($this->permittedFilters() as $filter) {
            $this->applyFilter($query, $filter);
        }

        // Add date filters based on report type
        $this->addDateFilters($query);

        return $query;
    }

    /**
     * The fields the rows are grouped by, outermost first - only the ones this
     * user may report on, and never the same field twice.
     *
     * @return array<int, string>
     */
    public function groupFields(): array
    {
        $fields = $this->permittedFields([$this->groupBy, $this->groupBy2]);

        return array_values(array_unique(array_slice($fields, 0, self::MAX_GROUP_LEVELS)));
    }

    /** The first grouping level, or '' - kept for the query's row ordering. */
    private function permittedGroupField(): string
    {
        return $this->groupFields()[0] ?? '';
    }

    private function buildQuery()
    {
        $query = $this->baseQuery();

        // Select fields with proper aliasing
        $selectFields = [];
        foreach ($this->permittedFields($this->selectedFields) as $field) {
            $selectFields[] = DB::raw("{$field} as {$this->fieldAlias($field)}");
        }

        // A grouped report carries each level's value on every row, so the rows
        // can be laid out under their group headers.
        foreach ($this->groupFields() as $depth => $groupField) {
            $selectFields[] = DB::raw($groupField.' as '.self::GROUP_ALIASES[$depth]);
            $query->orderBy(DB::raw($groupField));
        }

        // Add customer ID for calculated fields processing
        if (! in_array('customers.id', $this->selectedFields)) {
            $selectFields[] = DB::raw('customers.id as id');
        }

        // Debug: Log the query details
        Log::info('Build Query Details', [
            'selectedFields' => $this->selectedFields,
            'selectFields' => $selectFields,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $query->select($selectFields);

        // Load necessary relations for the selected fields
        $relationsToLoad = [];
        $fieldsString = implode(',', $this->permittedFields($this->selectedFields));

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

        if (! empty($relationsToLoad)) {
            $query->with($relationsToLoad);
        }

        return $query;
    }

    private function addJoins($query)
    {
        $fields = array_merge($this->permittedFields($this->selectedFields), $this->groupFields());

        $this->applyReportJoins(
            $query,
            $this->reportJoinFields($fields, $this->permittedFilters()),
            // A profitability report is about the finance figures whether or
            // not one of its columns was picked.
            $this->reportType === 'profitability' ? ['customer_finances'] : []
        );
    }

    private function applyFilter($query, $filter)
    {
        switch ($filter['operator']) {
            case 'LIKE':
                $query->where($filter['field'], 'LIKE', '%'.$filter['value'].'%');
                break;
            case 'NOT LIKE':
                $query->where($filter['field'], 'NOT LIKE', '%'.$filter['value'].'%');
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

        foreach ($this->permittedFields($this->selectedFields) as $field) {
            if ($field !== 'customers.id') { // Skip ID column used for calculations
                $columns[] = [
                    'field' => $this->fieldAlias($field),
                    'name' => $this->availableFields[$field] ?? $field,
                    'type' => 'data',
                    'numeric' => $this->getFieldType($field) === 'number',
                ];
            }
        }

        // Add calculated field columns
        foreach ($this->calculatedFields as $calcField) {
            $columns[] = [
                'field' => 'calc_'.Str::slug($calcField['name'], '_'),
                'name' => $calcField['name'],
                'type' => 'calculated',
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
                $fieldKey = 'calc_'.Str::slug($calcField['name'], '_');
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
                '{'.$field.'}',
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
        if (! is_string($string)) {
            return false;
        }

        json_decode($string);

        return json_last_error() == JSON_ERROR_NONE;
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
                    $formatted[] = $key.': '.$this->formatComplexValue($item);
                } else {
                    $formatted[] = $key.': '.(string) $item;
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

    /**
     * One cell of an export, formatted as the screen formats it.
     */
    private function exportCell($row, array $column): string
    {
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
            $value = is_object($row) ? ($row->{$column['field']} ?? 'N/A') : ($row[$column['field']] ?? 'N/A');
        }

        if (is_numeric($value) && ! is_string($value)) {
            $value = number_format($value, (is_float($value + 0) && floor($value + 0) != ($value + 0)) ? 2 : 0);
        }

        if ($value === null || (is_string($value) && trim($value) === '')) {
            $value = '-';
        }

        return (string) $value;
    }

    /**
     * The whole report for an export - every row, not the capped preview on
     * screen, with the group headings and subtotals it is read with.
     */
    private function exportRows(): array
    {
        $fields = $this->permittedFields($this->selectedFields);

        if ($fields === []) {
            return [];
        }

        $rows = $this->buildQuery()->get();
        $columns = $this->buildColumns();

        foreach ($this->calculatedFields as $calcField) {
            $key = 'calc_'.Str::slug($calcField['name'], '_');

            foreach ($rows as $row) {
                $row->{$key} = $this->evaluateExpression($calcField['expression'], $row);
            }
        }

        $groups = [];
        $totals = ['count' => 0, 'aggregates' => []];

        if ($this->groupFields() !== []) {
            $aggregates = $this->aggregatedColumns($fields, $this->columnSummaries);
            $base = fn () => $this->baseQuery();
            $groups = $this->attachRows(
                $this->groupTree($base, $this->groupFields(), $aggregates),
                $rows,
                count($this->groupFields())
            );
            $totals = $this->reportTotals($base, $aggregates);
        }

        return $this->exportTable($columns, $rows, $groups, $totals, fn ($row, $column) => $this->exportCell($row, $column));
    }

    public function exportExcel()
    {
        $results = $this->exportRows();

        if ($results === []) {
            session()->flash('error', 'No data to export. Pick a field first.');

            return;
        }

        return Excel::download(
            new DynamicReportExport($results, $this->buildColumns()),
            $this->exportFilename('xlsx')
        );
    }

    public function exportPdf()
    {
        $results = $this->exportRows();

        if ($results === []) {
            session()->flash('error', 'No data to export. Pick a field first.');

            return;
        }

        return Excel::download(
            new DynamicReportExport($results, $this->buildColumns()),
            $this->exportFilename('pdf'),
            \Maatwebsite\Excel\Excel::DOMPDF
        );
    }

    /** The file an export is offered as: the report's own name, then the date. */
    private function exportFilename(string $extension): string
    {
        $name = trim((string) $this->reportName) ?: 'Report';

        return Str::slug($name, '_').'_'.date('Y-m-d_H-i-s').'.'.$extension;
    }

    public function clearAll()
    {
        $this->selectedFields = [];
        $this->groupBy = '';
        $this->groupBy2 = '';
        $this->columnSummaries = [];
        $this->filters = [];
        $this->calculatedFields = [];
        $this->reportData = [];
        $this->showResults = false;
        $this->clearCalcBuilder();
        $this->setDefaultFields();
        $this->refreshPreview();
    }

    public function render()
    {
        return view('livewire.dynamic-report-builder');
    }
}
