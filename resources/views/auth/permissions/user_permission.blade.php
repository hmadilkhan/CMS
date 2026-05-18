@extends('layouts.master')
@section('title', 'User Permissions')
@section('content')
    @include('auth.partials.user-management-styles')
    <div class="user-management-page">
    <div class="user-management-heading">
        <h1><i class="icofont-user-suited me-2"></i>User Permissions</h1>
    </div>
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title w-100">
                Users Permission
            </h4>
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ !empty($userpermissions) ? route('update.user.permission') : route('store.user.permission') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($username) ? $username->id : '' }}">
                <div class="row mt-2 ">
                    <div class="col-sm-4">
                        <!-- <div class="form-group"> -->
                            <label>Users</label>
                            <select id="user" name="user" class="form-control select2 @error('role') is-invalid @enderror"
                                style="width: 100%;">
                                <option value="">Select User</option>
                                @foreach ($users as $user)
                                    <option
                                        {{ !empty($username) ? (in_array($user->name, $username->toArray()) ? 'selected' : '') : '' }}
                                        value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user')
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
                                        {{ !empty($permission) ? (in_array($permission->name, $userpermissions) ? 'selected' : '') : '' }}
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
                        <!-- <div class="form-group "> -->
                            <button type="submit" name="buttonstatus" class="btn btn-primary " value="save"><i
                                    class="icofont-save"></i> Save
                            </button>
                            <button type="button" class="btn btn-danger text-white ml-2"><i class="icofont-ban"></i>
                                Cancel
                            </button>
                        <!-- </div> -->
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
                        <th>Users</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $key => $userpermission)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $userpermission->name }}</td>
                            <td>
                                @foreach ($userpermission->permissions as $permission)
                                    {{ $permission->name . ' ,' }}
                                @endforeach
                            </td>
                            <td class="text-center">
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit"
                                    href="{{ url('user-permission') . '/' . $userpermission->id }}">
                                    <i class="icofont-pencil text-primary"></i></a>
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2"
                                    onclick="deleteUserPermission('{{ $userpermission->id }}')">
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

        function deleteUserPermission(userId) {
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
                        url: "{{ route('delete.user.permission') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: userId,
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
