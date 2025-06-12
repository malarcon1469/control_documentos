<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NombreDocumento extends Model
{
    use HasFactory;

    protected $table = 'nombre_documentos'; // Especificar nombre de tabla

    protected $fillable = [
        'nombre',
        'descripcion',
        'aplica_a',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}