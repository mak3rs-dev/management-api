<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class StockControl extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'stock_control';

    protected $fillable = [
        'id', 'in_community_id', 'piece_id', 'units_manufactured', 'validated_at'
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

    public function InCommunity() {
        return $this->belongsTo(InCommunity::class, 'in_community_id');
    }

    public function Piece() {
        return $this->belongsTo(Piece::class, 'piece_id');
    }
}
