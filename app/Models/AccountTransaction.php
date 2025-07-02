<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'milestone',
        'amount',
        'transaction_date',
        'transaction_details',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
