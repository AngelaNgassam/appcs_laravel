<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Services\QRCodeService;

echo "🎨 Génération du HTML du template...\n";
echo "====================================\n\n";

$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])->find(2);

if (!$eleve) {
    echo "❌ Élève non trouvé\n";
    exit(1);
}

$qrCodeService = app(QRCodeService::class);
$qrCode = $qrCodeService->generer($eleve->matricule);

$photo = $eleve->photoActive;
$photoBase64 = null;
$logoBase64 = null;

if ($photo) {
    $photoPath = storage_path('app/public/' . ($photo->photo_traitee ?: $photo->chemin_photo));
    if (file_exists($photoPath)) {
        $photoData = file_get_contents($photoPath);
        $mimeType = mime_content_type($photoPath);
        $photoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($photoData);
    }
}

if ($eleve->etablissement->logo) {
    $logoPath = storage_path('app/public/' . $eleve->etablissement->logo);
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $mimeType = mime_content_type($logoPath);
        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
    }
}

$data = [
    'eleve' => $eleve,
    'photo' => $photo,
    'photoBase64' => $photoBase64,
    'logoBase64' => $logoBase64,
    'qrCode' => $qrCode,
    'etablissement' => $eleve->etablissement,
    'classe' => $eleve->classe,
    'anneeAcademique' => date('Y') . '-' . (date('Y') + 1),
];

$html = view('cartes.template', $data)->render();

$outputPath = public_path('test-carte-output.html');
file_put_contents($outputPath, $html);

echo "✅ HTML généré avec succès !\n";
echo "   Fichier : {$outputPath}\n";
echo "   URL : http://127.0.0.1:8000/test-carte-output.html\n";
