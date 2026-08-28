<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectZoneFile;
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
        if ($this->deleteId != "") {
            $projectFile = ($this->fileModel())::findOrFail($this->deleteId);
            Storage::disk('public')->delete('projects/' . $projectFile->filename);
            $projectFile->delete();
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

    public function save()
    {
        if (empty($this->uploadedFiles)) {
            $this->addError('files', 'Please upload at least one file.');
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

    public function updateTitle($fileId, $newTitle)
    {
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

        return view('livewire.project.enhanced-files-section', compact("departmentFiles", "departmentId", "projectDepartmentId"));
    }
}
