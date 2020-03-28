<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Piece extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pieces';

    protected $fillable = [
        'uuid', 'community_id', 'name', 'picture', 'download_url', 'description'
    ];

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }
}
