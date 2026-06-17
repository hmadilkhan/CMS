<style>
    .premium-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: none;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    .premium-card-header {
        background: #ffffff !important;
        color: #050505 !important;
        padding: 1rem 1.25rem;
        border: none;
        border-bottom: 1px solid #e5e7eb;
    }
    .premium-card-header h3,
    .premium-card-header i {
        color: #050505 !important;
    }
    .premium-table thead {
        background: #f8fafc !important;
    }
    .premium-table thead th {
        color: #050505 !important;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .premium-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #f0f0f0;
    }
    .premium-table tbody tr:hover {
        background: #f8fafc;
        transform: none;
        box-shadow: none;
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
        letter-spacing: 0;
    }
    .ticket-badge-high {
        background: #fee2e2 !important;
        color: #991b1b !important;
    }
    .ticket-badge-medium,
    .ticket-badge-pending-time {
        background: #eff6ff !important;
        color: #F19828 !important;
    }
    .ticket-badge-low {
        background: #ecfeff !important;
        color: #0e7490 !important;
    }
    .ticket-badge-status {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
    .ticket-comment-badge {
        background: #050505 !important;
        color: #ffffff !important;
    }
    .admin-ticket-modal-header {
        background: #ffffff !important;
        color: #050505 !important;
        border: none;
        border-bottom: 1px solid #e5e7eb;
        border-radius: 16px 16px 0 0;
    }
    .admin-ticket-modal-header .modal-title,
    .admin-ticket-modal-header i {
        color: #050505 !important;
    }
    .project-link {
        color: #1d4ed8;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .project-link:hover {
        color: #050505;
        text-decoration: underline;
    }

    .premium-card {
        border: 0 !important;
        border-top: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .premium-card-header {
        border-bottom: 1px solid rgba(238, 143, 69, 0.16) !important;
        padding: 0.85rem 0 !important;
    }

    .premium-table thead,
    .premium-table thead th {
        background: rgba(255, 193, 143, 0.13) !important;
        color: #7c2d12 !important;
        border-bottom: 1px solid rgba(238, 143, 69, 0.42) !important;
    }

    .premium-table tbody td {
        border-bottom: 1px solid #eef2f7 !important;
        padding: 0.8rem 0.75rem !important;
    }

    .premium-table tbody tr:hover {
        background: #fffaf5 !important;
    }

    .premium-badge,
    .ticket-comment-badge,
    .ticket-badge-pending-time {
        border-radius: 999px !important;
    }

    .project-link {
        color: #9a3412;
    }

    .btn-dark,
    .btn-primary {
        border-radius: 999px !important;
    }
    .premium-card-header > .ticket-dashboard-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin: 0.85rem 0 0 !important;
        padding: 0 0 0.85rem !important;
        border: 0 !important;
        border-bottom: 1px solid rgba(238, 143, 69, 0.16) !important;
    }
    .premium-card-header > .ticket-dashboard-tabs .nav-item {
        margin: 0 !important;
    }
    .premium-card-header > .ticket-dashboard-tabs .nav-link {
        min-height: 42px;
        display: inline-flex !important;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.95rem !important;
        margin: 0 !important;
        border: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 999px !important;
        background: #ffffff !important;
        color: #7c2d12 !important;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .premium-card-header > .ticket-dashboard-tabs .nav-link:hover,
    .premium-card-header > .ticket-dashboard-tabs .nav-link:focus {
        border-color: rgba(238, 143, 69, 0.52) !important;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }
    .premium-card-header > .ticket-dashboard-tabs .nav-link.active {
        border-color: #F19828 !important;
        background: #F19828 !important;
        color: #ffffff !important;
    }
    .premium-card-header > .ticket-dashboard-tabs .nav-link i {
        color: inherit !important;
    }
    .premium-card-header > .ticket-dashboard-tabs .badge {
        flex: 0 0 auto;
        min-width: 1.6rem;
        padding: 0.25rem 0.5rem;
        border-radius: 999px;
        background: #fff7ed !important;
        color: #9a3412 !important;
    }
    .premium-card-header > .ticket-dashboard-tabs .nav-link.active .badge {
        background: rgba(255, 255, 255, 0.24) !important;
        color: #ffffff !important;
    }
</style>

@php
    $ticketBaseQuery = \App\Models\ServiceTicket::with(['project', 'assignedUser', 'creator'])
        ->withCount('comments')
        ->where("status", "!=", "Resolved");

    $createdTickets = (clone $ticketBaseQuery)
        ->where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->get();

    $assignedTicketsQuery = (clone $ticketBaseQuery);

    if (!auth()->user()->hasRole('Super Admin')) {
        $assignedTicketsQuery->where('assigned_to', auth()->id());
    }

    $assignedTickets = $assignedTicketsQuery->orderBy('created_at', 'desc')->get();

    $tickets = $assignedTickets;
    $ticketGroups = [
        'created-by-me' => [
            'label' => 'Created By Me',
            'icon' => 'icofont-user-alt-3',
            'tickets' => $createdTickets,
        ],
        'assigned-to-me' => [
            'label' => 'Ticket Assigned',
            'icon' => 'icofont-user-suited',
            'tickets' => $assignedTickets,
        ],
    ];
    $activeTicketTab = 'assigned-to-me';
@endphp

<style>
    .premium-widget {
        background: #ffffff !important;
        border-radius: 12px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
    }
    .premium-widget:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1 !important;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06) !important;
    }
    .premium-widget::before {
        content: none;
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: transparent;
        border-radius: 50%;
        transform: translate(30%, -30%);
    }
    .widget-icon {
        width: 56px;
        height: 56px;
        background: #eff6ff !important;
        border: 1px solid #dbeafe;
        backdrop-filter: none;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: #e47b11 !important;
        margin-bottom: 1rem;
    }
    .premium-widget .widget-icon i {
        color: #F19828 !important;
    }
    .widget-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #050505 !important;
        margin: 0;
        line-height: 1;
    }
    .widget-label {
        color: #64748b !important;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0;
        margin-top: 0.5rem;
    }
    .widget-pending,
    .widget-high {
        background: #ffffff !important;
    }
    .widget-pending .widget-icon {
        color: #F19828 !important;
    }
    .widget-high .widget-icon {
        color: #F19828 !important;
        border-color: #fecaca;
    }
</style>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="premium-widget">
            <div class="widget-icon"><i class="icofont-ticket"></i></div>
            <h2 class="widget-value">{{ $tickets->count() }}</h2>
            <p class="widget-label mb-0">Total Tickets</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="premium-widget widget-pending">
            <div class="widget-icon"><i class="icofont-clock-time"></i></div>
            <h2 class="widget-value">{{ $tickets->where('status', 'Pending')->count() }}</h2>
            <p class="widget-label mb-0">Pending</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="premium-widget widget-high">
            <div class="widget-icon"><i class="icofont-warning"></i></div>
            <h2 class="widget-value">{{ $tickets->where('priority', 'High')->count() }}</h2>
            <p class="widget-label mb-0">High Priority</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="premium-card">
            <div class="premium-card-header">
                <h3 class="mb-0"><i class="icofont-ticket me-2"></i>Service Tickets</h3>
                <ul class="nav nav-tabs ticket-dashboard-tabs" id="serviceTicketDashboardTabs" role="tablist">
                    @foreach($ticketGroups as $tabId => $ticketGroup)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $tabId === $activeTicketTab ? 'active' : '' }}"
                                id="{{ $tabId }}-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#{{ $tabId }}"
                                type="button"
                                role="tab"
                                aria-controls="{{ $tabId }}"
                                aria-selected="{{ $tabId === $activeTicketTab ? 'true' : 'false' }}">
                                <i class="{{ $ticketGroup['icon'] }} me-2"></i>{{ $ticketGroup['label'] }}
                                <span class="badge ms-2">{{ $ticketGroup['tickets']->count() }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    @foreach($ticketGroups as $tabId => $ticketGroup)
                        <div class="tab-pane fade {{ $tabId === $activeTicketTab ? 'show active' : '' }}"
                            id="{{ $tabId }}"
                            role="tabpanel"
                            aria-labelledby="{{ $tabId }}-tab">
                            <div class="table-responsive">
                                <table class="table premium-table mb-0">
                                    <thead>
                                        <tr>
                                            <th><i class="icofont-folder me-2"></i>Project</th>
                                            <th><i class="icofont-ui-text-chat me-2"></i>Subject</th>
                                            <th><i class="icofont-user me-2"></i>Assigned To</th>
                                            <th><i class="icofont-user me-2"></i>Created By</th>
                                            <th><i class="icofont-flag me-2"></i>Priority</th>
                                            <th><i class="icofont-check-circled me-2"></i>Status</th>
                                            <th><i class="icofont-ui-note me-2"></i>Notes</th>
                                            <th><i class="icofont-clock-time me-2"></i>Created</th>
                                            <th><i class="icofont-history me-2"></i>Pending Time</th>
                                            <th><i class="icofont-eye me-2"></i>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($ticketGroup['tickets'] as $ticket)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('projects.show', $ticket->project_id) }}" class="project-link">
                                                        <i class="icofont-folder-open me-2"></i>{{ $ticket->project->project_name }}
                                                    </a>
                                                </td>
                                                <td><strong>{{ $ticket->subject }}</strong></td>
                                                <td>{{ $ticket->assignedUser->name ?? 'Unassigned' }}</td>
                                                <td>{{ $ticket->creator->name ?? 'N/A' }}</td>
                                                <td>
                                                    <span class="premium-badge
                                                        @if($ticket->priority == 'High') ticket-badge-high
                                                        @elseif($ticket->priority == 'Medium') ticket-badge-medium
                                                        @else ticket-badge-low
                                                        @endif">
                                                        {{ $ticket->priority }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="premium-badge {{ $ticket->status == 'Resolved' ? 'bg-success' : 'ticket-badge-status' }}">
                                                        <i class="icofont-{{ $ticket->status == 'Resolved' ? 'check' : 'clock-time' }} me-1"></i>{{ $ticket->status }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ $ticket->notes }}
                                                    @if($ticket->comments_count > 0)
                                                        <span class="badge ticket-comment-badge ms-2" title="Comments">
                                                            <i class="icofont-comment"></i> {{ $ticket->comments_count }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td><i class="icofont-ui-calendar me-2"></i>{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                                                <td>
                                                    @if($ticket->status == 'Pending')
                                                        <span class="badge ticket-badge-pending-time">{{ $ticket->created_at->diffForHumans() }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-dark" onclick="viewAdminTicket({{ $ticket->id }})">
                                                        <i class="icofont-eye me-1"></i>View
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center py-5">
                                                    <i class="icofont-ticket" style="font-size: 3rem; opacity: 0.3;"></i>
                                                    <p class="text-muted mt-3">No service tickets found</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Details Modal -->
<div class="modal fade" id="adminTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header admin-ticket-modal-header">
                <h5 class="modal-title fw-bold"><i class="icofont-ticket me-2"></i>Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
