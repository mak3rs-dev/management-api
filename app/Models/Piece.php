<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Piece extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pieces';

    protected $fillable = [
        'uuid', 'community_id', 'name', 'picture', 'description', 'is_piece', 'is_material'
    ];

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }

    Public function StockControl() {
        return $this->hasMany(StockControl::class, 'piece_id');
    }

    Public function CollectPieces() {
        return $this->hasMany(CollectPieces::class, 'piece_id');
    }

    public function isPiece() {
        return $this->is_piece == 1;
    }

    public function isMaterial() {
        return $this->is_material == 1;
    }
}
