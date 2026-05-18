@extends("layouts.master")
@section('title', 'Employees')
@section('content')
@include('auth.partials.user-management-styles')
<div class="body d-flex py-lg-3 py-md-2">
    <div class="container-xxl user-management-page">
        <div class="user-management-heading">
            <h1><i class="icofont-users-alt-4 me-2"></i>Employees</h1>
        </div>
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="mb-4">
                    <div class="d-sm-flex align-items-center justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-dark me-1 mt-1 w-sm-100" id="openemployee"><i class="icofont-plus-circle me-2 fs-6"></i>Add Employee</button>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle mt-1  w-sm-100" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-expanded="false">
                                Status
                            </button>
                            <ul class="dropdown-menu  dropdown-menu-end" aria-labelledby="dropdownMenuButton2">
                                <li><a class="dropdown-item" href="#">All</a></li>
                                <li><a class="dropdown-item" href="#">Task Assign Members</a></li>
                                <li><a class="dropdown-item" href="#">Not Assign Members</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- Row End -->
        <div class="card mt-3">
            <div class="card-header">
                <h4 class="card-title">Employee List</h3>
            </div>
            <div class="card-body">
                <table id="example1" class="table table-bordered table-striped datatable">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Code</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $key => $employee)
                        @php $values = $employee->department->pluck('name'); @endphp
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $employee->code }}</td>
                            <td>{{ $employee->name }}</td>
                            <td>{{ $employee->email }}</td>
                            <td>{{implode(', ',$values->toArray() )}}</td>
                            <td>{{ $employee->user->username }}</td>
                            <td>{{ $employee->user->getRoleNames()[0] }}</td>
                            <td class="text-center">
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" onclick="edit('{{ $employee->id }}')">
                                    <i class="icofont-pencil text-primary fs-4"></i></a>
                                <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteEmployee('{{ $employee->id }}')">
                                    <i class="icofont-trash text-danger fs-4"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div> <!-- ROW END -->

        <!-- Create Employee-->
        @include("employees.create_model")
        @include("employees.delete_modal")
    </div>
</div>
@include("employees.script")
@endsection
