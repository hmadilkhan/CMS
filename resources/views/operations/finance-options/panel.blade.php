{{--
    The Finance Options screen's body - see operations/departments/panel.blade.php
    for what `$console` means here.
--}}
@php
    $console = $console ?? false;
    $screenUrl = fn ($id = null) => $console
        ? route('operations.console', array_filter(['section' => 'finance-options', 'id' => $id]))
        : route('finance.option.types', array_filter(['id' => $id]));
@endphp
    @if (session('success'))
        <div class="alert alert-primary" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @include('operations.partials.index-styles')
    <div class="operation-page-header">
        <div>
            <h1 class="operation-page-title">Finance Options</h1>
            <p class="operation-page-subtitle">Maintain finance products, restrictions, variances, holdback settings, and milestone payments.</p>
        </div>
        <div class="operation-summary">
            <span>Total Records</span>
            <strong>{{ $financeOptions->count() }}</strong>
        </div>
    </div>
    <div class="card operation-card">
        <div class="card-header">
            <h4 class="card-title">{{ !empty($finance) ? 'Update Finance Option' : 'Add Finance Option' }}</h4>
        </div>
        <div class="card-body">
            <!-- ADD NEW PRODUCT PART START -->
            <form class="operation-form" method="POST"
                action="{{ !empty($finance) ? route('finance.option.update', $finance->id) : route('finance.option.store') }}">
                @csrf
                @if ($console)
                <input type="hidden" name="ops_section" value="finance-options" />
                @endif
                <input type="hidden" name="id" value="{{ !empty($finance) ? $finance->id : '' }}" />
                <div class="row g-3 align-items-start">

                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label>Adder Finance Option</label>
                        <input type="text" required class="form-control @error('name') is-invalid @enderror"
                            id="name" name="name" placeholder="Enter Finance Option Name"
                            value="{{ old('name', !empty($finance) ? $finance->name : '') }}">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label class="form-label">Loan Id</label>
                        <select class="form-select select2" aria-label="Default select Finance Option" id="loan_id"
                            name="loan_id" required>
                            <option value="1" @if (!empty($finance) && $finance->loan_id == 1) selected @endif>Yes</option>
                            <option value="0" @if (!empty($finance) && $finance->loan_id == 0) selected @endif
                                {{ empty($finance) ? 'selected' : '' }}>No</option>
                        </select>
                        @error('loan_id')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label class="form-label">Production Requirements</label>
                        <select class="form-select select2" aria-label="Default select Finance Option"
                            id="production_requirements" name="production_requirements" required>
                            <option @if (!empty($finance) && $finance->production_requirements == 1) selected @endif value="1">Yes</option>
                            <option @if (!empty($finance) && $finance->production_requirements == 0) selected @endif
                                {{ empty($finance) ? 'selected' : '' }} value="0">No</option>
                        </select>
                        @error('production_requirements')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="positive_variance_div" style="display: none">
                        <label>Positive Variance</label>
                        <input type="number" step="0.01" min="0"
                            class="form-control @error('positive_variance') is-invalid @enderror" id="positive_variance"
                            name="positive_variance" placeholder="Enter Positive Variance"
                            value="{{ old('positive_variance', !empty($finance) ? $finance->positive_variance : '') }}">
                        @error('positive_variance')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="negative_variance_div" style="display: none">
                        <label>Negative Variance</label>
                        <input type="number" step="0.01" min="0"
                            class="form-control @error('negative_variance') is-invalid @enderror" id="negative_variance"
                            name="negative_variance" placeholder="Enter Negative Variance"
                            value="{{ old('negative_variance', !empty($finance) ? $finance->negative_variance : '') }}">
                        @error('negative_variance')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label class="form-label">Dealer Fee</label>
                        <select class="form-select select2" aria-label="Default select Dealer Fee" id="dealer_fee"
                            name="dealer_fee" required>
                            <option value="1" @if (!empty($finance) && $finance->dealer_fee == 1) selected @endif>Yes</option>
                            <option value="0" @if (!empty($finance) && $finance->dealer_fee == 0) selected @endif
                                {{ empty($finance) ? 'selected' : '' }}>No</option>
                        </select>
                        @error('dealer_fee')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label class="form-label">PTO Restriction</label>
                        <select class="form-select select2" aria-label="Default select PTO Restriction" id="pto_restriction"
                            name="pto_restriction" required>
                            <option @if (!empty($finance) && $finance->pto_restriction == 1) selected @endif value="1">Yes</option>
                            <option @if (!empty($finance) && $finance->pto_restriction == 0) selected @endif
                                {{ empty($finance) ? 'selected' : '' }} value="0">No</option>
                        </select>
                        @error('pto_restriction')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="no_of_days_div" style="display: none">
                        <label>No of Days</label>
                        <input type="number" step="1" min="0"
                            class="form-control @error('no_of_days') is-invalid @enderror" id="no_of_days"
                            name="no_of_days" placeholder="Enter No of Days"
                            value="{{ old('no_of_days', !empty($finance) ? $finance->no_of_days : '') }}">
                        @error('no_of_days')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label class="form-label">Holdback</label>
                        <select class="form-select select2" aria-label="Default select holdback" id="holdback"
                            name="holdback" required>
                            <option @if (!empty($finance) && $finance->holdback == 1) selected @endif value="1">Yes</option>
                            <option @if (!empty($finance) && $finance->holdback == 0) selected @endif
                                {{ empty($finance) ? 'selected' : '' }} value="0">No</option>
                        </select>
                        @error('holdback')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="dollar_watt_value_div" style="display: none">
                        <label>$ / watt</label>
                        <input type="number" step="0.01" min="0"
                            class="form-control @error('dollar_watt_value') is-invalid @enderror" id="dollar_watt_value"
                            name="dollar_watt_value" placeholder="Enter Dollar Watt Value"
                            value="{{ old('dollar_watt_value', !empty($finance) ? $finance->dollar_watt_value : '') }}">
                        @error('dollar_watt_value')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label class="form-label">Milestone</label>
                        <select class="form-select select2" id="milestone_enabled" name="milestone_enabled" required>
                            <option value="1" @if (old('milestone_enabled', !empty($finance) ? $finance->milestone_enabled : 0) == 1) selected @endif>Yes</option>
                            <option value="0" @if (old('milestone_enabled', !empty($finance) ? $finance->milestone_enabled : 0) == 0) selected @endif>No</option>
                        </select>
                        @error('milestone_enabled')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- The milestone schedule. Each row: what fires it, how much
                         (fixed, a percent of its base, or what is left of its
                         base), and who the transaction is for. Zone triggers
                         come from the zones table, so a new zone shows up here
                         on its own. Only posted while Milestone is Yes. --}}
                    <div class="col-12" id="milestone_schedule_div" style="display: none">
                        @include('operations.finance-options.milestone-styles')
                        <input type="hidden" name="milestones_submitted" id="milestones_submitted" value="1">
                        <div class="ms-card">
                            <div class="ms-card-header">
                                <div class="ms-card-title">
                                    <span class="ms-card-icon"><i class="icofont-money-bag"></i></span>
                                    <div>
                                        <h5>Milestone Schedule <span class="ms-count" id="milestone_count">({{ count($milestoneRows) }})</span></h5>
                                        <p>Payments this finance option raises, and what fires each one.</p>
                                    </div>
                                </div>
                                <button type="button" class="ms-btn-neutral" id="milestone_add">
                                    <i class="icofont-plus"></i> New Milestone
                                </button>
                            </div>
                            @error('milestones')
                                <div class="ms-alert"><i class="icofont-warning-alt"></i> {{ $message }}</div>
                            @enderror
                            <div class="ms-table-wrap">
                                <table class="ms-table" id="milestone_table">
                                    <thead>
                                        <tr>
                                            <th class="ms-col-num">#</th>
                                            <th class="ms-col-label">Milestone</th>
                                            <th class="ms-col-trigger">Trigger</th>
                                            <th class="ms-col-type">Amount Type</th>
                                            <th class="ms-col-amount">Amount</th>
                                            <th class="ms-col-base">Of (Base)</th>
                                            <th class="ms-col-payee">Payee</th>
                                            <th class="ms-col-status">Status</th>
                                            <th class="ms-col-action"><span class="visually-hidden">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="milestone_rows">
                                        @foreach ($milestoneRows as $index => $row)
                                            @include('operations.finance-options.milestone-row', ['index' => $index, 'row' => $row])
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="ms-empty" id="milestone_empty" style="display: none">
                                    <i class="icofont-info-circle"></i>
                                    No milestones yet - this finance option raises no payments. Use <strong>New Milestone</strong> to add one.
                                </div>
                            </div>
                            {{-- Per-base totals, worked out as you type: a base whose
                                 percents pass 100% is flagged before the save refuses it. --}}
                            <div class="ms-card-footer" id="milestone_totals"></div>
                        </div>
                        <template id="milestone_row_template">
                            @include('operations.finance-options.milestone-row', ['index' => '__INDEX__', 'row' => ['amount_type' => 'percent', 'amount_base' => 'contract_amount', 'payee' => 'customer', 'is_active' => true]])
                        </template>
                    </div>
                    <div class="col-12">
                        <div class="operation-actions">
                            <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i>
                                Save</button>
                            <a href="{{ $screenUrl() }}" class="btn btn-outline-secondary"><i
                                    class="icofont-ban"></i> Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
            <!-- ADD NEW PRODUCT PART END -->
        </div>
    </div>
    <div class="card operation-card mt-3">
        <div class="card-header">
            <h4 class="card-title">Milestone Email Recipients</h4>
        </div>
        <div class="card-body">
            <form class="operation-form mb-3" method="POST" action="{{ route('finance.milestone.email.mode.update') }}">
                @csrf
                @if ($console)
                <input type="hidden" name="ops_section" value="finance-options" />
                @endif
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                        <label class="form-label">Current Email Mode</label>
                        <select class="form-select" name="email_mode" required>
                            <option value="test" @if ($milestoneEmailMode === 'test') selected @endif>Test</option>
                            <option value="production" @if ($milestoneEmailMode === 'production') selected @endif>Production</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-12">
                        <button type="submit" class="btn btn-primary w-100"><i class="icofont-save"></i> Save Mode</button>
                    </div>
                </div>
            </form>
            <form class="operation-form mb-3" method="POST" action="{{ route('finance.milestone.recipient.store') }}">
                @csrf
                @if ($console)
                <input type="hidden" name="ops_section" value="finance-options" />
                @endif
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-lg-5 col-md-6 col-12">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter recipient email" required>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4 col-12">
                        <label class="form-label">Mode</label>
                        <select class="form-select" name="mode" required>
                            <option value="test">Test</option>
                            <option value="production">Production</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-12">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="is_active" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-12">
                        <button type="submit" class="btn btn-primary w-100"><i class="icofont-plus"></i> Add</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover operation-table mb-0">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($milestoneEmailRecipients as $recipient)
                            <tr>
                                <td colspan="4">
                                    <form method="POST" action="{{ route('finance.milestone.recipient.update') }}" class="row g-2 align-items-center">
                                        @csrf
                                        @if ($console)
                                        <input type="hidden" name="ops_section" value="finance-options" />
                                        @endif
                                        <input type="hidden" name="id" value="{{ $recipient->id }}">
                                        <div class="col-lg-5 col-md-12">
                                            <input type="email" class="form-control" name="email" value="{{ $recipient->email }}" required>
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <select class="form-select" name="mode" required>
                                                <option value="test" @if ($recipient->mode === 'test') selected @endif>Test</option>
                                                <option value="production" @if ($recipient->mode === 'production') selected @endif>Production</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <select class="form-select" name="is_active" required>
                                                <option value="1" @if ($recipient->is_active) selected @endif>Active</option>
                                                <option value="0" @if (!$recipient->is_active) selected @endif>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-3 col-md-4 d-flex gap-2">
                                            <button type="submit" class="btn btn-sm btn-primary"><i class="icofont-save"></i> Update</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteMilestoneRecipient('{{ $recipient->id }}')">
                                                <i class="icofont-trash"></i> Delete
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No milestone email recipients added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card operation-card mt-3">
        <div class="card-header">
            <h4 class="card-title">Finance Options</h4>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-hover operation-table datatable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Name</th>
                        <th>Loan Id</th>
                        <th>Production Requirements</th>
                        <th>Positive Variance</th>
                        <th>Negative Variance</th>
                        <th>Dealer Fee</th>
                        <th>PTO Restriction</th>
                        <th>No of Days</th>
                        <th>Holdback</th>
                        <th>Dollar Watt Value</th>
                        <th>Milestone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($financeOptions as $key => $financeOption)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $financeOption->name }}</td>
                            <td>{{ $financeOption->loan_id == 1 ? 'Yes' : 'No' }}</td>
                            <td>{{ $financeOption->production_requirements == 1 ? 'Yes' : 'No' }}</td>
                            <td>{{ $financeOption->positive_variance }}</td>
                            <td>{{ $financeOption->negative_variance }}</td>
                            <td>{{ $financeOption->dealer_fee == 1 ? 'Yes' : 'No' }}</td>
                            <td>{{ $financeOption->pto_restriction == 1 ? 'Yes' : 'No' }}</td>
                            <td>{{ $financeOption->no_of_days }}</td>
                            <td>{{ $financeOption->holdback == 1 ? 'Yes' : 'No' }}</td>
                            <td>{{ $financeOption->dollar_watt_value }}</td>
                            <td>{{ $financeOption->milestone_enabled ? 'Yes (' . $financeOption->milestones->where('is_active', true)->count() . ')' : 'No' }}</td>
                            <td class="text-center">
                                <a class="action-link" data-toggle="tooltip" title="Edit"
                                    href="{{ $screenUrl($financeOption->id) }}">
                                    <i class="icofont-pencil text-warning"></i></a>
                                <a class="action-link ml-2" data-toggle="tooltip" title="Delete"
                                    onclick="deleteDealerModal('{{ $financeOption->id }}')">
                                    <i class="icofont-trash text-danger"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($financeOptions->isEmpty())
                <div class="empty-state">No finance options have been added yet.</div>
            @endif
        </div>
    </div>
    <!-- Modal  Delete Folder/ File-->
    {{-- Outside the finance option form, so Enter here never submits that form. --}}
    <div class="modal fade" id="milestone_payee_modal" tabindex="-1" aria-labelledby="milestone_payee_modal_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="milestone_payee_modal_label">New Payee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="milestone_payee_label">Payee name</label>
                    <input type="text" class="form-control" id="milestone_payee_label" maxlength="100" autocomplete="off" placeholder="e.g. Solar Fund LLC">
                    <div class="text-danger small mt-1" id="milestone_payee_error" style="display: none"></div>
                    <div class="form-text">Added to the payee list for every milestone and account transaction.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="milestone_payee_save"><i class="icofont-plus"></i> Add Payee</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteproject" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
            <input type="hidden" id="deleteId" />
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Delete item Permanently?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body justify-content-center flex-column d-flex">
                    <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                    <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger color-fff" onclick="deleteDealerFee()">Delete</button>
                </div>
            </div>
        </div>
    </div>
@section('scripts')
    <script>
        function deleteDealerModal(id) {
            $("#deleteId").val(id);
            $("#deleteproject").modal("show")
        }

        function deleteDealerFee() {
            $.ajax({
                method: "POST",
                url: "{{ route('finance.option.delete') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: $("#deleteId").val()
                },
                success: function(response) {
                    if (response.status == 200) {
                        location.reload();
                    }
                }
            });
        }

        $("#production_requirements").change(function() {
            if ($(this).val() == 1) {
                $("#positive_variance_div").show();
                $("#negative_variance_div").show();
                $("#positive_variance, #negative_variance").prop("required", true);
            } else {
                $("#positive_variance_div").hide();
                $("#negative_variance_div").hide();
                $("#positive_variance, #negative_variance").prop("required", false);
            }
        });

        $("#pto_restriction").change(function() {
            if ($(this).val() == 1) {
                $("#no_of_days_div").show();
                $("#no_of_days").prop("required", true);
            } else {
                $("#no_of_days_div").hide();
                $("#no_of_days").prop("required", false);
            }
        });

        $("#holdback").change(function() {
            if ($(this).val() == 1) {
                $("#dollar_watt_value_div").show();
                $("#dollar_watt_value").prop("required", true);
            } else {
                $("#dollar_watt_value_div").hide();
                $("#dollar_watt_value").prop("required", false);
            }
        });

        {{-- Milestone off: the schedule is hidden and not posted at all, so a
             hidden row can neither block the save nor be rewritten. --}}
        $("#milestone_enabled").change(function() {
            var enabled = $(this).val() == 1;

            $("#milestone_schedule_div").toggle(enabled);
            $("#milestone_schedule_div").find("input, select").prop("disabled", !enabled);
        });

        function renumberMilestones() {
            var rows = $("#milestone_rows .milestone-row");

            rows.each(function(index) {
                $(this).find(".ms-row-number").text(index + 1);
                $(this).find("[data-field]").each(function() {
                    $(this).attr("name", "milestones[" + index + "][" + $(this).data("field") + "]");
                });
            });

            $("#milestone_count").text("(" + rows.length + ")");
            $("#milestone_table").toggle(rows.length > 0);
            $("#milestone_empty").toggle(rows.length === 0);
            renderMilestoneTotals();
        }

        {{-- Remaining has no amount of its own: it takes what is left of its base. --}}
        function syncMilestoneAmount(row) {
            var type = row.find(".milestone-amount-type").val();
            var remaining = type === "remaining";
            var input = row.find(".milestone-amount-value");

            row.find(".ms-amount").removeClass("is-fixed is-percent is-remaining").addClass("is-" + type);
            input.prop("readonly", remaining).prop("required", !remaining);
            if (remaining) {
                input.val("");
            }
            input.attr("placeholder", remaining ? "Balance" : (type === "fixed" ? "0.00" : "0"));
        }

        function syncMilestoneStatus(row) {
            var active = row.find(".milestone-active").is(":checked");

            row.toggleClass("is-inactive", !active);
            row.find(".ms-toggle-label").text(active ? "Active" : "Inactive");
        }

        {{-- One chip per base across the active rows: its percents, its fixed
             amounts, and whether a Remaining row closes it. --}}
        function renderMilestoneTotals() {
            var bases = {};

            $("#milestone_rows .milestone-row").each(function() {
                var row = $(this);

                if (!row.find(".milestone-active").is(":checked")) {
                    return;
                }

                var baseSelect = row.find(".milestone-amount-base");
                var key = baseSelect.val();
                var type = row.find(".milestone-amount-type").val();
                var value = parseFloat(row.find(".milestone-amount-value").val()) || 0;

                bases[key] = bases[key] || { label: baseSelect.find("option:selected").text(), percent: 0, fixed: 0, remaining: false };

                if (type === "percent") {
                    bases[key].percent += value;
                } else if (type === "fixed") {
                    bases[key].fixed += value;
                } else {
                    bases[key].remaining = true;
                }
            });

            var footer = $("#milestone_totals").empty();

            $.each(bases, function(key, base) {
                var parts = [];

                if (base.percent) {
                    parts.push(Math.round(base.percent * 100) / 100 + "%");
                }
                if (base.fixed) {
                    parts.push("$" + base.fixed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }
                if (base.remaining) {
                    parts.push("Remaining");
                }

                $("<span>", { "class": "ms-total" + (base.percent > 100 ? " is-over" : "") })
                    .append($("<span>").text(base.label + ":"))
                    .append($("<strong>").text(parts.join(" + ") || "-"))
                    .appendTo(footer);
            });

            if (!$.isEmptyObject(bases)) {
                footer.prepend($("<span>").text("Totals per base"));
            }
        }

        $("#milestone_add").on("click", function() {
            var html = $("#milestone_row_template").html().replace(/__INDEX__/g, $("#milestone_rows .milestone-row").length);
            var row = $($.parseHTML($.trim(html))).filter("tr");

            $("#milestone_rows").append(row);
            syncMilestoneAmount(row);
            syncMilestoneStatus(row);
            renumberMilestones();
            row.find("[data-field=label]").trigger("focus");
        });

        $("#milestone_rows").on("click", ".milestone-remove", function() {
            $(this).closest(".milestone-row").remove();
            renumberMilestones();
        });

        $("#milestone_rows").on("change", ".milestone-amount-type", function() {
            syncMilestoneAmount($(this).closest(".milestone-row"));
            renderMilestoneTotals();
        });

        $("#milestone_rows").on("change", ".milestone-active", function() {
            syncMilestoneStatus($(this).closest(".milestone-row"));
            renderMilestoneTotals();
        });

        $("#milestone_rows").on("input change", ".milestone-amount-value, .milestone-amount-base", renderMilestoneTotals);

        $("#milestone_rows .milestone-row").each(function() {
            syncMilestoneAmount($(this));
            syncMilestoneStatus($(this));
        });
        renumberMilestones();

        {{-- "+ Add new payee…" opens the dialog; the select keeps its previous
             payee until the new one is saved, and goes back to it on cancel. --}}
        var payeeSelect = null;

        $("#milestone_rows").on("focus", ".milestone-payee", function() {
            if ($(this).val() !== "__new__") {
                $(this).data("previous", $(this).val());
            }
        });

        $("#milestone_rows").on("change", ".milestone-payee", function() {
            if ($(this).val() !== "__new__") {
                $(this).data("previous", $(this).val());
                return;
            }

            payeeSelect = $(this);
            $("#milestone_payee_label").val("");
            $("#milestone_payee_error").hide().text("");
            $("#milestone_payee_modal").modal("show");
        });

        $("#milestone_payee_modal").on("shown.bs.modal", function() {
            $("#milestone_payee_label").trigger("focus");
        });

        $("#milestone_payee_modal").on("hidden.bs.modal", function() {
            if (payeeSelect && payeeSelect.val() === "__new__") {
                payeeSelect.val(payeeSelect.data("previous") || "customer");
            }
            payeeSelect = null;
        });

        $("#milestone_payee_label").on("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                $("#milestone_payee_save").trigger("click");
            }
        });

        {{-- Every payee select, the new-row template's included, gets the payee. --}}
        function addPayeeOption(key, label) {
            var template = document.getElementById("milestone_row_template");
            var selects = $("#milestone_rows .milestone-payee").add($(template.content).find(".milestone-payee"));

            selects.each(function() {
                if ($(this).find("option").filter(function() { return this.value === key; }).length === 0) {
                    $("<option>", { value: key }).text(label).insertBefore($(this).find("option[value='__new__']"));
                }
            });
        }

        $("#milestone_payee_save").on("click", function() {
            var button = $(this);
            var label = $.trim($("#milestone_payee_label").val());

            if (label === "") {
                $("#milestone_payee_error").text("Enter a payee name.").show();
                return;
            }

            button.prop("disabled", true);

            $.ajax({
                method: "POST",
                url: "{{ route('finance.milestone.payee.store') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    label: label
                },
                success: function(response) {
                    addPayeeOption(response.key, response.label);
                    if (payeeSelect) {
                        payeeSelect.val(response.key).data("previous", response.key);
                    }
                    $("#milestone_payee_modal").modal("hide");
                },
                error: function(xhr) {
                    var errors = xhr.responseJSON && xhr.responseJSON.errors;
                    $("#milestone_payee_error").text(errors && errors.label ? errors.label[0] : "The payee could not be added.").show();
                },
                complete: function() {
                    button.prop("disabled", false);
                }
            });
        });

        function deleteMilestoneRecipient(id) {
            if (!confirm("Delete this milestone recipient?")) {
                return;
            }

            $.ajax({
                method: "POST",
                url: "{{ route('finance.milestone.recipient.delete') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id
                },
                success: function(response) {
                    if (response.status == 200) {
                        location.reload();
                    }
                }
            });
        }

        // Trigger change event on page load to set initial visibility
        $("#production_requirements").trigger("change");
        $("#pto_restriction").trigger("change");
        $("#holdback").trigger("change");
        $("#milestone_enabled").trigger("change");
    </script>
@endsection
