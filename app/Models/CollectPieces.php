<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class CollectPieces extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'collect_pieces';

    protected $fillable = [
        'id', 'collect_control_id', 'piece_id', 'units'
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

    public function CollectControl() {
        return $this->belongsTo(CollectControl::class, 'collect_control_id');
    }

    public function Piece() {
        return $this->belongsTo(Piece::class, 'piece_id');
    }
}
