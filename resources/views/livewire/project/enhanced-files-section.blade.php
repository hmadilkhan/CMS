<div>
    <style>
        .file-card {
            background: var(--solen-cream);
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

        .project-file-delete-modal {
            z-index: 100200;
            background: transparent;
        }

        .project-file-delete-modal.show {
            background: rgba(255, 255, 255, 0.01);
            backdrop-filter: blur(8px);
        }

        .file-preview {
            width: 100%;
            height: 250px;
            background: var(--solen-cream);
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
            color: var(--solen-primary);
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
            color: var(--solen-primary-dark);
            text-transform: uppercase;
        }

        .file-info {
            padding: 15px;
            background: var(--solen-gradient);
            color: white;
        }

        .files-header {
            background: var(--solen-gradient);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            box-shadow: 0 14px 32px -22px rgba(151, 76, 18, 0.7);
        }

        .file-header {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            overflow-wrap: anywhere;
            word-break: break-word;
            line-height: 1.35;
        }

        .file-name {
            font-size: 0.85rem;
            opacity: 0.9;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.35;
            min-width: 0;
        }

        .file-name a {
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .upload-btn {
            background: var(--solen-gradient);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(151, 76, 18, 0.35);
            transition: all 0.3s ease;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(151, 76, 18, 0.45);
            color: white;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: var(--solen-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 20px 30px;
        }

        .modal-body {
            padding: 30px;
        }

        .form-control:focus {
            border-color: var(--solen-primary);
            box-shadow: 0 0 0 0.2rem var(--solen-primary-border);
        }

        .btn-save {
            background: var(--solen-gradient);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(151, 76, 18, 0.35);
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
            top: 8px;
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
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
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
            border: 3px dashed var(--solen-primary);
            border-radius: 15px;
            padding: 50px 30px;
            text-align: center;
            background: var(--solen-cream);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .premium-dropzone:hover {
            border-color: var(--solen-primary-dark);
            background: var(--solen-cream-strong);
            transform: translateY(-2px);
        }

        .premium-dropzone.drag-over {
            border-color: var(--solen-primary-dark);
            background: #fed7aa;
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
            background: var(--solen-cream);
        }

        .preview-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--solen-cream);
        }

        .preview-icon {
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--solen-primary);
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
            outline: 2px solid var(--solen-primary);
            background: rgba(255, 255, 255, 0.15);
            padding: 5px;
            border-radius: 5px;
        }

        .premium-dropzone .text-primary,
        [wire\:loading] .text-primary {
            color: var(--solen-primary) !important;
        }

        @media (max-width: 767px) {
            .files-grid,
            .preview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .file-preview {
                height: 140px;
            }

            .file-info {
                padding: 10px;
            }

            .file-header {
                font-size: 0.85rem;
                margin-bottom: 6px;
                line-height: 1.3;
            }

            .file-name {
                font-size: 0.75rem;
                gap: 5px;
                line-height: 1.3;
            }

            .file-preview .file-icon {
                font-size: 48px;
            }

            .file-type-icon {
                gap: 8px;
            }

            .file-type-icon span {
                font-size: 0.78rem;
            }

            .delete-icon {
                top: 6px;
                right: 6px;
                padding: 5px 7px;
                font-size: 0.78rem;
            }

            .preview-card {
                padding: 8px;
            }

            .preview-card img,
            .preview-icon {
                height: 105px;
            }

            .preview-name {
                font-size: 0.72rem;
                line-height: 1.25;
                word-break: break-word;
            }
        }

        @media (max-width: 420px) {
            .files-grid,
            .preview-grid {
                gap: 10px;
            }

            .file-preview {
                height: 118px;
            }

            .preview-card img,
            .preview-icon {
                height: 92px;
            }
        }
    </style>

    @can('Files Section')
        <div class="files-header">
            <i class="icofont-files-stack me-2"></i>Files
        </div>

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
                    <div class="file-header {{ $viewSource != 'website' ? 'editable-title' : '' }}" contenteditable="{{ $viewSource != 'website' ? 'true' : 'false' }}" data-file-id="{{ $file->id }}"
                        x-data="{ originalText: @js($file->header_text ?? 'Untitled') }"
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
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 100200;"
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
                        @php
                            $fileInputId = 'fileInput-' . $this->getId();
                        @endphp
                        <div class="premium-dropzone" ondrop="handleDrop(event)" ondragover="handleDragOver(event)"
                            ondragleave="handleDragLeave(event)"
                            onclick="document.getElementById('{{ $fileInputId }}')?.click()">
                            <i class="icofont-cloud-upload display-3 text-primary mb-3"></i>
                            <h5 class="fw-bold mb-2">Drop files here or click to browse</h5>
                            <p class="text-muted mb-0">Supports: PDF, JPG, PNG, HEIC, DXF, DOCX, DWG (Max 50MB)</p>
                            <input type="file" id="{{ $fileInputId }}" wire:model="files" multiple
                                accept=".pdf,.jpg,.jpeg,.png,.heic,.dxf,.docx,.dwg" style="display: none;"
                                onchange="return validateProjectFiles(event)">
                        </div>

                        <div class="alert alert-danger mt-3 d-none" data-file-upload-error></div>

                        @error('files.*')
                            <div class="alert alert-danger mt-3">{{ $message }}</div>
                        @enderror
                        @error('files')
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
            const fileInput = e.currentTarget.querySelector('input[type="file"]');
            if (!fileInput) {
                return;
            }
            fileInput.files = files;
            if (!validateProjectFiles({
                    target: fileInput
                })) {
                return;
            }
            fileInput.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }

        function validateProjectFiles(e) {
            const input = e.target;
            const files = Array.from(input.files || []);
            const maxSize = 50 * 1024 * 1024;
            const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'heic', 'dxf', 'docx', 'dwg'];
            const errorBox = input.closest('.modal-body')?.querySelector('[data-file-upload-error]');

            const error = files.find(file => {
                const extension = file.name.split('.').pop().toLowerCase();
                return file.size > maxSize || !allowedExtensions.includes(extension);
            });

            if (!error) {
                errorBox?.classList.add('d-none');
                if (errorBox) {
                    errorBox.textContent = '';
                }
                return true;
            }

            if (errorBox) {
                const extension = error.name.split('.').pop().toLowerCase();
                errorBox.textContent = error.size > maxSize ?
                    `${error.name} is larger than 50MB.` :
                    `${extension.toUpperCase()} files are not supported.`;
                errorBox.classList.remove('d-none');
            }

            input.value = '';
            return false;
        }
    </script>

    @php
        $deleteModalId = 'deletefile-' . $this->getId();
    @endphp

    <!-- Delete Modal -->
    <div class="modal fade project-file-delete-modal" id="{{ $deleteModalId }}" tabindex="-1" aria-hidden="true"
        data-bs-backdrop="false" data-backdrop="false" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Delete item Permanently?</h5>
                    <button type="button" class="btn-close close-delete-file-modal"></button>
                </div>
                <div class="modal-body justify-content-center flex-column d-flex">
                    <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                    <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-delete-file-modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteFile">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        (() => {
            const deleteModalId = @js($deleteModalId);
            const deleteModal = $('#' + deleteModalId);

            const showDeleteModal = () => {
                cleanupDeleteModal();
                deleteModal.addClass('show d-block').attr({
                    'aria-modal': 'true',
                    'aria-hidden': 'false'
                });
            };

            const hideDeleteModal = () => {
                deleteModal.removeClass('show d-block').attr({
                    'aria-hidden': 'true'
                }).removeAttr('aria-modal');
                cleanupDeleteModal();
            };

            const cleanupDeleteModal = () => {
                document.body.classList.remove('project-file-delete-modal-open');
                $('.modal-backdrop').remove();

                if (!$('.modal.show').length) {
                    $('body').removeClass('modal-open').css({
                        overflow: '',
                        paddingRight: ''
                    });
                }
            };

            window.addEventListener('show-delete-modal', (event) => {
                if (event.detail?.modalId !== deleteModalId) {
                    return;
                }

                showDeleteModal();
            });

            window.addEventListener('hide-delete-modal', (event) => {
                if (event.detail?.modalId !== deleteModalId) {
                    return;
                }

                hideDeleteModal();
            });

            deleteModal.find('.close-delete-file-modal').off('click.deleteFileModal').on('click.deleteFileModal', () => {
                hideDeleteModal();
            });
        })();

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
            const input = e.currentTarget.querySelector('input[type="file"]');
            if (!input) {
                return;
            }
            input.files = files;
            if (!validateProjectFiles({
                    target: input
                })) {
                return;
            }
            input.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }
    </script>
@endscript
