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
                        <a href="{{route('projects.index')}}" class="btn btn-dark me-1 mt-1 w-sm-100" id="openemployee"><i class="icofont-arrow-left me-2 fs-6"></i>Back to List</a>
                    </div>
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
                        <input disabled value="{{$project->customer->module->name}}" type="text" class="form-control" id="panel_qty" name="panel_qty" placeholder="System Size">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label">Inverter Type</label>
                        <input disabled value="{{$project->customer->inverter->name}}" type="text" class="form-control" id="panel_qty" name="panel_qty" placeholder="System Size">
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
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0" data-bs-toggle="collapse" data-bs-target="#adderTable" aria-expanded="false" aria-controls="adderTable">Adders Details</h3>
                </div>
                <table id="adderTable" class="table table-bordered table-striped collapse multi-collapse">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Adder</th>
                            <th>Sub Adders</th>
                            <th>Unit</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($project->customer->adders as $key => $adder)
                        <tr>
                            <td>{{++$key}}</td>
                            <td>{{$adder->type->name}}</td>
                            <td>{{$adder->subtype->name}}</td>
                            <td>{{$adder->unit->name}}</td>
                            <td>{{$adder->amount}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
                    <h3 class=" fw-bold flex-fill mb-0 mt-sm-0" data-bs-toggle="collapse" data-bs-target="#finance" aria-expanded="false" aria-controls="finance">Financial Details</h3>
                </div>
                <div class="row g-3 mb-3 collapse multi-collapse" id="finance">
                    <div class="col-sm-3 ">
                        <label for="finance_option_id" class="form-label">Finance Option</label>
                        <input disabled type="text" class="form-control" value="{{$project->customer->finances->finance->name}}">
                    </div>
                    <div class="col-sm-3  loandiv">
                        <label for="loan_term_id" class="form-label">Loan Term</label>
                        <input disabled type="text" class="form-control" value="{{(!empty($project->customer->finances->term) ? $project->customer->finances->term->year : '' )}}">
                    </div>
                    <div class="col-sm-3  loandiv">
                        <label for="loan_apr_id" class="form-label">Loan Apr</label>
                        <input disabled type="text" class="form-control" value="{{(!empty($project->customer->finances->apr) ? $project->customer->finances->apr->apr  :  '')}}">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="contract_amount" class="form-label">Contract Amount</label>
                        <input disabled type="text" class="form-control" value="{{$project->customer->finances->contract_amount}}">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="redline_costs" class="form-label">Redline Costs</label>
                        <input disabled type="text" class="form-control" value="{{$project->customer->finances->redline_costs}}">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="adders" class="form-label">Adders</label>
                        <input disabled type="text" class="form-control" value="{{$project->customer->finances->adders}}">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="commission" class="form-label">Commission</label>
                        <input disabled type="text" class="form-control" value="{{$project->customer->finances->commission}}">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="dealer_fee" class="form-label">Dealer Fee</label>
                        <input disabled disabled type="text" class="form-control" value="{{$project->customer->finances->dealer_fee}}">
                    </div>
                    <div class="col-sm-3 ">
                        <label for="dealer_fee_amount" class="form-label">Dealer Fee Amount</label>
                        <input disabled type="text" class="form-control" value="{{$project->customer->finances->dealer_fee_amount}}">
                    </div>
                </div>
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
                        <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">Project </h3>
                    </div>
                </div>
            </div>
            <form id="form" method="post" action="{{route('projects.move')}}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{$project->id}}">
                <input type="hidden" name="taskid" value="{{$task->id}}">
                <input type="hidden" name="length" value="{{$project->department->document_length}}">
                <input type="hidden" name="alreadyuploaded" value="{{count($filesCount)}}">
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
                                <input type="radio" id="stage" name="stage" value="forward">
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
                            <option value="{{$mdepartment->id}}">{{$mdepartment->name}}</option>
                            @endforeach
                            @endif
                        </select>
                        @error("back")
                        <div class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                    </div>
                    <div class="col-sm-3 ">
                        <label for="finance_option_id" class="form-label">Move Forward</label>
                        <select class="form-select select2" aria-label="Default select Move Forward" id="forward" name="forward">
                            <option value="">Select Move Forward</option>
                            @if(!empty($forwarddepartments))
                            @foreach($forwarddepartments as $bdepartment)
                            <option value="{{$bdepartment->id}}">{{$bdepartment->name}}</option>
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
                        <label for="formFileMultipleoneone" class="form-label">Required Files</label>
                        <input class="form-control" type="file" id="file" name="file[]" accept=".png,.jpg,.pdf" multiple>
                        @error("file")
                        <div id="file_message" class="text-danger message mt-2">{{$message}}</div>
                        @enderror
                        <div id="file_message" class="text-danger message mt-2"></div>
                    </div>
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
                    @foreach($filesCount as $file)
                    <div class="col-sm-12 mb-3">
                        <a target="_blank" href="{{asset('storage/projects/'.$file->filename)}}">{{$file->filename}}</a>
                    </div>
                    @endforeach
                </div>
            </form>
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



    $("#saveProject").click(function() {
        $("#file_message").html('')
        let fileCount = $("[name='file[]']").prop("files").length;
        let stage = $('input[name="stage"]:checked').val()
        let totalCount = "{{$project->department->document_length}}";
        let alreadyUploaded = "{{count($filesCount)}}";
        if (stage == "forward" && alreadyUploaded == 0) {
            if (fileCount == totalCount) {
                $("#file_message").html('')
                $("#form").submit();
            } else {
                $("#file_message").html("Please select total " + totalCount + " files");
            }
        } else {
            $("#form").submit();
        }

    });
</script>
@endsection