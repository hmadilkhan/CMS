<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
                $this->documentFollowUpService->storeDocuments($project, $type, $request->file('files') ?? [], auth()->user());
            } else {
                $project->update([$config['value_column'] => $request->value]);
            }

            $releasedInto = $this->documentFollowUpService->syncAndReport($project, $type, auth()->user());

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => $releasedInto
                    ? 'Saved. Follow up cleared and the project moved to '.$releasedInto.'.'
                    : 'Saved. Follow up cleared.',
                'cleared' => true,
                'cleared_at' => now()->format('d M Y, h:i A'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['status' => 500, 'message' => $th->getMessage()], 500);
        }
    }
}
