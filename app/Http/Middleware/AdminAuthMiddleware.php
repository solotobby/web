<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('admin_authenticated', false)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized executive access.'], 401);
            }

            return redirect()->guest(route('admin.login'))
                ->with('warning', 'Executive access required. Please enter your master passcode.');
        }

        return $next($request);
    }
}
