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
@include('operations.partials.index-styles')
<div class="operation-page-header">
    <div>
        <h1 class="operation-page-title">Module Types</h1>
        <p class="operation-page-subtitle">Maintain module wattage, customer pricing, and internal module cost by inverter type.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $types->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($type) ? 'Update Module Type' : 'Create New Module' }}</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form class="operation-form" method="POST" action="{{ !empty($type) ? route('module-types.update',$type->id) :  route('module-types.store') }}">
            @csrf
            @if(!empty($type))
            @method("PUT")
            @endif
            <input type="hidden" name="id" value="{{ !empty($type) ? $type->id : '' }}" />
            <div class="row g-3 align-items-start">
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Name</label>
                    <input type="text" required class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Complete Name" value="{{ old('name', !empty($type) ? $type->name : '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label class="form-label">Inverter Type</label>
                    <select class="form-select select2" aria-label="Default select Inverter Type" id="inverter_type_id" name="inverter_type_id" required>
                        <option value="">Select Inverter Type</option>
                        @foreach ($inverterTypes as $inverter)
                        <option {{ old('inverter_type_id', !empty($type) ? $type->inverter_type_id : '') == $inverter->id ? 'selected' : '' }} value="{{ $inverter->id }}">
                            {{ $inverter->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("inverter_type_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Watt</label>
                    <input type="number" step="0.01" min="0" required class="form-control @error('value') is-invalid @enderror" id="value" name="value" placeholder="Enter Value in Watt" value="{{ old('value', !empty($type) ? $type->value : '') }}">
                    @error('value')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Amount</label>
                    <div class="input-group cost-input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" required class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" placeholder="0.00" value="{{ old('amount', !empty($type) ? $type->amount : '') }}">
                    </div>
                    @error('amount')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Internal Module Cost</label>
                    <div class="input-group cost-input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" class="form-control @error('internal_module_cost') is-invalid @enderror" id="internal_module_cost" name="internal_module_cost" placeholder="0.00" value="{{ old('internal_module_cost', !empty($type) ? $type->internal_module_cost : '') }}">
                    </div>
                    @error('internal_module_cost')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Module PTC Rating</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('ptc_rating') is-invalid @enderror" id="ptc_rating" name="ptc_rating" placeholder="0.00" value="{{ old('ptc_rating', !empty($type) ? $type->ptc_rating : '') }}">
                    @error('ptc_rating')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Module VOC Rating</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('voc_rating') is-invalid @enderror" id="voc_rating" name="voc_rating" placeholder="0.00" value="{{ old('voc_rating', !empty($type) ? $type->voc_rating : '') }}">
                    @error('voc_rating')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Module ISC Rating</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('isc_rating') is-invalid @enderror" id="isc_rating" name="isc_rating" placeholder="0.00" value="{{ old('isc_rating', !empty($type) ? $type->isc_rating : '') }}">
                    @error('isc_rating')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Module Weight</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" placeholder="0.00" value="{{ old('weight', !empty($type) ? $type->weight : '') }}">
                    @error('weight')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <label>Module Square Footage</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('square_footage') is-invalid @enderror" id="square_footage" name="square_footage" placeholder="0.00" value="{{ old('square_footage', !empty($type) ? $type->square_footage : '') }}">
                    @error('square_footage')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('module-types.index') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Module Type List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Inverter Type</th>
                    <th>Watt</th>
                    <th>Amount</th>
                    <th>Internal Module Cost</th>
                    <th>PTC Rating</th>
                    <th>VOC Rating</th>
                    <th>ISC Rating</th>
                    <th>Weight</th>
                    <th>Square Footage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $key => $type)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->inverter->name ?? 'N/A' }}</td>
                    <td class="cost-value">{{ $type->value }}</td>
                    <td class="cost-value">$ {{ number_format($type->amount, 2) }}</td>
                    <td class="cost-value">$ {{ number_format($type->internal_module_cost, 2) }}</td>
                    <td class="cost-value">{{ $type->ptc_rating !== null ? number_format($type->ptc_rating, 2) : '-' }}</td>
                    <td class="cost-value">{{ $type->voc_rating !== null ? number_format($type->voc_rating, 2) : '-' }}</td>
                    <td class="cost-value">{{ $type->isc_rating !== null ? number_format($type->isc_rating, 2) : '-' }}</td>
                    <td class="cost-value">{{ $type->weight !== null ? number_format($type->weight, 2) : '-' }}</td>
                    <td class="cost-value">{{ $type->square_footage !== null ? number_format($type->square_footage, 2) : '-' }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('module-types.edit',$type->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteModuleTypeModal('{{ $type->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($types->isEmpty())
        <div class="empty-state">No module types have been added yet.</div>
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteModuleType()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section("scripts")
<script>
    function deleteModuleTypeModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteModuleType() {
        $.ajax({
            method: "DELETE",
            url: "{{ url('module-types') }}" + "/" + $("#deleteId").val(),
            data: {
                _token: "{{csrf_token()}}",
                //     id: $("#deleteId").val()
            },
            success: function(response) {
                if (response.status == 200) {
                    location.reload();
                }
            }
        });
    }
</script>
@endsection
