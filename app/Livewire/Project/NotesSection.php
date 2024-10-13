<?php

namespace App\Livewire\Project;

use App\Models\DepartmentNote;
use Livewire\Component;

class NotesSection extends Component
{
    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $departmentNote = "";

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
    
            $this->departmentNote = "";

        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    public function render()
    {
        $notes = DepartmentNote::where("project_id", $this->projectId)->get();
        return view('livewire.project.notes-section', compact("notes"));
    }
}
