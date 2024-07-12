<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WpContactLeadDetail extends Model
{
    protected $connection = 'wordpress';
    protected $table = 'wp_vxcf_leads_detail';
}
