<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoriqueAction;
use Illuminate\Http\Request;

class HistoriqueController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/historique",
     *     tags={"Historique"},
     *     summary="Historique des actions avec pagination",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         description="Filtrer par type d'action",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filtrer par utilisateur",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Historique des actions")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $query = HistoriqueAction::with('user');

        // Filtrer par établissement si pas admin
        if (!$user->isAdmin()) {
            $query->whereHas('user', function($q) use ($user) {
                $q->where('etablissement_id', $user->etablissement_id);
            });
        }

        // Si opérateur, voir seulement ses actions
        if ($user->isOperateur()) {
            $query->where('user_id', $user->id);
        }

        // Filtrer par action
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filtrer par utilisateur
        if ($request->has('user_id') && ($user->isAdmin() || $user->isProviseur())) {
            $query->where('user_id', $request->user_id);
        }

        // Filtrer par date
        if ($request->has('date_debut')) {
            $query->whereDate('date_action', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->whereDate('date_action', '<=', $request->date_fin);
        }

        $historique = $query->orderBy('date_action', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $historique->items(),
            'pagination' => [
                'total' => $historique->total(),
                'per_page' => $historique->perPage(),
                'current_page' => $historique->currentPage(),
                'last_page' => $historique->lastPage(),
            ]
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/historique/{id}",
     *     tags={"Historique"},
     *     summary="Détails d'une action",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails de l'action")
     * )
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $action = HistoriqueAction::with('user')->find($id);

        if (!$action) {
            return response()->json([
                'success' => false,
                'message' => 'Action non trouvée'
            ], 404);
        }

        // Vérifier les droits
        if (!$user->isAdmin() && !$user->isProviseur()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $action
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/historique/statistiques",
     *     tags={"Historique"},
     *     summary="Statistiques des actions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Statistiques")
     * )
     */
    public function statistiques(Request $request)
    {
        $user = $request->user();

        $query = HistoriqueAction::query();

        // Filtrer par établissement
        if (!$user->isAdmin()) {
            $query->whereHas('user', function($q) use ($user) {
                $q->where('etablissement_id', $user->etablissement_id);
            });
        }

        $stats = [
            'total_actions' => $query->count(),
            'aujourd_hui' => $query->whereDate('date_action', today())->count(),
            'cette_semaine' => $query->whereBetween('date_action', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'ce_mois' => $query->whereMonth('date_action', now()->month)->count(),
            'par_type' => $query->selectRaw('action, count(*) as total')
                ->groupBy('action')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }
}
