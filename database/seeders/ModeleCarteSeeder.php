<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModeleCarte;

class ModeleCarteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supprimer les anciens modèles globaux pour éviter les doublons
        ModeleCarte::whereNull('etablissement_id')->delete();

        // Modèle 1: Cameroun (Par défaut) - Avec drapeau tricolore
        ModeleCarte::create([
            'etablissement_id' => null,
            'nom_modele' => 'Modèle Cameroun 🇨🇲',
            'fichier_template' => 'cartes.modele-cameroun',
            'configuration' => json_encode([
                'description' => 'Design officiel avec drapeau du Cameroun',
                'features' => ['Drapeau tricolore', 'Devise nationale', 'Design professionnel']
            ]),
            'actif' => true,
            'est_defaut' => true,
        ]);

        // Modèle 2: Standard - Design professionnel bleu
        ModeleCarte::create([
            'etablissement_id' => null,
            'nom_modele' => 'Modèle Standard 📘',
            'fichier_template' => 'cartes.modele-standard',
            'configuration' => json_encode([
                'description' => 'Design professionnel avec dégradé bleu',
                'features' => ['Dégradé bleu', 'Design épuré', 'Moderne']
            ]),
            'actif' => true,
            'est_defaut' => false,
        ]);

        // Modèle 3: Premium - Design moderne avec gradients
        ModeleCarte::create([
            'etablissement_id' => null,
            'nom_modele' => 'Modèle Premium ✨',
            'fichier_template' => 'cartes.modele-premium',
            'configuration' => json_encode([
                'description' => 'Design premium avec gradients modernes',
                'features' => ['Gradients cyan', 'Design contemporain', 'Ombre élégante']
            ]),
            'actif' => true,
            'est_defaut' => false,
        ]);

        $this->command->info('✅ 3 modèles de cartes créés avec succès!');
    }
}
