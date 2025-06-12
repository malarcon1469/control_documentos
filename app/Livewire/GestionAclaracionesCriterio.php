<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AclaracionCriterio;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Aclaraciones de Criterio')]
class GestionAclaracionesCriterio extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?AclaracionCriterio $aclaracionActual; // La mantenemos nullable por el type hint
    public string $titulo = '';
    public bool $is_active = true;

    public string $filtroTitulo = '';
    public string $filtroEstado = 'todos';

    protected function rules()
    {
        // No se usa $aclaracionId aquí porque 'titulo' ya no es unique en BD
        return [
            'titulo' => "required|string|min:10|max:2000",
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'titulo.required' => 'El texto de la aclaración es obligatorio.',
        'titulo.min' => 'La aclaración debe tener al menos 10 caracteres.',
        'titulo.max' => 'La aclaración no puede exceder los 2000 caracteres.',
    ];

    public function updatedFiltroTitulo() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        // Inicializamos siempre $aclaracionActual con una nueva instancia.
        // Livewire se encargará de inyectar el modelo correcto en abrirModalParaEditar.
        $this->aclaracionActual = new AclaracionCriterio();
    }

    public function render()
    {
        $query = AclaracionCriterio::query();
        if (!empty($this->filtroTitulo)) {
            $query->where('titulo', 'like', '%' . $this->filtroTitulo . '%');
        }
        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }
        $aclaraciones = $query->orderBy('titulo', 'asc')->paginate(10);
        return view('livewire.gestion-aclaraciones-criterio', ['aclaraciones' => $aclaraciones]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->aclaracionActual = new AclaracionCriterio(); // Aseguramos nueva instancia
        $this->titulo = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(AclaracionCriterio $aclaracion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->aclaracionActual = $aclaracion; // Modelo inyectado
        $this->titulo = $aclaracion->titulo;
        $this->is_active = $aclaracion->is_active;
        $this->mostrarModal = true;
    }

    public function guardarAclaracion()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();

        // Opcional: Verificación de duplicados a nivel de aplicación si es importante
        // (Lo mantengo comentado, ya que decidimos no tener UNIQUE en BD para este)
        // $queryExiste = AclaracionCriterio::where('titulo', $validatedData['titulo']);
        // if ($this->aclaracionActual && $this->aclaracionActual->id) {
        //     $queryExiste->where('id', '!=', $this->aclaracionActual->id);
        // }
        // if ($queryExiste->exists()) {
        //     $this->addError('titulo', 'Esta aclaración de criterio ya existe.');
        //     return;
        // }

        // Usar el objeto $this->aclaracionActual que ya está seteado en abrirModalParaCrear o abrirModalParaEditar
        $this->aclaracionActual->fill($validatedData); // Llenar el modelo con datos validados
        $this->aclaracionActual->save(); // save() maneja create o update

        if ($this->aclaracionActual->wasRecentlyCreated) {
            session()->flash('success', 'Aclaración de criterio creada exitosamente.');
        } else {
            session()->flash('success', 'Aclaración de criterio actualizada exitosamente.');
        }
        
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->titulo = '';
        $this->is_active = true;
        $this->aclaracionActual = new AclaracionCriterio(); // Resetear para la próxima vez
    }

    public function confirmarAlternarEstado(AclaracionCriterio $aclaracion)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $nuevoEstado = !$aclaracion->is_active;
        $aclaracion->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado de la aclaración de criterio actualizado exitosamente.');
    }
}