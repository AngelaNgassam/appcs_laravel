<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Eleve;
use App\Services\PhotoService;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PhotoController extends Controller
{
    protected $photoService;
    protected $historiqueService;

    public function __construct(
        PhotoService $photoService,
        HistoriqueService $historiqueService
    ) {
        $this->photoService = $photoService;
        $this->historiqueService = $historiqueService;
    }

    /**
     * @OA\Get(
     *     path="/api/photos",
     *     tags={"Photos"},
     *     summary="Liste des photos avec pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="eleve_id",
     *         in="query",
     *         description="Filtrer par élève",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"brouillon", "validee", "refusee", "archivee"})
     *     ),
     *     @OA\Response(response=200, description="Liste des photos")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $query = Photo::with(['eleve.classe', 'operateur']);

        // Filtrer par établissement
        if (!$user->isAdmin()) {
            $query->whereHas('eleve', function($q) use ($user) {
                $q->where('etablissement_id', $user->etablissement_id);
            });
        }

        // Filtrer par opérateur (si c'est un opérateur)
        if ($user->isOperateur()) {
            $query->where('operateur_id', $user->id);
        }

        // Filtrer par élève
        if ($request->has('eleve_id')) {
            $query->where('eleve_id', $request->eleve_id);
        }

        // Filtrer par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Seulement les photos actives par défaut
        if (!$request->get('include_archived', false)) {
            $query->where('active', true);
        }

        $photos = $query->latest('date_prise')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $photos->items(),
            'pagination' => [
                'total' => $photos->total(),
                'per_page' => $photos->perPage(),
                'current_page' => $photos->currentPage(),
                'last_page' => $photos->lastPage(),
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/photos",
     *     tags={"Photos"},
     *     summary="Uploader une photo d'élève",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photo", "eleve_id"},
     *                 @OA\Property(property="photo", type="string", format="binary"),
     *                 @OA\Property(property="eleve_id", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Photo uploadée")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Seul l'opérateur peut uploader des photos
        if (!$user->isOperateur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un opérateur photo peut uploader des photos'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
            'eleve_id' => 'required|exists:eleves,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier que l'élève appartient au même établissement
            $eleve = Eleve::find($request->eleve_id);

            if (!$user->isAdmin() && $eleve->etablissement_id !== $user->etablissement_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'appartient pas à votre établissement'
                ], 403);
            }

            // Archiver l'ancienne photo si elle existe
            if ($eleve->photoActive) {
                $eleve->photoActive->archiver();
            }

            // Traiter et enregistrer la photo
            $photo = $this->photoService->traiterPhoto(
                $request->file('photo'),
                $request->eleve_id,
                $user->id
            );

            // Historique
            $this->historiqueService->enregistrer(
                'prise_photo',
                'Photo',
                $photo->id,
                "Photo prise pour l'élève {$eleve->nom_complet} par {$user->nom_complet}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Photo uploadée et traitée avec succès',
                'data' => $photo->load(['eleve', 'operateur'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/photos/{id}",
     *     tags={"Photos"},
     *     summary="Détails d'une photo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de la photo")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $photo = Photo::with(['eleve.classe', 'operateur'])->find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && $photo->eleve->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $photo
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/photos/{id}/valider",
     *     tags={"Photos"},
     *     summary="Valider une photo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Photo validée")
     * )
     */
    public function valider(Request $request, $id)
    {
        $user = $request->user();

        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo non trouvée'
            ], 404);
        }

        // Seuls le surveillant et le proviseur peuvent valider
        if (!$user->isSurveillant() && !$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les droits pour valider une photo'
            ], 403);
        }

        $photo->valider();

        // Historique
        $this->historiqueService->enregistrer(
            'validation_photo',
            'Photo',
            $photo->id,
            "Photo validée pour l'élève {$photo->eleve->nom_complet}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Photo validée avec succès',
            'data' => $photo
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/photos/{id}/refuser",
     *     tags={"Photos"},
     *     summary="Refuser une photo",
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
     *             required={"motif"},
     *             @OA\Property(property="motif", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Photo refusée")
     * )
     */
    public function refuser(Request $request, $id)
    {
        $user = $request->user();

        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo non trouvée'
            ], 404);
        }

        // Seuls le surveillant et le proviseur peuvent refuser
        if (!$user->isSurveillant() && !$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les droits pour refuser une photo'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'motif' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Le motif du refus est requis',
                'errors' => $validator->errors()
            ], 422);
        }

        $photo->refuser($request->motif);

        // Historique
        $this->historiqueService->enregistrer(
            'refus_photo',
            'Photo',
            $photo->id,
            "Photo refusée pour l'élève {$photo->eleve->nom_complet} - Motif: {$request->motif}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Photo refusée',
            'data' => $photo
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/photos/{id}",
     *     tags={"Photos"},
     *     summary="Supprimer une photo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Photo supprimée")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json([
                'success' => false,
                'message' => 'Photo non trouvée'
            ], 404);
        }

        // Seul l'admin peut supprimer définitivement
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'administrateur peut supprimer définitivement une photo'
            ], 403);
        }

        // Historique
        $this->historiqueService->enregistrer(
            'suppression_photo',
            'Photo',
            $photo->id,
            "Suppression de la photo de l'élève {$photo->eleve->nom_complet}"
        );

        $this->photoService->supprimerPhoto($photo);

        return response()->json([
            'success' => true,
            'message' => 'Photo supprimée avec succès'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/photos/statistiques",
     *     tags={"Photos"},
     *     summary="Statistiques des photos",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Statistiques")
     * )
     */
    public function statistiques(Request $request)
    {
        $user = $request->user();

        $query = Photo::query();

        // Filtrer par établissement
        if (!$user->isAdmin()) {
            $query->whereHas('eleve', function($q) use ($user) {
                $q->where('etablissement_id', $user->etablissement_id);
            });
        }

        // Filtrer par opérateur si c'est un opérateur
        if ($user->isOperateur()) {
            $query->where('operateur_id', $user->id);
        }

        $stats = [
            'total' => $query->count(),
            'validees' => $query->where('statut', 'validee')->count(),
            'refusees' => $query->where('statut', 'refusee')->count(),
            'brouillon' => $query->where('statut', 'brouillon')->count(),
            'aujourd_hui' => $query->whereDate('date_prise', today())->count(),
            'cette_semaine' => $query->whereBetween('date_prise', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'ce_mois' => $query->whereMonth('date_prise', now()->month)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }
}
