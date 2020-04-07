<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $table = 'material_requests';

    protected $fillable = [
        'id', 'in_community_id', 'piece_id', 'units_request'
    ];
}
