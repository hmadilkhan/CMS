<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'technician_id', 'survey_date', 'start_time', 'end_time',
        'customer_address', 'customer_lat', 'customer_lng', 'estimated_travel_time',
        'estimated_distance', 'status', 'actual_start_time', 'actual_end_time',
        'actual_lat', 'actual_lng', 'notes'
    ];

    protected $casts = [
        'survey_date' => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
