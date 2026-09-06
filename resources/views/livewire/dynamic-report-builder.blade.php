<div class="report-builder">
    @section('title', 'Dynamic Report Builder')

    {{--
        Three panes, as in the agreed design: what the report is made of on the
        left, what it returns in the middle, what narrows it on the right. The
        middle is the report itself run small, so every change answers for
        itself instead of waiting for a run.
    --}}
    <style>
        .report-builder {
            width: 100%;
            color: #342416;
        }

        .report-builder .rb-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 4px 14px;
            border-bottom: 1px solid #eadfce;
        }

        .report-builder .rb-shell {
            display: flex;
            align-items: stretch;
            min-height: 620px;
            height: calc(100vh - 220px);
        }

        .report-builder .rb-pane {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .report-builder .rb-left {
            width: 300px;
            flex-shrink: 0;
            border-right: 1px solid #eadfce;
        }

        .report-builder .rb-center {
            flex: 1;
            min-width: 0;
        }

        .report-builder .rb-right {
            width: 320px;
            flex-shrink: 0;
            border-left: 1px solid #eadfce;
        }

        .report-builder .rb-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-bottom: 1px solid #eadfce;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #5c4632;
        }

        .report-builder .rb-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 14px 16px;
        }

        /* the preview is as wide as the report is - it scrolls sideways
           rather than squeezing every column into the pane */
        .report-builder .rb-center .rb-scroll {
            overflow-x: auto;
        }

        .report-builder .rb-preview-table td > div {
            min-width: 90px;
        }

        .report-builder .rb-section-label {
            font-size: 11px;
            color: #7c6f60;
            margin-bottom: 6px;
        }

        .report-builder .rb-col {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 8px;
            border: 1px solid #eadfce;
            border-radius: 6px;
            margin-bottom: 4px;
            font-size: 12.5px;
            background: #ffffff;
        }

        .report-builder .rb-col .rb-col-name {
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .report-builder .rb-icon-btn {
            border: 0;
            background: transparent;
            color: #a49686;
            line-height: 1;
            padding: 2px 4px;
            border-radius: 4px;
        }

        .report-builder .rb-icon-btn:hover {
            color: #a94f1f;
            background: rgba(240, 122, 36, 0.1);
        }

        .report-builder .rb-field {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            text-align: left;
            border: 0;
            background: transparent;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 12.5px;
            color: #342416;
        }

        .report-builder .rb-field:hover {
            background: rgba(240, 122, 36, 0.08);
        }

        .report-builder .rb-field.is-picked {
            background: rgba(240, 122, 36, 0.1);
            color: #a94f1f;
            font-weight: 600;
        }

        .report-builder .rb-group-name {
            font-size: 12px;
            font-weight: 700;
            color: #5c4632;
            margin: 10px 0 4px;
        }

        .report-builder .rb-filter-card {
            border: 1px solid #eadfce;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }

        .report-builder .rb-filter-card.is-new {
            border-color: rgba(240, 122, 36, 0.24);
        }

        .report-builder .rb-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(240, 122, 36, 0.1);
            border: 1px solid rgba(240, 122, 36, 0.22);
            border-radius: 5px;
            padding: 2px 7px;
            font-size: 11.5px;
            color: #a94f1f;
        }

        .report-builder .rb-preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .report-builder .rb-preview-table th {
            position: sticky;
            top: 0;
            background: #ffffff;
            border-bottom: 1px solid #eadfce;
            padding: 9px 14px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #7c6f60;
            white-space: nowrap;
        }

        .report-builder .rb-preview-table td {
            border-bottom: 1px solid #f5eee2;
            padding: 8px 14px;
            vertical-align: top;
        }

        .report-builder .rb-group-row td {
            background: rgba(240, 122, 36, 0.1);
            color: #a94f1f;
            font-size: 12.5px;
            border-bottom: 1px solid #eadfce;
        }

        .report-builder .rb-subtotal-row td {
            font-weight: 700;
            border-bottom: 1px solid #eadfce;
            background: #ffffff;
        }

        .report-builder .rb-total-row td {
            font-weight: 700;
            color: #a94f1f;
            background: rgba(240, 122, 36, 0.1);
            border-top: 1px solid rgba(240, 122, 36, 0.22);
        }

        .report-builder .rb-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .report-builder .rb-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 100%;
            color: #7c6f60;
            text-align: center;
            padding: 40px;
        }

        .report-builder .form-control,
        .report-builder .form-select {
            border: 1px solid #eadfce;
            border-radius: 6px;
            font-size: 12.5px;
            min-height: 34px;
        }

        .report-builder .form-control:focus,
        .report-builder .form-select:focus {
            border-color: #ee8f45;
            box-shadow: 0 0 0 0.2rem rgba(240, 122, 36, 0.18);
        }

        .report-builder .btn-solen {
            background: linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%);
            border: 1px solid transparent;
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 12px 30px -18px rgba(151, 76, 18, 0.55);
        }

        .report-builder .btn-solen:hover {
            color: #ffffff;
            filter: brightness(1.03);
        }

        @media (max-width: 1199px) {
            .report-builder .rb-shell {
                flex-direction: column;
                height: auto;
            }

            .report-builder .rb-left,
            .report-builder .rb-right {
                width: 100%;
                border: 0;
                border-bottom: 1px solid #eadfce;
            }

            .report-builder .rb-scroll {
                max-height: 340px;
            }
        }
    </style>

    <div class="container-fluid px-0">

        <!-- top bar -->
        <div class="rb-bar">
            <div class="flex-grow-1">
                <div class="text-muted" style="font-size: 11px;">Reports</div>
                <input type="text" wire:model.live.debounce.500ms="reportName" class="form-control border-0 px-0 fw-bold"
                    style="font-size: 17px; min-height: 30px; box-shadow: none;" placeholder="Name this report">
                @error('reportName')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="d-flex align-items-center gap-2">
                <button wire:click="clearAll" class="btn btn-sm btn-outline-secondary">
                    <i class="icofont-refresh me-1"></i>Clear all
                </button>
                @if ($isEditing)
                    <button wire:click="updateReport" class="btn btn-sm btn-success">
                        <i class="icofont-save me-1"></i>Update report
                    </button>
                    <button wire:click="cancelEdit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                @else
                    <button wire:click="saveReport" class="btn btn-sm btn-solen">
                        <i class="icofont-save me-1"></i>Save report
                    </button>
                @endif
                <a href="{{ route('report-runner') }}" class="btn btn-sm btn-outline-success">
                    <i class="icofont-play-alt-2 me-1"></i>Run saved reports
                </a>
            </div>
        </div>

        <div class="rb-shell">

            <!-- LEFT: outline + fields -->
            <div class="rb-pane rb-left">
                <div class="rb-head">
                    <i class="icofont-listing-box"></i>Outline
                </div>

                <div class="rb-scroll">
                    <div class="rb-section-label">Group rows</div>
                    <select wire:model.live="groupBy" class="form-select mb-3">
                        <option value="">No grouping</option>
                        @foreach ($this->availableFields as $field => $name)
                            <option value="{{ $field }}">{{ $name }}</option>
                        @endforeach
                    </select>

                    <div class="rb-section-label">Columns ({{ count($selectedFields) }})</div>

                    @forelse ($selectedFields as $index => $field)
                        <div class="rb-col">
                            <span class="rb-col-name">{{ $this->availableFields[$field] ?? $field }}</span>
                            <button type="button" class="rb-icon-btn" title="Move up"
                                wire:click="moveFieldUp({{ $index }})">
                                <i class="icofont-simple-up"></i>
                            </button>
                            <button type="button" class="rb-icon-btn" title="Move down"
                                wire:click="moveFieldDown({{ $index }})">
                                <i class="icofont-simple-down"></i>
                            </button>
                            <button type="button" class="rb-icon-btn" title="Remove"
                                wire:click="removeField({{ $index }})">
                                <i class="icofont-close-line"></i>
                            </button>
                        </div>
                    @empty
                        <p class="text-muted" style="font-size: 12px;">Pick fields below to build the report.</p>
                    @endforelse

                    @if (!empty($calculatedFields))
                        <div class="rb-section-label mt-3">Calculated ({{ count($calculatedFields) }})</div>
                        @foreach ($calculatedFields as $index => $calcField)
                            <div class="rb-col">
                                <span class="rb-col-name" title="{{ $calcField['expression'] }}">
                                    {{ $calcField['name'] }}
                                </span>
                                <button type="button" class="rb-icon-btn" title="Remove"
                                    wire:click="removeCalculatedField({{ $index }})">
                                    <i class="icofont-close-line"></i>
                                </button>
                            </div>
                        @endforeach
                    @endif

                    <details class="mt-3">
                        <summary style="font-size: 12px; font-weight: 600; cursor: pointer;">
                            <i class="icofont-calculator me-1"></i>Add a calculated field
                        </summary>

                        <div class="mt-2 d-flex flex-column gap-2">
                            <select wire:model="calcInitialField" class="form-select"
                                @if ($calcExpressionBuilder) disabled @endif>
                                <option value="">First field</option>
                                @foreach ($this->availableFields as $field => $name)
                                    <option value="{{ $field }}">{{ $name }}</option>
                                @endforeach
                            </select>

                            <div class="d-flex gap-2">
                                <select wire:model="builderOperator" class="form-select" style="width: 72px;">
                                    <option value="+">+</option>
                                    <option value="-">-</option>
                                    <option value="*">*</option>
                                    <option value="/">/</option>
                                </select>
                                <select wire:model="builderField2" class="form-select">
                                    <option value="">Second field</option>
                                    @foreach ($this->availableFields as $field => $name)
                                        <option value="{{ $field }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <input type="number" wire:model="builderValue2" class="form-control"
                                placeholder="…or a plain number">

                            <div class="d-flex gap-2">
                                <button type="button" wire:click="addToCalcBuilder"
                                    class="btn btn-sm btn-outline-primary flex-grow-1">Add step</button>
                                <button type="button" wire:click="removeLastCalcBuilder"
                                    class="btn btn-sm btn-outline-danger">Undo</button>
                                <button type="button" wire:click="clearCalcBuilder"
                                    class="btn btn-sm btn-outline-secondary">Clear</button>
                            </div>

                            <div class="form-control bg-light" style="min-height: 34px;">
                                {{ $calcExpressionPreview ?: 'Build your expression…' }}
                            </div>
                            <button type="button" wire:click="useCalcBuilder"
                                class="btn btn-sm btn-outline-success">Use this expression</button>

                            <input type="text" wire:model="calcFieldName" class="form-control"
                                placeholder="Name, e.g. Net Profit">
                            <input type="text" wire:model="calcFieldExpression" class="form-control"
                                placeholder="{customer_finances.contract_amount} - {projects.actual_material_cost}">
                            @error('calcFieldName')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            @error('calcFieldExpression')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                            <button type="button" wire:click="addCalculatedField" class="btn btn-sm btn-solen">
                                <i class="icofont-plus me-1"></i>Add calculated field
                            </button>
                        </div>
                    </details>
                </div>

                <div class="rb-head" style="border-top: 1px solid #eadfce;">
                    <i class="icofont-database"></i>Fields
                </div>

                <div class="rb-scroll" style="flex: 1.4;">
                    <input type="search" wire:model.live.debounce.250ms="fieldSearch" class="form-control mb-2"
                        placeholder="Search {{ $this->fieldCount }} fields">

                    @forelse ($this->fieldGroups as $groupName => $fields)
                        <div class="rb-group-name">{{ $groupName }}
                            <span class="text-muted fw-normal">{{ count($fields) }}</span>
                        </div>
                        @foreach ($fields as $field => $name)
                            @php $picked = in_array($field, $selectedFields, true); @endphp
                            <button type="button" wire:click="toggleField('{{ $field }}')"
                                class="rb-field {{ $picked ? 'is-picked' : '' }}"
                                wire:key="field-{{ str_replace('.', '-', $field) }}">
                                <i class="{{ $picked ? 'icofont-check-circled' : 'icofont-plus-circle' }}"></i>
                                <span class="flex-grow-1">{{ $name }}</span>
                            </button>
                        @endforeach
                    @empty
                        <p class="text-muted" style="font-size: 12px;">No field matches “{{ $fieldSearch }}”.</p>
                    @endforelse
                </div>
            </div>

            <!-- CENTER: live preview -->
            <div class="rb-pane rb-center">
                <div class="rb-head">
                    <i class="icofont-eye"></i>Preview
                    <span class="fw-normal text-muted text-lowercase" style="font-size: 12px; letter-spacing: 0;">
                        @if ($showResults)
                            {{ number_format($previewCount) }} {{ Str::plural('record', $previewCount) }}
                            @if ($this->previewTruncated)
                                &middot; showing first {{ count($reportData) }}
                            @endif
                        @else
                            nothing to show yet
                        @endif
                    </span>
                    <div class="flex-grow-1"></div>
                    <div wire:loading wire:target="toggleField,removeField,addFilter,removeFilter,moveFieldUp,moveFieldDown,addCalculatedField,removeCalculatedField"
                        class="text-muted fw-normal text-lowercase" style="font-size: 12px; letter-spacing: 0;">
                        updating…
                    </div>
                    <div class="d-flex gap-2">
                        <button wire:click="exportExcel" class="btn btn-sm btn-outline-success"
                            @if (!$showResults) disabled @endif>
                            <i class="icofont-file-excel me-1"></i>CSV
                        </button>
                        <button wire:click="exportPdf" class="btn btn-sm btn-outline-danger"
                            @if (!$showResults) disabled @endif>
                            <i class="icofont-file-pdf me-1"></i>PDF
                        </button>
                    </div>
                </div>

                <div class="rb-scroll p-0">
                    @if ($previewError)
                        <div class="alert alert-warning m-3" style="font-size: 12.5px;">{{ $previewError }}</div>
                    @elseif ($showResults && count($reportData) > 0)
                        <table class="rb-preview-table">
                            <thead>
                                <tr>
                                    @foreach ($reportColumns as $column)
                                        <th class="{{ ($column['numeric'] ?? false) ? 'rb-num' : '' }}">
                                            {{ $column['name'] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @if ($groupBy && !empty($previewGroups))
                                    @foreach ($previewGroups as $group)
                                        <tr class="rb-group-row">
                                            <td colspan="{{ count($reportColumns) }}">
                                                <span class="fw-bold">{{ $this->groupHeading($group['value']) }}</span>
                                                <span class="ms-2">{{ number_format($group['count']) }}
                                                    {{ Str::plural('record', $group['count']) }}</span>
                                            </td>
                                        </tr>

                                        @foreach ($group['rows'] as $row)
                                            @include('livewire.partials.report-row', ['row' => $row])
                                        @endforeach

                                        <tr class="rb-subtotal-row">
                                            @foreach ($reportColumns as $index => $column)
                                                <td class="{{ ($column['numeric'] ?? false) ? 'rb-num' : '' }}">
                                                    @if ($index === 0)
                                                        Subtotal
                                                    @elseif (($column['numeric'] ?? false) && isset($group['sums'][$column['field']]))
                                                        {{ number_format($group['sums'][$column['field']], 2) }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach

                                    <tr class="rb-total-row">
                                        @foreach ($reportColumns as $index => $column)
                                            <td class="{{ ($column['numeric'] ?? false) ? 'rb-num' : '' }}">
                                                @if ($index === 0)
                                                    Total · {{ number_format($previewTotals['count']) }}
                                                    {{ Str::plural('record', $previewTotals['count']) }}
                                                @elseif (($column['numeric'] ?? false) && isset($previewTotals['sums'][$column['field']]))
                                                    {{ number_format($previewTotals['sums'][$column['field']], 2) }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @else
                                    @foreach ($reportData as $row)
                                        @include('livewire.partials.report-row', ['row' => $row])
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    @elseif ($showResults)
                        <div class="rb-empty">
                            <i class="icofont-database" style="font-size: 2.4rem;"></i>
                            <div class="fw-bold">No records match</div>
                            <div style="font-size: 12.5px;">The report runs, but nothing meets these filters.</div>
                        </div>
                    @else
                        <div class="rb-empty">
                            <i class="icofont-chart-bar-graph" style="font-size: 2.4rem;"></i>
                            <div class="fw-bold">Pick a field to start</div>
                            <div style="font-size: 12.5px;">The preview runs your report as you build it.</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- RIGHT: filters -->
            <div class="rb-pane rb-right">
                <div class="rb-head">
                    <i class="icofont-filter"></i>Filters
                    @if (!empty($filters))
                        <span class="badge rounded-pill"
                            style="background: linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%);">{{ count($filters) }}</span>
                    @endif
                </div>

                <div class="rb-scroll">
                    @foreach ($filters as $index => $filter)
                        <div class="rb-filter-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="fw-bold" style="font-size: 12.5px;">{{ $filter['field_name'] }}</span>
                                <button type="button" class="rb-icon-btn" wire:click="removeFilter({{ $index }})"
                                    title="Remove filter">
                                    <i class="icofont-close-line"></i>
                                </button>
                            </div>
                            <div class="text-muted" style="font-size: 11.5px;">{{ $filter['operator'] }}</div>
                            @if (!in_array($filter['operator'], ['IS NULL', 'IS NOT NULL']))
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    @foreach (explode(',', (string) ($filter['value_label'] ?? $filter['value'])) as $piece)
                                        <span class="rb-tag">{{ trim($piece) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="rb-filter-card is-new">
                        <div class="rb-section-label">Field</div>
                        <select wire:model.live="filterField" class="form-select mb-2">
                            <option value="">Select field</option>
                            @foreach ($this->availableFields as $field => $name)
                                <option value="{{ $field }}">{{ $name }}</option>
                            @endforeach
                        </select>

                        <div class="rb-section-label">Operator</div>
                        <select wire:model.live="filterOperator" class="form-select mb-2">
                            @foreach ($operators as $op => $name)
                                <option value="{{ $op }}">{{ $name }}</option>
                            @endforeach
                        </select>

                        <div class="rb-section-label">Value</div>
                        @php
                            $filterType = $filterField ? $this->getFieldType($filterField) : 'text';
                            $singleValue = !in_array($filterOperator, ['IN', 'NOT IN', 'BETWEEN', 'IS NULL', 'IS NOT NULL']);
                            $usesPicker = $this->filterFieldUsesPicker();
                        @endphp
                        @if (in_array($filterOperator, ['IS NULL', 'IS NOT NULL']))
                            <input type="text" class="form-control" value="No value required" disabled>
                        @elseif ($usesPicker)
                            @include('livewire.partials.filter-picker', [
                                'options' => $this->getDropdownOptions($filterField),
                                'selected' => $filterValueList,
                                'model' => 'filterValueList',
                                'pickerKey' => 'filter-picker-' . $filterField . '-' . $filterOperator,
                            ])
                        @elseif ($filterType === 'date' && $singleValue)
                            <input type="date" wire:model="filterValue" class="form-control">
                        @elseif ($filterType === 'number' && $singleValue)
                            <input type="number" step="any" wire:model="filterValue" class="form-control"
                                placeholder="Enter filter value">
                        @else
                            <input type="text" wire:model="filterValue" class="form-control"
                                placeholder="Enter filter value">
                        @endif

                        <small class="text-muted d-block mt-1">
                            @if ($usesPicker)
                            @elseif ($filterOperator === 'IN' || $filterOperator === 'NOT IN')
                                Use comma-separated values
                            @elseif ($filterOperator === 'BETWEEN')
                                Use format: start,end
                            @elseif ($filterOperator === 'LIKE' || $filterOperator === 'NOT LIKE')
                                Text search
                            @endif
                        </small>

                        @error('filterValue')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror
                        @error('filterValueList')
                            <small class="text-danger d-block">{{ $message }}</small>
                        @enderror

                        <button type="button" wire:click="addFilter" class="btn btn-sm btn-solen w-100 mt-2">
                            <i class="icofont-plus me-1"></i>Add filter
                        </button>
                    </div>

                    <p class="text-muted mt-2" style="font-size: 11.5px;">
                        Whoever runs this report can change these values without changing the report.
                    </p>
                </div>
            </div>
        </div>
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
                    <strong class="me-auto">Saved</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">{{ session('success') }}</div>
            </div>
        </div>
    @endif
</div>
