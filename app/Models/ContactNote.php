<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactNote extends Model
{
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'created_by',
        'body',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
