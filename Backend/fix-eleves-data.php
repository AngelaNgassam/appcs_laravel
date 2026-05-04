<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Eleve;

echo "🔧 Correction des données des élèves...\n";
echo "=======================================\n\n";

$eleves = Eleve::all();

foreach ($eleves as $eleve) {
    echo "Élève: {$eleve->nom} {$eleve->prenom}\n";
    
    // Ajouter un email si manquant
    if (!$eleve->email) {
        $email = strtolower($eleve->prenom) . '.' . strtolower($eleve->nom) . '@eleve.cm';
        $eleve->email = $email;
        $eleve->save();
        echo "  ✅ Email ajouté: {$email}\n";
    } else {
        echo "  ✅ Email déjà présent: {$eleve->email}\n";
    }
}

echo "\n✅ Tous les élèves ont maintenant un email !\n";
