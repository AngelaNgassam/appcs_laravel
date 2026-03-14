<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Models\ModeleCarte;
use App\Services\PDFService;
use App\Services\QRCodeService;

echo "🧪 Test de rendu HTML des templates\n";
echo "====================================\n\n";

// Trouver un élève avec photo
$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])
    ->whereHas('photoActive', function($q) {
        $q->where('statut', 'validee');
    })
    ->first();

if (!$eleve) {
    echo "❌ Aucun élève avec photo validée trouvé\n";
    exit(1);
}

echo "✅ Élève: {$eleve->nom} {$eleve->prenom}\n";
echo "   Classe: {$eleve->classe->nom}\n\n";

$pdfService = app(PDFService::class);
$qrCodeService = app(QRCodeService::class);

// Générer les données
$photoBase64 = $pdfService->imageToBase64($eleve->photoActive->photo_traitee ?: $eleve->photoActive->chemin_photo);
$logoBase64 = $eleve->etablissement->logo ? $pdfService->imageToBase64($eleve->etablissement->logo) : null;
$qrCode = $qrCodeService->generer($eleve->matricule);

$data = [
    'eleve' => $eleve,
    'photoBase64' => $photoBase64,
    'logoBase64' => $logoBase64,
    'qrCode' => $qrCode,
    'etablissement' => $eleve->etablissement,
    'classe' => $eleve->classe,
    'anneeAcademique' => optional($eleve->etablissement->anneeActive)->annee ?? date('Y') . '-' . (date('Y') + 1),
];

// Générer le HTML pour chaque modèle
$modeles = [
    'cartes.modele-cameroun' => 'Modèle Cameroun',
    'cartes.modele-standard' => 'Modèle Standard',
    'cartes.modele-premium' => 'Modèle Premium',
];

foreach ($modeles as $view => $name) {
    echo "🎨 Génération HTML: {$name}\n";
    
    try {
        $html = view($view, $data)->render();
        $filename = "test_" . str_replace('.', '_', $view) . ".html";
        file_put_contents($filename, $html);
        
        $size = strlen($html);
        echo "   ✅ HTML généré\n";
        echo "   📄 Fichier: {$filename}\n";
        echo "   📊 Taille: " . round($size / 1024, 2) . " KB\n";
        echo "   🔗 Ouvrir: file:///" . realpath($filename) . "\n\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur: {$e->getMessage()}\n\n";
    }
}

echo "✅ Test terminé!\n";
echo "\n💡 Ouvre les fichiers HTML dans ton navigateur pour voir le rendu\n";
