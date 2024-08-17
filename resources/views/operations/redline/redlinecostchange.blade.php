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
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Inverter Base Cost</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($redline) ? route('redlinecost.update',$redline->id) :  route('redlinecost.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($redline) ? $redline->id : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-4">
                    <label class="form-label">Inverter Type</label>
                    <select class="form-select select2" aria-label="Default select Inverter Type" id="inverter_type_id" name="inverter_type_id">
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
                <div class="col-sm-4">
                    <!-- <div class="form-group"> -->
                    <label>Base Cost</label>
                    <input type="text" class="form-control @error('base_cost') is-invalid @enderror" id="base_cost" name="base_cost" placeholder="Enter Redline Cost" value="{{ !empty($redline) ? $redline->base_cost : old('base_cost') }}">
                    @error('base_cost')
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
        <h4 class="card-title">Base Cost List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable table-responsive">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Inverter</th>
                    <th>Base Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($redlinelist as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->inverter->name }}</td>
                    <td>$ {{ number_format($list->base_cost,2) }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('view-redline-cost',$list->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteRedlineCost('{{ $list->id }}')">
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