<div>
    @section('title', 'Dynamic Report Builder')
    
    <!-- Header Section -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center justify-content-between border-bottom">
                        <h3 class="fw-bold flex-fill mb-0 mt-sm-0">
                            <i class="icofont-chart-bar-graph me-2"></i>
                            Dynamic Report Builder
                        </h3>
                        <div class="btn-group">
                            <button wire:click="clearAll" class="btn btn-outline-secondary">
                                <i class="icofont-refresh me-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Configuration Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="icofont-settings me-2"></i>Report Configuration
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Report Type Selection -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="reportType" class="form-label fw-bold">Report Type</label>
                                <select wire:model.live="reportType" class="form-select" id="reportType">
                                    @foreach($reportTypes as $key => $name)
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info mb-0 mt-4">
                                    <i class="icofont-info-circle me-2"></i>
                                    <strong>{{ $reportTypes[$reportType] }}</strong> - 
                                    @if($reportType === 'profitability')
                                        Analyze customer profitability with financial metrics
                                    @elseif($reportType === 'forecast')
                                        View customer sales forecast based on sold dates
                                    @else
                                        Review override costs and adjustments
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Field Selection Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">
                                    <i class="icofont-columns me-2"></i>Select Fields to Display
                                </h6>
                                
                                <!-- Available Fields -->
                                <div class="row">
                                    @php
                                        $fieldGroups = [
                                            'Customer Fields' => collect($this->availableFields)->filter(fn($name, $field) => str_starts_with($field, 'customers.')),
                                            'Project Fields' => collect($this->availableFields)->filter(fn($name, $field) => str_starts_with($field, 'projects.')),
                                            'Sales Partner Fields' => collect($this->availableFields)->filter(fn($name, $field) => str_starts_with($field, 'sales_partners.')),
                                            'Department Fields' => collect($this->availableFields)->filter(fn($name, $field) => str_starts_with($field, 'departments.') || str_starts_with($field, 'sub_departments.')),
                                            'Type Fields' => collect($this->availableFields)->filter(fn($name, $field) => str_starts_with($field, 'module_types.') || str_starts_with($field, 'inverter_types.')),
                                            'Finance Fields' => collect($this->availableFields)->filter(fn($name, $field) => str_starts_with($field, 'customer_finances.')),
                                        ];
                                    @endphp
                                    
                                    @foreach($fieldGroups as $groupName => $fields)
                                        @if($fields->isNotEmpty())
                                            <div class="col-md-4 mb-3">
                                                <div class="card border">
                                                    <div class="card-header py-2">
                                                        <h6 class="mb-0 text-primary">{{ $groupName }}</h6>
                                                    </div>
                                                    <div class="card-body py-2" style="max-height: 200px; overflow-y: auto;">
                                                        @foreach($fields as $field => $name)
                                                            <div class="form-check form-check-sm mb-1">
                                                                <input class="form-check-input" type="checkbox" 
                                                                       wire:change="addField('{{ $field }}')"
                                                                       @if(in_array($field, $selectedFields)) checked @endif
                                                                       id="field_{{ str_replace('.', '_', $field) }}">
                                                                <label class="form-check-label small" for="field_{{ str_replace('.', '_', $field) }}">
                                                                    {{ $name }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- Selected Fields Display -->
                                @if(!empty($selectedFields))
                                    <div class="mt-3">
                                        <h6 class="fw-bold mb-2">Selected Fields ({{ count($selectedFields) }})</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($selectedFields as $index => $field)
                                                <span class="badge bg-primary d-flex align-items-center">
                                                    {{ $this->availableFields[$field] ?? $field }}
                                                    <button type="button" wire:click="removeField({{ $index }})" 
                                                            class="btn-close btn-close-white ms-2" style="font-size: 0.7em;"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Filters Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">
                                    <i class="icofont-filter me-2"></i>Add Filters
                                </h6>
                                
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label">Field</label>
                                        <select wire:model="filterField" class="form-select">
                                            <option value="">Select Field</option>
                                            @foreach($this->availableFields as $field => $name)
                                                <option value="{{ $field }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Operator</label>
                                        <select wire:model="filterOperator" class="form-select">
                                            @foreach($operators as $op => $name)
                                                <option value="{{ $op }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Value</label>
                                        <input type="text" wire:model="filterValue" class="form-control" 
                                               placeholder="Enter filter value"
                                               @if(in_array($filterOperator, ['IS NULL', 'IS NOT NULL'])) disabled @endif>
                                        <small class="text-muted">
                                            @if($filterOperator === 'IN' || $filterOperator === 'NOT IN')
                                                Use comma-separated values
                                            @elseif($filterOperator === 'BETWEEN')
                                                Use format: start,end
                                            @elseif($filterOperator === 'LIKE' || $filterOperator === 'NOT LIKE')
                                                Text search
                                            @endif
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" wire:click="addFilter" class="btn btn-primary w-100">
                                            <i class="icofont-plus me-1"></i>Add Filter
                                        </button>
                                    </div>
                                </div>

                                <!-- Display Active Filters -->
                                @if(!empty($filters))
                                    <div class="mt-3">
                                        <h6 class="fw-bold mb-2">Active Filters ({{ count($filters) }})</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($filters as $index => $filter)
                                                <span class="badge bg-warning text-dark d-flex align-items-center">
                                                    {{ $filter['field_name'] }} {{ $filter['operator'] }} 
                                                    @if(!in_array($filter['operator'], ['IS NULL', 'IS NOT NULL']))
                                                        "{{ $filter['value'] }}"
                                                    @endif
                                                    <button type="button" wire:click="removeFilter({{ $index }})" 
                                                            class="btn-close ms-2" style="font-size: 0.7em;"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Calculated Fields Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3">
                                    <i class="icofont-calculator me-2"></i>Add Calculated Fields
                                </h6>
                                
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">Field Name</label>
                                        <input type="text" wire:model="calcFieldName" class="form-control" 
                                               placeholder="e.g., Net Profit">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label">Expression</label>
                                        <input type="text" wire:model="calcFieldExpression" class="form-control" 
                                               placeholder="e.g., {customer_finances.total_contract_value} - {customer_finances.adder_total}">
                                        <small class="text-muted">Use {field.name} format for field references</small>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" wire:click="addCalculatedField" class="btn btn-success w-100">
                                            <i class="icofont-plus me-1"></i>Add Calc
                                        </button>
                                    </div>
                                </div>

                                <!-- Display Calculated Fields -->
                                @if(!empty($calculatedFields))
                                    <div class="mt-3">
                                        <h6 class="fw-bold mb-2">Calculated Fields ({{ count($calculatedFields) }})</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($calculatedFields as $index => $calcField)
                                                <span class="badge bg-success d-flex align-items-center">
                                                    {{ $calcField['name'] }}: {{ $calcField['expression'] }}
                                                    <button type="button" wire:click="removeCalculatedField({{ $index }})" 
                                                            class="btn-close btn-close-white ms-2" style="font-size: 0.7em;"></button>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Generate Report Button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-center">
                                    <button type="button" wire:click="generateReport" class="btn btn-lg btn-primary me-3">
                                        <i class="icofont-chart-bar-graph me-2"></i>Generate Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        @if($showResults)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="icofont-table me-2"></i>Report Results ({{ count($reportData) }} records)
                            </h5>
                            <div class="btn-group">
                                <button wire:click="exportExcel" class="btn btn-success">
                                    <i class="icofont-file-excel me-1"></i>Export Excel
                                </button>
                                <button wire:click="exportPdf" class="btn btn-danger">
                                    <i class="icofont-file-pdf me-1"></i>Export PDF
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($reportData) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                @foreach($reportColumns as $column)
                                                    <th>{{ $column['name'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($reportData as $row)
                                                <tr>
                                                    @foreach($reportColumns as $column)
                                                        <td>
                                                            @php
                                                                $value = $this->getNestedProperty($row, $column['field']);
                                                                if ($column['type'] === 'calculated') {
                                                                    $value = $row->{$column['field']} ?? 'N/A';
                                                                }
                                                                // Format numbers
                                                                if (is_numeric($value)) {
                                                                    $value = number_format($value, (is_float($value + 0) && floor($value + 0) != ($value + 0)) ? 2 : 0);
                                                                }
                                                            @endphp
                                                            {{ $value ?? '-' }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="icofont-database text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted">No data found</h5>
                                    <p class="text-muted">Try adjusting your filters or field selection.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Loading State -->
        <div wire:loading class="position-fixed top-50 start-50 translate-middle">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    @if (session()->has('error'))
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <i class="icofont-exclamation-triangle me-2"></i>
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide success/error messages
    setTimeout(function() {
        var toasts = document.querySelectorAll('.toast');
        toasts.forEach(function(toast) {
            var bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        });
    }, 5000);
</script>
@endscript
