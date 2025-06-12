<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth; // Para verificar el rol del usuario

#[Layout('layouts.app')] // Usamos el layout principal de Breeze
#[Title('Gestión de Listados Universales')] // Título de la página
class GestionListadosUniversalesHub extends Component
{
    public function mount()
    {
        // Protección adicional a nivel de componente:
        // Si el usuario no es ASEM_Admin, no debería estar aquí.
        // La ruta ya tiene middleware 'role:ASEM_Admin', pero esto es una doble verificación.
        if (!Auth::user() || !Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'Acceso no autorizado a esta sección.');
            // Redirige al dashboard si no tiene permisos.
            // Usamos redirect()->route() para asegurar que la ruta exista.
            // Es importante que 'dashboard' sea una ruta nombrada válida.
            return redirect()->route('dashboard');
        }
    }

    public function render()
    {
        return view('livewire.gestion-listados-universales-hub');
    }
}