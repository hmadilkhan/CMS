<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Traits\MediaTrait;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class FilesSection extends Component
{
    use WithFileUploads, MediaTrait;

    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $files = [];
    public $projectDepartmentId = "";
    public $ghost;
    public $deleteId;

    protected $listeners = ['deleteConfirmation', 'refreshComponent' => '$refresh'];

    protected $rules = [
        'files.*' => 'max:51200' // 50MB max per file
    ];

    public function getListeners()
    {
        return [
            'deleteConfirmation',
            'refreshComponent' => '$refresh'
        ];
    }

    public function mount()
    {
        // Configure temporary file uploads for large files
        config(['livewire.temporary_file_upload.disk' => 'local']);
        config(['livewire.temporary_file_upload.directory' => 'livewire-tmp']);
    }

    public function updatedFiles()
    {
        $this->save();
    }

    #[On('deleteConfirmation')]
    public function deleteConfirmation($id)
    {
        if ($id != "") {
            $this->deleteId = $id;
            $this->dispatch('show-delete-modal');
            // dd($this->deleteId);
        }
    }

    public function deleteFile()
    {
        if ($this->deleteId != "") {
            $project = ProjectFile::findOrFail($this->deleteId);

            // $this->removeImage("projects/",$project->filename);
            Storage::disk('public')->delete('projects/' . $project->filename);
            $project->delete();
            $this->dispatch('hide-delete-modal');

            // Reset any properties if needed
            $this->reset(['files', 'deleteId']);

            // Emit a refresh event
            $this->projectId = $this->projectId;

            $this->dispatch('refreshComponent');
        }
    }

    public function save()
    {
        $this->validate();
        if (count($this->files) > 0) {
            foreach ($this->files as $file) {
                // Get the original filename and sanitize it to avoid issues with spaces
                $originalName = str_replace(' ', '_', $file->getClientOriginalName());

                // Add a timestamp to the filename to ensure uniqueness
                $timestampedName = time() . '_' . $originalName;

                // Store the file in the 'projects' directory within the 'public' disk
                $imageName =  $file->storeAs('projects', $timestampedName, 'public');

                $imageName = basename($imageName);

                ProjectFile::create([
                    "project_id" => $this->projectId,
                    "task_id" => $this->taskId,
                    "department_id" => $this->departmentId,
                    "filename" => $imageName,
                ]);

                $username = auth()->user()->name;
                $project = Project::findOrFail($this->projectId);
                // Get the changed field names
                activity('project')
                    ->performedOn($project)
                    ->causedBy(auth()->user()) // Log who did the action
                    ->setEvent("updated")
                    ->withProperties([
                        'files' => $imageName,
                    ])
                    ->log("{$username} added the file to the project : {$imageName}.");
            }
        }

        $this->files = [];
    }

    public function render()
    {
        $departmentFiles = ProjectFile::where("project_id", $this->projectId)->where("department_id", $this->departmentId)->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        return view('livewire.project.files-section', compact("departmentFiles", "departmentId", "projectDepartmentId"));
    }
}
