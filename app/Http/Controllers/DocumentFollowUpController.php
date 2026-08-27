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

    /** The only two values the Meter Spot Result dropdown offers. */
    private const METER_SPOT_RESULTS = ['same', 'relocation'];

    protected $documentFollowUpService;

    public function __construct(DocumentFollowUpService $documentFollowUpService)
    {
        $this->documentFollowUpService = $documentFollowUpService;
    }

    /**
     * What each follow up card collects: the meter spot result for the MPU
     * chase, the utility bill file itself for the Deal Review one. Producing it
     * closes the follow up and, when the project is parked, sends it on to the
     * chase's release lane.
     */
    public function update(Request $request)
    {
        $type = $request->type;
        $isUtilityBill = $type === DocumentFollowUpService::TYPE_UTILITY_BILL;

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => ['required', Rule::in(DocumentFollowUpService::types())],
            'value' => $isUtilityBill
                ? ['nullable', 'string', 'max:255']
                : ['required', Rule::in(self::METER_SPOT_RESULTS)],
            'files' => [$isUtilityBill ? 'required' : 'nullable', 'array'],
            'files.*' => self::FILE_RULES,
        ], [
            'files.required' => 'Please upload the utility bill before saving.',
            'value.in' => 'Please choose a valid Meter Spot Result.',
        ]);

        if (! $this->documentFollowUpService->visibleTo(auth()->user(), $type)) {
            return response()->json(['status' => 403, 'message' => 'You are not allowed to update this list'], 403);
        }

        $project = Project::findOrFail($request->project_id);

        if (! $this->documentFollowUpService->hasPending($project->id, $type)) {
            return response()->json([
                'status' => 422,
                'message' => 'This project no longer has an open '.$this->documentFollowUpService->label($type),
            ], 422);
        }

        try {
            DB::beginTransaction();

            if ($isUtilityBill) {
                $this->storeUtilityBills($request, $project);
            } else {
                $project->update(['meter_spot_result' => $request->value]);
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
     * Store the uploaded bills the same way the project Files card does - same
     * disk and folder - but filed under Deal Review and marked as utility bills
     * so they land in their own section instead of the department file list.
     */
    protected function storeUtilityBills(Request $request, Project $project): void
    {
        $files = $request->file('files') ?? [];

        if (empty($files)) {
            return;
        }

        $departmentId = $this->documentFollowUpService->ownerDepartmentId(DocumentFollowUpService::TYPE_UTILITY_BILL);
        $taskId = Task::where('project_id', $project->id)
            ->whereIn('status', ['In-Progress', 'Hold', 'Cancelled'])
            ->latest('id')
            ->value('id')
            ?? Task::where('project_id', $project->id)->latest('id')->value('id');

        $username = auth()->user()->name ?? 'System';

        foreach ($files as $file) {
            $originalName = str_replace(' ', '_', $file->getClientOriginalName());
            $storedName = basename($file->storeAs('projects', time().'_'.$originalName, 'public'));

            ProjectFile::create([
                'project_id' => $project->id,
                'task_id' => $taskId,
                'department_id' => $departmentId,
                'filename' => $storedName,
                'header_text' => $file->getClientOriginalName(),
                'category' => ProjectFile::CATEGORY_UTILITY_BILL,
            ]);

            activity('project')
                ->performedOn($project)
                ->causedBy(auth()->user())
                ->setEvent('updated')
                ->withProperties(['files' => $storedName, 'category' => ProjectFile::CATEGORY_UTILITY_BILL])
                ->log("{$username} uploaded the utility bill: {$storedName}.");
        }
    }
}
