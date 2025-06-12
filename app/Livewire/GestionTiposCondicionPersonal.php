<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoCondicionPersonal; // Modelo
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Tipos de Condición Personal')] // Título
class GestionTiposCondicionPersonal extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TipoCondicionPersonal $condicionActual; // Modelo actual
    public string $nombre = '';
    public ?string $descripcion = null;
    public bool $requires_special_document = false; // Nuevo campo
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $condicionId = $this->condicionActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:255|unique:tipos_condicion_personal,nombre,{$condicionId},id",
            'descripcion' => 'nullable|string|max:1000',
            'requires_special_document' => 'required|boolean',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la condición es obligatorio.',
        'nombre.unique' => 'Esta condición personal ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->condicionActual = new TipoCondicionPersonal();
    }

    public function render()
    {
        $query = TipoCondicionPersonal::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $condiciones = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-tipos-condicion-personal', [
            'condiciones' => $condiciones,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->condicionActual = new TipoCondicionPersonal();
        $this->nombre = '';
        $this->descripcion = null;
        $this->requires_special_document = false;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TipoCondicionPersonal $condicion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->condicionActual = $condicion;
        $this->nombre = $condicion->nombre;
        $this->descripcion = $condicion->descripcion;
        $this->requires_special_document = $condicion->requires_special_document;
        $this->is_active = $condicion->is_active;
        $this->mostrarModal = true;
    }

    public function guardarCondicion()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->condicionActual->id)) {
            TipoCondicionPersonal::create($validatedData);
            session()->flash('success', 'Tipo de condición personal creada exitosamente.');
        } else {
            $this->condicionActual->update($validatedData);
            session()->flash('success', 'Tipo de condición personal actualizada exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->descripcion = null;
        $this->requires_special_document = false;
        $this->is_active = true;
        $this->condicionActual = new TipoCondicionPersonal();
    }

    public function confirmarAlternarEstado(TipoCondicionPersonal $condicion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$condicion->is_active;
        $condicion->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de condición personal actualizado exitosamente.');
    }
}