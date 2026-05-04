<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ✅ Imports des controllers
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ClasseController;
use App\Http\Controllers\Api\EleveController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EtablissementController;
use App\Http\Controllers\Api\AnneeAcademiqueController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\CarteController;
use App\Http\Controllers\Api\ModeleCarteController;
use App\Http\Controllers\Api\HistoriqueController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================== ROUTES PUBLIQUES ====================
Route::prefix('v1')->group(function () {
    // Authentification
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Prévisualisation de carte (authentification manuelle via token en paramètre)
    Route::get('/cartes/{id}/previsualiser', [CarteController::class, 'previsualiser']);
});



// ==================== ROUTES PROTÉGÉES (SANCTUM) ====================
Route::prefix('v1')->middleware(['auth:sanctum', 'actif'])->group(function () {

    // ========== AUTHENTIFICATION ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ========== DASHBOARDS ==========
    Route::get('/dashboard/admin', [AdminController::class, 'dashboard'])
        ->middleware('role:admin');

    Route::get('/dashboard/proviseur', [DashboardController::class, 'proviseurDashboard'])
        ->middleware('role:admin,proviseur');

    Route::get('/dashboard/surveillant', [DashboardController::class, 'surveillantDashboard'])
        ->middleware('role:admin,surveillant');

    Route::get('/dashboard/operateur', [DashboardController::class, 'operateurDashboard'])
        ->middleware('role:admin,operateur');

    // ========== UTILISATEURS ==========
    Route::apiResource('users', UserController::class);
    Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

    // ========== ADMIN (SUPER ADMIN) ==========
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/etablissements', [AdminController::class, 'etablissements']);
        Route::patch('/etablissements/{id}/archiver', [AdminController::class, 'archiverEtablissement']);
        Route::patch('/etablissements/{id}/restaurer', [AdminController::class, 'restaurerEtablissement']);
        Route::get('/utilisateurs', [AdminController::class, 'utilisateurs']);
        Route::get('/statistiques-systeme', [AdminController::class, 'statistiquesSysteme']);
        Route::get('/rapport-activite', [AdminController::class, 'rapportActivite']);
    });

    // ========== ÉTABLISSEMENTS ==========
    Route::get('/etablissements', [EtablissementController::class, 'index'])
        ->middleware('role:admin');
    Route::get('/etablissements/{id}', [EtablissementController::class, 'show']);
    Route::put('/etablissements/{id}', [EtablissementController::class, 'update'])
        ->middleware('role:admin,proviseur');
    Route::post('/etablissements/{id}/logo', [EtablissementController::class, 'updateLogo'])
        ->middleware('role:proviseur');
    Route::get('/etablissements/{id}/statistiques', [EtablissementController::class, 'statistiques']);

    // ========== ANNÉES ACADÉMIQUES ==========
    Route::apiResource('annees-academiques', AnneeAcademiqueController::class);
    Route::patch('/annees-academiques/{id}/activer', [AnneeAcademiqueController::class, 'activer'])
        ->middleware('role:admin,proviseur');
    Route::get('/annees-academiques/active/current', [AnneeAcademiqueController::class, 'active']);

    // ========== CLASSES ==========
    Route::apiResource('classes', ClasseController::class);
    Route::get('/classes/{id}/eleves', [ClasseController::class, 'eleves']);

    // ========== ÉLÈVES ==========
    Route::apiResource('eleves', EleveController::class);
    Route::post('/eleves/import', [EleveController::class, 'importExcel'])
        ->middleware('role:admin,proviseur');
    Route::patch('/eleves/{id}/archiver', [EleveController::class, 'archiver'])
        ->middleware('role:admin,proviseur,surveillant');
    Route::patch('/eleves/{id}/desarchiver', [EleveController::class, 'desarchiver'])
        ->middleware('role:admin,proviseur');


    // ========== PHOTOS ==========
    Route::apiResource('photos', PhotoController::class);
    Route::patch('/photos/{id}/valider', [PhotoController::class, 'valider'])
        ->middleware('role:admin,proviseur,surveillant');
    Route::patch('/photos/{id}/refuser', [PhotoController::class, 'refuser'])
        ->middleware('role:admin,proviseur,surveillant');
    Route::get('/photos/stats/global', [PhotoController::class, 'statistiques']);

    // ========== CARTES SCOLAIRES ==========
    Route::get('/cartes', [CarteController::class, 'index']);
    Route::get('/cartes/classe/{classe_id}', [CarteController::class, 'elevesByClasse']);
    Route::get('/cartes/{id}', [CarteController::class, 'show']);
    Route::post('/cartes/generer/{eleve_id}', [CarteController::class, 'genererCarte'])
        ->middleware('role:admin,proviseur');
    Route::post('/cartes/generer-classe/{classe_id}', [CarteController::class, 'genererCartesClasse'])
        ->middleware('role:admin,proviseur');
    Route::post('/cartes/generer-planche', [CarteController::class, 'genererPlanche'])
        ->middleware('role:admin,proviseur');
    Route::get('/cartes/{id}/telecharger', [CarteController::class, 'telecharger']);
    Route::patch('/cartes/{id}/imprimer', [CarteController::class, 'marquerCommeImprimee'])
        ->middleware('role:admin,proviseur');
    Route::patch('/cartes/{id}/distribuer', [CarteController::class, 'marquerCommeDistribuee'])
        ->middleware('role:admin,proviseur,surveillant');
    Route::get('/cartes/stats/global', [CarteController::class, 'statistiques']);

    // ========== MODÈLES DE CARTES ==========
    Route::apiResource('modeles-cartes', ModeleCarteController::class);
    Route::patch('/modeles-cartes/{id}/definir-par-defaut', [ModeleCarteController::class, 'definirParDefaut'])
        ->middleware('role:admin,proviseur');

    // ========== HISTORIQUE ==========
    Route::get('/historique', [HistoriqueController::class, 'index'])
        ->middleware('role:admin,proviseur,surveillant');
    Route::get('/historique/{id}', [HistoriqueController::class, 'show'])
        ->middleware('role:admin,proviseur');
    Route::get('/historique/stats/global', [HistoriqueController::class, 'statistiques'])
        ->middleware('role:admin,proviseur');

    // ========== NOTIFICATIONS ==========
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/lire', [NotificationController::class, 'marquerCommeLue']);
    Route::patch('/notifications/tout-lire', [NotificationController::class, 'marquerToutesCommeLues']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

// ==================== ROUTE DE TEST ====================
Route::get('/test', function () {
    return response()->json([
        'message' => 'API Cartes Scolaires fonctionne !',
        'version' => '1.0.0',
        'timestamp' => now()
    ]);
});
