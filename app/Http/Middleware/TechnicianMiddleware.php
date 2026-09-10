<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TechnicianMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan masuk dengan akun Teknisi Lapangan atau Administrator.');
        }

        if (! Auth::user()->isTechnician()) {
            abort(403, 'Akses ditolak. Halaman ini khusus untuk Teknisi Lapangan dan Administrator Banterpool.');
        }

        return $next($request);
    }
}
