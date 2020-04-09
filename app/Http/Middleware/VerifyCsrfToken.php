<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [

    ];

    public function __construct()
    {
        $this->except = [
            ''.env('TELEGRAM_BOT_TOKEN').'/webhook'
        ];
        parent::__construct();
    }
}
