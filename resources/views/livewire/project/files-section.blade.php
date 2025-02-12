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

        .drop-zone {
            border: 2px dashed #007bff;
            border-radius: 5px;
            padding: 40px;
            text-align: center;
            transition: background-color 0.3s;
            cursor: pointer;
        }

        .drop-zone.dragover {
            background-color: rgba(0, 123, 255, 0.1);
        }
    </style>
    @can('Files Section')
        @if ($departmentId == $projectDepartmentId)
            <form wire:submit.prevent="save">
                <div class="drop-zone" id="dropZone" x-data="{ isDropping: false }" x-on:dragover.prevent="isDropping = true"
                    x-on:dragleave.prevent="isDropping = false"
                    x-on:drop.prevent="
                    isDropping = false;
                    let files = $event.dataTransfer.files;
                    $refs.filesInput.files = files;
                    $refs.filesInput.dispatchEvent(new Event('change'));"
                    x-on:click="$refs.filesInput.click()" class="border-2 border-dashed rounded p-4"
                    :class="{ 'border-blue-500 bg-blue-100': isDropping }">
                    <p class="text-center">Drag and drop files here, or click to select files</p>
                    <input type="file" multiple x-ref="filesInput" wire:model="files" class="hidden" />
                </div>
            </form>
        @endif
    @endcan
    <div wire:loading.class="d-flex flex-column" wire:target="files" wire:loading class="card-body">
        <div
            class='position-relative w-100 h-100 d-flex flex-column align-items-center bg-white justify-content-center'>
            <div class='spinner-border text-dark' role='status'>
                <span class='visually-hidden'>Loading...</span>
            </div>
        </div>
    </div>
    <div wire:loading.remove wire:target="files" class="mt-4">
        <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Files</label>
        <ul class="list-group list-group-custom">
            @if (count($departmentFiles) > 0)
                @foreach ($departmentFiles as $file)
                    <li class="list-group-item light-primary-bg">
                        @can('File Delete')
                            <i class="icofont-trash text-danger fs-6" style="cursor:pointer;"
                                wire:click="$dispatch('deleteConfirmation', {id: {{ $file->id }}})")">&nbsp;</i>
                        @endcan
                        <a target="_blank" href="{{ asset('storage/projects/' . $file->filename) }}"
                            class="ml-3">{{ $file->filename }}</a>
                    </li>
                @endforeach
            @else
                <label class="text-center fw-bold">No Files Found</label>
            @endif
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
                    <button type="button" class="btn btn-danger color-fff"
                        wire:click="deleteFile($('#deleteId').val())">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>
@script
    <script>
        window.addEventListener('show-delete-modal', (e) => {
            $('#deletefile').modal('show');
        });

        window.addEventListener('hide-delete-modal', () => {
            $('#deletefile').modal('hide');
        });
    </script>
@endscript
