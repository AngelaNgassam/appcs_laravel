<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'etablissement_id',
        'annee_academique_id',
        'nom',
        'niveau',
        'serie',
        'effectif',
    ];

    protected $casts = [
        'effectif' => 'integer',
    ];

    // Relations
    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeAcademique()
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class);
    }

    public function elevesActifs()
    {
        return $this->hasMany(Eleve::class)->where('archive', false);
    }

    public function elevesArchives()
    {
        return $this->hasMany(Eleve::class)->where('archive', true);
    }

    // Scopes
    public function scopeEtablissement($query, $etablissementId)
    {
        return $query->where('etablissement_id', $etablissementId);
    }

    public function scopeNiveau($query, $niveau)
    {
        return $query->where('niveau', $niveau);
    }

    public function scopeAnnee($query, $anneeId)
    {
        return $query->where('annee_academique_id', $anneeId);
    }

    // Helpers
    public function updateEffectif()
    {
        $this->effectif = $this->elevesActifs()->count();
        $this->save();
    }
}
