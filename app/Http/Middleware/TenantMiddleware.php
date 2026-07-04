<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // If user belongs to a company, set the tenant_id in the app container
            if ($user->company_id) {
                app()->instance('tenant_id', $user->company_id);
            }
        }

        return $next($request);
    }
}
