<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectControl extends Model
{
    protected $table = 'collect_control';

    protected $fillable = [
        'id', 'in_community_id', 'community_id', 'status_id', 'address', 'province', 'state', 'country', 'cp'
    ];

    public function InCommunity() {
        return $this->belongsTo(InCommunity::class, 'in_community_id');
    }

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }

    public function Status() {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
