<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CondicionFechaIngreso extends Model
{
    use HasFactory;

    protected $table = 'condiciones_fecha_ingreso';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_tope_anterior_o_igual',
        'fecha_tope_posterior_o_igual',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fecha_tope_anterior_o_igual' => 'date:Y-m-d', // Para asegurar el formato correcto
        'fecha_tope_posterior_o_igual' => 'date:Y-m-d', // Para asegurar el formato correcto
    ];
}