<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    protected $fillable = [
        'contact_id',
        'name',
        'relationship',
        'phone',
        'contacted',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
