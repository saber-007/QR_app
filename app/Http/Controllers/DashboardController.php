<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\QRCode;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le tableau de bord de l'administrateur.
     *
     * @return \Illuminate\View\View
     */
    public function admin()
    {
        // Statistiques pour l'admin
        $stats = [
            'total_codes' => QRCode::count(),
            'codes_scannes' => QRCode::where('scan_count', '>', 0)->count(),
            'tentatives_fraude' => QRCode::where('is_fraud', true)->count(),
            'scans_aujourd_hui' => Scan::whereDate('date_scan', today())->count(),
            'scans_cette_semaine' => Scan::whereBetween('date_scan', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'agents_actifs' => User::whereHas('scans', function($query) {
                $query->whereDate('date_scan', today());
            })->count(),
        ];

        // Scans récents
        $recent_scans = Scan::with('agent')
            ->orderBy('date_scan', 'desc')
            ->limit(10)
            ->get();

        // Codes frauduleux récents
        $recent_frauds = QRCode::where('is_fraud', true)
            ->orderBy('last_scanned_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_scans', 'recent_frauds'));
    }

    /**
     * Affiche le tableau de bord de l'agent d'entrée.
     *
     * @return \Illuminate\View\View
     */
    public function entry()
    {
        $user = Auth::user();

        // Récupérer la dernière activité de manière plus lisible
        $dernierScan = Scan::where('agent_id', $user->id)
            ->orderBy('date_scan', 'desc')
            ->first();

        // Statistiques de l'agent
        $stats = [
            'mes_scans_aujourd_hui' => Scan::where('agent_id', $user->id)
                ->whereDate('date_scan', today())
                ->count(),
            'mes_scans_total' => Scan::where('agent_id', $user->id)->count(),
            'derniere_activite' => $dernierScan && $dernierScan->date_scan
                ? $dernierScan->date_scan->format('d/m/Y H:i')
                : null,
        ];

        // Mes scans récents
        $mes_scans = Scan::where('agent_id', $user->id)
            ->orderBy('date_scan', 'desc')
            ->limit(10)
            ->get();

        return view('entry.dashboard', compact('stats', 'mes_scans'));
    }

    /**
     * Affiche le tableau de bord de l'agent de sortie.
     *
     * @return \Illuminate\View\View
     */
    public function exit()
    {
        $user = Auth::user();

        // Récupérer la dernière activité de manière plus lisible
        $dernierScan = Scan::where('agent_id', $user->id)
            ->orderBy('date_scan', 'desc')
            ->first();

        // Statistiques de l'agent
        $stats = [
            'mes_scans_aujourd_hui' => Scan::where('agent_id', $user->id)
                ->whereDate('date_scan', today())
                ->count(),
            'mes_scans_total' => Scan::where('agent_id', $user->id)->count(),
            'fraudes_detectees' => Scan::where('agent_id', $user->id)
                ->where('status', 'fraude')
                ->count(),
            'derniere_activite' => $dernierScan && $dernierScan->date_scan
                ? $dernierScan->date_scan->format('d/m/Y H:i')
                : null,
        ];

        // Mes scans récents
        $mes_scans = Scan::where('agent_id', $user->id)
            ->orderBy('date_scan', 'desc')
            ->limit(10)
            ->get();

        return view('exit.dashboard', compact('stats', 'mes_scans'));
    }

    /**
     * Créer un nouvel agent (admin seulement).
     *
     * @return \Illuminate\View\View
     */
    public function createAgent()
    {
        return view('admin.create-agent');
    }

    /**
     * Enregistrer un nouvel agent.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAgent(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,entry,exit',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Agent créé avec succès : ' . $user->name);
    }

    /**
     * Gestion des utilisateurs (admin seulement).
     *
     * @return \Illuminate\View\View
     */
    public function manageUsers()
    {
        $users = User::withCount('scans')->paginate(20);

        return view('admin.users', compact('users'));
    }

    /**
     * Statistiques détaillées (admin seulement).
     *
     * @return \Illuminate\View\View
     */
    public function stats()
    {
        // Statistiques par jour (7 derniers jours)
        $daily_stats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $daily_stats[] = [
                'date' => $date->format('d/m'),
                'scans' => Scan::whereDate('date_scan', $date)->count(),
                'fraudes' => Scan::whereDate('date_scan', $date)->where('status', 'fraude')->count(),
            ];
        }

        // Top des codes les plus scannés
        $top_codes = QRCode::orderBy('scan_count', 'desc')
            ->where('scan_count', '>', 0)
            ->limit(10)
            ->get();

        // Agents les plus actifs
        $top_agents = User::withCount(['scans' => function($query) {
                $query->whereMonth('date_scan', now()->month);
            }])
            ->orderBy('scans_count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.stats', compact('daily_stats', 'top_codes', 'top_agents'));
    }
}
