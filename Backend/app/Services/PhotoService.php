<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PhotoService
{
    /**
     * Traiter et enregistrer une photo
     */
    public function traiterPhoto(
        UploadedFile $file,
        int $eleveId,
        int $operateurId
    ): Photo {
        // Générer un nom unique
        $filename = 'photo_' . $eleveId . '_' . time() . '.jpg';

        // Chemins
        $cheminOriginal = 'photos/originales/' . $filename;
        $cheminTraitee = 'photos/traitees/' . $filename;

        // Sauvegarder l'original
        Storage::disk('public')->putFileAs(dirname($cheminOriginal), $file, basename($cheminOriginal));

        $cheminOriginalComplet = Storage::disk('public')->path($cheminOriginal);

        // Traiter l'image (redimensionnement, optimisation) avec GD si disponible
        $jpegBinaire = $this->convertirEnJpegOptimise($cheminOriginalComplet);

        // Sauvegarder la photo traitée
        Storage::disk('public')->put($cheminTraitee, $jpegBinaire);

        // Créer l'enregistrement
        return Photo::create([
            'eleve_id' => $eleveId,
            'operateur_id' => $operateurId,
            'chemin_photo' => $cheminTraitee,
            'photo_originale' => $cheminOriginal,
            'photo_traitee' => $cheminTraitee,
            'statut' => 'brouillon',
            'date_prise' => now(),
            'active' => true,
        ]);
    }

    private function convertirEnJpegOptimise(string $cheminImage): string
    {
        $contenu = @file_get_contents($cheminImage);
        if ($contenu === false) {
            throw new \RuntimeException('Impossible de lire le fichier image.');
        }

        if (!extension_loaded('gd')) {
            return $contenu;
        }

        $image = @imagecreatefromstring($contenu);
        if ($image === false) {
            return $contenu;
        }

        $largeur = imagesx($image);
        $hauteur = imagesy($image);

        // 1) Recadrage centré au ratio 3:4 (photo d'identité)
        $ratioCible = 3 / 4;
        $ratioSource = $hauteur > 0 ? ($largeur / $hauteur) : $ratioCible;

        $cropX = 0;
        $cropY = 0;
        $cropW = $largeur;
        $cropH = $hauteur;

        if ($ratioSource > $ratioCible) {
            // Trop large => on coupe la largeur
            $cropW = (int) max(1, floor($hauteur * $ratioCible));
            $cropX = (int) max(0, floor(($largeur - $cropW) / 2));
        } elseif ($ratioSource < $ratioCible) {
            // Trop haut => on coupe la hauteur
            $cropH = (int) max(1, floor($largeur / $ratioCible));
            $cropY = (int) max(0, floor(($hauteur - $cropH) / 2));
        }

        $imageCrop = $image;
        if (function_exists('imagecrop') && ($cropW !== $largeur || $cropH !== $hauteur)) {
            $tmp = @imagecrop($image, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);
            if ($tmp !== false) {
                $imageCrop = $tmp;
            }
        } elseif ($cropW !== $largeur || $cropH !== $hauteur) {
            $tmp = imagecreatetruecolor($cropW, $cropH);
            imagecopyresampled($tmp, $image, 0, 0, $cropX, $cropY, $cropW, $cropH, $cropW, $cropH);
            $imageCrop = $tmp;
        }

        // 2) Redimensionnement standard photo d'identité (ratio 3:4)
        // Hauteur cible ~354px (référence 300 DPI), largeur ajustée à 266px pour respecter 3:4
        $largeurCible = 266;
        $hauteurCible = 354;
        $imageCible = imagecreatetruecolor($largeurCible, $hauteurCible);
        imagecopyresampled(
            $imageCible,
            $imageCrop,
            0,
            0,
            0,
            0,
            $largeurCible,
            $hauteurCible,
            imagesx($imageCrop),
            imagesy($imageCrop)
        );

        imagefilter($imageCible, IMG_FILTER_BRIGHTNESS, 5);
        imagefilter($imageCible, IMG_FILTER_CONTRAST, -5);

        if (function_exists('imageconvolution')) {
            $noyau = [
                [-1, -1, -1],
                [-1, 16, -1],
                [-1, -1, -1],
            ];
            imageconvolution($imageCible, $noyau, 8, 0);
        }

        ob_start();
        imagejpeg($imageCible, null, 90);
        $jpegBinaire = ob_get_clean();

        imagedestroy($image);
        if ($imageCrop !== $image) {
            imagedestroy($imageCrop);
        }
        imagedestroy($imageCible);

        return $jpegBinaire !== false ? $jpegBinaire : $contenu;
    }

    /**
     * Supprimer une photo
     */
    public function supprimerPhoto(Photo $photo): bool
    {
        // Supprimer les fichiers physiques
        if ($photo->photo_originale) {
            Storage::disk('public')->delete($photo->photo_originale);
        }

        if ($photo->photo_traitee) {
            Storage::disk('public')->delete($photo->photo_traitee);
        }

        // Supprimer l'enregistrement
        return $photo->delete();
    }
}
