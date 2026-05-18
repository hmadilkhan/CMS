@extends('layouts.master')
@section('title', 'Role Permissions')
@section('content')
    @include('auth.partials.user-management-styles')
    <div class="user-management-page">
    <div class="user-management-heading">
        <h1><i class="icofont-shield me-2"></i>Role Permissions</h1>
    </div>
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title w-100">
                Role Permission
            </h4>
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ !empty($rolepermissions) ? route('update.role.permission') : route('store.permission') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($rolename) ? $rolename->id : '' }}">
                <div class="row mt-2 ">
                    <div class="col-sm-4">
                        <!-- <div class="form-group"> -->
                            <label>Roles</label>
                            <select id="role" name="role" class="form-control select2 @error('role') is-invalid @enderror"
                                style="width: 100%;">
                                <option value="">Select Roles</option>
                                @foreach ($roles as $role)
                                    <option
                                        {{ !empty($rolename) ? (in_array($role->name, $rolename->toArray()) ? 'selected' : '') : '' }}
                                        value="{{ $role->id }}">
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        <!-- </div> -->
                    </div>
                    <div class="col-sm-4">
                        <!-- <div class="form-group"> -->
                            <label>Permissions</label>
                            <select id="permission" name="permission[]" multiple
                                class="form-control select2 @error('permission') is-invalid @enderror"
                                style="width: 100%;">
                                <option value="">Select Permission</option>
                                @foreach ($permissions as $permission)
                                    <option
                                        {{ !empty($permission) ? (in_array($permission->name, $rolepermissions) ? 'selected' : '') : '' }}
                                        value="{{ $permission->id }}">
                                        {{ $permission->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('permission')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        <!-- </div> -->
                    </div>
                    <div class="col-4 mt-4">
                        <!-- <label></label> -->
                        <div class="form-group ">
                           
                            <button type="submit" name="buttonstatus" class="btn btn-primary " value="save"><i
                                    class="icofont-save"></i> Save
                            </button>
                            <button type="button" class="btn btn-danger text-white  ml-2"><i class="icofont-ban"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Users List</h3>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped datatable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Role Name</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $key => $rolepermission)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $rolepermission->name }}</td>
                            <td>
                                @foreach ($rolepermission->permissions as $permission)
                                    {{ $permission->name . ' ,' }}
                                @endforeach
                            </td>
                            <td class="text-center">
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit"
                                    href="{{ url('role-permission') . '/' . $rolepermission->id }}">
                                    <i class="icofont-pencil text-warning"></i></a>
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2"
                                    onclick="deleteRolePermission('{{ $rolepermission->id }}')">
                                    <i class="icofont-trash text-danger"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>
@endsection
@section('scripts')
    <script>

        $("#clickDemo").click(function() {
            $("#collapseOne").fadeToggle();
        })

        function deleteRolePermission(roleId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('delete.role.permission') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: roleId,
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire(
                                    'Deleted!',
                                    'Permission has been deleted.',
                                    'success'
                                )
                                location.reload();
                            }
                        },
                        error: function(error) {
                            Swal.fire(
                                'Error!',
                                'Some error occurred :)',
                                'error'
                            )
                        }
                    });
                }
                if (result.dismiss) {
                    Swal.fire(
                        'Cancelled!',
                        'Permission is safe :)',
                        'error'
                    )
                }
            })
        }
    </script>
@endsection
