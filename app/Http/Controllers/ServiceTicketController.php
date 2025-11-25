<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketComment;
use App\Models\ServiceTicketFile;
use App\Models\User;
use App\Notifications\ServiceTicketCreated;
use App\Notifications\ServiceTicketCommentAdded;
use App\Notifications\ServiceTicketResolved;
use App\Traits\MediaTrait;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ServiceTicketController extends Controller
{
    use MediaTrait;

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'subject' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:High,Medium,Low',
            'notes' => 'nullable|string',
            'files.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt'
        ]);

        $ticket = ServiceTicket::create($request->all() + ['user_id' => auth()->id()]);
        
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // $fileName = time() . '_' . $file->getClientOriginalName();
                // $filePath = $file->storeAs('service_tickets', $fileName, 'public');
                $result = $this->uploads($file, "tickets/", "");
                $filePath = $result['filePath'];
                
                ServiceTicketFile::create([
                    'service_ticket_id' => $ticket->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->id()
                ]);
            }
        }
        
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

        $oldStatus = $ticket->status;
        $ticket->update($request->only(['notes', 'status']));
        
        if ($oldStatus !== 'Resolved' && $request->status === 'Resolved') {
            $ticket->load(['creator', 'project', 'assignedUser']);
            if ($ticket->creator) {
                Notification::send($ticket->creator, new ServiceTicketResolved($ticket));
            }
        }
        
        return back()->with('success', 'Ticket updated successfully');
    }

    public function dashboard()
    {
        $user = auth()->user();
        $tickets = ServiceTicket::with(['project', 'assignedUser', 'creator'])
            ->withCount('comments')
            ->where('assigned_to', $user->id)
            ->where('status', '!=', 'Resolved')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('service-tickets.dashboard', compact('tickets'));
    }

    public function adminDashboard()
    {
        $tickets = ServiceTicket::with(['project', 'assignedUser', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('service-tickets.admin-dashboard', compact('tickets'));
    }

    public function addComment(Request $request, ServiceTicket $ticket)
    {
        $request->validate([
            'comment' => 'required|string',
            'files.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt'
        ]);

        $comment = ServiceTicketComment::create([
            'service_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('service_tickets', $fileName, 'public');
                
                ServiceTicketFile::create([
                    'service_ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->id()
                ]);
            }
        }

        
        $ticket->load('creator');
        // && $ticket->creator->id !== auth()->id()
        if ($ticket->creator) {
            Notification::send($ticket->creator, new ServiceTicketCommentAdded($ticket, $comment));
        }

        return back()->with('success', 'Comment added successfully');
    }

    public function showDetails(ServiceTicket $ticket)
    {
        $ticket->load(['comments.user', 'comments.files', 'project', 'files' => function($q) {
            $q->whereNull('comment_id');
        }, 'files.uploader']);
        return view('service-tickets.details', compact('ticket'));
    }

    public function showAdminDetails(ServiceTicket $ticket)
    {
        $ticket->load(['comments.user', 'comments.files', 'project', 'assignedUser', 'files' => function($q) {
            $q->whereNull('comment_id');
        }, 'files.uploader']);
        return view('service-tickets.admin-details', compact('ticket'));
    }

    public function deleteFile(ServiceTicketFile $file)
    {
        Storage::disk('public')->delete($file->file_path);
        $file->delete();
        return back()->with('success', 'File deleted successfully');
    }
}
