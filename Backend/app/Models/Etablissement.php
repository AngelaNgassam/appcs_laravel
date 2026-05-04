<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etablissement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'adresse',
        'telephone',
        'email',
        'logo',
        'ville',
        'proviseur_id',
    ];

    // Relations
    public function proviseur()
    {
        return $this->belongsTo(User::class, 'proviseur_id');
    }

    public function utilisateurs()
    {
        return $this->hasMany(User::class);
    }

    public function anneesAcademiques()
    {
        return $this->hasMany(AnneeAcademique::class);
    }

    public function anneeActive()
    {
        return $this->hasOne(AnneeAcademique::class)->where('active', true);
    }

    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class);
    }

    public function modelesCartes()
    {
        return $this->hasMany(ModeleCarte::class);
    }

    public function modeleActif()
    {
        return $this->hasOne(ModeleCarte::class)->where('actif', true);
    }

    // Scopes
    public function scopeVille($query, $ville)
    {
        return $query->where('ville', $ville);
    }

    // Accessors
    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
