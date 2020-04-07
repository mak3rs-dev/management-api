<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectMaterial extends Model
{
    protected $table = 'collect_materials';

    protected $fillable = [
      'id', 'material_requests_id', 'collect_control_id', 'units_delivered'
    ];
}
