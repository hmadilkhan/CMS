<style>
    .custom-card-body {
        min-height: 300px;
        min-width: 100px;
        margin-right: 5px;
    }
</style>

@foreach($subdepartments as $subdepartment)
<div class="container-fluid py-2">
    <div class="card border-0 mb-4 no-bg">
        <div class="card-header py-3 px-0 d-sm-flex align-items-center  justify-content-between border-bottom">
            <h3 class=" fw-bold flex-fill mb-0 mt-sm-0">{{$subdepartment->name}}</h3>
        </div>
    </div>
    <div class="d-flex flex-row flex-nowrap">
        @php $collections = $projects->filter(function ($item) use ($subdepartment) {
        return $item->sub_department_id == $subdepartment->id;
        })->values(); @endphp
        @if(count($collections) > 0)
        @foreach($collections as $project)
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-4 col-sm-4 " style="margin-right: 5px;">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                        <img src="{{($project->customer->salespartner->image != '' ? (asset('storage/projects/'.$project->customer->salespartner->image)) : (asset('assets/images/profile_av.png')))}}" alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                    </div>
                    <h6 class="mb-0 fw-bold d-block fs-6 mt-2">{{$project->department->name}}</h6>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div class="lesson_name">
                        </div>
                    </div>

                    <div class="row g-2 pt-4">
                        <div class="col-12 d-flex align-items-center">
                            <div class="">
                                <h3 class="mb-0 fw-bold  fs-6  mb-2">{{$project->project_name}}</h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="icofont-ui-calendar"></i>
                                <span class="ms-2">Sales Partner</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <span class="ms-2 text-success">{{$project->customer->salespartner->name}}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="icofont-ui-calendar"></i>
                                <span class="ms-2">Status</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="icofont-sand-clock"></i>
                                <span class="ms-2 text-danger">{{$project->assignedPerson[0]->status}}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="icofont-group-students "></i>
                                <span class="ms-2">Assigned To</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="icofont-ui-text-chat"></i>
                                <span class="ms-2">{{$project->assignedPerson[0]->employee->name}}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <h4 class="small fw-bold mb-2 mt-2">Progress</h4>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: {{$project->department_id/8*100}}%;" aria-valuenow="{{$project->department_id/8*100}}" aria-valuemin="0" aria-valuemax="100">{{$project->department_id/8*100}}%</div>
                            </div>
                        </div>
                    </div>

                    <div class="dividers-block"></div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <a href="{{route('projects.show',$project->id)}}" class="btn btn-dark btn-set-task w-sm-100"><i class="icofont-eye me-2 fs-6"></i>Details</a>
                        @if(auth()->user()->getRoleNames()[0] == "Manager")
                            <button type="button" class="btn btn-dark btn-set-task w-sm-100" onclick="assignTask('{{$project->department_id}}','{{$project->id}}')"><i class="icofont-plus-circle me-2 fs-6"></i>Assign Task</button>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        @endforeach
        @else
        <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 ">
            <div class="card">
                <div class="card-body">
                    <h5>No Records found</h5>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endforeach

<!-- Create task-->
<div class="modal fade" id="createtask" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <div class="modal-content">
            <input type="hidden" id="department_id" />
            <input type="hidden" id="project_id" />
            <div class="modal-header">
                <h5 class="modal-title  fw-bold" id="createprojectlLabel"> Assign Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Project Name</label>
                    <select id="employee" class="form-select select2" aria-label="Default select Project Category">
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnAssignTask" disabled>Done</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
    function assignTask(departmentId, projectId) {
        $("#createtask").modal("show");
        $("#department_id").val(departmentId)
        $("#project_id").val(projectId)
        getDepartmentEmployees(departmentId)
    }

    function getDepartmentEmployees(departmentId) {
        $.ajax({
            url: "{{route('get.employee.department')}}",
            method: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                id: departmentId
            },
            success: function(response) {
                $('#employee').empty();
                $('#employee').append($('<option value="">Select Employee</soption>'));
                $.each(response.employees, function(i, employee) {
                    $('#employee').append($('<option  value="' + employee.id + '">' + employee.name + '</option>'));
                });
                $("#btnAssignTask").prop("disabled", false);
            }
        })
    }
    $("#btnAssignTask").click(function() {
        $.ajax({
            url: "{{route('projects.assign')}}",
            method: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                department_id: $("#department_id").val(),
                project_id: $("#project_id").val(),
                employee_id: $("#employee").val(),
            },
            success: function(response) {
                $("#createtask").modal("hide");
                location.reload();
            }
        })
    });
</script>
<!-- <div class="row g-3 gy-5 py-3 row-deck">
    @foreach($projects as $project)
    <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-6 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-2 pe-sm-4 pe-4 w220">
                    <img src="assets/images/lg/avatar3.jpg" alt="" class="avatar xl rounded-circle img-thumbnail shadow-sm">
                </div>
                <h6 class="mb-0 fw-bold d-block fs-6 mt-2">{{$project->department->name}}</h6>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <div class="lesson_name">
                    </div>
                </div>

                <div class="row g-2 pt-4">
                    <div class="col-12 d-flex align-items-center">
                        <div class="">
                            <h3 class="mb-0 fw-bold  fs-6  mb-2">{{$project->project_name}}</h3>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-ui-calendar"></i>
                            <span class="ms-2">Sales Partner</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <span class="ms-2 text-success">{{$project->customer->salespartner->name}}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-ui-calendar"></i>
                            <span class="ms-2">Status</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-sand-clock"></i>
                            <span class="ms-2 text-danger">{{$project->assignedPerson[0]->status}}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-group-students "></i>
                            <span class="ms-2">Assigned To</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="icofont-ui-text-chat"></i>
                            <span class="ms-2">{{$project->assignedPerson[0]->employee->name}}</span>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <h4 class="small fw-bold mb-2 mt-2">Progress</h4>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: {{$project->department_id/8*100}}%;" aria-valuenow="{{$project->department_id/8*100}}" aria-valuemin="0" aria-valuemax="100">{{$project->department_id/8*100}}%</div>
                        </div>
                    </div>
                </div>

                <div class="dividers-block"></div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <a href="{{route('projects.show',$project->id)}}" class="btn btn-dark btn-sm mt-1"><i class="icofont-eye me-2 fs-6"></i>Details</a>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div> -->