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
@include('operations.partials.index-styles')
<div class="operation-page-header">
    <div>
        <h1 class="operation-page-title">Assign Departments</h1>
        <p class="operation-page-subtitle">Connect employees with departments for operational ownership.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $assignDepartments->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($assignDepartment) ? 'Edit' : 'Add' }} Assign Department</h4>
    </div>
    <div class="card-body">
        <form class="operation-form" method="POST" action="{{ !empty($assignDepartment) ? route('assign-department.update', $assignDepartment->id) : route('assign-department.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($assignDepartment) ? $assignDepartment->id : '' }}" />
            <div class="row g-3 align-items-start">
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
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
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
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
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="icofont-save"></i> Save
                        </button>
                        <a href="{{ route('assign-department.index') }}" class="btn btn-outline-secondary">
                            <i class="icofont-ban"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Assign Departments List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
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
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('assign-department.index', $assign->id) }}">
                            <i class="icofont-pencil text-warning"></i>
                        </a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteModal('{{ $assign->id }}')">
                            <i class="icofont-trash text-danger"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($assignDepartments->isEmpty())
        <div class="empty-state">No department assignments have been added yet.</div>
        @endif
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
