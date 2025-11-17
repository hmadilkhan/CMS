@extends("layouts.master")
@section("content")
<div class="container-xxl">
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
                                        @if(\Carbon\Carbon::parse($followUp->follow_up_date)->isPast())
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
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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
    border-color: #2c3e50;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
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