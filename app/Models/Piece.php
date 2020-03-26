<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Piece extends Model
{
    protected $table = 'pieces';

    protected $fillable = [
        'uuid', 'community_id', 'picture', 'download_url'
    ];

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }
}
