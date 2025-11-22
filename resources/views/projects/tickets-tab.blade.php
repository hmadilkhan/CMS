<div class="tab-pane fade" id="tickets" role="tabpanel">
    <div class="card mt-1">
        <div class="card-header">
            <h3 class="fw-bold mb-0">Service Tickets</h3>
        </div>
        <div class="card-body">
            @can("Create Tickets")
            <form id="ticketForm" method="POST" action="{{ route('service-tickets.store') }}" enctype="multipart/form-data">
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
                        <label class="form-label"><i class="icofont-attachment me-2"></i>Attach Files</label>
                        <div class="premium-file-upload">
                            <input type="file" id="ticketFiles" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt" style="display:none">
                            <div class="file-drop-zone" onclick="document.getElementById('ticketFiles').click()">
                                <i class="icofont-cloud-upload"></i>
                                <p class="mb-1">Click to upload or drag and drop</p>
                                <small class="text-muted">Images, PDF, DOC, XLS (Max 10MB each)</small>
                            </div>
                            <div id="ticketFilesList" class="uploaded-files-list"></div>
                        </div>
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
                            <th class="text-white">Subject</th>
                            <th class="text-white">Assigned To</th>
                            <th class="text-white">Created by</th>
                            <th class="text-white">Priority</th>
                            <th class="text-white">Status</th>
                            <th class="text-white">Notes</th>
                            <th class="text-white">Created</th>
                            <th class="text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($serviceTickets as $ticket)
                            <tr>
                                <td>{{ $ticket->subject }}</td>
                                <td>{{ $ticket->assignedUser->name ?? 'Unassigned' }}</td>
                                <td>{{ $ticket->creator->name ?? "N/A" }}</td>
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
                                    <button class="btn btn-sm btn-primary text-white" onclick="viewTicketDetails({{ $ticket->id }})">
                                        <i class="icofont-eye"></i> View
                                    </button>
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

<!-- View Ticket Details Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content premium-modal">
            <div class="modal-header premium-modal-header">
                <h5 class="modal-title text-white"><i class="icofont-ticket me-2"></i>Ticket Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body premium-modal-body" id="ticketDetailsContent">
                <div class="text-center py-5">
                    <div class="premium-spinner-large"></div>
                    <p class="mt-3 text-muted">Loading ticket details...</p>
                </div>
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
.premium-file-upload {
    margin-top: 10px;
}
.file-drop-zone {
    border: 2px dashed #2c3e50;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
.file-drop-zone:hover {
    border-color: #000;
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    transform: translateY(-2px);
}
.file-drop-zone i {
    font-size: 48px;
    color: #2c3e50;
    display: block;
    margin-bottom: 10px;
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
.premium-modal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}
.premium-modal-header {
    background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    border-radius: 16px 16px 0 0;
    padding: 1.5rem;
    border: none;
}
.premium-modal-body {
    padding: 2rem;
    max-height: 70vh;
    overflow-y: auto;
}
.premium-modal-body::-webkit-scrollbar {
    width: 8px;
}
.premium-modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}
.premium-modal-body::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    border-radius: 10px;
}
.premium-modal-body::-webkit-scrollbar-thumb:hover {
    background: #000000;
}
.premium-spinner-large {
    width: 60px;
    height: 60px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #2c3e50;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}
.premium-badge {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
}
.premium-btn {
    background: linear-gradient(135deg, #2c3e50 0%, #000000 100%);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}
.premium-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}space: nowrap;
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

<script>
document.getElementById('ticketFiles').addEventListener('change', function(e) {
    const filesList = document.getElementById('ticketFilesList');
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
            <i class="icofont-close-line remove-file" onclick="removeFile(${index})"></i>
        `;
        filesList.appendChild(fileItem);
    });
});

function removeFile(index) {
    const input = document.getElementById('ticketFiles');
    const dt = new DataTransfer();
    const files = Array.from(input.files);
    
    files.forEach((file, i) => {
        if (i !== index) dt.items.add(file);
    });
    
    input.files = dt.files;
    input.dispatchEvent(new Event('change'));
}
</script>
