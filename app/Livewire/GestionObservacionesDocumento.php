<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ObservacionDocumento; // Asegúrate que este modelo exista y esté correcto
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Observaciones de Documento')]
class GestionObservacionesDocumento extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?ObservacionDocumento $observacionActual; // Mantenemos nullable por el type hint
    public string $titulo = '';
    public bool $is_active = true;

    public string $filtroTitulo = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        // Quitamos la regla 'unique' para 'titulo' ya que es TEXT en BD
        return [
            'titulo' => "required|string|min:10|max:2000",
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'titulo.required' => 'El texto de la observación es obligatorio.',
        'titulo.min' => 'La observación debe tener al menos 10 caracteres.',
        'titulo.max' => 'La observación no puede exceder los 2000 caracteres.',
    ];

    public function updatedFiltroTitulo() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        // Inicializamos siempre $observacionActual con una nueva instancia.
        $this->observacionActual = new ObservacionDocumento();
    }

    public function render()
    {
        $query = ObservacionDocumento::query();
        if (!empty($this->filtroTitulo)) {
            $query->where('titulo', 'like', '%' . $this->filtroTitulo . '%');
        }
        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }
        $observaciones = $query->orderBy('titulo', 'asc')->paginate(10);
        return view('livewire.gestion-observaciones-documento', ['observaciones' => $observaciones]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->observacionActual = new ObservacionDocumento(); // Aseguramos nueva instancia
        $this->titulo = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(ObservacionDocumento $observacion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->observacionActual = $observacion; // Modelo inyectado
        $this->titulo = $observacion->titulo;
        $this->is_active = $observacion->is_active;
        $this->mostrarModal = true;
    }

    public function guardarObservacion()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        // Opcional: Verificación de duplicados a nivel de aplicación si es importante
        $queryExiste = ObservacionDocumento::where('titulo', $validatedData['titulo']);
        if ($this->observacionActual && $this->observacionActual->id) { // Si estamos editando, excluimos el actual
            $queryExiste->where('id', '!=', $this->observacionActual->id);
        }
        if ($queryExiste->exists()) {
            $this->addError('titulo', 'Esta observación de documento ya existe.');
            return;
        }
        
        // Usar el objeto $this->observacionActual que ya está seteado
        $this->observacionActual->fill($validatedData);
        $this->observacionActual->save(); // save() maneja create o update

        if ($this->observacionActual->wasRecentlyCreated) {
            session()->flash('success', 'Observación de documento creada exitosamente.');
        } else {
            session()->flash('success', 'Observación de documento actualizada exitosamente.');
        }
        
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->titulo = '';
        $this->is_active = true;
        $this->observacionActual = new ObservacionDocumento(); // Resetear para la próxima vez
    }

    public function confirmarAlternarEstado(ObservacionDocumento $observacion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$observacion->is_active;
        $observacion->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado de la observación de documento actualizado exitosamente.');
    }
}