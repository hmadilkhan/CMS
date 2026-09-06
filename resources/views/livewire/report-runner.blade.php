<div class="report-runner">
    @section('title', 'Run Saved Reports')

    {{--
        The run view from the agreed design: what was asked at the top, the
        shape of the answer as a chart, then the rows with their subtotals.
        The filter values are the only thing a reader may change here - the
        report itself is edited in the builder.
    --}}
    <style>
        .report-runner {
            color: #342416;
        }

        .report-runner .rr-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 0 14px;
            border-bottom: 1px solid #eadfce;
        }

        .report-runner .rr-panel {
            border: 1px solid #eadfce;
            border-radius: 8px;
            box-shadow: 0 18px 48px -34px rgba(52, 36, 22, 0.45);
        }

        .report-runner .rr-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid #eadfce;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #5c4632;
        }

        .report-runner .rr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .report-runner .rr-table th {
            padding: 10px 14px;
            border-bottom: 1px solid #eadfce;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #7c6f60;
            white-space: nowrap;
        }

        .report-runner .rr-table td {
            padding: 8px 14px;
            border-bottom: 1px solid #f5eee2;
            vertical-align: top;
        }

        .report-runner .rr-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .report-runner .rr-group-row td {
            background: rgba(240, 122, 36, 0.1);
            color: #a94f1f;
            font-weight: 700;
            border-bottom: 1px solid #eadfce;
        }

        .report-runner .rr-subtotal-row td {
            font-weight: 700;
            background: #ffffff;
            border-bottom: 1px solid #eadfce;
        }

        .report-runner .rr-total-row td {
            font-weight: 700;
            color: #a94f1f;
            background: rgba(240, 122, 36, 0.1);
            border-top: 1px solid rgba(240, 122, 36, 0.22);
        }

        .report-runner .rr-scroll {
            overflow-x: auto;
        }

        .report-runner .rr-bar-track {
            display: block;
            height: 20px;
            border-radius: 4px;
            background: #eadfce;
            position: relative;
            overflow: hidden;
        }

        .report-runner .rr-bar-fill {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            border-radius: 4px;
            background: linear-gradient(90deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%);
        }

        .report-runner .rr-chart-row {
            display: grid;
            grid-template-columns: minmax(120px, 220px) 1fr minmax(110px, 150px);
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .report-runner .form-control,
        .report-runner .form-select {
            border: 1px solid #eadfce;
            border-radius: 6px;
            font-size: 12.5px;
            min-height: 36px;
        }

        .report-runner .form-control:focus,
        .report-runner .form-select:focus {
            border-color: #ee8f45;
            box-shadow: 0 0 0 0.2rem rgba(240, 122, 36, 0.18);
        }

        .report-runner .btn-solen {
            background: linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%);
            border: 1px solid transparent;
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 12px 30px -18px rgba(151, 76, 18, 0.55);
        }

        .report-runner .btn-solen:hover {
            color: #ffffff;
            filter: brightness(1.03);
        }
    </style>

    <div class="container-fluid px-0">

        <div class="rr-bar">
            <div class="flex-grow-1">
                <div class="text-muted" style="font-size: 11px;">Reports</div>
                <h3 class="fw-bold mb-0" style="font-size: 18px;">
                    {{ $selectedReport->name ?? 'Run a saved report' }}
                </h3>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if ($selectedReport)
                    <button wire:click="exportExcel" class="btn btn-sm btn-outline-success">
                        <i class="icofont-file-excel me-1"></i>CSV
                    </button>
                    <button wire:click="exportPdf" class="btn btn-sm btn-outline-danger">
                        <i class="icofont-file-pdf me-1"></i>PDF
                    </button>
                    <button wire:click="editReport({{ $selectedReport->id }})" class="btn btn-sm btn-outline-secondary">
                        <i class="icofont-pencil me-1"></i>Edit report
                    </button>
                @endif
                <a href="{{ route('report-builder') }}" class="btn btn-sm btn-solen">
                    <i class="icofont-plus me-1"></i>New report
                </a>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <label class="form-label fw-bold" style="font-size: 12.5px;">Saved report</label>
                <select wire:model.live="selectedReportId" class="form-select">
                    <option value="">Select a report…</option>
                    @foreach ($this->userReports as $report)
                        <option value="{{ $report->id }}">
                            {{ $report->name }} — {{ $report->created_at->format('d M Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($selectedReport)
                <div class="col-lg-8 d-flex align-items-end">
                    <div class="text-muted" style="font-size: 12px;">
                        {{ count($selectedReport->selected_fields ?? []) }} columns
                        @if (!empty($selectedReport->group_by))
                            &middot; grouped
                        @endif
                        @if (!empty($selectedReport->filters))
                            &middot; {{ count($selectedReport->filters) }}
                            {{ Str::plural('filter', count($selectedReport->filters)) }}
                        @endif
                        @if ($showResults)
                            &middot; {{ number_format($rowCount) }} {{ Str::plural('record', $rowCount) }}
                        @endif
                    </div>
                </div>
            @endif
        </div>

        @if ($selectedReport && !empty($selectedReport->filters))
            <div class="rr-panel mt-3">
                <div class="rr-head">
                    <i class="icofont-filter"></i>Filter values
                    <span class="fw-normal text-lowercase text-muted"
                        style="font-size: 12px; letter-spacing: 0;">change these without changing the report</span>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        @foreach ($selectedReport->filters as $index => $filter)
                            <div class="col-md-6 col-xl-4">
                                <label class="form-label" style="font-size: 12.5px;">
                                    {{ $filter['field_name'] ?? $filter['field'] }}
                                    <span class="badge bg-secondary ms-1">{{ $filter['operator'] }}</span>
                                </label>

                                @if (in_array($filter['operator'], ['IS NULL', 'IS NOT NULL']))
                                    <input type="text" class="form-control" value="No value required" disabled>
                                @else
                                    @php $fieldType = $this->getFieldType($filter['field']) @endphp
                                    @if ($this->filterUsesPicker($filter))
                                        @include('livewire.partials.filter-picker', [
                                            'options' => $this->getDropdownOptions($filter['field']),
                                            'selected' => $filterValues[$index] ?? [],
                                            'model' => 'filterValues.' . $index,
                                            'pickerKey' => 'filter-picker-' . $index . '-' . $filter['field'],
                                        ])
                                    @elseif ($fieldType === 'dropdown')
                                        <select wire:model="filterValues.{{ $index }}" class="form-select">
                                            <option value="">Select value…</option>
                                            @foreach ($this->getDropdownOptions($filter['field']) as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($fieldType === 'date' && in_array($filter['operator'], ['BETWEEN', 'NOT BETWEEN']))
                                        <div class="d-flex gap-2">
                                            <input type="date" wire:model="filterStartDate.{{ $index }}"
                                                class="form-control">
                                            <span class="align-self-center">to</span>
                                            <input type="date" wire:model="filterEndDate.{{ $index }}"
                                                class="form-control">
                                        </div>
                                    @elseif ($fieldType === 'date')
                                        <input type="date" wire:model="filterValues.{{ $index }}"
                                            class="form-control">
                                    @elseif ($fieldType === 'number')
                                        <input type="number" step="any" wire:model="filterValues.{{ $index }}"
                                            class="form-control" placeholder="Enter a value">
                                    @else
                                        <input type="text" wire:model="filterValues.{{ $index }}"
                                            class="form-control" placeholder="Enter a value">
                                    @endif

                                    <small class="text-muted">
                                        @if ($this->filterUsesPicker($filter))
                                        @elseif ($filter['operator'] === 'IN' || $filter['operator'] === 'NOT IN')
                                            Use comma-separated values
                                        @elseif ($filter['operator'] === 'BETWEEN')
                                            Use format: start,end
                                        @elseif ($filter['operator'] === 'LIKE' || $filter['operator'] === 'NOT LIKE')
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

        @if ($selectedReport)
            <div class="d-flex justify-content-center my-3">
                <button wire:click="runReport" class="btn btn-solen px-4">
                    <i class="icofont-play-alt-2 me-1"></i>Run report
                </button>
            </div>
        @endif

        @if ($showResults)
            @if (!empty($chart['bars']))
                <div class="rr-panel mb-3">
                    <div class="rr-head">
                        <i class="icofont-chart-bar-graph"></i>{{ $chart['measure'] }} by group
                    </div>
                    <div class="p-3">
                        @foreach ($chart['bars'] as $bar)
                            <div class="rr-chart-row">
                                <span style="font-size: 12.5px; color: #5c4632;">{{ $bar['label'] }}</span>
                                <span class="rr-bar-track">
                                    <span class="rr-bar-fill" style="width: {{ $bar['width'] }}%;"></span>
                                </span>
                                <span class="rr-num fw-bold" style="font-size: 12.5px;">
                                    {{ number_format($bar['value'], 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rr-panel">
                <div class="rr-head">
                    <i class="icofont-table"></i>Results
                    <span class="fw-normal text-lowercase text-muted" style="font-size: 12px; letter-spacing: 0;">
                        {{ number_format($rowCount) }} {{ Str::plural('record', $rowCount) }}
                    </span>
                </div>

                @if (count($reportData) > 0)
                    <div class="rr-scroll">
                        <table class="rr-table">
                            <thead>
                                <tr>
                                    @foreach ($reportColumns as $column)
                                        <th class="{{ ($column['numeric'] ?? false) ? 'rr-num' : '' }}">
                                            {{ $column['name'] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($groups))
                                    @foreach ($groups as $group)
                                        @include('livewire.partials.report-group-runner', [
                                            'group' => $group,
                                            'depth' => 0,
                                        ])
                                    @endforeach

                                    <tr class="rr-total-row">
                                        @foreach ($reportColumns as $index => $column)
                                            <td class="{{ ($column['numeric'] ?? false) ? 'rr-num' : '' }}">
                                                @if ($index === 0)
                                                    Total · {{ number_format($totals['count']) }}
                                                    {{ Str::plural('record', $totals['count']) }}
                                                @elseif (isset($totals['aggregates'][$column['field']]))
                                                    {{ $this->formatSummary($totals['aggregates'][$column['field']]) }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @else
                                    @foreach ($reportData as $row)
                                        @include('livewire.partials.report-row-runner', ['row' => $row])
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if ($this->lastPage() > 1)
                        <div class="d-flex align-items-center gap-2 p-3 border-top" style="border-color: #eadfce;">
                            <span class="text-muted" style="font-size: 12px;">
                                Showing {{ number_format(($page - 1) * $perPage + 1) }}–{{ number_format(min($page * $perPage, $rowCount)) }}
                                of {{ number_format($rowCount) }}
                                @if (!empty($groups))
                                    &middot; subtotals cover every record
                                @endif
                            </span>
                            <div class="flex-grow-1"></div>
                            <button wire:click="gotoPage({{ $page - 1 }})" class="btn btn-sm btn-outline-secondary"
                                @if ($page <= 1) disabled @endif>Previous</button>
                            <span class="text-muted" style="font-size: 12px;">Page {{ $page }} of
                                {{ $this->lastPage() }}</span>
                            <button wire:click="gotoPage({{ $page + 1 }})" class="btn btn-sm btn-outline-secondary"
                                @if ($page >= $this->lastPage()) disabled @endif>Next</button>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="icofont-database text-muted" style="font-size: 2.4rem;"></i>
                        <div class="fw-bold mt-2">No records match</div>
                        <div class="text-muted" style="font-size: 12.5px;">The report ran; nothing meets these filter
                            values.</div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if (session()->has('error'))
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <i class="icofont-exclamation-triangle me-2"></i>
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    @if (session()->has('success'))
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="icofont-check-circled me-2"></i>
                    <strong class="me-auto">Done</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">{{ session('success') }}</div>
            </div>
        </div>
    @endif
</div>
