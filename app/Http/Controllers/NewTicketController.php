<?php

namespace App\Http\Controllers;

use App\Models\NewTicket as ModelsNewTicket;
use App\Notifications\NewTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class NewTicketController extends Controller
{
    public function index()
    {
        return view("tickets.index",[
            "tickets" => ModelsNewTicket::all(),
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            "form_name" => "required",
            "form_email" => "required",
            "form_subject" => "required",
            "form_phone" => "required",
            "form_phone" => "required",
        ]);
        try {
            $newTicket = ModelsNewTicket::create([
                "name" => $request->form_name,
                "email" => $request->form_email,
                "subject" => $request->form_subject,
                "phone" => $request->form_phone,
                "message" => $request->form_message,
            ]);
            // $this->sendNotification($newTicket);
            return response()->json(["status" => true,"message" => "Your information has been submitted. You will be contacted shortly."]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $ticket = ModelsNewTicket::findOrFail($request->id);
            $ticket->status = "Done";
            $ticket->save();
            return response()->json(["status" => 200, "message" => "Status changes successfully."]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => "Error. " . $th->getMessage()]);
        }
    }

    public function sendNotification(ModelsNewTicket $newTicket)
    {
        Notification::sendNow($newTicket, new NewTicket());
    }
}
