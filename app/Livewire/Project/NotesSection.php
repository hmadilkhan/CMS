<?php

namespace App\Livewire\Project;

use App\Models\DepartmentNote;
use App\Models\Project;
use Livewire\Component;

class NotesSection extends Component
{
    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $departmentNote = "";
    public $projectDepartmentId = "";
    public $ghost = "";

    protected $rules = [
        'departmentNote' => 'required',
    ];

    public function save()
    {
        $this->validate();
        try {            
            DepartmentNote::create([
                "project_id" => $this->projectId,
                "task_id" => $this->taskId,
                "department_id" => $this->departmentId,
                "notes" => $this->departmentNote,
                "user_id" => auth()->user()->id,
            ]);
            $project = Project::findOrFail($this->projectId);
            $username = auth()->user()->name;
            
            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user()) // Log who did the action
                ->setEvent("updated")
                ->withProperties([
                    'notes' => $this->departmentNote,
                ])
                ->log("{$username} added the notes to the project : {$this->departmentNote}.");
    
            $this->departmentNote = "";

        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    public function render()
    {
        $notes = DepartmentNote::where("project_id", $this->projectId)->where("department_id", $this->departmentId)->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        return view('livewire.project.notes-section', compact("notes","departmentId","projectDepartmentId"));
    }
}
