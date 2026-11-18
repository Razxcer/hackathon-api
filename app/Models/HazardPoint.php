<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HazardPoint extends Model
{
     protected $fillable = [
        'title',
        'description',
        'latitude',
        'longitude',
        'danger_radius',
        'photo_path',
        'status',
        'created_by',
        'moderated_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'danger_radius' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
