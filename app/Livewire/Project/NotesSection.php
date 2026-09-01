<?php

namespace App\Livewire\Project;

use App\Jobs\SendRawEmailJob;
use App\Livewire\Concerns\AuthorizesProjectAccess;
use App\Models\DepartmentNote;
use App\Models\Employee;
use App\Models\NotesMention;
use App\Models\Project;
use App\Models\ProjectZoneNote;
use App\Models\User;
use App\Models\Zone;
use App\Notifications\NoteMentionedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Locked;
use Livewire\Component;

class NotesSection extends Component
{
    use AuthorizesProjectAccess;

    public $editingNoteId = null;
    #[Locked]
    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $departmentNote = "";
    public $projectDepartmentId = "";
    public $ghost = "";
    public $employees;
    #[Locked]
    public $viewSource = "";
    public $showToCustomer = 0;

    /**
     * Zone mode. When $zoneId is set this instance belongs to a zone tab, not a
     * department tab: it reads and writes project_zone_notes instead of
     * department_notes, and it is editable only in the project's CURRENT zone
     * ($projectZoneId) - every other zone tab is read-only history. Everything
     * else - the @mention list, the notifications, the activity log - is
     * identical.
     */
    public $zoneId = null;
    public $projectZoneId = null;
    public $sectionTitle = "Department Notes";

    protected $listeners = ['refresh' => '$refresh'];

    /** @var array<int|string, array{sales_partner_id: int|null, sales_partner_user_id: int|null}> */
    protected static $salesPartnerCache = [];

    public function mount()
    {
        $this->showToCustomer = 0;
        $salesPartner = $this->projectSalesPartnerContext();

        // Built from users (not employees): a sales partner user does not always
        // have an employee record, and without one they could never be mentioned.
        $this->employees = User::select('id', 'name', 'email')
            ->with('roles:id,name')
            ->where(function ($query) use ($salesPartner) {
                $query->where(function ($staff) {
                    $staff->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['Manager', 'Sub-Contractor Manager', 'Employee', 'Super Admin']);
                    })
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('employees')
                            ->whereColumn('employees.user_id', 'users.id')
                            ->whereNull('employees.deleted_at');
                    });
                });

                // Everyone working for the project's sales partner. Note that
                // users.sales_partner_id doubles as the sub-contractor id for
                // sub-contractor users, so those are excluded here.
                if (!empty($salesPartner['sales_partner_id'])) {
                    $query->orWhere(function ($partner) use ($salesPartner) {
                        $partner->where('sales_partner_id', $salesPartner['sales_partner_id'])
                            ->where(function ($type) {
                                $type->whereNull('user_type_id')
                                    ->orWhere('user_type_id', '!=', 4);
                            });
                    });
                }

                // The sales partner user assigned to this project.
                if (!empty($salesPartner['sales_partner_user_id'])) {
                    $query->orWhere('id', $salesPartner['sales_partner_user_id']);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->pluck('name')->implode(', '),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Sales partner attached to this project: the partner company (via its
     * customer) and the partner user assigned to the project itself. Both feed
     * the @mention list.
     *
     * @return array{sales_partner_id: int|null, sales_partner_user_id: int|null}
     */
    protected function projectSalesPartnerContext(): array
    {
        if (empty($this->projectId)) {
            return ['sales_partner_id' => null, 'sales_partner_user_id' => null];
        }

        // One component instance is mounted per department tab, so memoise the
        // lookup for the request instead of re-querying it for every tab.
        if (!array_key_exists($this->projectId, static::$salesPartnerCache)) {
            $project = Project::select('id', 'customer_id', 'sales_partner_user_id')
                ->with('customer:id,sales_partner_id')
                ->find($this->projectId);

            static::$salesPartnerCache[$this->projectId] = [
                'sales_partner_id' => $project?->customer?->sales_partner_id,
                'sales_partner_user_id' => $project?->sales_partner_user_id,
            ];
        }

        return static::$salesPartnerCache[$this->projectId];
    }

    /**
     * Users referenced as @<user id>:<name> in the note being saved.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function mentionedUsers(array $mentionedIds)
    {
        $mentionedIds = array_values(array_unique(array_filter($mentionedIds)));

        if (empty($mentionedIds)) {
            return collect();
        }

        return User::whereIn('id', $mentionedIds)->get();
    }

    /**
     * Notify one mentioned user and log the mention. Sales partner users have
     * no employee record, so employee_id stays null for them.
     */
    protected function recordMention(User $user, Project $project, string $cleanNote, string $subjectPrefix): void
    {
        // A zone note still records the project's department, so every existing
        // mention query keeps working; zone_id says it came from a zone tab.
        NotesMention::create([
            "project_id" => $this->projectId,
            "department_id" => $this->departmentId ?: $project->department_id,
            "zone_id" => $this->zoneId,
            "employee_id" => Employee::where('user_id', $user->id)->value('id'),
            "user_id" => $user->id,
        ]);

        try {
            Notification::send($user, new NoteMentionedNotification($project, $cleanNote, auth()->user()));
        } catch (\Exception $e) {
            Log::error('Notification send failed: ' . $e->getMessage());
        }

        if ($user->email_preference == 1 && !empty($user->email)) {
            $context = $this->inZoneMode()
                ? 'zone (' . optional(Zone::find($this->zoneId))->name . ')'
                : 'department (' . $project->department->name . ')';
            $label = $this->inZoneMode()
                ? optional(Zone::find($this->zoneId))->name
                : $project->department->name;
            $message = "You have been mentioned in an updated note in the " . $context . " added by (" . auth()->user()->name . ")";
            SendRawEmailJob::dispatch(
                $user->email,
                $subjectPrefix . ' - (' . $project->project_name . ') - (' . $label . ')',
                $message
            )->afterCommit();
        }
    }

    protected $rules = [
        'departmentNote' => 'required',
    ];

    /** True while this instance is a zone tab's notes rather than a department's. */
    protected function inZoneMode(): bool
    {
        return !empty($this->zoneId);
    }

    /** The model this instance reads and writes. */
    protected function noteModel(): string
    {
        return $this->inZoneMode() ? ProjectZoneNote::class : DepartmentNote::class;
    }

    /** The attributes a new note is created with, per mode. */
    protected function noteAttributes(string $cleanNote): array
    {
        if ($this->inZoneMode()) {
            return [
                "project_id" => $this->projectId,
                "zone_id" => $this->zoneId,
                "notes" => $cleanNote,
                "user_id" => auth()->user()->id,
            ];
        }

        return [
            "project_id" => $this->projectId,
            "task_id" => $this->taskId,
            "department_id" => $this->departmentId,
            "notes" => $cleanNote,
            "user_id" => auth()->user()->id,
            "show_to_customer" => $this->showToCustomer,
        ];
    }

    public function save()
    {
        $this->abortIfPublicWebsiteView();

        $this->validate();
        try {
            // Optional: get mentions from frontend (pass as hidden input or refetch from note)
            $project = Project::with("department")->findOrFail($this->projectId);
            preg_match_all('/@(\d+):([^@\s]+)/', $this->departmentNote, $matches);
            $mentionedIds = $matches[1];

            // Create clean note text with only names (no IDs)
            $cleanNote = $this->departmentNote;
            foreach ($matches[0] as $index => $fullMatch) {
                $mentionedName = $matches[2][$index];
                $cleanNote = str_replace($fullMatch, "@{$mentionedName}", $cleanNote);
            }

            foreach ($this->mentionedUsers($mentionedIds) as $user) {
                $this->recordMention($user, $project, $cleanNote, 'New Project Notes Mention');
            }

            ($this->noteModel())::create($this->noteAttributes($cleanNote));

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
        $note = ($this->noteModel())::findOrFail($id);
        $this->editingNoteId = $id;
        $this->departmentNote = $note->notes;
        $this->projectId = $note->project_id;

        if ($this->inZoneMode()) {
            $this->zoneId = $note->zone_id;

            return;
        }

        $this->showToCustomer = $note->show_to_customer;
        $this->taskId = $note->task_id;
        $this->departmentId = $note->department_id;
    }

    public function updateNote()
    {
        $this->abortIfPublicWebsiteView();

        $this->validate();
        try {
            $note = ($this->noteModel())::findOrFail($this->editingNoteId);
            $oldNote = $note->notes;

            // Get mentions from the updated note
            $project = Project::with("department")->findOrFail($this->projectId);
            preg_match_all('/@(\d+):([^@\s]+)/', $this->departmentNote, $matches);
            $mentionedIds = $matches[1];

            // Create clean note text with only names (no IDs)
            $cleanNote = $this->departmentNote;
            foreach ($matches[0] as $index => $fullMatch) {
                $mentionedName = $matches[2][$index];
                $cleanNote = str_replace($fullMatch, "@{$mentionedName}", $cleanNote);
            }

            // Send emails to mentioned users
            foreach ($this->mentionedUsers($mentionedIds) as $user) {
                $this->recordMention($user, $project, $cleanNote, 'Updated Project Notes Mention');
            }

            // Update the note with clean text (only names)
            $note->update($this->inZoneMode()
                ? ["notes" => $cleanNote]
                : ["notes" => $cleanNote, "show_to_customer" => $this->showToCustomer]);

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
            report($th);
            // dd($th->getMessage());
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
        $this->abortIfPublicWebsiteView();

        $note = ($this->noteModel())::findOrFail($id);
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
        $notes = $this->inZoneMode()
            ? ProjectZoneNote::with('user')->where("project_id", $this->projectId)->where("zone_id", $this->zoneId)->orderBy('id', "DESC")->get()
            : DepartmentNote::where("project_id", $this->projectId)->where("department_id", $this->departmentId)->orderBy('id', "DESC")->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        return view('livewire.project.notes-section', compact("notes", "departmentId", "projectDepartmentId"));
    }
}
