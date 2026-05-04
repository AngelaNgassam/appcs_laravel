<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Models\ModeleCarte;
use App\Services\PDFService;

echo "🧪 Génération carte pour TATA Pap\n";
echo "==================================\n\n";

$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])->find(6);

if (!$eleve) {
    echo "❌ Élève non trouvé\n";
    exit(1);
}

echo "✅ Élève : {$eleve->nom} {$eleve->prenom}\n";
echo "   Matricule : {$eleve->matricule}\n";
echo "   Classe : {$eleve->classe->nom}\n\n";

// Forcer le modèle Cameroun
$modeleCameroun = ModeleCarte::where('fichier_template', 'cartes.modele-cameroun')->first();

if (!$modeleCameroun) {
    echo "❌ Modèle Cameroun non trouvé\n";
    exit(1);
}

echo "🎨 Utilisation du modèle : {$modeleCameroun->nom_modele}\n";
echo "   Template : {$modeleCameroun->fichier_template}\n\n";

$pdfService = app(PDFService::class);

try {
    $carte = $pdfService->genererCarte($eleve, $modeleCameroun->id);
    
    echo "✅ Carte générée !\n";
    echo "   Fichier : {$carte->chemin_pdf}\n";
    
    $fullPath = storage_path('app/public/' . $carte->chemin_pdf);
    echo "   Chemin : {$fullPath}\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur : {$e->getMessage()}\n";
    echo "   Trace : {$e->getTraceAsString()}\n";
    exit(1);
}
