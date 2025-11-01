<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTicketComment extends Model
{
    use HasFactory;

    protected $fillable = ['service_ticket_id', 'user_id', 'comment'];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
