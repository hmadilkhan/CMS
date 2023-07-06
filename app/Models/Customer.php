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
        return $this->belongsTo(ModuleType::class,"module_type_id","id");
    }

    public function inverter()
    {
        return $this->belongsTo(InverterType::class,"inverter_type_id","id");
    }

    public function salespartner()
    {
        return $this->belongsTo(SalesPartner::class,"sales_partner_id","id");
    }

    public function adders()
    {
        return $this->hasMany(CustomerAdder::class,"customer_id","id");
    }
}
