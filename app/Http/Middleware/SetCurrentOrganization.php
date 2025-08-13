<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Get organization from route parameter or session
            $organizationSlug = $request->route('organization');
            
            if ($organizationSlug) {
                // Verify user has access to this organization
                $organization = $user->organizations()
                    ->where('slug', $organizationSlug)
                    ->first();
                
                if (!$organization) {
                    abort(403, 'You do not have access to this organization.');
                }
                
                // Set current organization in session
                session(['current_organization_id' => $organization->id]);
            } else {
                // If no organization in route, ensure we have one in session
                if (!session('current_organization_id')) {
                    $firstOrg = $user->organizations()->first();
                    if ($firstOrg) {
                        session(['current_organization_id' => $firstOrg->id]);
                    }
                }
            }
        }
        
        return $next($request);
    }
}
