<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoEmpresaLegal; // Cambiamos al modelo TipoEmpresaLegal
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Tipos de Empresa Legal')] // Nuevo título
class GestionTiposEmpresaLegal extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?TipoEmpresaLegal $tipoEmpresaActual; // Cambiado
    public string $nombre = '';
    public ?string $sigla = null; // Añadido campo sigla, puede ser nulo
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $tipoEmpresaId = $this->tipoEmpresaActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:255|unique:tipos_empresa_legal,nombre,{$tipoEmpresaId},id",
            'sigla' => "nullable|string|max:50|unique:tipos_empresa_legal,sigla,{$tipoEmpresaId},id", // Sigla es opcional pero única si se proporciona
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del tipo de empresa es obligatorio.',
        'nombre.unique' => 'Este nombre de tipo de empresa ya existe.',
        'sigla.unique' => 'Esta sigla ya está en uso.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->tipoEmpresaActual = new TipoEmpresaLegal();
    }

    public function render()
    {
        $query = TipoEmpresaLegal::query();

        if (!empty($this->filtroNombre)) {
            // Buscar por nombre o sigla
            $query->where(function($q) {
                $q->where('nombre', 'like', '%' . $this->filtroNombre . '%')
                  ->orWhere('sigla', 'like', '%' . $this->filtroNombre . '%');
            });
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $tiposEmpresa = $query->orderBy('nombre', 'asc')->paginate(10); // Cambiado

        return view('livewire.gestion-tipos-empresa-legal', [
            'tiposEmpresa' => $tiposEmpresa, // Pasamos $tiposEmpresa a la vista
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoEmpresaActual = new TipoEmpresaLegal();
        $this->nombre = '';
        $this->sigla = null;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(TipoEmpresaLegal $tipoEmpresa) // Cambiado
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->tipoEmpresaActual = $tipoEmpresa;
        $this->nombre = $tipoEmpresa->nombre;
        $this->sigla = $tipoEmpresa->sigla;
        $this->is_active = $tipoEmpresa->is_active;
        $this->mostrarModal = true;
    }

    public function guardarTipoEmpresa() // Cambiado nombre de método
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->tipoEmpresaActual->id)) {
            TipoEmpresaLegal::create($validatedData);
            session()->flash('success', 'Tipo de empresa legal creado exitosamente.');
        } else {
            $this->tipoEmpresaActual->update($validatedData);
            session()->flash('success', 'Tipo de empresa legal actualizado exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->sigla = null;
        $this->is_active = true;
        $this->tipoEmpresaActual = new TipoEmpresaLegal();
    }

    public function confirmarAlternarEstado(TipoEmpresaLegal $tipoEmpresa) // Cambiado
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$tipoEmpresa->is_active;
        $tipoEmpresa->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de empresa legal actualizado exitosamente.');
    }
}