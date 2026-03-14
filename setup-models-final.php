<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModeleCarte;

echo "🎨 Configuration FINALE des modèles de cartes\n";
echo "============================================\n\n";

// Supprimer tous les anciens modèles
ModeleCarte::truncate();
echo "✅ Anciens modèles supprimés\n\n";

// Créer les 3 nouveaux modèles
$modeles = [
    [
        'nom_modele' => 'Modèle Standard 📘',
        'description' => 'Design classique et professionnel avec bordures bleues',
        'fichier_template' => 'cartes.modele-standard',
        'actif' => true,
        'est_defaut' => true,
    ],
    [
        'nom_modele' => 'Modèle Premium ✨',
        'description' => 'Design moderne avec dégradés et ombres',
        'fichier_template' => 'cartes.modele-premium',
        'actif' => true,
        'est_defaut' => false,
    ],
    [
        'nom_modele' => 'Modèle Cameroun 🇨🇲',
        'description' => 'Carte avec drapeau camerounais et devise',
        'fichier_template' => 'cartes.modele-cameroun',
        'actif' => true,
        'est_defaut' => false,
    ],
];

foreach ($modeles as $modele) {
    $created = ModeleCarte::create($modele);
    echo "✅ {$modele['nom_modele']}\n";
    echo "   Template: {$modele['fichier_template']}\n";
    echo "   Défaut: " . ($modele['est_defaut'] ? 'OUI' : 'NON') . "\n\n";
}

echo "✅ Configuration terminée !\n";
echo "\n📋 Résumé:\n";
echo "- 3 modèles créés\n";
echo "- Tous avec: logo, photo, classe, QR code\n";
echo "- Modèle par défaut: Standard 📘\n";
