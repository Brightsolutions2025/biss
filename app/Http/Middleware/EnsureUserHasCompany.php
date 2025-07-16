<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = \Auth::user();

        // Check if user has any companies
        if ($user->companies->isEmpty()) {
            return redirect()->route('companies.create')->with('status', 'You must create a company first.');
        }

        // Check if user has selected a company in preferences
        $activeCompanyId = optional($user->preference)->company_id;

        if (!$activeCompanyId) {
            return redirect()->route('preferences.edit')->with('status', 'You must select a company first.');
        }

        return $next($request);
    }
}
