<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\TenantScoped;

class Document extends Model
{
    use TenantScoped;

    protected $fillable = [
        'contact_id',
        'original_name',
        'file_path',
        'url',
        'agency_id',   // required for multi-tenant scoping
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
