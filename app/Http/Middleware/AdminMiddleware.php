<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log all requests hitting admin middleware
        file_put_contents(storage_path('logs/admin-middleware.log'), 
            "[" . date('Y-m-d H:i:s') . "] Admin middleware hit: " . $request->fullUrl() . 
            " | Method: " . $request->method() . 
            " | Auth check: " . (auth()->check() ? 'PASS' : 'FAIL') . 
            " | User ID: " . (auth()->id() ?? 'NULL') . 
            " | Is Admin: " . (auth()->check() && auth()->user()->is_admin ? 'YES' : 'NO') . "\n", 
            FILE_APPEND | LOCK_EX
        );
        
        if (!auth()->check() || !auth()->user()->is_admin) {
            file_put_contents(storage_path('logs/admin-middleware.log'), 
                "[" . date('Y-m-d H:i:s') . "] ADMIN ACCESS DENIED for: " . $request->fullUrl() . "\n", 
                FILE_APPEND | LOCK_EX
            );
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        file_put_contents(storage_path('logs/admin-middleware.log'), 
            "[" . date('Y-m-d H:i:s') . "] ADMIN ACCESS GRANTED - proceeding to controller\n", 
            FILE_APPEND | LOCK_EX
        );

        return $next($request);
    }
}
