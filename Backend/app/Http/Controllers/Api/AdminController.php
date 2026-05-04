<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Etablissement;
use App\Models\Eleve;
use App\Models\CarteScolaire;
use App\Models\Photo;
use App\Models\HistoriqueAction;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Dashboard principal du super admin
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        try {
            // Statistiques globales
            $stats = [
                'etablissements' => [
                    'total' => Etablissement::count(),
                    'actifs' => Etablissement::whereHas('utilisateurs', function($q) {
                        $q->where('actif', true);
                    })->count(),
                    'archives' => Etablissement::onlyTrashed()->count(),
                    'nouveau_mois' => Etablissement::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count(),
                ],
                'utilisateurs' => [
                    'total' => User::count(),
                    'actifs' => User::where('actif', true)->count(),
                    'inactifs' => User::where('actif', false)->count(),
                    'par_role' => User::select('role', DB::raw('count(*) as total'))
                        ->groupBy('role')
                        ->get()
                        ->pluck('total', 'role'),
                ],
                'eleves' => [
                    'total' => Eleve::count(),
                    'actifs' => Eleve::where('archive', false)->count(),
                    'archives' => Eleve::onlyTrashed()->count(),
                ],
                'cartes' => [
                    'total' => CarteScolaire::count(),
                    'generees' => CarteScolaire::where('statut', 'generee')->count(),
                    'imprimees' => CarteScolaire::where('statut', 'imprimee')->count(),
                    'distribuees' => CarteScolaire::where('statut', 'distribuee')->count(),
                ],
                'photos' => [
                    'total' => Photo::count(),
                    'en_attente' => Photo::where('statut', 'en_attente')->count(),
                    'validees' => Photo::where('statut', 'validee')->count(),
                    'refusees' => Photo::where('statut', 'refusee')->count(),
                ],
            ];

            // Évolution des établissements (12 derniers mois)
            $evolutionEtablissements = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $evolutionEtablissements[] = [
                    'mois' => $date->format('M Y'),
                    'total' => Etablissement::whereYear('created_at', '<=', $date->year)
                        ->whereMonth('created_at', '<=', $date->month)
                        ->count(),
                    'nouveaux' => Etablissement::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count(),
                ];
            }

            // Évolution des cartes (30 derniers jours)
            $evolutionCartes = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $evolutionCartes[] = [
                    'date' => $date->format('d/m'),
                    'generees' => CarteScolaire::whereDate('created_at', $date)->count(),
                ];
            }

            // Top 10 établissements par nombre d'élèves
            $topEtablissements = Etablissement::withCount('eleves')
                ->orderBy('eleves_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function($etab) {
                    return [
                        'id' => $etab->id,
                        'nom' => $etab->nom,
                        'ville' => $etab->ville,
                        'eleves_count' => $etab->eleves_count,
                    ];
                });

            // Activités récentes (50 dernières)
            $activitesRecentes = HistoriqueAction::with(['user:id,nom,prenom,role'])
                ->latest()
                ->limit(50)
                ->get()
                ->map(function($action) {
                    return [
                        'id' => $action->id,
                        'action' => $action->action,
                        'description' => $action->description,
                        'user' => $action->user ? $action->user->nom_complet : 'Système',
                        'role' => $action->user ? $action->user->role : null,
                        'created_at' => $action->created_at->format('d/m/Y H:i'),
                    ];
                });

            // Répartition géographique
            $repartitionGeographique = Etablissement::select('ville', DB::raw('count(*) as total'))
                ->groupBy('ville')
                ->orderBy('total', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'statistiques' => $stats,
                    'evolution_etablissements' => $evolutionEtablissements,
                    'evolution_cartes' => $evolutionCartes,
                    'top_etablissements' => $topEtablissements,
                    'activites_recentes' => $activitesRecentes,
                    'repartition_geographique' => $repartitionGeographique,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur dashboard admin', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du dashboard'
            ], 500);
        }
    }

    /**
     * Liste des établissements avec statistiques
     */
    public function etablissements(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        $search = $request->get('search');
        $ville = $request->get('ville');
        $includeArchived = $request->get('include_archived', false);

        $query = Etablissement::with(['proviseur:id,nom,prenom,email'])
            ->withCount(['utilisateurs', 'eleves', 'classes']);

        if ($includeArchived) {
            $query->withTrashed();
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('ville', 'like', "%{$search}%");
            });
        }

        if ($ville) {
            $query->where('ville', $ville);
        }

        $etablissements = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $etablissements->items(),
            'pagination' => [
                'total' => $etablissements->total(),
                'per_page' => $etablissements->perPage(),
                'current_page' => $etablissements->currentPage(),
                'last_page' => $etablissements->lastPage(),
            ]
        ]);
    }

    /**
     * Archiver un établissement
     */
    public function archiverEtablissement(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $etablissement = Etablissement::find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], 404);
        }

        try {
            // Archiver l'établissement (soft delete)
            $etablissement->delete();

            // Désactiver tous les utilisateurs de cet établissement
            User::where('etablissement_id', $id)->update(['actif' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Établissement archivé avec succès'
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur archivage établissement', [
                'etablissement_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage'
            ], 500);
        }
    }

    /**
     * Restaurer un établissement archivé
     */
    public function restaurerEtablissement(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $etablissement = Etablissement::onlyTrashed()->find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Établissement archivé non trouvé'
            ], 404);
        }

        try {
            $etablissement->restore();

            return response()->json([
                'success' => true,
                'message' => 'Établissement restauré avec succès'
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur restauration établissement', [
                'etablissement_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration'
            ], 500);
        }
    }

    /**
     * Liste des utilisateurs (tous établissements)
     */
    public function utilisateurs(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        $search = $request->get('search');
        $role = $request->get('role');
        $etablissementId = $request->get('etablissement_id');
        $actif = $request->get('actif');

        $query = User::with(['etablissement:id,nom,ville']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($etablissementId) {
            $query->where('etablissement_id', $etablissementId);
        }

        if ($actif !== null) {
            $query->where('actif', filter_var($actif, FILTER_VALIDATE_BOOLEAN));
        }

        $utilisateurs = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $utilisateurs->items(),
            'pagination' => [
                'total' => $utilisateurs->total(),
                'per_page' => $utilisateurs->perPage(),
                'current_page' => $utilisateurs->currentPage(),
                'last_page' => $utilisateurs->lastPage(),
            ]
        ]);
    }

    /**
     * Statistiques système avancées
     */
    public function statistiquesSysteme(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        try {
            // Performance par établissement
            $performanceEtablissements = Etablissement::select('etablissements.*')
                ->withCount([
                    'eleves',
                    'classes',
                    'utilisateurs',
                ])
                ->with(['proviseur:id,nom,prenom'])
                ->get()
                ->map(function($etab) {
                    $cartesGenerees = CarteScolaire::whereHas('eleve', function($q) use ($etab) {
                        $q->where('etablissement_id', $etab->id);
                    })->count();

                    $tauxGeneration = $etab->eleves_count > 0 
                        ? round(($cartesGenerees / $etab->eleves_count) * 100, 2)
                        : 0;

                    return [
                        'id' => $etab->id,
                        'nom' => $etab->nom,
                        'ville' => $etab->ville,
                        'proviseur' => $etab->proviseur ? $etab->proviseur->nom_complet : 'N/A',
                        'eleves' => $etab->eleves_count,
                        'classes' => $etab->classes_count,
                        'utilisateurs' => $etab->utilisateurs_count,
                        'cartes_generees' => $cartesGenerees,
                        'taux_generation' => $tauxGeneration,
                    ];
                });

            // Statistiques par rôle
            $statsParRole = User::select('role', DB::raw('count(*) as total'))
                ->where('actif', true)
                ->groupBy('role')
                ->get();

            // Activité par jour (7 derniers jours)
            $activiteJournaliere = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $activiteJournaliere[] = [
                    'date' => $date->format('d/m'),
                    'actions' => HistoriqueAction::whereDate('created_at', $date)->count(),
                    'cartes' => CarteScolaire::whereDate('created_at', $date)->count(),
                    'photos' => Photo::whereDate('created_at', $date)->count(),
                ];
            }

            // Taux d'utilisation
            $tauxUtilisation = [
                'cartes_generees' => CarteScolaire::count(),
                'eleves_total' => Eleve::count(),
                'taux' => Eleve::count() > 0 
                    ? round((CarteScolaire::count() / Eleve::count()) * 100, 2)
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'performance_etablissements' => $performanceEtablissements,
                    'stats_par_role' => $statsParRole,
                    'activite_journaliere' => $activiteJournaliere,
                    'taux_utilisation' => $tauxUtilisation,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur statistiques système', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques'
            ], 500);
        }
    }

    /**
     * Rapport d'activité global
     */
    public function rapportActivite(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $dateDebut = $request->get('date_debut', now()->subMonth()->format('Y-m-d'));
        $dateFin = $request->get('date_fin', now()->format('Y-m-d'));

        try {
            $rapport = [
                'periode' => [
                    'debut' => $dateDebut,
                    'fin' => $dateFin,
                ],
                'etablissements_crees' => Etablissement::whereBetween('created_at', [$dateDebut, $dateFin])->count(),
                'utilisateurs_crees' => User::whereBetween('created_at', [$dateDebut, $dateFin])->count(),
                'eleves_inscrits' => Eleve::whereBetween('created_at', [$dateDebut, $dateFin])->count(),
                'cartes_generees' => CarteScolaire::whereBetween('created_at', [$dateDebut, $dateFin])->count(),
                'photos_validees' => Photo::where('statut', 'validee')
                    ->whereBetween('updated_at', [$dateDebut, $dateFin])
                    ->count(),
                'actions_total' => HistoriqueAction::whereBetween('created_at', [$dateDebut, $dateFin])->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $rapport
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur rapport activité', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport'
            ], 500);
        }
    }
}
