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
                        <label for="assigned_to" class="form-label">Assigned Person <span class="text-danger">*</span></label>
                        <select class="form-select" id="assigned_to" name="assigned_to" required>
                            <option value="">Select Service Manager</option>
                            @foreach($serviceManagers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select an assigned person.</div>
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
                        <label for="notes" class="form-label">Notes <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" required></textarea>
                        <div class="invalid-feedback">Please provide notes.</div>
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

<style>
.premium-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
.premium-loader-content {
    background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    padding: 40px 60px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.1);
    text-align: center;
}
.premium-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-top: 4px solid #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.premium-loader-text {
    color: #ffffff;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.5px;
}
.premium-loader-subtext {
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    margin-top: 8px;
}
</style>

<!-- Premium Loader -->
<div class="premium-loader-overlay" id="premiumLoader">
    <div class="premium-loader-content">
        <div class="premium-spinner"></div>
        <p class="premium-loader-text">Creating Ticket...</p>
        <p class="premium-loader-subtext">Please wait a moment</p>
    </div>
</div>

@section('scripts')
<script>
(function() {
    'use strict';
    
    $(document).ready(function() {
        // Restore active tab on page load
        const activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            $('.nav-link[href="' + activeTab + '"]').tab('show');
            localStorage.removeItem('activeTab');
        }
        
        // Ticket form submission
        $('#ticketForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            const form = this;
            const assignedTo = $('#assigned_to').val();
            const notes = $('#notes').val().trim();
            
            $(form).find('.is-invalid').removeClass('is-invalid');
            
            let isValid = true;
            
            if (!assignedTo) {
                $('#assigned_to').addClass('is-invalid');
                isValid = false;
            }
            
            if (!notes) {
                $('#notes').addClass('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                return false;
            }
            
            // Save active tab before reload
            localStorage.setItem('activeTab', '#tickets');
            
            $('#premiumLoader').css('display', 'flex');
            
            $.ajax({
                url: $(form).attr('action'),
                method: 'POST',
                data: $(form).serialize(),
                success: function(response) {
                    $(form)[0].reset();
                    setTimeout(function() {
                        $('#premiumLoader').hide();
                        location.reload();
                    }, 500);
                },
                error: function(error) {
                    $('#premiumLoader').hide();
                    localStorage.removeItem('activeTab');
                    alert('Error creating ticket. Please try again.');
                }
            });
            
            return false;
        });

        $('#assigned_to, #notes').on('change input', function() {
            $(this).removeClass('is-invalid');
        });

        $('#updateTicketForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            // Save active tab before reload
            localStorage.setItem('activeTab', '#tickets');
            
            $('#premiumLoader').css('display', 'flex');
            $('.premium-loader-text').text('Updating Ticket...');
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    setTimeout(function() {
                        $('#updateTicketModal').modal('hide');
                        $('#premiumLoader').hide();
                        $('.premium-loader-text').text('Creating Ticket...');
                        location.reload();
                    }, 500);
                },
                error: function(error) {
                    $('#premiumLoader').hide();
                    $('.premium-loader-text').text('Creating Ticket...');
                    localStorage.removeItem('activeTab');
                    alert('Error updating ticket. Please try again.');
                }
            });
            
            return false;
        });
    });
    
    window.updateTicket = function(ticketId) {
        $('#updateTicketModal').modal('show');
        $('#updateTicketForm').attr('action', '/service-tickets/' + ticketId);
    };
})();
</script>
@endsection
