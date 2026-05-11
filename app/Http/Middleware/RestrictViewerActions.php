<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictViewerActions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'viewer') {
            if (!$request->isMethod('get') && !$request->is('logout')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Viewers are not allowed to perform this action.'], 403);
                }
                return back()->withErrors(['error' => 'Viewers are not allowed to perform this action.']);
            }
        }

        return $next($request);
    }
}
