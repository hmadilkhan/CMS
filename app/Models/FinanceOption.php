<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceOption extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    /**
     * Does this finance option split the contract into a third-party credit and
     * a customer portion?
     *
     * Prepaid PPA and Wheelhouse Credit Union both do, so both collect the pair
     * on the customer form and both show it on the project's Financial Ledger.
     * Seeded ids are matched as well as names, because either can be renamed.
     */
    public function usesCustomerPortion(): bool
    {
        return in_array((int) $this->id, [9, 10], true)
            || strcasecmp(trim((string) $this->name), 'Prepaid PPA') === 0
            || strcasecmp(trim((string) $this->name), 'Wheelhouse Credit Union') === 0;
    }

    public function milestones()
    {
        return $this->hasMany(FinanceOptionMilestone::class)->orderBy('sort_order');
    }
}
