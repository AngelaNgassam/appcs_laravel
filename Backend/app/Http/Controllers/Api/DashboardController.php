<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\Classe;
use App\Models\User;
use App\Models\Photo;
use App\Models\CarteScolaire;
use App\Models\HistoriqueAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Tableau de bord du proviseur
     */
    public function proviseurDashboard(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isProviseur() && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], 403);
            }

            $etablissementId = $user->etablissement_id;

            // ========== KPIs PRINCIPAUX ==========
            $totalEleves = Eleve::where('etablissement_id', $etablissementId)
                ->where('archive', false)
                ->count();

            $totalClasses = Classe::where('etablissement_id', $etablissementId)->count();

            $totalUtilisateurs = User::where('etablissement_id', $etablissementId)
                ->where('actif', true)
                ->count();

            $elevesAvecPhoto = Eleve::where('etablissement_id', $etablissementId)
                ->where('archive', false)
                ->whereHas('photoActive')
                ->count();

            // ✅ GESTION SÉCURISÉE DES CARTES (peut ne pas exister encore)
            try {
                $cartesGenerees = CarteScolaire::whereHas('eleve', function($q) use ($etablissementId) {
                    $q->where('etablissement_id', $etablissementId);
                })->whereIn('statut', ['generee', 'imprimee', 'distribuee'])->count();

                $cartesImprimees = CarteScolaire::whereHas('eleve', function($q) use ($etablissementId) {
                    $q->where('etablissement_id', $etablissementId);
                })->whereIn('statut', ['imprimee', 'distribuee'])->count();

                $cartesDistribuees = CarteScolaire::whereHas('eleve', function($q) use ($etablissementId) {
                    $q->where('etablissement_id', $etablissementId);
                })->where('statut', 'distribuee')->count();
            } catch (\Exception $e) {
                Log::warning('Table cartes_scolaires non accessible : ' . $e->getMessage());
                $cartesGenerees = 0;
                $cartesImprimees = 0;
                $cartesDistribuees = 0;
            }

            // ========== STATISTIQUES DÉTAILLÉES ==========

            $elevesParStatut = [
                'total' => $totalEleves,
                'avec_photo' => $elevesAvecPhoto,
                'sans_photo' => $totalEleves - $elevesAvecPhoto,
                'archives' => Eleve::where('etablissement_id', $etablissementId)
                    ->where('archive', true)
                    ->count(),
            ];

            // Photos par statut
            $photosParStatut = Photo::whereHas('eleve', function($q) use ($etablissementId) {
                $q->where('etablissement_id', $etablissementId);
            })->where('active', true)
            ->select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get()
            ->pluck('total', 'statut')
            ->toArray();

            // Cartes par statut (avec gestion d'erreur)
            $cartesParStatut = [];
            try {
                $cartesParStatut = CarteScolaire::whereHas('eleve', function($q) use ($etablissementId) {
                    $q->where('etablissement_id', $etablissementId);
                })->select('statut', DB::raw('count(*) as total'))
                ->groupBy('statut')
                ->get()
                ->pluck('total', 'statut')
                ->toArray();
            } catch (\Exception $e) {
                // Table pas encore accessible
            }

            // Répartition par classe
            $classesStats = Classe::where('etablissement_id', $etablissementId)
                ->withCount([
                    'elevesActifs',
                    'elevesActifs as eleves_avec_photo' => function($q) {
                        $q->whereHas('photoActive');
                    }
                ])
                ->get()
                ->map(function($classe) {
                    return [
                        'id' => $classe->id,
                        'nom' => $classe->nom,
                        'niveau' => $classe->niveau,
                        'effectif' => $classe->eleves_actifs_count,
                        'avec_photo' => $classe->eleves_avec_photo,
                        'sans_photo' => $classe->eleves_actifs_count - $classe->eleves_avec_photo,
                        'taux_completion' => $classe->eleves_actifs_count > 0
                            ? round(($classe->eleves_avec_photo / $classe->eleves_actifs_count) * 100, 1)
                            : 0
                    ];
                });

            // Utilisateurs par rôle
            $utilisateursParRole = User::where('etablissement_id', $etablissementId)
                ->where('actif', true)
                ->select('role', DB::raw('count(*) as total'))
                ->groupBy('role')
                ->get()
                ->pluck('total', 'role')
                ->toArray();

            // ========== ACTIVITÉ RÉCENTE ==========
            $activiteRecente = HistoriqueAction::whereHas('user', function($q) use ($etablissementId) {
                $q->where('etablissement_id', $etablissementId);
            })
            ->with('user:id,nom,prenom,role')
            ->orderBy('date_action', 'desc')
            ->limit(10)
            ->get()
            ->map(function($action) {
                return [
                    'id' => $action->id,
                    'action' => $action->action,
                    'utilisateur' => $action->user ? "{$action->user->prenom} {$action->user->nom}" : 'Utilisateur supprimé',
                    'role' => $action->user ? $action->user->role : null,
                    'details' => $action->details,
                    'date' => $action->date_action->format('d/m/Y H:i'),
                    'date_relative' => $action->date_action->diffForHumans(),
                ];
            });

            // ========== PROGRESSION (derniers 7 jours) ==========
            $progressionPhotos = Photo::whereHas('eleve', function($q) use ($etablissementId) {
                $q->where('etablissement_id', $etablissementId);
            })
            ->where('date_prise', '>=', now()->subDays(7))
            ->select(DB::raw('DATE(date_prise) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'total' => $item->total
                ];
            });

            $progressionCartes = [];
            try {
                $progressionCartes = CarteScolaire::whereHas('eleve', function($q) use ($etablissementId) {
                    $q->where('etablissement_id', $etablissementId);
                })
                ->where('date_generation', '>=', now()->subDays(7))
                ->whereNotNull('date_generation')
                ->select(DB::raw('DATE(date_generation) as date'), DB::raw('count(*) as total'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function($item) {
                    return [
                        'date' => $item->date,
                        'total' => $item->total
                    ];
                });
            } catch (\Exception $e) {
                // Pas encore de cartes
            }

            // ========== TAUX DE COMPLÉTION ==========
            $tauxCompletion = [
                'photos' => $totalEleves > 0 ? round(($elevesAvecPhoto / $totalEleves) * 100, 1) : 0,
                'cartes_generees' => $totalEleves > 0 ? round(($cartesGenerees / $totalEleves) * 100, 1) : 0,
                'cartes_imprimees' => $totalEleves > 0 ? round(($cartesImprimees / $totalEleves) * 100, 1) : 0,
                'cartes_distribuees' => $totalEleves > 0 ? round(($cartesDistribuees / $totalEleves) * 100, 1) : 0,
            ];

            // ========== RÉPONSE ==========
            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => [
                        'total_eleves' => $totalEleves,
                        'total_classes' => $totalClasses,
                        'total_utilisateurs' => $totalUtilisateurs,
                        'eleves_avec_photo' => $elevesAvecPhoto,
                        'cartes_generees' => $cartesGenerees,
                        'cartes_imprimees' => $cartesImprimees,
                        'cartes_distribuees' => $cartesDistribuees,
                    ],
                    'statistiques' => [
                        'eleves' => $elevesParStatut,
                        'photos' => $photosParStatut,
                        'cartes' => $cartesParStatut,
                        'utilisateurs' => $utilisateursParRole,
                        'taux_completion' => $tauxCompletion,
                    ],
                    'classes' => $classesStats,
                    'activite_recente' => $activiteRecente,
                    'progression' => [
                        'photos' => $progressionPhotos,
                        'cartes' => $progressionCartes,
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur Dashboard Proviseur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
    /**
     * 📊 Dashboard du SURVEILLANT GÉNÉRAL
     */
    public function surveillantDashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isSurveillant() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $etablissementId = $user->etablissement_id;

        // KPIs
        $totalEleves = Eleve::where('etablissement_id', $etablissementId)
            ->where('archive', false)
            ->count();

        $elevesAvecPhoto = Eleve::where('etablissement_id', $etablissementId)
            ->where('archive', false)
            ->whereHas('photoActive')
            ->count();

        $photosAValider = Photo::whereHas('eleve', function($q) use ($etablissementId) {
            $q->where('etablissement_id', $etablissementId);
        })->where('statut', 'brouillon')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'total_eleves' => $totalEleves,
                    'eleves_avec_photo' => $elevesAvecPhoto,
                    'photos_a_valider' => $photosAValider,
                ],
            ]
        ], 200);
    }

    /**
     * 📸 Dashboard de l'OPÉRATEUR PHOTO
     */
    public function operateurDashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isOperateur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $etablissementId = $user->etablissement_id;

        $mesPhotos = Photo::where('operateur_id', $user->id)->count();
        $photosAujourdhui = Photo::where('operateur_id', $user->id)
            ->whereDate('date_prise', today())
            ->count();

        $elevesRestants = Eleve::where('etablissement_id', $etablissementId)
            ->where('archive', false)
            ->whereDoesntHave('photoActive')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => [
                    'mes_photos' => $mesPhotos,
                    'photos_aujourdhui' => $photosAujourdhui,
                    'eleves_restants' => $elevesRestants,
                ],
            ]
        ], 200);
    }
}
