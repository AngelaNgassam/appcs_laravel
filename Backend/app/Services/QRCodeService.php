<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Générer un QR Code
     * @param array|string $data - Données à encoder (array ou string simple)
     */
    public function generer($data): string
    {
        // Si c'est un tableau, on le convertit en JSON
        // Si c'est une string, on l'utilise directement
        $content = is_array($data) ? json_encode($data) : $data;

        return QrCode::format('svg')
            ->size(200)
            ->errorCorrection('H')
            ->generate($content);
    }

    /**
     * Générer et sauvegarder un QR Code
     */
    public function genererEtSauvegarder(array $data, string $filename): string
    {
        $qrCode = $this->generer($data);

        $path = 'qrcodes/' . $filename . '.svg';

        \Storage::disk('public')->put($path, $qrCode);

        return $path;
    }
}
