<div>
    <style>
        .drop-zone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: background-color 0.2s ease;
        }

        .drop-zone.dragover {
            background-color: #e9f7ff;
        }

        .drop-zone input {
            display: none;
        }

        .drop-zone:hover {
            cursor: pointer;
        }
    </style>
    @can('Files Section')
        @if ($departmentId == $projectDepartmentId)
            <form wire:submit.prevent="save">
                <div class="container mt-5">
                    <div class="drop-zone" id="dropZone" wire:ignore x-data @click="$refs.fileInput.click()"
                        @dragover.prevent="event.target.classList.add('dragover')"
                        @dragleave.prevent="event.target.classList.remove('dragover')"
                        @drop.prevent="handleDrop($event, $refs.fileInput)">
                        <p class="mb-0">Drag & drop your file here, or <span class="text-primary">click to select</span></p>
                        <input type="file" x-ref="fileInput" wire:model="image">
                    </div>
                    <div id="fileName" class="mt-3">
                        @if ($image)
                            <ul>
                                <li>{{ $image->getClientOriginalName() }}</li>
                            </ul>
                        @endif
                    </div>
                </div>
            </form>
        @endif
    @endcan
    <div wire:loading.class="d-flex flex-column" wire:loading class="card-body">
        <div
            class='position-relative w-100 h-100 d-flex flex-column align-items-center bg-white justify-content-center'>
            <div class='spinner-border text-dark' role='status'>
                <span class='visually-hidden'>Loading...</span>
            </div>
        </div>
    </div>
    <div wire:loading.remove>
        <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Files</label>
        <ul class="list-group list-group-custom">
            @foreach ($files as $file)
                <li class="list-group-item light-primary-bg">
                    @can('File Delete')
                        <i class="icofont-trash text-danger fs-6" style="cursor:pointer;"
                            wire:click="deleteConfirmation('{{ $file->id }}')">&nbsp;</i>
                    @endcan
                    <a target="_blank" href="{{ asset('storage/projects/' . $file->filename) }}"
                        class="ml-3">{{ $file->filename }}</a>
                </li>
            @endforeach
        </ul>
    </div>
    <!-- Modal  Delete Folder/ File-->
    <div class="modal fade" id="deletefile" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
            <input type="hidden" id="deleteId" />
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title  fw-bold" id="deleteprojectLabel"> Delete item Permanently?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body justify-content-center flex-column d-flex">
                    <i class="icofont-ui-delete text-danger display-2 text-center mt-2"></i>
                    <p class="mt-4 fs-5 text-center">You can only delete this item Permanently</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger color-fff" wire:click="deleteFile()">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@script
    <script>
        function handleDrop(event, input) {
            const files = event.dataTransfer.files;
            if (files.length) {
                input.files = files;
                input.dispatchEvent(new Event('change', {
                    bubbles: true
                })); // Trigger Livewire change
            }
            event.target.classList.remove('dragover');
        }

        function handleFile(files) {
            if (files && files.length > 0) {
                document.getElementById('fileName').textContent =
                    `Selected files: ${Array.from(files).map(file => file.name).join(', ')}`;
            } else {
                document.getElementById('fileName').textContent = 'No file selected';
            }
        }

        window.addEventListener('show-delete-modal', event => {
            $('#deletefile').modal('show');
        });

        window.addEventListener('hide-delete-modal', event => {
            $('#deletefile').modal('hide');
        });
    </script>
@endscript
