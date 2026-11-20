<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Buyers and admins share one login session, so every admin route must check the role, not just "logged in".
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->is_admin, 403, 'Halaman ini khusus admin.');
        // Presence for the live chat ("Admin Online"): written at most once a minute, without touching updated_at.
        if (! $user->last_seen_at || $user->last_seen_at->lt(now()->subMinute())) {
            DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
