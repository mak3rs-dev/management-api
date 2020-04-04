<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Piece extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pieces';

    protected $fillable = [
        'uuid', 'community_id', 'name', 'picture', 'description'
    ];

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }

    Public function StockControl() {
        return $this->hasMany(StockControl::class, 'piece_id');
    }

    Public function CollectControl() {
        return $this->hasMany(CollectControl::class, 'community_id', 'community_id');
    }

    Public function CollectPieces() {
        return $this->hasMany(CollectPieces::class, 'piece_id');
    }
}
