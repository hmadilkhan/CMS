<?php

namespace App\Http\Controllers;

use App\Models\NewTicket as ModelsNewTicket;
use App\Notifications\NewTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class NewTicketController extends Controller
{
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
            return response()->json(["status" => true]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function sendNotification(ModelsNewTicket $newTicket)
    {
        Notification::sendNow($newTicket, new NewTicket());
    }
}
