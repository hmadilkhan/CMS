<style>
    .files-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .file-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid #dee2e6;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .file-card:hover {
        box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        transform: translateY(-4px);
    }
    .file-preview {
        width: 100%;
        height: 150px;
        overflow: hidden;
        background: #f8f9fa;
    }
    .file-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .file-icon {
        width: 100%;
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    }
    .file-icon i {
        font-size: 64px;
        color: #ffffff;
    }
    .file-details {
        padding: 12px;
    }
    .file-name {
        font-size: 13px;
        font-weight: 600;
        color: #2c3e50;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 5px;
    }
    .file-meta {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: #6c757d;
    }
    .file-actions {
        padding: 8px 12px;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    .btn-file-action {
        color: #2c3e50;
        font-size: 18px;
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-file-action:hover {
        color: #000;
        transform: scale(1.2);
    }
    .file-drop-zone-small {
        border: 2px dashed #2c3e50;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .file-drop-zone-small:hover {
        border-color: #000;
        background: #e9ecef;
    }
    .file-drop-zone-small i {
        font-size: 24px;
        color: #2c3e50;
    }
    .uploaded-files-list {
        margin-top: 15px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
    .file-item {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .file-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .file-item i {
        font-size: 24px;
        color: #2c3e50;
    }
    .file-item .file-info {
        flex: 1;
        min-width: 0;
    }
    .file-item .file-name {
        font-size: 13px;
        font-weight: 600;
        color: #2c3e50;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .file-item .file-size {
        font-size: 11px;
        color: #6c757d;
    }
    .file-item .remove-file {
        cursor: pointer;
        color: #dc3545;
        font-size: 18px;
        transition: transform 0.2s;
    }
    .file-item .remove-file:hover {
        transform: scale(1.2);
    }
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

@if($ticket->files->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <h6 class="fw-bold text-muted mb-3"><i class="icofont-attachment me-2"></i>Attached Files ({{ $ticket->files->count() }})</h6>
        <div class="files-grid">
            @foreach($ticket->files as $file)
                <div class="file-card">
                    @if(in_array(strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                        <div class="file-preview">
                            <img src="{{ asset('storage/' . $file->file_path) }}" alt="{{ $file->file_name }}">
                        </div>
                    @else
                        <div class="file-icon">
                            <i class="icofont-file-{{ 
                                str_contains($file->file_type, 'pdf') ? 'pdf' : 
                                (str_contains($file->file_type, 'word') ? 'word' : 
                                (str_contains($file->file_type, 'excel') || str_contains($file->file_type, 'spreadsheet') ? 'excel' : 'document'))
                            }}"></i>
                        </div>
                    @endif
                    <div class="file-details">
                        <div class="file-name" title="{{ $file->file_name }}">{{ $file->file_name }}</div>
                        <div class="file-meta">
                            <small>{{ number_format($file->file_size / 1024, 2) }} KB</small>
                            <small>{{ $file->uploader->name }}</small>
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="btn-file-action" title="View">
                            <i class="icofont-eye"></i>
                        </a>
                        <a href="{{ asset('storage/' . $file->file_path) }}" download class="btn-file-action" title="Download">
                            <i class="icofont-download"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<hr>

<div class="row mb-3">
    <div class="col-12">
        <h6 class="fw-bold mb-3"><i class="icofont-history me-2"></i>Activity Timeline</h6>
        <div class="activity-timeline">
            @forelse($ticket->comments as $comment)
                <div class="activity-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong><i class="icofont-user me-2"></i>{{ $comment->user->name }}</strong>
                        <small class="text-muted"><i class="icofont-clock-time me-1"></i>{{ $comment->created_at->diffForHumans() }}</small>
                    </div>
                    <p class="mb-2">{{ $comment->comment }}</p>
                    @if($comment->files->count() > 0)
                        <div class="comment-files mt-2">
                            <div class="files-grid" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
                                @foreach($comment->files as $file)
                                    <div class="file-card" style="font-size: 0.9em;">
                                        @if(in_array(strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']))
                                            <div class="file-preview" style="height: 100px;">
                                                <img src="{{ asset('storage/' . $file->file_path) }}" alt="{{ $file->file_name }}">
                                            </div>
                                        @else
                                            <div class="file-icon" style="height: 100px;">
                                                <i class="icofont-file-{{ str_contains($file->file_type, 'pdf') ? 'pdf' : (str_contains($file->file_type, 'word') ? 'word' : (str_contains($file->file_type, 'excel') || str_contains($file->file_type, 'spreadsheet') ? 'excel' : 'document')) }}" style="font-size: 40px;"></i>
                                            </div>
                                        @endif
                                        <div class="file-details" style="padding: 8px;">
                                            <div class="file-name" style="font-size: 11px;">{{ $file->file_name }}</div>
                                            <div class="file-meta" style="font-size: 10px;">
                                                <small>{{ number_format($file->file_size / 1024, 2) }} KB</small>
                                            </div>
                                        </div>
                                        <div class="file-actions" style="padding: 5px;">
                                            <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="btn-file-action" style="font-size: 14px;" title="View">
                                                <i class="icofont-eye"></i>
                                            </a>
                                            <a href="{{ asset('storage/' . $file->file_path) }}" download class="btn-file-action" style="font-size: 14px;" title="Download">
                                                <i class="icofont-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-muted">No activity yet</p>
            @endforelse
        </div>
    </div>
</div>

<hr>
@if(auth()->user()->hasRole('Service Manager') && $ticket->assigned_to == auth()->id())
<form id="commentForm" method="POST" action="{{ route('service-tickets.comment', $ticket->id) }}" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-12 mb-3">
            <label for="comment" class="form-label fw-bold"><i class="icofont-comment me-2"></i>Add Comment</label>
            <textarea class="form-control" id="comment" name="comment" rows="3" style="border-radius: 10px; border: 2px solid #e9ecef;" placeholder="What are you working on? What's next?" required></textarea>
        </div>
        <div class="col-md-6 mb-3">
            <label for="ticketstatus" class="form-label fw-bold"><i class="icofont-check-circled me-2"></i>Update Status</label>
            <select class="form-select" id="ticketstatus" name="ticketstatus" style="border-radius: 10px; border: 2px solid #e9ecef; padding: 0.75rem;">
                <option value="Pending" {{ $ticket->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Resolved" {{ $ticket->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label fw-bold"><i class="icofont-attachment me-2"></i>Attach Files</label>
            <div class="premium-file-upload">
                <input type="file" id="commentFiles" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display:none">
                <div class="file-drop-zone-small" onclick="document.getElementById('commentFiles').click()">
                    <i class="icofont-cloud-upload"></i>
                    <span>Click to attach files</span>
                </div>
                <div id="commentFilesList" class="uploaded-files-list"></div>
            </div>
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
document.getElementById('commentFiles')?.addEventListener('change', function(e) {
    const filesList = document.getElementById('commentFilesList');
    filesList.innerHTML = '';
    
    Array.from(this.files).forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        
        const icon = file.type.includes('image') ? 'icofont-image' : 
                     file.type.includes('pdf') ? 'icofont-file-pdf' :
                     file.type.includes('word') ? 'icofont-file-word' :
                     file.type.includes('excel') || file.type.includes('spreadsheet') ? 'icofont-file-excel' :
                     'icofont-file-document';
        
        fileItem.innerHTML = `
            <i class="${icon}"></i>
            <div class="file-info">
                <div class="file-name">${file.name}</div>
                <div class="file-size">${(file.size / 1024).toFixed(2)} KB</div>
            </div>
            <i class="icofont-close-line remove-file" onclick="removeCommentFile(${index})"></i>
        `;
        filesList.appendChild(fileItem);
    });
});

function removeCommentFile(index) {
    const input = document.getElementById('commentFiles');
    const dt = new DataTransfer();
    const files = Array.from(input.files);
    
    files.forEach((file, i) => {
        if (i !== index) dt.items.add(file);
    });
    
    input.files = dt.files;
    input.dispatchEvent(new Event('change'));
}

$('#commentForm').on('submit', function(e) {
    e.preventDefault();
    
    // Update status first
    $.ajax({
        url: '/service-tickets/{{ $ticket->id }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            _method: 'PUT',
            status: $('#ticketstatus').val(),
            notes: {!! json_encode($ticket->notes) !!}
        },
        success: function() {
            // Then add comment with files
            var formData = new FormData(document.getElementById('commentForm'));
            $.ajax({
                url: $(e.target).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
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
