@extends('layouts.master')
@section('title', 'Users')
@section('content')
<style>
.premium-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}
.premium-header {
    background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    color: white;
    padding: 1.25rem 1.5rem;
    border: none;
}
.premium-header h3 {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
}
.premium-body {
    padding: 2rem;
    background: white;
}
.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.4rem;
    font-size: 0.875rem;
}
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    transition: all 0.3s;
    font-size: 0.9rem;
    height: auto;
}
.form-control:focus, .form-select:focus {
    border-color: #2c3e50;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.15);
}
.btn-premium {
    background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    color: white;
    transition: all 0.3s;
    font-size: 0.9rem;
}
.btn-premium:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    color: white;
}
.btn-cancel {
    background: #6c757d;
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    color: white;
    transition: all 0.3s;
    font-size: 0.9rem;
}
.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-1px);
    color: white;
}
.table-premium {
    margin-bottom: 0;
}
.table-premium thead {
    background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
}
.table-premium thead th {
    border: none;
    padding: 0.75rem;
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
}
.table-premium tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    font-size: 0.9rem;
}
.table-premium tbody tr {
    transition: background 0.2s;
}
.table-premium tbody tr:hover {
    background: #f8f9fa;
}
.action-icon {
    font-size: 1.1rem;
    transition: transform 0.2s;
    display: inline-block;
}
.action-icon:hover {
    transform: scale(1.2);
}
.btn-sm.btn-premium {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
}
</style>
@include('auth.partials.user-management-styles')

<div class="user-management-page">
    <div class="user-management-heading">
        <h1><i class="icofont-users-alt-4 me-2"></i>Users</h1>
    </div>

    <div class="card premium-card">
        <div class="card-header premium-header">
            <h3><i class="icofont-user-alt-4 me-2"></i>{{ !empty($user) ? 'Edit User' : 'Create New User' }}</h3>
        </div>
        <div class="card-body premium-body">
            <!-- ADD NEW PRODUCT PART START -->
            <form method="POST" action="{{ !empty($user) ? route('update.user') : route('store.register') }}"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ !empty($user) ? $user->id : '' }}" />
                <input type="hidden" name="previous_logo" value="{{ !empty($user) ? $user->image : '' }}" />
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
                                        {{ !empty($user) ? (in_array($role->name, $rolenames->toArray()) ? 'selected' : '') : '' }}
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
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <label for="formFileMultipleoneone" class="form-label"><i class="icofont-image me-2"></i>Profile Image</label>
                            <input class="form-control" type="file" id="formFileMultipleoneone" name="file">
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

    <div class="card premium-card mt-4">
        <div class="card-header premium-header">
            <h3><i class="icofont-users-alt-4 me-2"></i>Users List</h3>
        </div>
        <div class="card-body premium-body">
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
                                <a href="{{ url('register') . '/' . $user->id }}" class="action-icon text-warning me-2" data-bs-toggle="tooltip" title="Edit">
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
        })

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
