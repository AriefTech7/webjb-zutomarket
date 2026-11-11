<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Buyers and admins share one login session, so every admin route must check the role, not just "logged in".
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin, 403, 'Halaman ini khusus admin.');

        return $next($request);
    }
}
