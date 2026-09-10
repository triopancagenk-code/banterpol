<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan masuk dengan akun Administrator.');
        }

        if (! Auth::user()->isAdmin()) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk Administrator Banterpool.');
        }

        return $next($request);
    }
}
