<?php

namespace App\Http\Controllers;

use App\Notifications\SendTwoFactorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function index(): View
    {
        return view('auth.twoFactor');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'two_factor_code' => ['integer', 'required'],
        ]);

        $user = auth()->user();

        if (! $user->two_factor_code || ! hash_equals((string) $user->two_factor_code, (string) $request->input('two_factor_code'))) {
            throw ValidationException::withMessages([
                'two_factor_code' => __("The code you entered doesn't match our records"),
            ]);
        }

        $user->resetTwoFactorCode();

        return redirect()->intended('/admin/export');
    }

    public function resend(): RedirectResponse
    {
        $user = auth()->user();
        $user->generateTwoFactorCode();
        $user->notify(new SendTwoFactorCode);

        return redirect()->back()->withStatus(__('Code has been sent again'));
    }
}
