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
        <h4 class="card-title">Change Redline Cost</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($loan) ? route('dealerfee.update',$loan->id) :  route('dealerfee.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($loan) ? $loan->id : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-4">
                    <label class="form-label">Loan Term</label>
                    <select class="form-select select2" aria-label="Default select Loan Term" id="loan_term_id" name="loan_term_id">
                        <option value="">Select Loan Term</option>
                        @foreach ($terms as $term)
                        <option {{(!empty($loan) && $loan->loan_term_id == $term->id ? 'selected' : '')}} value="{{ $term->id }}">
                            {{ $term->year }}
                        </option>
                        @endforeach
                    </select>
                    @error("loan_term_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4 ">
                    <!-- <div class="form-group"> -->
                    <label>APR</label>
                    <input type="text" class="form-control @error('apr') is-invalid @enderror" id="apr" name="apr" placeholder="Enter Apr" value="{{ !empty($loan) ? $loan->apr : old('apr') }}">
                    @error('apr')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-sm-4">
                    <!-- <div class="form-group"> -->
                    <label>Dealer Fee</label>
                    <input type="text" class="form-control @error('dealer_fee') is-invalid @enderror" id="dealer_fee" name="dealer_fee" placeholder="Enter Dealer Fee" value="{{ !empty($loan) ? $loan->dealer_fee : old('dealer_fee') }}">
                    @error('dealer_fee')
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
        <h4 class="card-title">Redline Cost List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Loan Term</th>
                    <th>Finance Option</th>
                    <th>Panel Qty</th>
                    <th>Redline Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dealerfeelist as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->loan->year }}</td>
                    <td>{{(!empty($list->loan->finance) ? $list->loan->finance->name : '') }}</td>
                    <td>{{ $list->apr }}</td>
                    <td>{{ $list->dealer_fee }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('view-dealer-fee',$list->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteDealerModal('{{ $list->id }}')">
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
            url: "{{ route('dealerfee.delete') }}",
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
</script>
@endsection