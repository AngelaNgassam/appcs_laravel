<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\AnneeAcademique;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ClasseController extends Controller
{
    protected $historiqueService;

    public function __construct(HistoriqueService $historiqueService)
    {
        $this->historiqueService = $historiqueService;
    }

    /**
     * 📋 Liste des classes
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        $query = Classe::with(['etablissement', 'anneeAcademique']);

        // Filtrer par établissement
        if (!$user->isAdmin()) {
            $query->where('etablissement_id', $user->etablissement_id);
        }

        // Filtrer par niveau
        if ($request->has('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        // Filtrer par année académique active
        if ($request->get('annee_active', false)) {
            $query->whereHas('anneeAcademique', function($q) {
                $q->where('active', true);
            });
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('niveau', 'like', "%{$search}%");
            });
        }

        $classes = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $classes->items(),
            'pagination' => [
                'total' => $classes->total(),
                'per_page' => $classes->perPage(),
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
            ]
        ], 200);
    }

    /**
     * ➕ Créer une classe
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Seuls le proviseur et l'admin peuvent créer
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'niveau' => 'required|string|max:50',
            'serie' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Récupérer l'année académique active
            $anneeActive = AnneeAcademique::where('etablissement_id', $user->etablissement_id)
                ->where('active', true)
                ->first();

            if (!$anneeActive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune année académique active trouvée'
                ], 400);
            }

            // Vérifier que la classe n'existe pas déjà
            $classeExiste = Classe::where('etablissement_id', $user->etablissement_id)
                ->where('annee_academique_id', $anneeActive->id)
                ->where('nom', $request->nom)
                ->exists();

            if ($classeExiste) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une classe avec ce nom existe déjà pour cette année'
                ], 422);
            }

            $classe = Classe::create([
                'etablissement_id' => $user->etablissement_id,
                'annee_academique_id' => $anneeActive->id,
                'nom' => $request->nom,
                'niveau' => $request->niveau,
                'serie' => $request->serie,
                'effectif' => 0,
            ]);

            // Historique
            $this->historiqueService->enregistrer(
                'creation_classe',
                'Classe',
                $classe->id,
                "Création de la classe {$classe->nom}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Classe créée avec succès',
                'data' => $classe->load(['etablissement', 'anneeAcademique'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
 * 🔍 Détails d'une classe
 */
public function show(Request $request, $id)
{
    $user = $request->user();

    $classe = Classe::find($id);

    if (!$classe) {
        return response()->json([
            'success' => false,
            'message' => 'Classe non trouvée'
        ], 404);
    }

    // Vérifier les droits
    if (!$user->isAdmin() && $classe->etablissement_id !== $user->etablissement_id) {
        return response()->json([
            'success' => false,
            'message' => 'Accès refusé'
        ], 403);
    }

    // Charger les élèves actifs
    $elevesActifs = $classe->eleves()
        ->where('archive', false)
        ->with(['photoActive', 'carteActive'])
        ->orderBy('nom')
        ->orderBy('prenom')
        ->get();

    // Charger les élèves archivés
    $elevesArchives = $classe->eleves()
        ->where('archive', true)
        ->with(['photoActive', 'carteActive'])
        ->orderBy('date_archivage', 'desc')
        ->get();

    // Statistiques
    $statistiques = [
        'total_eleves' => $classe->eleves()->count(),
        'eleves_actifs' => $elevesActifs->count(),
        'eleves_archives' => $elevesArchives->count(),
        'eleves_avec_photo' => $classe->eleves()
            ->whereHas('photoActive', function($q) {
                $q->where('statut', 'validee');
            })
            ->count(),
        'eleves_sans_photo' => $classe->eleves()
            ->whereDoesntHave('photoActive', function($q) {
                $q->where('statut', 'validee');
            })
            ->count(),
    ];

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $classe->id,
            'nom' => $classe->nom,
            'niveau' => $classe->niveau,
            'serie' => $classe->serie,
            'effectif' => $classe->effectif,
            'etablissement_id' => $classe->etablissement_id,
            'elevesActifs' => $elevesActifs,      // ✅ IMPORTANT
            'elevesArchives' => $elevesArchives,  // ✅ IMPORTANT
        ],
        'statistiques' => $statistiques
    ], 200);
}

    /**
     * ✏️ Modifier une classe
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $classe = Classe::find($id);

        if (!$classe) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:100',
            'niveau' => 'sometimes|string|max:50',
            'serie' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $classe->update($request->only(['nom', 'niveau', 'serie']));

            // Historique
            $this->historiqueService->enregistrer(
                'modification_classe',
                'Classe',
                $classe->id,
                "Modification de la classe {$classe->nom}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Classe mise à jour avec succès',
                'data' => $classe
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ Supprimer une classe
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $classe = Classe::find($id);

        if (!$classe) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        // Vérifier qu'il n'y a pas d'élèves
        if ($classe->eleves()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une classe contenant des élèves'
            ], 422);
        }

        // Historique
        $this->historiqueService->enregistrer(
            'suppression_classe',
            'Classe',
            $classe->id,
            "Suppression de la classe {$classe->nom}"
        );

        $classe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Classe supprimée avec succès'
        ], 200);
    }
}
