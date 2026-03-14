<?php

namespace App\Services;

use App\Models\HistoriqueAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HistoriqueService
{
    /**
     * Enregistrer une action dans l'historique
     */
    public function enregistrer(
        string $action,
        string $cibleType,
        ?int $cibleId = null,
        ?string $details = null
    ): void {
        // ✅ Vérifier que l'utilisateur est authentifié
        if (!Auth::check()) {
            Log::warning("Tentative d'enregistrement d'historique sans utilisateur authentifié", [
                'action' => $action,
                'cible_type' => $cibleType,
                'cible_id' => $cibleId
            ]);
            return; // Ne rien faire si pas d'utilisateur connecté
        }

        try {
            HistoriqueAction::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'cible_type' => $cibleType,
                'cible_id' => $cibleId,
                'details' => $details,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'date_action' => now(),
            ]);
        } catch (\Exception $e) {
            // ✅ Logger l'erreur au lieu de faire planter l'app
            Log::error('Erreur lors de l\'enregistrement de l\'historique', [
                'error' => $e->getMessage(),
                'action' => $action,
                'cible_type' => $cibleType,
                'cible_id' => $cibleId
            ]);
        }
    }
}
