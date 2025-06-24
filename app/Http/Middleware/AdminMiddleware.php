<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */

    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est un admin
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Si ce n'est pas un admin, rediriger vers la page d'accueil
        return redirect('/home');
    }
}
