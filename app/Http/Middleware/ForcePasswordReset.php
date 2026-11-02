<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->password_reset_required) {
            // Allow the password-set screens and logout through; redirect everything else.
            $allowed = $request->routeIs('password.set', 'password.set.post', 'logout');

            if (! $allowed) {
                return redirect()->route('password.set')
                    ->with('warning', 'Please set a new password before continuing.');
            }
        }

        return $next($request);
    }
}
