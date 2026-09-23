<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * URL Callback Google OAuth resmi.
     */
    public const GOOGLE_CALLBACK_URL = 'https://banterpol.sagainfra.id/auth/google/callback';

    /**
     * Dapatkan URL callback untuk Google OAuth.
     */
    public function getGoogleCallbackUrl(): string
    {
        return self::GOOGLE_CALLBACK_URL;
    }

    /**
     * Pastikan redirect URI menggunakan scheme http:// atau https://
     * dan default ke https://banterpol.sagainfra.id/auth/google/callback.
     */
    private function normalizeRedirectUri(): string
    {
        $redirect = self::GOOGLE_CALLBACK_URL;

        config(['services.google.redirect' => $redirect]);

        return $redirect;
    }

    /**
     * Redirect pengguna ke halaman login Google.
     */
    public function google_redirect(): RedirectResponse
    {
        $this->normalizeRedirectUri();

        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            Log::error('Google Auth Redirect error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal menghubungkan ke Google: ' . $e->getMessage() . '. Pastikan GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET pada .env telah sesuai.'
            ]);
        }
    }

    /**
     * Alias method untuk redirectToGoogle.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return $this->google_redirect();
    }

    /**
     * Handle callback dari otentikasi Google.
     */
    public function google_callback(): RedirectResponse
    {
        $this->normalizeRedirectUri();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            // Fallback stateless jika terjadi perbedaan session/state
            try {
                $googleUser = Socialite::driver('google')->stateless()->user();
            } catch (\Exception $subE) {
                Log::error('Google OAuth callback stateless error: ' . $subE->getMessage());
                return redirect()->route('login')->withErrors([
                    'email' => 'Autentikasi Google gagal atau sesi kadaluarsa. Silakan coba kembali.'
                ]);
            }
        } catch (\Exception $e) {
            try {
                $googleUser = Socialite::driver('google')->stateless()->user();
            } catch (\Exception $subE) {
                Log::error('Google OAuth callback error: ' . $e->getMessage());
                return redirect()->route('login')->withErrors([
                    'email' => 'Autentikasi Google dibatalkan atau terjadi kesalahan saat login.'
                ]);
            }
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (empty($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Google Anda tidak memiliki alamat email publik yang valid.'
            ]);
        }

        // Cari pengguna berdasarkan google_id terlebih dahulu, atau email
        $user = User::where('google_id', $googleId)->first();

        if (!$user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // Tautkan akun lama dengan google_id
                $user->google_id = $googleId;
                if ($googleUser->getAvatar() && empty($user->avatar)) {
                    $user->avatar = $googleUser->getAvatar();
                }
                $user->save();
            } else {
                // Buat akun baru pelanggan (customer)
                $user = User::create([
                    'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Pelanggan Banterpool'),
                    'email' => $email,
                    'google_id' => $googleId,
                    'phone' => null,
                    'role' => 'customer',
                    'is_active' => true,
                    'avatar' => $googleUser->getAvatar() ?: 'https://lh3.googleusercontent.com/a/default-user=s96-c',
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                ]);
            }
        } else {
            if ($googleUser->getAvatar() && empty($user->avatar)) {
                $user->avatar = $googleUser->getAvatar();
                $user->save();
            }
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        // Redirect sesuai role pengguna
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        if ($user->role === 'technician' || $user->role === 'teknisi') {
            request()->session()->forget('url.intended');
            return redirect()->route('teknisi.dashboard');
        }

        if ($user->role === 'collector' || $user->role === 'kolektor') {
            request()->session()->forget('url.intended');
            return redirect()->route('kolektor.dashboard');
        }

        return redirect()->intended(route('home', absolute: false))->with('status', 'Selamat datang, ' . $user->name . '! Anda berhasil masuk.');
    }

    /**
     * Alias method untuk handleGoogleCallback.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        return $this->google_callback();
    }

    /**
     * Logout pengguna dan invalidate session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
