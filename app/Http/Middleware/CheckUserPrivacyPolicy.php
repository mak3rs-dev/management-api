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
            if (Carbon::parse(auth()->user()->privacy_policy_accepted_at) < Carbon::parse(env('POLICY_AT'))) {
                return redirect()->route('not-policy');
            }
        }

        return $next($request);
    }
}
