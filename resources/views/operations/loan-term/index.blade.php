@extends("layouts.master")
@section('title', 'Loan Terms')
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
        <h4 class="card-title">Loan Terms</h4>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($loanTerm) ? route('loan.term.update',$loanTerm->id) :  route('loan.term.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($loanTerm) ? $loanTerm->id : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-4">
                    <label class="form-label">Finance Option</label>
                    <select class="form-select select2" aria-label="Default select Types" id="finance_option_id" name="finance_option_id">
                        <option value="">Select Finance</option>
                        @foreach ($financeOptions as $financeOption)
                        <option {{(!empty($loanTerm) && $loanTerm->finance_option_id == $financeOption->id ? 'selected' : '')}} value="{{ $financeOption->id }}">
                            {{ $financeOption->name }}
                        </option>
                        @endforeach
                    </select>
                    @error("finance_option_id")
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label>Loan Term</label>
                    <input type="text" class="form-control @error('year') is-invalid @enderror" id="year" name="year" placeholder="Enter Loan Term" value="{{ !empty($loanTerm) ? $loanTerm->year : old('year') }}">
                    @error('year')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
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
        <h4 class="card-title">Loan Terms</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Finance Option</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($loanTerms as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->finance->name ?? 'N/A' }}</td>
                    <td>{{ $list->year }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('loan.term',$list->id)}}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteModal('{{ $list->id }}')">
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteInverterType()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    function deleteModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteInverterType() {
        $.ajax({
            method: "POST",
            url: "{{ route('loan.term.delete') }}",
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
</script>
@endsection