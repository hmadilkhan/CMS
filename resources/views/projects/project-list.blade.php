<style>
    .blink {
        animation: blinker 1s linear infinite;
    }

    @keyframes blinker {
        50% {
            opacity: 0;
        }
    }

    .project-card {
        transition: all 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin: 0 0.75rem;
    }

    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .project-header {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        padding: 1rem;
        color: white;
    }

    .days-badge {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-weight: 600;
    }

    .info-row {
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        color: #6c757d;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .info-value {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .progress-modern {
        height: 8px;
        border-radius: 10px;
        background: #e9ecef;
    }

    .progress-modern .progress-bar {
        border-radius: 10px;
        background: linear-gradient(90deg, #2c3e50 0%, #000000 100%);
    }

    .notes-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.75rem;
        margin-top: 1rem;
        font-size: 0.85rem;
        color: #495057;
    }

    .department-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-left: 4px solid #2c3e50;
        border-radius: 8px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
    }

    .department-header h3 {
        color: #2c3e50;
        font-size: 1.5rem;
        margin: 0;
        letter-spacing: 0.5px;
    }
</style>
@if ($value == 'all')
    @foreach ($departments as $department)
        <div class="container-fluid py-2">
            <div class="department-header">
                <h3 class="fw-bold"><i class="icofont-tasks me-2"></i>{{ $department->name }}</h3>
            </div>
            <div class="d-flex flex-row flex-nowrap overflow-auto" style="padding: 0.5rem 0; margin-top: -0.5rem;">
                @php
                    $collections = $projects
                        ->filter(function ($item) use ($department) {
                            return $item->department_id == $department->id;
                        })
                        ->values();
                    
                @endphp
                @if (count($collections) > 0)
                    @foreach ($collections as $project)
                    @php
                    $acceptanceStatus = "Not Initiated";
                    $acceptanceClass = "";
                    if (!empty($project->projectAcceptance)) {
                        if ($project->projectAcceptance->status == 0) {
                            $acceptanceStatus = "Pending";
                            $acceptanceClass = "text-warning";
                        }else if ($project->projectAcceptance->status == 1) {
                            $acceptanceStatus = "Approved";
                            $acceptanceClass = "text-success";
                        }else if ($project->projectAcceptance->status == 2) {
                            $acceptanceStatus = "Rejected";
                            $acceptanceClass = "text-danger";
                        } 
                    }    
                    @endphp
                        <div class="col-xxxl-3 col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-12"
                            style="cursor:pointer; min-width: 320px; padding: 0.5rem;" onclick="showProject('{{ $project->id }}')">
                            <div class="card project-card border-0">
                                <div class="project-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center flex-grow-1 min-w-0">
                                            <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/salespartners/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                                                alt="" class="rounded-circle flex-shrink-0" style="width: 45px; height: 45px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                                            <h5 class="mb-0 fw-bold ms-3 text-white text-truncate" style="max-width: 150px;">{{ $project->project_name }}</h5>
                                        </div>
                                        <div class="d-flex align-items-center ms-2 flex-shrink-0">
                                            <span class="days-badge">
                                                @if (empty($project->pto_approval_date))
                                                    {{ now()->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                                @else
                                                    {{ Carbon\Carbon::parse($project->pto_approval_date)->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                                @endif
                                                <small>d</small>
                                            </span>
                                            @if($project->viewed_emails_count)
                                                <i class="icofont-email text-white blink fs-5 ms-2"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body" style="overflow-wrap: break-word; word-wrap: break-word;">
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-code-alt me-2"></i>Project Code</span>
                                        <span class="info-value text-success text-truncate ms-2">{{ $project->code }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-ui-user me-2"></i>Sales Partner</span>
                                        <span class="info-value text-success text-truncate ms-2">{{ $project->customer->salespartner->name }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-sand-clock me-2"></i>Status</span>
                                        <span class="badge bg-danger text-truncate ms-2">{{ $project->assignedPerson[0]->status }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-group-students me-2"></i>Assigned To</span>
                                        <span class="info-value text-truncate ms-2">{{ $project->assignedPerson[0]->employee->name }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-check-circled me-2"></i>Acceptance</span>
                                        <span class="badge bg-{{ $acceptanceClass == 'text-success' ? 'success' : ($acceptanceClass == 'text-warning' ? 'warning' : 'secondary') }} text-truncate ms-2">{{ $acceptanceStatus }}</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="info-label">Progress</span>
                                            <span class="fw-bold" style="color: #2c3e50;">{{ ($project->department_id / 8) * 100 }}%</span>
                                        </div>
                                        <div class="progress progress-modern">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: {{ ($project->department_id / 8) * 100 }}%;"
                                                aria-valuenow="{{ ($project->department_id / 8) * 100 }}"
                                                aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    @if (!empty($project->notes) && $project->notes->assign_to_notes != '')
                                        <div class="notes-section">
                                            <i class="icofont-ui-note me-2"></i>{{ $project->notes->assign_to_notes }}
                                        </div>
                                    @endif

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
@else
    @foreach ($subdepartments as $subdepartment)
        <div class="container-fluid py-2">
            <div class="department-header">
                <h3 class="fw-bold"><i class="icofont-tasks me-2"></i>{{ $subdepartment->name }}</h3>
            </div>
            <div class="d-flex flex-row flex-nowrap overflow-auto" style="padding: 0.5rem 0; margin-top: -0.5rem;">
                @if ($subdepartment->id != 21)
                    @php
                        $collections = $projects
                            ->filter(function ($item) use ($subdepartment) {
                                return $item->sub_department_id == $subdepartment->id;
                            })
                            ->values();
                    @endphp
                    @if (count($collections) > 0)
                        @foreach ($collections as $project)
                        @php
                        $acceptanceStatus = "Not Initiated";
                        $acceptanceClass = "";
                        if (!empty($project->projectAcceptance)) {
                            if ($project->projectAcceptance->status == 0) {
                                $acceptanceStatus = "Pending";
                                $acceptanceClass = "text-warning";
                            }else if ($project->projectAcceptance->status == 1) {
                                $acceptanceStatus = "Approved";
                                $acceptanceClass = "text-success";
                            }else if ($project->projectAcceptance->status == 2) {
                                $acceptanceStatus = "Rejected";
                                $acceptanceClass = "text-danger";
                            } 
                        }    
                        @endphp
                            <div class="col-xxxl-3 col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-12"
                                style="cursor:pointer; min-width: 320px; padding: 0.5rem;" onclick="showProject('{{ $project->id }}')">
                                <div class="card project-card border-0">
                                    <div class="project-header">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center flex-grow-1 min-w-0">
                                                <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/salespartners/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                                                    alt="" class="rounded-circle flex-shrink-0" style="width: 45px; height: 45px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                                                <h5 class="mb-0 fw-bold ms-3 text-white text-truncate" style="max-width: 150px;">{{ $project->project_name }}</h5>
                                            </div>
                                            <div class="d-flex align-items-center ms-2 flex-shrink-0">
                                                <span class="days-badge">
                                                    @if (empty($project->pto_approval_date))
                                                        {{ now()->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                                    @else
                                                        {{ Carbon\Carbon::parse($project->pto_approval_date)->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                                    @endif
                                                    <small>d</small>
                                                </span>
                                                @if($project->viewed_emails_count)
                                                    <i class="icofont-email text-white blink fs-5 ms-2"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body" style="overflow-wrap: break-word; word-wrap: break-word;">
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-code-alt me-2"></i>Project Code</span>
                                            <span class="info-value text-success text-truncate ms-2">{{ $project->code }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-ui-user me-2"></i>Sales Partner</span>
                                            <span class="info-value text-success text-truncate ms-2">{{ $project->customer->salespartner->name }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-sand-clock me-2"></i>Status</span>
                                            <span class="badge bg-danger text-truncate ms-2">{{ $project->assignedPerson[0]->status }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-group-students me-2"></i>Assigned To</span>
                                            <span class="info-value text-truncate ms-2">{{ $project->assignedPerson[0]->employee->name }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-check-circled me-2"></i>Acceptance</span>
                                            <span class="badge bg-{{ $acceptanceClass == 'text-success' ? 'success' : ($acceptanceClass == 'text-warning' ? 'warning' : 'secondary') }} text-truncate ms-2">{{ $acceptanceStatus }}</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="info-label">Progress</span>
                                                <span class="fw-bold" style="color: #2c3e50;">{{ ($project->department_id / 8) * 100 }}%</span>
                                            </div>
                                            <div class="progress progress-modern">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: {{ ($project->department_id / 8) * 100 }}%;"
                                                    aria-valuenow="{{ ($project->department_id / 8) * 100 }}"
                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        @if (!empty($project->notes) && $project->notes->assign_to_notes != '')
                                            <div class="notes-section">
                                                <i class="icofont-ui-note me-2"></i>{{ $project->notes->assign_to_notes }}
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 " >
                            <div class="card">
                                <div class="card-body">
                                    <h5>No Records found</h5>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    {{-- GHOST PROJECTS START --}}

                    @if (count($ghostProjects) > 0)
                        @foreach ($ghostProjects as $project)
                            <div class="col-xxl-2 col-xl-3 col-lg-3 col-md-3 col-sm-3 border border-dark border-rounded border-2 "
                                style="margin-right: 5px;cursor:pointer;" onclick="showGhostProject('{{ $project->id }}','ghost')">
                                <div class="card">
                                    <div class="card-body">
                                        <div
                                            class="d-flex align-items-center justify-content-between profile-av pe-xl-4 pe-md-4 pe-sm-4 pe-4">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/salespartners/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                                                    alt=""
                                                    class="avatar lg rounded-circle img-thumbnail shadow-sm">
                                                <h3 class="mb-0 fw-bold fs-6 mx-3 text-start">
                                                    {{ $project->project_name }}</h3>
                                            </div>
                                            <div class="text-end ms-auto">
                                                <h6 class="mb-0 fs-6 font-monospace fw-bold">
                                                    @if (empty($project->pto_approval_date))
                                                        {{ now()->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                                    @else
                                                        {{ Carbon\Carbon::parse($project->pto_approval_date)->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}
                                                    @endif
                                                </h6>
                                            </div>
                                        </div>
                                        <div class="row g-2 pt-4">
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <i class="icofont-code-alt"></i>
                                                    <span class="ms-2">Project Code</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <span class="ms-2 text-success">{{ $project->code }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <i class="icofont-ui-user"></i>
                                                    <span class="ms-2">Sales Partner</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <span
                                                        class="ms-2 text-success">{{ $project->customer->salespartner->name }}</span>
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
                                                    <span
                                                        class="ms-2 text-danger">{{ $project->assignedPerson[0]->status }}</span>
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
                                                    <span
                                                        class="ms-2">{{ $project->assignedPerson[0]->employee->name }}</span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <h4 class="small fw-bold mb-2 mt-2">Progress</h4>
                                                <div class="progress">
                                                    <div class="progress-bar p-2" role="progressbar"
                                                        style="width: {{ ($project->department_id / 8) * 100 }}%;"
                                                        aria-valuenow="{{ ($project->department_id / 8) * 100 }}"
                                                        aria-valuemin="0" aria-valuemax="100">
                                                        {{ ($project->department_id / 8) * 100 }}%</div>
                                                </div>
                                            </div>
                                        </div>

                                        @if (!empty($project->notes))
                                            <ul class="list-group list-group-flush fs-6">
                                                @if ($project->notes->assign_to_notes != '')
                                                    <li class="list-group-item">{{ $project->notes->assign_to_notes }}
                                                    </li>
                                                @endif
                                            </ul>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 ">
                            <div class="card">
                                <div class="card-body">
                                    <h5>No Records found {{ count($ghostCollections) }}</h5>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
@endif
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
            url: "{{ route('get.employee.department') }}",
            method: "POST",
            data: {
                "_token": "{{ csrf_token() }}",
                id: departmentId
            },
            success: function(response) {
                $('#employee').empty();
                $('#employee').append($('<option value="">Select Employee</soption>'));
                $.each(response.employees, function(i, employee) {
                    $('#employee').append($('<option  value="' + employee.id + '">' + employee
                        .name + '</option>'));
                });
                $("#btnAssignTask").prop("disabled", false);
            }
        })
    }
    $("#btnAssignTask").click(function() {
        $.ajax({
            url: "{{ route('projects.assign') }}",
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

    function showProject(id) {
        window.location.href = "{{ url('projects') }}" + "/" + id;
    }
    function showGhostProject(id,ghost) {
        window.location.href = "{{ url('projects') }}" + "/" + id + "/ghost" ;
    }
</script>
