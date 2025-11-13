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
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin: 0 0.75rem;
    }

    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .project-header {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        padding: 1rem;
        color: white;
    }

    .days-badge {
        background: rgba(255, 255, 255, 0.2);
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
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }

    .department-header h3 {
        color: #2c3e50;
        font-size: 1.5rem;
        margin: 0;
        letter-spacing: 0.5px;
    }

    .premium-filter {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        color: #2c3e50;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .premium-filter:hover {
        border-color: #2c3e50;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-1px);
    }

    .premium-filter:focus {
        border-color: #2c3e50;
        box-shadow: 0 0 0 0.2rem rgba(44,62,80,0.15);
        outline: none;
    }

    .count-badge {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        color: white;
        padding: 0.4rem 0.9rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 700;
        margin-left: 0.75rem;
        box-shadow: 0 2px 8px rgba(44,62,80,0.3);
        transition: all 0.3s ease;
    }

    .no-results-message {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        margin: 1rem;
        display: none;
    }

    .no-results-message i {
        font-size: 3rem;
        color: #6c757d;
        margin-bottom: 1rem;
    }

    .no-results-message h5 {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .no-results-message p {
        color: #6c757d;
        margin: 0;
    }
</style>
@if ($value == 'all')
    @foreach ($departments as $department)
        @php
            $collections = $projects
                ->filter(function ($item) use ($department) {
                    return $item->department_id == $department->id;
                })
                ->values();
        @endphp
        <div class="container-fluid py-2">
            <div class="department-header">
                <h3 class="fw-bold">
                    <i class="icofont-tasks me-2"></i>{{ $department->name }}
                    <span class="count-badge">{{ count($collections) }}</span>
                </h3>
            </div>
            <div class="d-flex flex-row flex-nowrap overflow-auto" style="padding: 0.5rem 0; margin-top: -0.5rem;">
                @if (count($collections) > 0)
                    @foreach ($collections as $project)
                        @php
                            $acceptanceStatus = 'Not Initiated';
                            $acceptanceClass = '';
                            if (!empty($project->projectAcceptance)) {
                                if ($project->projectAcceptance->status == 0) {
                                    $acceptanceStatus = 'Pending';
                                    $acceptanceClass = 'text-warning';
                                } elseif ($project->projectAcceptance->status == 1) {
                                    $acceptanceStatus = 'Approved';
                                    $acceptanceClass = 'text-success';
                                } elseif ($project->projectAcceptance->status == 2) {
                                    $acceptanceStatus = 'Rejected';
                                    $acceptanceClass = 'text-danger';
                                }
                            }
                        @endphp
                        <div class="col-xxxl-3 col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-12"
                            style="cursor:pointer; min-width: 320px; max-width: 380px; padding: 0.5rem;"
                            onclick="showProject('{{ $project->id }}')">
                            <div class="card project-card border-0">
                                <div class="project-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center flex-grow-1 min-w-0">
                                            <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/salespartners/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                                                alt="" class="rounded-circle flex-shrink-0"
                                                style="width: 45px; height: 45px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                                            <h5 class="mb-0 fw-bold ms-3 text-white text-truncate"
                                                style="max-width: 150px;">{{ $project->project_name }}</h5>
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
                                            @if ($project->viewed_emails_count)
                                                <i class="icofont-email text-white blink fs-5 ms-2"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body" style="overflow-wrap: break-word; word-wrap: break-word;">
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-code-alt me-2"></i>Project
                                            Code</span>
                                        <span
                                            class="info-value text-success text-truncate ms-2">{{ $project->code }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-ui-user me-2"></i>Sales
                                            Partner</span>
                                        <span
                                            class="info-value text-success text-truncate ms-2">{{ $project->customer->salespartner->name }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-sand-clock me-2"></i>Status</span>
                                        <span
                                            class="badge bg-danger text-truncate ms-2">{{ $project->assignedPerson[0]->status }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i class="icofont-group-students me-2"></i>Assigned
                                            To</span>
                                        <span
                                            class="info-value text-truncate ms-2">{{ $project->assignedPerson[0]->employee->name }}</span>
                                    </div>
                                    <div class="info-row d-flex justify-content-between align-items-center">
                                        <span class="info-label"><i
                                                class="icofont-check-circled me-2"></i>Acceptance</span>
                                        <span
                                            class="badge bg-{{ $acceptanceClass == 'text-success' ? 'success' : ($acceptanceClass == 'text-warning' ? 'warning' : 'secondary') }} text-truncate ms-2">{{ $acceptanceStatus }}</span>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="info-label">Progress</span>
                                            <span class="fw-bold"
                                                style="color: #2c3e50;">{{ ($project->department_id / 8) * 100 }}%</span>
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
        @php
            $collections = $projects
                ->filter(function ($item) use ($subdepartment) {
                    return $item->sub_department_id == $subdepartment->id;
                })
                ->values();
        @endphp
        <div class="container-fluid py-2">
            <div class="department-header d-flex justify-content-between align-items-center">
                <h3 class="fw-bold mb-0">
                    <i class="icofont-tasks me-2"></i>{{ $subdepartment->name }}
                    <span class="count-badge" id="count-{{ $subdepartment->id }}">{{ count($collections) }}</span>
                </h3>
                @if (in_array($subdepartment->name, ['Permitting New', 'Permitting Rework']))
                    <select class="premium-filter" style="width: 220px;" onchange="filterPermitting(this.value, {{ $subdepartment->id }})">
                        <option value="all">🔍 All Projects</option>
                        <option value="submitted">✅ Submitted</option>
                        <option value="not_submitted">⏳ Not Submitted</option>
                    </select>
                @endif
            </div>
            <div class="d-flex flex-row flex-nowrap overflow-auto subdept-{{ $subdepartment->id }}" style="padding: 0.5rem 0; margin-top: -0.5rem;">
                <div class="no-results-message" id="no-results-{{ $subdepartment->id }}">
                    <i class="icofont-search-document"></i>
                    <h5>No Projects Found</h5>
                    <p>No projects match the selected filter criteria</p>
                </div>
                @if ($subdepartment->id != 21)
                    @if (count($collections) > 0)
                        @foreach ($collections as $project)
                            @php
                                $acceptanceStatus = 'Not Initiated';
                                $acceptanceClass = '';
                                if (!empty($project->projectAcceptance)) {
                                    if ($project->projectAcceptance->status == 0) {
                                        $acceptanceStatus = 'Pending';
                                        $acceptanceClass = 'text-warning';
                                    } elseif ($project->projectAcceptance->status == 1) {
                                        $acceptanceStatus = 'Approved';
                                        $acceptanceClass = 'text-success';
                                    } elseif ($project->projectAcceptance->status == 2) {
                                        $acceptanceStatus = 'Rejected';
                                        $acceptanceClass = 'text-danger';
                                    }
                                }
                            @endphp
                            <div class="col-xxxl-3 col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-12 project-item"
                                style="cursor:pointer; min-width: 320px; max-width: 380px; padding: 0.5rem;"
                                data-submitted="{{ $project->permitting_submittion_date ? 'yes' : 'no' }}"
                                onclick="showProject('{{ $project->id }}')">
                                <div class="card project-card border-0">
                                    <div class="project-header">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center flex-grow-1 min-w-0">
                                                <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/salespartners/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                                                    alt="" class="rounded-circle flex-shrink-0"
                                                    style="width: 45px; height: 45px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                                                <h5 class="mb-0 fw-bold ms-3 text-white text-truncate"
                                                    style="max-width: 150px;">{{ $project->project_name }}</h5>
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
                                                @if ($project->viewed_emails_count)
                                                    <i class="icofont-email text-white blink fs-5 ms-2"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body" style="overflow-wrap: break-word; word-wrap: break-word;">
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-code-alt me-2"></i>Project
                                                Code</span>
                                            <span
                                                class="info-value text-success text-truncate ms-2">{{ $project->code }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-ui-user me-2"></i>Sales
                                                Partner</span>
                                            <span
                                                class="info-value text-success text-truncate ms-2">{{ $project->customer->salespartner->name }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i
                                                    class="icofont-sand-clock me-2"></i>Status</span>
                                            <span
                                                class="badge bg-danger text-truncate ms-2">{{ $project->assignedPerson[0]->status }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i class="icofont-group-students me-2"></i>Assigned
                                                To</span>
                                            <span
                                                class="info-value text-truncate ms-2">{{ $project->assignedPerson[0]->employee->name }}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between align-items-center">
                                            <span class="info-label"><i
                                                    class="icofont-check-circled me-2"></i>Acceptance</span>
                                            <span
                                                class="badge bg-{{ $acceptanceClass == 'text-success' ? 'success' : ($acceptanceClass == 'text-warning' ? 'warning' : 'secondary') }} text-truncate ms-2">{{ $acceptanceStatus }}</span>
                                        </div>
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="info-label">Progress</span>
                                                <span class="fw-bold"
                                                    style="color: #2c3e50;">{{ ($project->department_id / 8) * 100 }}%</span>
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
                                                <i
                                                    class="icofont-ui-note me-2"></i>{{ $project->notes->assign_to_notes }}
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
                @else
                    {{-- GHOST PROJECTS START --}}

                    @if (count($ghostProjects) > 0)
                        @foreach ($ghostProjects as $project)
                            @if ($project->assignedPerson[0]->status == 'In-Progress')
                                <div class="col-xxxl-3 col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-12"
                                    style="cursor:pointer; min-width: 320px; max-width: 380px; padding: 0.5rem;"
                                    onclick="showGhostProject('{{ $project->id }}','ghost')">
                                    <div class="card project-card border-0">
                                        <div class="project-header">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center flex-grow-1 min-w-0">
                                                    <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/salespartners/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                                                        alt="" class="rounded-circle flex-shrink-0"
                                                        style="width: 45px; height: 45px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                                                    <h5 class="mb-0 fw-bold ms-3 text-white text-truncate"
                                                        style="max-width: 150px;">{{ $project->project_name }}</h5>
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
                                                    @if ($project->viewed_emails_count)
                                                        <i class="icofont-email text-white blink fs-5 ms-2"></i>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body"
                                            style="overflow-wrap: break-word; word-wrap: break-word;">
                                            <div class="info-row d-flex justify-content-between align-items-center">
                                                <span class="info-label"><i class="icofont-code-alt me-2"></i>Project
                                                    Code</span>
                                                <span
                                                    class="info-value text-success text-truncate ms-2">{{ $project->code }}</span>
                                            </div>
                                            <div class="info-row d-flex justify-content-between align-items-center">
                                                <span class="info-label"><i class="icofont-ui-user me-2"></i>Sales
                                                    Partner</span>
                                                <span
                                                    class="info-value text-success text-truncate ms-2">{{ $project->customer->salespartner->name }}</span>
                                            </div>
                                            <div class="info-row d-flex justify-content-between align-items-center">
                                                <span class="info-label"><i
                                                        class="icofont-sand-clock me-2"></i>Status</span>
                                                <span
                                                    class="badge bg-danger text-truncate ms-2">{{ $project->assignedPerson[0]->status }}</span>
                                            </div>
                                            <div class="info-row d-flex justify-content-between align-items-center">
                                                <span class="info-label"><i
                                                        class="icofont-group-students me-2"></i>Assigned
                                                    To</span>
                                                <span
                                                    class="info-value text-truncate ms-2">{{ $project->assignedPerson[0]->employee->name }}</span>
                                            </div>
                                            <div class="info-row d-flex justify-content-between align-items-center">
                                                <span class="info-label"><i
                                                        class="icofont-check-circled me-2"></i>Acceptance</span>
                                                <span
                                                    class="badge bg-{{ $acceptanceClass == 'text-success' ? 'success' : ($acceptanceClass == 'text-warning' ? 'warning' : 'secondary') }} text-truncate ms-2">{{ $acceptanceStatus }}</span>
                                            </div>
                                            <div class="mt-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="info-label">Progress</span>
                                                    <span class="fw-bold"
                                                        style="color: #2c3e50;">{{ ($project->department_id / 8) * 100 }}%</span>
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
                                                    <i
                                                        class="icofont-ui-note me-2"></i>{{ $project->notes->assign_to_notes }}
                                                </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            @endif
                            {{-- <div class="col-xxl-2 col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3">
                                <div class="card premium-card h-100"
                                    style="cursor:pointer; transition: all 0.3s ease;"
                                    onclick="showGhostProject('{{ $project->id }}','ghost')"
                                    onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.15)';"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='';">
                                    <div class="card-header premium-header d-flex align-items-center justify-content-between"
                                        style="padding: 1rem;">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $project->customer->salespartner->image != '' ? asset('storage/salespartners/' . $project->customer->salespartner->image) : asset('assets/images/profile_av.png') }}"
                                                alt="" class="rounded-circle"
                                                style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            <h6 class="mb-0 fw-bold ms-2" style="font-size: 0.9rem;">
                                                {{ Str::limit($project->project_name, 15) }}</h6>
                                        </div>
                                        <span class="badge"
                                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-size: 0.75rem; padding: 0.4rem 0.6rem;">
                                            @if (empty($project->pto_approval_date))
                                                {{ now()->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}d
                                            @else
                                                {{ Carbon\Carbon::parse($project->pto_approval_date)->diffInDays(Carbon\Carbon::parse($project->customer->sold_date)) }}d
                                            @endif
                                        </span>
                                    </div>
                                    <div class="card-body premium-body" style="padding: 1rem;">
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="text-muted"><i
                                                        class="icofont-code-alt me-1"></i>Code</small>
                                                <small class="fw-bold text-success">{{ $project->code }}</small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="text-muted"><i
                                                        class="icofont-ui-user me-1"></i>Partner</small>
                                                <small
                                                    class="fw-bold">{{ Str::limit($project->customer->salespartner->name, 12) }}</small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="text-muted"><i
                                                        class="icofont-sand-clock me-1"></i>Status</small>
                                                <small
                                                    class="fw-bold text-danger">{{ $project->assignedPerson[0]->status }}</small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted"><i
                                                        class="icofont-group-students me-1"></i>Assigned</small>
                                                <small
                                                    class="fw-bold">{{ Str::limit($project->assignedPerson[0]->employee->name, 12) }}</small>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="fw-bold">Progress</small>
                                                <small
                                                    class="fw-bold">{{ round(($project->department_id / 8) * 100) }}%</small>
                                            </div>
                                            <div class="progress" style="height: 6px; border-radius: 10px;">
                                                <div class="progress-bar" role="progressbar"
                                                    style="width: {{ ($project->department_id / 8) * 100 }}%; background: linear-gradient(90deg, #48bb78 0%, #38a169 100%); border-radius: 10px;"
                                                    aria-valuenow="{{ ($project->department_id / 8) * 100 }}"
                                                    aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>

                                        @if (!empty($project->notes) && $project->notes->assign_to_notes != '')
                                            <div class="mt-2 p-2"
                                                style="background: #f8f9fa; border-radius: 8px; border-left: 3px solid #667eea;">
                                                <small class="text-muted d-block"
                                                    style="font-size: 0.75rem;">{{ Str::limit($project->notes->assign_to_notes, 50) }}</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div> --}}
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

    function showGhostProject(id, ghost) {
        window.location.href = "{{ url('projects') }}" + "/" + id + "/ghost";
    }

    function filterPermitting(filter, subdeptId) {
        const container = document.querySelector('.subdept-' + subdeptId);
        const projects = container.querySelectorAll('.project-item');
        const noResultsMsg = document.getElementById('no-results-' + subdeptId);
        let visibleCount = 0;
        
        projects.forEach(project => {
            if (filter === 'all') {
                project.style.display = '';
                visibleCount++;
            } else if (filter === 'submitted') {
                if (project.dataset.submitted === 'yes') {
                    project.style.display = '';
                    visibleCount++;
                } else {
                    project.style.display = 'none';
                }
            } else if (filter === 'not_submitted') {
                if (project.dataset.submitted === 'no') {
                    project.style.display = '';
                    visibleCount++;
                } else {
                    project.style.display = 'none';
                }
            }
        });
        
        document.getElementById('count-' + subdeptId).textContent = visibleCount;
        noResultsMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }
</script>
