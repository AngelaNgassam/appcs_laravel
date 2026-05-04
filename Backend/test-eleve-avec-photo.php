<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Services\PDFService;

echo "🧪 Test avec un élève qui a une photo\n";
echo "=====================================\n\n";

// Élève 3 qui a une photo
$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])->find(3);

if (!$eleve) {
    echo "❌ Élève non trouvé\n";
    exit(1);
}

echo "✅ Élève : {$eleve->nom} {$eleve->prenom}\n";
echo "   Matricule : {$eleve->matricule}\n";
echo "   Classe : " . ($eleve->classe->nom ?? 'N/A') . "\n";
echo "   Email : " . ($eleve->email ?? 'N/A') . "\n";

if ($eleve->photoActive) {
    $photo = $eleve->photoActive;
    $photoPath = storage_path('app/public/' . ($photo->photo_traitee ?: $photo->chemin_photo));
    echo "   Photo : " . (file_exists($photoPath) ? '✅ Existe' : '❌ Manquante') . "\n";
    echo "   Chemin : {$photoPath}\n";
}

echo "\n🔄 Génération de la carte...\n";

try {
    $pdfService = app(PDFService::class);
    $carte = $pdfService->genererCarte($eleve);
    
    echo "✅ Carte générée !\n";
    echo "   Fichier : {$carte->chemin_pdf}\n";
    
    $fullPath = storage_path('app/public/' . $carte->chemin_pdf);
    echo "   Chemin complet : {$fullPath}\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur : {$e->getMessage()}\n";
    exit(1);
}
