<?php

namespace App\Livewire\Project;

use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\NotesMention;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NoteMentionedNotification;
use Illuminate\Support\Facades\Log;

class NotesSection extends Component
{
    public $editingNoteId = null;
    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $departmentNote = "";
    public $projectDepartmentId = "";
    public $ghost = "";
    public $employees;
    public $showToCustomer = 0;

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        $loggedInUser = auth()->user();
        $this->showToCustomer = 0;
        $this->employees = Employee::select('id', 'name', 'email')
            ->where(function($query) use ($loggedInUser) {
                $query->whereHas('user.roles', function($q) {
                    $q->whereIn('name', ['Manager', 'Sub-Contractor Manager', 'Employee', 'Super Admin']);
                })
                ->when($loggedInUser && $loggedInUser->sales_partner_id, function($q) use ($loggedInUser) {
                    $q->orWhereHas('user', function($userQuery) use ($loggedInUser) {
                        $userQuery->where('sales_partner_id', $loggedInUser->sales_partner_id);
                    });
                });
            })
            ->get();
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
            preg_match_all('/@(\d+):([^@\s]+)/', $this->departmentNote, $matches);
            $mentionedIds = $matches[1];

            // Create clean note text with only names (no IDs)
            $cleanNote = $this->departmentNote;
            foreach ($matches[0] as $index => $fullMatch) {
                $employeeName = $matches[2][$index];
                $cleanNote = str_replace($fullMatch, "@{$employeeName}", $cleanNote);
            }

            $employees = Employee::with("user")->whereIn('id', $mentionedIds)->get();
            foreach ($employees as $employee) {
                NotesMention::create([
                    "project_id" => $this->projectId,
                    "department_id" => $this->departmentId,
                    "employee_id" => $employee->id,
                ]);
                $message = "You have been mentioned in an updated note in the department (" . $project->department->name . ") added by (" . auth()->user()->name . ")";
                // Send notification (below mail code)
                $user = User::find($employee->user->id);
                if ($user) {
                    try {
                        Notification::send($user, new NoteMentionedNotification($project, $cleanNote, auth()->user()));
                    } catch (\Exception $e) {
                        Log::error('Notification send failed: ' . $e->getMessage());
                    }
                }
                // $employee->user->notify(new NoteMentionedNotification($project, $cleanNote, auth()->user()));
                // Notification::send($employee->user, new NoteMentionedNotification($project, $cleanNote, auth()->user()));
                if ($employee->user && $employee->user->email_preference == 1) {
                    Mail::raw($message, function ($message) use ($employee, $project) {
                        $message->to($employee->email)
                            ->subject('New Project Notes Mention - (' . $project->project_name . ') - (' . $project->department->name . ')');
                    });
                }
            }

            DepartmentNote::create([
                "project_id" => $this->projectId,
                "task_id" => $this->taskId,
                "department_id" => $this->departmentId,
                "notes" => $cleanNote,
                "user_id" => auth()->user()->id,
                "show_to_customer" => $this->showToCustomer,
            ]);

            $username = auth()->user()->name;

            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->setEvent("updated")
                ->withProperties([
                    'notes' => $cleanNote,
                ])
                ->log("{$username} added the notes to the project : {$cleanNote}.");

            $this->departmentNote = "";
            $this->reset('showToCustomer');
            $this->dispatch('refresh');
        } catch (\Throwable $th) {
            //throw $th;
            // dd($th->getMessage());
            Log::error('Error saving note: ' . $th->getMessage());
        }
    }

    public function editNote($id)
    {
        $note = DepartmentNote::findOrFail($id);
        $this->editingNoteId = $id;
        $this->departmentNote = $note->notes;
        $this->showToCustomer = $note->show_to_customer;
        $this->projectId = $note->project_id;
        $this->taskId = $note->task_id;
        $this->departmentId = $note->department_id;
    }

    public function updateNote()
    {
        $this->validate();
        try {
            $note = DepartmentNote::findOrFail($this->editingNoteId);
            $oldNote = $note->notes;

            // Get mentions from the updated note
            $project = Project::with("department")->findOrFail($this->projectId);
            preg_match_all('/@(\d+):([^@\s]+)/', $this->departmentNote, $matches);
            $mentionedIds = $matches[1];

            // Create clean note text with only names (no IDs)
            $cleanNote = $this->departmentNote;
            foreach ($matches[0] as $index => $fullMatch) {
                $employeeName = $matches[2][$index];
                $cleanNote = str_replace($fullMatch, "@{$employeeName}", $cleanNote);
            }

            // Send emails to mentioned employees
            $employees = Employee::with("user")->whereIn('id', $mentionedIds)->get();
            foreach ($employees as $employee) {
                NotesMention::create([
                    "project_id" => $this->projectId,
                    "department_id" => $this->departmentId,
                    "employee_id" => $employee->id,
                ]);
                $message = "You have been mentioned in an updated note in the department (" . $project->department->name . ") added by (" . auth()->user()->name . ")";
                // Send notification (below mail code)
                try {
                    Notification::send($employee->user, new NoteMentionedNotification($project, $cleanNote, auth()->user()));
                } catch (\Exception $e) {
                    Log::error('Notification send failed in updateNote: ' . $e->getMessage());
                }
                if ($employee->user && $employee->user->email_preference == 1) {
                    Mail::raw($message, function ($message) use ($employee, $project) {
                        $message->to($employee->email)
                            ->subject('Updated Project Notes Mention - (' . $project->project_name . ') - (' . $project->department->name . ')');
                    });
                }
            }

            // Update the note with clean text (only names)
            $note->update([
                "notes" => $cleanNote,
                "show_to_customer" => $this->showToCustomer,
            ]);

            $username = auth()->user()->name;

            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->setEvent("updated")
                ->withProperties([
                    'old_notes' => $oldNote,
                    'new_notes' => $cleanNote,
                ])
                ->log("{$username} updated the notes from '{$oldNote}' to '{$cleanNote}'.");

            // Reset editing state
            $this->editingNoteId = null;
            $this->departmentNote = "";
            $this->reset('showToCustomer');
            $this->dispatch('refresh');
        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    public function cancelEdit()
    {
        $this->editingNoteId = null;
        $this->departmentNote = "";
        $this->reset('showToCustomer');
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
        $notes = DepartmentNote::where("project_id", $this->projectId)->where("department_id", $this->departmentId)->orderBy('id',"DESC")->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        return view('livewire.project.notes-section', compact("notes", "departmentId", "projectDepartmentId"));
    }
}
