<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockControl extends Model
{
    protected $table = 'stock_control';

    protected $fillable = [
        'id', 'in_community_id', 'piece_id', 'units_manufactured'
    ];

    public function InCommunity() {
        return $this->belongsTo(InCommunity::class, 'in_community_id');
    }

    public function Piece() {
        return $this->belongsTo(Piece::class, 'piece_id');
    }
}
