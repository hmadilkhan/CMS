<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\SubDepartment;
use App\Models\Task;
use App\Services\DocumentFollowUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DocumentFollowUpController extends Controller
{
    /** Same set the project Files card accepts. */
    private const FILE_RULES = 'file|max:51200|mimes:pdf,jpg,jpeg,png,heic,dxf,docx,dwg';

    protected $documentFollowUpService;

    public function __construct(DocumentFollowUpService $documentFollowUpService)
    {
        $this->documentFollowUpService = $documentFollowUpService;
    }

    /**
     * What each follow up card collects: a value for the chases that wait on a
     * project field (the meter spot result), or the document itself for the
     * chases that wait on a file (utility bill, fire approval). Producing it
     * closes the follow up and, when the project is parked, sends it on to the
     * chase's release lane.
     */
    public function update(Request $request)
    {
        $type = $request->type;
        $config = $this->documentFollowUpService->config($type);
        $wantsFile = (bool) $config['file_category'];
        $label = $this->documentFollowUpService->label($type);

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => ['required', Rule::in(DocumentFollowUpService::types())],
            'value' => $wantsFile
                ? ['nullable', 'string', 'max:255']
                : ['required', Rule::in($config['value_options'])],
            'files' => [$wantsFile ? 'required' : 'nullable', 'array'],
            'files.*' => self::FILE_RULES,
        ], [
            'files.required' => 'Please upload the document before saving.',
            'value.in' => 'Please choose a valid value.',
        ]);

        if (! $this->documentFollowUpService->visibleTo(auth()->user(), $type)) {
            return response()->json(['status' => 403, 'message' => 'You are not allowed to update this list'], 403);
        }

        $project = Project::findOrFail($request->project_id);

        if (! $this->documentFollowUpService->hasPending($project->id, $type)) {
            return response()->json([
                'status' => 422,
                'message' => 'This project no longer has an open '.$label,
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($wantsFile) {
                $this->storeDocuments($request, $project, $type);
            } else {
                $project->update([$config['value_column'] => $request->value]);
            }

            $project->refresh();

            $wasParked = (int) $project->sub_department_id === $this->documentFollowUpService->parkedSubDepartmentId($type);

            $this->documentFollowUpService->syncType($project, $type, auth()->user());
            $project->refresh();

            $released = $wasParked
                && (int) $project->sub_department_id === $this->documentFollowUpService->releasedSubDepartmentId($type);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => $released
                    ? 'Saved. Follow up cleared and the project moved to '.(SubDepartment::find($project->sub_department_id)->name ?? 'the next lane').'.'
                    : 'Saved. Follow up cleared.',
                'cleared' => true,
                'cleared_at' => now()->format('d M Y, h:i A'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['status' => 500, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Store the uploaded documents the same way the project Files card does -
     * same disk and folder - but filed under the chase's owning department and
     * marked with its category, so they land in their own section instead of
     * the department file list.
     */
    protected function storeDocuments(Request $request, Project $project, string $type): void
    {
        $files = $request->file('files') ?? [];

        if (empty($files)) {
            return;
        }

        $config = $this->documentFollowUpService->config($type);
        $departmentId = $this->documentFollowUpService->ownerDepartmentId($type);
        $taskId = Task::where('project_id', $project->id)
            ->whereIn('status', ['In-Progress', 'Hold', 'Cancelled'])
            ->latest('id')
            ->value('id')
            ?? Task::where('project_id', $project->id)->latest('id')->value('id');

        $username = auth()->user()->name ?? 'System';
        $category = $config['file_category'];
        $what = $type === DocumentFollowUpService::TYPE_FIRE_REVIEW
            ? 'fire approval document'
            : 'utility bill';

        foreach ($files as $file) {
            $originalName = str_replace(' ', '_', $file->getClientOriginalName());
            $storedName = basename($file->storeAs('projects', time().'_'.$originalName, 'public'));

            ProjectFile::create([
                'project_id' => $project->id,
                'task_id' => $taskId,
                'department_id' => $departmentId,
                'filename' => $storedName,
                'header_text' => $file->getClientOriginalName(),
                'category' => $category,
            ]);

            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->setEvent('updated')
                ->withProperties(['files' => $storedName, 'category' => $category])
                ->log("{$username} uploaded the {$what}: {$storedName}.");
        }
    }
}
