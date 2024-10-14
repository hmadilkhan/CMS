<?php

namespace App\Livewire\Project;

use App\Models\ProjectFile;
use Livewire\Component;
use Livewire\WithFileUploads;

class FilesSection extends Component
{
    use WithFileUploads;

    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $images = [];
    public $projectDepartmentId = "";

    protected $rules = [
        'image' => 'required'
    ];

    public function updatedImages()
    {
        $this->save();
    }

    public function save()
    {
        $this->validate();

        if (!empty($this->image)) {
            foreach ($this->images as $key => $file) {
                $imageName = $file->store('projects/', 'public');
                ProjectFile::create([
                    "project_id" => $this->projectId,
                    "task_id" => $this->taskId,
                    "department_id" => $this->departmentId,
                    "filename" => $imageName,
                ]);
            }
        }

        $this->images = [];
    }

    public function render()
    {
        $files = ProjectFile::where("project_id", $this->projectId)->where("department_id", $this->departmentId)->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        return view('livewire.project.files-section', compact("files", "departmentId", "projectDepartmentId"));
    }
}
