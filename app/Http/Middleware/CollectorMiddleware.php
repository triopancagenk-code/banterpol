<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CollectorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->isCollector()) {
            return $next($request);
        }

        abort(403, 'Akses ditolak. Portal ini khusus untuk Petugas Kolektor Lapangan Banterpool.');
    }
}
