<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTicketFile extends Model
{
    use HasFactory;

    protected $fillable = ['service_ticket_id', 'comment_id', 'file_name', 'file_path', 'file_type', 'file_size', 'uploaded_by'];

    public function ticket()
    {
        return $this->belongsTo(ServiceTicket::class, 'service_ticket_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function comment()
    {
        return $this->belongsTo(ServiceTicketComment::class, 'comment_id');
    }
}
