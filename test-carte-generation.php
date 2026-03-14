<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Services\PDFService;

echo "🧪 Test de génération de carte scolaire\n";
echo "========================================\n\n";

// Récupérer le premier élève
$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])->first();

if (!$eleve) {
    echo "❌ Aucun élève trouvé dans la base de données\n";
    exit(1);
}

echo "✅ Élève trouvé : {$eleve->nom} {$eleve->prenom}\n";
echo "   Matricule : {$eleve->matricule}\n";
echo "   Classe : " . ($eleve->classe->nom ?? 'N/A') . "\n";
echo "   Établissement : " . ($eleve->etablissement->nom ?? 'N/A') . "\n";

// Vérifier la photo
if (!$eleve->photoActive) {
    echo "❌ Aucune photo validée pour cet élève\n";
    echo "   Veuillez d'abord valider une photo pour cet élève\n";
    exit(1);
}

echo "✅ Photo validée trouvée\n\n";

// Générer la carte
try {
    echo "🔄 Génération de la carte en cours...\n";
    
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
        echo "   Fichier accessible à : /storage/{$carte->chemin_pdf}\n";
    }
    
    echo "\n✅ Test réussi ! La carte a été générée correctement.\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur lors de la génération : {$e->getMessage()}\n";
    echo "   Trace : {$e->getTraceAsString()}\n";
    exit(1);
}
