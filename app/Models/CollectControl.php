<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class CollectControl extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'collect_control';

    protected $fillable = [
        'id', 'in_community_id', 'status_id', 'address', 'province', 'state', 'country', 'cp', 'address_description'
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

    public function CollectPieces() {
        return $this->hasMany(CollectPieces::class, 'collect_control_id');
    }

    public function CollectMaterial() {
        return $this->hasMany(CollectMaterial::class, 'collect_control_id');
    }

    // To rename CollectPiece
    public function Pieces() {
        return $this->CollectPieces();
    }

    public function hasStatus(string $str) {
        $status = $this->Status;
        $aStr = explode('|', $str);
        $count = 0;
        foreach ($aStr as $s) {
            $count += (($status == null) ? 0 : $status->code == $s) ? 1 : 0;
        }

        return $count > 0;
    }
}
