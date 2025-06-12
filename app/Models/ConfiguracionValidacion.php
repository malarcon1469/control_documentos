<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role; // Importar el modelo Role de Spatie

class ConfiguracionValidacion extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_validacion';

    protected $fillable = [
        'nombre',
        'descripcion',
        'primer_rol_validador_id',
        'segundo_rol_validador_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the role for the first validator.
     */
    public function primerRolValidador(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'primer_rol_validador_id');
    }

    /**
     * Get the role for the second validator.
     */
    public function segundoRolValidador(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'segundo_rol_validador_id');
    }
}