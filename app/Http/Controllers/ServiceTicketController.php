<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceTicket;
use App\Models\User;
use App\Notifications\ServiceTicketCreated;
use Illuminate\Support\Facades\Notification;

class ServiceTicketController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'subject' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:High,Medium,Low',
            'notes' => 'nullable|string'
        ]);

        $ticket = ServiceTicket::create($request->all());
        
        if ($request->assigned_to) {
            $assignedUser = User::find($request->assigned_to);
            if ($assignedUser) {
                Notification::send($assignedUser, (new ServiceTicketCreated($ticket))->delay(now()->addSeconds(5)));
            }
        }
        
        return back()->with('success', 'Ticket created successfully');
    }

    public function update(Request $request, ServiceTicket $ticket)
    {
        $request->validate([
            'notes' => 'nullable|string',
            'status' => 'required|in:Pending,Resolved'
        ]);

        $ticket->update($request->only(['notes', 'status']));
        return back()->with('success', 'Ticket updated successfully');
    }

    public function dashboard()
    {
        $user = auth()->user();
        $tickets = ServiceTicket::with(['project', 'assignedUser'])
            ->withCount('comments')
            ->where('assigned_to', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('service-tickets.dashboard', compact('tickets'));
    }

    public function adminDashboard()
    {
        $tickets = ServiceTicket::with(['project', 'assignedUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('service-tickets.admin-dashboard', compact('tickets'));
    }

    public function addComment(Request $request, ServiceTicket $ticket)
    {
        $request->validate([
            'comment' => 'required|string'
        ]);

        \App\Models\ServiceTicketComment::create([
            'service_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment
        ]);

        return back()->with('success', 'Comment added successfully');
    }

    public function showDetails(ServiceTicket $ticket)
    {
        $ticket->load(['comments.user', 'project']);
        return view('service-tickets.details', compact('ticket'));
    }

    public function showAdminDetails(ServiceTicket $ticket)
    {
        $ticket->load(['comments.user', 'project', 'assignedUser']);
        return view('service-tickets.admin-details', compact('ticket'));
    }
}
