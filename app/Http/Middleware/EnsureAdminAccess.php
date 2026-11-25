<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not logged in, or the account was deactivated: end the session.
        if ($user === null || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        // The super admin always passes. Everyone else needs the "admin.access"
        // permission through their role (Members do not have it).
        if (! $user->hasPermission('admin.access')) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
