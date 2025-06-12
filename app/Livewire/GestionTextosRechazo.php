<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TextoRechazo; // Modelo
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Textos de Rechazo')] // Título
class GestionTextosRechazo extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TextoRechazo $textoActual; // Modelo actual
    public string $titulo = '';
    public string $descripcion_detalle = ''; // Cambiado de ?string a string
    public bool $is_active = true;

    public string $filtroTitulo = ''; // Cambiado para buscar por título
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $textoId = $this->textoActual?->id ?? 'NULL';
        return [
            'titulo' => "required|string|min:3|max:255|unique:textos_rechazo,titulo,{$textoId},id",
            'descripcion_detalle' => 'required|string|min:10|max:2000', // Descripción ahora es requerida y más larga
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'titulo.required' => 'El título del texto de rechazo es obligatorio.',
        'titulo.unique' => 'Este título para texto de rechazo ya existe.',
        'descripcion_detalle.required' => 'La descripción detallada del rechazo es obligatoria.',
        'descripcion_detalle.min' => 'La descripción debe tener al menos 10 caracteres.',
    ];

    public function updatedFiltroTitulo() { $this->resetPage(); } // Cambiado
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->textoActual = new TextoRechazo();
    }

    public function render()
    {
        $query = TextoRechazo::query();

        if (!empty($this->filtroTitulo)) { // Cambiado
            $query->where('titulo', 'like', '%' . $this->filtroTitulo . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $textosRechazo = $query->orderBy('titulo', 'asc')->paginate(10); // Cambiado

        return view('livewire.gestion-textos-rechazo', [
            'textosRechazo' => $textosRechazo, // Cambiado
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->textoActual = new TextoRechazo();
        $this->titulo = '';
        $this->descripcion_detalle = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TextoRechazo $texto) // Cambiado
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->textoActual = $texto;
        $this->titulo = $texto->titulo;
        $this->descripcion_detalle = $texto->descripcion_detalle;
        $this->is_active = $texto->is_active;
        $this->mostrarModal = true;
    }

    public function guardarTextoRechazo() // Cambiado
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->textoActual->id)) {
            TextoRechazo::create($validatedData);
            session()->flash('success', 'Texto de rechazo creado exitosamente.');
        } else {
            $this->textoActual->update($validatedData);
            session()->flash('success', 'Texto de rechazo actualizado exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->titulo = '';
        $this->descripcion_detalle = '';
        $this->is_active = true;
        $this->textoActual = new TextoRechazo();
    }

    public function confirmarAlternarEstado(TextoRechazo $texto) // Cambiado
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$texto->is_active;
        $texto->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del texto de rechazo actualizado exitosamente.');
    }
}