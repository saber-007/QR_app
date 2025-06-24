<?php

namespace App\Http\Controllers;

use App\Models\QRCode;
use App\Models\Scan;
use App\Services\QRCodeScanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ScanController extends Controller
{
    protected $scanService;

    public function __construct(QRCodeScanService $scanService)
    {
        $this->middleware('auth');
        $this->scanService = $scanService;
    }

    /**
     * Affiche le formulaire de scan
     *
     * @return View
     */
    public function showScanForm(): View
    {
        $stats = $this->getDailyStats();
        $recentScans = $this->getRecentScans();

        return view('scan.form', compact('stats', 'recentScans'));
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
                'notes' => 'nullable|string|max:500',
            ]);

            Log::info('Scan initié', [
                'code' => $validated['code'],
                'agent_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            // Traitement du scan via le service
            $result = $this->scanService->processScan($validated, Auth::id());

            // Log du résultat
            Log::info('Scan traité', [
                'code' => $validated['code'],
                'status' => $result['status'],
                'agent_id' => Auth::id()
            ]);

            return response()->json($result, $result['http_code'] ?? 200);

        } catch (ValidationException $e) {
            Log::warning('Données de scan invalides', [
                'errors' => $e->errors(),
                'agent_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Données de scan invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur lors du scan', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Affiche l'historique des scans avec filtres
     *
     * @param Request $request
     * @return View
     */
    public function history(Request $request): View
    {
        // Validation des filtres
        $filters = $request->validate([
            'status' => 'nullable|in:valide,nouveau,fraude',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'code' => 'nullable|string|max:255',
            'agent' => 'nullable|exists:users,id',
            'produit' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:10|max:100'
        ]);

        // Construction de la requête
        $query = Scan::with(['agent:id,name,email', 'qrcode:id,code'])
                    ->orderBy('date_scan', 'desc');

        // Application des filtres
        $this->applyFilters($query, $filters);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;
        $scans = $query->paginate($perPage)->withQueryString();

        // Statistiques pour la période filtrée
        $stats = $this->getFilteredStats($filters);

        // Liste des agents pour le filtre
        $agents = \App\Models\User::select('id', 'name')->orderBy('name')->get();

        Log::info('Consultation historique des scans', [
            'agent_id' => Auth::id(),
            'filters' => $filters,
            'total_results' => $scans->total()
        ]);

        return view('scan.history', compact('scans', 'stats', 'agents', 'filters'));
    }

    /**
     * Export des données de scan
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->validate([
                'status' => 'nullable|in:valide,nouveau,fraude',
                'date_debut' => 'nullable|date',
                'date_fin' => 'nullable|date|after_or_equal:date_debut',
                'format' => 'required|in:csv,xlsx'
            ]);

            $query = Scan::with(['agent:id,name', 'qrcode:id,code'])
                        ->orderBy('date_scan', 'desc');

            $this->applyFilters($query, $filters);
            $scans = $query->get();

            Log::info('Export des scans', [
                'agent_id' => Auth::id(),
                'format' => $filters['format'],
                'count' => $scans->count()
            ]);

            return $this->scanService->exportScans($scans, $filters['format']);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export', [
                'error' => $e->getMessage(),
                'agent_id' => Auth::id()
            ]);

            return back()->with('error', 'Erreur lors de l\'export des données');
        }
    }

    /**
     * API pour obtenir les statistiques en temps réel
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'today' => $this->getDailyStats(),
                'week' => $this->getWeeklyStats(),
                'month' => $this->getMonthlyStats()
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des statistiques', [
                'error' => $e->getMessage(),
                'agent_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du calcul des statistiques'
            ], 500);
        }
    }

    /**
     * Applique les filtres à la requête
     *
     * @param mixed $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_scan', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_scan', '<=', $filters['date_fin']);
        }

        if (!empty($filters['code'])) {
            $query->where('code', 'like', '%' . $filters['code'] . '%');
        }

        if (!empty($filters['agent'])) {
            $query->where('agent_id', $filters['agent']);
        }

        if (!empty($filters['produit'])) {
            $query->where('produit', 'like', '%' . $filters['produit'] . '%');
        }
    }

    /**
     * Statistiques quotidiennes
     *
     * @return array
     */
    private function getDailyStats(): array
    {
        $today = today();

        return [
            'total_scans' => Scan::whereDate('date_scan', $today)->count(),
            'scans_valides' => Scan::whereDate('date_scan', $today)
                                 ->whereIn('status', ['valide', 'nouveau'])->count(),
            'tentatives_fraude' => Scan::whereDate('date_scan', $today)
                                     ->where('status', 'fraude')->count(),
            'codes_uniques' => Scan::whereDate('date_scan', $today)
                                 ->distinct('code')->count(),
            'mon_activite' => Scan::whereDate('date_scan', $today)
                                ->where('agent_id', Auth::id())->count()
        ];
    }

    /**
     * Statistiques hebdomadaires
     *
     * @return array
     */
    private function getWeeklyStats(): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return [
            'total_scans' => Scan::whereBetween('date_scan', [$startOfWeek, $endOfWeek])->count(),
            'scans_valides' => Scan::whereBetween('date_scan', [$startOfWeek, $endOfWeek])
                                 ->whereIn('status', ['valide', 'nouveau'])->count(),
            'tentatives_fraude' => Scan::whereBetween('date_scan', [$startOfWeek, $endOfWeek])
                                     ->where('status', 'fraude')->count(),
        ];
    }

    /**
     * Statistiques mensuelles
     *
     * @return array
     */
    private function getMonthlyStats(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return [
            'total_scans' => Scan::whereBetween('date_scan', [$startOfMonth, $endOfMonth])->count(),
            'scans_valides' => Scan::whereBetween('date_scan', [$startOfMonth, $endOfMonth])
                                 ->whereIn('status', ['valide', 'nouveau'])->count(),
            'tentatives_fraude' => Scan::whereBetween('date_scan', [$startOfMonth, $endOfMonth])
                                     ->where('status', 'fraude')->count(),
        ];
    }

    /**
     * Statistiques filtrées
     *
     * @param array $filters
     * @return array
     */
    private function getFilteredStats(array $filters): array
    {
        $query = Scan::query();
        $this->applyFilters($query, $filters);

        return [
            'total_scans' => $query->count(),
            'scans_valides' => (clone $query)->whereIn('status', ['valide', 'nouveau'])->count(),
            'tentatives_fraude' => (clone $query)->where('status', 'fraude')->count(),
            'codes_uniques' => (clone $query)->distinct('code')->count()
        ];
    }

    /**
     * Récupère les scans récents
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getRecentScans(int $limit = 10)
    {
        return Scan::with(['agent:id,name'])
                  ->select('id', 'code', 'status', 'date_scan', 'agent_id', 'produit')
                  ->orderBy('date_scan', 'desc')
                  ->limit($limit)
                  ->get();
    }
}
