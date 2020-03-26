<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Piece extends Model
{
    protected $table = 'pieces';

    protected $fillable = [
        'uuid', 'community_id', 'name', 'picture', 'download_url', 'description'
    ];

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }
}
