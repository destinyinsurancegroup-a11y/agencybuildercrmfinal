<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        // add other fields from agencies table as needed
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Optional: relate to contacts, leads, etc. if you want
}
