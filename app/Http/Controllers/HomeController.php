<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\Employee;
use App\Models\ProjectFollowUp;
use App\Models\ServiceTicket;
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

        if (auth()->user()->hasRole("Technician")) {
            return view('technician-dashboard-wrapper');
        }

        
        if (auth()->user()->hasRole("Super Admin")) {
            return view('executive-dashboard');
        }

        if (auth()->user()->hasRole("Service Manager")) {
            $tickets = ServiceTicket::where("assigned_to", auth()->user()->id)->where('status', '!=', 'Resolved')->orderBy("id", "desc")->get();
            return view('service-tickets.dashboard', [
                "tickets" => $tickets
            ]);
        }
        
        if (!empty(auth()->user()->employee)) {
            $emails = Email::with("project", "customer")->whereIn("project_id", Task::where("employee_id", auth()->user()->employee->id)->where("status", "!=", "Completed")->pluck("project_id"))->where("is_view", 1)->get();
        }

        // Get follow-ups for logged-in employee
        $followUps = [];
        if (!empty(auth()->user()->employee)) {
            $followUps = ProjectFollowUp::with(['project', 'employee'])
                ->where('employee_id', auth()->user()->employee->id)
                ->where('status', '!=', 'Resolved')
                ->orderBy('follow_up_date', 'asc')
                ->get();
        }

        // Get service tickets for logged-in employee
        $serviceTickets = [];
        if (!empty(auth()->user()->id)) {
            $serviceTickets = ServiceTicket::with(['project', 'assignedUser'])
                ->withCount('comments')
                ->where('assigned_to', auth()->user()->id)
                ->where('status', '!=', 'Resolved')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('dashboard', [
            "projects" => $this->projectService->projectQuery($request),
            "emails" => $emails,
            "followUps" => $followUps,
            "serviceTickets" => $serviceTickets
        ]);
    }
}
