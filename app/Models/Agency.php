<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\TenantScoped;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * AGENCY RELATIONSHIPS
     */

    // All users in this agency
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // All contacts owned by this agency
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    // All activities
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    // All service events
    public function serviceEvents()
    {
        return $this->hasMany(ServiceEvent::class);
    }

    // All CRM events
    public function crmEvents()
    {
        return $this->hasMany(CrmEvent::class);
    }

    // All contact relations (beneficiaries, emergency contacts)
    public function relations()
    {
        return $this->hasMany(ContactRelation::class);
    }
}
