<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Créer une notification
     */
    public function creer(
        int $userId,
        string $type,
        string $titre,
        string $message
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
        ]);
    }

    /**
     * Marquer comme lue
     */
    public function marquerCommeLue(int $notificationId): bool
    {
        $notification = Notification::find($notificationId);

        if ($notification) {
            $notification->marquerCommeLue();
            return true;
        }

        return false;
    }

    /**
     * Marquer toutes comme lues
     */
    public function marquerToutesCommeLues(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->where('lue', false)
            ->update([
                'lue' => true,
                'date_lecture' => now(),
            ]);
    }
}
