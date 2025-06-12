<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etnia extends Model
{
    use HasFactory;

    protected $table = 'etnias';

    protected $fillable = [
        'nombre',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // En el futuro, si los trabajadores tienen una etnia_id:
    // public function trabajadores()
    // {
    //     return $this->hasMany(Trabajador::class, 'etnia_id');
    // }
}