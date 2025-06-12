<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AclaracionCriterio extends Model
{
    use HasFactory;

    protected $table = 'aclaraciones_criterio';

    protected $fillable = [
        'titulo', // Este es ahora el texto principal de la aclaración
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}