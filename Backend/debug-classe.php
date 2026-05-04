<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Classe;
use App\Models\Eleve;

echo "🔍 Debug Classe 6e 1\n";
echo "====================\n\n";

$classe = Classe::find(1);
echo "Classe: {$classe->nom}\n";
echo "ID: {$classe->id}\n\n";

// Requête exacte du backend
$elevesActifs = Eleve::where('classe_id', 1)
    ->where('archive', false)
    ->with(['photoActive', 'carteActive'])
    ->orderBy('nom')
    ->orderBy('prenom')
    ->get();

echo "Élèves retournés par la requête backend:\n";
echo "----------------------------------------\n";
foreach ($elevesActifs as $e) {
    echo "ID: {$e->id} | {$e->nom} {$e->prenom}\n";
    echo "  Photo Active: " . ($e->photoActive ? 'OUI' : 'NON') . "\n";
    echo "  Carte Active: " . ($e->carteActive ? 'OUI' : 'NON') . "\n";
}

echo "\n\nTous les élèves de la classe (sans filtre):\n";
echo "-------------------------------------------\n";
$tous = Eleve::where('classe_id', 1)->get();
foreach ($tous as $e) {
    echo "ID: {$e->id} | {$e->nom} {$e->prenom} | Archive: {$e->archive} | Deleted: {$e->deleted_at}\n";
}
