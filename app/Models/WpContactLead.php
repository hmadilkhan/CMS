<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WpContactLead extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'wp_vxcf_leads';
    protected $guarded = [];
    public $timestamps = false;
}
