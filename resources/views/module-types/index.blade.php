@extends("layouts.master")
@section('title', 'Module Types')
@section('content')
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Create New Module</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($type) ? route('module-types.update',$type->id) :  route('module-types.store') }}">
            @csrf
            @if(!empty($type))
            @method("PUT")
            @endif
            <input type="hidden" name="id" value="{{ !empty($type) ? $type->id : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-3 ">
                    <label>Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Complete Name" value="{{ !empty($type) ? $type->name : old('name') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-sm-3">
                    <label class="form-label">Inverter Type</label>
                    <select class="form-select select2" aria-label="Default select Inverter Type" id="inverter_type_id" name="inverter_type_id">
                        <option value="">Select Inverter Type</option>
                        @foreach ($inverterTypes as $inverter)
                        <option {{(!empty($type) && $inverter->id == $type->inverter->id ? 'selected' : '')}} value="{{ $inverter->id }}">
                            {{ $inverter->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("inverter_type_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-3">
                    <label>Watt</label>
                    <input type="text" class="form-control @error('value') is-invalid @enderror" id="value" name="value" placeholder="Enter Value in Watt" value="{{ !empty($type) ? $type->value : old('value') }}">
                    @error('value')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-sm-3">
                    <label>Amount</label>
                    <input type="text" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" placeholder="Enter Amount" value="{{ !empty($type) ? $type->amount : old('amount') }}">
                    @error('amount')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-sm-3">
                    <label>Internal Module Cost</label>
                    <input type="text" class="form-control @error('internal_module_cost') is-invalid @enderror" id="internal_module_cost" name="internal_module_cost" placeholder="Enter Internal Module Cost" value="{{ !empty($type) ? $type->internal_module_cost : old('internal_module_cost') }}">
                    @error('internal_module_cost')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-3">
                    <label></label>
                    <div class="form-group ">
                        <button type="button" class="btn btn-danger float-right ml-2 text-white"><i class="icofont-ban"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary float-right " value="save"><i class="icofont-save"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>
<div class="card mt-3">
    <div class="card-header">
        <h4 class="card-title">Module Type List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Inverter Type</th>
                    <th>Watt</th>
                    <th>Amount</th>
                    <th>Internal Module Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types as $key => $type)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->inverter->name }}</td>
                    <td>{{ $type->value }}</td>
                    <td>{{ $type->amount }}</td>
                    <td>{{ $type->internal_module_cost }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('module-types.edit',$type->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteModuleType('{{ $type->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
    function deleteModuleType(id) {
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