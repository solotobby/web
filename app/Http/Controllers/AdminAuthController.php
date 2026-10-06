<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show the executive admin login view.
     */
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('admin_authenticated', false)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    /**
     * Process admin master passcode authentication.
     */
    public function login(Request $request): RedirectResponse
    {
        $throttleKey = 'admin-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'passcode' => "Too many failed attempts. Security cooldown active for {$seconds} seconds.",
            ]);
        }

        $request->validate([
            'passcode' => ['required', 'string'],
        ]);

        $masterKey = (string) config('app.admin_key');
        $inputKey = (string) $request->input('passcode');

        if ($masterKey !== '' && hash_equals($masterKey, $inputKey)) {
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();
            $request->session()->put('admin_authenticated', true);
            $request->session()->put('admin_logged_in_at', now()->toIso8601String());

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Executive session authenticated.');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'passcode' => 'Invalid executive master passcode. Access denied.',
        ]);
    }

    /**
     * Terminate the executive session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_authenticated', 'admin_logged_in_at']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('info', 'Executive session securely terminated.');
    }
}
