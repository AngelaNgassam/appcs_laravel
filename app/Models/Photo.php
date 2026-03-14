<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Photo extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'photo_url',
        'photo_originale_url',
        'photo_traitee_url',
    ];

    protected $fillable = [
        'eleve_id',
        'operateur_id',
        'chemin_photo',
        'photo_originale',
        'photo_traitee',
        'statut',
        'motif_refus',
        'date_prise',
        'active',
    ];

    protected $casts = [
        'date_prise' => 'datetime',
        'active' => 'boolean',
    ];

    // Relations
    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function operateur()
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    public function cartes()
    {
        return $this->hasMany(CarteScolaire::class);
    }

    // Scopes
    public function scopeValidee($query)
    {
        return $query->where('statut', 'validee');
}
public function scopeActive($query)
{
    return $query->where('active', true);
}

public function scopeEleve($query, $eleveId)
{
    return $query->where('eleve_id', $eleveId);
}

public function scopeOperateur($query, $operateurId)
{
    return $query->where('operateur_id', $operateurId);
}

// Accessors
public function getPhotoUrlAttribute()
{
    return $this->chemin_photo ? asset('storage/' . $this->chemin_photo) : null;
}

public function getPhotoOriginaleUrlAttribute()
{
    return $this->photo_originale ? asset('storage/' . $this->photo_originale) : null;
}

public function getPhotoTraiteeUrlAttribute()
{
    return $this->photo_traitee ? asset('storage/' . $this->photo_traitee) : null;
}

// Helpers
public function valider()
{
    $this->update(['statut' => 'validee']);
}

public function refuser($motif)
{
    $this->update([
        'statut' => 'refusee',
        'motif_refus' => $motif,
    ]);
}

public function archiver()
{
    $this->update([
        'statut' => 'archivee',
        'active' => false,
    ]);
}
}
