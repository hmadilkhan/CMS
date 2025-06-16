<?php

namespace App\Livewire\Project;

use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\NotesMention;
use App\Models\Project;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class NotesSection extends Component
{
    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $departmentNote = "";
    public $projectDepartmentId = "";
    public $ghost = "";
    public $employees;

    public function mount()
    {
        $this->employees = Employee::select('id', 'name', 'email')->get();
    }

    protected $rules = [
        'departmentNote' => 'required',
    ];

    public function save()
    {
        $this->validate();
        try {
            // Optional: get mentions from frontend (pass as hidden input or refetch from note)
            $project = Project::with("department")->findOrFail($this->projectId);
            preg_match_all('/@([\w\s]+)/', $this->departmentNote, $matches);
            $mentionedIds = $matches[1];

            $employees = Employee::whereIn('id', $mentionedIds)->get();
            foreach ($employees as $employee) {
                NotesMention::create([
                    "project_id" => $this->projectId,
                    "department_id" =>$this->departmentId,
                    "employee_id" => $employee->id,
                ]);
                Mail::raw("You have been mentioned in a new note in the (".$project->department->name.") department by (".auth()->user()->name.")", function ($message) use ($employee, $project) {
                    $message->to($employee->email)
                        ->subject('New Project Notes Mention - (' . $project->project_name . ') - (' . $project->department->name . ')');
                });
            }

            DepartmentNote::create([
                "project_id" => $this->projectId,
                "task_id" => $this->taskId,
                "department_id" => $this->departmentId,
                "notes" => $this->departmentNote,
                "user_id" => auth()->user()->id,
            ]);

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
            $this->dispatch('refresh');
        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    public function deleteNote($id)
    {
        $note = DepartmentNote::findOrFail($id);
        $note->delete();
        $project = Project::findOrFail($this->projectId);
        $username = auth()->user()->name;
        activity('project')
            ->performedOn($project)
            ->causedBy(auth()->user()) // Log who did the action
            ->setEvent("deleted")
            ->withProperties([
                'notes' => $note->notes,
            ])
            ->log("{$username} deleted the notes from the project : {$note->notes}.");
    }

    public function render()
    {
        $notes = DepartmentNote::where("project_id", $this->projectId)->where("department_id", $this->departmentId)->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        return view('livewire.project.notes-section', compact("notes", "departmentId", "projectDepartmentId"));
    }
}
