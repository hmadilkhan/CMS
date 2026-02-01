@extends("layouts.master")
@section('title', 'Assign Department')
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
        <h4 class="card-title">{{ !empty($assignDepartment) ? 'Edit' : 'Add' }} Assign Department</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ !empty($assignDepartment) ? route('assign-department.update', $assignDepartment->id) : route('assign-department.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($assignDepartment) ? $assignDepartment->id : '' }}" />
            <div class="row g-3 mb-3 align-items-center">
                <div class="col-sm-4">
                    <label>Department <span class="text-danger">*</span></label>
                    <select class="form-select select2 @error('department_id') is-invalid @enderror" name="department_id" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ (!empty($assignDepartment) && $assignDepartment->department_id == $department->id) ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('department_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-sm-4">
                    <label>Employee <span class="text-danger">*</span></label>
                    <select class="form-select select2 @error('employee_id')  is-invalid @enderror" name="employee_id" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ (!empty($assignDepartment) && $assignDepartment->employee_id == $employee->id) ? 'selected' : '' }}>
                            {{ $employee->user->name ?? 'N/A' }}
                        </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-4 mt-3">
                    <label></label>
                    <div class="form-group float-left">
                        <a href="{{ route('assign-department.index') }}" class="btn btn-danger float-right ml-2 text-white">
                            <i class="icofont-ban"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary float-right">
                            <i class="icofont-save"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card mt-3">
    <div class="card-header">
        <h4 class="card-title">Assign Departments List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Department</th>
                    <th>Employee</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assignDepartments as $key => $assign)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $assign->department->name ?? 'N/A' }}</td>
                    <td>{{ $assign->employee->user->name ?? 'N/A' }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ route('assign-department.index', $assign->id) }}">
                            <i class="icofont-pencil text-warning"></i>
                        </a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteModal('{{ $assign->id }}')">
                            <i class="icofont-trash text-danger"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<!-- Modal Delete -->
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteAssignDepartment()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    function deleteModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show");
    }

    function deleteAssignDepartment() {
        $.ajax({
            method: "POST",
            url: "{{ route('assign-department.destroy') }}",
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
