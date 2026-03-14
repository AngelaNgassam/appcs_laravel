<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Services\PDFService;

echo "🔍 Recherche d'un autre élève...\n";
echo "================================\n\n";

// Récupérer un élève différent (pas le premier)
$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])
    ->where('id', '!=', 1)
    ->first();

if (!$eleve) {
    echo "❌ Aucun autre élève trouvé, utilisons le premier\n";
    $eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])->first();
}

if (!$eleve) {
    echo "❌ Aucun élève dans la base de données\n";
    exit(1);
}

echo "✅ Élève sélectionné : {$eleve->nom} {$eleve->prenom}\n";
echo "   ID : {$eleve->id}\n";
echo "   Matricule : {$eleve->matricule}\n";
echo "   Classe : " . ($eleve->classe->nom ?? 'N/A') . "\n";
echo "   Établissement : " . ($eleve->etablissement->nom ?? 'N/A') . "\n";

// Vérifier la photo
if (!$eleve->photoActive) {
    echo "❌ Aucune photo validée pour cet élève\n";
    echo "   Essayons quand même...\n";
}

echo "\n🔄 Génération de la carte en cours...\n";

try {
    $pdfService = app(PDFService::class);
    $carte = $pdfService->genererCarte($eleve);
    
    echo "✅ Carte générée avec succès !\n";
    echo "   Chemin : {$carte->chemin_pdf}\n";
    echo "   Statut : {$carte->statut}\n";
    echo "   Date : {$carte->date_generation}\n\n";
    
    $fullPath = storage_path('app/public/' . $carte->chemin_pdf);
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        echo "   Taille du fichier : " . number_format($size / 1024, 2) . " KB\n";
        echo "   Fichier : {$fullPath}\n";
    }
    
    echo "\n✅ Carte générée pour {$eleve->nom} {$eleve->prenom} !\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur : {$e->getMessage()}\n";
    exit(1);
}
