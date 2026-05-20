<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectEmployeeOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Org owners always see the regular dashboard (even if also member elsewhere).
        if ($user->isOrganizationOwner()) {
            return $next($request);
        }

        // External collaborators (invited via project_members) → /collaborateur
        if ($user->isCollaborator()) {
            return redirect()->route('collaborator.dashboard');
        }

        // HR employees with a portal account but no business settings → /mon-espace-rh
        if ($user->isEmployee() && !$user->businessSettings) {
            return redirect()->route('employee-portal.dashboard');
        }

        return $next($request);
    }
}
