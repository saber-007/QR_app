<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Affiche le formulaire de connexion
     *
     * @return View|RedirectResponse
     */
    public function showLoginForm()
    {
        // Rediriger si déjà connecté
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    /**
     * Traite la connexion utilisateur
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function login(Request $request)
    {
        try {
            // Validation des données
            $credentials = $request->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:6',
            ]);

            $remember = $request->boolean('remember');

            // Limitation des tentatives de connexion
            if ($this->hasTooManyLoginAttempts($request)) {
                $message = 'Trop de tentatives de connexion. Réessayez dans quelques minutes.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message
                    ], 429);
                }

                return back()->withErrors(['email' => $message]);
            }

            // Tentative d'authentification
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                $this->clearLoginAttempts($request);

                $user = Auth::user();

                Log::info('Connexion réussie', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Connexion réussie',
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email
                        ],
                        'redirect_url' => route('home')
                    ]);
                }

                return redirect()->intended(route('home'));
            }

            // Échec de connexion
            $this->incrementLoginAttempts($request);

            Log::warning('Tentative de connexion échouée', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $message = 'Email ou mot de passe incorrect';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message
                ], 401);
            }

            return back()->withErrors(['email' => $message])->withInput();

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Données de connexion invalides',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();

        } catch (\Illuminate\Session\TokenMismatchException $e) {
            Log::warning('Token CSRF expiré lors de la connexion', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session expirée. Veuillez actualiser la page.'
                ], 419);
            }

            return redirect()->route('login')->withErrors(['email' => 'Session expirée. Veuillez réessayer.']);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip()
            ]);

            $message = 'Une erreur système est survenue';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message
                ], 500);
            }

            return back()->withErrors(['email' => $message]);
        }
    }

    /**
     * Déconnexion de l'utilisateur
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function logout(Request $request)
    {
        try {
            $userId = Auth::id();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('Déconnexion utilisateur', [
                'user_id' => $userId,
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Déconnexion réussie',
                    'redirect_url' => route('login')
                ]);
            }

            return redirect()->route('login');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur lors de la déconnexion'
                ], 500);
            }

            return redirect()->route('login');
        }
    }

    /**
     * Affiche le formulaire d'inscription
     *
     * @return View|RedirectResponse
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    /**
     * Traite l'inscription d'un nouvel utilisateur
     *
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     */
    public function register(Request $request)
    {
        try {
            // Validation des données
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ], [
                // Messages d'erreur personnalisés
                'name.required' => 'Le nom est obligatoire.',
                'email.required' => 'L\'adresse email est obligatoire.',
                'email.email' => 'L\'adresse email doit être valide.',
                'email.unique' => 'Cette adresse email est déjà utilisée.',
                'password.required' => 'Le mot de passe est obligatoire.',
                'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
                'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            ]);

            // Vérification supplémentaire pour éviter les doublons
            if (User::where('email', strtolower(trim($validated['email'])))->exists()) {
                $message = 'Un utilisateur avec cette adresse email existe déjà.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $message,
                        'errors' => ['email' => [$message]]
                    ], 422);
                }

                return back()->withErrors(['email' => $message])->withInput();
            }

            // Création de l'utilisateur
            $user = User::create([
                'name' => $validated['name'],
                'email' => strtolower(trim($validated['email'])),
                'password' => bcrypt($validated['password']),
            ]);

            // Connexion automatique
            Auth::login($user);
            $request->session()->regenerate();

            Log::info('Nouveau compte créé', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Compte créé avec succès',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'redirect_url' => route('home')
                ]);
            }

            return redirect()->route('home')->with('success', 'Compte créé avec succès !');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Données d\'inscription invalides',
                    'errors' => $e->errors()
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();

        } catch (\Illuminate\Session\TokenMismatchException $e) {
            Log::warning('Token CSRF expiré lors de l\'inscription', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Session expirée. Veuillez actualiser la page.'
                ], 419);
            }

            return redirect()->route('register')->withErrors(['email' => 'Session expirée. Veuillez réessayer.']);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'inscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip()
            ]);

            $message = 'Erreur lors de la création du compte';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $message
                ], 500);
            }

            return back()->withErrors(['general' => $message]);
        }
    }

    /**
     * Vérifie si l'utilisateur a fait trop de tentatives de connexion
     *
     * @param Request $request
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        $maxAttempts = 5;
        $decayTime = 15; // minutes

        $key = $this->throttleKey($request);
        $attempts = session()->get($key . '_attempts', 0);
        $lastAttempt = session()->get($key . '_time', 0);

        if ($attempts >= $maxAttempts && (time() - $lastAttempt) < ($decayTime * 60)) {
            return true;
        }

        // Reset si le délai est dépassé
        if ((time() - $lastAttempt) >= ($decayTime * 60)) {
            session()->forget([$key . '_attempts', $key . '_time']);
        }

        return false;
    }

    /**
     * Incrémente le compteur de tentatives de connexion
     *
     * @param Request $request
     * @return void
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        $key = $this->throttleKey($request);
        $attempts = session()->get($key . '_attempts', 0) + 1;

        session()->put($key . '_attempts', $attempts);
        session()->put($key . '_time', time());
    }

    /**
     * Efface les tentatives de connexion
     *
     * @param Request $request
     * @return void
     */
    protected function clearLoginAttempts(Request $request): void
    {
        $key = $this->throttleKey($request);
        session()->forget([$key . '_attempts', $key . '_time']);
    }

    /**
     * Génère une clé unique pour le throttling
     *
     * @param Request $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        return 'login_attempts_' . md5($request->ip() . '|' . ($request->input('email') ?? ''));
    }
}
