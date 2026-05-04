<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;

echo "🔍 Vérification des données de l'élève...\n";
echo "=========================================\n\n";

$eleve = Eleve::with(['classe', 'etablissement', 'photoActive'])->find(2);

if (!$eleve) {
    echo "❌ Élève non trouvé\n";
    exit(1);
}

echo "Élève: {$eleve->nom} {$eleve->prenom}\n";
echo "Matricule: {$eleve->matricule}\n\n";

echo "📧 Email: " . ($eleve->email ?: '❌ MANQUANT') . "\n";
echo "📚 Classe: " . ($eleve->classe ? $eleve->classe->nom : '❌ MANQUANT') . "\n";
echo "📸 Photo: " . ($eleve->photoActive ? '✅ OUI' : '❌ MANQUANT') . "\n";

if ($eleve->photoActive) {
    $photo = $eleve->photoActive;
    echo "   - Chemin: {$photo->chemin_photo}\n";
    echo "   - Traité: " . ($photo->photo_traitee ?: 'Non') . "\n";
    echo "   - Statut: {$photo->statut}\n";
    
    $photoPath = storage_path('app/public/' . ($photo->photo_traitee ?: $photo->chemin_photo));
    echo "   - Fichier existe: " . (file_exists($photoPath) ? '✅ OUI' : '❌ NON') . "\n";
    
    if (file_exists($photoPath)) {
        echo "   - Chemin complet: {$photoPath}\n";
    }
}

echo "\n🏫 Établissement: " . ($eleve->etablissement ? $eleve->etablissement->nom : '❌ MANQUANT') . "\n";

// Vérifier tous les élèves
echo "\n\n📋 Liste de tous les élèves:\n";
echo "============================\n";

$eleves = Eleve::with(['classe', 'photoActive'])->get();

foreach ($eleves as $e) {
    echo "\nID: {$e->id} - {$e->nom} {$e->prenom}\n";
    echo "  Email: " . ($e->email ?: '❌ Manquant') . "\n";
    echo "  Classe: " . ($e->classe ? $e->classe->nom : '❌ Manquant') . "\n";
    echo "  Photo: " . ($e->photoActive ? '✅ Oui' : '❌ Manquant') . "\n";
}
