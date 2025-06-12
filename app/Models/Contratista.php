<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; 
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contratista extends Model
{
    use HasFactory;

    protected $table = 'contratistas';

    protected $fillable = [
        'razon_social',
        'nombre_fantasia',
        'rut',
        'direccion_calle',
        'direccion_numero',
        'comuna_id',
        'telefono_empresa',
        'email_empresa',
        'tipo_empresa_legal_id',
        'rubro_id',
        'rango_cantidad_trabajadores_id',
        'mutualidad_id',
        'admin_user_id',
        'rep_legal_nombres',
        'rep_legal_apellido_paterno',
        'rep_legal_apellido_materno',
        'rep_legal_rut',
        'rep_legal_telefono',
        'rep_legal_email',
        'tipo_inscripcion', 
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function tipoEmpresaLegal(): BelongsTo
    {
        return $this->belongsTo(TipoEmpresaLegal::class, 'tipo_empresa_legal_id');
    }

    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class, 'rubro_id');
    }

    public function comuna(): BelongsTo
    {
        return $this->belongsTo(Comuna::class, 'comuna_id');
    }

    public function getRegionAttribute()
    {
        if ($this->comuna && $this->comuna->relationLoaded('region')) {
            return $this->comuna->region;
        } elseif ($this->comuna) {
            return $this->comuna->load('region')->region; 
        }
        return null; 
    }

    public function rangoCantidadTrabajadores(): BelongsTo
    {
        return $this->belongsTo(RangoCantidadTrabajadores::class, 'rango_cantidad_trabajadores_id');
    }

    public function mutualidad(): BelongsTo
    {
        return $this->belongsTo(Mutualidad::class, 'mutualidad_id');
    }

    public function tiposEntidadControlable(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoEntidadControlable::class,
            'contratista_tipo_entidad_controlable', 
            'contratista_id',                     
            'tipo_entidad_controlable_id'         
        )->withTimestamps(); 
    }

    public function tiposCondicion(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoCondicion::class,
            'contratista_tipo_condicion', 
            'contratista_id',            
            'tipo_condicion_id'          
        )->withTimestamps(); 
    }

    /**
     * Las unidades organizacionales (del mandante) asignadas al Contratista.
     * (Relación ManyToMany con la tabla pivote contratista_unidad_organizacional)
     * MODIFICACIÓN: Se añade el with('mandante') para asegurar que se carga.
     */
    public function unidadesOrganizacionalesMandante(): BelongsToMany
    {
        return $this->belongsToMany(
            UnidadOrganizacionalMandante::class,
            'contratista_unidad_organizacional',
            'contratista_id',
            'unidad_organizacional_mandante_id'
        )->withPivot('tipo_condicion_id')
         ->with('mandante:id,razon_social'); // <-- Asegurar que el mandante de la UO se cargue con columnas específicas
    }

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class, 'contratista_id');
    }
}