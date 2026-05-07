@extends("layouts.master")
@section('title', 'Sub Departments')
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
        <h1 class="operation-page-title">Sub Departments</h1>
        <p class="operation-page-subtitle">Maintain sub-department stages and ordering within each department.</p>
    </div>
    <div class="operation-summary">
        <span>Total Records</span>
        <strong>{{ $subDepartments->count() }}</strong>
    </div>
</div>
<div class="card operation-card">
    <div class="card-header">
        <h4 class="card-title">{{ !empty($subDepartment) ? 'Update Sub Department' : 'Add Sub Department' }}</h4>
    </div>
    <div class="card-body">
        <form class="operation-form" method="POST" action="{{ !empty($subDepartment) ? route('sub.department.update', $subDepartment->id) : route('sub.department.store') }}">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($subDepartment) ? $subDepartment->id : '' }}" />
            <div class="row g-3 align-items-start">
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label class="form-label">Department</label>
                    <select class="form-select select2 @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        @foreach ($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id', !empty($subDepartment) ? $subDepartment->department_id : '') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('department_id')
                    <div class="text-danger message mt-2">{{$message}}</div>
                    @enderror
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Sub Department Name</label>
                    <input type="text" required class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Sub Department Name" value="{{ old('name', !empty($subDepartment) ? $subDepartment->name : '') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                    <label>Order</label>
                    <input type="number" step="1" min="0" required class="form-control @error('order') is-invalid @enderror" id="order" name="order" placeholder="Enter Order" value="{{ old('order', !empty($subDepartment) ? $subDepartment->order : 0) }}">
                    @error('order')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="col-12">
                    <div class="operation-actions">
                        <button type="submit" class="btn btn-primary" value="save"><i class="icofont-save"></i> Save</button>
                        <a href="{{ route('sub.departments.list') }}" class="btn btn-outline-secondary"><i class="icofont-ban"></i> Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card operation-card mt-3">
    <div class="card-header">
        <h4 class="card-title">Sub Department List</h4>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-hover operation-table datatable">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Department</th>
                    <th>Name</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($subDepartments as $key => $list)
                <tr>
                    <td>{{ ++$key }}</td>
                    <td>{{ $list->department->name ?? 'N/A' }}</td>
                    <td>{{ $list->name }}</td>
                    <td class="cost-value">{{ $list->order }}</td>
                    <td class="text-center">
                        <a class="action-link" data-toggle="tooltip" title="Edit" href="{{ route('sub.departments.list', $list->id) }}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a class="action-link ml-2" data-toggle="tooltip" title="Delete" onclick="deleteSubDepartmentModal('{{ $list->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($subDepartments->isEmpty())
        <div class="empty-state">No sub departments have been added yet.</div>
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
                <button type="button" class="btn btn-danger color-fff" onclick="deleteSubDepartment()">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
    function deleteSubDepartmentModal(id) {
        $("#deleteId").val(id);
        $("#deleteproject").modal("show")
    }

    function deleteSubDepartment() {
        $.ajax({
            method: "POST",
            url: "{{ route('sub.department.delete') }}",
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
