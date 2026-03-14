<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Eleve extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'etablissement_id',
        'classe_id',
        'matricule',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'contact_parent',
        'nom_parent',
        'archive',
        'date_archivage',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'archive' => 'boolean',
        'date_archivage' => 'datetime',
    ];

    // Relations
    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function photos()
    {
        return $this->hasMany(Photo::class);
    }

    public function photoActive()
    {
        return $this->hasOne(Photo::class)->where('active', true)->latest();
    }

    public function cartes()
    {
        return $this->hasMany(CarteScolaire::class);
    }

    public function carteActive()
    {
        return $this->hasOne(CarteScolaire::class)->latest();
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('archive', false);
    }

    public function scopeArchive($query)
    {
        return $query->where('archive', true);
    }

    // Accessors
    public function getNomCompletAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function getAgeAttribute()
    {
        return $this->date_naissance ? $this->date_naissance->age : null;
    }

    // Helpers
    public function archiver()
    {
        $this->update([
            'archive' => true,
            'date_archivage' => now(),
        ]);
    }

    public function desarchiver()
    {
        $this->update([
            'archive' => false,
            'date_archivage' => null,
        ]);
    }

    public function hasPhoto()
    {
        return $this->photoActive()->where('statut', 'validee')->exists();
    }

    public function hasCarte()
    {
        return $this->carteActive()->exists();
    }
}
