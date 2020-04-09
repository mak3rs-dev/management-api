<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $table = 'material_requests';

    protected $fillable = [
        'id', 'in_community_id', 'piece_id', 'units_request'
    ];

    public function InCommunity() {
        return $this->belongsTo(InCommunity::class, 'in_community_id');
    }

    public function Piece() {
        return $this->belongsTo(Piece::class, 'piece_id');
    }

    public function CollectMaterials() {
        return $this->hasMany(CollectMaterial::class, 'material_requests_id');
    }
}
