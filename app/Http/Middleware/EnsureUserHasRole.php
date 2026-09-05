<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response|RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user || ! in_array($user->role, $roles, true), 403);

        if ($user->isVendor() && ($user->isApplicationPending() || $user->isApplicationRejected())) {
            return redirect()->route('vendor.status');
        }

        abort_if(! $user->is_active, 403, 'تم إيقاف حسابك.');

        return $next($request);
    }
}
