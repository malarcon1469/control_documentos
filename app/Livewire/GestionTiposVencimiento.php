<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoVencimiento; // Modelo
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Tipos de Vencimiento')] // Título
class GestionTiposVencimiento extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TipoVencimiento $tipoVencimientoActual; // Modelo actual
    public string $nombre = '';
    public ?string $descripcion = null;
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $tipoVencimientoId = $this->tipoVencimientoActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:255|unique:tipos_vencimiento,nombre,{$tipoVencimientoId},id",
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del tipo de vencimiento es obligatorio.',
        'nombre.unique' => 'Este tipo de vencimiento ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->tipoVencimientoActual = new TipoVencimiento();
    }

    public function render()
    {
        $query = TipoVencimiento::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $tiposVencimiento = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-tipos-vencimiento', [
            'tiposVencimiento' => $tiposVencimiento,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoVencimientoActual = new TipoVencimiento();
        $this->nombre = '';
        $this->descripcion = null;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TipoVencimiento $tipoVencimiento)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoVencimientoActual = $tipoVencimiento;
        $this->nombre = $tipoVencimiento->nombre;
        $this->descripcion = $tipoVencimiento->descripcion;
        $this->is_active = $tipoVencimiento->is_active;
        $this->mostrarModal = true;
    }

    public function guardarTipoVencimiento()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();
        
        $this->tipoVencimientoActual->fill($validatedData);
        $this->tipoVencimientoActual->save();

        if ($this->tipoVencimientoActual->wasRecentlyCreated) {
            session()->flash('success', 'Tipo de vencimiento creado exitosamente.');
        } else {
            session()->flash('success', 'Tipo de vencimiento actualizado exitosamente.');
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
        $this->tipoVencimientoActual = new TipoVencimiento();
    }

    public function confirmarAlternarEstado(TipoVencimiento $tipoVencimiento)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$tipoVencimiento->is_active;
        $tipoVencimiento->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de vencimiento actualizado exitosamente.');
    }
}