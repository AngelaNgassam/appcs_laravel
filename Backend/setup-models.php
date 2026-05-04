<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ModeleCarte;

echo "🎨 Configuration des modèles de cartes\n";
echo "======================================\n\n";

// Supprimer les anciens modèles
ModeleCarte::truncate();

// Créer les 4 nouveaux modèles
$modeles = [
    [
        'nom_modele' => 'Modèle Cameroun 🇨🇲',
        'description' => 'Carte avec drapeau camerounais, devise et étoile',
        'fichier_template' => 'cartes.modele-cameroun',
        'actif' => true,
        'est_defaut' => true,
    ],
    [
        'nom_modele' => 'Modèle Moderne ✨',
        'description' => 'Design épuré et professionnel avec dégradés verts',
        'fichier_template' => 'cartes.modele-moderne',
        'actif' => true,
        'est_defaut' => false,
    ],
    [
        'nom_modele' => 'Modèle Classique 📘',
        'description' => 'Style académique traditionnel avec bordures bleues',
        'fichier_template' => 'cartes.modele-classique',
        'actif' => true,
        'est_defaut' => false,
    ],
    [
        'nom_modele' => 'Modèle Compact 🎯',
        'description' => 'Minimaliste et efficace avec tons orangés',
        'fichier_template' => 'cartes.modele-compact',
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

echo "✅ Tous les modèles ont été configurés !\n";
echo "\n📋 Résumé:\n";
echo "- 4 modèles créés\n";
echo "- Tous sur UN SEUL CADRE\n";
echo "- Tous avec photo, classe, QR code visibles\n";
echo "- Modèle par défaut: Cameroun 🇨🇲\n";
