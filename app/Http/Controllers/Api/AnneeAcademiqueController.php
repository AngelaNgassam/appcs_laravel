<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnneeAcademique;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnneeAcademiqueController extends Controller
{
    protected $historiqueService;

    public function __construct(HistoriqueService $historiqueService)
    {
        $this->historiqueService = $historiqueService;
    }

    /**
     * @OA\Get(
     *     path="/api/annees-academiques",
     *     tags={"Années Académiques"},
     *     summary="Liste des années académiques",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="etablissement_id",
     *         in="query",
     *         description="Filtrer par établissement",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filtrer par statut actif",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(response=200, description="Liste des années académiques")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        $query = AnneeAcademique::with('etablissement');

        // Filtrer par établissement
        if ($user->isAdmin() && $request->has('etablissement_id')) {
            $query->where('etablissement_id', $request->etablissement_id);
        } elseif (!$user->isAdmin()) {
            $query->where('etablissement_id', $user->etablissement_id);
        }

        // Filtrer par statut actif
        if ($request->has('active')) {
            $query->where('active', $request->active);
        }

        $annees = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $annees->items(),
            'pagination' => [
                'total' => $annees->total(),
                'per_page' => $annees->perPage(),
                'current_page' => $annees->currentPage(),
                'last_page' => $annees->lastPage(),
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/annees-academiques",
     *     tags={"Années Académiques"},
     *     summary="Créer une année académique",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"libelle", "date_debut", "date_fin"},
     *             @OA\Property(property="libelle", type="string", example="2024-2025"),
     *             @OA\Property(property="date_debut", type="string", format="date", example="2024-09-01"),
     *             @OA\Property(property="date_fin", type="string", format="date", example="2025-07-31"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Année académique créée")
     * )
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
            'libelle' => 'required|string|max:20',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $etablissementId = $user->isAdmin() && $request->has('etablissement_id')
                ? $request->etablissement_id
                : $user->etablissement_id;

            // Vérifier si une année existe déjà avec ce libellé
            $existe = AnneeAcademique::where('etablissement_id', $etablissementId)
                ->where('libelle', $request->libelle)
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une année académique avec ce libellé existe déjà'
                ], 422);
            }

            // Si on active cette année, désactiver les autres
            if ($request->get('active', false)) {
                AnneeAcademique::where('etablissement_id', $etablissementId)
                    ->update(['active' => false]);
            }

            $annee = AnneeAcademique::create([
                'etablissement_id' => $etablissementId,
                'libelle' => $request->libelle,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
                'active' => $request->get('active', false),
            ]);

            // Historique
            $this->historiqueService->enregistrer(
                'creation_annee_academique',
                'AnneeAcademique',
                $annee->id,
                "Création de l'année académique {$annee->libelle}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Année académique créée avec succès',
                'data' => $annee->load('etablissement')
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
     * @OA\Get(
     *     path="/api/annees-academiques/{id}",
     *     tags={"Années Académiques"},
     *     summary="Détails d'une année académique",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de l'année")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $annee = AnneeAcademique::with(['etablissement', 'classes'])->find($id);

        if (!$annee) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && $annee->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        // Statistiques
        $stats = [
            'total_classes' => $annee->classes()->count(),
            'total_eleves' => $annee->classes()->withCount('elevesActifs')->get()->sum('eleves_actifs_count'),
        ];

        return response()->json([
            'success' => true,
            'data' => $annee,
            'statistiques' => $stats
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/annees-academiques/{id}",
     *     tags={"Années Académiques"},
     *     summary="Mettre à jour une année académique",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="libelle", type="string"),
     *             @OA\Property(property="date_debut", type="string", format="date"),
     *             @OA\Property(property="date_fin", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Année mise à jour")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $annee = AnneeAcademique::find($id);

        if (!$annee) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        if (!$user->isAdmin() && $annee->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'libelle' => 'sometimes|string|max:20',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after:date_debut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $annee->update($request->only(['libelle', 'date_debut', 'date_fin']));

            // Historique
            $this->historiqueService->enregistrer(
                'modification_annee_academique',
                'AnneeAcademique',
                $annee->id,
                "Modification de l'année académique {$annee->libelle}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Année académique mise à jour avec succès',
                'data' => $annee
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
     * @OA\Patch(
     *     path="/api/annees-academiques/{id}/activer",
     *     tags={"Années Académiques"},
     *     summary="Activer une année académique",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Année activée")
     * )
     */
    public function activer(Request $request, $id)
    {
        $user = $request->user();

        $annee = AnneeAcademique::find($id);

        if (!$annee) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        try {
            // Désactiver toutes les autres années de cet établissement
            AnneeAcademique::where('etablissement_id', $annee->etablissement_id)
                ->where('id', '!=', $annee->id)
                ->update(['active' => false]);

            // Activer celle-ci
            $annee->active = true;
            $annee->save();

            // Historique
            $this->historiqueService->enregistrer(
                'activation_annee_academique',
                'AnneeAcademique',
                $annee->id,
                "Activation de l'année académique {$annee->libelle}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Année académique activée avec succès',
                'data' => $annee
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/annees-academiques/active",
     *     tags={"Années Académiques"},
     *     summary="Obtenir l'année académique active",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Année active")
     * )
     */
    public function active(Request $request)
    {
        $user = $request->user();

        $annee = AnneeAcademique::where('etablissement_id', $user->etablissement_id)
            ->where('active', true)
            ->with(['etablissement', 'classes'])
            ->first();

        if (!$annee) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune année académique active'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $annee
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/annees-academiques/{id}",
     *     tags={"Années Académiques"},
     *     summary="Supprimer une année académique",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Année supprimée")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $annee = AnneeAcademique::find($id);

        if (!$annee) {
            return response()->json([
                'success' => false,
                'message' => 'Année académique non trouvée'
            ], 404);
        }

        // Seuls le proviseur et l'admin peuvent supprimer
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        // Vérifier qu'il n'y a pas de classes
        if ($annee->classes()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une année académique contenant des classes'
            ], 422);
        }

        // Historique
        $this->historiqueService->enregistrer(
            'suppression_annee_academique',
            'AnneeAcademique',
            $annee->id,
            "Suppression de l'année académique {$annee->libelle}"
        );

        $annee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Année académique supprimée avec succès'
        ], 200);
    }
}
