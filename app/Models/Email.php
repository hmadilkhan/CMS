<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function attachments()
    {
        return $this->hasMany(EmailAttachment::class, "email_id", "id");
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id")->withTrashed();
    }

    public function project()
    {
        return $this->belongsTo(Project::class, "project_id", "id")->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, "customer_id", "id")->withTrashed();
    }
}
