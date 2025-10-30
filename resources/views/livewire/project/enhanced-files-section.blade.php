<div>
    <style>
        .file-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        }

        .file-preview {
            width: 100%;
            height: 250px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-preview iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .file-preview .file-icon {
            font-size: 80px;
            color: #667eea;
        }

        .file-info {
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .file-header {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            word-wrap: break-word;
        }

        .file-name {
            font-size: 0.85rem;
            opacity: 0.9;
            word-wrap: break-word;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .upload-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.6);
            color: white;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 20px 30px;
        }

        .modal-body {
            padding: 30px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-secondary {
            border-radius: 20px;
            padding: 10px 25px;
            font-weight: 600;
        }

        .delete-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(220, 53, 69, 0.9);
            padding: 8px 10px;
            border-radius: 50%;
            color: white;
            z-index: 10;
        }

        .delete-icon:hover {
            background: rgba(220, 53, 69, 1);
            transform: scale(1.15);
        }

        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .no-files {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.1rem;
        }
    </style>

    @can('Files Section')
        @php
            $showEditFields =
                ($ghost == 'ghost' && $departmentId == 7) ||
                ($ghost != 'ghost' && $departmentId == $projectDepartmentId);
        @endphp
        @if ($showEditFields)
            <div class="mb-4">
                <button type="button" wire:click="openModal" class="upload-btn">
                    <i class="icofont-upload-alt me-2"></i>Upload Files
                </button>
            </div>
        @endif
    @endcan

    <div class="files-grid">
        @forelse ($departmentFiles as $file)
            @php
                $extension = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
                $filePath = asset('storage/projects/' . $file->filename);
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'heic']);
                $isPdf = $extension === 'pdf';
            @endphp
            <div class="file-card">
                <div class="file-preview">
                    @if($isImage)
                        <img src="{{ $filePath }}" alt="{{ $file->header_text }}">
                    @elseif($isPdf)
                        <iframe src="{{ $filePath }}#toolbar=0&navpanes=0&scrollbar=0" 
                                title="{{ $file->header_text }}"></iframe>
                    @elseif($extension === 'docx')
                        <i class="icofont-file-word file-icon"></i>
                    @elseif($extension === 'dxf')
                        <i class="icofont-file-alt file-icon"></i>
                    @elseif($extension === 'dwg')
                        <i class="icofont-file-image file-icon"></i>
                    @else
                        <i class="icofont-file-document file-icon"></i>
                    @endif
                    @can('File Delete')
                        <div class="delete-icon" wire:click="$dispatch('deleteConfirmation', {id: {{ $file->id }}})">
                            <i class="icofont-trash"></i>
                        </div>
                    @endcan
                </div>
                <div class="file-info">
                    <div class="file-header">{{ $file->header_text ?? 'Untitled' }}</div>
                    <div class="file-name">
                        <i class="icofont-file-document"></i>
                        <a target="_blank" href="{{ $filePath }}" 
                           class="text-white text-decoration-none">
                            {{ Str::limit($file->filename, 30) }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="no-files">
                <i class="icofont-folder-open display-4 d-block mb-3"></i>
                No Files Found
            </div>
        @endforelse
    </div>

    <!-- Upload Modal -->
    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="icofont-upload-alt me-2"></i>Upload File
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="save">
                            <div class="mb-4">
                                <label for="headerText" class="form-label fw-bold">Header Text</label>
                                <input type="text" class="form-control" id="headerText" wire:model="headerText" 
                                       placeholder="Enter header text">
                                @error('headerText') 
                                    <span class="text-danger small">{{ $message }}</span> 
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label for="file" class="form-label fw-bold">Select File</label>
                                <input type="file" class="form-control" id="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png,.heic,.dxf,.docx">
                                @error('file') 
                                    <span class="text-danger small d-block">{{ $message }}</span> 
                                @enderror
                                <div wire:loading wire:target="file" class="text-primary small mt-2">
                                    <i class="icofont-spinner icofont-spin"></i> Uploading...
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="button" class="btn btn-save" wire:click="saveAndAddMore">
                            Save & Add More
                        </button>
                        <button type="button" class="btn btn-save" wire:click="save">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Modal -->
    <div class="modal fade" id="deletefile" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Delete item Permanently?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body justify-content-center flex-column d-flex">
                    <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                    <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteFile">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    window.addEventListener('show-delete-modal', () => {
        $('#deletefile').modal('show');
    });

    window.addEventListener('hide-delete-modal', () => {
        $('#deletefile').modal('hide');
    });
</script>
@endscript
