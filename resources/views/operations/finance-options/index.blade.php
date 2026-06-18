@extends('layouts.master')
@section('title', 'Finance Options')
@section('content')
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
            <p class="operation-page-subtitle">Maintain finance products, restrictions, variances, and holdback settings.</p>
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
                    <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="milestone_amount_source_div" style="display: none">
                        <label class="form-label">Milestone Amount Source</label>
                        <select class="form-select select2" id="milestone_amount_source" name="milestone_amount_source" required>
                            <option value="contract_amount" @if (old('milestone_amount_source', !empty($finance) ? $finance->milestone_amount_source : 'contract_amount') == 'contract_amount') selected @endif>Contract Amount</option>
                            <option value="customer_portion" @if (old('milestone_amount_source', !empty($finance) ? $finance->milestone_amount_source : 'contract_amount') == 'customer_portion') selected @endif>Customer Portion</option>
                        </select>
                        @error('milestone_amount_source')
                            <div class="text-danger message mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12" id="milestone_schedule_div" style="display: none">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover operation-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Milestone</th>
                                        <th>Trigger</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Deal Review</td>
                                        <td>Project Create</td>
                                        <td>$1,000 fixed</td>
                                    </tr>
                                    <tr>
                                        <td>Permit Approval Date</td>
                                        <td>Permitting Approval Date filled</td>
                                        <td>50%</td>
                                    </tr>
                                    <tr>
                                        <td>Solar Install Date</td>
                                        <td>Solar Install Date filled</td>
                                        <td>35%</td>
                                    </tr>
                                    <tr>
                                        <td>Inspection Approval Date</td>
                                        <td>Inspection Approval Date filled</td>
                                        <td>Remaining Amount</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="operation-actions">
                            <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i>
                                Save</button>
                            <a href="{{ route('finance.option.types') }}" class="btn btn-outline-secondary"><i
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
                        <th>Amount Source</th>
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
                            <td>{{ $financeOption->milestone_enabled ? 'Yes' : 'No' }}</td>
                            <td>{{ $financeOption->milestone_amount_source == 'customer_portion' ? 'Customer Portion' : 'Contract Amount' }}</td>
                            <td class="text-center">
                                <a class="action-link" data-toggle="tooltip" title="Edit"
                                    href="{{ route('finance.option.types', $financeOption->id) }}">
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
@endsection
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

        $("#milestone_enabled").change(function() {
            if ($(this).val() == 1) {
                $("#milestone_amount_source_div").show();
                $("#milestone_schedule_div").show();
                $("#milestone_amount_source").prop("required", true);
            } else {
                $("#milestone_amount_source_div").hide();
                $("#milestone_schedule_div").hide();
                $("#milestone_amount_source").prop("required", false);
            }
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
