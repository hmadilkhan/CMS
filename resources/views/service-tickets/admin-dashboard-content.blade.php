<style>
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
    }
    .premium-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
</style>

@php
    $tickets = \App\Models\ServiceTicket::with(['project', 'assignedUser'])
        ->withCount('comments')
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #2c3e50 0%, #000000 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    {{ $tickets->count() }}
                </h2>
                <p class="text-muted mb-0">Total Tickets</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="fw-bold mb-0 text-warning">{{ $tickets->where('status', 'Pending')->count() }}</h2>
                <p class="text-muted mb-0">Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="fw-bold mb-0 text-success">{{ $tickets->where('status', 'Resolved')->count() }}</h2>
                <p class="text-muted mb-0">Resolved</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="fw-bold mb-0 text-danger">{{ $tickets->where('priority', 'High')->count() }}</h2>
                <p class="text-muted mb-0">High Priority</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="premium-card">
            <div class="premium-card-header">
                <h3 class="mb-0"><i class="icofont-ticket me-2"></i>All Service Tickets</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table premium-table mb-0">
                        <thead>
                            <tr>
                                <th><i class="icofont-folder me-2"></i>Project</th>
                                <th><i class="icofont-ui-text-chat me-2"></i>Subject</th>
                                <th><i class="icofont-user me-2"></i>Assigned To</th>
                                <th><i class="icofont-flag me-2"></i>Priority</th>
                                <th><i class="icofont-check-circled me-2"></i>Status</th>
                                <th><i class="icofont-ui-note me-2"></i>Notes</th>
                                <th><i class="icofont-clock-time me-2"></i>Created</th>
                                <th><i class="icofont-eye me-2"></i>Actions</th>
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
                                    <td>{{ $ticket->assignedUser->name ?? 'Unassigned' }}</td>
                                    <td>
                                        <span class="premium-badge 
                                            @if($ticket->priority == 'High') bg-danger
                                            @elseif($ticket->priority == 'Medium') bg-warning text-dark
                                            @else bg-info
                                            @endif">
                                            {{ $ticket->priority }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="premium-badge {{ $ticket->status == 'Resolved' ? 'bg-success' : 'bg-secondary' }}">
                                            <i class="icofont-{{ $ticket->status == 'Resolved' ? 'check' : 'clock-time' }} me-1"></i>{{ $ticket->status }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ Str::limit($ticket->notes, 50) }}
                                        @if($ticket->comments_count > 0)
                                            <span class="badge bg-dark ms-2" title="Comments">
                                                <i class="icofont-comment"></i> {{ $ticket->comments_count }}
                                            </span>
                                        @endif
                                    </td>
                                    <td><i class="icofont-ui-calendar me-2"></i>{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-dark" onclick="viewAdminTicket({{ $ticket->id }})">
                                            <i class="icofont-eye me-1"></i>View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="icofont-ticket" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="text-muted mt-3">No service tickets found</p>
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

<!-- Ticket Details Modal -->
<div class="modal fade" id="adminTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50 0%, #000000 100%); color: #fff; border: none; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold"><i class="icofont-ticket me-2"></i>Ticket Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div id="adminTicketDetails"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewAdminTicket(ticketId) {
    $.ajax({
        url: '/service-tickets/' + ticketId + '/admin-details',
        method: 'GET',
        success: function(response) {
            $('#adminTicketDetails').html(response);
            $('#adminTicketModal').modal('show');
        },
        error: function(error) {
            alert('Error loading ticket details');
        }
    });
}
</script>
