<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\DocumentFollowUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentFollowUpController extends Controller
{
    protected $documentFollowUpService;

    public function __construct(DocumentFollowUpService $documentFollowUpService)
    {
        $this->documentFollowUpService = $documentFollowUpService;
    }

    /**
     * The one editable cell on the Document Follow Up table. Filling it in
     * closes the follow up and, when the project is parked in Install Pending
     * Document, sends it on to Install Not Scheduled.
     */
    public function updateMeterSpotResult(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'meter_spot_result' => 'required|string|max:255',
        ]);

        if (! $this->documentFollowUpService->visibleTo(auth()->user())) {
            return response()->json(['status' => 403, 'message' => 'You are not allowed to update this list'], 403);
        }

        $project = Project::findOrFail($request->project_id);

        if (! $this->documentFollowUpService->hasPending($project->id)) {
            return response()->json(['status' => 422, 'message' => 'This project no longer has an open Document Follow Up'], 422);
        }

        try {
            DB::beginTransaction();

            $project->update(['meter_spot_result' => $request->meter_spot_result]);
            $project->refresh();

            $wasPendingDocument = (int) $project->sub_department_id === DocumentFollowUpService::PENDING_DOCUMENT_SUB_DEPARTMENT_ID;

            $this->documentFollowUpService->sync($project, auth()->user());
            $project->refresh();

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => $wasPendingDocument && (int) $project->sub_department_id === DocumentFollowUpService::NOT_SCHEDULED_SUB_DEPARTMENT_ID
                    ? 'Meter spot result saved. Follow up cleared and the project moved to Install Not Scheduled.'
                    : 'Meter spot result saved. Follow up cleared.',
                'cleared' => true,
                'cleared_at' => now()->format('d M Y, h:i A'),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json(['status' => 500, 'message' => $th->getMessage()], 500);
        }
    }
}
