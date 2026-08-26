<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\UpcomingAhjService;
use Illuminate\Http\Request;

class UpcomingAhjController extends Controller
{
    protected $upcomingAhjService;

    public function __construct(UpcomingAhjService $upcomingAhjService)
    {
        $this->upcomingAhjService = $upcomingAhjService;
    }

    /** Mark As Remove - drop the project off the Upcoming AHJ's list. */
    public function remove(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id']);

        if (! $this->upcomingAhjService->visibleTo(auth()->user())) {
            return response()->json(['status' => 403, 'message' => 'You are not allowed to update this list'], 403);
        }

        try {
            $project = Project::findOrFail($request->project_id);
            $track = $this->upcomingAhjService->markRemoved($project, auth()->user());

            return response()->json([
                'status' => 200,
                'message' => 'Removed from the list',
                'removed_at' => $track->removed_at->format('d M Y, h:i A'),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => $th->getMessage()], 500);
        }
    }

    /** Undo a manual removal. */
    public function restore(Request $request)
    {
        $request->validate(['project_id' => 'required|exists:projects,id']);

        if (! $this->upcomingAhjService->visibleTo(auth()->user())) {
            return response()->json(['status' => 403, 'message' => 'You are not allowed to update this list'], 403);
        }

        try {
            $project = Project::findOrFail($request->project_id);

            if (! $this->upcomingAhjService->restore($project)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'This project left the list on its own and cannot be restored',
                ], 422);
            }

            return response()->json(['status' => 200, 'message' => 'Restored to the list']);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => $th->getMessage()], 500);
        }
    }
}
