<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function attachments()
    {
        return $this->hasMany(EmailAttachment::class, "email_id", "id");
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id");
    }

    public function project()
    {
        return $this->belongsTo(Project::class, "project_id", "id");
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, "customer_id", "id");
    }
}
