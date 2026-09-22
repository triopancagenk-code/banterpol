<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginInput = $this->input('email');
        $password = $this->input('password');

        // Check credentials with input directly (supports email: 'admin' or 'admin@banterpool.net' or normal email)
        $authenticated = Auth::attempt(['email' => $loginInput, 'password' => $password], $this->boolean('remember'));

        if (!$authenticated && ($loginInput === 'admin' || $loginInput === 'admin@banterpol.net')) {
            $authenticated = Auth::attempt(['email' => 'admin@banterpool.net', 'password' => $password], $this->boolean('remember'));
        }

        if (!$authenticated && ($loginInput === 'direktur' || $loginInput === 'direktur@banterpol.net' || $loginInput === 'direktur@banterpool.net')) {
            $authenticated = Auth::attempt(['email' => 'direktur@banterpool.net', 'password' => $password], $this->boolean('remember'));
        }

        if (!$authenticated && in_array(strtolower($loginInput), ['mamat', 'danu', 'okta'])) {
            $authenticated = Auth::attempt(['email' => strtolower($loginInput) . '@teknisi.net', 'password' => $password], $this->boolean('remember'));
        }

        if (!$authenticated && in_array(strtolower($loginInput), ['dila', 'saefudin', 'arti', 'bagas'])) {
            $authenticated = Auth::attempt(['email' => strtolower($loginInput) . '@kolektor.net', 'password' => $password], $this->boolean('remember'));
        }

        if (!$authenticated && strtolower($loginInput) === 'aji') {
            $authenticated = Auth::attempt(['email' => 'aji@teknisi.net', 'password' => $password], $this->boolean('remember'))
                || Auth::attempt(['email' => 'aji@kolektor.net', 'password' => $password], $this->boolean('remember'));
        }

        if (!$authenticated && $loginInput === 'root') {
            $authenticated = Auth::attempt(['email' => 'root@gmail.com', 'password' => $password], $this->boolean('remember'));
        }

        if (!$authenticated) {
            $authenticated = Auth::attempt(['phone' => $loginInput, 'password' => $password], $this->boolean('remember'));
        }

        if (!$authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
