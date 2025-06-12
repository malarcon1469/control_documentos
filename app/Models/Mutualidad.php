<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutualidad extends Model
{
    use HasFactory;

    protected $table = 'mutualidades';

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relación futura con Contratista
    // public function contratistas()
    // {
    //     return $this->hasMany(Contratista::class, 'mutualidad_id');
    // }
}