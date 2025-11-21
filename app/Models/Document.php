<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'contact_id',
        'original_name',
        'file_path',
        'url',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
