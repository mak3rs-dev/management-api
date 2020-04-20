<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Community extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'community';

    protected $fillable = [
        'uuid', 'alias', 'name', 'picture', 'description', 'telegram_data'
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date) : string
    {
        return $date->format('d-m-Y H:i:s');
    }

    public function Pieces() {
        return $this->hasMany(Piece::class, 'community_id');
    }

    public function InCommunities() {
        return $this->hasMany(InCommunity::class, 'community_id');
    }

    public function InCommunitiesUser() {
        return $this->InCommunities()->where('user_id', auth()->user()->id)->first();
    }
}
