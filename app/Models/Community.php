<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Community extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'community';

    protected $fillable = [
        'uuid', 'alias', 'name', 'description'
    ];
}
