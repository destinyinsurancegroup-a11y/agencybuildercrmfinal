<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GideonSparringMessage extends Model
{
    use HasFactory;

    protected $table = 'gideon_sparring_messages';

    protected $fillable = [
        'session_id',
        'agency_id',
        'user_id',

        // ✅ must match DB column name
        'role',     // agent | prospect | coach/system

        'content',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Ledger safety: messages are append-only (no edits).
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('Sparring messages are immutable (append-only ledger).');
        });
    }

    /**
     * 🔒 Tenant safety scope.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('agency_id', $user->agency_id)
            ->where('user_id', $user->id);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GideonSparringSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
