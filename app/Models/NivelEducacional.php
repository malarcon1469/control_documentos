<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NivelEducacional extends Model
{
    use HasFactory;

    protected $table = 'niveles_educacionales';

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // En el futuro, si los trabajadores tienen un nivel_educacional_id:
    // public function trabajadores()
    // {
    //     return $this->hasMany(Trabajador::class, 'nivel_educacional_id');
    // }
}