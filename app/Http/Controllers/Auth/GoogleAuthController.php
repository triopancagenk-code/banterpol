<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     * Mengarahkan pengguna langsung ke halaman otentikasi resmi Google (accounts.google.com).
     */
    public function redirectToGoogle(): RedirectResponse
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            Log::error('Socialite redirect error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal menghubungkan ke Google: ' . $e->getMessage() . '. Pastikan GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET di berkas .env telah diisi dengan kredensial Google Cloud Console Anda.'
            ]);
        }
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => 'Autentikasi Google dibatalkan atau terjadi kesalahan saat login.'
            ]);
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (empty($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Google tidak memiliki alamat email publik yang valid.'
            ]);
        }

        // Cari berdasarkan google_id terlebih dahulu, atau email
        $user = User::where('google_id', $googleId)->first();

        if (!$user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // Sambungkan akun yang sudah ada ke google_id
                $user->google_id = $googleId;
                if ($googleUser->getAvatar() && empty($user->avatar)) {
                    $user->avatar = $googleUser->getAvatar();
                }
                $user->save();
            } else {
                // Buat akun baru pelanggan
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
}
