<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsAllowed
{
    /**
     * Redirect any authentication attempt that isn't the allowed email to home.
     *
     * The allowed address is read from the `auth.allowed_email` config value
     * (backed by the AUTH_ALLOWED_EMAIL env var). If it is not configured, every
     * attempt is redirected home so the app fails closed rather than open.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('login.store', 'register.store')) {
            return $next($request);
        }

        $allowedEmail = Str::lower(trim((string) config('auth.allowed_email')));
        $submittedEmail = Str::lower(trim((string) $request->input('email')));

        if ($allowedEmail === '' || $submittedEmail !== $allowedEmail) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
