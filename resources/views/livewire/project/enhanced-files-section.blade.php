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

        .modal.show {
            pointer-events: none;
        }

        .modal-dialog {
            pointer-events: auto;
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

        .file-type-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .file-type-icon span {
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
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

        .btn-secondary,
        .btn-danger {
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

        .premium-dropzone {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 50px 30px;
            text-align: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .premium-dropzone:hover {
            border-color: #764ba2;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: translateY(-2px);
        }

        .premium-dropzone.drag-over {
            border-color: #764ba2;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.15) 0%, rgba(118, 75, 162, 0.15) 100%);
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        .preview-card {
            position: relative;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
        }

        .preview-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .preview-icon {
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #667eea;
        }

        .preview-name {
            margin-top: 8px;
            font-size: 0.85rem;
            color: #495057;
            font-weight: 500;
        }

        .preview-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            border: none;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .preview-remove:hover {
            background: rgba(220, 53, 69, 1);
            transform: scale(1.1);
        }

        .editable-title {
            cursor: text;
            transition: all 0.3s ease;
        }

        .editable-title:hover {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px;
            border-radius: 5px;
        }

        .editable-title:focus {
            outline: 2px solid #667eea;
            background: rgba(255, 255, 255, 0.15);
            padding: 5px;
            border-radius: 5px;
        }
    </style>

    @can('Files Section')
        @php
            $showEditFields =
                ($ghost == 'ghost' && $departmentId == 7) ||
                ($ghost != 'ghost' && $departmentId == $projectDepartmentId);
        @endphp
        @if ($showEditFields && $viewSource != 'website')
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
                    @if ($isImage)
                        <img src="{{ $filePath }}" alt="{{ $file->header_text }}">
                    @elseif($isPdf)
                        <iframe src="{{ $filePath }}#toolbar=0&navpanes=0&scrollbar=0"
                            title="{{ $file->header_text }}"></iframe>
                    @elseif($extension === 'docx')
                        <div class="file-type-icon">
                            <i class="icofont-file-word file-icon"></i>
                            <span>DOCX File</span>
                        </div>
                    @elseif($extension === 'dxf')
                        <div class="file-type-icon">
                            <i class="icofont-file-alt file-icon"></i>
                            <span>DXF File</span>
                        </div>
                    @elseif($extension === 'dwg')
                        <div class="file-type-icon">
                            <i class="icofont-file-image file-icon"></i>
                            <span>DWG File</span>
                        </div>
                    @else
                        <div class="file-type-icon">
                            <i class="icofont-file-document file-icon"></i>
                            <span>{{ strtoupper($extension) }} File</span>
                        </div>
                    @endif
                    @can('File Delete')
                        @if ($viewSource != 'website')
                            <div class="delete-icon"
                                wire:click="$dispatch('deleteConfirmation', {id: {{ $file->id }}})">
                                <i class="icofont-trash"></i>
                            </div>
                        @endif
                    @endcan
                </div>
                <div class="file-info">
                    <div class="file-header {{ $viewSource != 'website' ? 'editable-title' : '' }}" contenteditable="{{ $viewSource == 'website' ? 'true' : 'false' }}" data-file-id="{{ $file->id }}"
                        x-data="{ originalText: '{{ $file->header_text ?? 'Untitled' }}' }"
                        x-on:blur="if($el.textContent.trim() !== originalText) { $wire.updateTitle({{ $file->id }}, $el.textContent.trim()); originalText = $el.textContent.trim(); }">
                        {{ $file->header_text ?? 'Untitled' }}</div>
                    <div class="file-name">
                        <i class="icofont-file-document"></i>
                        <a target="_blank" href="{{ $filePath }}" class="text-white text-decoration-none">
                            {{ Str::limit(preg_replace('/^\d+_/', '', $file->filename), 30) }}
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
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1050;"
            wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered modal-lg" style="pointer-events: auto;">
                <div class="modal-content" style="pointer-events: auto;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">
                            <i class="icofont-upload-alt me-2"></i>Upload Files
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="premium-dropzone" ondrop="handleDrop(event)" ondragover="handleDragOver(event)"
                            ondragleave="handleDragLeave(event)" onclick="document.getElementById('fileInput').click()">
                            <i class="icofont-cloud-upload display-3 text-primary mb-3"></i>
                            <h5 class="fw-bold mb-2">Drop files here or click to browse</h5>
                            <p class="text-muted mb-0">Supports: PDF, JPG, PNG, HEIC, DXF, DOCX, DWG (Max 50MB)</p>
                            <input type="file" id="fileInput" wire:model="files" multiple
                                accept=".pdf,.jpg,.jpeg,.png,.heic,.dxf,.docx,.dwg" style="display: none;">
                        </div>

                        @error('files.*')
                            <div class="alert alert-danger mt-3">{{ $message }}</div>
                        @enderror

                        <div wire:loading wire:target="files" class="text-center mt-3">
                            <i class="icofont-spinner icofont-spin fs-3 text-primary"></i>
                            <p class="text-primary mt-2">Processing files...</p>
                        </div>

                        @if (count($uploadedFiles) > 0)
                            <div class="preview-grid mt-4">
                                @foreach ($uploadedFiles as $index => $file)
                                    <div class="preview-card" wire:key="preview-{{ $index }}">
                                        <button type="button" class="preview-remove"
                                            wire:click="removePreview({{ $index }})">
                                            <i class="icofont-close"></i>
                                        </button>
                                        @if ($file['isImage'])
                                            @if ($file['preview'])
                                                <img src="{{ $file['preview'] }}" alt="Preview"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                    loading="lazy">
                                            @endif
                                            <div class="preview-icon"
                                                @if ($file['preview']) style="display: none;" @endif>
                                                <i class="icofont-image fs-1 text-success"></i>
                                                <span class="d-block mt-2">{{ strtoupper($file['extension']) }}</span>
                                            </div>
                                        @else
                                            <div class="preview-icon">
                                                <i
                                                    class="icofont-file-{{ $file['extension'] === 'pdf' ? 'pdf' : 'document' }} fs-1"></i>
                                                <span class="d-block mt-2">{{ strtoupper($file['extension']) }}</span>
                                            </div>
                                        @endif
                                        <div class="preview-name">{{ Str::limit($file['name'], 25) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                        <button type="button" class="btn btn-save" wire:click="save"
                            @if (count($uploadedFiles) === 0) disabled @endif>
                            <i class="icofont-save me-2"></i>Save Files
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');

            const files = e.dataTransfer.files;
            const fileInput = document.getElementById('fileInput');
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }
    </script>

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

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.add('drag-over');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('drag-over');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            const input = document.getElementById('fileInput');
            input.files = files;
            input.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }
    </script>
@endscript
