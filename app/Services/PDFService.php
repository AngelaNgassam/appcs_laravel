<?php

namespace App\Services;

use App\Models\CarteScolaire;
use App\Models\Eleve;
use App\Models\ModeleCarte;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PDFService
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Générer une carte scolaire en PDF
     */
    public function genererCarte(Eleve $eleve, int $modeleId = null): CarteScolaire
    {
        // Récupérer la photo active (doit être validée)
        $photo = $eleve->photoActive;

        if (!$photo || $photo->statut !== 'validee') {
            throw new \Exception("Aucune photo validée trouvée pour cet élève");
        }

        // Choisir le modèle
        $modele = null;
        if ($modeleId) {
            $modele = ModeleCarte::find($modeleId);
        }

        if (!$modele) {
            $modele = ModeleCarte::query()
                ->where('actif', true)
                ->where(function ($q) use ($eleve) {
                    $q->whereNull('etablissement_id')
                        ->orWhere('etablissement_id', $eleve->etablissement_id);
                })
                ->orderByDesc('est_defaut')
                ->latest()
                ->first();
        }

        // Générer le QR Code avec UNIQUEMENT le matricule
        $qrCode = $this->qrCodeService->generer($eleve->matricule);

        // Convertir les images en base64 pour le PDF
        $photoBase64 = $this->imageToBase64($photo->photo_traitee ?: $photo->chemin_photo);
        $logoBase64 = $eleve->etablissement->logo ? $this->imageToBase64($eleve->etablissement->logo) : null;

        // Préparer les données pour le PDF
        $data = [
            'eleve' => $eleve,
            'photo' => $photo,
            'photoBase64' => $photoBase64,
            'logoBase64' => $logoBase64,
            'qrCode' => $qrCode,
            'matricule' => $eleve->matricule,
            'etablissement' => $eleve->etablissement,
            'classe' => $eleve->classe,
            'modele' => $modele,
            'anneeAcademique' => optional($eleve->etablissement->anneeActive)->annee ?? date('Y') . '-' . (date('Y') + 1),
        ];

        // Utiliser le template simplifié pour le PDF
        $pdf = Pdf::loadView('cartes.pdf-card', $data)
            ->setPaper([0, 0, 243, 153], 'portrait') // Format carte (85.6mm x 53.98mm)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('margin_top', 0)
            ->setOption('margin_bottom', 0)
            ->setOption('margin_left', 0)
            ->setOption('margin_right', 0)
            ->setOption('dpi', 96)
            ->setOption('defaultFont', 'Arial');

        // Sauvegarder
        $filename = 'carte_' . $eleve->matricule . '_' . time() . '.pdf';
        $path = 'cartes/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        // Créer ou mettre à jour l'enregistrement
        $carte = CarteScolaire::updateOrCreate(
            ['eleve_id' => $eleve->id],
            [
                'photo_id' => $photo->id,
                'modele_id' => $modele?->id,
                'qr_code' => $eleve->matricule, // Stocker uniquement le matricule
                'chemin_pdf' => $path,
                'statut' => 'generee',
                'date_generation' => now(),
            ]
        );

        return $carte;
    }

    /**
     * Convertir une image en base64 pour l'intégration dans le PDF
     * Compresse l'image pour réduire la taille du PDF
     */
    public function imageToBase64(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        
        if (!file_exists($fullPath)) {
            return null;
        }

        try {
            // Lire le fichier image
            $imageData = file_get_contents($fullPath);
            if (!$imageData) {
                return null;
            }

            // Créer une image à partir des données
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                // Si imagecreatefromstring échoue, retourner directement en base64
                $mimeType = mime_content_type($fullPath);
                return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            }

            // Obtenir les dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Redimensionner pour les cartes (max 200x250px)
            $maxWidth = 200;
            $maxHeight = 250;
            
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Convertir en JPEG compressé
            ob_start();
            imagejpeg($image, null, 75); // 75% qualité
            $compressedData = ob_get_clean();
            imagedestroy($image);

            return 'data:image/jpeg;base64,' . base64_encode($compressedData);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner l'image originale en base64
            $mimeType = mime_content_type($fullPath);
            $imageData = file_get_contents($fullPath);
            return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
    }

    /**
     * Générer les cartes pour une classe
     */
    public function genererCartesClasse(int $classeId, ?int $modeleId = null): array
    {
        $eleves = Eleve::where('classe_id', $classeId)
            ->where('archive', false)
            ->get();

        $resultats = [
            'success' => 0,
            'erreurs' => [],
        ];

        foreach ($eleves as $eleve) {
            try {
                $this->genererCarte($eleve, $modeleId);
                $resultats['success']++;
            } catch (\Exception $e) {
                $resultats['erreurs'][] = [
                    'eleve' => $eleve->nom_complet,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $resultats;
    }

    /**
     * Générer une planche d'impression (10 cartes par page A4)
     */
    public function genererPlancheImpression(array $eleveIds, ?int $modeleId = null): string
    {
        $eleves = Eleve::with(['photoActive', 'classe', 'etablissement.anneeActive'])
            ->whereIn('id', $eleveIds)
            ->get();

        // Choisir le modèle
        $modele = null;
        if ($modeleId) {
            $modele = ModeleCarte::find($modeleId);
        }

        if (!$modele) {
            $modele = ModeleCarte::query()
                ->where('actif', true)
                ->orderByDesc('est_defaut')
                ->latest()
                ->first();
        }

        // Utiliser le template du modèle choisi
        $viewName = $modele?->fichier_template ?: 'cartes.modele-cameroun';

        $cartesData = [];

        foreach ($eleves as $eleve) {
            $photo = $eleve->photoActive;
            
            if (!$photo || $photo->statut !== 'validee') {
                continue;
            }

            // Générer le QR Code avec UNIQUEMENT le matricule
            $qrCode = $this->qrCodeService->generer($eleve->matricule);

            // Convertir les images en base64
            $photoBase64 = $this->imageToBase64($photo->photo_traitee ?: $photo->chemin_photo);
            $logoBase64 = $eleve->etablissement->logo ? $this->imageToBase64($eleve->etablissement->logo) : null;

            $cartesData[] = [
                'eleve' => $eleve,
                'photo' => $photo,
                'photoBase64' => $photoBase64,
                'logoBase64' => $logoBase64,
                'qrCode' => $qrCode,
                'matricule' => $eleve->matricule,
                'etablissement' => $eleve->etablissement,
                'classe' => $eleve->classe,
                'anneeAcademique' => optional($eleve->etablissement->anneeActive)->annee ?? date('Y') . '-' . (date('Y') + 1),
            ];
        }

        // Générer le PDF avec toutes les cartes
        $pdf = Pdf::loadView('cartes.planche-impression', [
            'cartes' => $cartesData,
            'modele' => $modele,
            'viewName' => $viewName
        ])
            ->setPaper('A4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        // Sauvegarder
        $filename = 'planche_cartes_' . time() . '.pdf';
        $path = 'cartes/planches/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Générer une prévisualisation d'une carte (format carte individuelle)
     */
    public function genererPrevisualisation(Eleve $eleve, int $modeleId = null): string
    {
        // Récupérer la photo active (doit être validée)
        $photo = $eleve->photoActive;

        if (!$photo || $photo->statut !== 'validee') {
            throw new \Exception("Aucune photo validée trouvée pour cet élève");
        }

        // Choisir le modèle
        $modele = null;
        if ($modeleId) {
            $modele = ModeleCarte::find($modeleId);
        }

        if (!$modele) {
            $modele = ModeleCarte::query()
                ->where('actif', true)
                ->orderByDesc('est_defaut')
                ->latest()
                ->first();
        }

        // Utiliser le template du modèle choisi
        $viewName = $modele?->fichier_template ?: 'cartes.modele-standard';

        // Générer le QR Code avec UNIQUEMENT le matricule
        $qrCode = $this->qrCodeService->generer($eleve->matricule);

        // Convertir les images en base64
        $photoBase64 = $this->imageToBase64($photo->photo_traitee ?: $photo->chemin_photo);
        $logoBase64 = $eleve->etablissement->logo ? $this->imageToBase64($eleve->etablissement->logo) : null;

        // Préparer les données pour le PDF
        $data = [
            'eleve' => $eleve,
            'photo' => $photo,
            'photoBase64' => $photoBase64,
            'logoBase64' => $logoBase64,
            'qrCode' => $qrCode,
            'matricule' => $eleve->matricule,
            'etablissement' => $eleve->etablissement,
            'classe' => $eleve->classe,
            'modele' => $modele,
            'anneeAcademique' => optional($eleve->etablissement->anneeActive)->annee ?? date('Y') . '-' . (date('Y') + 1),
        ];

        // Générer le PDF au format carte (85.6mm x 53.98mm)
        $pdf = Pdf::loadView($viewName, $data)
            ->setPaper([0, 0, 243, 153], 'portrait') // Format carte
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        // Sauvegarder temporairement
        $filename = 'preview_carte_' . $eleve->matricule . '_' . time() . '.pdf';
        $path = 'cartes/previews/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
