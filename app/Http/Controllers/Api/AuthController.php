<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Etablissement;
use App\Models\AnneeAcademique;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $historiqueService;

    public function __construct(HistoriqueService $historiqueService)
    {
        $this->historiqueService = $historiqueService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"Authentication"},
     *     summary="Inscription d'un proviseur avec création automatique de l'établissement",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"nom", "prenom", "email", "mot_de_passe", "nom_etablissement", "adresse_etablissement", "ville_etablissement"},
     *                 @OA\Property(property="nom", type="string", example="KAMGA"),
     *                 @OA\Property(property="prenom", type="string", example="Paul"),
     *                 @OA\Property(property="email", type="string", example="paul.kamga@lycee.cm"),
     *                 @OA\Property(property="mot_de_passe", type="string", example="password123"),
     *                 @OA\Property(property="telephone", type="string", example="677123456"),
     *                 @OA\Property(property="nom_etablissement", type="string", example="Lycée Bilingue de Douala"),
     *                 @OA\Property(property="adresse_etablissement", type="string", example="Quartier Akwa"),
     *                 @OA\Property(property="ville_etablissement", type="string", example="Douala"),
     *                 @OA\Property(property="telephone_etablissement", type="string", example="699123456"),
     *                 @OA\Property(property="email_etablissement", type="string", example="contact@lycee.cm"),
     *                 @OA\Property(property="logo", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Inscription réussie"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function register(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            // Infos proviseur
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|string|min:8',
            'telephone' => 'nullable|string|max:20',

            // Infos établissement
            'nom_etablissement' => 'required|string|max:200',
            'adresse_etablissement' => 'required|string',
            'ville_etablissement' => 'required|string|max:100',
            'telephone_etablissement' => 'nullable|string|max:20',
            'email_etablissement' => 'nullable|email',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Créer l'établissement SANS proviseur_id d'abord
            $etablissement = Etablissement::create([
                'nom' => $request->nom_etablissement,
                'adresse' => $request->adresse_etablissement,
                'ville' => $request->ville_etablissement,
                'telephone' => $request->telephone_etablissement,
                'email' => $request->email_etablissement,
            ]);

            // 2. Gérer le logo si présent
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('logos', 'public');
                $etablissement->logo = $logoPath;
                $etablissement->save();
            }

            // 3. Créer le proviseur avec l'établissement_id
            $proviseur = User::create([
                'nom' => strtoupper($request->nom),
                'prenom' => ucwords($request->prenom),
                'email' => $request->email,
                'password' => Hash::make($request->mot_de_passe),
                'role' => 'proviseur',
                'telephone' => $request->telephone,
                'etablissement_id' => $etablissement->id,
                'actif' => true,
            ]);

            // 4. Mettre à jour l'établissement avec le proviseur_id
            $etablissement->proviseur_id = $proviseur->id;
            $etablissement->save();

            // 5. Créer une année académique active par défaut
            $anneeEnCours = date('Y');
            $anneeSuivante = $anneeEnCours + 1;

            AnneeAcademique::create([
                'etablissement_id' => $etablissement->id,
                'libelle' => "{$anneeEnCours}-{$anneeSuivante}",
                'date_debut' => "{$anneeEnCours}-09-01",
                'date_fin' => "{$anneeSuivante}-07-31",
                'active' => true,
            ]);

            // ✅ 6. Authentifier l'utilisateur AVANT de générer le token
            Auth::login($proviseur);

            // 7. Générer le token
            $token = $proviseur->createToken('auth_token')->plainTextToken;

            // 8. Enregistrer dans l'historique (maintenant l'utilisateur est authentifié)
            $this->historiqueService->enregistrer(
                'inscription',
                'User',
                $proviseur->id,
                "Inscription du proviseur {$proviseur->nom_complet} et création de l'établissement {$etablissement->nom}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'token' => $token,
                'user' => [
                    'id' => $proviseur->id,
                    'nom' => $proviseur->nom,
                    'prenom' => $proviseur->prenom,
                    'email' => $proviseur->email,
                    'role' => $proviseur->role,
                    'telephone' => $proviseur->telephone,
                    'etablissement_id' => $proviseur->etablissement_id,
                    'actif' => $proviseur->actif,
                ],
                'etablissement' => [
                    'id' => $etablissement->id,
                    'nom' => $etablissement->nom,
                    'ville' => $etablissement->ville,
                    'adresse' => $etablissement->adresse,
                    'telephone' => $etablissement->telephone,
                    'email' => $etablissement->email,
                    'logo_url' => $etablissement->logo_url,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ Logger l'erreur détaillée
            Log::error('Erreur lors de l\'inscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     tags={"Authentication"},
     *     summary="Connexion avec identification automatique du rôle",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "mot_de_passe"},
     *             @OA\Property(property="email", type="string", example="admin@example.com"),
     *             @OA\Property(property="mot_de_passe", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Connexion réussie"),
     *     @OA\Response(response=401, description="Identifiants incorrects")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'mot_de_passe' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Chercher l'utilisateur
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->mot_de_passe, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ], 401);
            }

            // Vérifier si le compte est actif
            if (!$user->actif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.'
                ], 403);
            }

            // ✅ Authentifier l'utilisateur AVANT de créer le token
            Auth::login($user);

            // Générer le token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Enregistrer dans l'historique (maintenant l'utilisateur est authentifié)
            $this->historiqueService->enregistrer(
                'connexion',
                'User',
                $user->id,
                "Connexion de {$user->nom_complet}"
            );

            // Préparer la réponse
            $response = [
                'success' => true,
                'message' => 'Connexion réussie',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'nom_complet' => $user->nom_complet,
                    'email' => $user->email,
                    'role' => $user->role,
                    'telephone' => $user->telephone,
                    'etablissement_id' => $user->etablissement_id,
                    'actif' => $user->actif,
                ]
            ];

            // ✅ Ajouter les infos de l'établissement avec TOUTES les données
            if ($user->etablissement_id) {
                $etablissement = $user->etablissement;
                $response['etablissement'] = [
                    'id' => $etablissement->id,
                    'nom' => $etablissement->nom,
                    'ville' => $etablissement->ville,
                    'adresse' => $etablissement->adresse,
                    'telephone' => $etablissement->telephone,
                    'email' => $etablissement->email,
                    'logo_url' => $etablissement->logo_url,
                ];
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            // ✅ Logger l'erreur détaillée
            Log::error('Erreur lors de la connexion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     tags={"Authentication"},
     *     summary="Déconnexion",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Déconnexion réussie")
     * )
     */
    public function logout(Request $request)
    {
        try {
            // Enregistrer dans l'historique
            $this->historiqueService->enregistrer(
                'deconnexion',
                'User',
                $request->user()->id,
                "Déconnexion de {$request->user()->nom_complet}"
            );

            // Supprimer tous les tokens de l'utilisateur
            $request->user()->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/me",
     *     tags={"Authentication"},
     *     summary="Obtenir les informations de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Informations utilisateur")
     * )
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            $response = [
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'nom_complet' => $user->nom_complet,
                    'email' => $user->email,
                    'role' => $user->role,
                    'telephone' => $user->telephone,
                    'etablissement_id' => $user->etablissement_id,
                    'actif' => $user->actif,
                ]
            ];

            // ✅ Ajouter toutes les infos de l'établissement
            if ($user->etablissement_id) {
                $etablissement = $user->etablissement;
                $response['etablissement'] = [
                    'id' => $etablissement->id,
                    'nom' => $etablissement->nom,
                    'ville' => $etablissement->ville,
                    'adresse' => $etablissement->adresse,
                    'telephone' => $etablissement->telephone,
                    'email' => $etablissement->email,
                    'logo_url' => $etablissement->logo_url,
                ];
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des infos utilisateur', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
