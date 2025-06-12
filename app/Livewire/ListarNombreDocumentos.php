<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\NombreDocumento;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title; // Asegúrate de que este 'use' esté
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Documentos')] // <--- TÍTULO CAMBIADO
class ListarNombreDocumentos extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?NombreDocumento $documentoActual;
    public string $nombre = '';
    public string $descripcion = '';
    public string $aplica_a = 'trabajador';
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroAplicaA = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        return [
            'nombre' => 'required|string|min:3|max:255|unique:nombre_documentos,nombre,' . ($this->documentoActual?->id ?? 'NULL') . ',id',
            'descripcion' => 'nullable|string|max:1000',
            'aplica_a' => 'required|string|in:empresa,trabajador,vehiculo,maquinaria,instalacion,otro',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del tipo de documento es obligatorio.',
        'nombre.unique' => 'Este nombre de tipo de documento ya existe.',
        'aplica_a.required' => 'Debe seleccionar a quién aplica el documento.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroAplicaA() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->documentoActual = new NombreDocumento();
    }

    public function render()
    {
        $query = NombreDocumento::query();
        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }
        if (!empty($this->filtroAplicaA)) {
            $query->where('aplica_a', $this->filtroAplicaA);
        }
        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }
        $nombresDeDocumento = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.listar-nombre-documentos', [
            'nombresDeDocumento' => $nombresDeDocumento,
            'opcionesAplicaA' => ['empresa', 'trabajador', 'vehiculo', 'maquinaria', 'instalacion', 'otro'],
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->documentoActual = new NombreDocumento();
        $this->nombre = '';
        $this->descripcion = '';
        $this->aplica_a = 'trabajador';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(NombreDocumento $documento)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->documentoActual = $documento;
        $this->nombre = $documento->nombre;
        $this->descripcion = $documento->descripcion ?? '';
        $this->aplica_a = $documento->aplica_a;
        $this->is_active = $documento->is_active;
        $this->mostrarModal = true;
    }

    public function guardarDocumento()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();
        if (empty($this->documentoActual->id)) {
            NombreDocumento::create($validatedData);
            session()->flash('success', 'Tipo de documento creado exitosamente.');
        } else {
            $this->documentoActual->update($validatedData);
            session()->flash('success', 'Tipo de documento actualizado exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->descripcion = '';
        $this->aplica_a = 'trabajador';
        $this->is_active = true;
        $this->documentoActual = new NombreDocumento();
    }

    public function confirmarEliminacion(NombreDocumento $documento)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$documento->is_active;
        $documento->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del tipo de documento actualizado exitosamente.');
    }
}