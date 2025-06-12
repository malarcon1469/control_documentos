<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Nacionalidad; // Modelo Nacionalidad
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Nacionalidades')] // Título
class GestionNacionalidades extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?Nacionalidad $nacionalidadActual; // Modelo actual
    public string $nombre = '';
    public ?string $codigo_iso_3166_1_alpha_2 = null; // Puede ser nulo
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $nacionalidadId = $this->nacionalidadActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:255|unique:nacionalidades,nombre,{$nacionalidadId},id",
            'codigo_iso_3166_1_alpha_2' => "nullable|string|size:2|unique:nacionalidades,codigo_iso_3166_1_alpha_2,{$nacionalidadId},id", // 2 caracteres, opcional, único
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la nacionalidad es obligatorio.',
        'nombre.unique' => 'Esta nacionalidad ya existe.',
        'codigo_iso_3166_1_alpha_2.size' => 'El código ISO debe tener 2 caracteres.',
        'codigo_iso_3166_1_alpha_2.unique' => 'Este código ISO ya está en uso.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->nacionalidadActual = new Nacionalidad();
    }

    public function render()
    {
        $query = Nacionalidad::query();

        if (!empty($this->filtroNombre)) {
            // Buscar por nombre o código ISO
            $query->where(function($q) {
                $q->where('nombre', 'like', '%' . $this->filtroNombre . '%')
                  ->orWhere('codigo_iso_3166_1_alpha_2', 'like', '%' . $this->filtroNombre . '%');
            });
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $nacionalidades = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-nacionalidades', [
            'nacionalidades' => $nacionalidades,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->nacionalidadActual = new Nacionalidad();
        $this->nombre = '';
        $this->codigo_iso_3166_1_alpha_2 = null;
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(Nacionalidad $nacionalidad)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->nacionalidadActual = $nacionalidad;
        $this->nombre = $nacionalidad->nombre;
        $this->codigo_iso_3166_1_alpha_2 = $nacionalidad->codigo_iso_3166_1_alpha_2;
        $this->is_active = $nacionalidad->is_active;
        $this->mostrarModal = true;
    }

    public function guardarNacionalidad()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();
        // Asegurarse de que el código ISO se guarde como null si está vacío
        $validatedData['codigo_iso_3166_1_alpha_2'] = !empty($validatedData['codigo_iso_3166_1_alpha_2']) ? strtoupper($validatedData['codigo_iso_3166_1_alpha_2']) : null;


        if (empty($this->nacionalidadActual->id)) {
            Nacionalidad::create($validatedData);
            session()->flash('success', 'Nacionalidad creada exitosamente.');
        } else {
            $this->nacionalidadActual->update($validatedData);
            session()->flash('success', 'Nacionalidad actualizada exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->codigo_iso_3166_1_alpha_2 = null;
        $this->is_active = true;
        $this->nacionalidadActual = new Nacionalidad();
    }

    public function confirmarAlternarEstado(Nacionalidad $nacionalidad)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$nacionalidad->is_active;
        $nacionalidad->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado de la nacionalidad actualizado exitosamente.');
    }
}