<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    public function store(Request $request)
    {
        $credentials = $request->only('login', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Redirige en fonction du type d'agent
            if ($user->type == 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->type == 'entry') {
                return redirect()->route('entry.dashboard');
            } elseif ($user->type == 'exit') {
                return redirect()->route('exit.dashboard');
            }
        }

        return back()->withErrors([
            'login' => 'Les identifiants ne sont pas corrects.',
        ]);
    }
}
