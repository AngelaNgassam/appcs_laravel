<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModeleCarte;

echo "🔄 Mise à jour de tous les modèles de cartes...\n";
echo "===============================================\n\n";

$modeles = ModeleCarte::all();

foreach ($modeles as $modele) {
    echo "Modèle: {$modele->nom_modele}\n";
    echo "  Ancien template: {$modele->fichier_template}\n";
    
    // Mettre à jour pour utiliser le nouveau template
    $modele->fichier_template = 'cartes.template';
    $modele->save();
    
    echo "  ✅ Nouveau template: {$modele->fichier_template}\n\n";
}

echo "✅ Tous les modèles ont été mis à jour !\n";
