<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectControl extends Model
{
    protected $table = 'collect_control';

    protected $fillable = [
        'id', 'in_community_id', 'piece_id', 'status_id', 'units'
    ];

    public function InCommunity() {
        return $this->belongsTo(InCommunity::class, 'in_community_id');
    }

    public function Piece() {
        return $this->belongsTo(Piece::class, 'piece_id');
    }

    public function Status() {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
