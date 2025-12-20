<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\TenantScoped;
use App\Models\User;
use App\Models\Agency;

class Activity extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The attributes that are mass assignable.
     *
     * NOTE:
     * - The DB schema uses tenant_id (see activities migration).
     * - tenant_id is safe to mass-assign here because:
     *   - Routes are behind auth middleware
     *   - Controller sets tenant_id from the authenticated user
     *   - TenantScoped also auto-sets tenant_id on create
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',          // ✅ canonical multi-tenant scoping field
        'user_id',

        'leads_worked',
        'calls',
        'stops',
        'presentations',
        'apps_written',
        'premium_collected',
        'ap',

        // You appear to manually set created_at in some places
        'created_at',
    ];

    /**
     * Use Laravel's timestamps (created_at / updated_at).
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The user this activity belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The agency/tenant that owns this activity.
     *
     * NOTE: DB uses tenant_id, so we map this relationship via tenant_id.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'tenant_id');
    }
}
