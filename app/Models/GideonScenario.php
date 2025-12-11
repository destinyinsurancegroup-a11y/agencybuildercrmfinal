<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GideonScenario extends Model
{
    use HasFactory;

    protected $table = 'gideon_scenarios';

    protected $fillable = [
        'name',
        'code',
        'product_type',
        'starting_stage_id',
        'description',
        'prospect_profile',
        'script_engine',
        'is_active',
    ];

    protected $casts = [
        'prospect_profile' => 'array',
        'script_engine' => 'array',
    ];

    public function startingStage()
    {
        return $this->belongsTo(GideonStage::class, 'starting_stage_id');
    }
}
