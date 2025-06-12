<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoCivil extends Model
{
    use HasFactory;

    protected $table = 'estados_civiles';

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // En el futuro, si los trabajadores tienen un estado_civil_id:
    // public function trabajadores()
    // {
    //     return $this->hasMany(Trabajador::class, 'estado_civil_id');
    // }
}