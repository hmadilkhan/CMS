<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeployLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'run_by',
        'output',
        'status',
    ];

    public $timestamps = false; // we use created_at only
}
