<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Rubro; // Cambiamos al modelo Rubro
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Rubros')] // Nuevo título
class GestionRubros extends Component
{
    use WithPagination;

    // Propiedades para el formulario y el modal
    public bool $mostrarModal = false;
    public ?Rubro $rubroActual; // Cambiado a Rubro
    public string $nombre = '';
    // 'descripcion' no es necesaria para Rubro según nuestra tabla
    public bool $is_active = true;

    // Filtros (solo por nombre para Rubro, es más simple)
    public string $filtroNombre = '';
    public string $filtroEstado = 'todos'; // 'todos', 'activos', 'inactivos'


    protected function rules()
    {
        // Reglas de validación para Rubro
        return [
            'nombre' => 'required|string|min:3|max:255|unique:rubros,nombre,' . ($this->rubroActual?->id ?? 'NULL') . ',id',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del rubro es obligatorio.',
        'nombre.unique' => 'Este nombre de rubro ya existe.',
    ];

    // Resetear paginación al filtrar
    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->rubroActual = new Rubro();
    }

    public function render()
    {
        $query = Rubro::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $rubros = $query->orderBy('nombre', 'asc')->paginate(10); // Cambiado a $rubros

        return view('livewire.gestion-rubros', [
            'rubros' => $rubros, // Pasamos $rubros a la vista
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->rubroActual = new Rubro(); // Nuevo Rubro
        $this->nombre = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(Rubro $rubro) // Cambiado a Rubro $rubro
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->rubroActual = $rubro;
        $this->nombre = $rubro->nombre;
        $this->is_active = $rubro->is_active;
        $this->mostrarModal = true;
    }

    public function guardarRubro() // Cambiado nombre de método
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->rubroActual->id)) {
            Rubro::create($validatedData); // Usar modelo Rubro
            session()->flash('success', 'Rubro creado exitosamente.');
        } else {
            $this->rubroActual->update($validatedData);
            session()->flash('success', 'Rubro actualizado exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->is_active = true;
        $this->rubroActual = new Rubro();
    }

    public function confirmarEliminacionORAlternarEstado(Rubro $rubro) // Cambiado a Rubro $rubro
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$rubro->is_active;
        $rubro->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del rubro actualizado exitosamente.');
    }
}