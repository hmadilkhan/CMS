<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    protected $with = ['finances','module','inverter','salespartner','adders'];

    public function finances()
    {
        return $this->belongsTo(CustomerFinance::class,"id","customer_id");
    }

    public function module()
    {
        return $this->belongsTo(ModuleType::class,"module_type_id","id")->withTrashed();
    }

    public function inverter()
    {
        return $this->belongsTo(InverterType::class,"inverter_type_id","id")->withTrashed();
    }

    // public function salespartner()
    // {
    //     return $this->belongsTo(User::class,"sales_partner_id","id");
    // }

    public function salespartner()
    {
        return $this->belongsTo(SalesPartner::class,"sales_partner_id","id")->withTrashed();
    }

    public function subContractor()
    {
        return $this->belongsTo(SubContractor::class,"sub_contractor_id","id")->withTrashed();
    }

    public function project()
    {
        return $this->belongsTo(Project::class,"id","customer_id");
    }

    public function adders()
    {
        return $this->hasMany(CustomerAdder::class,"customer_id","id");
    }

    public function getRoleName()
    {
        return auth()->user()->getRoleNames()[0];
    }

    /**
     * Is this one customer inside what the user is already shown?
     *
     * Mirrors scopeGetCustomers: only Sales Person and Sub-Contractor User have a
     * narrowed customer list, so only they are narrowed here — a gate must not be
     * stricter than the list it guards. A user holding several roles gets the
     * widest of them, since the list itself reads only the first role.
     */
    public static function accessibleBy(User $user, int $customerId): bool
    {
        $roles = $user->getRoleNames();
        $narrowed = ["Sales Person", "Sub-Contractor User"];

        if ($roles->intersect($narrowed)->count() !== $roles->count() || $roles->isEmpty()) {
            return static::where("id", $customerId)->exists();
        }

        return static::where("id", $customerId)
            ->where(function ($query) use ($roles, $user) {
                if ($roles->contains("Sales Person")) {
                    $query->orWhere("sales_partner_id", $user->sales_partner_id);
                }

                if ($roles->contains("Sub-Contractor User")) {
                    $query->orWhere("sub_contractor_id", $user->sales_partner_id);
                }
            })
            ->exists();
    }

    public function scopeGetCustomers($query) 
    {
        if ($this->getRoleName() == "Sales Person") {
            return $query->where("sales_partner_id",auth()->user()->sales_partner_id);
        }
        if ($this->getRoleName() == "Sub-Contractor User") {
            return $query->where("sub_contractor_id",auth()->user()->sales_partner_id);
        }
    }

    public function scopeGetCustomersBySalesUser($query)
    {
        if ($this->getRoleName() == "Sales Person") {
            return $query->whereHas('project', function($q) {
                $q->where('sales_partner_user_id', auth()->user()->id);
            });
        }
        if ($this->getRoleName() == "Sales Manager") {
            return $query->where("sales_partner_id",auth()->user()->sales_partner_id);
        }
    }
}
