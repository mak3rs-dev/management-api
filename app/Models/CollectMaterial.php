<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CollectMaterial extends Model
{
    protected $table = 'collect_materials';

    protected $fillable = [
      'id', 'material_requests_id', 'collect_control_id', 'units_delivered'
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

    public function MaterialRequest() {
        return $this->belongsTo(MaterialRequest::class, 'material_requests_id');
    }
}
