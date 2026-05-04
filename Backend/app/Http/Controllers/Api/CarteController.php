<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarteScolaire;
use App\Models\Eleve;
use App\Models\Classe;
use App\Services\PDFService;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarteController extends Controller
{
    protected $pdfService;
    protected $historiqueService;

    public function __construct(
        PDFService $pdfService,
        HistoriqueService $historiqueService
    ) {
        $this->pdfService = $pdfService;
        $this->historiqueService = $historiqueService;
    }

    /**
     * @OA\Get(
     *     path="/api/cartes/classe/{classe_id}",
     *     tags={"Cartes"},
     *     summary="Obtenir les élèves d'une classe avec leurs cartes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classe_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Élèves avec cartes")
     * )
     */
    public function elevesByClasse(Request $request, $classeId)
    {
        $user = $request->user();

        // Vérifier les droits
        $classe = Classe::find($classeId);
        if (!$classe) {
            return response()->json([
                'success' => false,
                'message' => 'Classe non trouvée'
            ], 404);
        }

        if (!$user->isAdmin() && $classe->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        // Récupérer les élèves actifs de la classe avec leurs cartes
        $eleves = Eleve::where('classe_id', $classeId)
            ->where('archive', false)
            ->with(['carteActive', 'photoActive'])
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        // Transformer les données pour le frontend
        $data = $eleves->map(function($eleve) {
            $carte = $eleve->carteActive;
            return [
                'id' => $eleve->id,
                'matricule' => $eleve->matricule,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'nom_complet' => $eleve->nom_complet,
                'classe_id' => $eleve->classe_id,
                'classe' => $eleve->classe,
                'sexe' => $eleve->sexe,
                'date_naissance' => $eleve->date_naissance,
                'lieu_naissance' => $eleve->lieu_naissance,
                'photo_active' => $eleve->photoActive,
                'has_photo' => $eleve->hasPhoto(),
                'carte' => $carte ? [
                    'id' => $carte->id,
                    'statut' => $carte->statut,
                    'date_generation' => $carte->date_generation,
                    'modele' => $carte->modele,
                ] : null,
                'has_carte' => $carte ? true : false,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $data->count()
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/cartes",
     *     tags={"Cartes"},
     *     summary="Liste des cartes scolaires avec pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"en_attente", "generee", "imprimee", "distribuee"})
     *     ),
     *     @OA\Parameter(
     *         name="classe_id",
     *         in="query",
     *         description="Filtrer par classe",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Liste des cartes")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $query = CarteScolaire::with(['eleve.classe', 'photo', 'modele', 'imprimeur']);

        // Filtrer par établissement
        if (!$user->isAdmin()) {
            $query->whereHas('eleve', function($q) use ($user) {
                $q->where('etablissement_id', $user->etablissement_id);
            });
        }

        // Filtrer par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtrer par classe
        if ($request->has('classe_id')) {
            $query->whereHas('eleve', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }

        $cartes = $query->latest('date_generation')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $cartes->items(),
            'pagination' => [
                'total' => $cartes->total(),
                'per_page' => $cartes->perPage(),
                'current_page' => $cartes->currentPage(),
                'last_page' => $cartes->lastPage(),
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/cartes/generer/{eleve_id}",
     *     tags={"Cartes"},
     *     summary="Générer une carte pour un élève",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="eleve_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=201, description="Carte générée")
     * )
     */
    public function genererCarte(Request $request, $eleveId)
    {
        $user = $request->user();

        // Seuls le proviseur et l'admin peuvent générer des cartes
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        try {
            $eleve = Eleve::find($eleveId);

            if (!$eleve) {
                return response()->json([
                    'success' => false,
                    'message' => 'Élève non trouvé'
                ], 404);
            }

            // Vérifier les droits
            if (!$user->isAdmin() && $eleve->etablissement_id !== $user->etablissement_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé'
                ], 403);
            }

            // Vérifier que l'élève a une photo
            if (!$eleve->hasPhoto()) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'élève n\'a pas de photo'
                ], 400);
            }

            $modeleId = $request->get('modele_id');
            if ($modeleId !== null) {
                $modeleId = (int) $modeleId;
            }

            // Générer la carte
            $carte = $this->pdfService->genererCarte($eleve, $modeleId);

            // Historique
            $this->historiqueService->enregistrer(
                'generation_carte',
                'CarteScolaire',
                $carte->id,
                "Génération de la carte pour l'élève {$eleve->nom_complet}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Carte générée avec succès',
                'data' => $carte->load(['eleve', 'photo', 'modele'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/cartes/generer-classe/{classe_id}",
     *     tags={"Cartes"},
     *     summary="Générer les cartes pour toute une classe",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="classe_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Cartes générées")
     * )
     */
    public function genererCartesClasse(Request $request, $classeId)
    {
        $user = $request->user();

        // Seuls le proviseur et l'admin peuvent générer des cartes
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        try {
            $classe = Classe::find($classeId);

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

            // Générer les cartes
            $modeleId = $request->get('modele_id');
            if ($modeleId !== null) {
                $modeleId = (int) $modeleId;
            }

            $resultats = $this->pdfService->genererCartesClasse($classeId, $modeleId);

            // Historique
            $this->historiqueService->enregistrer(
                'generation_cartes_classe',
                'Classe',
                $classe->id,
                "Génération de {$resultats['success']} cartes pour la classe {$classe->nom}"
            );

            return response()->json([
                'success' => true,
                'message' => "Génération terminée : {$resultats['success']} cartes générées",
                'data' => $resultats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/cartes/generer-planche",
     *     tags={"Cartes"},
     *     summary="Générer une planche d'impression (10 cartes par page A4)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="eleve_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="Planche générée")
     * )
     */
    public function genererPlanche(Request $request)
    {
        $user = $request->user();

        // Seuls le proviseur et l'admin peuvent générer des planches
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $request->validate([
            'eleve_ids' => 'required|array|min:1',
            'eleve_ids.*' => 'integer|exists:eleves,id',
            'modele_id' => 'nullable|integer|exists:modele_cartes,id'
        ]);

        try {
            $path = $this->pdfService->genererPlancheImpression($request->eleve_ids, $request->modele_id);

            // Historique
            $this->historiqueService->enregistrer(
                'generation_planche',
                'CarteScolaire',
                null,
                "Génération d'une planche de " . count($request->eleve_ids) . " cartes"
            );

            return response()->json([
                'success' => true,
                'message' => 'Planche générée avec succès',
                'data' => [
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'nombre_cartes' => count($request->eleve_ids)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la planche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/cartes/{id}",
     *     tags={"Cartes"},
     *     summary="Détails d'une carte",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de la carte")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $carte = CarteScolaire::with(['eleve.classe', 'photo', 'modele', 'imprimeur'])->find($id);

        if (!$carte) {
            return response()->json([
                'success' => false,
                'message' => 'Carte non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && $carte->eleve->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $carte
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/cartes/{id}/telecharger",
     *     tags={"Cartes"},
     *     summary="Télécharger le PDF d'une carte",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="PDF téléchargé")
     * )
     */
    public function telecharger(Request $request, $id)
    {
        $user = $request->user();

        $carte = CarteScolaire::with('eleve')->find($id);

        if (!$carte) {
            return response()->json([
                'success' => false,
                'message' => 'Carte non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && $carte->eleve->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        if (!$carte->chemin_pdf || !Storage::disk('public')->exists($carte->chemin_pdf)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF non disponible'
            ], 404);
        }

        // ✅ CORRECTION : Utiliser response()->download() au lieu de Storage::disk()->download()
        $filePath = Storage::disk('public')->path($carte->chemin_pdf);
        $fileName = "carte_{$carte->eleve->matricule}.pdf";

        return response()->download($filePath, $fileName);
    }

    /**
     * @OA\Get(
     *     path="/api/cartes/{id}/previsualiser",
     *     tags={"Cartes"},
     *     summary="Prévisualiser le PDF d'une carte dans le navigateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="PDF affiché")
     * )
     */
    public function previsualiser(Request $request, $id)
    {
        // Gérer l'authentification par token en paramètre GET (pour ouverture dans nouvel onglet)
        $token = $request->query('token');
        
        if ($token) {
            // Extraire l'ID du token (format: "id|hash")
            $tokenParts = explode('|', $token, 2);
            if (count($tokenParts) === 2) {
                $tokenId = $tokenParts[0];
                $tokenHash = $tokenParts[1];
                
                // Chercher le token dans la base
                $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                
                if ($personalAccessToken) {
                    $user = $personalAccessToken->tokenable;
                    
                    // Authentifier l'utilisateur pour cette requête
                    auth()->setUser($user);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token invalide'
                    ], 401);
                }
            }
        } else {
            // Utiliser l'authentification normale (header Authorization)
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié'
                ], 401);
            }
        }
        
        $user = auth()->user();

        $carte = CarteScolaire::with('eleve')->find($id);

        if (!$carte) {
            return response()->json([
                'success' => false,
                'message' => 'Carte non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && $carte->eleve->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        try {
            $eleve = $carte->eleve;
            $photo = $eleve->photoActive;

            if (!$photo || $photo->statut !== 'validee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune photo validée'
                ], 400);
            }

            // Convertir les images en base64
            $photoBase64 = $this->pdfService->imageToBase64($photo->photo_traitee ?: $photo->chemin_photo);
            $logoBase64 = $eleve->etablissement->logo ? $this->pdfService->imageToBase64($eleve->etablissement->logo) : null;

            // Générer le QR Code
            $qrCodeService = app(\App\Services\QRCodeService::class);
            $qrCode = $qrCodeService->generer($eleve->matricule);

            // Retourner le HTML de prévisualisation
            return view('cartes.preview', [
                'eleve' => $eleve,
                'photoBase64' => $photoBase64,
                'logoBase64' => $logoBase64,
                'qrCode' => $qrCode,
                'etablissement' => $eleve->etablissement,
                'classe' => $eleve->classe,
                'anneeAcademique' => optional($eleve->etablissement->anneeActive)->annee ?? date('Y') . '-' . (date('Y') + 1),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la prévisualisation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/cartes/{id}/imprimer",
     *     tags={"Cartes"},
     *     summary="Marquer une carte comme imprimée",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Carte marquée comme imprimée")
     * )
     */
    public function marquerCommeImprimee(Request $request, $id)
    {
        $user = $request->user();

        $carte = CarteScolaire::find($id);

        if (!$carte) {
            return response()->json([
                'success' => false,
                'message' => 'Carte non trouvée'
            ], 404);
        }

        // Seuls le proviseur et l'admin peuvent marquer comme imprimée
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $carte->marquerCommeImprimee($user->id);

        // Historique
        $this->historiqueService->enregistrer(
            'impression_carte',
            'CarteScolaire',
            $carte->id,
            "Impression #{$carte->nombre_impressions} de la carte pour l'élève {$carte->eleve->nom_complet}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Carte marquée comme imprimée',
            'data' => $carte
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/cartes/{id}/distribuer",
     *     tags={"Cartes"},
     *     summary="Marquer une carte comme distribuée",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Carte marquée comme distribuée")
     * )
     */
    public function marquerCommeDistribuee(Request $request, $id)
    {
        $user = $request->user();

        $carte = CarteScolaire::find($id);

        if (!$carte) {
            return response()->json([
                'success' => false,
                'message' => 'Carte non trouvée'
            ], 404);
        }

        // Seuls le surveillant et le proviseur peuvent marquer comme distribuée
        if (!$user->isSurveillant() && !$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $carte->marquerCommeDistribuee();

        // Historique
        $this->historiqueService->enregistrer(
            'distribution_carte',
            'CarteScolaire',
            $carte->id,
            "Distribution de la carte pour l'élève {$carte->eleve->nom_complet}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Carte marquée comme distribuée',
            'data' => $carte
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/cartes/statistiques",
     *     tags={"Cartes"},
     *     summary="Statistiques des cartes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Statistiques")
     * )
     */
    public function statistiques(Request $request)
    {
        $user = $request->user();

        $query = CarteScolaire::query();

        // Filtrer par établissement
        if (!$user->isAdmin()) {
            $query->whereHas('eleve', function($q) use ($user) {
                $q->where('etablissement_id', $user->etablissement_id);
            });
        }

        $stats = [
            'total' => $query->count(),
            'en_attente' => $query->where('statut', 'en_attente')->count(),
            'generees' => $query->where('statut', 'generee')->count(),
            'imprimees' => $query->where('statut', 'imprimee')->count(),
            'distribuees' => $query->where('statut', 'distribuee')->count(),
            'total_impressions' => $query->sum('nombre_impressions'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }
}
