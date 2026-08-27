<?php

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\Employee;
use App\Models\ProjectFollowUp;
use App\Models\ServiceTicket;
use App\Models\Task;
use App\Services\DocumentFollowUpService;
use App\Services\ProjectService;
use App\Services\UpcomingAhjService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $projectService;

    protected $upcomingAhjService;

    protected $documentFollowUpService;

    public function __construct(ProjectService $projectService, UpcomingAhjService $upcomingAhjService, DocumentFollowUpService $documentFollowUpService)
    {
        $this->projectService = $projectService;
        $this->upcomingAhjService = $upcomingAhjService;
        $this->documentFollowUpService = $documentFollowUpService;
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

        // Document Follow Up - the MPU paperwork chase, Engineering assignees only
        $showDocumentFollowUp = $this->documentFollowUpService->visibleTo(auth()->user(), DocumentFollowUpService::TYPE_MPU);
        $documentFollowUps = $showDocumentFollowUp
            ? $this->documentFollowUpService->pendingList(DocumentFollowUpService::TYPE_MPU)
            : collect();

        // Utility Bill Follow Up - the Deal Review chase, Deal Review assignees only
        $showUtilityBillFollowUp = $this->documentFollowUpService->visibleTo(auth()->user(), DocumentFollowUpService::TYPE_UTILITY_BILL);
        $utilityBillFollowUps = $showUtilityBillFollowUp
            ? $this->documentFollowUpService->pendingList(DocumentFollowUpService::TYPE_UTILITY_BILL)
            : collect();

        // Fire Review Follow Up - the Permitting chase, Permitting assignees only
        $showFireReviewFollowUp = $this->documentFollowUpService->visibleTo(auth()->user(), DocumentFollowUpService::TYPE_FIRE_REVIEW);
        $fireReviewFollowUps = $showFireReviewFollowUp
            ? $this->documentFollowUpService->pendingList(DocumentFollowUpService::TYPE_FIRE_REVIEW)
            : collect();

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

        // Upcoming AHJ's tab - Permitting department users (and Admin) only
        $showUpcomingAhj = $this->upcomingAhjService->visibleTo(auth()->user());
        $upcomingAhjProjects = $showUpcomingAhj
            ? $this->upcomingAhjService->projectsFor(auth()->user())
            : collect();
        $removedAhjProjects = $showUpcomingAhj
            ? $this->upcomingAhjService->removedProjects()
            : collect();

        return view('dashboard', [
            "projects" => $this->projectService->projectQuery($request),
            "emails" => $emails,
            "followUps" => $followUps,
            "showDocumentFollowUp" => $showDocumentFollowUp,
            "documentFollowUps" => $documentFollowUps,
            "showUtilityBillFollowUp" => $showUtilityBillFollowUp,
            "utilityBillFollowUps" => $utilityBillFollowUps,
            "showFireReviewFollowUp" => $showFireReviewFollowUp,
            "fireReviewFollowUps" => $fireReviewFollowUps,
            "serviceTickets" => $serviceTickets,
            "showUpcomingAhj" => $showUpcomingAhj,
            "upcomingAhjProjects" => $upcomingAhjProjects,
            "removedAhjProjects" => $removedAhjProjects
        ]);
    }
}
