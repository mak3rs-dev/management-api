<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Status extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'status';

    protected $fillable= [
        'uuid', 'code', 'name', 'description'
    ];
}
