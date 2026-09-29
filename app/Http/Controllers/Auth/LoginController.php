<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\SendTwoFactorCode;
use App\Services\AdminAuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the application's login form.
     *
     * @return View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $auditService = new AdminAuditLogService;

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();
            $user->generateTwoFactorCode();
            $user->notify(new SendTwoFactorCode);

            // Log successful login
            $auditService->log(
                action: 'login',
                wasSuccessful: true
            );

            // Redirect to the intended URL or a default location
            return redirect()->intended('/admin/export'); // Redirect to admin export page after login
        }

        // Log failed login attempt
        $auditService->log(
            action: 'login',
            wasSuccessful: false,
            errorMessage: 'Invalid credentials for email: '.$credentials['email']
        );

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        (new AdminAuditLogService)->log(
            action: 'logout',
            wasSuccessful: true,
            authUserId: $userId
        );

        return redirect('/'); // Redirect to home page after logout
    }
}
