@extends("layouts.master")
@section('title', 'Adders')
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
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Add Adders</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($adder) ? route('adders.update',$adder->id) :  route('adder.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($adder) ? $adder->id : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-4">
                    <label class="form-label">Types</label>
                    <select class="form-select select2" aria-label="Default select Types" id="adder_type_id" name="adder_type_id">
                        <option value="">Select Types</option>
                        @foreach ($types as $type)
                        <option {{(!empty($adder) && $adder->adder_type_id == $type->id ? 'selected' : '')}} value="{{ $type->id }}">
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("adder_type_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Sub Types</label>
                    <select class="form-select select2" aria-label="Default select Sub Type" id="adder_sub_type_id" name="adder_sub_type_id">
                        <option value="">Select Sub Type</option>
                    </select>
                    @error("adder_sub_type_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Units</label>
                    <select class="form-select select2" aria-label="Default select Unit" id="adder_unit_id" name="adder_unit_id">
                        <option value="">Select Unit</option>
                        @foreach ($units as $unit)
                        <option {{(!empty($adder) && $adder->adder_unit_id == $unit->id ? 'selected' : '')}} value="{{ $unit->id }}">
                            {{ $unit->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("adder_unit_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4 ">
                    <!-- <div class="form-group"> -->
                    <label>Price</label>
                    <input type="text" class="form-control @error('price') is-invalid @enderror" id="price" name="price" placeholder="Enter Price" value="{{ !empty($adder) ? $adder->price : old('price') }}">
                    @error('price')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-4 mt-3">
                    <label></label>
                    <div class="form-group float-left ">
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
        <h4 class="card-title">Adders List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Type</th>
                    <th>Sub Type</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adders as $key => $adderList)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $adderList->type->name }}</td>
                    <td>{{$adderList->subtype->name}}</td>
                    <td>{{ $adderList->unit->name }}</td>
                    <td>{{ $adderList->price }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('view-adders',$adderList->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteDealerModal('{{ $adderList->id }}')">
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
            url: "{{ route('adders.delete') }}",
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
    $("#adder_type_id").change(function() {
        getSubTypes();
    });

    function getSubTypes()
    {
        let adder_sub_type_id = "{{!empty($adder) ? $adder->adder_sub_type_id: ''}}";
        $.ajax({
            method: "POST",
            url: "{{ route('get.sub.types') }}",
            data: {
                _token: "{{csrf_token()}}",
                id: $("#adder_type_id").val()
            },
            success: function(response) {
                console.log(response);
                $("#adder_sub_type_id").empty();
                if (response.status == 200) {
                    $.each(response.subtypes, function(index, item) {
                        $('#adder_sub_type_id').append($('<option '+(adder_sub_type_id != "" && adder_sub_type_id ==  item.id ? 'selected' : '')+' value="' + item.id + '">' + item.name + '</option>'));
                    });
                }
            }
        });
    }
    @if(!empty($adder))
        getSubTypes()
    @endif
</script>
@endsection