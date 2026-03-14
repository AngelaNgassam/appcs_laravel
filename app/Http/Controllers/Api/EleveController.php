<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Eleve;
use App\Models\Classe;
use App\Services\HistoriqueService;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EleveController extends Controller
{
    protected $historiqueService;
    protected $excelImportService;

    public function __construct(
        HistoriqueService $historiqueService,
        ExcelImportService $excelImportService
    ) {
        $this->historiqueService = $historiqueService;
        $this->excelImportService = $excelImportService;
    }

    /**
     * 📋 Liste des élèves avec pagination
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $query = Eleve::with(['classe', 'etablissement', 'photoActive', 'carteActive']);

        // Filtrer par établissement
        if (!$user->isAdmin()) {
            $query->where('etablissement_id', $user->etablissement_id);
        }

        // Filtrer par classe
        if ($request->has('classe_id')) {
            $query->where('classe_id', $request->classe_id);
        }

        // Filtrer par statut archivé
        $archive = $request->get('archive', false);
        $query->where('archive', $archive);

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $eleves = $query->orderBy('nom')->orderBy('prenom')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $eleves->items(),
            'pagination' => [
                'total' => $eleves->total(),
                'per_page' => $eleves->perPage(),
                'current_page' => $eleves->currentPage(),
                'last_page' => $eleves->lastPage(),
            ]
        ], 200);
    }

    /**
     * ➕ Créer un élève manuellement
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Seuls le proviseur et le surveillant peuvent créer des élèves
        if (!$user->isProviseur() && !$user->isSurveillant() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'classe_id' => 'required|exists:classes,id',
            'matricule' => 'required|string|max:50|unique:eleves,matricule',
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'nullable|string|max:150',
            'sexe' => 'required|in:M,F',
            'contact_parent' => 'nullable|string|max:20',
            'nom_parent' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier que la classe appartient à l'établissement
            $classe = Classe::find($request->classe_id);

            if (!$user->isAdmin() && $classe->etablissement_id !== $user->etablissement_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette classe n\'appartient pas à votre établissement'
                ], 403);
            }

            $eleve = Eleve::create([
                'etablissement_id' => $classe->etablissement_id,
                'classe_id' => $request->classe_id,
                'matricule' => $request->matricule,
                'nom' => strtoupper($request->nom),
                'prenom' => ucwords($request->prenom),
                'date_naissance' => $request->date_naissance,
                'lieu_naissance' => $request->lieu_naissance,
                'sexe' => $request->sexe,
                'contact_parent' => $request->contact_parent,
                'nom_parent' => $request->nom_parent,
            ]);

            // Mettre à jour l'effectif
            $classe->updateEffectif();

            // Historique
            $this->historiqueService->enregistrer(
                'creation_eleve',
                'Eleve',
                $eleve->id,
                "Création de l'élève {$eleve->nom_complet} (Matricule: {$eleve->matricule})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Élève créé avec succès',
                'data' => $eleve->load(['classe', 'etablissement'])
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
     * 🔍 Détails d'un élève
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $eleve = Eleve::with([
            'classe',
            'etablissement',
            'photos',
            'photoActive',
            'cartes',
            'carteActive'
        ])->find($id);

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

        return response()->json([
            'success' => true,
            'data' => $eleve
        ], 200);
    }

    /**
     * ✏️ Mettre à jour un élève
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $eleve = Eleve::find($id);

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

        $validator = Validator::make($request->all(), [
            'classe_id' => 'sometimes|exists:classes,id',
            'matricule' => 'sometimes|string|max:50|unique:eleves,matricule,' . $id,
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'date_naissance' => 'sometimes|date|before:today',
            'lieu_naissance' => 'nullable|string|max:150',
            'sexe' => 'sometimes|in:M,F',
            'contact_parent' => 'nullable|string|max:20',
            'nom_parent' => 'nullable|string|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $eleve->update($request->only([
                'classe_id',
                'matricule',
                'nom',
                'prenom',
                'date_naissance',
                'lieu_naissance',
                'sexe',
                'contact_parent',
                'nom_parent'
            ]));

            // Historique
            $this->historiqueService->enregistrer(
                'modification_eleve',
                'Eleve',
                $eleve->id,
                "Modification de l'élève {$eleve->nom_complet}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Élève mis à jour avec succès',
                'data' => $eleve->load(['classe', 'etablissement'])
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
     * 🗑️ Supprimer un élève
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $eleve = Eleve::find($id);

        if (!$eleve) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        // Vérifier les droits (seul admin ou proviseur)
        if (!$user->isAdmin() && !$user->isProviseur()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        if (!$user->isAdmin() && $eleve->etablissement_id !== $user->etablissement_id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        try {
            $nomComplet = $eleve->nom_complet;
            $classeId = $eleve->classe_id;

            $eleve->delete();

            // Mettre à jour l'effectif de la classe
            $classe = Classe::find($classeId);
            if ($classe) {
                $classe->updateEffectif();
            }

            // Historique
            $this->historiqueService->enregistrer(
                'suppression_eleve',
                'Eleve',
                $id,
                "Suppression de l'élève {$nomComplet}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Élève supprimé avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📦 Archiver un élève
     */
    public function archiver(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Rechercher l'élève
            $eleve = Eleve::find($id);

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

            // Vérifier que l'élève n'est pas déjà archivé
            if ($eleve->archive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève est déjà archivé'
                ], 400);
            }

            // Archiver l'élève en utilisant la méthode du modèle
            $eleve->archiver();

            // Recharger les relations
            $eleve->load(['classe', 'etablissement', 'photoActive', 'carteActive']);

            // Historique
            $this->historiqueService->enregistrer(
                'archivage_eleve',
                'Eleve',
                $eleve->id,
                "Archivage de l'élève {$eleve->nom_complet}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Élève archivé avec succès',
                'data' => $eleve
            ], 200);

        } catch (\Exception $e) {
            // Logger l'erreur
            Log::error('Erreur archivage élève: ' . $e->getMessage(), [
                'eleve_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'archivage de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * 📤 Désarchiver un élève
     */
    public function desarchiver(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Rechercher l'élève
            $eleve = Eleve::find($id);

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

            // Vérifier que l'élève est bien archivé
            if (!$eleve->archive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet élève n\'est pas archivé'
                ], 400);
            }

            // Désarchiver l'élève en utilisant la méthode du modèle
            $eleve->desarchiver();

            // Recharger les relations
            $eleve->load(['classe', 'etablissement', 'photoActive', 'carteActive']);

            // Historique
            $this->historiqueService->enregistrer(
                'desarchivage_eleve',
                'Eleve',
                $eleve->id,
                "Désarchivage de l'élève {$eleve->nom_complet}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Élève désarchivé avec succès',
                'data' => $eleve
            ], 200);

        } catch (\Exception $e) {
            // Logger l'erreur
            Log::error('Erreur désarchivage élève: ' . $e->getMessage(), [
                'eleve_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du désarchivage de l\'élève',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * 📥 Importer des élèves depuis Excel
     */
    public function importExcel(Request $request)
    {
        $user = $request->user();

        // Seul le proviseur peut importer
        if (!$user->isProviseur() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'classe_id' => 'required|exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Vérifier que la classe appartient à l'établissement
            $classe = Classe::find($request->classe_id);

            if (!$user->isAdmin() && $classe->etablissement_id !== $user->etablissement_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette classe n\'appartient pas à votre établissement'
                ], 403);
            }

            // Importer
            $resultat = $this->excelImportService->importerEleves(
                $request->file('file'),
                $request->classe_id,
                $classe->etablissement_id
            );

            // Historique
            $this->historiqueService->enregistrer(
                'import_eleves',
                'Classe',
                $classe->id,
                "Import de {$resultat['importes']} élèves dans la classe {$classe->nom}"
            );

            return response()->json([
                'success' => true,
                'message' => "Import terminé : {$resultat['importes']} élèves importés sur {$resultat['total']}",
                'data' => $resultat
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
