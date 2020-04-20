<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class InCommunity extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'in_community';

    protected $fillable = [
        'id', 'user_id', 'community_id', 'role_id', 'mak3r_num', 'disabled_at', 'blockuser_at'
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

    public function Role() {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function User() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Community() {
        return $this->belongsTo(Community::class, 'community_id');
    }

    public function StockControl() {
        return $this->hasMany(StockControl::class, 'in_community_id');
    }

    public function CollectControl() {
        return $this->hasMany(CollectControl::class, 'in_community_id');
    }

    public function MaterialsRequest() {
        return $this->hasMany(MaterialRequest::class, 'in_community_id');
    }

    public function hasRole(string $str) {
        $role = $this->Role;
        $aStr = explode('|', $str);
        $count = 0;
        foreach ($aStr as $s) {
            $count += (($role == null) ? 0 : trim($role->name) == $s) ? 1 : 0;
        }

        return $count > 0;
    }

    public function isDisabledUser() {
        return $this->disabled_at != null;
    }

    public function isBlockUser() {
        return $this->blockuser_at != null;
    }
}
