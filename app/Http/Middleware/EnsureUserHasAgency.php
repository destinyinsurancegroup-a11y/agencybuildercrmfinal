<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAgency
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Safety check: if a logged-in user somehow has no agency_id,
        // immediately log them out to avoid any cross-tenant leakage.
        if ($user && empty($user->agency_id)) {
            auth()->logout();

            return redirect('/login')->withErrors([
                'agency' => 'Your account is not associated with an agency.',
            ]);
        }

        return $next($request);
    }
}
