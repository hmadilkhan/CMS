<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\Task;
use App\Services\ProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }
    public function dashboard(Request $request)
    {
        $emails = [];
        if (!empty(auth()->user()->employee)) {
            $emails = Email::with("project","customer")->whereIn("project_id", Task::where("employee_id", auth()->user()->employee->id)->where("status", "!=", "Completed")->pluck("project_id"))->where("is_view", 1)->get();
        }
        if (auth()->user()->hasRole("Super Admin")) {
            return view('executive-dashboard');
        }
        return view('dashboard', [
            "projects" => $this->projectService->projectQuery($request),
            "emails" => $emails
        ]);
    }
}
