

use App\Http\Controllers\QrcodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Route pour l'accueil (redirection vers login)
Route::get('/', function () {
    return redirect()->route('login');
});

// Routes d'authentification
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Registration routes - both GET and POST
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// Routes protégées par authentification
Route::middleware(['auth'])->group(function () {
    // Page d'accueil après connexion
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', function() {
        return redirect()->route('home');
    })->name('dashboard');

    // Tableaux de bord
    Route::get('/admin-dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/entry-dashboard', [DashboardController::class, 'entry'])->name('entry.dashboard');
    Route::get('/exit-dashboard', [DashboardController::class, 'exit'])->name('exit.dashboard');

    // QR Code - Formulaire de scan
    Route::get('/scan', [QrcodeController::class, 'showScanForm'])->name('qrcode.scan.form');

    // QR Code - Traitement du scan
    Route::post('/scan', [QrcodeController::class, 'scan'])->name('qrcode.scan');

    // Historique des scans
    Route::get('/historique', [QrcodeController::class, 'historique'])->name('qrcode.historique');
});

// Routes administratives
Route::middleware(['auth', 'admin'])->group(function () {
    // Gestion des agents
    Route::get('/create-agent', [DashboardController::class, 'createAgent'])->name('create-agent');
    Route::post('/store-agent', [DashboardController::class, 'storeAgent'])->name('store-agent');

    // Gestion des utilisateurs
    Route::get('/admin/users', [DashboardController::class, 'manageUsers'])->name('admin.users');
    Route::get('/admin/stats', [DashboardController::class, 'stats'])->name('admin.stats');

    // Nouvelles routes pour la gestion des rôles
    Route::get('/admin/roles', [QrcodeController::class, 'manageRoles'])->name('admin.roles');
    Route::post('/admin/users/update-role', [QrcodeController::class, 'updateUserRole'])->name('admin.users.update-role');
    Route::delete('/admin/users/delete', [QrcodeController::class, 'deleteUser'])->name('admin.users.delete');
});
