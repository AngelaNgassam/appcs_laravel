<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModeleCarte extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'modele_cartes';

    protected $appends = [
        'apercu_url',
    ];

    protected $fillable = [
        'etablissement_id',
        'nom_modele',
        'fichier_template',
        'apercu',
        'configuration',
        'actif',
        'est_defaut',
    ];

    protected $casts = [
        'configuration' => 'array',
        'actif' => 'boolean',
        'est_defaut' => 'boolean',
    ];

    // Relations
    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function cartes()
    {
        return $this->hasMany(CarteScolaire::class, 'modele_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeDefaut($query)
    {
        return $query->where('est_defaut', true);
    }

    public function scopeEtablissement($query, $etablissementId)
    {
        return $query->where('etablissement_id', $etablissementId);
    }

    // Accessors
    public function getApercuUrlAttribute()
    {
        return $this->apercu ? asset('storage/' . $this->apercu) : null;
    }
}
