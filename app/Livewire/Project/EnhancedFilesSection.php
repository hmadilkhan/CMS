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
    public $headerText = "";
    public $file;
    
    protected $rules = [
        'headerText' => 'required|string|max:255',
        'file' => 'required|file|max:51200'
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
        $this->reset(['headerText', 'file']);
        $this->resetValidation();
    }

    public function save($addMore = false)
    {
        $this->validate();

        $originalName = str_replace(' ', '_', $this->file->getClientOriginalName());
        $timestampedName = time() . '_' . $originalName;
        $imageName = $this->file->storeAs('projects', $timestampedName, 'public');
        $imageName = basename($imageName);

        ProjectFile::create([
            "project_id" => $this->projectId,
            "task_id" => $this->taskId,
            "department_id" => $this->departmentId,
            "filename" => $imageName,
            "header_text" => $this->headerText,
        ]);

        $username = auth()->user()->name;
        $project = Project::findOrFail($this->projectId);
        activity('project')
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->setEvent("updated")
            ->withProperties(['files' => $imageName, 'header' => $this->headerText])
            ->log("{$username} added the file to the project: {$imageName}.");

        if ($addMore) {
            $this->reset(['headerText', 'file']);
            $this->resetValidation();
        } else {
            $this->closeModal();
        }
    }

    public function saveAndAddMore()
    {
        $this->save(true);
    }

    public function render()
    {
        $departmentFiles = ProjectFile::where("project_id", $this->projectId)
            ->where("department_id", $this->departmentId)
            ->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        
        return view('livewire.project.enhanced-files-section', compact("departmentFiles", "departmentId", "projectDepartmentId"));
    }
}
