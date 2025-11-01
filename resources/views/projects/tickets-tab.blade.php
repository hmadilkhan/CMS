<div class="tab-pane fade" id="tickets" role="tabpanel">
    <div class="card mt-1">
        <div class="card-header">
            <h3 class="fw-bold mb-0">Service Tickets</h3>
        </div>
        <div class="card-body">
            @can("Create Tickets")
            <form id="ticketForm" method="POST" action="{{ route('service-tickets.store') }}">
                @csrf
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="col-sm-3">
                        <label for="assigned_to" class="form-label">Assigned Person</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">Select Service Manager</option>
                            @foreach($serviceManagers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority" required>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                    <div class="col-sm-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-dark">Create Ticket</button>
                    </div>
                </div>
            </form>
            @endcan

            <hr class="my-4">

            <h4 class="fw-bold mb-3">Existing Tickets</h4>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceTickets as $ticket)
                            <tr>
                                <td>{{ $ticket->subject }}</td>
                                <td>{{ $ticket->assignedUser->name ?? 'Unassigned' }}</td>
                                <td>
                                    <span class="badge 
                                        @if($ticket->priority == 'High') bg-danger
                                        @elseif($ticket->priority == 'Medium') bg-warning
                                        @else bg-info
                                        @endif">
                                        {{ $ticket->priority }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $ticket->status == 'Resolved' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $ticket->status }}
                                    </span>
                                </td>
                                <td>{{ Str::limit($ticket->notes, 50) }}</td>
                                <td>{{ $ticket->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if(auth()->user()->hasRole('Service Manager') && $ticket->assigned_to == auth()->id())
                                        <button class="btn btn-sm btn-primary" onclick="updateTicket({{ $ticket->id }})">
                                            Update
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No tickets found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Update Ticket Modal -->
<div class="modal fade" id="updateTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateTicketForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="update_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="update_notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="update_status" class="form-label">Status</label>
                        <select class="form-select" id="update_status" name="status" required>
                            <option value="Pending">Pending</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#ticketForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            location.reload();
        },
        error: function(error) {
            alert('Error creating ticket');
        }
    });
});

function updateTicket(ticketId) {
    $('#updateTicketModal').modal('show');
    $('#updateTicketForm').attr('action', '/service-tickets/' + ticketId);
}

$('#updateTicketForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            $('#updateTicketModal').modal('hide');
            location.reload();
        },
        error: function(error) {
            alert('Error updating ticket');
        }
    });
});
</script>
