<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoEntidadControlable; // Modelo
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Tipos de Entidad Controlable')] // Título
class GestionTiposEntidadControlable extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TipoEntidadControlable $entidadActual; // Modelo actual
    public string $nombre_entidad = '';
    public ?string $descripcion = null;
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $entidadId = $this->entidadActual?->id ?? 'NULL';
        return [
            'nombre_entidad' => "required|string|min:3|max:100|unique:tipos_entidad_controlable,nombre_entidad,{$entidadId},id",
            'descripcion' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre_entidad.required' => 'El nombre de la entidad es obligatorio.',
        'nombre_entidad.unique' => 'Este tipo de entidad controlable ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->entidadActual = new TipoEntidadControlable();
    }

    public function render()
    {
        $query = TipoEntidadControlable::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre_entidad', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $tiposEntidad = $query->orderBy('nombre_entidad', 'asc')->paginate(10);

        return view('livewire.gestion-tipos-entidad-controlable', [
            'tiposEntidad' => $tiposEntidad,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->entidadActual = new TipoEntidadControlable();
        $this->nombre_entidad = '';
        $this->descripcion = null;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TipoEntidadControlable $entidad)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->entidadActual = $entidad;
        $this->nombre_entidad = $entidad->nombre_entidad;
        $this->descripcion = $entidad->descripcion;
        $this->is_active = $entidad->is_active;
        $this->mostrarModal = true;
    }

    public function guardarEntidad()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();
        
        $this->entidadActual->fill($validatedData);
        $this->entidadActual->save();

        if ($this->entidadActual->wasRecentlyCreated) {
            session()->flash('success', 'Tipo de entidad controlable creado exitosamente.');
        } else {
            session()->flash('success', 'Tipo de entidad controlable actualizado exitosamente.');
        }
        
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre_entidad = '';
        $this->descripcion = null;
        $this->is_active = true;
        $this->entidadActual = new TipoEntidadControlable();
    }

    public function confirmarAlternarEstado(TipoEntidadControlable $entidad)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$entidad->is_active;
        $entidad->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de entidad controlable actualizado exitosamente.');
    }
}