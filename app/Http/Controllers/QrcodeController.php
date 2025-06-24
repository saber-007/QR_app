<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\QRCode;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class QrcodeController extends Controller
{
    /**
     * Middleware d'authentification pour protéger les routes sensibles
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['login', 'showLoginForm']);
    }

    /**
     * Affiche le formulaire de connexion
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Traite la connexion utilisateur
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            // Validation des données d'entrée
            $credentials = $request->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:6',
            ]);

            $remember = $request->boolean('remember');

            // Tentative d'authentification
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();

                $user = Auth::user();

                Log::info('Connexion réussie', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Connexion réussie',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'redirect_url' => route('qrcode.scan.form')
                ]);
            }

            // Échec de connexion
            Log::warning('Tentative de connexion échouée', [
                'email' => $credentials['email'],
                'ip' => $request->ip()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Email ou mot de passe incorrect'
            ], 401);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la connexion'
            ], 500);
        }
    }

    /**
     * Affiche le formulaire de scan
     *
     * @return View
     */
    public function showScanForm(): View
    {
        $stats = $this->getStatistics();
        return view('scan', compact('stats')); //un changement
    }

    /**
     * Traite le scan d'un code QR
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function scan(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $validated = $request->validate([
                'code' => 'required|string|max:255',
                'produit' => 'nullable|string|max:255',
                'quantite' => 'nullable|integer|min:0|max:9999',
                'chauffeur' => 'nullable|string|max:255',
            ]);

            Log::info('Scan initié', [
                'code' => $validated['code'],
                'agent_id' => Auth::id()
            ]);

            // Recherche du code QR existant
            $qrcode = QRCode::where('code', $validated['code'])->first();

            // Nouveau code QR
            if (!$qrcode) {
                return $this->handleNewQRCode($validated);
            }

            // Code déjà scanné (fraude potentielle)
            if ($this->isAlreadyScanned($qrcode)) {
                return $this->handleFraud($qrcode, $validated);
            }

            // Premier scan d'un code existant
            return $this->handleValidScan($qrcode, $validated);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors du scan', [
                'error' => $e->getMessage(),
                'code' => $validated['code'] ?? 'unknown',
                'agent_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors du scan'
            ], 500);
        }
    }

    /**
     * Gère un nouveau code QR
     *
     * @param array $validated
     * @return JsonResponse
     */
    private function handleNewQRCode(array $validated): JsonResponse
    {
        $qrcode = QRCode::create([
            'code' => $validated['code'],
            'scan_count' => 1,
            'last_scanned_at' => now(),
            'sortie' => true,
            'date_sortie' => now(),
            'is_fraud' => false
        ]);

        $this->recordScan($validated, 'nouveau', $qrcode->id);

        Log::info('Nouveau code QR créé', [
            'qrcode_id' => $qrcode->id,
            'code' => $validated['code']
        ]);

        return response()->json([
            'status' => 'nouveau',
            'message' => 'Nouveau code QR enregistré avec succès',
            'qrcode' => $qrcode->only(['id', 'code', 'scan_count', 'last_scanned_at'])
        ]);
    }

    /**
     * Gère une tentative de fraude
     *
     * @param QRCode $qrcode
     * @param array $validated
     * @return JsonResponse
     */
    private function handleFraud(QRCode $qrcode, array $validated): JsonResponse
    {
        $qrcode->increment('scan_count');
        $qrcode->update([
            'is_fraud' => true,
            'last_scanned_at' => now()
        ]);

        $this->recordScan($validated, 'fraude', $qrcode->id);

        Log::warning('Fraude détectée', [
            'qrcode_id' => $qrcode->id,
            'code' => $validated['code'],
            'scan_count' => $qrcode->scan_count,
            'agent_id' => Auth::id()
        ]);

        return response()->json([
            'status' => 'fraude',
            'message' => sprintf(
                'ALERTE FRAUDE: Ce code a déjà été scanné %d fois. Dernière fois: %s',
                $qrcode->scan_count - 1,
                $qrcode->last_scanned_at->format('d/m/Y à H:i')
            ),
            'qrcode' => $qrcode->only(['id', 'code', 'scan_count', 'last_scanned_at'])
        ], 409);
    }

    /**
     * Gère un scan valide
     *
     * @param QRCode $qrcode
     * @param array $validated
     * @return JsonResponse
     */
    private function handleValidScan(QRCode $qrcode, array $validated): JsonResponse
    {
        $qrcode->update([
            'scan_count' => $qrcode->scan_count + 1,
            'last_scanned_at' => now(),
            'sortie' => true,
            'date_sortie' => now()
        ]);

        $this->recordScan($validated, 'valide', $qrcode->id);

        Log::info('Scan valide', [
            'qrcode_id' => $qrcode->id,
            'code' => $validated['code']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Code QR validé avec succès',
            'qrcode' => $qrcode->only(['id', 'code', 'scan_count', 'last_scanned_at'])
        ]);
    }

    /**
     * Vérifie si un code QR a déjà été scanné
     *
     * @param QRCode $qrcode
     * @return bool
     */
    private function isAlreadyScanned(QRCode $qrcode): bool
    {
        return $qrcode->scan_count > 0 || $qrcode->sortie;
    }

    /**
     * Enregistre un scan dans l'historique
     *
     * @param array $validated
     * @param string $status
     * @param int|null $qrcodeId
     * @return void
     */
    private function recordScan(array $validated, string $status, ?int $qrcodeId = null): void
    {
        try {
            Scan::create([
                'qrcode_id' => $qrcodeId,
                'code' => $validated['code'],
                'produit' => $validated['produit'] ?? 'Non précisé',
                'quantite' => $validated['quantite'] ?? 0,
                'chauffeur' => $validated['chauffeur'] ?? 'Non précisé',
                'status' => $status,
                'date_scan' => now(),
                'agent_id' => Auth::id()
            ]);

            Log::info('Scan enregistré', [
                'code' => $validated['code'],
                'status' => $status,
                'agent_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur enregistrement scan', [
                'error' => $e->getMessage(),
                'code' => $validated['code'],
                'agent_id' => Auth::id()
            ]);
        }
    }

    /**
     * Affiche l'historique des scans avec filtres
     *
     * @param Request $request
     * @return View
     */
    public function historique(Request $request): View
    {
        // Construction de la requête avec filtres
        $query = Scan::with(['agent:id,name,email'])
                    ->orderBy('date_scan', 'desc');

        // Application des filtres
        $this->applyFilters($query, $request);

        // Pagination
        $scans = $query->paginate(20)->withQueryString();

        // Statistiques
        $stats = $this->getStatistics();

        Log::info('Consultation historique', [
            'agent_id' => Auth::id(),
            'filters' => $request->only(['status', 'date_debut', 'date_fin', 'code']),
            'total_results' => $scans->total()
        ]);

        return view('historique', compact('scans', 'stats'));
    }

    /**
     * Applique les filtres à la requête
     *
     * @param mixed $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_scan', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_scan', '<=', $request->date_fin);
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->filled('agent')) {
            $query->where('agent_id', $request->agent);
        }
    }

    /**
     * Calcule les statistiques générales
     *
     * @return array
     */
    private function getStatistics(): array
    {
        return [
            'total_scans' => Scan::count(),
            'scans_valides' => Scan::whereIn('status', ['valide', 'nouveau'])->count(),
            'tentatives_fraude' => Scan::where('status', 'fraude')->count(),
            'scans_aujourd_hui' => Scan::whereDate('date_scan', today())->count(),
            'scans_cette_semaine' => Scan::whereBetween('date_scan', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'codes_uniques' => QRCode::count(),
            'codes_frauduleux' => QRCode::where('is_fraud', true)->count()
        ];
    }

    /**
     * Déconnexion de l'utilisateur
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Déconnexion utilisateur', ['ip' => $request->ip()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie',
            'redirect_url' => route('qrcode.login.form')
        ]);
    }
    public function manageRoles(): View
{
    // Récupérer tous les utilisateurs avec leurs rôles
    $users = \App\Models\User::orderBy('name')->get();

    // Définir les rôles disponibles
    $availableRoles = [
        'admin' => 'Administrateur',
        'entry' => 'Agent d\'entrée',
        'exit' => 'Agent de sortie',
        'user' => 'Utilisateur standard'
    ];

    Log::info('Consultation page gestion des rôles', [
        'admin_id' => Auth::id(),
        'total_users' => $users->count()
    ]);

    return view('admin.roles', compact('users', 'availableRoles'));
}

/**
 * Met à jour le rôle d'un utilisateur
 *
 * @param Request $request
 * @return JsonResponse
 */
public function updateUserRole(Request $request): JsonResponse
{
    try {
        // Validation des données
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|in:admin,entry,exit,user'
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);

        // Empêcher un admin de modifier son propre rôle
        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas modifier votre propre rôle'
            ], 403);
        }

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        Log::info('Rôle utilisateur modifié', [
            'admin_id' => Auth::id(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'old_role' => $oldRole,
            'new_role' => $validated['role']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => sprintf(
                'Rôle de %s modifié avec succès de "%s" vers "%s"',
                $user->name,
                $this->getRoleLabel($oldRole),
                $this->getRoleLabel($validated['role'])
            ),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Données invalides',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur modification rôle utilisateur', [
            'error' => $e->getMessage(),
            'admin_id' => Auth::id(),
            'user_id' => $validated['user_id'] ?? null
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue lors de la modification du rôle'
        ], 500);
    }
}

/**
 * Supprime un utilisateur (admin uniquement)
 *
 * @param Request $request
 * @return JsonResponse
 */
public function deleteUser(Request $request): JsonResponse
{
    try {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);

        // Empêcher un admin de se supprimer lui-même
        if ($user->id === Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 403);
        }

        $userName = $user->name;
        $userEmail = $user->email;

        $user->delete();

        Log::warning('Utilisateur supprimé', [
            'admin_id' => Auth::id(),
            'deleted_user_id' => $validated['user_id'],
            'deleted_user_name' => $userName,
            'deleted_user_email' => $userEmail
        ]);

        return response()->json([
            'status' => 'success',
            'message' => sprintf('Utilisateur %s supprimé avec succès', $userName)
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Données invalides',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Erreur suppression utilisateur', [
            'error' => $e->getMessage(),
            'admin_id' => Auth::id(),
            'user_id' => $validated['user_id'] ?? null
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue lors de la suppression'
        ], 500);
    }
}

/**
 * Retourne le libellé d'un rôle
 *
 * @param string $role
 * @return string
 */
private function getRoleLabel(string $role): string
{
    $labels = [
        'admin' => 'Administrateur',
        'entry' => 'Agent d\'entrée',
        'exit' => 'Agent de sortie',
        'user' => 'Utilisateur standard'
    ];

    return $labels[$role] ?? $role;
}
}
