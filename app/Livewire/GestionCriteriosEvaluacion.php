<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CriterioEvaluacion; // Modelo
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Criterios de Evaluación')] // Título
class GestionCriteriosEvaluacion extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?CriterioEvaluacion $criterioActual; // Modelo actual
    public string $nombre_criterio = '';
    public ?string $descripcion_criterio = null;
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $criterioId = $this->criterioActual?->id ?? 'NULL';
        return [
            'nombre_criterio' => "required|string|min:3|max:255|unique:criterios_evaluacion,nombre_criterio,{$criterioId},id",
            'descripcion_criterio' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre_criterio.required' => 'El nombre del criterio es obligatorio.',
        'nombre_criterio.unique' => 'Este criterio de evaluación ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->criterioActual = new CriterioEvaluacion();
    }

    public function render()
    {
        $query = CriterioEvaluacion::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre_criterio', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $criterios = $query->orderBy('nombre_criterio', 'asc')->paginate(10);

        return view('livewire.gestion-criterios-evaluacion', [
            'criterios' => $criterios,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->criterioActual = new CriterioEvaluacion();
        $this->nombre_criterio = '';
        $this->descripcion_criterio = null;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(CriterioEvaluacion $criterio)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->criterioActual = $criterio;
        $this->nombre_criterio = $criterio->nombre_criterio;
        $this->descripcion_criterio = $criterio->descripcion_criterio;
        $this->is_active = $criterio->is_active;
        $this->mostrarModal = true;
    }

    public function guardarCriterio()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->criterioActual->id)) {
            CriterioEvaluacion::create($validatedData);
            session()->flash('success', 'Criterio de evaluación creado exitosamente.');
        } else {
            $this->criterioActual->update($validatedData);
            session()->flash('success', 'Criterio de evaluación actualizado exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre_criterio = '';
        $this->descripcion_criterio = null;
        $this->is_active = true;
        $this->criterioActual = new CriterioEvaluacion();
    }

    public function confirmarAlternarEstado(CriterioEvaluacion $criterio)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$criterio->is_active;
        $criterio->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del criterio de evaluación actualizado exitosamente.');
    }
}