<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModeleCarte;

echo "📋 Modèles de cartes dans la base de données:\n";
echo "=============================================\n\n";

$modeles = ModeleCarte::all();

if ($modeles->isEmpty()) {
    echo "❌ Aucun modèle trouvé dans la base de données\n";
} else {
    foreach ($modeles as $modele) {
        echo "ID: {$modele->id}\n";
        echo "Nom: {$modele->nom_modele}\n";
        echo "Template: {$modele->fichier_template}\n";
        echo "Actif: " . ($modele->actif ? '✅ OUI' : '❌ NON') . "\n";
        echo "Défaut: " . ($modele->est_defaut ? '✅ OUI' : '❌ NON') . "\n";
        echo "---\n";
    }
}

echo "\n🎯 Template qui sera utilisé par défaut:\n";
$defaut = ModeleCarte::where('actif', true)
    ->orderByDesc('est_defaut')
    ->latest()
    ->first();

if ($defaut) {
    echo "✅ {$defaut->nom_modele} => {$defaut->fichier_template}\n";
} else {
    echo "⚠️  Aucun modèle actif, utilisation de: cartes.template-cameroun\n";
}
