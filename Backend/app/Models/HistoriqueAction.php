<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'cible_type',
        'cible_id',
        'details',
        'ip_address',
        'user_agent',
        'date_action',
    ];

    protected $casts = [
        'date_action' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cible()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_action', 'desc');
    }
}
