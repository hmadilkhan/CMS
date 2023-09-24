@extends("layouts.master")
@section('title', 'Projects')
@section('content')
<div class="card card-info">
    <div class="card-body">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">{{$project->project_name}}</h3>
                        <a class="btn {{($task->status == 'Hold' ? 'btn-warning' : ($task->status == 'Cancelled' ? 'btn-danger' : 'btn-dark'))}} text-white me-1 mt-1 w-sm-100" id="openemployee">{{$task->status}}</a>
                        <a href="{{route('projects.index')}}" class="btn btn-dark me-1 mt-1 w-sm-100" id="openemployee"><i class="icofont-arrow-left me-2 fs-6"></i>Back to List</a>
                    </div>
                </div>
                <div class="card border-0 mb-4 no-bg d-flex py-2 project-tab flex-wrap w-sm-100">
                    <ul class="nav nav-tabs tab-body-header rounded ms-3 prtab-set w-sm-100" role="tablist">
                        @foreach($departments as $department)
                        @if($department->id < $project->department_id)
                            <li class="nav-item "><a class="nav-link active bg-success" data-bs-toggle="tab" role="tab">{{$department->name}}</a></li>
                            @elseif($department->id == $project->department_id)
                            <li class="nav-item "><a class="nav-link active " data-bs-toggle="tab" role="tab">{{$department->name}}</a></li>
                            @else
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" role="tab">{{$department->name}}</a></li>
                            @endif
                            @endforeach
                    </ul>
                </div>
                @if(auth()->user()->getRoleNames()[0] == "Manager" or auth()->user()->getRoleNames()[0] == "Admin" or auth()->user()->getRoleNames()[0] == "Super Admin")
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Assign Task</h3>
                </div>
                <form method="post" action="{{route('projects.assign')}}">
                    <div class="row g-3 mb-3 border-bottom">
                        @csrf
                        <input type="hidden" name="project_id" value="{{$project->id}}">
                        <input type="hidden" name="task_id" value="{{$task->id}}">
                        <input type="hidden" name="sub_department_id" value="{{$task->sub_department_id}}">
                        <input type="hidden" name="department_id" value="{{$project->department_id}}">
                        <div class="col-sm-3 mb-3">
                            <label for="employee" class="form-label">Select Employee</label>
                            <select class="form-select select2" aria-label="Default Select Employee" id="employee" name="employee">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                <option value="{{$employee->id}}">{{$employee->name}}</option>
                                @endforeach
                            </select>
                            @error("loan_term_id")
                            <div class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="col-sm-8 mb-3">
                            <label for="formFileMultipleoneone" class="form-label">Notes</label>
                            <textarea class="form-control" rows="1" name="notes"></textarea>
                            @error("notes")
                            <div class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn btn-dark me-1 w-sm-100"><i class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                        </div>
                    </div>
                </form>

                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Update Project Status</h3>
                </div>
                <form method="post" action="{{route('projects.status')}}">
                    <div class="row g-3 mb-3 border-bottom">
                        @csrf
                        <input type="hidden" name="project_id" value="{{$project->id}}">
                        <input type="hidden" name="taskid" value="{{$task->id}}">

                        <div class="col-sm-3 mb-3">
                            <label for="employee" class="form-label">Select Status</label>
                            <select class="form-select select2" aria-label="Default Select Status" id="status" name="status">
                                <option value="">Select Status</option>
                                <option {{(old('status') == "In-Progress" ? 'selected' : '')}} value="In-Progress">In-Progress</option>
                                <option {{(old('status') == "Hold" ? 'selected' : '')}} value="Hold">Hold</option>
                                <option {{(old('status') == "Cancelled" ? 'selected' : '')}} value="Cancelled">Cancelled</option>
                            </select>
                            @error("status")
                            <div class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="col-sm-8 mb-3">
                            <label for="formFileMultipleoneone" class="form-label">Reason</label>
                            <textarea class="form-control" rows="1" name="reason"></textarea>
                            @error("reason")
                            <div class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="col-sm-12 mb-3">
                            <button type="submit" class="btn btn-dark me-1 w-sm-100"><i class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                        </div>
                    </div>
                </form>
                @endif
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Customer Details</h3>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">First Name</label>
                        <input disabled value="{{$project->customer->first_name}}" type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">Last Name</label>
                        <input disabled value="{{$project->customer->last_name}}" type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">Street</label>
                        <input disabled value="{{$project->customer->street}}" type="text" class="form-control" id="street" name="street" placeholder="Street">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">City</label>
                        <input disabled value="{{$project->customer->city}}" type="text" class="form-control" id="city" name="city" placeholder="City">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">State</label>
                        <input disabled value="{{$project->customer->state}}" type="text" class="form-control" id="state" name="state" placeholder="State">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">Zip Code</label>
                        <input disabled value="{{$project->customer->zipcode}}" type="text" class="form-control" id="zipcode" name="zipcode" placeholder="Zip Code">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input disabled value="{{$project->customer->phone}}" type="text" class="form-control" id="phone" name="phone" placeholder="phone">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
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
                        <input disabled value="{{$project->customer->panel_qty}}" type="text" class="form-control" id="panel_qty" name="panel_qty" placeholder="System Size">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Module Type</label>
                        <input disabled value="{{$project->customer->module->name}}" type="text" class="form-control" id="module_type_id" name="module_type_id" placeholder="System Size">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Inverter Type</label>
                        <input disabled value="{{$project->customer->inverter->name}}" type="text" class="form-control" id="inverter_type_id" name="inverter_type_id" placeholder="System Size">
                    </div>

                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">Module Qty</label>
                        <input disabled value="{{$project->customer->module_value}}" type="text" class="form-control" id="module_qty" name="module_qty" placeholder="Module Qty">
                    </div>
                    <div class="col-sm-3">
                        <label for="exampleFormControlInput877" class="form-label">Inverter Qty</label>
                        <input disabled value="{{$project->customer->inverter_qty}}" type="text" class="form-control" id="inverter_qty" name="inverter_qty" placeholder="Inverter Qty">
                    </div>
                </div>
                </hr>
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Sales Partner Details</h3>
                </div>
                <div class="row g-3 mb-3 mt-1">
                    <div class="col-sm-3d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                        <img src="{{($project->customer->salespartner->image != '' ? (asset('storage/users/'.$project->customer->salespartner->image)) : (asset('assets/images/profile_av.png')))}}" alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Sales Partner Name</label>
                        <input disabled value="{{$project->customer->salespartner->name}}" type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Email</label>
                        <input disabled value="{{$project->customer->salespartner->email}}" type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="exampleFormControlInput877" class="form-label">Phone</label>
                        <input disabled value="{{$project->customer->salespartner->phone}}" type="text" class="form-control" id="street" name="street" placeholder="Street">
                    </div>
                </div>
                </hr>
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0" data-bs-toggle="collapse" data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Adders Details</h3>
                </div>
                <form method="post" action="{{route('projects.adders')}}">
                    @csrf
                    <input type="hidden" name="project_id" value="{{$project->id}}">
                    <input type="hidden" name="customer_id" value="{{$project->customer->id}}">
                    <input type="hidden" name="finance_option_id" value="{{$project->customer->finances->finance->id}}">
                    @if(auth()->user()->getRoleNames()[0] == "Manager" or auth()->user()->getRoleNames()[0] == "Admin" or auth()->user()->getRoleNames()[0] == "Super Admin")
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3 mb-3">
                            <label for="adders" class="form-label">Adders</label>
                            <select class="form-select select2" aria-label="Default select Adders" id="adders" name="adders">
                                <option value="">Select Adders</option>
                                @foreach ($adders as $adder)
                                <option value="{{ $adder->id }}">
                                    {{ $adder->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3 mb-3">
                            <label for="sub_type" class="form-label">Sub Type</label>
                            <select class="form-select select2" aria-label="Default select Sub Type" id="sub_type" name="sub_type">
                                <option value="">Select Sub Type</option>
                            </select>
                        </div>
                        <div class="col-sm-2 mb-3">
                            <label for="uom" class="form-label">UOM</label>
                            <select class="form-select select2" aria-label="Default select UOM" id="uom">
                                <option value="">Select UOM</option>
                                @foreach ($uoms as $uom)
                                <option value="{{ $uom->id }}">
                                    {{ $uom->name }}
                                </option>
                                @endforeach
                            </select>
                            @error("uom")
                            <div class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2 mb-3">
                            <label for="amount" class="form-label">Amount</label>
                            <input type="text" class="form-control" id="amount" name="amount" placeholder="Adders Amount">
                            @error("amount")
                            <div class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="col-sm-2 mt-5">
                            <button type="button" id="btnAdder" class="btn btn-primary"><i class="icofont-save me-2 fs-6"></i>Add</button>
                        </div>
                    </div>
                    </hr>
                    @endif
                    <table id="adderTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Adder</th>
                                <th>Sub Adders</th>
                                <th>Unit</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->customer->adders as $key => $adder)
                            @php $index = ++$key; @endphp
                            <tr id="row{{$key}}">
                                <input type="hidden" value="{{$adder->adder_type_id}}" name="adders[]" />
                                <input type="hidden" value="{{$adder->adder_sub_type_id}}" name="subadders[]" />
                                <input type="hidden" value="{{$adder->adder_unit_id}}" name="uom[]" />
                                <input type="hidden" value="{{$adder->amount}}" name="amount[]" />
                                <td>{{$index}}</td>
                                <td>{{$adder->type->name}}</td>
                                <td>{{$adder->subtype->name}}</td>
                                <td>{{$adder->unit->name}}</td>
                                <td>{{$adder->amount}}</td>
                                <td>
                                    <i style='cursor: pointer;' class='icofont-trash text-danger' onClick="deleteItem('{{$index}}')"> Delete</i>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if(auth()->user()->getRoleNames()[0] == "Manager" or auth()->user()->getRoleNames()[0] == "Admin" or auth()->user()->getRoleNames()[0] == "Super Admin")
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0" data-bs-toggle="collapse" data-bs-target="#finance" aria-expanded="false" aria-controls="finance">Financial Details</h3>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-3 ">
                            <label for="finance_option_id" class="form-label">Finance Option</label>
                            <input type="text" class="form-control" value="{{$project->customer->finances->finance->name}}">
                        </div>
                        @if($project->customer->finances->finance->name != "Cash")
                        <div class="col-sm-3  loandiv">
                            <label for="loan_term_id" class="form-label">Loan Term</label>
                            <input type="text" class="form-control" value="{{(!empty($project->customer->finances->term) ? $project->customer->finances->term->year : '' )}}" id="loan_term_id" name="loan_term_id">
                        </div>
                        <div class="col-sm-3  loandiv">
                            <label for="loan_apr_id" class="form-label">Loan Apr</label>
                            <input type="text" class="form-control" value="{{(!empty($project->customer->finances->apr) ? $project->customer->finances->apr->apr  :  '')}}" id="loan_apr_id" name="loan_apr_id">
                        </div>
                        @endif
                        <div class="col-sm-3 ">
                            <label for="contract_amount" class="form-label">Contract Amount</label>
                            <input type="text" class="form-control" value="{{$project->customer->finances->contract_amount}}" id="contract_amount" name="contract_amount">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="redline_costs" class="form-label">Redline Costs</label>
                            <input type="text" class="form-control" value="{{$project->customer->finances->redline_costs}}" id="redline_costs" name="redline_costs">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="adders" class="form-label">Adders</label>
                            <input type="text" class="form-control" value="{{$project->customer->finances->adders}}" id="adders_amount" name="adders_amount">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="commission" class="form-label">Commission</label>
                            <input type="text" class="form-control" value="{{$project->customer->finances->commission}}" id="commission" name="commission">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="dealer_fee" class="form-label">Dealer Fee</label>
                            <input type="text" class="form-control" value="{{$project->customer->finances->dealer_fee}}" id="dealer_fee" name="dealer_fee">
                        </div>
                        <div class="col-sm-3 ">
                            <label for="dealer_fee_amount" class="form-label">Dealer Fee Amount</label>
                            <input type="text" class="form-control" value="{{$project->customer->finances->dealer_fee_amount}}" id="dealer_fee_amount" name="dealer_fee_amount">
                        </div>
                    </div>
                    <div class="col-sm-12 mb-3">
                        <button type="submit" class="btn btn-dark me-1 mt-1 w-sm-100"><i class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                    </div>
                    @endif
                </form>
            </div>
        </div><!-- Row End -->
    </div>
</div>
<div class="card card-info mt-2">
    <div class="card-body">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Call Logs </h3>
                    </div>
                </div>
            </div>
            <form id="form" method="post" action="{{route('projects.move')}}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{$project->id}}">
                <input type="hidden" name="taskid" value="{{$task->id}}">
                <input type="hidden" name="length" value="{{$project->department->document_length}}">
                <input type="hidden" name="alreadyuploaded" value="{{count($filesCount)}}">

                <div class="row g-3 mb-3">
                    <div class="col-sm-3 mb-3">
                        <label for="call_no_1" class="form-label">Call No 1</label>
                        <select class="form-select select2" aria-label="Default select Call No 1" id="call_no_1" name="call_no_1">
                            <option value="">Select Call</option>
                            <option {{old("call_no_1") != "" && old("call_no_1") == 'yes'  ? 'selected' : '' }} value="yes">Yes</option>
                            <option {{old("call_no_1") != "" && old("call_no_1") == 'no'  ? 'selected' : '' }} value="no">Customer Not Responding</option>
                        </select>
                        <div id="call_no_1_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-9 mb-3">
                        <label for="notes_1" class="form-label">Comments:</label>
                        <input type="text" class="form-control" id="notes_1" name="notes_1" value="{{old('notes_1')}}" />
                        <div id="notes_1_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="call_no_2" class="form-label">Call No 2</label>
                        <select class="form-select select2" aria-label="Default select Call No 1" id="call_no_2" name="call_no_2">
                            <option value="">Select Call</option>
                            <option {{old("call_no_2") != "" && old("call_no_2") == 'yes'  ? 'selected' : '' }} value="yes">Yes</option>
                            <option {{old("call_no_2") != "" && old("call_no_2") == 'no'  ? 'selected' : '' }} value="no">Customer Not Responding</option>
                        </select>
                        <div id="call_no_2_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-9 mb-3">
                        <label for="notes_2" class="form-label">Comments:</label>
                        <input type="text" class="form-control" id="notes_2" name="notes_2" value="{{old('notes_2')}}" />
                        <div id="notes_2_message" class="text-danger message mt-2"></div>
                    </div>
                </div>


                <div class="col-md-12">
                    <div class="card border-0 mb-4 no-bg">
                        <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project </h3>
                        </div>
                    </div>
                </div>


                <div class="row  mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">Select Where to sent this project</label>
                            <br />
                            <label class="fancy-radio">
                                <input type="radio" id="stage" name="stage" value="back">
                                <span><i></i>Back</span>
                            </label>
                            <label class="fancy-radio">
                                <input type="radio" id="stage" name="stage" value="forward" >
                                <span><i></i>Forward</span>
                            </label>
                            <p id="error-radio"></p>
                        </div>
                        @error("stage")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 ">
                        <label for="finance_option_id" class="form-label">Move Back {{count($filesCount)}}</label>
                        <select class="form-select select2" aria-label="Default select Move Back" id="back" name="back">
                            <option value="">Select Move Back</option>
                            @if(!empty($backdepartments))
                            @foreach($backdepartments as $mdepartment)
                            <option {{old("back") != "" && old("back") == $mdepartment->id  ? 'selected' : '' }} value="{{$mdepartment->id}}">{{$mdepartment->name}}</option>
                            @endforeach
                            @endif
                        </select>
                        @error("back")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    @if(!empty($forwarddepartments))
                    @foreach($forwarddepartments as $bdepartment)
                    <input type="hidden" id="{{$bdepartment['id']}}_length" value="{{$bdepartment['document_length']}}" />
                    @endforeach
                    @endif
                    <div class="col-sm-3 ">
                        <label for="finance_option_id" class="form-label">Move Forward</label>
                        <select class="form-select select2" aria-label="Default select Move Forward" id="forward" name="forward">
                            <option value="">Select Move Forward</option>
                            @if(!empty($forwarddepartments))
                            @foreach($forwarddepartments as $bdepartment)
                            <option {{old("forward") != "" && old("forward") == $bdepartment['id']  ? 'selected' : '' }} value="{{$bdepartment['id']}}">{{$bdepartment['name']}}</option>
                            @endforeach
                            @endif
                        </select>
                        @error("forward")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 ">
                        <label for="finance_option_id" class="form-label">Sub Department</label>
                        <select class="form-select select2" aria-label="Default select Sub Department" id="sub_department" name="sub_department">
                            <option value="">Select Sub Department</option>
                        </select>
                        @error("sub_department")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="formFileMultipleoneone" class="form-label" id="requiredfiles">Required Files</label>
                        <input class="form-control" type="file" id="file" name="file[]" accept=".png,.jpg,.pdf" multiple>
                        @error("file")
                        <div id="file_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="file_message" class="text-danger message mt-2"></div>
                    </div>
                    @if($project->department->id == 1)
                    <div class="col-sm-3 mb-3">
                        <label for="utility_company" class="form-label" id="requiredfiles">Utility Company</label>
                        <input class="form-control" type="text" id="utility_company" name="utility_company"  value="{{$project->utility_company}}">
                        @error("utility_company")
                        <div id="utility_company_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="utility_company_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-3 mb-3">
                        <label for="ntp_approval_date" class="form-label" id="requiredfiles">NTP Approval Date</label>
                        <input class="form-control" type="text" id="ntp_approval_date" name="ntp_approval_date"  value="{{$project->ntp_approval_date}}">
                        @error("ntp_approval_date")
                        <div id="ntp_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="ntp_approval_date_message" class="text-danger message mt-2"></div>
                    </div>
                    @endif
                    @if($project->department->id == 2)
                    <div class="col-sm-3 mb-3">
                        <label for="site_survey_link" class="form-label" id="requiredfiles">Site Survey Link</label>
                        <input class="form-control" type="text" id="site_survey_link" name="site_survey_link" value="{{$project->site_survey_link}}">
                        @error("site_survey_link")
                        <div id="site_survey_link_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="site_survey_link_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-3 ">
                        <label for="hoa" class="form-label">HOA</label>
                        <select class="form-select select2" aria-label="Default select HOA" id="hoa" name="hoa">
                            <option value="">Select HOA</option>
                            <option {{$project->hoa != "" && $project->hoa == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
                            <option {{$project->hoa != "" && $project->hoa == 'no' ? 'selected' : '' }} value="no">No</option>
                        </select>
                        @error("hoa")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3" id="hoa_select" style="display:none;">
                        <label for="hoa_phone_number" class="form-label" id="requiredfiles">Phone Number Field</label>
                        <input class="form-control" type="text" id="hoa_phone_number" name="hoa_phone_number" value="{{$project->hoa_phone_number}}">
                        @error("hoa_phone_number")
                        <div id="hoa_phone_number_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="hoa_phone_number_message" class="text-danger message mt-2"></div>
                    </div>
                    @endif
                    @if($project->department->id == 3)
                    <div class="col-sm-3 ">
                        <label for="hoa" class="form-label">Adders Approved</label>
                        <select class="form-select select2" aria-label="Default select Adders Approved" id="adders_approve_checkbox" name="adders_approve_checkbox">
                            <option value="">Select Adders Approved</option>
                            <option {{$project->adders_approve_checkbox != "" && $project->adders_approve_checkbox == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
                            <option {{$project->adders_approve_checkbox != "" && $project->adders_approve_checkbox == 'no' ? 'selected' : '' }} value="no">No</option>
                        </select>
                        @error("adders_approve_checkbox")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 ">
                        <label for="mpu_required" class="form-label">MPU Required</label>
                        <select class="form-select select2" aria-label="Default select MPU Required" id="mpu_required" name="mpu_required">
                            <option value="">Select MPU Required</option>
                            <option {{$project->mpu_required != "" && $project->mpu_required == 'yes' ? 'selected' : '' }} value="yes">Yes</option>
                            <option {{$project->mpu_required != "" && $project->mpu_required == 'no' ? 'selected' : '' }} value="no">No</option>
                        </select>
                        @error("mpu_required")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 mb-3 mpuselect" style="display:none;">
                        <label for="meter_spot_request_date" class="form-label" id="requiredfiles">Meter Spot Request Date</label>
                        <input class="form-control" type="text" id="meter_spot_request_date" name="meter_spot_request_date" value="{{$project->meter_spot_request_date}}">
                        @error("meter_spot_request_date")
                        <div id="meter_spot_request_date_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="meter_spot_request_date_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-3 mb-3 mpuselect" style="display:none;">
                        <label for="meter_spot_request_number" class="form-label" id="requiredfiles">Meter Spot Request Number</label>
                        <input class="form-control" type="text" id="meter_spot_request_number" name="meter_spot_request_number" value="{{$project->meter_spot_request_number}}">
                        @error("meter_spot_request_number")
                        <div id="meter_spot_request_number_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="meter_spot_request_number_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-3 " >
                        <label for="meter_spot_result" class="form-label">Meter Spot Result</label>
                        <select class="form-select select2" aria-label="Default select Meter Spot Result" id="meter_spot_result" name="meter_spot_result">
                            <option value="">Select Meter Spot Result</option>
                            <option {{$project->meter_spot_result != "" && $project->meter_spot_result == 'same' ? 'selected' : '' }} value="same">Same Location</option>
                            <option {{$project->meter_spot_result != "" && $project->meter_spot_result == 'relocation' ? 'selected' : '' }} value="relocation">Relocation</option>
                        </select>
                        @error("meter_spot_result")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    @endif
                    @if($project->department->id == 4)
                    <div class="col-sm-3 mb-3 ">
                        <label for="permitting_submittion_date" class="form-label">Permit Submission Date</label>
                        <input class="form-control" type="text" id="permitting_submittion_date" name="permitting_submittion_date" value="{{$project->permitting_submittion_date}}">
                        @error("permitting_submittion_date")
                        <div id="permitting_submittion_date_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="permitting_submittion_date_message" class="text-danger message mt-2"></div>
                    </div>
                    <div class="col-sm-3 mb-3 ">
                        <label for="permitting_approval_date" class="form-label" >Meter Approval Date</label>
                        <input class="form-control" type="text" id="permitting_approval_date" name="permitting_approval_date" value="{{$project->permitting_approval_date}}">
                        @error("permitting_approval_date")
                        <div id="permitting_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="permitting_approval_date_message" class="text-danger message mt-2"></div>
                    </div>
                    <input type="hidden" name="projecthoa" value="{{$project->hoa}}"/>
                    @if($project->hoa == "yes")
                        <div class="col-sm-3 mb-3 ">
                            <label for="hoa_approval_request_date" class="form-label" >HOA Approval Request Date</label>
                            <input class="form-control" type="text" id="hoa_approval_request_date" name="hoa_approval_request_date" value="{{$project->hoa_approval_request_date}}">
                            @error("hoa_approval_request_date")
                            <div id="hoa_approval_request_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="hoa_approval_request_date_message" class="text-danger message mt-2"></div>
                        </div>
                        <div class="col-sm-3 mb-3 ">
                            <label for="hoa_approval_date" class="form-label" >HOA Approval Date</label>
                            <input class="form-control" type="text" id="hoa_approval_date" name="hoa_approval_date" value="{{$project->hoa_approval_date}}">
                            @error("hoa_approval_date")
                            <div id="hoa_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="hoa_approval_date_message" class="text-danger message mt-2"></div>
                        </div>
                    @endif
                    @endif
                    @if($project->department->id == 5)
                        <div class="col-sm-3 mb-3 ">
                            <label for="solar_install_date" class="form-label" >Solar Install Date </label>
                            <input class="form-control" type="text" id="solar_install_date" name="solar_install_date" value="{{$project->solar_install_date}}">
                            @error("solar_install_date")
                            <div id="solar_install_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="solar_install_date_message" class="text-danger message mt-2"></div>
                        </div>
                        <div class="col-sm-3 mb-3 ">
                            <label for="battery_install_date" class="form-label" >Battery Install Date</label>
                            <input class="form-control" type="text" id="battery_install_date" name="battery_install_date" value="{{$project->battery_install_date}}">
                            @error("battery_install_date")
                            <div id="battery_install_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="battery_install_date_message" class="text-danger message mt-2"></div>
                        </div>
                        <input type="hidden" name="projectmpu" value="{{$project->mpu_required}}"/>
                        @if($project->mpu_required == "yes")
                        <div class="col-sm-3 mb-3 ">
                            <label for="mpu_install_date" class="form-label" >MPU Install Date</label>
                            <input class="form-control" type="text" id="mpu_install_date" name="mpu_install_date" value="{{$project->mpu_install_date}}">
                            @error("mpu_install_date")
                            <div id="mpu_install_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="mpu_install_date_message" class="text-danger message mt-2"></div>
                        </div>
                        @endif
                    @endif
                    @if($project->department->id == 6)
                        <div class="col-sm-3 mb-3 ">
                            <label for="rough_inspection_date" class="form-label" >Rough Inspection Date</label>
                            <input class="form-control" type="text" id="rough_inspection_date" name="rough_inspection_date" value="{{$project->rough_inspection_date}}">
                            @error("rough_inspection_date")
                            <div id="rough_inspection_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="rough_inspection_date_message" class="text-danger message mt-2"></div>
                        </div>
                        <div class="col-sm-3 mb-3 ">
                            <label for="final_inspection_date" class="form-label" >Final Inspection Date</label>
                            <input class="form-control" type="text" id="final_inspection_date" name="final_inspection_date" value="{{$project->final_inspection_date}}">
                            @error("final_inspection_date")
                            <div id="final_inspection_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="final_inspection_date_message" class="text-danger message mt-2"></div>
                        </div>
                    @endif
                    @if($project->department->id == 7)
                        <div class="col-sm-3 mb-3 ">
                            <label for="pto_submission_date" class="form-label" >PTO Submission Date</label>
                            <input class="form-control" type="text" id="pto_submission_date" name="pto_submission_date" value="{{$project->pto_submission_date}}">
                            @error("pto_submission_date")
                            <div id="pto_submission_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="pto_submission_date_message" class="text-danger message mt-2"></div>
                        </div>
                        <div class="col-sm-3 mb-3 ">
                            <label for="pto_approval_date" class="form-label" >PTO Approval Date</label>
                            <input class="form-control" type="text" id="pto_approval_date" name="pto_approval_date" value="{{$project->pto_approval_date}}">
                            @error("pto_approval_date")
                            <div id="pto_approval_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="pto_approval_date_message" class="text-danger message mt-2"></div>
                        </div>
                    @endif
                    @if($project->department->id == 8)
                        <div class="col-sm-3 mb-3 ">
                            <label for="coc_packet_mailed_out_date" class="form-label" >COC Packet</label>
                            <input class="form-control" type="text" id="coc_packet_mailed_out_date" name="coc_packet_mailed_out_date" value="{{$project->coc_packet_mailed_out_date}}">
                            @error("coc_packet_mailed_out_date")
                            <div id="coc_packet_mailed_out_date_message" class="text-danger message mt-2">{{$message}}</div>
                            @enderror
                            <div id="coc_packet_mailed_out_date_message" class="text-danger message mt-2"></div>
                        </div>
                    @endif
                    <div class="col-sm-12 mb-3">
                        <label for="formFileMultipleoneone" class="form-label">Notes</label>
                        <textarea class="form-control" rows="3" name="notes"></textarea>
                        @error("notes")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-12 mb-3">
                        <button type="button" class="btn btn-dark me-1 mt-1 w-sm-100" id="saveProject"><i class="icofont-arrow-left me-2 fs-6"></i>Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="card card-info mt-2">
    <div class="card-body">
        <div class="row clearfix">
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project Notes </h3>
                    </div>
                </div>
            </div>
            @foreach($departments as $department)
            <div class="col-md-12">
                <div class="card border-0 mb-4 no-bg">
                    <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom border-top">
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">{{$department->name}}</h3>
                    </div>
                </div>
            </div>
            @php
            $filtered_collection = $project->task->filter(function ($item) use ($department) {
            return $item->department_id == $department->id;
            })->values();

            $logs = $project->logs->filter(function ($item) use ($department) {
            return $item->department_id == $department->id;
            })->values();

            $files = $project->files->filter(function ($item) use ($department) {
            return $item->department_id == $department->id;
            })->values();

            @endphp

            <input type="hidden" id="{{$department->id}}_log_count" value="{{count($logs)}}" />
            <div class="col-sm-4 mb-3">
                <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Department Notes</label>
                @foreach($filtered_collection as $value)
                @if($value->notes != "")
                <textarea class="form-control" disabled rows="3">{{$value->notes}}</textarea>
                @endif
                @endforeach
            </div>
            <div class="col-sm-4 mb-3">
                @foreach($logs as $key => $log)
                <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Call No {{++$key}} : {{($log->call_no == "no" ? "Customer not responding" : $log->call_no)}} </label>
                <textarea class="form-control" disabled rows="3">{{$log->notes}}</textarea>
                @endforeach
            </div>
            <div class="col-sm-4 mb-3">
                <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Files</label>
                <ul class="list-group list-group-custom">
                    @foreach($files as $file)
                    <!-- <label class="badge bg-light"> <a target="_blank" href="{{asset('storage/projects/'.$file->filename)}}" class="ml-3">{{$file->filename}}</a></label> -->
                    <li class="list-group-item light-primary-bg"><a target="_blank" href="{{asset('storage/projects/'.$file->filename)}}" class="ml-3">{{$file->filename}}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
@section("scripts")
<script>
    $("#back").prop("disabled", true)
    $("#forward").prop("disabled", true)
    $('input[type=radio][name=stage]').change(function() {
        if (this.value == "back") {
            $("#back").prop("disabled", false)
            $("#forward").prop("disabled", true)
        }
        if (this.value == "forward") {
            $("#forward").prop("disabled", false)
            $("#back").prop("disabled", true)
        }

    });
    $("#back").change(function() {
        getSubDepartments($(this).val())
    });

    $("#forward").change(function() {
        let totalCount = $("#" + $("#forward").val() + "_length").val();
        $("#requiredfiles").html(totalCount + " File Required");
        getSubDepartments($(this).val())
    });

    function getSubDepartments(id) {
        if (id != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.sub.departments') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                },
                dataType: 'json',
                success: function(response) {
                    $('#sub_department').empty();
                    $('#sub_department').append($('<option value="">Select Sub Department</soption>'));
                    $.each(response.subdepartments, function(i, value) {
                        $('#sub_department').append($('<option  value="' + value.id + '">' + value.name + '</option>'));
                    });
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    }

    $("#saveProject").click(function(e) {
        $("#file_message").html('')
        let fileCount = $("[name='file[]']").prop("files").length;
        let stage = $('input[name="stage"]:checked').val()
        let totalCount = $("#" + $("#forward").val() + "_length").val(); //"{{$project->department->document_length}}";
        let alreadyUploaded = "{{count($filesCount)}}";
        let currentproject = "{{$project->department->id}}";
        let project = $("#forward").val();
        let logs = $("#" + $("#forward").val() + "_log_count").val() //"{{count($project->department->logs)}}"
        $("#call_no_1_message").html("");
        $("#call_no_2_message").html("");
        $("#notes_1_message").html("");
        $("#notes_2_message").html("");
        // alert(($("#notes_1").val() == ""))
        // alert((stage == "forward" && alreadyUploaded == 0 && (project != $("#forward").val())))

        if (project != 1 && project != 8 && logs == 0) {
            if ($("#call_no_1").val() == "") {
                $("#call_no_1").focus();
                $("#call_no_1_message").html("Please select the desired option");
            } else if ($("#notes_1").val() == "") {
                $("#notes_1").focus();
                $("#notes_1_message").html("Please enter notes");
            } else if ($("#call_no_2").val() == "") {
                $("#call_no_2").focus();
                $("#call_no_2_message").html("Please select the desired option");
            } else if ($("#notes_2").val() == "") {
                $("#notes_2").focus();
                $("#notes_2_message").html("Please enter notes");
            } else {
                if (stage == "forward" && alreadyUploaded == 0 && (currentproject != $("#forward").val())) {
                    if (fileCount == totalCount) {
                        $("#file_message").html('')
                        $("#form").submit();
                    } else {
                        $("#file_message").html("Please select total " + totalCount + " files");
                    }
                } else {
                    $("#form").submit();
                }
            }

        } else {
            if (stage == "forward" && alreadyUploaded == 0 && (currentproject != $("#forward").val())) {
                if (fileCount == totalCount) {
                    $("#file_message").html('')
                    $("#form").submit();
                } else {
                    $("#file_message").html("Please select total " + totalCount + " files");
                }
            } else {
                $("#form").submit();
            }
        }

    });

    $("#adders").change(function() {
        if ($(this).val() != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.sub.adders') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: $(this).val(),
                },
                dataType: 'json',
                success: function(response) {
                    $('#sub_type').empty();
                    $('#sub_type').append($('<option value="">Select Sub Type</soption>'));
                    $.each(response.subadders, function(i, subtype) {
                        $('#sub_type').append($('<option  value="' + subtype.id + '">' + subtype.name + '</option>'));
                    });
                    calculateCommission()
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    })

    $("#sub_type").change(function() {
        if ($(this).val() != "") {
            $.ajax({
                method: "POST",
                url: "{{ route('get.adders') }}",
                data: {
                    _token: "{{ csrf_token() }}",
                    subadder: $(this).val(),
                    adder: $("#adders").val(),
                },
                dataType: 'json',
                success: function(response) {
                    $("#uom").val(response.adders.adder_unit_id).change();
                    $("#amount").val(response.adders.price);
                },
                error: function(error) {
                    console.log(error.responseJSON.message);
                }
            })
        }
    })
    $("#btnAdder").click(function() {
        let rowLength = $('#adderTable tbody').find('tr').length;
        let adders_id = $("#adders").val();
        let subadder_id = $("#sub_type").val();
        let unit_id = $("#uom").val();
        let adders_name = $.trim($("#adders option:selected").text());
        let subadder_name = $.trim($("#sub_type option:selected").text());
        let unit_name = $.trim($("#uom option:selected").text());
        let amount = $("#amount").val();
        if (unit_id == 3) {
            let moduleQty = $('#module_qty').val();
            let panelQty = $('#panel_qty').val();
            amount = amount * moduleQty; //* panelQty;
        }
        let result = checkExistence(adders_id, subadder_id, unit_id);
        if (result == false) {
            let newRow = "<tr id='row" + (rowLength + 1) + "'>" +
                '<input type="hidden" value="' + adders_id + '" name="adders[]" />' +
                '<input type="hidden" value="' + subadder_id + '" name="subadders[]" />' +
                '<input type="hidden" value="' + unit_id + '" name="uom[]" />' +
                '<input type="hidden" value="' + amount + '" name="amount[]" />' +


                "<td>" + (rowLength + 1) + "</td>" +
                "<td>" + adders_name + "</td>" +
                "<td>" + subadder_name + "</td>" +
                "<td>" + unit_name + "</td>" +
                "<td>" + amount + "</td>" +

                "<td colspan='4'>&nbsp;&nbsp;<i style='cursor: pointer;' class='icofont-trash text-danger' onClick=deleteItem(" +
                (rowLength + 1) + ")>Delete</i></td>" +
                "</tr>";

            $("#adderTable > tbody").append(newRow);
            calculateAddersAmount();
            emptyControls();
        } else {
            alert("already added")
        }
    });

    function addToTable() {

    }

    function deleteItem(id) {
        $("#row" + id).remove();
        calculateAddersAmount();
    }

    function editItem(id, addersId, subAdderId, uomId, amount) {
        $("#adders").val(addersId).change();
        $("#sub_type").val(subAdderId).change()
        $("#uom").val(uomId).change();
        $("#amount").val(amount).change();

    }

    function checkExistence(firstval, secondval, thirdval) {
        let result = false;
        $("#adderTable tbody tr").each(function(index) {
            let first = $(this).children().eq(0).val();
            let second = $(this).children().eq(1).val();
            let third = $(this).children().eq(2).val();
            if (firstval == first && secondval == second && thirdval == third) {
                result = true;
            } else {
                result = false;
            }
        });
        return result;
    }

    function calculateAddersAmount() {
        let adders_amount = 0;
        $("#adderTable tbody tr").each(function(index) {
            console.log($(this).children().eq(8).text() * 1);
            adders_amount += $(this).children().eq(8).text() * 1;
        });
        $("#adders_amount").val(adders_amount);
        calculateCommission();
    }

    function emptyControls() {
        $("#adders").val('').change();
        $("#sub_type").val('').change();
        $("#uom").val('').change();
        $("#amount").val('');
    }

    function calculateCommission() {
        let contractAmount = parseFloat($("#contract_amount").val());
        let dealerFeeAmount = parseFloat($("#dealer_fee_amount").val());
        let redlineFee = parseFloat($("#redline_costs").val());
        let adders = parseFloat($("#adders_amount").val());
        console.log(contractAmount, dealerFeeAmount);
        console.log(redlineFee, adders);
        let commission = contractAmount - dealerFeeAmount - redlineFee - adders;
        $("#commission").val(commission.toFixed(2));
    }

    $("#hoa").change(function(){
        if ($(this).val() == "yes") {
            $("#hoa_select").css("display","block")
        }else{
            $("#hoa_select").css("display","none")
        }
    })
    $("#mpu_required").change(function(){
        if ($(this).val() == "yes") {
            $(".mpuselect").css("display","block")
        }else{
            $(".mpuselect").css("display","none")
        }
    })
</script>
@endsection