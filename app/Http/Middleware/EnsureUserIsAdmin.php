<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        $activeCompanyId = optional($user->preference)->company_id;

        if (!$activeCompanyId) {
            return redirect()->route('preferences.edit')->with('status', 'You must select a company first.');
        }

        // Assuming you have a method like hasRoleInCompany
        if (!$user->hasRoleInCompany('admin', $activeCompanyId)) {
            abort(403, 'You do not have administrative access.');
        }

        return $next($request);
    }
}
