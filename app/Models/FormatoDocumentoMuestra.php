<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // Necesario para interactuar con el storage

class FormatoDocumentoMuestra extends Model
{
    use HasFactory;

    protected $table = 'formatos_documento_muestra';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ruta_archivo',
        'nombre_archivo_original',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Accesor para obtener la URL pública del archivo
    public function getUrlArchivoAttribute()
    {
        if ($this->ruta_archivo && Storage::disk('public')->exists($this->ruta_archivo)) {
            return Storage::disk('public')->url($this->ruta_archivo);
        }
        return null;
    }

    // Mutador para eliminar el archivo antiguo si se actualiza/elimina el registro
    // Esto se puede manejar también en el componente o con Observers,
    // pero un ejemplo de cómo se haría aquí con un evento 'deleting':
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($formato) {
            if ($formato->ruta_archivo && Storage::disk('public')->exists($formato->ruta_archivo)) {
                Storage::disk('public')->delete($formato->ruta_archivo);
            }
        });
    }
}