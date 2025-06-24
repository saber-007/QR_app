<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
class AuthAgentMiddleware
{
    public function handle(Request $request, Closure $next)
    {
            if (!session()->has('agent_id')) {
                return redirect()->route('agents.login'); // Redirige vers la page de login si l'agent n'est pas authentifié
            }

            return $next($request);
    }}
