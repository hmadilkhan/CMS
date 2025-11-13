<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EnhancedFilesSection extends Component
{
    use WithFileUploads;

    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $projectDepartmentId = "";
    public $ghost;
    public $deleteId;
    
    public $showModal = false;
    public $files = [];
    public $uploadedFiles = [];
    
    protected $rules = [
        'files.*' => 'required|file|max:51200|mimes:pdf,jpg,jpeg,png,heic,dxf,docx,dwg'
    ];

    #[On('deleteConfirmation')]
    public function deleteConfirmation($id)
    {
        if ($id != "") {
            $this->deleteId = $id;
            $this->dispatch('show-delete-modal');
        }
    }

    public function deleteFile()
    {
        if ($this->deleteId != "") {
            $projectFile = ProjectFile::findOrFail($this->deleteId);
            Storage::disk('public')->delete('projects/' . $projectFile->filename);
            $projectFile->delete();
            $this->dispatch('hide-delete-modal');
            $this->reset(['deleteId']);
            $this->dispatch('refreshComponent');
        }
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['files', 'uploadedFiles']);
        $this->resetValidation();
    }

    public function updatedFiles()
    {
        $this->validate();
        foreach ($this->files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'heic']);
            
            $this->uploadedFiles[] = [
                'file' => $file,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'extension' => $ext,
                'isImage' => $isImage,
                'preview' => $isImage ? $file->temporaryUrl() : null
            ];
        }
        $this->reset('files');
    }

    public function removePreview($index)
    {
        unset($this->uploadedFiles[$index]);
        $this->uploadedFiles = array_values($this->uploadedFiles);
    }

    public function save()
    {
        if (empty($this->uploadedFiles)) {
            $this->addError('files', 'Please upload at least one file.');
            return;
        }

        $username = auth()->user()->name;
        $project = Project::findOrFail($this->projectId);

        foreach ($this->uploadedFiles as $uploadedFile) {
            $originalName = str_replace(' ', '_', $uploadedFile['file']->getClientOriginalName());
            $timestampedName = time() . '_' . $originalName;
            $imageName = $uploadedFile['file']->storeAs('projects', $timestampedName, 'public');
            $imageName = basename($imageName);

            ProjectFile::create([
                "project_id" => $this->projectId,
                "task_id" => $this->taskId,
                "department_id" => $this->departmentId,
                "filename" => $imageName,
                "header_text" => 'Untitled',
            ]);

            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->setEvent("updated")
                ->withProperties(['files' => $imageName])
                ->log("{$username} added the file to the project: {$imageName}.");
        }

        $this->closeModal();
    }

    public function updateTitle($fileId, $newTitle)
    {
        $file = ProjectFile::findOrFail($fileId);
        $file->update(['header_text' => $newTitle]);
    }

    public function render()
    {
        $departmentFiles = ProjectFile::where("project_id", $this->projectId)
            ->where("department_id", $this->departmentId)
            ->orderBy('created_at', 'desc')
            ->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        
        return view('livewire.project.enhanced-files-section', compact("departmentFiles", "departmentId", "projectDepartmentId"));
    }
}
