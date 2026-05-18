@extends('layouts.master')
@section('title', 'Permissions')
@section('content')
    @include('auth.partials.user-management-styles')
    <div class="user-management-page">
    <div class="user-management-heading">
        <h1><i class="icofont-lock me-2"></i>Permissions</h1>
    </div>
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title w-100">
                Permission Section
            </h4>
        </div>
        <div class="card-body">
            <form method="POST"
                action="{{ !empty($permission) ? route('update.permission') : route('permission.store') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($permission) ? $permission->id : '' }}" />
                <div class="row mt-2 ">
                    <div class="col-sm-6">
                        <!-- <div class="form-group"> -->
                            <label>Permission Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                placeholder="Enter Permission Name"
                                value="{{ !empty($permission) ? $permission->name : old('name') }}">
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        <!-- </div> -->
                    </div>
                    <div class="col-6 mt-4">
                        <!-- <label></label> -->
                        <!-- <div class="form-group "> -->
                            <button type="submit" class="btn btn-primary " value="save"><i class="icofont-save"></i>
                                Save
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
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title">Permissions List</h3>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table- datatable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Permission Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $key => $permission)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $permission->name }}</td>
                            <td class="text-center">
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit"
                                    href="{{ url('permission') . '/' . $permission->id }}">
                                    <i class="icofont-pencil text-warning"></i></a>
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2"
                                    onclick="deletePermission('{{ $permission->id }}')">
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
        function deletePermission(roleId) {
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
                        url: "{{ route('permission.delete') }}",
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
                                sleep(500).then(() => {
                                    // Do something after the sleep!
                                    location.reload();
                                });
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

        // sleep time expects milliseconds
        function sleep(time) {
            return new Promise((resolve) => setTimeout(resolve, time));
        }
    </script>
@endsection
