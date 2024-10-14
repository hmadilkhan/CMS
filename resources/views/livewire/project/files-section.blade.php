{{-- <div>
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

        .drop-zone :hover {
            cursor: pointer;
        }
    </style>
    @if ($departmentId == $projectDepartmentId)
        <form wire:submit.prevent="save">
            <div class="container mt-5">
                <div class="drop-zone" id="dropZone">
                    <p class="mb-0">Drag & drop your file here, or <span class="text-primary">click to select</span></p>
                    <input type="file" id="fileInput" wire:model="images" multiple>
                </div>
                <div id="fileName" class="mt-3"></div>
            </div>
        </form>
    @endif
    <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Files</label>
    <ul class="list-group list-group-custom">
        @foreach ($files as $file)
            <li class="list-group-item light-primary-bg">
                @can('File Delete')
                    <i class="icofont-trash text-danger fs-6" style="cursor:pointer;"
                        onclick="deleteFile('{{ $file->id }}')">&nbsp;</i>
                @endcan
                <a target="_blank" href="{{ asset('storage/projects/' . $file->filename) }}"
                    class="ml-3">{{ $file->filename }}</a>
            </li>
        @endforeach
    </ul>
</div>
@script
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileName');

        dropZone.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', () => {
            handleFile(fileInput.files[0]);
        });

        dropZone.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (event) => {
            event.preventDefault();
            dropZone.classList.remove('dragover');
            const files = event.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                handleFile(files[0]);
            }
        });

        function handleFile(file) {
            if (file) {
                fileNameDisplay.textContent = `Selected file: ${file.name}`;
            } else {
                fileNameDisplay.textContent = 'No file selected';
            }
        }
    </script>
@endscript --}}
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

    @if ($departmentId == $projectDepartmentId)
        <form wire:submit.prevent="save">
            <div class="container mt-5">
                <div class="drop-zone" 
                     id="dropZone" 
                     wire:ignore 
                     x-data 
                     @click="$refs.fileInput.click()" 
                     @dragover.prevent="event.target.classList.add('dragover')" 
                     @dragleave.prevent="event.target.classList.remove('dragover')" 
                     @drop.prevent="handleDrop($event, $refs.fileInput)">
                    <p class="mb-0">Drag & drop your file here, or <span class="text-primary">click to select</span></p>
                    <input type="file" 
                           x-ref="fileInput" 
                           wire:model="images" 
                           multiple 
                           @change="handleFile($event.target.files)">
                </div>
                <div id="fileName" class="mt-3">
                    @if ($images)
                        <ul>
                            @foreach ($images as $image)
                                <li>{{ $image->getClientOriginalName() }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </form>
    @endif

    <label for="formFileMultipleoneone" class="form-label fw-bold flex-fill mb-2 mt-sm-0">Files</label>
    <ul class="list-group list-group-custom">
        @foreach ($files as $file)
            <li class="list-group-item light-primary-bg">
                @can('File Delete')
                    <i class="icofont-trash text-danger fs-6" style="cursor:pointer;"
                        wire:click="deleteFile('{{ $file->id }}')">&nbsp;</i>
                @endcan
                <a target="_blank" href="{{ asset('storage/projects/' . $file->filename) }}" class="ml-3">{{ $file->filename }}</a>
            </li>
        @endforeach
    </ul>
</div>

<script>
    function handleDrop(event, input) {
        const files = event.dataTransfer.files;
        if (files.length) {
            input.files = files;
            input.dispatchEvent(new Event('change', { bubbles: true })); // Trigger Livewire change
        }
        event.target.classList.remove('dragover');
    }

    function handleFile(files) {
        if (files && files.length > 0) {
            document.getElementById('fileName').textContent = `Selected files: ${Array.from(files).map(file => file.name).join(', ')}`;
        } else {
            document.getElementById('fileName').textContent = 'No file selected';
        }
    }
</script>

