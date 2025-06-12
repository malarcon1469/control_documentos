<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sexo; // Modelo
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Sexos')] // Título
class GestionSexos extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?Sexo $sexoActual; // Modelo actual
    public string $nombre = '';
    public bool $is_active = true;

    public string $filtroNombre = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        $sexoId = $this->sexoActual?->id ?? 'NULL';
        return [
            'nombre' => "required|string|min:3|max:50|unique:sexos,nombre,{$sexoId},id",
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del sexo es obligatorio.',
        'nombre.unique' => 'Este nombre de sexo ya existe.',
    ];

    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->sexoActual = new Sexo();
    }

    public function render()
    {
        $query = Sexo::query();

        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $sexos = $query->orderBy('nombre', 'asc')->paginate(10);

        return view('livewire.gestion-sexos', [
            'sexos' => $sexos,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->sexoActual = new Sexo();
        $this->nombre = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(Sexo $sexo)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->sexoActual = $sexo;
        $this->nombre = $sexo->nombre;
        $this->is_active = $sexo->is_active;
        $this->mostrarModal = true;
    }

    public function guardarSexo()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        if (empty($this->sexoActual->id)) {
            Sexo::create($validatedData);
            session()->flash('success', 'Sexo creado exitosamente.');
        } else {
            $this->sexoActual->update($validatedData);
            session()->flash('success', 'Sexo actualizado exitosamente.');
        }
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->nombre = '';
        $this->is_active = true;
        $this->sexoActual = new Sexo();
    }

    public function confirmarAlternarEstado(Sexo $sexo)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$sexo->is_active;
        $sexo->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado del sexo actualizado exitosamente.');
    }
}