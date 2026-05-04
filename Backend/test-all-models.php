<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Models\ModeleCarte;
use App\Services\PDFService;

echo "🧪 Test de tous les modèles de cartes\n";
echo "=====================================\n\n";

$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])->find(3);

if (!$eleve) {
    echo "❌ Élève non trouvé\n";
    exit(1);
}

echo "Élève: {$eleve->nom} {$eleve->prenom}\n";
echo "Classe: {$eleve->classe->nom}\n\n";

$pdfService = app(PDFService::class);
$modeles = ModeleCarte::where('actif', true)->get();

foreach ($modeles as $modele) {
    echo "🎨 Test du modèle: {$modele->nom_modele}\n";
    
    try {
        $carte = $pdfService->genererCarte($eleve, $modele->id);
        echo "   ✅ Carte générée: {$carte->chemin_pdf}\n";
        
        $fullPath = storage_path('app/public/' . $carte->chemin_pdf);
        echo "   📄 Fichier: {$fullPath}\n\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur: {$e->getMessage()}\n\n";
    }
}

echo "✅ Test terminé !\n";
echo "\n📂 Les cartes sont dans: storage/app/public/cartes/\n";
