<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! ($user->is_admin ?? false)) {
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Admins only.'], 403);
            }
            abort(403, 'Admins only');
        }
        return $next($request);
    }
}
