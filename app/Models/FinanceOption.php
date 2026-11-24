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
     * Set per option by the "TPO Credit Split" dropdown in Operations → Finance
     * Options (`uses_tpo_split`). An option that does collects Solar + Battery
     * TPO Credit on the customer form and shows the pair on the project's
     * Financial Ledger. Prepaid PPA and Wheelhouse Credit Union were switched
     * on by the migration that added the column.
     */
    public function usesCustomerPortion(): bool
    {
        return (bool) $this->uses_tpo_split;
    }

    public function milestones()
    {
        return $this->hasMany(FinanceOptionMilestone::class)->orderBy('sort_order');
    }
}
