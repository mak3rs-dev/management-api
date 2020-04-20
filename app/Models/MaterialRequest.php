<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $table = 'material_requests';

    protected $fillable = [
        'id', 'in_community_id', 'piece_id', 'units_request'
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date) : string
    {
        return $date->format('d-m-Y H:i:s');
    }

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
