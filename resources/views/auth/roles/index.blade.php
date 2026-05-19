@extends('layouts.master')
@section('title', 'Roles')
@section('content')
    @if (session('success'))
        <div class="alert alert-primary" role="alert">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @include('operations.partials.index-styles')
    @include('auth.partials.user-management-styles')

    <div class="user-management-page">
    <div class="operation-page-header">
        <div>
            <h1 class="operation-page-title">Roles</h1>
            <p class="operation-page-subtitle">Create and maintain system roles used for access control across the CRM.</p>
        </div>
        <div class="operation-summary">
            <span>Total Roles</span>
            <strong>{{ $roles->count() }}</strong>
        </div>
    </div>

    <div class="user-management-section">
        <div class="user-management-section-header">
            <h4 class="user-management-section-title">{{ !empty($role) ? 'Update Role' : 'Add Role' }}</h4>
        </div>
        <div class="user-management-section-body">
            <form class="operation-form" method="POST" action="{{ !empty($role) ? route('update.role') : route('save.role') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($role) ? $role->id : '' }}">

                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <label for="name">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" placeholder="Enter Role Name"
                            value="{{ old('name', !empty($role) ? $role->name : '') }}" required maxlength="255">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        @error('id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-xl-4 col-lg-6 col-md-6 col-12">
                        <div class="operation-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="icofont-save"></i> Save
                            </button>
                            <a href="{{ route('role') }}" class="btn btn-outline-secondary">
                                <i class="icofont-ban"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="user-management-section mt-3">
        <div class="user-management-section-header">
            <h4 class="user-management-section-title">Roles List</h4>
        </div>
        <div class="user-management-section-body">
            <table id="example1" class="table table-hover operation-table datatable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Role Name</th>
                        <th>Guard</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $key => $list)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $list->name }}</td>
                            <td>{{ $list->guard_name }}</td>
                            <td class="text-center">
                                <a class="action-link" data-toggle="tooltip" title="Edit"
                                    href="{{ route('role', $list->id) }}">
                                    <i class="icofont-pencil text-primary"></i>
                                </a>
                                <a class="action-link ml-2" data-toggle="tooltip" title="Delete"
                                    onclick="deleteRoleModal('{{ $list->id }}')">
                                    <i class="icofont-trash text-danger"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($roles->isEmpty())
                <div class="empty-state">No roles have been added yet.</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
            <input type="hidden" id="deleteId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="deleteRoleModalLabel">Delete Role Permanently?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body justify-content-center flex-column d-flex">
                    <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                    <p class="mt-4 fs-5 text-center">This role will be removed from the CRM if it is not assigned to any users.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger color-fff" onclick="deleteRole()">Delete</button>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
@section('scripts')
    <script>
        function deleteRoleModal(roleId) {
            $("#deleteId").val(roleId);
            $("#deleteRoleModal").modal("show");
        }

        function deleteRole() {
            $.ajax({
                url: "{{ route('delete.role') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: $("#deleteId").val(),
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        location.reload();
                    }
                },
                error: function(error) {
                    const message = error.responseJSON?.message || 'Some error occurred :)';
                    $("#deleteRoleModal").modal("hide");
                    Swal.fire(
                        'Error!',
                        message,
                        'error'
                    );
                }
            });
        }
    </script>
@endsection
