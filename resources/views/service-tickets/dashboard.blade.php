@extends('layouts.master')
@section('title', 'Service Dashboard')
@section('content')
<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    .premium-header {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }
    .premium-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    .premium-header h1 {
        color: #fff;
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .premium-header p {
        color: rgba(255,255,255,0.8);
        margin: 0.5rem 0 0 0;
        font-size: 1.1rem;
    }
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
    .premium-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        border: none;
        overflow: hidden;
    }
    .premium-card-header {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        color: #fff;
        padding: 1.5rem 2rem;
        border: none;
    }
    .premium-card-header h3 {
        margin: 0;
        font-weight: 700;
        font-size: 1.5rem;
    }
    .premium-table {
        margin: 0;
    }
    .premium-table thead {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    }
    .premium-table thead th {
        color: #fff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 1.2rem 1rem;
        border: none;
    }
    .premium-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #f0f0f0;
    }
    .premium-table tbody tr:hover {
        background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
        transform: scale(1.01);
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .premium-table tbody td {
        padding: 1.2rem 1rem;
        vertical-align: middle;
        font-size: 0.95rem;
    }
    .premium-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .premium-btn {
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .premium-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    .project-link {
        color: #2c3e50;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .project-link:hover {
        color: #000;
        text-decoration: underline;
    }
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }
</style>
<div class="container-xxxl">
    <div class="premium-header">
        <h1><i class="icofont-ticket me-3"></i>Service Dashboard</h1>
        <p>Manage and track your assigned service tickets</p>
    </div>

    <div class="row g-3 mb-3 row-deck">
        <div class="col-md-4">
            <div class="card mb-3 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-gradient-primary">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold text-white"><i class="icofont-clock-time me-2"></i>Pending Tickets</h6>
                    </div>
                    <span class="badge bg-light text-primary rounded-pill">{{ $tickets->where('status', 'Pending')->count() }}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-gradient-primary">
                    <div class="info-header">
                        <h6 class="mb-0 fw-bold text-white"><i class="icofont-flag me-2"></i>High Priority</h6>
                    </div>
                    <span class="badge bg-light text-primary rounded-pill">{{ $tickets->where('priority', 'High')->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="premium-card">
                <div class="premium-card-header">
                    <h3><i class="icofont-list me-2"></i>My Service Tickets</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table premium-table">
                            <thead>
                                <tr>
                                    <th><i class="icofont-folder me-2"></i>Project</th>
                                    <th><i class="icofont-ui-text-chat me-2"></i>Subject</th>
                                    <th><i class="icofont-user me-2"></i>Created By</th>
                                    <th><i class="icofont-flag me-2"></i>Priority</th>
                                    <th><i class="icofont-check-circled me-2"></i>Status</th>
                                    <th><i class="icofont-ui-note me-2"></i>Notes</th>
                                    <th><i class="icofont-clock-time me-2"></i>Created</th>
                                    <th><i class="icofont-settings me-2"></i>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr>
                                        <td>
                                            <a href="{{ route('projects.show', $ticket->project_id) }}" class="project-link">
                                                <i class="icofont-folder-open me-2"></i>{{ $ticket->project->project_name }}
                                            </a>
                                        </td>
                                        <td><strong>{{ $ticket->subject }}</strong></td>
                                        <td><i class="icofont-user me-2"></i>{{ $ticket->creator->name ?? 'N/A' }}</td>
                                        <td>
                                        <span class="premium-badge text-white
                                            @if($ticket->priority == 'High') bg-danger
                                            @elseif($ticket->priority == 'Medium') bg-warning text-dark
                                            @else bg-info
                                            @endif">
                                            {{ $ticket->priority }}
                                        </span>
                                    </td>
                                        <td>
                                            <span class="premium-badge {{ $ticket->status == 'Resolved' ? 'bg-success' : 'bg-warning' }}">
                                                <i class="icofont-{{ $ticket->status == 'Resolved' ? 'check' : 'clock-time' }} me-1"></i>{{ $ticket->status }}
                                            </span>
                                        </td>
                                        <td>
                                            {{$ticket->notes }}
                                            @if($ticket->comments_count > 0)
                                                <span class="badge bg-dark ms-2" title="Comments">
                                                    <i class="icofont-comment"></i> {{ $ticket->comments_count }}
                                                </span>
                                            @endif
                                        </td>
                                        <td><i class="icofont-ui-calendar me-2"></i>{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <button class="btn btn-sm premium-btn text-white" onclick="viewTicket({{ $ticket->id }})">
                                                <i class="icofont-eye me-1"></i>View Details
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="icofont-ticket"></i>
                                                <h4>No Tickets Assigned</h4>
                                                <p>You don't have any service tickets assigned to you at the moment.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Details Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50 0%, #000000 100%); color: #fff; border: none; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="icofont-ticket me-2"></i>Ticket Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div id="ticketDetails"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewTicket(ticketId) {
    $.ajax({
        url: '/service-tickets/' + ticketId + '/details',
        method: 'GET',
        success: function(response) {
            $('#ticketDetails').html(response);
            $('#ticketModal').modal('show');
        },
        error: function(error) {
            alert('Error loading ticket details');
        }
    });
}
</script>
@endsection
