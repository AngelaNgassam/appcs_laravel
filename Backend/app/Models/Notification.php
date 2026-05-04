<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'lue',
        'date_lecture',
    ];

    protected $casts = [
        'lue' => 'boolean',
        'date_lecture' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeNonLue($query)
    {
        return $query->where('lue', false);
    }

    public function scopeLue($query)
    {
        return $query->where('lue', true);
    }

    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function marquerCommeLue()
    {
        $this->update([
            'lue' => true,
            'date_lecture' => now(),
        ]);
    }
}
