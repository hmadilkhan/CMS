@extends('layouts.master')
@section('title', 'Users')
@section('content')
@include('auth.partials.user-management-styles')
@php
    $selectedRoleIds = old('role', !empty($user) ? $user->roles->pluck('id')->toArray() : []);
    $selectedRoleNames = $roles->whereIn('id', $selectedRoleIds)->pluck('name')->toArray();
    $employeeRoleNames = ['Employee', 'Manager', 'Service Manager', 'Sub-Contractor Manager'];
    $selectedTypeId = old('user_type_id', !empty($user) ? $user->user_type_id : null);
    $selectedTypeName = optional($types->firstWhere('id', $selectedTypeId))->name;
    $showEmployeeFields = !empty($employee)
        || $selectedTypeName === 'Employee'
        || count(array_intersect($selectedRoleNames, $employeeRoleNames)) > 0;
@endphp

<div class="user-management-page">
    <div class="operation-page-header">
        <div>
            <h1 class="operation-page-title"><i class="icofont-users-alt-4 me-2"></i>Users</h1>
            <p class="operation-page-subtitle">Create and maintain CRM users, roles, and access details.</p>
        </div>
    </div>

    <div class="user-management-section">
        <div class="user-management-section-header">
            <h3 class="user-management-section-title"><i class="icofont-user-alt-4 me-2"></i>{{ !empty($user) ? 'Edit User' : 'Create New User' }}</h3>
        </div>
        <div class="user-management-section-body">
            <!-- ADD NEW PRODUCT PART START -->
            <form method="POST" action="{{ !empty($user) ? route('update.user') : route('store.register') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($user) ? $user->id : '' }}" />
                <input type="hidden" name="previous_logo" value="{{ !empty($user) ? $user->image : '' }}" />
                <input type="hidden" name="sync_employee" id="sync_employee" value="{{ $showEmployeeFields ? 1 : 0 }}" />
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label"><i class="icofont-user me-2"></i>Full Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" placeholder="Enter Complete Name"
                            value="{{ !empty($user) ? $user->name : old('name') }}">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label"><i class="icofont-email me-2"></i>Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" placeholder="Enter Email"
                            value="{{ !empty($user) ? $user->email : old('email') }}">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label"><i class="icofont-phone me-2"></i>Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone"
                                name="phone" placeholder="Enter Phone"
                                value="{{ !empty($user) ? $user->phone : old('phone') }}">
                            @error('phone')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label"><i class="icofont-ui-user me-2"></i>Username</label>
                        <input {{ !empty($user) ? 'disabled' : '' }} type="text"
                            class="form-control @error('username') is-invalid @enderror" id="username" name="username"
                            placeholder="Enter Username" value="{{ !empty($user) ? $user->username : old('username') }}">
                        @error('username')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <label class="form-label"><i class="icofont-lock me-2"></i>Password</label>
                            <input {{ !empty($user) ? 'disabled' : '' }} type="password"
                                class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                                placeholder="Enter Password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label"><i class="icofont-lock me-2"></i>Confirm Password</label>
                            <input {{ !empty($user) ? 'disabled' : '' }} type="password" class="form-control"
                                id="password_confirmation" name="password_confirmation"
                                placeholder="Enter Confirm Password">
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label"><i class="icofont-users-alt-5 me-2"></i>Type</label>
                            <select id="user_type_id" name="user_type_id"
                                class="form-control select2 @error('user_type_id') is-invalid @enderror" style="width: 100%;">
                                <option value="">Select User Type</option>
                                @foreach ($types as $typeValue)
                                    @if (!empty($user))
                                        <option value="{{ $typeValue->id }}"
                                            {{ $user->user_type_id == $typeValue->id ? 'selected' : '' }}>
                                            {{ $typeValue->name }}
                                        </option>
                                    @else
                                        <option value="{{ $typeValue->id }}"
                                            {{ old('user_type_id') == $typeValue->id ? 'selected' : '' }}>
                                            {{ $typeValue->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('user_type_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                    </div>
                    <div class="col-md-6 col-lg-3" id="salesPartnerDiv"
                        style="{{ !empty($user) && $user->user_type_id == 3 ? 'display:block' : 'display:none' }}">
                        <label class="form-label">Sales Partner</label>
                            <select id="sales_partner_id" name="sales_partner_id"
                                class="form-control select2 @error('sales_partner_id') is-invalid @enderror"
                                style="width: 100%;">
                                <option value="">Select Partner</option>
                                @foreach ($partners as $partner)
                                    <option
                                        {{ !empty($user) && $partner->id == $user->sales_partner_id ? 'selected' : '' }}
                                        value="{{ $partner->id }}">
                                        {{ $partner->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sales_partner_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                    </div>
                    <div class="col-md-6 col-lg-3" id="subContractorDiv"
                        style="{{ !empty($user) && $user->user_type_id == 4 ? 'display:block' : 'display:none' }}">
                        <label class="form-label">Sub-Contractors</label>
                            <select id="sub_contractor_id" name="sub_contractor_id"
                                class="form-control select2 @error('sub_contractor_id') is-invalid @enderror"
                                style="width: 100%;">
                                <option value="">Sub-Contractors</option>
                                @foreach ($contractors as $contractor)
                                    <option
                                        {{ !empty($user) && $contractor->id == $user->sales_partner_id ? 'selected' : '' }}
                                        value="{{ $contractor->id }}">
                                        {{ $contractor->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sub_contractor_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                    </div>
                    <div class="col-md-6 col-lg-3" id="addressDiv"
                        style="{{ !empty($user) && $user->user_type_id == 5 ? 'display:block' : 'display:none' }}">
                        <label class="form-label">Address</label>
                            <input  type="text" class="form-control"
                                id="address" name="address" placeholder="Enter Address" value="{{ !empty($user) ? $user->address : old('address') }}">
                            @error('address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label class="form-label"><i class="icofont-shield me-2"></i>Roles</label>
                            <select id="role" name="role[]" multiple
                                class="form-control select2 @error('role') is-invalid @enderror" style="width: 100%;">
                                <option value="">Select Roles</option>
                                    @foreach ($roles as $role)
                                    <option
                                        {{ in_array($role->id, $selectedRoleIds) || (!empty($user) && in_array($role->name, $rolenames->toArray())) ? 'selected' : '' }}
                                        value="{{ $role->id }}" data-role-name="{{ $role->name }}">
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label for="formFileMultipleoneone" class="form-label"><i class="icofont-image me-2"></i>Profile Image</label>
                            <input class="form-control" type="file" id="formFileMultipleoneone" name="file">
                    </div>

                    <div class="col-12" id="employeeFieldsDiv" style="{{ $showEmployeeFields ? 'display:block' : 'display:none' }}">
                        <div class="user-management-section mt-2 mb-0">
                            <div class="user-management-section-header">
                                <h3 class="user-management-section-title"><i class="icofont-id-card me-2"></i>Employee Details</h3>
                            </div>
                            <div class="user-management-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" class="form-control @error('employee_code') is-invalid @enderror"
                                            id="employee_code" name="employee_code" placeholder="Employee Code"
                                            value="{{ old('employee_code', !empty($employee) ? $employee->code : '') }}">
                                        @error('employee_code')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Joining Date</label>
                                        <input type="date" class="form-control @error('joined_date') is-invalid @enderror"
                                            id="joined_date" name="joined_date"
                                            value="{{ old('joined_date', !empty($employee) ? $employee->joined_date : '') }}">
                                        @error('joined_date')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Override Base Price</label>
                                        <input type="text" class="form-control @error('overwrite_base_price') is-invalid @enderror"
                                            id="overwrite_base_price" name="overwrite_base_price" placeholder="Override Base Cost"
                                            value="{{ old('overwrite_base_price', !empty($user) ? $user->overwrite_base_price : '') }}">
                                        @error('overwrite_base_price')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 col-lg-3">
                                        <label class="form-label">Override Panel Price</label>
                                        <input type="text" class="form-control @error('overwrite_panel_price') is-invalid @enderror"
                                            id="overwrite_panel_price" name="overwrite_panel_price" placeholder="Override Panel Price"
                                            value="{{ old('overwrite_panel_price', !empty($user) ? $user->overwrite_panel_price : '') }}">
                                        @error('overwrite_panel_price')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Departments</label>
                                        <select class="form-control select2 @error('departments') is-invalid @enderror"
                                            multiple id="departments" name="departments[]" style="width: 100%;">
                                            @php
                                                $selectedDepartmentIds = old('departments', !empty($employee) ? $employee->department->pluck('id')->toArray() : []);
                                            @endphp
                                            @foreach ($departments as $department)
                                                <option value="{{ $department->id }}" {{ in_array($department->id, $selectedDepartmentIds) ? 'selected' : '' }}>
                                                    {{ $department->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('departments')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 justify-content-end mt-3">
                            <a href="{{ route('get.register') }}" class="btn btn-cancel">
                                <i class="icofont-close-line me-2"></i>Cancel
                            </a>
                            <button type="submit" name="buttonstatus" class="btn btn-premium" value="save">
                                <i class="icofont-save me-2"></i>{{ !empty($user) ? 'Update User' : 'Create User' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <!-- ADD NEW PRODUCT PART END -->
        </div>
    </div>

    <div class="user-management-section mt-4">
        <div class="user-management-section-header">
            <h3 class="user-management-section-title"><i class="icofont-users-alt-4 me-2"></i>Users List</h3>
        </div>
        <div class="user-management-section-body">
            <div class="table-responsive">
            <table id="example1" class="table table-premium table-hover datatable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Roles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $key => $user)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->type->name }}</td>
                            <td>{{ implode(',', $user->getRoleNames()->toArray()) }}</td>
                            <td class="text-center">
                                <a href="{{ url('register') . '/' . $user->id }}" class="action-icon text-primary me-2" data-bs-toggle="tooltip" title="Edit">
                                    <i class="icofont-pencil"></i>
                                </a>
                                <a onclick="deleteUser('{{ $user->id }}')" class="action-icon text-danger me-2" style="cursor: pointer;" data-bs-toggle="tooltip" title="Delete">
                                    <i class="icofont-trash"></i>
                                </a>
                                @if (auth()->user()->canImpersonate() && $user->canBeImpersonated())
                                    <a href="{{ route('impersonate', $user->id) }}" class="action-icon text-primary" data-bs-toggle="tooltip" title="Impersonate {{ $user->name }}">
                                        <i class="icofont-user-suited"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
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
                    <button type="button" class="btn btn-danger color-fff" onclick="deleteModuleTpe()">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script type="text/javascript">
        $("#user_type_id").change(function() {
            if ($(this).val() == 3) {
                $("#salesPartnerDiv").css("display", "block")
            } else {
                $("#salesPartnerDiv").css("display", "none")
            }
            if ($(this).val() == 4) {
                $("#subContractorDiv").css("display", "block")
            } else {
                $("#subContractorDiv").css("display", "none")
            }
            if ($(this).val() == 5) {
                $("#addressDiv").css("display", "block")
            } else {
                $("#addressDiv").css("display", "none")
            }
            toggleEmployeeFields();
        })

        $("#role").change(function() {
            toggleEmployeeFields();
        })

        function toggleEmployeeFields() {
            let selectedTypeName = $("#user_type_id option:selected").text().trim();
            let employeeRoles = ["Employee", "Manager", "Service Manager", "Sub-Contractor Manager"];
            let hasEmployeeRole = $("#role option:selected").toArray().some(function(option) {
                return employeeRoles.includes($(option).data("role-name"));
            });

            if (selectedTypeName === "Employee" || hasEmployeeRole) {
                $("#employeeFieldsDiv").css("display", "block");
                $("#sync_employee").val(1);
            } else {
                $("#employeeFieldsDiv").css("display", "none");
                $("#sync_employee").val(0);
            }
        }

        function deleteUser(id) {
            $("#deleteId").val(id);
            $("#deleteproject").modal("show")
        }

        function deleteModuleTpe() {
            $.ajax({
                method: "POST",
                url: "{{ route('delete.user') }}",
                data: {
                    _token: "{{ csrf_token() }}",
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
