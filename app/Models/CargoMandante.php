<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // <-- AÑADIDA ESTA IMPORTACIÓN

class CargoMandante extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'cargos_mandante';

    protected $fillable = [
        'mandante_id',
        'nombre_cargo',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * El mandante al que pertenece este cargo.
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    /**
     * Obtiene las vinculaciones de trabajadores asociadas a este cargo.
     * Un cargo puede estar en muchas vinculaciones de trabajadores.
     */
    public function trabajadorVinculaciones(): HasMany
    {
        return $this->hasMany(TrabajadorVinculacion::class, 'cargo_mandante_id');
    }


    // --- NUEVA RELACIÓN BelongsToMany A REGLAS DOCUMENTALES ---
    /**
     * Las reglas documentales que aplican a este cargo.
     */
    public function reglasDocumentales(): BelongsToMany
    {
        return $this->belongsToMany(
            ReglaDocumental::class,                     // Modelo relacionado
            'regla_documental_cargo_mandante',          // Nombre de la tabla pivote
            'cargo_mandante_id',                        // Clave foránea de este modelo en la tabla pivote
            'regla_documental_id'                       // Clave foránea del modelo relacionado en la tabla pivote
        );
        // No usamos withTimestamps() aquí a menos que la tabla pivote los tenga
    }
}