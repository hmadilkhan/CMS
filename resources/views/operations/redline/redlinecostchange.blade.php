@extends("layouts.master")
@section('title', 'Module Types')
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
<style>
    .operation-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .operation-page-title {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
    }

    .operation-page-subtitle {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .operation-summary {
        min-width: 150px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        text-align: right;
    }

    .operation-summary span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .operation-summary strong {
        display: block;
        color: #111827;
        font-size: 24px;
        line-height: 1.1;
    }

    .operation-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
    }

    .operation-card .card-header {
        background: #fff;
        border-bottom: 1px solid #edf0f2;
        padding: 16px 18px;
    }

    .operation-card .card-title {
        margin: 0;
        color: #111827;
        font-size: 16px;
        font-weight: 700;
    }

    .operation-card .card-body {
        padding: 18px;
    }

    .operation-form label {
        color: #374151;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .operation-form .form-control,
    .operation-form .form-select {
        border-color: #d1d5db;
        min-height: 40px;
        width: 100%;
    }

    .operation-form .input-group-text {
        border-color: #d1d5db;
        background: #f9fafb;
        color: #6b7280;
        font-weight: 600;
    }

    .operation-form .cost-input-group {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 100%;
    }

    .operation-form .cost-input-group .input-group-text {
        flex: 0 0 42px;
        justify-content: center;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .operation-form .cost-input-group .form-control {
        flex: 1 1 auto;
        min-width: 0;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .operation-actions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        margin-top: 18px;
    }

    .operation-actions .btn {
        min-width: 96px;
    }

    .operation-table {
        margin-bottom: 0;
        width: 100%;
    }

    .operation-table thead th {
        border-bottom: 1px solid #e5e7eb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .operation-table tbody td {
        vertical-align: middle;
        color: #1f2937;
    }

    .cost-value {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }

    .action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        transition: background .15s ease, border-color .15s ease;
    }

    .action-link:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
        text-decoration: none;
    }

    .empty-state {
        padding: 32px 16px;
        color: #6b7280;
        text-align: center;
    }

    @media (max-width: 767px) {
        .operation-page-header {
            display: block;
        }

        .operation-summary {
            margin-top: 12px;
            text-align: left;
        }

        .operation-actions {
            justify-content: flex-start;
            margin-top: 4px;
            flex-wrap: wrap;
        }
    }
</style>
<div class="operation-page-header">
    <div>
        <h1 class="operation-page-title">Inverter Base Cost</h1>
        <p class="operation-page-subtitle">Maintain customer-facing and internal redline cost values by inverter type.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $redlinelist->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($redline) ? 'Update Base Cost' : 'Add Base Cost' }}</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form class="operation-form" method="POST" action="{{ !empty($redline) ? route('redlinecost.update',$redline->id) :  route('redlinecost.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($redline) ? $redline->id : '' }}" />
            <div class="row g-3 align-items-start">
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <label class="form-label">Inverter Type</label>
                    <select class="form-select select2" aria-label="Default select Inverter Type" id="inverter_type_id" name="inverter_type_id" required>
                        <option value="">Select Inverter Type</option>
                        @foreach ($inverters as $inverter)
                        <option {{(!empty($redline) && $inverter->id == $redline->inverter_type_id ? 'selected' : '')}} value="{{ $inverter->id }}">
                            {{ $inverter->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("inverter_type_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <label>Base Cost</label>
                    <div class="input-group cost-input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" required class="form-control @error('base_cost') is-invalid @enderror" id="base_cost" name="base_cost" placeholder="0.00" value="{{ old('base_cost', !empty($redline) ? $redline->base_cost : '') }}">
                    </div>
                    @error('base_cost')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <label>Internal Base Cost</label>
                    <div class="input-group cost-input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" required class="form-control @error('internal_base_cost') is-invalid @enderror" id="internal_base_cost" name="internal_base_cost" placeholder="0.00" value="{{ old('internal_base_cost', !empty($redline) ? $redline->internal_base_cost : '') }}">
                    </div>
                    @error('internal_base_cost')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                    <label>Internal Labor Cost</label>
                    <div class="input-group cost-input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" required class="form-control @error('internal_labor_cost') is-invalid @enderror" id="internal_labor_cost" name="internal_labor_cost" placeholder="0.00" value="{{ old('internal_labor_cost', !empty($redline) ? $redline->internal_labor_cost : '') }}">
                    </div>
                    @error('internal_labor_cost')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save">
                            <i class="icofont-save"></i> Save
                        </button>
                        <a href="{{ route('view-redline-cost') }}" class="btn btn-outline-secondary">
                            <i class="icofont-ban"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Base Cost List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Inverter</th>
                    <th>Base Cost</th>
                    <th>Internal Base Cost</th>
                    <th>Internal Labor Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($redlinelist as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->inverter->name ?? 'N/A' }}</td>
                    <td class="cost-value">$ {{ number_format($list->base_cost,2) }}</td>
                    <td class="cost-value">$ {{ number_format($list->internal_base_cost,2) }}</td>
                    <td class="cost-value">$ {{ number_format($list->internal_labor_cost,2) }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('view-redline-cost',$list->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteRedlineCost('{{ $list->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($redlinelist->isEmpty())
        <div class="empty-state">No inverter base costs have been added yet.</div>
        @endif
    </div>
</div>
<!-- Modal  Delete Folder/ File-->
<div class="modal fade" id="deleteproject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <input type="hidden" id="deleteId"/>
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteRedline()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    function deleteRedlineCost(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }
    function deleteRedline() {
        $.ajax({
            method: "POST",
            url: "{{ route('redlinecost.delete') }}",
            data: {
                _token: "{{csrf_token()}}",
                id: $("#deleteId").val()
            },
            success:function(response){
                if (response.status == 200) {
                    location.reload();
                }
            }
        });
    }
    // $("#inverter_type_id").change(function() {
    //     $.ajax({
    //         method: "POST",
    //         url: "{{ route('get-redline-cost') }}",
    //         data: {
    //             _token: "{{csrf_token()}}",
    //             inverter_type_id: $(this).val()
    //         },
    //         success: function(response) {
    //             $("#example1 tbody").empty();
    //             $.each(response.redlinecostlist, function(index, value) {
    //                 console.log(value);
    //             });

    //         },
    //         error: function(error) {
    //             console.log(error.responseJSON.message);
    //         }
    //     })
    // })
</script>
@endsection
