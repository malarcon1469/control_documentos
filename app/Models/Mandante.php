<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mandante extends Model
{
    use HasFactory;

    protected $fillable = [
        'razon_social',
        'rut',
        'persona_contacto_nombre',
        'persona_contacto_email',
        'persona_contacto_telefono',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function unidadesOrganizacionales(): HasMany
    {
        return $this->hasMany(UnidadOrganizacionalMandante::class);
    }

    // NUEVA RELACIÓN
    public function cargosDefinidos(): HasMany
    {
        return $this->hasMany(CargoMandante::class);
    }
}