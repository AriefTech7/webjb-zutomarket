<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Store-wide settings (categories, banners, admins, sales, DP rules, ...) are for the super admin only.
 * Other admins manage just their own game accounts.
 */
class EnsureUserIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->is_admin && $request->user()->is_super_admin, 403, 'Halaman ini khusus super admin.');

        return $next($request);
    }
}
