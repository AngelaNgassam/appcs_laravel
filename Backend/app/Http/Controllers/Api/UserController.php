<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $historiqueService;

    public function __construct(HistoriqueService $historiqueService)
    {
        $this->historiqueService = $historiqueService;
    }

    /**
     * @OA\Get(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Liste des utilisateurs (avec pagination)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filtrer par rôle",
     *         required=false,
     *         @OA\Schema(type="string", enum={"admin", "proviseur", "surveillant", "operateur"})
     *     ),
     *     @OA\Response(response=200, description="Liste des utilisateurs")
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $user = $request->user();

        $query = User::with(['etablissement', 'createurDuCompte']);

        // Filtrer par établissement si pas admin
        if (!$user->isAdmin()) {
            $query->where('etablissement_id', $user->etablissement_id);
        }

        // Filtrer par rôle si demandé
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     tags={"Users"},
     *     summary="Créer un utilisateur (surveillant ou opérateur par le proviseur)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "email", "password", "role"},
     *             @OA\Property(property="nom", type="string"),
     *             @OA\Property(property="prenom", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="role", type="string", enum={"surveillant", "operateur"}),
     *             @OA\Property(property="telephone", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Utilisateur créé"),
     *     @OA\Response(response=403, description="Accès refusé")
     * )
     */
    public function store(Request $request)
    {
        $currentUser = $request->user();

        // Seuls le proviseur et l'admin peuvent créer des utilisateurs
        if (!$currentUser->isProviseur() && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les droits pour créer un utilisateur'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:surveillant,operateur',
            'telephone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'nom' => strtoupper($request->nom),
                'prenom' => ucwords($request->prenom),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'telephone' => $request->telephone,
                'etablissement_id' => $currentUser->etablissement_id,
                'cree_par' => $currentUser->id,
                'actif' => true,
            ]);

            // Historique
            $this->historiqueService->enregistrer(
                'creation_utilisateur',
                'User',
                $user->id,
                "{$currentUser->nom_complet} a créé le compte de {$user->nom_complet} ({$user->role})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'data' => $user
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
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Détails d'un utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de l'utilisateur")
     * )
     */
    public function show(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::with(['etablissement', 'createurDuCompte'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Vérifier les droits
        if (!$currentUser->isAdmin() && $user->etablissement_id !== $currentUser->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Mettre à jour un utilisateur",
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
     *             @OA\Property(property="prenom", type="string"),
     *             @OA\Property(property="telephone", type="string"),
     *             @OA\Property(property="actif", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Utilisateur mis à jour")
     * )
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Vérifier les droits
        if (!$currentUser->isAdmin() && !$currentUser->isProviseur()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'telephone' => 'nullable|string|max:20',
            'actif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $anciensDetails = $user->toArray();

            $user->update($request->only(['nom', 'prenom', 'telephone', 'actif']));

            // Historique
            $this->historiqueService->enregistrer(
                'modification_utilisateur',
                'User',
                $user->id,
                "Modification de {$user->nom_complet}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $user
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
     *     path="/api/users/{id}/toggle-active",
     *     tags={"Users"},
     *     summary="Activer/Désactiver un utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Statut modifié")
     * )
     */
    public function toggleActive(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Seuls le proviseur et l'admin peuvent activer/désactiver
        if (!$currentUser->isProviseur() && !$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $user->actif = !$user->actif;
        $user->save();

        $action = $user->actif ? 'activation' : 'desactivation';

        // Historique
        $this->historiqueService->enregistrer(
            "{$action}_utilisateur",
            'User',
            $user->id,
            "{$currentUser->nom_complet} a {$action} le compte de {$user->nom_complet}"
        );

        return response()->json([
            'success' => true,
            'message' => $user->actif ? 'Utilisateur activé' : 'Utilisateur désactivé',
            'data' => $user
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     summary="Supprimer un utilisateur (soft delete)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Utilisateur supprimé")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }

        // Seul l'admin peut supprimer
        if (!$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'administrateur peut supprimer un utilisateur'
            ], 403);
        }

        // Historique
        $this->historiqueService->enregistrer(
            'suppression_utilisateur',
            'User',
            $user->id,
            "{$currentUser->nom_complet} a supprimé le compte de {$user->nom_complet}"
        );

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ], 200);
    }
}
