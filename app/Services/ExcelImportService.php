<?php

namespace App\Services;

use App\Models\Eleve;
use App\Models\Classe;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ExcelImportService
{
    /**
     * Importer des élèves depuis Excel
     */
    public function importerEleves($file, int $classeId, int $etablissementId): array
    {
        $classe = Classe::findOrFail($classeId);

        $data = Excel::toArray([], $file)[0];

        $importes = 0;
        $erreurs = [];

        // Ignorer la première ligne (en-têtes)
        array_shift($data);

        foreach ($data as $index => $row) {
            $ligne = $index + 2; // +2 car on a supprimé la ligne d'en-tête

            try {
                // ✅ Convertir la date de naissance
                $dateNaissance = $this->parseDate($row[3]);

                if (!$dateNaissance) {
                    $erreurs[] = "Ligne {$ligne}: Format de date invalide. Utilisez JJ/MM/AAAA (ex: 05/11/2011)";
                    continue;
                }

                // Valider les données
                $validator = Validator::make([
                    'matricule' => $row[0] ?? null,
                    'nom' => $row[1] ?? null,
                    'prenom' => $row[2] ?? null,
                    'date_naissance' => $dateNaissance,
                    'sexe' => $row[4] ?? null,
                ], [
                    'matricule' => 'required|string|max:50',
                    'nom' => 'required|string|max:100',
                    'prenom' => 'required|string|max:100',
                    'date_naissance' => 'required|date|before:today',
                    'sexe' => 'required|in:M,F',
                ]);

                if ($validator->fails()) {
                    $erreurs[] = "Ligne {$ligne}: " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Vérifier si le matricule existe déjà
                $eleveExistant = Eleve::where('matricule', $row[0])->first();

                if ($eleveExistant) {
                    $erreurs[] = "Ligne {$ligne}: Matricule {$row[0]} existe déjà";
                    continue;
                }

                // Créer l'élève
                Eleve::create([
                    'etablissement_id' => $etablissementId,
                    'classe_id' => $classeId,
                    'matricule' => $row[0],
                    'nom' => strtoupper($row[1]),
                    'prenom' => ucwords(strtolower($row[2])),
                    'date_naissance' => $dateNaissance,
                    'lieu_naissance' => $row[5] ?? null,
                    'sexe' => strtoupper($row[4]),
                    'contact_parent' => $row[6] ?? null,
                    'nom_parent' => $row[7] ?? null,
                ]);

                $importes++;

            } catch (\Exception $e) {
                $erreurs[] = "Ligne {$ligne}: " . $e->getMessage();
            }
        }

        // Mettre à jour l'effectif de la classe
        $classe->updateEffectif();

        return [
            'importes' => $importes,
            'erreurs' => $erreurs,
            'total' => count($data),
        ];
    }

    /**
     * ✅ Convertir différents formats de date en Y-m-d
     */
    private function parseDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            // Si c'est déjà une date Carbon/DateTime
            if ($dateValue instanceof \DateTime) {
                return $dateValue->format('Y-m-d');
            }

            // Si c'est un nombre (format Excel)
            if (is_numeric($dateValue)) {
                return Carbon::createFromFormat('U', ($dateValue - 25569) * 86400)->format('Y-m-d');
            }

            // Essayer différents formats
            $formats = [
                'd/m/Y',     // 05/11/2011
                'd-m-Y',     // 05-11-2011
                'd.m.Y',     // 05.11.2011
                'Y-m-d',     // 2011-11-05
                'd/m/y',     // 05/11/11
                'd-m-y',     // 05-11-11
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateValue);
                    if ($date && $date->year >= 1900 && $date->year <= date('Y')) {
                        return $date->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Dernier recours : laisser Carbon deviner
            return Carbon::parse($dateValue)->format('Y-m-d');

        } catch (\Exception $e) {
            return null;
        }
    }
}
