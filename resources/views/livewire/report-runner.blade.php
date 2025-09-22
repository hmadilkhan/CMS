<div>
    @section('title', 'Run Saved Reports')

    <!-- Header Section -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 mb-4 no-bg">
                    <div
                        class="card-header py-3 px-0 d-sm-flex align-items-center justify-content-between border-bottom">
                        <h3 class="fw-bold flex-fill mb-0 mt-sm-0">
                            <i class="icofont-play me-2"></i>
                            Run Saved Reports
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Selection Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="icofont-list me-2"></i>Select Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <label for="reportSelect" class="form-label fw-bold">Choose a Saved Report</label>
                                <select wire:model.live="selectedReportId" class="form-select" id="reportSelect">
                                    <option value="">Select a report...</option>
                                    @foreach ($this->userReports as $report)
                                        <option value="{{ $report->id }}">
                                            {{ $report->name }} ({{ ucfirst($report->report_type) }}) -
                                            {{ $report->created_at->format('M d, Y') }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($this->userReports->isEmpty())
                                    <small class="text-muted">No saved reports found. <a
                                            href="{{ route('report-builder') }}">Create a report</a> to get
                                        started.</small>
                                @endif
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                @if ($selectedReport)
                                    <button type="button" wire:click="editReport({{ $selectedReport->id }})"
                                        class="btn btn-outline-primary me-2">
                                        <i class="icofont-edit me-1"></i>Edit Report
                                    </button>

                                    <button type="button" wire:click="deleteReport({{ $selectedReport->id }})"
                                        class="btn btn-outline-danger"
                                        onclick="return confirm('Are you sure you want to delete this report?')">
                                        <i class="icofont-trash me-1"></i>Delete Report
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Report Details -->
                        @if ($selectedReport)
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading mb-2">
                                            <i class="icofont-info-circle me-2"></i>Report Details:
                                            {{ $selectedReport->name }}
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Type:</strong> {{ ucfirst($selectedReport->report_type) }}<br>
                                                <strong>Fields:</strong> {{ count($selectedReport->selected_fields) }}
                                                selected<br>
                                                <strong>Created:</strong>
                                                {{ $selectedReport->created_at->format('M d, Y \a\t g:i A') }}
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Filters:</strong> {{ count($selectedReport->filters ?? []) }}
                                                filters<br>
                                                <strong>Calculated Fields:</strong>
                                                {{ count($selectedReport->calculated_fields ?? []) }} fields
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter Values Section -->
                            @if (!empty($selectedReport->filters))
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="fw-bold mb-3">
                                            <i class="icofont-filter me-2"></i>Provide Filter Values
                                        </h6>

                                        <div class="row g-3">
                                            @foreach ($selectedReport->filters as $index => $filter)
                                                <div class="col-md-6">
                                                    <label class="form-label">
                                                        {{ $filter['field_name'] ?? $filter['field'] }}
                                                        <span
                                                            class="badge bg-secondary ms-1">{{ $filter['operator'] }}</span>
                                                    </label>
                                                    @if (in_array($filter['operator'], ['IS NULL', 'IS NOT NULL']))
                                                        <input type="text" class="form-control"
                                                            value="No value required" disabled>
                                                    @else
                                                        @php $fieldType = $this->getFieldType($filter['field']) @endphp
                                                        @if ($fieldType === 'dropdown')
                                                            <select wire:model="filterValues.{{ $index }}"
                                                                class="form-select">
                                                                <option value="">Select value...</option>
                                                                @foreach ($this->getDropdownOptions($filter['field']) as $value => $label)
                                                                    <option value="{{ $value }}">
                                                                        {{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        @elseif($fieldType === 'date' && $filter['operator'] === 'EQUALS')
                                                            <input type="date"
                                                                wire:model="filterValues.{{ $index }}"
                                                                class="form-control">
                                                        @elseif($fieldType === 'date' && in_array($filter['operator'], ['BETWEEN', 'NOT BETWEEN']))
                                                            <div class="d-flex gap-2">
                                                                <input type="date"
                                                                    wire:model="filterStartDate.{{ $index }}"
                                                                    class="form-control" placeholder="Start Date">
                                                                <span class="align-self-center">to</span>
                                                                <input type="date"
                                                                    wire:model="filterEndDate.{{ $index }}"
                                                                    class="form-control" placeholder="End Date">
                                                            </div>
                                                        @elseif($fieldType === 'number')
                                                            <input type="number"
                                                                wire:model="filterValues.{{ $index }}"
                                                                class="form-control" step="any"
                                                                placeholder="Enter value for {{ $filter['field_name'] ?? $filter['field'] }}">
                                                        @else
                                                            <input type="text"
                                                                wire:model="filterValues.{{ $index }}"
                                                                class="form-control"
                                                                placeholder="Enter value for {{ $filter['field_name'] ?? $filter['field'] }}">
                                                        @endif
                                                        <small class="text-muted">
                                                            @if ($filter['operator'] === 'IN' || $filter['operator'] === 'NOT IN')
                                                                Use comma-separated values
                                                            @elseif($filter['operator'] === 'BETWEEN')
                                                                Use format: start,end (or use comma-separated values)
                                                            @elseif($filter['operator'] === 'LIKE' || $filter['operator'] === 'NOT LIKE')
                                                                Text search
                                                            @endif
                                                        </small>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Run Report Button -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-center">
                                        <button type="button" wire:click="runReport" class="btn btn-lg btn-success">
                                            <i class="icofont-play-alt-1 me-2"></i>Run Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        @if ($showResults)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="icofont-table me-2"></i>{{ $selectedReport->name }} - Results
                                ({{ count($reportData) }} records)
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
                            @if (count($reportData) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                @foreach ($reportColumns as $column)
                                                    <th>{{ $column['name'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($reportData as $row)
                                                <tr>
                                                    @foreach ($reportColumns as $column)
                                                        <td>
                                                            @php
                                                                $value = $this->getNestedProperty(
                                                                    $row,
                                                                    $column['field'],
                                                                );
                                                                if ($column['type'] === 'calculated') {
                                                                    $value = $row->{$column['field']} ?? 'N/A';
                                                                }
                                                                // Format numbers only if they're not already formatted strings
                                                                if (is_numeric($value) && !is_string($value)) {
                                                                    $value = number_format(
                                                                        $value,
                                                                        is_float($value + 0) &&
                                                                        floor($value + 0) != $value + 0
                                                                            ? 2
                                                                            : 0,
                                                                    );
                                                                }
                                                            @endphp
                                                            <div
                                                                style="max-width: 300px; word-wrap: break-word; white-space: pre-wrap;">
                                                                {{ $value ?? '-' }}</div>
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
                                    <p class="text-muted">The report executed successfully but returned no results. Try
                                        adjusting your filter values.</p>
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

    <!-- Success/Error Messages -->
    @if (session()->has('success'))
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="icofont-check-circle me-2"></i>
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

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
    <script>
        // Auto-hide success/error messages
        setTimeout(function() {
            var toasts = document.querySelectorAll('.toast');
            toasts.forEach(function(toast) {
                var bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            });
        }, 5000);
    </script>
</div>
