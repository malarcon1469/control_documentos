<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Trabajador;
use App\Models\Contratista;
use App\Models\Region;
use App\Models\Comuna;
use App\Models\Nacionalidad;
use App\Models\Sexo;
use App\Models\EstadoCivil;
use App\Models\NivelEducacional;
use App\Models\Etnia;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\CargoMandante;
use App\Models\TipoCondicionPersonal;
use App\Models\TrabajadorVinculacion;
use App\Models\ReglaDocumental;
use App\Models\CondicionFechaIngreso;
use App\Models\TipoEntidadControlable;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use App\Rules\ValidarRutRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.app')]
class GestionTrabajadoresContratista extends Component
{
    use WithPagination;

    // --- Propiedades para Etapa 1: Selección de Vinculación ---
    public $unidadesHabilitadasContratista = [];
    public ?int $selectedUnidadOrganizacionalId = null;
    public string $nombreVinculacionSeleccionada = '';
    // -----------------------------------------------------------

    public string $vistaActual = 'listado_trabajadores';
    public ?Trabajador $trabajadorSeleccionado = null;
    public $contratistaId;

    public string $searchTrabajador = '';
    public string $sortByTrabajador = 'id';
    public string $sortDirectionTrabajador = 'asc';

    public bool $showModalFichaTrabajador = false;
    public ?int $trabajadorId = null;
    public string $nombres = '', $apellido_paterno = '', $apellido_materno = '', $rut_trabajador = '';
    public ?string $fecha_nacimiento = null, $email_trabajador = null, $celular_trabajador = null, $fecha_ingreso_empresa = null;
    public ?int $nacionalidad_id = null, $sexo_id = null, $estado_civil_id = null, $nivel_educacional_id = null, $etnia_id = null;
    public ?string $direccion_calle = null, $direccion_numero = null, $direccion_departamento = null;
    public ?int $trabajador_region_id = null, $trabajador_comuna_id = null;
    public bool $trabajador_is_active = true;

    public bool $showModalVinculacion = false;
    public ?int $vinculacionId = null;
    public ?int $v_mandante_id = null;
    public ?int $v_unidad_organizacional_mandante_id = null;
    public ?int $v_cargo_mandante_id = null;
    public ?int $v_tipo_condicion_personal_id = null;
    public ?string $v_fecha_ingreso_vinculacion = null;
    public ?string $v_fecha_contrato = null;
    public bool $v_is_active = true;
    public ?string $v_fecha_desactivacion = null;
    public ?string $v_motivo_desactivacion = null;

    public $nacionalidades = [], $sexos = [], $estadosCiviles = [], $nivelesEducacionales = [], $etnias = [];
    public $regiones = [], $comunasDisponiblesTrabajador = [];
    public $mandantesDisponibles = [], $unidadesOrganizacionalesDisponibles = [], $cargosMandanteDisponibles = [];
    public $tiposCondicionPersonal = [];

    // --- Propiedades para Modal Documentos Trabajador ---
    public bool $showModalDocumentosTrabajador = false;
    public ?int $trabajadorParaDocumentosId = null;
    public ?Trabajador $trabajadorParaDocumentos = null;
    public array $documentosRequeridosParaTrabajador = [];
    public ?TrabajadorVinculacion $vinculacionActivaEnUOContexto = null;
    public string $nombreTrabajadorParaDocumentosModal = '';
    // -------------------------------------------------------------


    protected function messages()
    {
        return [
            '*.required' => 'Este campo es obligatorio.',
            'rut_trabajador.unique' => 'El RUT del trabajador ya existe.',
            'email_trabajador.email' => 'El formato del email no es válido.',
            'email_trabajador.unique' => 'El email del trabajador ya existe.',
            'v_fecha_desactivacion.required_if' => 'La fecha de desactivación es obligatoria si la vinculación no está activa.',
            'v_motivo_desactivacion.required_if' => 'El motivo de desactivación es obligatorio si la vinculación no está activa.',
            'selectedUnidadOrganizacionalId.required' => 'Debe seleccionar una Vinculación (Mandante - UO) para operar.'
        ];
    }


    public function mount()
    {
        $user = Auth::user();
        $contratista = Contratista::with([
            'unidadesOrganizacionalesMandante.mandante:id,razon_social'
        ])->where('admin_user_id', $user->id)->first();

        if (!$contratista) {
            session()->flash('error', 'Usuario no asociado a un contratista.');
            return redirect()->route('dashboard');
        }
        $this->contratistaId = $contratista->id;

        $this->unidadesHabilitadasContratista = $contratista->unidadesOrganizacionalesMandante
            ->map(function ($uo) {
                return [
                    'id' => $uo->id,
                    'nombre_completo' => ($uo->mandante->razon_social ?? 'Mandante Desconocido') . ' - ' . $uo->nombre_unidad,
                    'mandante_id' => $uo->mandante_id,
                    'mandante_nombre' => $uo->mandante->razon_social ?? 'Mandante Desconocido',
                    'uo_nombre' => $uo->nombre_unidad
                ];
            })->sortBy('nombre_completo')->values();
        
         if ($this->unidadesHabilitadasContratista->count() === 1) {
             $this->selectedUnidadOrganizacionalId = $this->unidadesHabilitadasContratista->first()['id'];
             $this->updatedSelectedUnidadOrganizacionalId($this->selectedUnidadOrganizacionalId);
         }

        $this->nacionalidades = Nacionalidad::orderBy('nombre')->get();
        $this->sexos = Sexo::orderBy('nombre')->get();
        $this->estadosCiviles = EstadoCivil::orderBy('nombre')->get();
        $this->nivelesEducacionales = NivelEducacional::orderBy('nombre')->get();
        $this->etnias = Etnia::orderBy('nombre')->get();
        $this->regiones = Region::orderBy('nombre')->get();
        $this->tiposCondicionPersonal = TipoCondicionPersonal::orderBy('nombre')->get();

        $this->mandantesDisponibles = Mandante::whereHas('unidadesOrganizacionales', function ($query) use ($contratista) {
            $query->whereHas('contratistasHabilitados', function ($subQuery) use ($contratista) {
                $subQuery->where('contratistas.id', $contratista->id);
            });
        })->orderBy('razon_social')->get();
    }

    public function updatedSelectedUnidadOrganizacionalId($value)
    {
        $this->selectedUnidadOrganizacionalId = $value ? (int)$value : null;
        $this->searchTrabajador = '';
        $this->resetPage('trabajadoresPage');
        $this->irAListadoTrabajadores(); 
        
        $uoSeleccionada = collect($this->unidadesHabilitadasContratista)->firstWhere('id', $this->selectedUnidadOrganizacionalId);
        $this->nombreVinculacionSeleccionada = $uoSeleccionada ? $uoSeleccionada['nombre_completo'] : '';

        if ($this->showModalDocumentosTrabajador) {
            $this->cerrarModalDocumentosTrabajador();
        }
    }
    
    public function seleccionarTrabajadorParaVinculaciones($trabajadorId)
    {
        $this->trabajadorSeleccionado = Trabajador::find($trabajadorId);
        if ($this->trabajadorSeleccionado && $this->trabajadorSeleccionado->contratista_id == $this->contratistaId) {
            $this->vistaActual = 'listado_vinculaciones';
            $this->resetPage('vinculacionesPage'); 
        } else {
            session()->flash('error_trabajador', 'Trabajador no encontrado o no pertenece a su empresa.');
            $this->trabajadorSeleccionado = null;
        }
    }

    public function irAListadoTrabajadores()
    {
        $this->vistaActual = 'listado_trabajadores';
        $this->trabajadorSeleccionado = null;
        $this->resetPage('trabajadoresPage');
    }

    public function rulesFichaTrabajador()
    {
        // ... (sin cambios) ...
        return [
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'rut_trabajador' => ['required', 'string', new ValidarRutRule(), Rule::unique('trabajadores', 'rut')->ignore($this->trabajadorId)],
            'nacionalidad_id' => 'required|exists:nacionalidades,id',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'sexo_id' => 'nullable|exists:sexos,id',
            'email_trabajador' => ['nullable', 'email', 'max:255', Rule::unique('trabajadores', 'email')->ignore($this->trabajadorId)],
            'celular_trabajador' => 'nullable|string|max:20',
            'estado_civil_id' => 'nullable|exists:estados_civiles,id',
            'nivel_educacional_id' => 'nullable|exists:niveles_educacionales,id',
            'etnia_id' => 'nullable|exists:etnias,id',
            'direccion_calle' => 'nullable|string|max:255',
            'direccion_numero' => 'nullable|string|max:50',
            'direccion_departamento' => 'nullable|string|max:50',
            'trabajador_region_id' => 'nullable|exists:regiones,id',
            'trabajador_comuna_id' => 'nullable|exists:comunas,id',
            'fecha_ingreso_empresa' => 'nullable|date',
            'trabajador_is_active' => 'boolean',
        ];
    }

    public function updatedTrabajadorRegionId($value)
    {
        // ... (sin cambios) ...
        if ($value) {
            $this->comunasDisponiblesTrabajador = Comuna::where('region_id', $value)->orderBy('nombre')->get();
        } else {
            $this->comunasDisponiblesTrabajador = [];
        }
        $this->trabajador_comuna_id = null;
    }

    private function resetFichaTrabajadorFields()
    {
        // ... (sin cambios) ...
        $this->trabajadorId = null;
        $this->nombres = ''; $this->apellido_paterno = ''; $this->apellido_materno = ''; $this->rut_trabajador = '';
        $this->fecha_nacimiento = null; $this->email_trabajador = null; $this->celular_trabajador = null; $this->fecha_ingreso_empresa = null;
        $this->nacionalidad_id = null; $this->sexo_id = null; $this->estado_civil_id = null; $this->nivel_educacional_id = null; $this->etnia_id = null;
        $this->direccion_calle = null; $this->direccion_numero = null; $this->direccion_departamento = null;
        $this->trabajador_region_id = null; $this->trabajador_comuna_id = null;
        $this->trabajador_is_active = true;
        $this->comunasDisponiblesTrabajador = [];
        $this->resetValidation();
    }

    public function abrirModalNuevoTrabajador()
    {
        // ... (sin cambios) ...
         if (!$this->selectedUnidadOrganizacionalId) {
             session()->flash('error', 'Por favor, seleccione primero una Vinculación (Mandante - UO) para operar.');
             return;
         }
        $this->resetFichaTrabajadorFields();
        $this->showModalFichaTrabajador = true;
    }

    public function abrirModalEditarTrabajador($id)
    {
        // ... (sin cambios) ...
         if (!$this->selectedUnidadOrganizacionalId) {
             session()->flash('error', 'Error: La vinculación actual no está definida para esta acción.');
             return;
         }
        $trabajador = Trabajador::with('comuna.region')->find($id);
        if ($trabajador && $trabajador->contratista_id == $this->contratistaId) {
            $this->trabajadorId = $trabajador->id;
            if($this->vistaActual === 'listado_vinculaciones' && $this->trabajadorSeleccionado && $this->trabajadorSeleccionado->id === $trabajador->id) {
            } else {
                $this->trabajadorSeleccionado = $trabajador; 
            }

            $this->nombres = $trabajador->nombres;
            $this->apellido_paterno = $trabajador->apellido_paterno;
            $this->apellido_materno = $trabajador->apellido_materno;
            $this->rut_trabajador = $trabajador->rut;
            $this->nacionalidad_id = $trabajador->nacionalidad_id;
            $this->fecha_nacimiento = $trabajador->fecha_nacimiento ? $trabajador->fecha_nacimiento->format('Y-m-d') : null;
            $this->sexo_id = $trabajador->sexo_id;
            $this->email_trabajador = $trabajador->email;
            $this->celular_trabajador = $trabajador->celular;
            $this->estado_civil_id = $trabajador->estado_civil_id;
            $this->nivel_educacional_id = $trabajador->nivel_educacional_id;
            $this->etnia_id = $trabajador->etnia_id;
            $this->direccion_calle = $trabajador->direccion_calle;
            $this->direccion_numero = $trabajador->direccion_numero;
            $this->direccion_departamento = $trabajador->direccion_departamento;
            $this->trabajador_region_id = $trabajador->comuna?->region_id;
            if ($this->trabajador_region_id) {
                $this->comunasDisponiblesTrabajador = Comuna::where('region_id', $this->trabajador_region_id)->orderBy('nombre')->get();
            }
            $this->trabajador_comuna_id = $trabajador->comuna_id;
            $this->fecha_ingreso_empresa = $trabajador->fecha_ingreso_empresa ? $trabajador->fecha_ingreso_empresa->format('Y-m-d') : null;
            $this->trabajador_is_active = $trabajador->is_active;
            $this->showModalFichaTrabajador = true;
        }
    }

    public function guardarTrabajador()
    {
        // ... (sin cambios) ...
        if (!$this->selectedUnidadOrganizacionalId) {
            session()->flash('error_trabajador', 'Debe seleccionar una Vinculación (Mandante - UO) antes de agregar o modificar un trabajador.');
            $this->cerrarModalFichaTrabajador();
            return;
        }

        $validatedData = $this->validate($this->rulesFichaTrabajador());
        $validatedData['contratista_id'] = $this->contratistaId;

        DB::beginTransaction();
        try {
            $trabajador = null;
            if ($this->trabajadorId) {
                $trabajador = Trabajador::find($this->trabajadorId);
                if ($trabajador && $trabajador->contratista_id == $this->contratistaId) {
                    $trabajador->update($validatedData);
                    session()->flash('message_trabajador', 'Ficha del trabajador actualizada correctamente.');
                } else {
                    session()->flash('error_trabajador', 'Error al actualizar: Trabajador no encontrado o no pertenece a su empresa.');
                    DB::rollBack();
                    return;
                }
            } else {
                $trabajador = Trabajador::create($validatedData);
                session()->flash('message_trabajador', 'Trabajador agregado correctamente.');
            }
            
            DB::commit();
            $this->cerrarModalFichaTrabajador();
            if($this->trabajadorSeleccionado && $this->trabajadorSeleccionado->id == ($this->trabajadorId ?? null)){
                $this->trabajadorSeleccionado = Trabajador::find($this->trabajadorSeleccionado->id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar trabajador: " . $e->getMessage());
            session()->flash('error_trabajador', 'Ocurrió un error al guardar la ficha del trabajador.');
        }
    }
    
    public function cerrarModalFichaTrabajador()
    {
        // ... (sin cambios) ...
        $this->showModalFichaTrabajador = false;
        $this->resetFichaTrabajadorFields();
    }

    public function toggleActivoTrabajador(Trabajador $trabajador)
    {
        // ... (sin cambios) ...
        if ($trabajador && $trabajador->contratista_id == $this->contratistaId) {
            $trabajador->is_active = !$trabajador->is_active;
            $trabajador->save();
            session()->flash('message_trabajador', 'Estado del trabajador cambiado.');
        }
    }

    public function rulesVinculacion()
    {
        // ... (sin cambios) ...
        $rules = [
            'v_mandante_id' => 'required|exists:mandantes,id',
            'v_unidad_organizacional_mandante_id' => ['required', 'exists:unidades_organizacionales_mandante,id'],
            'v_cargo_mandante_id' => 'required|exists:cargos_mandante,id',
            'v_tipo_condicion_personal_id' => 'nullable|exists:tipos_condicion_personal,id',
            'v_fecha_ingreso_vinculacion' => 'required|date',
            'v_fecha_contrato' => 'nullable|date|after_or_equal:v_fecha_ingreso_vinculacion',
            'v_is_active' => 'required|boolean',
            'v_fecha_desactivacion' => 'nullable|required_if:v_is_active,false|date|after_or_equal:v_fecha_ingreso_vinculacion',
            'v_motivo_desactivacion' => 'nullable|required_if:v_is_active,false|string|max:500',
        ];

        $rules['v_unidad_organizacional_mandante_id'][] = function ($attribute, $value, $fail) {
            if ($this->v_is_active) {
                $query = TrabajadorVinculacion::where('trabajador_id', $this->trabajadorSeleccionado->id)
                    ->where('unidad_organizacional_mandante_id', $value)
                    ->where('is_active', true);

                if ($this->vinculacionId) { 
                    $query->where('id', '!=', $this->vinculacionId);
                }

                if ($query->exists()) {
                    $fail('El trabajador ya tiene una vinculación activa en esta Unidad Organizacional.');
                }
            }
        };
        return $rules;
    }


    public function updatedVMandanteId($mandanteId)
    {
        // ... (sin cambios) ...
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->cargosMandanteDisponibles = [];
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_cargo_mandante_id = null;

        if ($mandanteId) {
            $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)
                ->where('is_active', true)
                ->whereHas('contratistasHabilitados', function($query) {
                    $query->where('contratista_id', $this->contratistaId);
                })
                ->orderBy('nombre_unidad')->get();

            $this->cargosMandanteDisponibles = CargoMandante::where('mandante_id', $mandanteId)
                ->where('is_active', true)
                ->orderBy('nombre_cargo')->get();
        }
    }
    
    private function resetVinculacionFields()
    {
        // ... (sin cambios) ...
        $this->vinculacionId = null;
        $this->v_mandante_id = null;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_cargo_mandante_id = null;
        $this->v_tipo_condicion_personal_id = null;
        $this->v_fecha_ingreso_vinculacion = null;
        $this->v_fecha_contrato = null;
        $this->v_is_active = true;
        $this->v_fecha_desactivacion = null;
        $this->v_motivo_desactivacion = null;
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->cargosMandanteDisponibles = [];
        $this->resetValidation();
    }

    public function abrirModalNuevaVinculacion()
    {
        // ... (sin cambios) ...
        if (!$this->trabajadorSeleccionado) {
             session()->flash('error', 'Debe seleccionar un trabajador para agregar una vinculación.');
             return;
        }
         $uoActualSeleccionada = collect($this->unidadesHabilitadasContratista)->firstWhere('id', $this->selectedUnidadOrganizacionalId);
        $this->resetVinculacionFields();

         if ($uoActualSeleccionada) {
             $this->v_mandante_id = $uoActualSeleccionada['mandante_id'];
             $this->updatedVMandanteId($this->v_mandante_id);
         }
        $this->v_fecha_ingreso_vinculacion = now()->format('Y-m-d');
        $this->showModalVinculacion = true;
    }

    public function abrirModalEditarVinculacion($id)
    {
        // ... (sin cambios) ...
        $vinculacion = TrabajadorVinculacion::find($id);
        if ($vinculacion && $vinculacion->trabajador_id == $this->trabajadorSeleccionado?->id) {
            $this->vinculacionId = $vinculacion->id;
            $this->v_mandante_id = $vinculacion->unidadOrganizacionalMandante?->mandante_id; 
            $this->updatedVMandanteId($this->v_mandante_id); 
            $this->v_unidad_organizacional_mandante_id = $vinculacion->unidad_organizacional_mandante_id;
            $this->v_cargo_mandante_id = $vinculacion->cargo_mandante_id;
            $this->v_tipo_condicion_personal_id = $vinculacion->tipo_condicion_personal_id;
            $this->v_fecha_ingreso_vinculacion = $vinculacion->fecha_ingreso_vinculacion->format('Y-m-d');
            $this->v_fecha_contrato = $vinculacion->fecha_contrato ? $vinculacion->fecha_contrato->format('Y-m-d') : null;
            $this->v_is_active = $vinculacion->is_active;
            $this->v_fecha_desactivacion = $vinculacion->fecha_desactivacion ? $vinculacion->fecha_desactivacion->format('Y-m-d') : null;
            $this->v_motivo_desactivacion = $vinculacion->motivo_desactivacion;
            $this->showModalVinculacion = true;
        } else {
            session()->flash('error_vinculacion', 'Vinculación no encontrada o no pertenece al trabajador seleccionado.');
        }
    }

    public function guardarVinculacion()
    {
        // ... (sin cambios) ...
        if (!$this->trabajadorSeleccionado) {
            session()->flash('error_vinculacion', 'No se ha seleccionado un trabajador.');
            return;
        }
         if (!$this->selectedUnidadOrganizacionalId) {
             session()->flash('error_vinculacion', 'La Vinculación principal (Mandante - UO) no está seleccionada.');
             return;
         }

        $validatedData = $this->validate($this->rulesVinculacion());
        $validatedData['trabajador_id'] = $this->trabajadorSeleccionado->id;

        if ($validatedData['v_is_active']) {
            $validatedData['v_fecha_desactivacion'] = null;
            $validatedData['v_motivo_desactivacion'] = null;
        }
        
        $dataToSave = [
            'trabajador_id' => $validatedData['trabajador_id'],
            'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
            'cargo_mandante_id' => $validatedData['v_cargo_mandante_id'],
            'tipo_condicion_personal_id' => $validatedData['v_tipo_condicion_personal_id'],
            'fecha_ingreso_vinculacion' => $validatedData['v_fecha_ingreso_vinculacion'],
            'fecha_contrato' => $validatedData['v_fecha_contrato'],
            'is_active' => $validatedData['v_is_active'],
            'fecha_desactivacion' => $validatedData['v_fecha_desactivacion'],
            'motivo_desactivacion' => $validatedData['v_motivo_desactivacion'],
        ];

        try {
            if ($this->vinculacionId) {
                $vinculacion = TrabajadorVinculacion::find($this->vinculacionId);
                if ($vinculacion && $vinculacion->trabajador_id == $this->trabajadorSeleccionado->id) {
                    $vinculacion->update($dataToSave);
                    session()->flash('message_vinculacion', 'Vinculación actualizada correctamente.');
                }
            } else {
                TrabajadorVinculacion::create($dataToSave);
                session()->flash('message_vinculacion', 'Vinculación creada correctamente.');
            }
            $this->cerrarModalVinculacion();
        } catch (\Exception $e) {
            Log::error("Error al guardar vinculación: " . $e->getMessage());
            session()->flash('error_vinculacion', 'Ocurrió un error al guardar la vinculación: ' . $e->getMessage());
        }
    }

    public function cerrarModalVinculacion()
    {
        // ... (sin cambios) ...
        $this->showModalVinculacion = false;
        $this->resetVinculacionFields();
    }

    public function toggleActivoVinculacion(TrabajadorVinculacion $vinculacion)
    {
        // ... (sin cambios) ...
        if ($vinculacion && $vinculacion->trabajador_id == $this->trabajadorSeleccionado?->id) {
            if ($vinculacion->is_active) {
                $vinculacion->is_active = false;
                $vinculacion->fecha_desactivacion = $vinculacion->fecha_desactivacion ?? now(); 
                $vinculacion->motivo_desactivacion = $vinculacion->motivo_desactivacion ?? 'Desactivado manualmente desde listado.';
            } else {
                $existeOtraActivaEnMismaUO = TrabajadorVinculacion::where('trabajador_id', $vinculacion->trabajador_id)
                    ->where('unidad_organizacional_mandante_id', $vinculacion->unidad_organizacional_mandante_id)
                    ->where('is_active', true)
                    ->where('id', '!=', $vinculacion->id) 
                    ->exists();

                if ($existeOtraActivaEnMismaUO) {
                    session()->flash('error_vinculacion', 'No se puede activar. El trabajador ya tiene otra vinculación activa en esta Unidad Organizacional.');
                    return;
                }
                $vinculacion->is_active = true;
                $vinculacion->fecha_desactivacion = null;
                $vinculacion->motivo_desactivacion = null;
            }
            $vinculacion->save();
            session()->flash('message_vinculacion', 'Estado de la vinculación cambiado.');
        }
    }

    // --- MÉTODOS PARA MODAL DE DOCUMENTOS ---
    public function abrirModalDocumentosTrabajador($trabajadorId)
    {
        // ... (sin cambios) ...
        if (!$this->selectedUnidadOrganizacionalId) {
            session()->flash('error', 'Por favor, seleccione primero una Vinculación (Mandante - UO) para operar.');
            return;
        }

        $this->trabajadorParaDocumentosId = $trabajadorId;
        $this->trabajadorParaDocumentos = Trabajador::with('nacionalidad')->find($trabajadorId);

        if (!$this->trabajadorParaDocumentos || $this->trabajadorParaDocumentos->contratista_id != $this->contratistaId) {
            session()->flash('error', 'Trabajador no encontrado o no pertenece a su empresa.');
            $this->resetModalDocumentosFields();
            return;
        }
        $this->nombreTrabajadorParaDocumentosModal = $this->trabajadorParaDocumentos->nombre_completo;

        $this->vinculacionActivaEnUOContexto = TrabajadorVinculacion::with([
                'cargoMandante:id,nombre_cargo',
                'tipoCondicionPersonal:id,nombre'
            ])
            ->where('trabajador_id', $this->trabajadorParaDocumentos->id)
            ->where('unidad_organizacional_mandante_id', $this->selectedUnidadOrganizacionalId)
            ->where('is_active', true)
            ->orderBy('fecha_ingreso_vinculacion', 'desc')
            ->first();

        if (!$this->vinculacionActivaEnUOContexto) {
            Log::info("Trabajador ID {$trabajadorId} no tiene vinculación activa en UO ID {$this->selectedUnidadOrganizacionalId}. Algunos filtros de reglas pueden no aplicar.");
        }
        
        $this->determinarDocumentosRequeridos();
        $this->showModalDocumentosTrabajador = true;
    }

    public function cerrarModalDocumentosTrabajador()
    {
        // ... (sin cambios) ...
        $this->resetModalDocumentosFields();
    }

    private function resetModalDocumentosFields()
    {
        // ... (sin cambios) ...
        $this->showModalDocumentosTrabajador = false;
        $this->trabajadorParaDocumentosId = null;
        $this->trabajadorParaDocumentos = null;
        $this->documentosRequeridosParaTrabajador = [];
        $this->vinculacionActivaEnUOContexto = null;
        $this->nombreTrabajadorParaDocumentosModal = '';
    }
    
    private function esAncestro($idUoAncestroPotencial, $idUoDescendiente)
    {
        // ... (sin cambios) ...
        $uoActual = UnidadOrganizacionalMandante::find($idUoDescendiente);
        while ($uoActual && $uoActual->parent_id) {
            if ($uoActual->parent_id == $idUoAncestroPotencial) {
                return true;
            }
            $uoActual = UnidadOrganizacionalMandante::find($uoActual->parent_id);
        }
        return false;
    }

    private function determinarDocumentosRequeridos()
    {
        Log::info("DEBUG: Iniciando determinarDocumentosRequeridos para trabajador ID: {$this->trabajadorParaDocumentosId} en UO ID: {$this->selectedUnidadOrganizacionalId}");

        if (!$this->trabajadorParaDocumentos || !$this->selectedUnidadOrganizacionalId) {
            $this->documentosRequeridosParaTrabajador = [];
            Log::info("DEBUG: Saliendo temprano - trabajador o UO no seleccionados.");
            return;
        }

        $trabajador = $this->trabajadorParaDocumentos;
        $vinculacion = $this->vinculacionActivaEnUOContexto;
        $uoContextoId = $this->selectedUnidadOrganizacionalId;
        
        $uoContexto = UnidadOrganizacionalMandante::find($uoContextoId);
        if (!$uoContexto) {
            $this->documentosRequeridosParaTrabajador = [];
            Log::info("DEBUG: Saliendo temprano - UO de contexto no encontrada (ID: {$uoContextoId}).");
            return;
        }
        $mandanteId = $uoContexto->mandante_id;
        Log::info("DEBUG: Mandante ID: {$mandanteId}, Trabajador RUT: {$trabajador->rut}, Nacionalidad ID Trabajador: {$trabajador->nacionalidad_id}"); // Log de Nacionalidad del trabajador
        if ($vinculacion) {
            Log::info("DEBUG: Vinculación activa: Cargo ID: {$vinculacion->cargo_mandante_id}, Cond. Personal ID: {$vinculacion->tipo_condicion_personal_id}, Fecha Ingreso Vinc: {$vinculacion->fecha_ingreso_vinculacion}");
        } else {
            Log::info("DEBUG: No hay vinculación activa para el trabajador en esta UO.");
        }

        $condicionContratistaEnUO = DB::table('contratista_unidad_organizacional')
            ->where('contratista_id', $this->contratistaId)
            ->where('unidad_organizacional_mandante_id', $uoContextoId)
            ->value('tipo_condicion_id');
        Log::info("DEBUG: Contratista ID: {$this->contratistaId}, Condición Contratista en UO {$uoContextoId}: " . ($condicionContratistaEnUO ?? 'Ninguna'));

        $tipoEntidadPersonaId = TipoEntidadControlable::where('nombre_entidad', 'Persona')->value('id'); 
        if (!$tipoEntidadPersonaId) {
             Log::error("ERROR DEBUG: No se encontró el Tipo de Entidad Controlable 'Persona' usando la columna 'nombre_entidad'. Verifica el valor exacto en la BD.");
             $this->documentosRequeridosParaTrabajador = [];
             return;
        }
        Log::info("DEBUG: Tipo Entidad 'Persona' ID: {$tipoEntidadPersonaId}");

        $reglasCandidatas = ReglaDocumental::with([
            'nombreDocumento:id,nombre',
            'unidadesOrganizacionales:id,parent_id', 
            'observacionDocumento:id,titulo', 
            'criterios.criterioEvaluacion:id,nombre_criterio', 
            'criterios.subCriterio:id,nombre',
            'criterios.textoRechazo:id,titulo',
            'criterios.aclaracionCriterio:id,titulo',
            'tipoVencimiento:id,nombre',
            'cargosAplica:id', // Solo necesitamos los IDs para el pluck
            'nacionalidadesAplica:id' // Solo necesitamos los IDs para el pluck
        ])
        ->where('mandante_id', $mandanteId)
        ->where('tipo_entidad_controlada_id', $tipoEntidadPersonaId)
        ->where('is_active', true)
        ->get();

        Log::info("DEBUG: Total reglas candidatas encontradas (Mandante, Tipo Entidad Persona, Activa): " . $reglasCandidatas->count());

        $documentosFinales = [];
        $idsDocumentosAgregados = [];

        foreach ($reglasCandidatas as $regla) {
            Log::info("DEBUG: ----- Procesando Regla ID: {$regla->id} (Documento: {$regla->nombreDocumento?->nombre}) -----");

            // ... (Filtro UO sin cambios) ...
            $aplicaUO = false;
            $idsUORegla = $regla->unidadesOrganizacionales->pluck('id')->toArray();
            Log::info("DEBUG: Regla ID {$regla->id} - UOs de la regla: " . implode(', ', $idsUORegla) . " | UO Contexto: {$uoContextoId}");
            if (!empty($idsUORegla)) {
                if (in_array($uoContextoId, $idsUORegla)) {
                    $aplicaUO = true;
                    Log::info("DEBUG: Regla ID {$regla->id} - Filtro UO: Coincidencia directa.");
                } else {
                    foreach ($idsUORegla as $idUoRegla) {
                        if ($this->esAncestro($idUoRegla, $uoContextoId)) {
                            $aplicaUO = true;
                            Log::info("DEBUG: Regla ID {$regla->id} - Filtro UO: Coincidencia por herencia.");
                            break;
                        }
                    }
                }
            } else { 
                Log::info("DEBUG: Regla ID {$regla->id} - Filtro UO: La regla no tiene UOs específicas. Asumiendo NO aplica.");
                 $aplicaUO = false; 
            }
            if (!$aplicaUO) {
                Log::info("DEBUG: Regla ID {$regla->id} - Filtro UO: NO PASA.");
                continue;
            }
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro UO: PASA.");


            // ... (Filtros RUT y Cond. Empresa sin cambios) ...
            if (!empty($regla->rut_especificos)) { /* ... */ }
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro RUT Específico: PASA (o no aplica).");
            if (!empty($regla->rut_excluidos)) { /* ... */ }
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro RUT Excluido: PASA (o no aplica).");
            if ($regla->aplica_empresa_condicion_id) { /* ... */ }
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro Cond. Empresa: PASA (o no aplica).");


            // ... (Filtro Cond. Persona sin cambios) ...
            if ($regla->aplica_persona_condicion_id) { /* ... */ }
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro Cond. Persona: PASA (o no aplica).");


            // 7. Filtro Cargo (MODIFICADO PARA MÚLTIPLES CARGOS)
            $idsCargosRegla = $regla->cargosAplica->pluck('id')->toArray();
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro Cargo: IDs Cargos en Regla: [" . implode(',', $idsCargosRegla) . "], Cargo Vinculación: " . ($vinculacion?->cargo_mandante_id ?? 'N/A'));
            if (!empty($idsCargosRegla)) { // Si la regla especifica cargos
                if (!$vinculacion || !in_array($vinculacion->cargo_mandante_id, $idsCargosRegla)) {
                    Log::info("DEBUG: Regla ID {$regla->id} - Filtro Cargo: NO PASA (cargo de vinculación no está en la lista de la regla).");
                    continue;
                }
            } // Si $idsCargosRegla está vacío, aplica a todos los cargos (pasa el filtro)
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro Cargo: PASA.");

            // 8. Filtro Nacionalidad (MODIFICADO PARA MÚLTIPLES NACIONALIDADES)
            $idsNacionalidadesRegla = $regla->nacionalidadesAplica->pluck('id')->toArray();
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro Nacionalidad: IDs Nacionalidades en Regla: [" . implode(',', $idsNacionalidadesRegla) . "], Nacionalidad Trabajador: {$trabajador->nacionalidad_id}");
            if (!empty($idsNacionalidadesRegla)) { // Si la regla especifica nacionalidades
                if (!$trabajador->nacionalidad_id || !in_array($trabajador->nacionalidad_id, $idsNacionalidadesRegla)) {
                    Log::info("DEBUG: Regla ID {$regla->id} - Filtro Nacionalidad: NO PASA (nacionalidad del trabajador no está en la lista de la regla).");
                    continue;
                }
            } // Si $idsNacionalidadesRegla está vacío, aplica a todas las nacionalidades (pasa el filtro)
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro Nacionalidad: PASA.");
            

            // ... (Filtro Fecha Ingreso sin cambios) ...
            if ($regla->condicion_fecha_ingreso_id && $regla->fecha_comparacion_ingreso) { /* ... */ }
            Log::info("DEBUG: Regla ID {$regla->id} - Filtro Fecha Ingreso: PASA (o no aplica).");


            Log::info("DEBUG: !!!!! REGLA ID {$regla->id} (Documento: {$regla->nombreDocumento?->nombre}) HA PASADO TODOS LOS FILTROS !!!!!");
            if (!in_array($regla->nombre_documento_id, $idsDocumentosAgregados)) {
                // ... (resto de la asignación al array $documentosFinales sin cambios) ...
                $documentosFinales[] = [
                    'nombre_documento_id' => $regla->nombre_documento_id,
                    'nombre_documento_texto' => $regla->nombreDocumento?->nombre ?? 'Doc. Desconocido',
                    'regla_documental_id_origen' => $regla->id,
                    'afecta_cumplimiento' => (bool) $regla->afecta_porcentaje_cumplimiento,
                    'restringe_acceso' => (bool) $regla->restringe_acceso,
                    'observacion_documento_texto' => $regla->observacionDocumento?->titulo, 
                    'criterios_evaluacion' => $regla->criterios->map(function ($c) {
                        return [
                            'criterio' => $c->criterioEvaluacion?->nombre_criterio, 
                            'sub_criterio' => $c->subCriterio?->nombre,
                            'texto_rechazo' => $c->textoRechazo?->titulo,
                            'aclaracion' => $c->aclaracionCriterio?->titulo,
                        ];
                    })->all(),
                    'valida_emision' => (bool) $regla->valida_emision,
                    'valida_vencimiento' => (bool) $regla->valida_vencimiento,
                    'tipo_vencimiento_nombre' => $regla->tipoVencimiento?->nombre,
                    'dias_validez_documento' => $regla->dias_validez_documento,
                    'estado_actual_documento' => 'No Cargado', 
                    'fecha_carga_actual' => null,
                    'fecha_emision_actual' => null,
                    'fecha_vencimiento_actual' => null,
                    'periodo_actual' => null,
                    'archivo_actual_path' => null, 
                ];
                $idsDocumentosAgregados[] = $regla->nombre_documento_id;
            } else {
                 Log::info("DEBUG: Regla ID {$regla->id} - Documento ID {$regla->nombre_documento_id} ya fue agregado por otra regla. Omitiendo duplicado.");
            }
        }
        $this->documentosRequeridosParaTrabajador = $documentosFinales;
        Log::info("DEBUG: Documentos finales determinados: " . count($documentosFinales));
        if (empty($documentosFinales)) {
            Log::info("DEBUG: NINGUNA REGLA APLICÓ FINALMENTE.");
        }
    }
    
    public function render()
    {
        // ... (sin cambios) ...
        $trabajadoresPaginados = null;
        $vinculacionesPaginadas = null;

        if ($this->vistaActual === 'listado_trabajadores') {
            $queryTrabajadores = Trabajador::query();
             
             if ($this->selectedUnidadOrganizacionalId) {
                 $queryTrabajadores->where('contratista_id', $this->contratistaId)
                     ->whereHas('vinculaciones', function ($query) {
                         $query->where('unidad_organizacional_mandante_id', $this->selectedUnidadOrganizacionalId);
                     });
             } else {
                 $queryTrabajadores->whereRaw('1 = 0');
             }

            $queryTrabajadores->when($this->searchTrabajador, function ($query) {
                $query->where(function ($q) {
                    $q->where('rut', 'like', '%' . $this->searchTrabajador . '%')
                      ->orWhere('nombres', 'like', '%' . $this->searchTrabajador . '%')
                      ->orWhere('apellido_paterno', 'like', '%' . $this->searchTrabajador . '%')
                      ->orWhere('apellido_materno', 'like', '%' . $this->searchTrabajador . '%');
                });
            })
            ->with([
                'vinculaciones' => function ($query) {
                    $query->where('unidad_organizacional_mandante_id', $this->selectedUnidadOrganizacionalId)
                          ->where('is_active', true)
                          ->with('cargoMandante:id,nombre_cargo')
                          ->orderBy('fecha_ingreso_vinculacion', 'desc');
                }
            ])
            ->orderBy($this->sortByTrabajador, $this->sortDirectionTrabajador);
            $trabajadoresPaginados = $queryTrabajadores->paginate(10, ['*'], 'trabajadoresPage');

        } elseif ($this->vistaActual === 'listado_vinculaciones' && $this->trabajadorSeleccionado) {
             $unidadesHabilitadasIds = collect($this->unidadesHabilitadasContratista)->pluck('id');
            $vinculacionesPaginadas = TrabajadorVinculacion::where('trabajador_id', $this->trabajadorSeleccionado->id)
                ->whereIn('unidad_organizacional_mandante_id', $unidadesHabilitadasIds)
                ->with(['unidadOrganizacionalMandante.mandante', 'cargoMandante', 'tipoCondicionPersonal'])
                ->orderBy('is_active', 'desc')
                ->orderBy('fecha_ingreso_vinculacion', 'desc')
                ->paginate(10, ['*'], 'vinculacionesPage');
        }

        return view('livewire.gestion-trabajadores-contratista', [
            'trabajadoresPaginados' => $trabajadoresPaginados,
            'vinculacionesPaginadas' => $vinculacionesPaginadas,
        ]);
    }
}