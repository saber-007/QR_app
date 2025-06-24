<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                // Optional: Add more password complexity rules
                // Password::min(8)->mixedCase()->numbers()->symbols()
            ],
        ], [
            // Custom error messages
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])), // Normalize email
            'password' => Hash::make($data['password']),
            'email_verified_at' => null, // Set to null if using email verification
        ]);
    }

    /**
     * Handle a registration request for the application.
     * Override to add additional checks if needed.
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        // Double-check if user exists (extra safety)
        if (User::where('email', strtolower(trim($request->email)))->exists()) {
            return back()->withErrors([
                'email' => 'This email address is already registered.'
            ])->withInput();
        }

        $user = $this->create($request->all());

        // Optionally, you can add additional logic here
        // like sending welcome email, logging the registration, etc.

        $this->guard()->login($user);

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }
}
