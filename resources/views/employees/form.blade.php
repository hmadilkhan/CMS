<form id="form" method="post" action="" enctype="multipart/form-data">
    @csrf
    @if(!empty($employee))
    @method('PUT')
    @endif
    <input type="hidden" name="id" id="id" value="{{ !empty($employee) ? $employee->id : '' }}" />
    <input type="hidden" id="route" value="{{!empty($employee) ? route('employees.update',$employee->id) : route('employees.store')}}" />
    <input type="hidden" name="previous_logo" id="previous_logo" value="{{!empty($employee) ? $employee->image : ''}}" />
    <input type="hidden" name="user_id" id="user_id" value="{{!empty($employee) ? $employee->user_id : ''}}" />
    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

    <div class="row g-3 mb-3">
        <div class="mb-3">
            <label for="exampleFormControlInput877" class="form-label">Employee Name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Employee Name" value="{{!empty($employee) ? $employee->name : ''}}">
            <div id="name_message" class="text-danger message mt-2"></div>
        </div>
        <div class="col-sm-6">
            <label for="code" class="form-label">Employee ID</label>
            <input type="text" class="form-control" id="code" name="code" placeholder="Employee Code" value="{{!empty($employee) ? $employee->code : ''}}">
            <div id="code_message" class="text-danger message mt-2"></div>
        </div>
        <div class="col-sm-6">
            <label for="joined_date" class="form-label">Joining Date</label>
            <input type="date" class="form-control" id="joined_date" name="joined_date" value="{{!empty($employee) ? $employee->joined_date : ''}}">
            <div id="joined_date_message" class="text-danger message mt-2"></div>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col">
            <label for="username" class="form-label">User Name</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="User Name" value="{{!empty($employee) ? $employee->user->username : ''}}">
            <div id="username_message" class="text-danger message mt-2"></div>
        </div>
        <div class="col">
            <label for="password" class="form-label">Password</label>
            <input type="Password" class="form-control" id="password" name="password" placeholder="Password" {{!empty($employee) ? 'readonly' : ''}} value="{{!empty($employee) ? $employee->user->password : ''}}">
            <div id="password_message" class="text-danger message mt-2"></div>
        </div>
        <div class="col">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input {{ !empty($user) ? 'disabled' : '' }} type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Enter Confirm Password" {{!empty($employee) ? 'readonly' : ''}} value="{{!empty($employee) ? $employee->user->password : ''}}">
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col">
            <label for="overwrite_base_price" class="form-label">Override Base Price</label>
            <input type="text" class="form-control" id="overwrite_base_price" name="overwrite_base_price" placeholder="Override Base Cost" value="{{!empty($employee) ? $employee->user->overwrite_base_price : ''}}">
            <div id="overwrite_base_price_message" class="text-danger message mt-2"></div>
        </div>
        <div class="col">
            <label for="overwrite_panel_price" class="form-label">Override Panel Price</label>
            <input type="text" class="form-control" id="overwrite_panel_price" name="overwrite_panel_price" placeholder="Override Panel Price" value="{{!empty($employee) ? $employee->user->overwrite_panel_price : ''}}">
            <div id="overwrite_panel_price_message" class="text-danger message mt-2"></div>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col">
            <label for="email" class="form-label">Email ID</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="{{!empty($employee) ? $employee->user->email : ''}}">
            <div id="email_message" class="text-danger message mt-2"></div>
        </div>
        <div class="col">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="Phone" value="{{!empty($employee) ? $employee->phone : ''}}">
            <div id="phone_message" class="text-danger message mt-2"></div>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col">
            <label class="form-label">Department</label>
            <select class="form-select select2" multiple aria-label="Default select Project Category" id="department_id" name="departments[]">
                @foreach ($departments as $department)
                <option value="{{ $department->id }}" {{ !empty($employee) ? (in_array($department->id, $employee->department->pluck('id')->toArray()) ? 'selected' : '') : '' }}>{{ $department->name }}</option>
                @endforeach
            </select>
            <div id="department_id_message" class="text-danger message mt-2"></div>
        </div>
        <div class="col">
            <label class="form-label">Roles</label>
            <select class="form-select select2" aria-label="Default select Project Category" id="role" name="roles">
                @foreach ($roles as $role)
                <option value="{{ $role->id }}" {{ !empty($employee) ? (in_array($role->id, $employee->user->roles->pluck('id')->toArray()) ? 'selected' : '') : '' }} >
                    {{ $role->name }}
                </option>
                @endforeach
            </select>
            <div id="role_message" class="text-danger message mt-2"></div>
        </div>
    </div>
    <div class="mb-3">
        <label for="formFileMultipleoneone" class="form-label">Employee Profile</label>
        <input class="form-control" type="file" id="formFileMultipleoneone" name="file">
    </div>
    <button type="submit" class="btn btn-primary" style="display:none" id="btnFormSubmit">Create</button>
</form>
@section("scrips")
<script>

</script>
@endsection