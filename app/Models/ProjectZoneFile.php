<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A file uploaded inside a zone tab. Stored on the same disk/folder as the
 * department files (`projects/`), but in its own table so it never shows up in
 * a department's file list or in a follow-up chase's document section.
 */
class ProjectZoneFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
