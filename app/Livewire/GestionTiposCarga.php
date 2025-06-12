<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoCarga; // Modelo
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Tipos de Carga')] // Título
class GestionTiposCarga extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TipoCarga $tipoCargaActual; // Modelo actual
    public string $nombre = '';
    public ?string $descripcion = null; // Descripción es opcional
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $tipoCargaId = $this->tipoCargaActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:255|unique:tipos_carga,nombre,{$tipoCargaId},id",
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del tipo de carga es obligatorio.',
        'nombre.unique' => 'Este tipo de carga ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->tipoCargaActual = new TipoCarga();
    }

    public function render()
    {
        $query = TipoCarga::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $tiposCarga = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-tipos-carga', [
            'tiposCarga' => $tiposCarga,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoCargaActual = new TipoCarga();
        $this->nombre = '';
        $this->descripcion = null;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TipoCarga $tipoCarga)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoCargaActual = $tipoCarga;
        $this->nombre = $tipoCarga->nombre;
        $this->descripcion = $tipoCarga->descripcion;
        $this->is_active = $tipoCarga->is_active;
        $this->mostrarModal = true;
    }

    public function guardarTipoCarga()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();
        
        $this->tipoCargaActual->fill($validatedData);
        $this->tipoCargaActual->save();

        if ($this->tipoCargaActual->wasRecentlyCreated) {
            session()->flash('success', 'Tipo de carga creado exitosamente.');
        } else {
            session()->flash('success', 'Tipo de carga actualizado exitosamente.');
        }
        
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->descripcion = null;
        $this->is_active = true;
        $this->tipoCargaActual = new TipoCarga();
    }

    public function confirmarAlternarEstado(TipoCarga $tipoCarga)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$tipoCarga->is_active;
        $tipoCarga->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de carga actualizado exitosamente.');
    }
}