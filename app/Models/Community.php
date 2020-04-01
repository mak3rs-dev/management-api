<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Community extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'community';

    protected $fillable = [
        'uuid', 'alias', 'name', 'picture', 'description'
    ];

    public function Pieces() {
        return $this->hasMany(Piece::class, 'community_id');
    }

    public function InCommunities() {
        return $this->hasMany(InCommunity::class, 'community_id');
    }

    public function InCommunitiesUser() {
        return $this->hasMany(InCommunity::class, 'community_id')->where('user_id', auth()->user()->id);
    }
}
