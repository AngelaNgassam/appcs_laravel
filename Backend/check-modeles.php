<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModeleCarte;

echo "📋 Modèles de cartes en base:\n";
echo "=============================\n\n";

$modeles = ModeleCarte::all();
echo "Total: " . $modeles->count() . "\n\n";

foreach ($modeles as $m) {
    echo "ID: {$m->id} | {$m->nom_modele}\n";
    echo "  Template: {$m->fichier_template}\n";
    echo "  Actif: " . ($m->actif ? 'OUI' : 'NON') . "\n";
    echo "  Défaut: " . ($m->est_defaut ? 'OUI' : 'NON') . "\n\n";
}
