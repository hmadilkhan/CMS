@extends("layouts.master")
@section("content")
<style>
    .dashboard-tabs .nav-link {
        border: none;
        color: #050505 !important;
        font-weight: 600;
        padding: 0 0 0.85rem;
        border-radius: 0;
        transition: all 0.3s ease;
        background: transparent !important;
        margin-right: 1.5rem;
        border-bottom: 2px solid transparent;
    }
    .employee-dashboard-page .dashboard-tabs .nav-link,
    .employee-dashboard-page .dashboard-tabs .nav-link *,
    .employee-dashboard-page .dashboard-tabs .nav-link:not(.active),
    .employee-dashboard-page .dashboard-tabs .nav-link:not(.active) * {
        color: #050505 !important;
    }
    .dashboard-tabs .nav-link:hover {
        background: transparent !important;
        color: #050505 !important;
    }
    .dashboard-tabs .nav-link.active {
        background: transparent !important;
        color: #F19828 !important;
        border-bottom-color: #F19828;
        box-shadow: none !important;
    }
    .employee-dashboard-page .dashboard-tabs .nav-link.active,
    .employee-dashboard-page .dashboard-tabs .nav-link.active * {
        color: #F19828 !important;
    }
    .dashboard-tabs {
        border-bottom: 1px solid #e5e7eb;
    }
    .employee-dashboard-page .card {
        border: 1px solid #e5e7eb !important;
        border-radius: 8px;
        box-shadow: none !important;
    }
    .employee-dashboard-page .card-header {
        background: #ffffff !important;
        border-bottom: 1px solid #e5e7eb !important;
        color: #050505 !important;
    }
    .employee-dashboard-page .card-header *,
    .employee-dashboard-page .card-title {
        color: #050505 !important;
    }
    .employee-dashboard-page .badge.bg-danger {
        background: #1d4ed8 !important;
    }
    /* Upcoming AHJ's count - theme accent, dark text (best contrast on #F19828) */
    .employee-dashboard-page .dashboard-tabs .nav-link .ahj-tab-badge,
    .employee-dashboard-page .dashboard-tabs .nav-link.active .ahj-tab-badge {
        background: #F19828 !important;
        color: #050505 !important;
        font-weight: 700;
    }
</style>
<div class="container-xxl employee-dashboard-page">
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs dashboard-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" role="tab">
                        <i class="icofont-dashboard me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#service-tickets" role="tab">
                        <i class="icofont-ticket me-2"></i>Service Tickets
                        @if(count($serviceTickets) > 0)
                            <span class="badge bg-danger ms-1">{{ count($serviceTickets) }}</span>
                        @endif
                    </a>
                </li>
                @if(!empty($showUpcomingAhj))
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#upcoming-ahjs" role="tab">
                        <i class="icofont-building me-2"></i>Upcoming AHJ's
                        @if($upcomingAhjProjects->count() > 0)
                            <span class="badge ahj-tab-badge ms-1">{{ $upcomingAhjProjects->count() }}</span>
                        @endif
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
    <div class="row g-3 mb-3 row-deck">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold ">New Emails Received</h6>
                    </div>
                </div>
                <div class="card-body">
                    <table id="emailsTable" class="table table-hover align-middle mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Customer Name</th>
                                <th>Customer email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($emails as $key => $email)
                            <tr>
                                <td>{{ ++$key }}</td>
                                <td>{{ $email->customer->first_name." ".$email->customer->last_name }}</td>
                                <td>{{ $email->customer->email }}</td>
                                <td>
                                    <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{route('projects.show',$email->project->id)}}">
                                        <i class="icofont-eye text-primary fs-4"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card mb-3 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-gradient-primary">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold text-white"><i class="icofont-calendar me-2"></i>Follow Up Tasks</h6>
                    </div>
                    <span class="badge bg-light text-primary rounded-pill">{{ count($followUps) }}</span>
                </div>
                <div class="card-body p-0">
                    @if(count($followUps) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-semibold text-muted">Project</th>
                                    <th class="border-0 fw-semibold text-muted">Notes</th>
                                    <th class="border-0 fw-semibold text-muted">Follow Up Date</th>
                                    <th class="border-0 fw-semibold text-muted">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($followUps as $followUp)
                                <tr class="border-bottom">
                                    <td class="py-3">
                                        <a href="{{ route('projects.show', $followUp->project->id) }}" 
                                           class="text-decoration-none fw-semibold text-primary hover-underline">
                                            {{ $followUp->project->project_name }}
                                        </a>
                                        <div class="small text-muted mt-1">
                                            {{ $followUp->project->customer->first_name }} {{ $followUp->project->customer->last_name }}
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $followUp->notes }}">
                                            {{ $followUp->notes }}
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex align-items-center">
                                            <i class="icofont-calendar text-muted me-2"></i>
                                            <span class="{{ \Carbon\Carbon::parse($followUp->follow_up_date)->isPast() ? 'text-danger fw-semibold' : 'text-dark' }}">
                                                {{ \Carbon\Carbon::parse($followUp->follow_up_date)->format('M d, Y') }}
                                            </span>
                                        </div>
                                        @if(\Carbon\Carbon::parse($followUp->follow_up_date)->isToday())
                                            <small class="text-warning">Due Today</small>
                                        @elseif(\Carbon\Carbon::parse($followUp->follow_up_date)->isPast())
                                            <small class="text-danger">Overdue</small>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @if($followUp->status == 'Resolved')
                                            <span class="badge bg-success">Resolved</span>
                                            @if($followUp->resolved_date)
                                                <div class="small text-muted mt-1">
                                                    {{ $followUp->resolved_date->format('M d, Y H:i') }}
                                                </div>
                                            @endif
                                        @else
                                            <select class="form-select form-select-sm status-select" 
                                                    data-followup-id="{{ $followUp->id }}" 
                                                    style="width: auto; min-width: 100px;">
                                                <option value="Pending" selected>Pending</option>
                                                <option value="Resolved">Resolved</option>
                                            </select>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="icofont-calendar text-muted" style="font-size: 3rem;"></i>
                        <h6 class="text-muted mt-3">No follow-up tasks scheduled</h6>
                        <p class="text-muted small">Follow-up tasks will appear here when assigned.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold ">{{((auth()->user()->getRoleNames()[0] != "Super Admin" and auth()->user()->getRoleNames()[0] != "Admin") ? 'Project Assigned to Me' : 'Projects Information')}}</h6>
                    </div>
                </div>
                <div class="card-body">
                    <table id="myProjectTable" class="table table-hover align-middle mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Project Name</th>
                                <th>Sales Partner</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Sub Department</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($projects["projects"] as $key => $project)
                            <tr>
                                <td>{{ ++$key }}</td>
                                <td>{{ $project->project_name }}</td>
                                <td>{{ $project->customer->salespartner->name }}</td>
                                <td>{{ $project->assignedPerson[0]->employee->name }}</td>
                                <td>{{ $project->department->name }}</td>
                                <td>{{ $project->subdepartment->name }}</td>
                                <td>
                                    <span class="small  {{($project->status == 'In-Progress' ? 'light-danger-bg' : 'light-success-bg')}}  p-1 rounded"><i class="icofont-ui-clock"></i> {{$project->assignedPerson[0]->status}}</span>
                                </td>
                                @can("View Project")
                                <td class="text-center">
                                    <a style="cursor: pointer;" data-toggle="tooltip" title="Edit" href="{{route('projects.show',$project->id)}}">
                                        <i class="icofont-eye text-primary fs-4"></i></a>
                                </td>
                                @endcan
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> --}}

    </div><!-- Row End -->
        </div>

        <div class="tab-pane fade" id="service-tickets" role="tabpanel">
            @include('service-tickets.employee-dashboard-content')
        </div>

        @if(!empty($showUpcomingAhj))
        <div class="tab-pane fade" id="upcoming-ahjs" role="tabpanel">
            <div class="card mb-3 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold"><i class="icofont-building me-2"></i>Upcoming AHJ's</h6>
                        <small class="text-muted">Projects currently in Site Survey or Engineering</small>
                    </div>
                    <span class="badge bg-light text-primary rounded-pill">{{ $upcomingAhjProjects->count() }}</span>
                </div>
                <div class="card-body">
                    <div class="{{ $upcomingAhjProjects->isEmpty() ? 'd-none' : '' }}" id="upcomingAhjTableWrap">
                        <table id="upcomingAhjTable" class="table table-hover align-middle mb-0 datatable" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Project Id</th>
                                    <th>Project Name</th>
                                    <th>AHJ Name</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($upcomingAhjProjects as $ahjProject)
                                <tr class="upcoming-ahj-row" data-project-id="{{ $ahjProject->id }}">
                                    <td>{{ $ahjProject->code ?? '—' }}</td>
                                    <td>
                                        <a class="text-decoration-none" href="{{ route('projects.show', $ahjProject->id) }}">{{ $ahjProject->project_name }}</a>
                                    </td>
                                    <td>{{ trim((string) $ahjProject->ahj) !== '' ? $ahjProject->ahj : '—' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger ahj-mark-remove" data-project-id="{{ $ahjProject->id }}">
                                            <i class="icofont-close-circled me-1"></i>Mark As Remove
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center text-muted py-4 {{ $upcomingAhjProjects->isEmpty() ? '' : 'd-none' }}" id="upcomingAhjEmpty">
                        No upcoming AHJ projects right now.
                    </div>
                </div>
            </div>

            <div class="card mb-3 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold"><i class="icofont-history me-2"></i>Removed From The List</h6>
                        <small class="text-muted">Removed by hand, or moved on to Permitting</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#removedAhjPanel">
                        <span class="badge bg-light text-primary rounded-pill me-1" id="removedAhjCount">{{ $removedAhjProjects->count() }}</span> Show / Hide
                    </button>
                </div>
                <div class="collapse" id="removedAhjPanel">
                    <div class="card-body">
                        <div class="{{ $removedAhjProjects->isEmpty() ? 'd-none' : '' }}" id="removedAhjTableWrap">
                            <table id="removedAhjTable" class="table table-hover align-middle mb-0" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Project Id</th>
                                        <th>Project Name</th>
                                        <th>AHJ Name</th>
                                        <th>Left The List On</th>
                                        <th>Reason</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="removedAhjBody">
                                    @foreach ($removedAhjProjects as $removed)
                                    <tr class="removed-ahj-row" data-project-id="{{ $removed->project_id }}">
                                        <td>{{ $removed->project->code ?? '—' }}</td>
                                        <td>
                                            <a class="text-decoration-none" href="{{ route('projects.show', $removed->project_id) }}">{{ $removed->project->project_name }}</a>
                                        </td>
                                        <td>{{ trim((string) $removed->project->ahj) !== '' ? $removed->project->ahj : '—' }}</td>
                                        <td>{{ optional($removed->removed_at)->format('d M Y, h:i A') }}</td>
                                        <td>
                                            @if($removed->isManual())
                                                <span class="badge bg-secondary">Marked as removed</span>
                                                <small class="text-muted d-block">by {{ $removed->removedBy->name ?? 'Unknown' }}</small>
                                            @else
                                                <span class="badge bg-success">Moved to {{ $removed->movedToDepartment->name ?? 'another lane' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($removed->isManual())
                                                <button type="button" class="btn btn-sm btn-outline-primary ahj-restore" data-project-id="{{ $removed->project_id }}">
                                                    <i class="icofont-undo me-1"></i>Undo
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center text-muted py-4 {{ $removedAhjProjects->isEmpty() ? '' : 'd-none' }}" id="removedAhjEmpty">
                            Nothing has left the list yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@section('scripts')
<script src="{{asset('assets/bundles/apexcharts.bundle.js')}}"></script>
<script src="{{asset('page/index.js')}}"></script>
<script>
$(document).ready(function() {
    // Handle status change for follow-ups
    $('.status-select').on('change', function() {
        const followUpId = $(this).data('followup-id');
        const newStatus = $(this).val();
        const selectElement = $(this);
        
        $.ajax({
            url: '{{ route("followup.status.update") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                followup_id: followUpId,
                status: newStatus
            },
            success: function(response) {
                if(response.status === 200) {
                    // Show success toast
                    showToast('Success!', 'Follow-up status updated successfully', 'success');
                    
                    // If status changed to Resolved, refresh page to show updated UI
                    if(newStatus === 'Resolved') {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        // Add visual feedback for other status changes
                        selectElement.addClass('border-success');
                        setTimeout(() => {
                            selectElement.removeClass('border-success');
                        }, 2000);
                    }
                } else {
                    showToast('Error!', 'Failed to update status', 'error');
                }
            },
            error: function() {
                showToast('Error!', 'Failed to update status', 'error');
                // Revert the select to previous value
                selectElement.val(selectElement.data('original-value'));
            }
        });
    });
    
    // Store original values
    $('.status-select').each(function() {
        $(this).data('original-value', $(this).val());
    });

    // Upcoming AHJ's - the live table is a DataTable, so rows on another page
    // leave the DOM; the counts are kept on counters, not by counting rows.
    let ahjLiveCount = {{ !empty($showUpcomingAhj) ? $upcomingAhjProjects->count() : 0 }};
    let ahjRemovedCount = {{ !empty($showUpcomingAhj) ? $removedAhjProjects->count() : 0 }};
    const ahjCurrentUser = @json(auth()->user()->name);

    function ahjLiveTable() {
        return ($.fn.DataTable && $.fn.DataTable.isDataTable('#upcomingAhjTable'))
            ? $('#upcomingAhjTable').DataTable()
            : null;
    }

    // DataTables sizes hidden tables wrong; re-measure when the tab is opened.
    $('a[href="#upcoming-ahjs"]').on('shown.bs.tab', function() {
        const dt = ahjLiveTable();
        if (dt) {
            dt.columns.adjust();
        }
    });

    function ahjRowCells(row) {
        return {
            code: row.find('td').eq(0).html(),
            name: row.find('td').eq(1).html(),
            ahj: row.find('td').eq(2).html()
        };
    }

    // Mark As Remove - takes the project off the live list for everyone
    $(document).on('click', '.ahj-mark-remove', function() {
        const button = $(this);
        const row = button.closest('.upcoming-ahj-row');
        const projectId = button.data('project-id');
        const cells = ahjRowCells(row);

        button.prop('disabled', true);

        $.ajax({
            url: '{{ route("upcoming.ahj.remove") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                project_id: projectId
            },
            success: function(response) {
                if (response.status === 200) {
                    dropLiveAhjRow(row);
                    prependRemovedAhjRow(projectId, cells, response.removed_at, ahjCurrentUser);
                    showToast('Success!', response.message, 'success');
                } else {
                    showToast('Error!', response.message || 'Failed to update', 'error');
                    button.prop('disabled', false);
                }
            },
            error: function(xhr) {
                showToast('Error!', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update', 'error');
                button.prop('disabled', false);
            }
        });
    });

    // Undo - put a manually removed project back on the live list
    $(document).on('click', '.ahj-restore', function() {
        const button = $(this);
        const row = button.closest('.removed-ahj-row');
        const projectId = button.data('project-id');
        const cells = ahjRowCells(row);

        button.prop('disabled', true);

        $.ajax({
            url: '{{ route("upcoming.ahj.restore") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                project_id: projectId
            },
            success: function(response) {
                if (response.status === 200) {
                    row.remove();
                    ahjRemovedCount = Math.max(0, ahjRemovedCount - 1);
                    addLiveAhjRow(projectId, cells);
                    showToast('Success!', response.message, 'success');
                } else {
                    showToast('Error!', response.message || 'Failed to update', 'error');
                    button.prop('disabled', false);
                }
            },
            error: function(xhr) {
                showToast('Error!', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update', 'error');
                button.prop('disabled', false);
            }
        });
    });

    function ahjRemoveButton(projectId) {
        return '<button type="button" class="btn btn-sm btn-outline-danger ahj-mark-remove" data-project-id="' + projectId + '">' +
            '<i class="icofont-close-circled me-1"></i>Mark As Remove</button>';
    }

    function dropLiveAhjRow(row) {
        const dt = ahjLiveTable();

        if (dt) {
            dt.row(row).remove().draw(false);
        } else {
            row.remove();
        }

        ahjLiveCount = Math.max(0, ahjLiveCount - 1);
        refreshAhjCounts();
    }

    function addLiveAhjRow(projectId, cells) {
        const dt = ahjLiveTable();

        if (dt) {
            const added = dt.row.add([cells.code, cells.name, cells.ahj, ahjRemoveButton(projectId)]).draw(false);
            $(added.node())
                .addClass('upcoming-ahj-row')
                .attr('data-project-id', projectId)
                .find('td').last().addClass('text-center');
        } else {
            $('#upcomingAhjTable tbody').append(
                '<tr class="upcoming-ahj-row" data-project-id="' + projectId + '">' +
                '<td>' + cells.code + '</td><td>' + cells.name + '</td><td>' + cells.ahj + '</td>' +
                '<td class="text-center">' + ahjRemoveButton(projectId) + '</td></tr>'
            );
        }

        ahjLiveCount += 1;
        refreshAhjCounts();
    }

    function prependRemovedAhjRow(projectId, cells, removedAt, removedBy) {
        $('#removedAhjBody').prepend(
            '<tr class="removed-ahj-row" data-project-id="' + projectId + '">' +
            '<td>' + cells.code + '</td><td>' + cells.name + '</td><td>' + cells.ahj + '</td>' +
            '<td>' + removedAt + '</td>' +
            '<td><span class="badge bg-secondary">Marked as removed</span>' +
            '<small class="text-muted d-block">by ' + $('<div>').text(removedBy).html() + '</small></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary ahj-restore" data-project-id="' + projectId + '">' +
            '<i class="icofont-undo me-1"></i>Undo</button></td></tr>'
        );

        ahjRemovedCount += 1;
        refreshAhjCounts();
    }

    function refreshAhjCounts() {
        const tabLink = $('a[href="#upcoming-ahjs"]');
        let badge = tabLink.find('.ahj-tab-badge');

        if (ahjLiveCount === 0) {
            badge.remove();
        } else {
            if (badge.length === 0) {
                badge = $('<span class="badge ahj-tab-badge ms-1"></span>').appendTo(tabLink);
            }
            badge.text(ahjLiveCount);
        }

        $('#upcomingAhjTableWrap').toggleClass('d-none', ahjLiveCount === 0);
        $('#upcomingAhjEmpty').toggleClass('d-none', ahjLiveCount !== 0);
        $('#removedAhjCount').text(ahjRemovedCount);
        $('#removedAhjTableWrap').toggleClass('d-none', ahjRemovedCount === 0);
        $('#removedAhjEmpty').toggleClass('d-none', ahjRemovedCount !== 0);
    }
    
    // Toast notification function
    function showToast(title, message, type) {
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        $('body').append(toastHtml);
        const toast = new bootstrap.Toast(document.getElementById(toastId));
        toast.show();
        
        // Remove toast after it's hidden
        document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
            $(this).remove();
        });
    }
});
</script>
<style>
.bg-gradient-primary {
    background: #ffffff !important;
}
.hover-underline:hover {
    text-decoration: underline !important;
}
.card {
    border: none;
    border-radius: 12px;
}
.table th {
    font-weight: 600;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}
.form-select-sm {
    border-radius: 6px;
    border: 1px solid #e3e6f0;
}
.form-select-sm:focus {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.12);
}
#removedAhjTable td {
    color: #6b7280;
}
</style>
@endsection
<!-- Jquery Core Js -->
<!-- <script src="assets/bundles/libscripts.bundle.js"></script> -->
<!-- Plugin Js-->
<!-- <script src="{{asset('assets/bundles/libscripts.bundle.js')}}"></script>

<script src="{{asset('page/hr.js')}}"></script>
<script src="{{asset('page/index.js')}}'"></script> -->
@endsection
