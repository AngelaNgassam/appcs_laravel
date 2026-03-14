<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModeleCarte;
use App\Services\HistoriqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ModeleCarteController extends Controller
{
    protected $historiqueService;

    public function __construct(HistoriqueService $historiqueService)
    {
        $this->historiqueService = $historiqueService;
    }

    /**
     * Liste des modèles de cartes
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $perPage = (int) $request->get('per_page', 15);

        /**
         * AUTO-INITIALISATION DES MODÈLES GLOBAUX
         * (sécurisée pour éviter les erreurs SQL)
         */
        try {
            if (ModeleCarte::whereNull('etablissement_id')->count() === 0) {

                ModeleCarte::create([
                    'etablissement_id' => null,
                    'nom_modele' => 'Modèle Carte - Standard',
                    'fichier_template' => 'cartes.template',
                    'configuration' => null,
                    'actif' => true,
                    'est_defaut' => true,
                ]);

                ModeleCarte::create([
                    'etablissement_id' => null,
                    'nom_modele' => 'Modèle Carte - Alternatif',
                    'fichier_template' => 'cartes.template2',
                    'configuration' => null,
                    'actif' => true,
                    'est_defaut' => false,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Erreur auto-init modèles cartes', [
                'error' => $e->getMessage()
            ]);
        }

        $query = ModeleCarte::with('etablissement');

        /**
         * FILTRAGE PAR ÉTABLISSEMENT
         */
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('etablissement_id', $user->etablissement_id)
                  ->orWhereNull('etablissement_id');
            });
        }

        /**
         * FILTRAGE PAR STATUT ACTIF (robuste)
         */
        if ($request->has('actif')) {
            $actif = filter_var(
                $request->actif,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($actif !== null) {
                $query->where('actif', $actif);
            }
        }

        $modeles = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $modeles->items(),
            'pagination' => [
                'total' => $modeles->total(),
                'per_page' => $modeles->perPage(),
                'current_page' => $modeles->currentPage(),
                'last_page' => $modeles->lastPage(),
            ]
        ], 200);
    }

    /**
     * Créer un modèle de carte
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom_modele' => 'required|string|max:100',
            'fichier_template' => 'nullable|string|max:191',
            'apercu' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'configuration' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'etablissement_id' => $user->isAdmin() ? null : $user->etablissement_id,
                'nom_modele' => $request->nom_modele,
                'fichier_template' => $request->fichier_template,
                'configuration' => $request->configuration
                    ? json_decode($request->configuration, true)
                    : null,
                'actif' => true,
            ];

            if ($request->hasFile('apercu')) {
                $data['apercu'] = $request->file('apercu')
                    ->store('modeles/apercu', 'public');
            }

            $modele = ModeleCarte::create($data);

            $this->historiqueService->enregistrer(
                'creation_modele_carte',
                'ModeleCarte',
                $modele->id,
                "Création du modèle {$modele->nom_modele}"
            );

            return response()->json([
                'success' => true,
                'data' => $modele
            ], 201);

        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création'
            ], 500);
        }
    }

    /**
     * Définir un modèle par défaut
     */
    public function definirParDefaut(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $modele = ModeleCarte::find($id);

        if (!$modele) {
            return response()->json([
                'success' => false,
                'message' => 'Modèle non trouvé'
            ], 404);
        }

        try {
            ModeleCarte::where('etablissement_id', $modele->etablissement_id)
                ->where('id', '!=', $modele->id)
                ->update(['est_defaut' => false]);

            $modele->update(['est_defaut' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Modèle défini par défaut'
            ]);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur'
            ], 500);
        }
    }
}
