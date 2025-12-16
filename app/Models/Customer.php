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
