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
     * Note: we no longer expose tenant_id here. Multi-tenancy is driven by agency_id.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_id',          // multi-tenant scoping field
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
     * The agency (tenant) that owns this activity.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
