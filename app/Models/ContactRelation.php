<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'type',
        'name',
        'relationship',
        'phone',
        'contacted',
        'tenant_id',
        'created_by',
    ];

    protected $casts = [
        'contacted' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
