<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;

class CheckUserPrivacyPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            if (auth()->user()->privacy_policy_accepted_at == Carbon::parse('1970-01-01')) {
                return redirect()->route('not-policy');
            }
        }

        return $next($request);
    }
}
