<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * For now, we exclude the calendar events endpoint so the JS
     * fetch() call can POST without being blocked by a 419 error.
     * Later, once auth is wired up, we can lock this back down.
     */
    protected $except = [
        'calendar/events',
    ];
}
