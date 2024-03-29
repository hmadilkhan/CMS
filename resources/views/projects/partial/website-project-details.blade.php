
    <div id="mytask-layout" class="theme-indigo">
        <div class="card card-info">
            <div class="card-body">
                <div class="row clearfix">
                <div class="col-md-12">
                    <div class="card border-0 mb-4 no-bg">
                        <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">{{$project->project_name}}</h3>
                            <a class="btn {{($task->status == 'Hold' ? 'btn-warning' : ($task->status == 'Cancelled' ? 'btn-danger' : 'btn-dark'))}} text-white me-1 mt-1 w-sm-100" id="openemployee">{{$task->status}}</a>
                        </div>
                    </div>
                </div>
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Details</h3>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input disabled value="{{$project->customer->first_name}}" type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input disabled value="{{$project->customer->last_name}}" type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="street" class="form-label">Street</label>
                        <input disabled value="{{$project->customer->street}}" type="text" class="form-control" id="street" name="street" placeholder="Street">
                    </div>
                    <div class="col-sm-3">
                        <label for="city" class="form-label">City</label>
                        <input disabled value="{{$project->customer->city}}" type="text" class="form-control" id="city" name="city" placeholder="City">
                    </div>
                    <div class="col-sm-3">
                        <label for="state" class="form-label">State</label>
                        <input disabled value="{{$project->customer->state}}" type="text" class="form-control" id="state" name="state" placeholder="State">
                    </div>
                    <div class="col-sm-3">
                        <label for="zipcode" class="form-label">Zip Code</label>
                        <input disabled value="{{$project->customer->zipcode}}" type="text" class="form-control" id="zipcode" name="zipcode" placeholder="Zip Code">
                    </div>
                    <div class="col-sm-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input disabled value="{{$project->customer->phone}}" type="text" class="form-control" id="phone" name="phone" placeholder="phone">
                    </div>
                    <div class="col-sm-3">
                        <label for="email" class="form-label">Email</label>
                        <input disabled value="{{$project->customer->email}}" type="text" class="form-control" id="email" name="email" placeholder="Email">
                    </div>

                    <div class="col-sm-3">
                        <label for="sold_date" class="form-label">Sold Date</label>
                        <input disabled value="{{$project->customer->sold_date}}" type="date" class="form-control" id="sold_date" name="sold_date" placeholder="Sold Date">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Sales Partner</label>
                        <input disabled value="{{$project->customer->salespartner->name}}" type="text" class="form-control" />
                    </div>
                    <div class="col-sm-3">
                        <label for="code" class="form-label">Panel Qty</label>
                        <input disabled value="{{$project->customer->panel_qty}}" type="text" class="form-control" id="panel_qty" name="panel_qty" placeholder="Panel Qty">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Module Type</label>
                        <input disabled value="{{$project->customer->module->name}}" type="text" class="form-control" id="module_type_id" name="module_type_id" placeholder="Module Type">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Inverter Type</label>
                        <input disabled value="{{$project->customer->inverter->name}}" type="text" class="form-control" id="inverter_type_id" name="inverter_type_id" placeholder="Inverter Type">
                    </div>
                    <div class="col-sm-3">
                        <label for="module_qty" class="form-label">System Size</label>
                        <input disabled value="{{$project->customer->module_value}}" type="text" class="form-control" id="module_qty" name="module_qty" placeholder="System Size">
                    </div>
                    <div class="col-sm-3">
                        <label for="inverter_qty" class="form-label">Inverter Qty</label>
                        <input disabled value="{{$project->customer->inverter_qty}}" type="text" class="form-control" id="inverter_qty" name="inverter_qty" placeholder="Inverter Qty">
                    </div>
                </div>
                </hr>
            </div>
        </div>
    </div>
