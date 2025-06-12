<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CargoMandante;
use App\Models\Mandante;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
#[Title('Gestión de Cargos por Mandante')]
class GestionCargosMandante extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?CargoMandante $cargoActual;
    
    // Campos del formulario
    public $mandante_id = '';
    public string $nombre_cargo = '';
    public ?string $descripcion = null;
    public bool $is_active = true;

    // Para el select de mandantes en el modal
    public $mandantesDisponibles = [];

    // Filtros
    public string $filtroNombre = '';
    public string $filtroMandanteId = '';
    public string $filtroEstado = 'todos'; // todos, activos, inactivos

    protected function rules()
    {
        return [
            'mandante_id' => 'required|exists:mandantes,id',
            'nombre_cargo' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('cargos_mandante', 'nombre_cargo')
                    ->where(fn ($query) => $query->where('mandante_id', $this->mandante_id))
                    ->ignore($this->cargoActual?->id),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'mandante_id.required' => 'Debe seleccionar un mandante.',
        'mandante_id.exists' => 'El mandante seleccionado no es válido.',
        'nombre_cargo.required' => 'El nombre del cargo es obligatorio.',
        'nombre_cargo.unique' => 'Ya existe un cargo con este nombre para el mandante seleccionado.',
    ];

    public function mount()
    {
        if (!Auth::user() || !Auth::user()->hasRole('ASEM_Admin')) {
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }
        $this->cargoActual = new CargoMandante();
        $this->mandantesDisponibles = Mandante::where('is_active', true)->orderBy('razon_social')->get();
    }
    
    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroMandanteId() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function render()
    {
        $query = CargoMandante::with('mandante') // Eager load la relación mandante
                    ->orderBy('mandante_id', 'asc')
                    ->orderBy('nombre_cargo', 'asc');

        if (!empty($this->filtroNombre)) {
            $query->where('nombre_cargo', 'like', '%' . $this->filtroNombre . '%');
        }
        if (!empty($this->filtroMandanteId)) {
            $query->where('mandante_id', $this->filtroMandanteId);
        }
        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $cargos = $query->paginate(10);

        // Mandantes para el filtro (todos, no solo activos)
        $todosLosMandantesParaFiltro = Mandante::orderBy('razon_social')->get();


        return view('livewire.gestion-cargos-mandante', [
            'cargos' => $cargos,
            'todosLosMandantesParaFiltro' => $todosLosMandantesParaFiltro,
        ]);
    }

    private function resetInputFields()
    {
        $this->mandante_id = '';
        $this->nombre_cargo = '';
        $this->descripcion = null;
        $this->is_active = true;
        $this->cargoActual = new CargoMandante();
        $this->resetValidation();
    }

    public function abrirModalParaCrear()
    {
        $this->resetInputFields();
        // $this->mandantesDisponibles ya se cargan en mount y no cambian
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(CargoMandante $cargo)
    {
        $this->resetValidation();
        $this->cargoActual = $cargo;
        $this->mandante_id = $cargo->mandante_id;
        $this->nombre_cargo = $cargo->nombre_cargo;
        $this->descripcion = $cargo->descripcion;
        $this->is_active = $cargo->is_active;
        
        // $this->mandantesDisponibles ya se cargan en mount y no cambian
        $this->mostrarModal = true;
    }

    public function guardarCargo()
    {
        $validatedData = $this->validate();

        try {
            $this->cargoActual->fill($validatedData);
            $this->cargoActual->save();

            session()->flash('success', $this->cargoActual->wasRecentlyCreated ? 'Cargo creado exitosamente.' : 'Cargo actualizado exitosamente.');
            $this->cerrarModal();
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if($errorCode == 1062){
                 session()->flash('error', 'Error: Ya existe un cargo con ese nombre para el mandante seleccionado.');
            } else {
                 session()->flash('error', 'Error al guardar el cargo. Intente nuevamente. Detalles: ' . $e->getMessage());
            }
        }  catch (\Exception $e) {
            session()->flash('error', 'Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetInputFields();
    }

    public function confirmarAlternarEstado(CargoMandante $cargo)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$cargo->is_active;
        $cargo->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del Cargo actualizado exitosamente.');
    }
}