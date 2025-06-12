<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <-- AÑADIDA ESTA IMPORTACIÓN

class Nacionalidad extends Model
{
    use HasFactory;

    protected $table = 'nacionalidades';

    protected $fillable = [
        'nombre',
        'codigo_iso_3166_1_alpha_2',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class);
    }

    // --- NUEVA RELACIÓN BelongsToMany A REGLAS DOCUMENTALES ---
    /**
     * Las reglas documentales que aplican a esta nacionalidad.
     */
    public function reglasDocumentales(): BelongsToMany
    {
        return $this->belongsToMany(
            ReglaDocumental::class,                  // Modelo relacionado
            'regla_documental_nacionalidad',         // Nombre de la tabla pivote
            'nacionalidad_id',                       // Clave foránea de este modelo en la tabla pivote
            'regla_documental_id'                    // Clave foránea del modelo relacionado en la tabla pivote
        );
    }
}