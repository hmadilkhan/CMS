<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTicket extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'subject', 'assigned_to', 'priority', 'notes', 'status'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(ServiceTicketComment::class, 'service_ticket_id');
    }

    public function files()
    {
        return $this->hasMany(ServiceTicketFile::class, 'service_ticket_id');
    }
}
