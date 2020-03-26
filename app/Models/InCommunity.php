<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InCommunity extends Model
{
    protected $table = 'in_community';

    protected $fillable = [
        'id', 'user_id', 'community_id', 'role_id'
    ];

    public function Role() {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function User() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }
}