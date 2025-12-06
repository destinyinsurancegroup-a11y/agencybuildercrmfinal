<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'agency_id', // REQUIRED for multi-tenancy: user belongs to an agency/tenant
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        // uses the global hasher (we'll configure Argon2id in config/hashing.php)
        'password' => 'hashed',
    ];

    /**
     * Agency (Tenant) relationship.
     *
     * Every user belongs to exactly one agency/tenant.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
