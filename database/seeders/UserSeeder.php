<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Etablissement;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer un établissement pour les tests
        $etablissement = Etablissement::create([
            'nom' => 'Établissement Test',
            'adresse' => '123 Rue Test',
            'ville' => 'Yaoundé',
            'telephone' => '237123456789',
            'email' => 'test@etablissement.cm',
        ]);

        // Admin
        $admin = User::create([
            'nom' => 'prince',
            'prenom' => 'hybrel',
            'email' => 'princehybrel@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'etablissement_id' => $etablissement->id,
            'actif' => true,
        ]);

        // Proviseur
        $proviseur = User::create([
            'nom' => 'Proviseur',
            'prenom' => 'Test',
            'email' => 'proviseur@test.com',
            'password' => Hash::make('password123'),
            'role' => 'proviseur',
            'etablissement_id' => $etablissement->id,
            'actif' => true,
        ]);

        // Mettre à jour l'établissement avec le proviseur
        $etablissement->proviseur_id = $proviseur->id;
        $etablissement->save();

        // Surveillant
        User::create([
            'nom' => 'Surveillant',
            'prenom' => 'Test',
            'email' => 'surveillant@test.com',
            'password' => Hash::make('password123'),
            'role' => 'surveillant',
            'etablissement_id' => $etablissement->id,
            'actif' => true,
        ]);

        // Opérateur
        User::create([
            'nom' => 'Operateur',
            'prenom' => 'Test',
            'email' => 'operateur@test.com',
            'password' => Hash::make('password123'),
            'role' => 'operateur',
            'etablissement_id' => $etablissement->id,
            'actif' => true,
        ]);
    }
}
