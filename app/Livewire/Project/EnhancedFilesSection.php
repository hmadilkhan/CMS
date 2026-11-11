<?php

namespace App\Livewire\Project;

use App\Livewire\Concerns\AuthorizesProjectAccess;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectZoneFile;
use App\Services\DocumentFollowUpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EnhancedFilesSection extends Component
{
    use AuthorizesProjectAccess;

    use WithFileUploads;

    #[Locked]
    public $projectId = "";
    public $taskId = "";
    public $departmentId = "";
    public $projectDepartmentId = "";
    public $ghost;
    public $deleteId;
    #[Locked]
    public $viewSource = "";

    /**
     * Which slice of the project's files this instance shows. NULL is the
     * department's ordinary file list; a category name (e.g. "utility_bill")
     * makes it that group's own section, rendered identically.
     */
    public $category = null;
    public $sectionTitle = "Files";
    public $sectionIcon = "icofont-files-stack";
    public $allowUpload = true;

    /**
     * Zone mode. When $zoneId is set this instance belongs to a zone tab: it
     * reads and writes project_zone_files instead of project_files, and it
     * accepts uploads only in the project's CURRENT zone ($projectZoneId) -
     * every other zone tab is a read-only list. Zone files are kept out of
     * project_files on purpose - a department file list, the customer page and
     * the follow-up chases all read that table.
     */
    public $zoneId = null;
    public $projectZoneId = null;

    /** Shown after an upload here cleared the section's follow up. */
    public $followUpMessage = null;

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
            $this->dispatch('show-delete-modal', modalId: 'deletefile-' . $this->getId());
        }
    }

    /** True while this instance is a zone tab's file list. */
    protected function inZoneMode(): bool
    {
        return !empty($this->zoneId);
    }

    /** The model this instance reads and writes. */
    protected function fileModel(): string
    {
        return $this->inZoneMode() ? ProjectZoneFile::class : ProjectFile::class;
    }

    public function deleteFile()
    {
        $this->abortIfPublicWebsiteView();

        if ($this->deleteId != "") {
            $projectFile = ($this->fileModel())::findOrFail($this->deleteId);
            Storage::disk('public')->delete('projects/' . $projectFile->filename);
            $projectFile->delete();

            // A chase's document has just left the project. The field the files
            // answer ("Utility Bill Uploaded") has to follow them back, and the
            // chase re-opens with it - see docs/follow-ups.md.
            if (!$this->inZoneMode() && $projectFile->category) {
                $project = Project::find($projectFile->project_id);

                if ($project) {
                    app(DocumentFollowUpService::class)->sync($project, auth()->user());
                }
            }

            $this->dispatch('hide-delete-modal', modalId: 'deletefile-' . $this->getId());
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
        $this->uploadedFiles = [];

        foreach ($this->files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'heic']);
            $isPreviewable = in_array($ext, ['jpg', 'jpeg', 'png']);
            $preview = null;

            if ($isPreviewable) {
                try {
                    $preview = $file->temporaryUrl();
                } catch (\Throwable $e) {
                    $preview = null;
                }
            }

            $this->uploadedFiles[] = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'extension' => $ext,
                'isImage' => $isImage,
                'preview' => $preview
            ];
        }
    }

    public function removePreview($index)
    {
        unset($this->uploadedFiles[$index]);
        unset($this->files[$index]);

        $this->uploadedFiles = array_values($this->uploadedFiles);
        $this->files = array_values($this->files);
    }

    /**
     * The chase this section collects documents for (utility bill, fire
     * review), or null for an ordinary department / zone file list.
     */
    protected function followUpType(): ?string
    {
        if ($this->inZoneMode()) {
            return null;
        }

        return app(DocumentFollowUpService::class)->typeForFileCategory($this->category);
    }

    /**
     * A chase's own section takes uploads while that chase is open on the
     * project - from whichever department tab it sits under, and for anyone who
     * may work on the project, not only the owning department's assignees who
     * see the dashboard card. Uploading here clears the chase exactly as the
     * card does. Once the chase is closed the section is a read-only list again.
     */
    protected function canClearFollowUp(): bool
    {
        $type = $this->followUpType();

        return $type !== null
            && ! $this->onPublicWebsiteView()
            && app(DocumentFollowUpService::class)->hasPending((int) $this->projectId, $type);
    }

    public function save()
    {
        $this->abortIfPublicWebsiteView();

        if (empty($this->uploadedFiles)) {
            $this->addError('files', 'Please upload at least one file.');
            return;
        }

        if ($this->followUpType()) {
            $this->saveFollowUpDocuments();

            return;
        }

        $username = auth()->user()->name;
        $project = Project::findOrFail($this->projectId);

        foreach ($this->files as $file) {
            $originalName = str_replace(' ', '_', $file->getClientOriginalName());
            $timestampedName = time() . '_' . $originalName;
            $imageName = $file->storeAs('projects', $timestampedName, 'public');
            $imageName = basename($imageName);

            ($this->fileModel())::create($this->inZoneMode() ? [
                "project_id" => $this->projectId,
                "zone_id" => $this->zoneId,
                "user_id" => auth()->id(),
                "filename" => $imageName,
                "header_text" => 'Untitled',
            ] : [
                "project_id" => $this->projectId,
                "task_id" => $this->taskId,
                "department_id" => $this->departmentId,
                "filename" => $imageName,
                "header_text" => 'Untitled',
                "category" => $this->category,
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

    /**
     * Upload into a chase's section: the same storage and the same clearing the
     * dashboard card does (DocumentFollowUpService), so the chase closes, a
     * parked project is released and its assignee e-mailed.
     */
    protected function saveFollowUpDocuments(): void
    {
        $service = app(DocumentFollowUpService::class);
        $type = $this->followUpType();

        if (! $this->canClearFollowUp()) {
            $this->addError('files', 'This project no longer has an open '.$service->label($type).'.');

            return;
        }

        $project = Project::findOrFail($this->projectId);

        try {
            $releasedInto = DB::transaction(function () use ($service, $project, $type) {
                $service->storeDocuments($project, $type, $this->files, auth()->user());

                return $service->syncAndReport($project, $type, auth()->user());
            });
        } catch (\Throwable $e) {
            report($e);
            $this->addError('files', 'The document could not be saved. Please try again.');

            return;
        }

        $this->closeModal();

        $this->followUpMessage = $releasedInto
            ? $service->label($type).' cleared. The project moved to '.$releasedInto.'.'
            : $service->label($type).' cleared.';

        // The project changed lane: the lane badge and the move bar on the page
        // are now stale, so reload it.
        if ($releasedInto) {
            $this->dispatch('document-follow-up-released');
        }
    }

    public function updateTitle($fileId, $newTitle)
    {
        $this->abortIfPublicWebsiteView();

        $file = ($this->fileModel())::findOrFail($fileId);
        $file->update(['header_text' => $newTitle]);
    }

    public function render()
    {
        if ($this->inZoneMode()) {
            return view('livewire.project.enhanced-files-section', [
                'departmentFiles' => ProjectZoneFile::where("project_id", $this->projectId)
                    ->where("zone_id", $this->zoneId)
                    ->orderBy('created_at', 'desc')
                    ->get(),
                'departmentId' => $this->departmentId,
                'projectDepartmentId' => $this->projectDepartmentId,
            ]);
        }

        // A category section (e.g. utility bills) shows that group across the
        // project; the plain section shows the department's ungrouped files.
        $departmentFiles = ProjectFile::where("project_id", $this->projectId)
            ->when(
                $this->category,
                fn ($query) => $query->category($this->category),
                fn ($query) => $query->where("department_id", $this->departmentId)->ungrouped()
            )
            ->orderBy('created_at', 'desc')
            ->get();
        $departmentId = $this->departmentId;
        $projectDepartmentId = $this->projectDepartmentId;
        $canClearFollowUp = $this->canClearFollowUp();
        $followUpLabel = $canClearFollowUp
            ? app(DocumentFollowUpService::class)->label($this->followUpType())
            : null;

        return view('livewire.project.enhanced-files-section', compact("departmentFiles", "departmentId", "projectDepartmentId", "canClearFollowUp", "followUpLabel"));
    }
}
