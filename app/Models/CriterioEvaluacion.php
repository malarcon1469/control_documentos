<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CriterioEvaluacion extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'criterios_evaluacion'; // <--- AÑADE O CORRIGE ESTA LÍNEA

    protected $fillable = [
        'nombre_criterio',
        'descripcion_criterio',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Aquí podrías añadir relaciones en el futuro si es necesario, por ejemplo:
    // Un criterio puede estar asociado a muchas "Aclaraciones de Criterio"
    // public function aclaraciones() { return $this->hasMany(AclaracionCriterio::class); }
}