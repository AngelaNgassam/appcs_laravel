<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Liste des notifications de l'utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="lue",
     *         in="query",
     *         description="Filtrer par statut lu/non lu",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(response=200, description="Liste des notifications")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);

        $query = Notification::where('user_id', $user->id);

        // Filtrer par statut
        if ($request->has('lue')) {
            $query->where('lue', $request->lue);
        }

        $notifications = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'non_lues' => Notification::where('user_id', $user->id)->where('lue', false)->count(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ]
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/notifications/{id}/lire",
     *     tags={"Notifications"},
     *     summary="Marquer une notification comme lue",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notification marquée comme lue")
     * )
     */
    public function marquerCommeLue(Request $request, $id)
    {
        $user = $request->user();

        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }

        // Vérifier que c'est bien sa notification
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $this->notificationService->marquerCommeLue($id);

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/notifications/tout-lire",
     *     tags={"Notifications"},
     *     summary="Marquer toutes les notifications comme lues",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Toutes les notifications marquées comme lues")
     * )
     */
    public function marquerToutesCommeLues(Request $request)
    {
        $user = $request->user();

        $this->notificationService->marquerToutesCommeLues($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Supprimer une notification",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Notification supprimée")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée'
            ], 404);
        }

        // Vérifier que c'est bien sa notification
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée'
        ], 200);
    }
}
