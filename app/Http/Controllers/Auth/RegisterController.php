<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminInvite;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/admin/export';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'invite' => ['required', 'string'],
        ]);
    }

    /**
     * Fetch the invite matching the given token and email, or fail.
     */
    protected function resolveInvite(string $token, string $email): AdminInvite
    {
        $invite = AdminInvite::where('token', $token)->first();

        if (! $invite || ! $invite->isValid() || strcasecmp($invite->email, $email) !== 0) {
            throw ValidationException::withMessages([
                'invite' => 'This registration link is invalid or has expired.',
            ]);
        }

        return $invite;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return User
     */
    protected function create(array $data, AdminInvite $invite)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->role = $invite->role;
        $user->save();

        return $user;
    }

    /**
     * Show the application registration form.
     */
    public function showRegistrationForm(Request $request): View|RedirectResponse
    {
        $token = (string) $request->query('invite', '');
        $invite = AdminInvite::where('token', $token)->first();

        if (! $invite || ! $invite->isValid()) {
            return redirect()->route('login')->with('error', 'This registration link is invalid or has expired.');
        }

        return view('auth.register', ['invite' => $invite]);
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request): RedirectResponse
    {
        $this->validator($request->all())->validate();

        $invite = $this->resolveInvite((string) $request->input('invite'), (string) $request->input('email'));

        $user = $this->create($request->all(), $invite);

        $invite->used_at = now();
        $invite->save();

        event(new Registered($user));

        Auth::login($user);

        return redirect($this->redirectTo);
    }
}
