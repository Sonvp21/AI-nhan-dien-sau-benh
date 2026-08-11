<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    protected $fillable = [
        'crop_key', 'class_key', 'name_vi', 'pathogen', 'conditions',
        'level', 'recommended_steps', 'affected_organ', 'info_source',
    ];

    protected $casts = [
        'recommended_steps' => 'array',
    ];
}
