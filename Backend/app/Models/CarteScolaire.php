<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarteScolaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cartes_scolaires';

    protected $fillable = [
        'eleve_id',
        'photo_id',
        'modele_id',
        'qr_code',
        'chemin_pdf',
        'statut',
        'date_generation',
        'date_impression',
        'date_distribution',
        'imprimee_par',
        'nombre_impressions',
    ];

    protected $casts = [
        'date_generation' => 'datetime',
        'date_impression' => 'datetime',
        'date_distribution' => 'datetime',
        'nombre_impressions' => 'integer',
    ];

    // Relations
    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function photo()
    {
        return $this->belongsTo(Photo::class);
    }

    public function modele()
    {
        return $this->belongsTo(ModeleCarte::class, 'modele_id');
    }

    public function imprimeur()
    {
        return $this->belongsTo(User::class, 'imprimee_par');
    }

    // Scopes
    public function scopeGeneree($query)
    {
        return $query->where('statut', 'generee');
    }

    public function scopeImprimee($query)
    {
        return $query->where('statut', 'imprimee');
    }

    public function scopeDistribuee($query)
    {
        return $query->where('statut', 'distribuee');
    }

    public function scopeEleve($query, $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    // Accessors
    public function getPdfUrlAttribute()
    {
        return $this->chemin_pdf ? asset('storage/' . $this->chemin_pdf) : null;
    }

    // Helpers
    public function marquerCommeGeneree()
    {
        $this->update([
            'statut' => 'generee',
            'date_generation' => now(),
        ]);
    }

    public function marquerCommeImprimee($userId)
    {
        $this->increment('nombre_impressions');
        $this->update([
            'statut' => 'imprimee',
            'date_impression' => now(),
            'imprimee_par' => $userId,
        ]);
    }

    public function marquerCommeDistribuee()
    {
        $this->update([
            'statut' => 'distribuee',
            'date_distribution' => now(),
        ]);
    }
}
