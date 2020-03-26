<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectPieces extends Model
{
    protected $table = 'collect_pieces';

    protected $fillable = [
        'id', 'collect_control_id', 'piece_id', 'units'
    ];

    public function CollectControl() {
        return $this->belongsTo(CollectControl::class, 'collect_control_id');
    }

    public function Piece() {
        return $this->belongsTo(Piece::class, 'piece_id');
    }
}
