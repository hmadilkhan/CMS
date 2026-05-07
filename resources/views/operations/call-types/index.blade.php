@extends("layouts.master")
@section('title', 'Call Types')
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
        <h1 class="operation-page-title">Call Types</h1>
        <p class="operation-page-subtitle">Maintain call type options used by call scripts and workflows.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $callTypes->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($callType) ? 'Update Call Type' : 'Add Call Type' }}</h4>
    </div>
    <div class="card-body">
        <form class="operation-form" method="POST" action="{{ !empty($callType) ? route('call.types.update', $callType->id) : route('call.types.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($callType) ? $callType->id : '' }}" />
            <div class="row g-3 align-items-start">
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Call Type Name</label>
                    <input type="text" required class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Call Type Name" value="{{ old('name', !empty($callType) ? $callType->name : '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('call.types.list') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Call Type List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($callTypes as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->name }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('call.types.list', $list->id) }}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteCallTypeModal('{{ $list->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($callTypes->isEmpty())
        <div class="empty-state">No call types have been added yet.</div>
        @endif
    </div>
</div>
<div class="modal fade" id="deleteproject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <input type="hidden" id="deleteId" />
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="deleteprojectLabel">Delete item Permanently?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body justify-content-center flex-column d-flex">
                <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger color-fff" onclick="deleteCallType()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    function deleteCallTypeModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteCallType() {
        $.ajax({
            method: "POST",
            url: "{{ route('call.types.delete') }}",
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
