@extends("layouts.master")
@section('title', 'Users')
@section('content')
<div class="card card-info">
    <div class="card-header">
        <h5 class="card-title">Create New User</h5>
    </div>
    <div class="card-body">
        <!-- ADD NEW PRODUCT PART START -->
        <form method="POST" action="{{ !empty($user) ? route('update.user') :  route('store.register') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="{{ !empty($user) ? $user->id : '' }}" />
            <input type="hidden" name="previous_logo" value="{{ !empty($user) ? $user->image : '' }}" />
            <div class="row g-3  mb-3 align-items-center">
                <div class="col-sm-3 ">
                    <!-- <div class="form-group"> -->
                    <label>Full Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter Complete Name" value="{{ !empty($user) ? $user->name : old('name') }}">
                    @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>
                <div class="col-sm-3">
                    <!-- <div class="form-group"> -->
                    <label>Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Enter Email" value="{{ !empty($user) ? $user->email : old('email') }}">
                    @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Phone</label>
                        <input  type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" placeholder="Enter Phone">
                        @error('phone')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <div class="col-sm-3">
                    <!-- <div class="form-group "> -->
                    <label>Username</label>
                    <input {{ !empty($user) ? 'disabled' : '' }} type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" placeholder="Enter Username" value="{{ !empty($user) ? $user->username : old('username') }}">
                    @error('username')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                    <!-- </div> -->
                </div>

                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Password</label>
                        <input {{ !empty($user) ? 'disabled' : '' }} type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter Password">
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input {{ !empty($user) ? 'disabled' : '' }} type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Enter Confirm Password">
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        <label>Type</label>
                        <select id="user_type_id" name="user_type_id" class="form-control select2 @error('type') is-invalid @enderror" style="width: 100%;">
                            <option value="">Select User Type</option>
                            @foreach ($types as $typeValue)
                            @if (!empty($user))
                            <option value="{{ $typeValue->id }}" {{ $user->user_type_id == $typeValue->id ? 'selected' : '' }}>
                                {{ $typeValue->name }}
                            </option>
                            @else
                            <option value="{{ $typeValue->id }}" {{ old('type') == $typeValue->id ? 'selected' : '' }}>
                                {{ $typeValue->name }}
                            </option>
                            @endif
                            @endforeach
                        </select>
                        @error('type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="form-group">
                        <label>Roles</label>
                        <select id="role" name="role[]" multiple class="form-control select2 @error('role') is-invalid @enderror" style="width: 100%;">
                            <option value="">Select Roles</option>
                            @foreach ($roles as $role)
                            <option {{!empty($user) ? (in_array($role->name,$rolenames->toArray()) ? "selected" : "") : ""}} value="{{ $role->id }}">
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
                </div>
                <div class="col-sm-4 mb-2">
                    <div class="form-group">
                        <label for="formFileMultipleoneone" class="form-label">Image</label>
                        <input class="form-control" type="file" id="formFileMultipleoneone" name="file">
                    </div>
                </div>
                <div class="col-sm-4 mb-3">
                    <label></label>
                    <div class="form-group float-left mt-3">
                        <button type="button" class="btn btn-danger float-right ml-2 text-white"><i class="icofont-ban"></i>
                            Cancel
                        </button>
                        <button type="submit" name="buttonstatus" class="btn btn-primary float-right " value="save"><i class="icofont-save"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <!-- ADD NEW PRODUCT PART END -->
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h4 class="card-title">Users List</h3>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped datatable">
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
                    <td>{{ implode(",",$user->getRoleNames()->toArray()) }}</td>
                    <td class="text-center">
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{ url('register') . '/' . $user->id }}">
                            <i class="icofont-pencil text-warning"></i></a>
                        <a style="cursor: pointer;" data-toggle="tooltip" title="Delete" class="ml-2" onclick="deleteUser('{{ $user->id }}')">
                            <i class="icofont-trash text-danger"></i></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection