<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\Mandante;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

#[Layout('layouts.app')]
#[Title('Gestión de Unidades Organizacionales')]
class GestionUnidadesOrganizacionalesMandante extends Component
{
    use WithPagination;

    public bool $mostrarModal = false;
    public ?UnidadOrganizacionalMandante $unidadActual;
    
    // Campos del formulario
    public $mandante_id = '';
    public string $nombre_unidad = '';
    public ?string $codigo_unidad = null;
    public ?string $descripcion = null;
    public $parent_id = null; // Puede ser string vacío o null para el select
    public bool $is_active = true;

    // Para los selects
    public $mandantes = [];
    public $unidadesPadreDisponibles = [];

    // Filtros
    public string $filtroNombre = '';
    public string $filtroMandanteId = '';
    public string $filtroEstado = 'todos'; // todos, activos, inactivos

    protected function rules()
    {
        return [
            'mandante_id' => 'required|exists:mandantes,id',
            'nombre_unidad' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('unidades_organizacionales_mandante', 'nombre_unidad')
                    ->where(fn ($query) => $query->where('mandante_id', $this->mandante_id)
                                                ->where('parent_id', $this->parent_id === '' ? null : $this->parent_id)) // Unicidad bajo el mismo padre y mandante
                    ->ignore($this->unidadActual?->id),
            ],
            'codigo_unidad' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('unidades_organizacionales_mandante', 'codigo_unidad')
                    ->where(fn ($query) => $query->where('mandante_id', $this->mandante_id))
                    ->ignore($this->unidadActual?->id),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                'exists:unidades_organizacionales_mandante,id',
                 // Validación para evitar que la unidad sea su propio padre
                Rule::notIn([$this->unidadActual?->id]),
                // Asegurar que el padre seleccionado pertenece al mismo mandante
                Rule::exists('unidades_organizacionales_mandante', 'id')->where(function ($query) {
                    $query->where('mandante_id', $this->mandante_id);
                }),
            ],
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'mandante_id.required' => 'Debe seleccionar un mandante.',
        'mandante_id.exists' => 'El mandante seleccionado no es válido.',
        'nombre_unidad.required' => 'El nombre de la unidad es obligatorio.',
        'nombre_unidad.unique' => 'Ya existe una unidad con este nombre para el mandante y unidad padre seleccionados.',
        'codigo_unidad.unique' => 'Este código de unidad ya está en uso para el mandante seleccionado.',
        'parent_id.exists' => 'La unidad padre seleccionada no es válida.',
        'parent_id.not_in' => 'Una unidad no puede ser su propio padre.',
    ];

    public function mount()
    {
        if (!Auth::user() || !Auth::user()->hasRole('ASEM_Admin')) {
            // session()->flash('error', 'Acceso no autorizado.'); // Esto podría ser manejado por middleware más globalmente.
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }
        $this->unidadActual = new UnidadOrganizacionalMandante();
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
    }
    
    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroMandanteId() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function updatedMandanteId($value)
    {
        // Cuando el mandante_id cambia en el modal, actualizamos las unidades padre disponibles.
        if (!empty($value)) {
            $query = UnidadOrganizacionalMandante::where('mandante_id', $value)
                                                ->where('is_active', true)
                                                ->orderBy('nombre_unidad');
            
            if ($this->unidadActual && $this->unidadActual->id) {
                // Excluir la unidad actual y sus descendientes (simplificado: solo la actual)
                $query->where('id', '!=', $this->unidadActual->id);
                // Para excluir descendientes se necesitaría una función recursiva.
            }
            $this->unidadesPadreDisponibles = $query->get();
        } else {
            $this->unidadesPadreDisponibles = [];
        }
        // Si el parent_id actual no pertenece al nuevo mandante_id, resetearlo
        if ($this->parent_id) {
            $parentExistsInNewMandante = collect($this->unidadesPadreDisponibles)->contains('id', $this->parent_id);
            if (!$parentExistsInNewMandante) {
                $this->parent_id = null;
            }
        }
    }

    public function render()
    {
        $query = UnidadOrganizacionalMandante::with(['mandante', 'parent'])
                    ->orderBy('mandante_id', 'asc')
                    ->orderBy('nombre_unidad', 'asc');

        if (!empty($this->filtroNombre)) {
            $query->where('nombre_unidad', 'like', '%' . $this->filtroNombre . '%')
                  ->orWhere('codigo_unidad', 'like', '%' . $this->filtroNombre . '%');
        }
        if (!empty($this->filtroMandanteId)) {
            $query->where('mandante_id', $this->filtroMandanteId);
        }
        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $unidades = $query->paginate(10);

        return view('livewire.gestion-unidades-organizacionales-mandante', [
            'unidades' => $unidades,
            'todosLosMandantes' => Mandante::orderBy('razon_social')->get(), // Para el filtro
        ]);
    }

    private function resetInputFields()
    {
        $this->mandante_id = '';
        $this->nombre_unidad = '';
        $this->codigo_unidad = null;
        $this->descripcion = null;
        $this->parent_id = null;
        $this->is_active = true;
        $this->unidadActual = new UnidadOrganizacionalMandante();
        $this->unidadesPadreDisponibles = [];
        $this->resetValidation();
    }

    public function abrirModalParaCrear()
    {
        $this->resetInputFields();
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get(); // Asegurar que estén cargados
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(UnidadOrganizacionalMandante $unidad)
    {
        $this->resetValidation();
        $this->unidadActual = $unidad;
        $this->mandante_id = $unidad->mandante_id;
        $this->nombre_unidad = $unidad->nombre_unidad;
        $this->codigo_unidad = $unidad->codigo_unidad;
        $this->descripcion = $unidad->descripcion;
        $this->parent_id = $unidad->parent_id ?? null; // Asegurar null si está vacío
        $this->is_active = $unidad->is_active;
        
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get(); // Cargar mandantes activos

        // Cargar unidades padre disponibles para el mandante actual de la unidad
        $this->updatedMandanteId($this->mandante_id); 

        $this->mostrarModal = true;
    }

    public function guardarUnidad()
    {
        // Para la validación del parent_id, si es un string vacío del select, convertirlo a null.
        if ($this->parent_id === '') {
            $this->parent_id = null;
        }
        
        $validatedData = $this->validate();

        try {
            $this->unidadActual->fill($validatedData);
            $this->unidadActual->parent_id = $validatedData['parent_id']; // Asegurar que se asigne correctamente
            $this->unidadActual->save();

            session()->flash('success', $this->unidadActual->wasRecentlyCreated ? 'Unidad Organizacional creada exitosamente.' : 'Unidad Organizacional actualizada exitosamente.');
            $this->cerrarModal();
        } catch (\Illuminate\Database\QueryException $e) {
            // Manejar errores de base de datos, por ejemplo, violaciones de unicidad no capturadas por la validación de Livewire
            // o problemas con claves foráneas si algo está mal configurado.
            $errorCode = $e->errorInfo[1];
            if($errorCode == 1062){ // Error de entrada duplicada
                 session()->flash('error', 'Error: Ya existe un registro con datos similares (nombre o código). Por favor, verifique.');
            } else {
                 session()->flash('error', 'Error al guardar la unidad organizacional. Intente nuevamente. Detalles: ' . $e->getMessage());
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

    public function confirmarAlternarEstado(UnidadOrganizacionalMandante $unidad)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        // Aquí podríamos añadir lógica para verificar si la unidad tiene hijos activos antes de desactivarla,
        // o si tiene recursos asociados, etc., como una mejora futura.
        $nuevoEstado = !$unidad->is_active;
        $unidad->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado de la Unidad Organizacional actualizado exitosamente.');
    }

    // Método auxiliar para obtener descendientes (opcional para validación más estricta de parent_id)
    // protected function getDescendantIds(UnidadOrganizacionalMandante $unidad): array
    // {
    //     $descendantIds = [];
    //     foreach ($unidad->children as $child) {
    //         $descendantIds[] = $child->id;
    //         $descendantIds = array_merge($descendantIds, $this->getDescendantIds($child));
    //     }
    //     return $descendantIds;
    // }
}