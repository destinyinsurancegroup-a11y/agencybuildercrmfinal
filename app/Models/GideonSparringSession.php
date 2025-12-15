<?php

namespace App\Models;

use App\Enums\Gideon\TrainingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class GideonSparringSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'gideon_sparring_sessions';

    protected $fillable = [
        'agency_id',
        'user_id',

        // legacy (keep for compatibility; stop using going forward)
        'mode',

        // ✅ canonical fields (now in DB)
        'training_mode',
        'selected_stage',
        'difficulty',

        'persona_key',
        'config',
        'state',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        // If the enum exists, this is ideal:
        'training_mode' => TrainingMode::class,

        'config'     => 'array',
        'state'      => 'array',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    /**
     * 🔒 Always use this scope when loading sessions in controllers/services.
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('agency_id', $user->agency_id)
                     ->where('user_id', $user->id);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GideonSparringMessage::class, 'session_id');
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(GideonSparringAssessment::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Convenience helper for policies / guards.
     */
    public function isOwnedBy(User $user): bool
    {
        return (int)$this->user_id === (int)$user->id
            && (int)$this->agency_id === (int)$user->agency_id;
    }

    /**
     * Small helper: only set "first failed stage" once (supports latent failure).
     * Keeps your "intro" stage naming as-is.
     */
    public function setFirstFailureOnce(string $stageKey, string $reason): void
    {
        $state = $this->state ?? [];

        if (!empty($state['first_failed_stage'])) {
            return; // already set; do not overwrite
        }

        $state['first_failed_stage'] = $stageKey; // e.g. "intro"
        $state['first_failed_reason'] = $reason;

        $this->state = $state;
    }
}
