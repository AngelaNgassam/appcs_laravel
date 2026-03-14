<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Models\ModeleCarte;
use App\Services\PDFService;

echo "🧪 Test de rendu PDF\n";
echo "====================\n\n";

// Trouver un élève avec photo
$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])
    ->whereHas('photoActive', function($q) {
        $q->where('statut', 'validee');
    })
    ->first();

if (!$eleve) {
    echo "❌ Aucun élève avec photo validée trouvé\n";
    
    // Afficher les élèves disponibles
    $eleves = Eleve::with('photoActive')->limit(10)->get();
    echo "\n📋 Élèves disponibles:\n";
    foreach ($eleves as $e) {
        $photo = $e->photoActive ? "✓ ({$e->photoActive->statut})" : "✗";
        echo "   ID: {$e->id} - {$e->nom} {$e->prenom} - Photo: {$photo}\n";
    }
    exit(1);
}

echo "✅ Élève trouvé: {$eleve->nom} {$eleve->prenom}\n";
echo "   Classe: {$eleve->classe->nom}\n";
echo "   Établissement: {$eleve->etablissement->nom}\n";
echo "   Photo: {$eleve->photoActive->chemin_photo}\n\n";

$pdfService = app(PDFService::class);
$modeles = ModeleCarte::where('actif', true)->get();

echo "📊 Modèles disponibles: " . count($modeles) . "\n\n";

foreach ($modeles as $modele) {
    echo "🎨 Génération: {$modele->nom_modele}\n";
    
    try {
        $carte = $pdfService->genererCarte($eleve, $modele->id);
        $fullPath = storage_path('app/public/' . $carte->chemin_pdf);
        $fileSize = filesize($fullPath);
        
        echo "   ✅ PDF généré\n";
        echo "   📄 Chemin: {$carte->chemin_pdf}\n";
        echo "   📊 Taille: " . round($fileSize / 1024, 2) . " KB\n";
        echo "   🔗 URL: http://localhost:8000/storage/{$carte->chemin_pdf}\n\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur: {$e->getMessage()}\n\n";
    }
}

echo "✅ Test terminé!\n";
