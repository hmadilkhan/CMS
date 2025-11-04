<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id', 'date', 'start_time', 'end_time', 'start_location_address',
        'start_lat', 'start_lng', 'current_lat', 'current_lng', 'is_available'
    ];

    protected $casts = [
        'date' => 'date',
        'is_available' => 'boolean',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function surveys()
    {
        return $this->hasMany(SiteSurvey::class, 'technician_id', 'technician_id')
            ->whereDate('survey_date', $this->date);
    }
}
