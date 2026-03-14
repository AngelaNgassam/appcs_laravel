<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;
use App\Models\Classe;

echo "📋 Vérification des élèves\n";
echo "==========================\n\n";

// Tous les élèves
$tous = Eleve::withTrashed()->get();
echo "Total élèves (avec soft delete): " . $tous->count() . "\n";

// Élèves actifs
$actifs = Eleve::where('archive', false)->get();
echo "Élèves actifs (archive=false): " . $actifs->count() . "\n\n";

// Détails
echo "Détails des élèves:\n";
echo "-------------------\n";
foreach ($tous as $eleve) {
    $classe = Classe::find($eleve->classe_id);
    $photo = $eleve->photoActive;
    
    echo "ID: {$eleve->id} | {$eleve->nom} {$eleve->prenom}\n";
    echo "  Classe: {$eleve->classe_id} (" . ($classe ? $classe->nom : 'INEXISTANTE') . ")\n";
    echo "  Archive: " . ($eleve->archive ? 'OUI' : 'NON') . "\n";
    echo "  Soft Delete: " . ($eleve->deleted_at ? 'OUI (' . $eleve->deleted_at . ')' : 'NON') . "\n";
    echo "  Photo Active: " . ($photo ? 'OUI (statut: ' . $photo->statut . ')' : 'NON') . "\n";
    echo "\n";
}

echo "\n📊 Résumé par classe:\n";
echo "--------------------\n";
$classes = Classe::all();
foreach ($classes as $classe) {
    $count = Eleve::where('classe_id', $classe->id)->where('archive', false)->count();
    $withPhoto = Eleve::where('classe_id', $classe->id)
        ->where('archive', false)
        ->whereHas('photoActive', function($q) {
            $q->where('statut', 'validee');
        })
        ->count();
    
    echo "{$classe->nom}: {$count} élèves ({$withPhoto} avec photo validée)\n";
}
