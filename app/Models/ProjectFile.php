<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectFile extends Model
{
    use HasFactory,SoftDeletes;

    /** The bill collected by the Utility Bill Follow Up. */
    public const CATEGORY_UTILITY_BILL = 'utility_bill';

    protected $guarded = [];

    /** Files that belong to a named group, e.g. the utility bills. */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * The department's ordinary file list - everything that is not filed under
     * a named group of its own.
     */
    public function scopeUngrouped($query)
    {
        return $query->whereNull('category');
    }
}
