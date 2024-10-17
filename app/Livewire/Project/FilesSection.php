<?php

namespace App\Livewire\Project;

use App\Models\ProjectFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class FilesSection extends Component
{
    use WithFileUploads;

    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $image;
    public $projectDepartmentId = "";
    public $deleteId;

    protected $rules = [
        'image' => 'required'
    ];

    public function updatedImage()
    {
        $this->save();
    }

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
            $project = ProjectFile::findOrFail($this->deleteId);
            Storage::disk('public')->delete($project->filename);
            $project->delete();
            $this->dispatch('hide-delete-modal');
        }
    }

    public function save()
    {
        $this->validate();
        dd($this->validate());
        if ($this->image != "") {

            // Get the original filename and sanitize it to avoid issues with spaces
            $originalName = str_replace(' ', '_', $this->image->getClientOriginalName());

            // Add a timestamp to the filename to ensure uniqueness
            $timestampedName = now()->format('Ymd_His') . '_' . $originalName;

            // Store the file in the 'projects' directory within the 'public' disk
            $imageName =  $this->image->storeAs('projects', $timestampedName, 'public');
            
            $imageName = basename($imageName);

            ProjectFile::create([
                "project_id" => $this->projectId,
                "task_id" => $this->taskId,
                "department_id" => $this->departmentId,
                "filename" => $imageName,
            ]);
        }

        $this->image = "";
    }

    public function render()
    {
        $files = ProjectFile::where("project_id", $this->projectId)->where("department_id", $this->departmentId)->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        return view('livewire.project.files-section', compact("files", "departmentId", "projectDepartmentId"));
    }
}
