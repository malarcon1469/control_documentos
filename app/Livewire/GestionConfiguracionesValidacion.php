<?php

namespace App\Livewire;

use App\Models\ConfiguracionValidacion;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role; // Importar Role
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionConfiguracionesValidacion extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $configId;
    public $nombre;
    public $descripcion;
    public $primer_rol_validador_id;
    public $segundo_rol_validador_id;
    public $is_active = true;

    public $search = '';
    public $perPage = 10;
    public $sortBy = 'nombre';
    public $sortDirection = 'asc';

    public $rolesDisponibles = []; // Para el desplegable

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortBy' => ['except' => 'nombre'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount()
    {
        // Cargar roles que pueden validar. Ajusta los nombres de los roles según tu sistema.
        $this->rolesDisponibles = Role::whereIn('name', ['ASEM_Admin', 'Mandante_Admin']) // Roles que pueden validar
                                    ->orWhere('name', 'like', '%Validator%') // Opcional, si tienes roles como ASEM_Validator
                                    ->orderBy('name')
                                    ->get();
    }

    protected function rules()
    {
        return [
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('configuraciones_validacion', 'nombre')->ignore($this->configId),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'primer_rol_validador_id' => 'nullable|exists:roles,id',
            'segundo_rol_validador_id' => [
                'nullable',
                'exists:roles,id',
                Rule::when($this->primer_rol_validador_id, ['different:primer_rol_validador_id']) // No puede ser igual al primero si el primero está seteado
            ],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la configuración es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.unique' => 'Este nombre de configuración ya existe.',
        'primer_rol_validador_id.exists' => 'El rol seleccionado para el primer validador no es válido.',
        'segundo_rol_validador_id.exists' => 'El rol seleccionado para el segundo validador no es válido.',
        'segundo_rol_validador_id.different' => 'El segundo rol validador no puede ser el mismo que el primero.',
    ];

    public function render()
    {
        $configuraciones = ConfiguracionValidacion::with(['primerRolValidador', 'segundoRolValidador'])
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-configuraciones-validacion', [
            'configuraciones' => $configuraciones,
            'rolesParaSelect' => $this->rolesDisponibles, // Pasar roles a la vista
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->is_active = true;
        $this->openModal();
    }

    public function edit($id)
    {
        $config = ConfiguracionValidacion::findOrFail($id);
        $this->configId = $id;
        $this->nombre = $config->nombre;
        $this->descripcion = $config->descripcion;
        $this->primer_rol_validador_id = $config->primer_rol_validador_id;
        $this->segundo_rol_validador_id = $config->segundo_rol_validador_id;
        $this->is_active = $config->is_active;
        $this->openModal();
    }

    public function store()
    {
        // Validación adicional: Al menos un rol debe ser seleccionado si se quiere una validación
        if (empty($this->primer_rol_validador_id) && empty($this->segundo_rol_validador_id)) {
            // Aquí podrías añadir un error o permitirlo si una config "sin validadores" tiene sentido
             // (ej. auto-aprobación, aunque eso sería mejor manejarlo con un flag específico).
             // Por ahora, lo permitiremos, pero considera esta lógica.
        }
        // Asegurar que si el segundo rol no está seteado, el primero sí lo esté.
         if (empty($this->primer_rol_validador_id) && !empty($this->segundo_rol_validador_id)) {
             $this->addError('primer_rol_validador_id', 'Debe seleccionar un primer rol si selecciona un segundo.');
             return;
         }


        $validatedData = $this->validate();

        // Convertir IDs vacíos a null explícitamente si los selectores devuelven strings vacíos
        $validatedData['primer_rol_validador_id'] = $validatedData['primer_rol_validador_id'] ?: null;
        $validatedData['segundo_rol_validador_id'] = $validatedData['segundo_rol_validador_id'] ?: null;


        ConfiguracionValidacion::updateOrCreate(['id' => $this->configId], $validatedData);

        session()->flash('message',
            $this->configId ? 'Configuración de Validación actualizada.' : 'Configuración de Validación creada.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $config = ConfiguracionValidacion::findOrFail($id);
        $config->is_active = !$config->is_active;
        $config->save();
        session()->flash('message', 'Estado de la Configuración actualizado.');
    }

    public function delete($id)
    {
        $config = ConfiguracionValidacion::find($id);
        if ($config) {
            // Antes de eliminar/desactivar, se debería verificar si esta configuración está en uso por alguna Regla Documental.
            // Por ahora, solo desactivamos:
            $config->is_active = false;
            $config->save();
            session()->flash('message', 'Configuración de Validación desactivada.');
            // Para eliminar:
            // $config->delete();
            // session()->flash('message', 'Configuración de Validación eliminada.');
        }
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetInputFields();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function resetInputFields()
    {
        $this->configId = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->primer_rol_validador_id = null;
        $this->segundo_rol_validador_id = null;
        $this->is_active = true;
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
}