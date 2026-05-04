<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'telephone',
        'etablissement_id',
        'cree_par',
        'actif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'actif' => 'boolean',
    ];

    // Relations
    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function createurDuCompte()
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function utilisateursCrees()
    {
        return $this->hasMany(User::class, 'cree_par');
    }

    public function photos()
    {
        return $this->hasMany(Photo::class, 'operateur_id');
    }

    public function cartesImprimees()
    {
        return $this->hasMany(CarteScolaire::class, 'imprimee_par');
    }

    public function historiqueActions()
    {
        return $this->hasMany(HistoriqueAction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeEtablissement($query, $etablissementId)
    {
        return $query->where('etablissement_id', $etablissementId);
    }

    // Accessors
    public function getNomCompletAttribute()
    {
        return "{$this->prenom} {$this->nom}";
    }

    // Helpers
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isProviseur()
    {
        return $this->role === 'proviseur';
    }

    public function isSurveillant()
    {
        return $this->role === 'surveillant';
    }

    public function isOperateur()
    {
        return $this->role === 'operateur';
    }
}
