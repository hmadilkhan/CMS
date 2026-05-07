@extends("layouts.master")
@section('title', 'Finance Options')
@section('content')
@if(session('success'))
<div class="alert alert-primary" role="alert">
    {{session('success')}}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger" role="alert">
    {{session('error')}}
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
        <form class="operation-form" method="POST" action="{{ !empty($finance) ? route('finance.option.update',$finance->id) :  route('finance.option.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($finance) ? $finance->id : '' }}" />
            <div class="row g-3 align-items-start">
               
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Adder Finance Option</label>
                    <input type="text" required class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Finance Option Name" value="{{ old('name', !empty($finance) ? $finance->name : '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label">Loan Id</label>
                    <select class="form-select select2" aria-label="Default select Finance Option" id="loan_id" name="loan_id" required>
                        <option value="1" @if(!empty($finance) && $finance->loan_id == 1) selected @endif>Yes</option>
                        <option value="0" @if(!empty($finance) && $finance->loan_id == 0) selected @endif {{ empty($finance) ? 'selected' : '' }}>No</option>
                    </select>
                    @error("loan_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label">Production Requirements</label>
                    <select class="form-select select2" aria-label="Default select Finance Option" id="production_requirements" name="production_requirements" required>
                        <option  @if(!empty($finance) && $finance->production_requirements == 1) selected @endif value="1">Yes</option>
                        <option  @if(!empty($finance) && $finance->production_requirements == 0) selected @endif  {{ empty($finance) ? 'selected' : '' }} value="0">No</option>
                    </select>
                    @error("production_requirements")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="positive_variance_div" style="display: none">
                    <label>Positive Variance</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('positive_variance') is-invalid @enderror" id="positive_variance" name="positive_variance" placeholder="Enter Positive Variance" value="{{ old('positive_variance', !empty($finance) ? $finance->positive_variance : '') }}">
                    @error('positive_variance')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="negative_variance_div" style="display: none">
                    <label>Negative Variance</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('negative_variance') is-invalid @enderror" id="negative_variance" name="negative_variance" placeholder="Enter Negative Variance" value="{{ old('negative_variance', !empty($finance) ? $finance->negative_variance : '') }}">
                    @error('negative_variance')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label">Dealer Fee</label>
                    <select class="form-select select2" aria-label="Default select Dealer Fee" id="dealer_fee" name="dealer_fee" required>
                        <option value="1" @if(!empty($finance) && $finance->dealer_fee == 1) selected @endif>Yes</option>
                        <option value="0" @if(!empty($finance) && $finance->dealer_fee == 0) selected @endif {{ empty($finance) ? 'selected' : '' }}>No</option>
                    </select>
                    @error("dealer_fee")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label">PTO Restriction</label>
                    <select class="form-select select2" aria-label="Default select PTO Restriction" id="pto_restriction" name="pto_restriction" required>
                        <option  @if(!empty($finance) && $finance->pto_restriction == 1) selected @endif value="1">Yes</option>
                        <option  @if(!empty($finance) && $finance->pto_restriction == 0) selected @endif  {{ empty($finance) ? 'selected' : '' }} value="0">No</option>
                    </select>
                    @error("pto_restriction")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="no_of_days_div" style="display: none">
                    <label>No of Days</label>
                    <input type="number" step="1" min="0" class="form-control @error('no_of_days') is-invalid @enderror" id="no_of_days" name="no_of_days" placeholder="Enter No of Days" value="{{ old('no_of_days', !empty($finance) ? $finance->no_of_days : '') }}">
                    @error('no_of_days')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label">Holdback</label>
                    <select class="form-select select2" aria-label="Default select holdback" id="holdback" name="holdback" required>
                        <option  @if(!empty($finance) && $finance->holdback == 1) selected @endif value="1">Yes</option>
                        <option  @if(!empty($finance) && $finance->holdback == 0) selected @endif  {{ empty($finance) ? 'selected' : '' }} value="0">No</option>
                    </select>
                    @error("holdback")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12" id="dollar_watt_value_div" style="display: none">
                    <label>$ / watt</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('dollar_watt_value') is-invalid @enderror" id="dollar_watt_value" name="dollar_watt_value" placeholder="Enter Dollar Watt Value" value="{{ old('dollar_watt_value', !empty($finance) ? $finance->dollar_watt_value : '') }}">
                    @error('dollar_watt_value')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('finance.option.types') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
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
                    <td>{{ $financeOption->dollar_watt_value  }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('finance.option.types',$financeOption->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteDealerModal('{{ $financeOption->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($financeOptions->isEmpty())
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
@section("scripts")
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
                _token: "{{csrf_token()}}",
                id: $("#deleteId").val()
            },
            success: function(response) {
                if (response.status == 200) {
                    location.reload();
                }
            }
        });
    }
    $(document).ready(function() {
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

        // Trigger change event on page load to set initial visibility
        $("#production_requirements").trigger("change");
        $("#pto_restriction").trigger("change");
        $("#holdback").trigger("change");
    });
</script>
@endsection
