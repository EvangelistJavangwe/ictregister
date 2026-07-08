<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->mfa_enabled && !session('mfa_verified')) {
            if (!$request->is('mfa*') && !$request->is('logout')) {
                return redirect()->route('mfa.verify');
            }
        }

        return $next($request);
    }
}
