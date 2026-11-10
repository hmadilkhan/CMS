<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = [];

    /**
     * Every department list in the CRM follows the pipeline sequence set in
     * Operations -> Departments (`sort_order`, ties broken by id). It is a
     * global scope so lists, dropdowns and eager loads all agree without each
     * query having to remember it.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function (Builder $builder) {
            $builder->orderBy($builder->qualifyColumn('sort_order'))
                ->orderBy($builder->qualifyColumn('id'));
        });
    }

    /**
     * Departments that come after the given one in the sequence (or from it
     * onwards, when inclusive). This replaces `where('id', '>', ...)`, which
     * only worked while the sequence happened to be the id.
     */
    public function scopeAfter(Builder $query, int $departmentId, bool $inclusive = false): Builder
    {
        return $this->scopeRelativeTo($query, $departmentId, $inclusive ? '>=' : '>');
    }

    /**
     * Departments that come before the given one in the sequence.
     */
    public function scopeBefore(Builder $query, int $departmentId): Builder
    {
        return $this->scopeRelativeTo($query, $departmentId, '<');
    }

    private function scopeRelativeTo(Builder $query, int $departmentId, string $operator): Builder
    {
        $anchor = static::withTrashed()->withoutGlobalScope('ordered')->find($departmentId, ['id', 'sort_order']);

        // An id that no longer exists has no position; fall back to the old
        // id comparison rather than returning nothing.
        if (! $anchor) {
            return $query->where($this->qualifyColumn('id'), $operator, $departmentId);
        }

        $strict = rtrim($operator, '=');

        // (sort_order, id) compared as a pair, so equal sort orders still have
        // a definite before/after.
        return $query->where(function (Builder $q) use ($anchor, $operator, $strict) {
            $q->where($this->qualifyColumn('sort_order'), $strict, $anchor->sort_order)
                ->orWhere(function (Builder $q) use ($anchor, $operator) {
                    $q->where($this->qualifyColumn('sort_order'), $anchor->sort_order)
                        ->where($this->qualifyColumn('id'), $operator, $anchor->id);
                });
        });
    }

    /**
     * Where this department sits in the pipeline, as a pair that sorts the same
     * way the global scope orders: `sort_order` first, id breaking ties.
     */
    public function pipelinePosition(): array
    {
        return [(int) $this->sort_order, (int) $this->id];
    }

    /**
     * Whether this department comes earlier in the pipeline than another, which
     * is what "moving the project backwards" means. Compares the positions in
     * PHP rather than querying, so a list already in hand costs nothing - and
     * it replaces `id <` comparisons, which only held while the sequence
     * happened to be the id.
     */
    public function comesBefore(?self $other): bool
    {
        if (! $other) {
            return false;
        }

        return ($this->pipelinePosition() <=> $other->pipelinePosition()) < 0;
    }

    /**
     * The position a newly created department gets: the end of the sequence.
     */
    public static function nextSortOrder(): int
    {
        return (int) static::withTrashed()->withoutGlobalScope('ordered')->max('sort_order') + 1;
    }

    public function logs(): HasMany {
        return $this->hasMany(ProjectCallLog::class,"department_id","id");
    }

    public function subdepartments(): HasMany {
        return $this->hasMany(SubDepartment::class,"department_id","id")->withTrashed();
    }

    public function assignDepartments(): HasMany {
        return $this->hasMany(AssignDepartment::class,"department_id","id");
    }
}
