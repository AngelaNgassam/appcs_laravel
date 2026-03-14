<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EtablissementController extends Controller
{
    protected $historiqueService;

    public function __construct(HistoriqueService $historiqueService)
    {
        $this->historiqueService = $historiqueService;
    }

    /**
     * @OA\Get(
     *     path="/api/etablissements",
     *     tags={"Etablissements"},
     *     summary="Liste des établissements (Admin uniquement)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Liste des établissements")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Seul l'admin peut voir tous les établissements
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $perPage = $request->get('per_page', 15);

        $query = Etablissement::with(['proviseur', 'anneeActive']);

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('ville', 'like', "%{$search}%");
            });
        }

        // Filtrer par ville
        if ($request->has('ville')) {
            $query->where('ville', $request->ville);
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
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/etablissements/{id}",
     *     tags={"Etablissements"},
     *     summary="Détails d'un établissement",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de l'établissement")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $etablissement = Etablissement::with([
            'proviseur',
            'anneeActive',
            'classes',
            'eleves'
        ])->find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && $user->etablissement_id !== $etablissement->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        // Statistiques
        $stats = [
            'total_eleves' => $etablissement->eleves()->where('archive', false)->count(),
            'total_classes' => $etablissement->classes()->count(),
            'total_utilisateurs' => $etablissement->utilisateurs()->count(),
            'eleves_avec_photo' => $etablissement->eleves()->whereHas('photoActive')->count(),
            'cartes_generees' => $etablissement->eleves()
                ->whereHas('carteActive', function($q) {
                    $q->where('statut', '!=', 'en_attente');
                })->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $etablissement,
            'statistiques' => $stats
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/etablissements/{id}",
     *     tags={"Etablissements"},
     *     summary="Mettre à jour un établissement",
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
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="ville", type="string"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Établissement mis à jour")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $etablissement = Etablissement::find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && !$user->isProviseur()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        if ($user->isProviseur() && $user->etablissement_id !== $etablissement->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que votre établissement'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:200',
            'adresse' => 'sometimes|string',
            'ville' => 'sometimes|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $etablissement->update($request->only([
                'nom', 'adresse', 'ville', 'telephone', 'email'
            ]));

            // Historique
            $this->historiqueService->enregistrer(
                'modification_etablissement',
                'Etablissement',
                $etablissement->id,
                "Modification de l'établissement {$etablissement->nom}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Établissement mis à jour avec succès',
                'data' => $etablissement
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
     * @OA\Post(
     *     path="/api/etablissements/{id}/logo",
     *     tags={"Etablissements"},
     *     summary="Mettre à jour le logo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="logo", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Logo mis à jour")
     * )
     */
    public function updateLogo(Request $request, $id)
    {
        $user = $request->user();

        $etablissement = Etablissement::find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isProviseur() || $user->etablissement_id !== $etablissement->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Supprimer l'ancien logo
            if ($etablissement->logo) {
                Storage::disk('public')->delete($etablissement->logo);
            }

            // Sauvegarder le nouveau logo
            $logoPath = $request->file('logo')->store('logos', 'public');

            $etablissement->logo = $logoPath;
            $etablissement->save();

            // Historique
            $this->historiqueService->enregistrer(
                'modification_logo',
                'Etablissement',
                $etablissement->id,
                "Modification du logo de {$etablissement->nom}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Logo mis à jour avec succès',
                'logo_url' => $etablissement->logo_url
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/etablissements/{id}/statistiques",
     *     tags={"Etablissements"},
     *     summary="Statistiques détaillées d'un établissement",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Statistiques")
     * )
     */
    public function statistiques(Request $request, $id)
    {
        $user = $request->user();

        $etablissement = Etablissement::find($id);

        if (!$etablissement) {
            return response()->json([
                'success' => false,
                'message' => 'Établissement non trouvé'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && $user->etablissement_id !== $etablissement->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $stats = [
            'eleves' => [
                'total' => $etablissement->eleves()->count(),
                'actifs' => $etablissement->eleves()->where('archive', false)->count(),
                'archives' => $etablissement->eleves()->where('archive', true)->count(),
                'avec_photo' => $etablissement->eleves()->whereHas('photoActive')->count(),
                'sans_photo' => $etablissement->eleves()
                    ->where('archive', false)
                    ->whereDoesntHave('photoActive')->count(),
            ],
            'classes' => [
                'total' => $etablissement->classes()->count(),
                'par_niveau' => $etablissement->classes()
                    ->selectRaw('niveau, count(*) as total')
                    ->groupBy('niveau')
                    ->get(),
            ],
            'cartes' => [
                'total' => $etablissement->eleves()->whereHas('carteActive')->count(),
                'en_attente' => $etablissement->eleves()
                    ->whereHas('carteActive', function($q) {
                        $q->where('statut', 'en_attente');
                    })->count(),
                'generees' => $etablissement->eleves()
                    ->whereHas('carteActive', function($q) {
                        $q->where('statut', 'generee');
                    })->count(),
                'imprimees' => $etablissement->eleves()
                    ->whereHas('carteActive', function($q) {
                        $q->where('statut', 'imprimee');
                    })->count(),
                'distribuees' => $etablissement->eleves()
                    ->whereHas('carteActive', function($q) {
                        $q->where('statut', 'distribuee');
                    })->count(),
            ],
            'utilisateurs' => [
                'total' => $etablissement->utilisateurs()->count(),
                'proviseurs' => $etablissement->utilisateurs()->where('role', 'proviseur')->count(),
                'surveillants' => $etablissement->utilisateurs()->where('role', 'surveillant')->count(),
                'operateurs' => $etablissement->utilisateurs()->where('role', 'operateur')->count(),
                'actifs' => $etablissement->utilisateurs()->where('actif', true)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }
}
