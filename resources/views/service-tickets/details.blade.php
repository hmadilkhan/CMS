<style>
    .activity-timeline {
        position: relative;
        padding-left: 30px;
    }
    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(180deg, #2c3e50 0%, #000000 100%);
    }
    .activity-item {
        position: relative;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        border-left: 3px solid #2c3e50;
    }
    .activity-item::before {
        content: '';
        position: absolute;
        left: -33px;
        top: 20px;
        width: 16px;
        height: 16px;
        background: #2c3e50;
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
</style>

<div class="row mb-4">
    <div class="col-md-6">
        <h6 class="fw-bold text-muted mb-2">Subject</h6>
        <p class="fs-5">{{ $ticket->subject }}</p>
    </div>
    <div class="col-md-3">
        <h6 class="fw-bold text-muted mb-2">Priority</h6>
        <span class="premium-badge 
            @if($ticket->priority == 'High') bg-danger
            @elseif($ticket->priority == 'Medium') bg-warning text-dark
            @else bg-info
            @endif">
            {{ $ticket->priority }}
        </span>
    </div>
    <div class="col-md-3">
        <h6 class="fw-bold text-muted mb-2">Status</h6>
        <span class="premium-badge {{ $ticket->status == 'Resolved' ? 'bg-success' : 'bg-secondary' }}">
            {{ $ticket->status }}
        </span>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h6 class="fw-bold text-muted mb-2">Initial Notes</h6>
        <p>{{ $ticket->notes ?: 'No initial notes' }}</p>
    </div>
</div>

<hr>

<div class="row mb-3">
    <div class="col-12">
        <h6 class="fw-bold mb-3"><i class="icofont-history me-2"></i>Activity Timeline</h6>
        <div class="activity-timeline">
            @forelse($ticket->comments()->with('user')->orderBy('created_at', 'desc')->get() as $comment)
                <div class="activity-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong><i class="icofont-user me-2"></i>{{ $comment->user->name }}</strong>
                        <small class="text-muted"><i class="icofont-clock-time me-1"></i>{{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-0">{{ $comment->comment }}</p>
                </div>
            @empty
                <p class="text-muted">No activity yet</p>
            @endforelse
        </div>
    </div>
</div>

<hr>
@if(auth()->user()->hasRole('Service Manager') && $ticket->assigned_to == auth()->id())
<form id="commentForm" method="POST" action="{{ route('service-tickets.comment', $ticket->id) }}">
    @csrf
    <div class="row">
        <div class="col-12 mb-3">
            <label for="comment" class="form-label fw-bold"><i class="icofont-comment me-2"></i>Add Comment</label>
            <textarea class="form-control" id="comment" name="comment" rows="3" style="border-radius: 10px; border: 2px solid #e9ecef;" placeholder="What are you working on? What's next?" required></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label for="status" class="form-label fw-bold"><i class="icofont-check-circled me-2"></i>Update Status</label>
            <select class="form-select" id="status" name="status" style="border-radius: 10px; border: 2px solid #e9ecef; padding: 0.75rem;">
                <option value="Pending" {{ $ticket->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Resolved" {{ $ticket->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn premium-btn text-white" style="padding: 0.6rem 1.5rem;">
                <i class="icofont-paper-plane me-2"></i>Add Comment & Update
            </button>
        </div>
    </div>
</form>
@endif

<script>
$('#commentForm').on('submit', function(e) {
    e.preventDefault();
    
    // Update status first
    $.ajax({
        url: '/service-tickets/{{ $ticket->id }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _method: 'PUT',
            status: $('#status').val(),
            notes: {!! json_encode($ticket->notes) !!}
        },
        success: function() {
            // Then add comment
            $.ajax({
                url: $(e.target).attr('action'),
                method: 'POST',
                data: $(e.target).serialize(),
                success: function(response) {
                    $('#ticketModal').modal('hide');
                    location.reload();
                },
                error: function(error) {
                    alert('Error adding comment');
                }
            });
        }
    });
});
</script>
