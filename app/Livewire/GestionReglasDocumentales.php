<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ReglaDocumental;
use App\Models\Mandante;
use App\Models\TipoEntidadControlable;
use App\Models\NombreDocumento;
use App\Models\TipoCondicion;
use App\Models\TipoCondicionPersonal;
use App\Models\CargoMandante;
use App\Models\Nacionalidad;
use App\Models\CondicionFechaIngreso;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\ObservacionDocumento;
use App\Models\FormatoDocumentoMuestra;
use App\Models\TipoVencimiento;
use App\Models\ConfiguracionValidacion;
use App\Models\CriterioEvaluacion;
use App\Models\SubCriterio;
use App\Models\TextoRechazo;
use App\Models\AclaracionCriterio;
use App\Models\TipoVehiculo;        // <--- AÑADIDO
use App\Models\TipoMaquinaria;     // <--- AÑADIDO
use App\Models\TipoEmbarcacion;    // <--- AÑADIDO
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection; // <--- AÑADIDO para el manejo de colecciones

class GestionReglasDocumentales extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $reglaDocumentalId;
    public $modoEdicion = false;

    // --- Propiedades del formulario ---
    public $mandante_id;
    public $tipo_entidad_controlada_id;
    public $nombre_documento_id;
    public $valor_nominal_documento = 1;
    public $aplica_empresa_condicion_id;
    public $aplica_persona_condicion_id;
    public $rut_especificos; // Usado para RUT, Patente, Código, Matrícula
    public $rut_excluidos;   // Usado para RUT, Patente, Código, Matrícula
    public $condicion_fecha_ingreso_id;
    public $fecha_comparacion_ingreso;
    public $observacion_documento_id;
    public $formato_documento_id;
    public $documento_relacionado_id;
    public $tipo_vencimiento_id;
    public $dias_validez_documento;
    public $dias_aviso_vencimiento = 30;
    public $valida_emision = false;
    public $valida_vencimiento = false;
    public $configuracion_validacion_id;
    public $restringe_acceso = false;
    public $afecta_porcentaje_cumplimiento = false;
    public $documento_es_perseguidor = false;
    public $mostrar_historico_documento = false;
    public $permite_ver_nacionalidad_trabajador = false;
    public $permite_modificar_nacionalidad_trabajador = false;
    public $permite_ver_fecha_nacimiento_trabajador = false;
    public $permite_modificar_fecha_nacimiento_trabajador = false;
    public $is_active = true;

    public $criterios = [];
    public $unidadesSeleccionadas = [];
    
    // Propiedades para selecciones múltiples
    public $cargosSeleccionados = [];
    public $nacionalidadesSeleccionadas = [];
    public $tiposVehiculoSeleccionados = [];     // <--- NUEVO
    public $tiposMaquinariaSeleccionados = [];  // <--- NUEVO
    public $tiposEmbarcacionSeleccionados = []; // <--- NUEVO

    // --- Listados para selects ---
    public $mandantes;
    public Collection $tiposEntidadControlable; // Tipado para fácil acceso
    public $nombresDocumento;
    public $tiposCondicionEmpresa;
    public $tiposCondicionPersonal;
    public $cargosMandante = [];
    public $nacionalidades;
    public $tiposVehiculo;                      // <--- NUEVO
    public $tiposMaquinaria;                   // <--- NUEVO
    public $tiposEmbarcacion;                  // <--- NUEVO
    public $condicionesFechaIngreso;
    public $observacionesDocumento;
    public $formatosDocumentoMuestra;
    public $tiposVencimiento;
    public $configuracionesValidacion;
    public $criteriosEvaluacion;
    public $subCriteriosGeneral = [];
    public $textosRechazo;
    public $aclaracionesCriterio;

    public $showConfirmDeleteModal = false;
    public $reglaIdParaEliminar;
    public $nombreReglaParaEliminar;

    // --- Propiedades para filtros y ordenamiento (Listado principal) ---
    public $filtroMandanteId = '';
    public $filtroTipoEntidadId = '';
    public $filtroNombreDocumento = '';
    public $sortBy = 'reglas_documentales.id';
    public $sortDirection = 'desc';

    // --- Propiedad computada para nombre de entidad ---
    public ?string $nombreEntidadSeleccionada = null; // Para lógica de UI

    protected function rules()
    {
        $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION'];
        $idsTiposVencimientoQueRequierenDias = [];
        if ($this->tiposVencimiento instanceof Collection && !$this->tiposVencimiento->isEmpty()) {
            $idsTiposVencimientoQueRequierenDias = $this->tiposVencimiento
                ->whereIn('nombre', $nombresTiposVencimientoQueRequierenDias)
                ->pluck('id')
                ->toArray();
        }
    
        $rules = [
            'mandante_id' => 'required|exists:mandantes,id',
            'tipo_entidad_controlada_id' => 'required|exists:tipos_entidad_controlable,id',
            'nombre_documento_id' => 'required|exists:nombre_documentos,id',
            'valor_nominal_documento' => 'nullable|integer|min:0',
            'aplica_empresa_condicion_id' => 'nullable|exists:tipos_condicion,id',
            
            'rut_especificos' => 'nullable|string|max:65535',
            'rut_excluidos' => 'nullable|string|max:65535', 
            
            'condicion_fecha_ingreso_id' => 'nullable|exists:condiciones_fecha_ingreso,id',
            'fecha_comparacion_ingreso' => 'nullable|date|required_with:condicion_fecha_ingreso_id',
            'observacion_documento_id' => 'nullable|exists:observaciones_documento,id',
            'formato_documento_id' => 'nullable|exists:formatos_documento_muestra,id',
            'documento_relacionado_id' => 'nullable|exists:nombre_documentos,id|different:nombre_documento_id',
            'tipo_vencimiento_id' => 'nullable|exists:tipos_vencimiento,id',
            'dias_validez_documento' => [
                'nullable', 'integer', 'min:0',
                Rule::requiredIf(function () use ($idsTiposVencimientoQueRequierenDias) {
                    return in_array($this->tipo_vencimiento_id, $idsTiposVencimientoQueRequierenDias);
                }),
            ],
            'dias_aviso_vencimiento' => 'nullable|integer|min:0',
            'valida_emision' => 'boolean',
            'valida_vencimiento' => 'boolean',
            'configuracion_validacion_id' => 'nullable|exists:configuraciones_validacion,id',
            'restringe_acceso' => 'boolean',
            'afecta_porcentaje_cumplimiento' => 'boolean',
            'documento_es_perseguidor' => 'boolean',
            'mostrar_historico_documento' => 'boolean',
            'is_active' => 'boolean',
            'criterios' => 'array',
            'criterios.*.criterio_evaluacion_id' => 'required|exists:criterios_evaluacion,id',
            'criterios.*.sub_criterio_id' => 'nullable|exists:sub_criterios,id',
            'criterios.*.texto_rechazo_id' => 'nullable|exists:textos_rechazo,id',
            'criterios.*.aclaracion_criterio_id' => 'nullable|exists:aclaraciones_criterio,id',
            'unidadesSeleccionadas' => 'array|min:1', 
            'unidadesSeleccionadas.*.final_uo_id' => 'required|exists:unidades_organizacionales_mandante,id',
        ];

        // Reglas condicionales basadas en la entidad seleccionada
        $entidad = $this->getNombreEntidadSeleccionada();

        if ($entidad === 'PERSONA') {
            $rules['aplica_persona_condicion_id'] = 'nullable|exists:tipos_condicion_personal,id';
            $rules['cargosSeleccionados'] = 'nullable|array'; 
            $rules['cargosSeleccionados.*'] = 'integer|exists:cargos_mandante,id'; 
            $rules['nacionalidadesSeleccionadas'] = 'nullable|array'; 
            $rules['nacionalidadesSeleccionadas.*'] = 'integer|exists:nacionalidades,id';
            $rules['permite_ver_nacionalidad_trabajador'] = 'boolean';
            $rules['permite_modificar_nacionalidad_trabajador'] = 'boolean';
            $rules['permite_ver_fecha_nacimiento_trabajador'] = 'boolean';
            $rules['permite_modificar_fecha_nacimiento_trabajador'] = 'boolean';
        } elseif ($entidad === 'VEHICULO') {
            $rules['tiposVehiculoSeleccionados'] = 'nullable|array';
            $rules['tiposVehiculoSeleccionados.*'] = 'integer|exists:tipos_vehiculo,id';
        } elseif ($entidad === 'MAQUINARIA') {
            $rules['tiposMaquinariaSeleccionados'] = 'nullable|array';
            $rules['tiposMaquinariaSeleccionados.*'] = 'integer|exists:tipos_maquinaria,id';
        } elseif ($entidad === 'EMBARCACION') {
            $rules['tiposEmbarcacionSeleccionados'] = 'nullable|array';
            $rules['tiposEmbarcacionSeleccionados.*'] = 'integer|exists:tipos_embarcacion,id';
        }
        // Para 'EMPRESA', no se añaden reglas específicas de Persona, Vehículo, etc.

        return $rules;
    }

    protected $validationAttributes = [
        'mandante_id' => 'Mandante',
        'tipo_entidad_controlada_id' => 'Entidad Controlada',
        'nombre_documento_id' => 'Documento',
        'aplica_empresa_condicion_id' => 'Condición Empresa',
        'aplica_persona_condicion_id' => 'Condición Persona',
        'cargosSeleccionados' => 'Cargos Aplicables',
        'cargosSeleccionados.*' => 'Cargo Aplicable',
        'nacionalidadesSeleccionadas' => 'Nacionalidades Aplicables',
        'nacionalidadesSeleccionadas.*' => 'Nacionalidad Aplicable',
        'tiposVehiculoSeleccionados' => 'Tipos de Vehículo Aplicables',        // <--- NUEVO
        'tiposVehiculoSeleccionados.*' => 'Tipo de Vehículo Aplicable',     // <--- NUEVO
        'tiposMaquinariaSeleccionados' => 'Tipos de Maquinaria Aplicables',  // <--- NUEVO
        'tiposMaquinariaSeleccionados.*' => 'Tipo de Maquinaria Aplicable', // <--- NUEVO
        'tiposEmbarcacionSeleccionados' => 'Tipos de Embarcación Aplicables',// <--- NUEVO
        'tiposEmbarcacionSeleccionados.*' => 'Tipo de Embarcación Aplicable',// <--- NUEVO
        'rut_especificos' => 'Identificadores Específicos', // Etiqueta genérica que cambiará en la vista
        'rut_excluidos' => 'Identificadores Excluidos',   // Etiqueta genérica que cambiará en la vista
        'condicion_fecha_ingreso_id' => 'Opción Fechas Ingreso',
        'fecha_comparacion_ingreso' => 'Fecha de Comparación',
        'observacion_documento_id' => 'Observación Documento',
        'formato_documento_id' => 'Formato Documento',
        'documento_relacionado_id' => 'Documento Relacionado',
        'tipo_vencimiento_id' => 'Tipo de Vencimiento',
        'dias_validez_documento' => 'Días Validez Documento',
        'configuracion_validacion_id' => 'Quién Valida',
        'criterios.*.criterio_evaluacion_id' => 'Criterio de Evaluación (fila :index)',
        'unidadesSeleccionadas.*.final_uo_id' => 'Unidad Organizacional seleccionada (fila :index)',
    ];

    public function mount()
    {
        $this->cargarListadosUniversales();
        $this->actualizarNombreEntidadSeleccionada(); 
    }

    public function cargarListadosUniversales()
    {
        $this->mandantes = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->tiposEntidadControlable = TipoEntidadControlable::where('is_active', true)->orderBy('nombre_entidad')->get();
        $this->nombresDocumento = NombreDocumento::where('is_active', true)->orderBy('nombre')->get();
        $this->tiposCondicionEmpresa = TipoCondicion::where('is_active', true)->orderBy('nombre')->get();
        $this->tiposCondicionPersonal = TipoCondicionPersonal::where('is_active', true)->orderBy('nombre')->get();
        $this->nacionalidades = Nacionalidad::where('is_active', true)->orderBy('nombre')->get();
        $this->tiposVehiculo = TipoVehiculo::where('is_active', true)->orderBy('nombre')->get();           // <--- NUEVO
        $this->tiposMaquinaria = TipoMaquinaria::where('is_active', true)->orderBy('nombre')->get();       // <--- NUEVO
        $this->tiposEmbarcacion = TipoEmbarcacion::where('is_active', true)->orderBy('nombre')->get();     // <--- NUEVO
        $this->condicionesFechaIngreso = CondicionFechaIngreso::where('is_active', true)->orderBy('nombre')->get();
        $this->observacionesDocumento = ObservacionDocumento::where('is_active', true)->orderBy('titulo')->get();
        $this->formatosDocumentoMuestra = FormatoDocumentoMuestra::where('is_active', true)->orderBy('nombre')->get();
        if ($this->tiposVencimiento === null || ($this->tiposVencimiento instanceof Collection && $this->tiposVencimiento->isEmpty())) {
            $this->tiposVencimiento = TipoVencimiento::where('is_active', true)->orderBy('nombre')->get();
        }
        $this->configuracionesValidacion = ConfiguracionValidacion::where('is_active', true)->orderBy('nombre')->get();
        $this->criteriosEvaluacion = CriterioEvaluacion::where('is_active', true)->orderBy('nombre_criterio')->get();
        $this->subCriteriosGeneral = SubCriterio::where('is_active', true)->orderBy('nombre')->get();
        $this->textosRechazo = TextoRechazo::where('is_active', true)->orderBy('titulo')->get();
        $this->aclaracionesCriterio = AclaracionCriterio::where('is_active', true)->orderBy('titulo')->get();
    }
    
    public function updatedTipoEntidadControladaId($value)
    {
        // Resetear campos que dependen de la entidad anterior
        $this->aplica_persona_condicion_id = null;
        $this->cargosSeleccionados = [];
        $this->nacionalidadesSeleccionadas = [];
        $this->tiposVehiculoSeleccionados = [];
        $this->tiposMaquinariaSeleccionados = [];
        $this->tiposEmbarcacionSeleccionados = [];
        // Resetear opciones de identidad si no es Persona
        $this->permite_ver_nacionalidad_trabajador = false;
        $this->permite_modificar_nacionalidad_trabajador = false;
        $this->permite_ver_fecha_nacimiento_trabajador = false;
        $this->permite_modificar_fecha_nacimiento_trabajador = false;

        $this->actualizarNombreEntidadSeleccionada();
    }

    private function actualizarNombreEntidadSeleccionada()
    {
        if ($this->tipo_entidad_controlada_id && $this->tiposEntidadControlable instanceof Collection) {
            $entidad = $this->tiposEntidadControlable->firstWhere('id', $this->tipo_entidad_controlada_id);
            $this->nombreEntidadSeleccionada = $entidad ? strtoupper($entidad->nombre_entidad) : null;
        } else {
            $this->nombreEntidadSeleccionada = null;
        }
    }

    public function getNombreEntidadSeleccionada() : ?string
    {
        // Este método asegura que siempre tengamos el nombre actualizado para la lógica en rules y la vista
        if (!$this->nombreEntidadSeleccionada && $this->tipo_entidad_controlada_id) {
            $this->actualizarNombreEntidadSeleccionada();
        }
        return $this->nombreEntidadSeleccionada;
    }


    // --- Métodos para filtros y paginación (Sin cambios) ---
    public function updatedFiltroMandanteId(){ $this->resetPage(); }
    public function updatedFiltroTipoEntidadId(){ $this->resetPage(); }
    public function updatedFiltroNombreDocumento(){ $this->resetPage(); }
    public function resetFilters(){ $this->filtroMandanteId = ''; $this->filtroTipoEntidadId = ''; $this->filtroNombreDocumento = ''; $this->sortBy = 'reglas_documentales.id'; $this->sortDirection = 'desc'; $this->resetPage(); }
    public function sortBy($field){ if ($this->sortBy === $field) { $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; } else { $this->sortDirection = 'asc'; } $this->sortBy = $field; $this->resetPage(); }

    public function updatedMandanteId($value)
    {
        $this->cargosMandante = $value ? CargoMandante::where('mandante_id', $value)->where('is_active', true)->orderBy('nombre_cargo')->get() : [];
        $this->cargosSeleccionados = []; // Resetear cargos al cambiar mandante
        // Resetear UOs
        if (!empty($this->unidadesSeleccionadas)) {
            foreach (array_keys($this->unidadesSeleccionadas) as $index) {
                $this->unidadesSeleccionadas[$index]['uo_nivel1_id'] = null;
                $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] = null;
                $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = null;
                $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null;
                $this->unidadesSeleccionadas[$index]['final_uo_id'] = null;
            }
        }
    }

    public function updatedTipoVencimientoId($value) // Sin cambios en su lógica interna
    {
        $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION'];
        $tipoSeleccionado = null;
        if ($this->tiposVencimiento instanceof Collection && $value) {
             $tipoSeleccionado = $this->tiposVencimiento->firstWhere('id', $value);
        }

        if ($tipoSeleccionado && !in_array(strtoupper($tipoSeleccionado->nombre), $nombresTiposVencimientoQueRequierenDias)) {
            $this->dias_validez_documento = null;
        }
        if (empty($value)) {
             $this->dias_validez_documento = null;
        }
    }

    // Métodos para UO (Sin cambios)
    public function getNivel1Options($index) { if (!$this->mandante_id) { return []; } return UnidadOrganizacionalMandante::where('mandante_id', $this->mandante_id)->whereNull('parent_id')->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function getNivel2Options($index) { $parentId = $this->unidadesSeleccionadas[$index]['uo_nivel1_id'] ?? null; if (!$parentId) { return []; } return UnidadOrganizacionalMandante::where('parent_id', $parentId)->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function getNivel3Options($index) { $parentId = $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] ?? null; if (!$parentId) { return []; } return UnidadOrganizacionalMandante::where('parent_id', $parentId)->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function getNivel4Options($index) { $parentId = $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] ?? null; if (!$parentId) { return []; } return UnidadOrganizacionalMandante::where('parent_id', $parentId)->where('is_active', true)->orderBy('nombre_unidad')->get()->toArray(); }
    public function uoNivel1Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel1_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] = null; $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = null; $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: null; }
    public function uoNivel2Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel2_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = null; $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: ($this->unidadesSeleccionadas[$index]['uo_nivel1_id'] ?? null); }
    public function uoNivel3Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel3_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = null; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: ($this->unidadesSeleccionadas[$index]['uo_nivel2_id'] ?? null); }
    public function uoNivel4Changed($index, $selectedValue) { $this->unidadesSeleccionadas[$index]['uo_nivel4_id'] = $selectedValue; $this->unidadesSeleccionadas[$index]['final_uo_id'] = $selectedValue ?: ($this->unidadesSeleccionadas[$index]['uo_nivel3_id'] ?? null); }
    public function agregarUnidadSeleccionada() { $this->unidadesSeleccionadas[] = [ 'uo_nivel1_id' => null, 'uo_nivel2_id' => null, 'uo_nivel3_id' => null, 'uo_nivel4_id' => null, 'final_uo_id'  => null, ]; }
    public function eliminarUnidadSeleccionada($index) { unset($this->unidadesSeleccionadas[$index]); $this->unidadesSeleccionadas = array_values($this->unidadesSeleccionadas); if (empty($this->unidadesSeleccionadas)) { $this->agregarUnidadSeleccionada(); } }
    
    // Métodos para Criterios (Sin cambios)
    public function agregarCriterio() { $this->criterios[] = ['criterio_evaluacion_id' => '', 'sub_criterio_id' => '', 'texto_rechazo_id' => '', 'aclaracion_criterio_id' => '']; }
    public function eliminarCriterio($index) { unset($this->criterios[$index]); $this->criterios = array_values($this->criterios); if (empty($this->criterios)) { $this->agregarCriterio(); } }

    // Métodos para selección múltiple (Cargos y Nacionalidades - existentes)
    public function quitarSeleccionDeCargos() { $this->cargosSeleccionados = []; }
    public function seleccionarTodosLosCargos() { if ($this->cargosMandante && $this->cargosMandante->isNotEmpty()) { $this->cargosSeleccionados = $this->cargosMandante->pluck('id')->toArray(); } else { $this->cargosSeleccionados = []; } }
    public function quitarSeleccionDeNacionalidades() { $this->nacionalidadesSeleccionadas = []; }
    public function seleccionarTodasLasNacionalidades() { if ($this->nacionalidades && $this->nacionalidades->isNotEmpty()) { $this->nacionalidadesSeleccionadas = $this->nacionalidades->pluck('id')->toArray(); } else { $this->nacionalidadesSeleccionadas = []; } }

    // --- NUEVOS MÉTODOS PARA SELECCIÓN MÚLTIPLE DE TIPOS DE VEHÍCULO, MAQUINARIA, EMBARCACIÓN ---
    public function quitarSeleccionDeTiposVehiculo() { $this->tiposVehiculoSeleccionados = []; }
    public function seleccionarTodosLosTiposVehiculo() { if ($this->tiposVehiculo && $this->tiposVehiculo->isNotEmpty()) { $this->tiposVehiculoSeleccionados = $this->tiposVehiculo->pluck('id')->toArray(); } else { $this->tiposVehiculoSeleccionados = []; } }

    public function quitarSeleccionDeTiposMaquinaria() { $this->tiposMaquinariaSeleccionados = []; }
    public function seleccionarTodosLosTiposMaquinaria() { if ($this->tiposMaquinaria && $this->tiposMaquinaria->isNotEmpty()) { $this->tiposMaquinariaSeleccionados = $this->tiposMaquinaria->pluck('id')->toArray(); } else { $this->tiposMaquinariaSeleccionados = []; } }

    public function quitarSeleccionDeTiposEmbarcacion() { $this->tiposEmbarcacionSeleccionados = []; }
    public function seleccionarTodosLosTiposEmbarcacion() { if ($this->tiposEmbarcacion && $this->tiposEmbarcacion->isNotEmpty()) { $this->tiposEmbarcacionSeleccionados = $this->tiposEmbarcacion->pluck('id')->toArray(); } else { $this->tiposEmbarcacionSeleccionados = []; } }
    // --- FIN NUEVOS MÉTODOS ---
    
    public function create() { $this->resetInputFields(); $this->modoEdicion = false; $this->agregarUnidadSeleccionada(); $this->agregarCriterio(); $this->openModal(); }
    public function openModal() { $this->isOpen = true; $this->actualizarNombreEntidadSeleccionada(); /*Asegurar al abrir*/ }
    public function closeModal() { $this->isOpen = false; $this->resetErrorBag(); }
    
    private function resetInputFields() {
        $this->reglaDocumentalId = null; $this->mandante_id = null; $this->tipo_entidad_controlada_id = null; $this->nombre_documento_id = null;
        $this->valor_nominal_documento = 1; $this->aplica_empresa_condicion_id = null; $this->aplica_persona_condicion_id = null;
        $this->cargosSeleccionados = []; 
        $this->nacionalidadesSeleccionadas = []; 
        $this->tiposVehiculoSeleccionados = [];     // <--- NUEVO RESET
        $this->tiposMaquinariaSeleccionados = [];  // <--- NUEVO RESET
        $this->tiposEmbarcacionSeleccionados = []; // <--- NUEVO RESET
        $this->rut_especificos = null; $this->rut_excluidos = null;
        $this->condicion_fecha_ingreso_id = null; $this->fecha_comparacion_ingreso = null; $this->observacion_documento_id = null;
        $this->formato_documento_id = null; $this->documento_relacionado_id = null; $this->tipo_vencimiento_id = null;
        $this->dias_validez_documento = null; $this->dias_aviso_vencimiento = 30; $this->valida_emision = false; $this->valida_vencimiento = false;
        $this->configuracion_validacion_id = null; $this->restringe_acceso = false; $this->afecta_porcentaje_cumplimiento = false;
        $this->documento_es_perseguidor = false; $this->mostrar_historico_documento = false;
        $this->permite_ver_nacionalidad_trabajador = false; $this->permite_modificar_nacionalidad_trabajador = false;
        $this->permite_ver_fecha_nacimiento_trabajador = false; $this->permite_modificar_fecha_nacimiento_trabajador = false;
        $this->is_active = true; 
        $this->criterios = []; 
        $this->unidadesSeleccionadas = []; 
        $this->cargosMandante = []; 
        $this->modoEdicion = false;
        $this->reglaIdParaEliminar = null; 
        $this->nombreReglaParaEliminar = null; 
        $this->showConfirmDeleteModal = false; 
        $this->actualizarNombreEntidadSeleccionada(); // Actualizar al resetear
        $this->resetErrorBag(); 
    }

    private function prepararDatosParaDB(array $data): array
    {
        $camposFKOpcionales = [
            'aplica_empresa_condicion_id', 'aplica_persona_condicion_id',
            'condicion_fecha_ingreso_id', 'observacion_documento_id',
            'formato_documento_id', 'documento_relacionado_id', 'tipo_vencimiento_id',
            'configuracion_validacion_id', 'dias_validez_documento'
        ];

        foreach ($camposFKOpcionales as $campo) {
            if (isset($data[$campo]) && ($data[$campo] === '' || $data[$campo] === null)) { // Asegurar que null también se maneje
                $data[$campo] = null;
            }
        }
        
        if (empty($data['condicion_fecha_ingreso_id'])) {
            $data['fecha_comparacion_ingreso'] = null;
        }

        $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION'];
        $tipoVencimientoSeleccionado = null;
        if ($this->tiposVencimiento instanceof Collection && !empty($data['tipo_vencimiento_id'])) {
             $tipoVencimientoSeleccionado = $this->tiposVencimiento->firstWhere('id', $data['tipo_vencimiento_id']);
        }
        if (!$tipoVencimientoSeleccionado || !in_array(strtoupper($tipoVencimientoSeleccionado->nombre), $nombresTiposVencimientoQueRequierenDias)) {
            $data['dias_validez_documento'] = null;
        }
        
        // Limpiar datos no aplicables según la entidad
        $entidad = $this->getNombreEntidadSeleccionada();
        if ($entidad !== 'PERSONA') {
            $data['aplica_persona_condicion_id'] = null;
            $data['permite_ver_nacionalidad_trabajador'] = false;
            $data['permite_modificar_nacionalidad_trabajador'] = false;
            $data['permite_ver_fecha_nacimiento_trabajador'] = false;
            $data['permite_modificar_fecha_nacimiento_trabajador'] = false;
        }
        if ($entidad !== 'PERSONA' && $entidad !== 'VEHICULO' && $entidad !== 'MAQUINARIA' && $entidad !== 'EMBARCACION') {
             // No hay campos específicos de estos tipos que estén directamente en $data principal, se manejan por sync
        }


        return $data;
    }

    public function store() {
        $validatedData = $this->validate(); // Las reglas ahora son dinámicas
        $datosParaGuardar = $this->prepararDatosParaDB($validatedData);

        try {
            DB::beginTransaction();
            $regla = ReglaDocumental::create([
                'mandante_id' => $datosParaGuardar['mandante_id'], 
                'tipo_entidad_controlada_id' => $datosParaGuardar['tipo_entidad_controlada_id'],
                'nombre_documento_id' => $datosParaGuardar['nombre_documento_id'],
                'valor_nominal_documento' => $datosParaGuardar['valor_nominal_documento'] ?? 1,
                'aplica_empresa_condicion_id' => $datosParaGuardar['aplica_empresa_condicion_id'],
                'aplica_persona_condicion_id' => $datosParaGuardar['aplica_persona_condicion_id'],
                'rut_especificos' => $datosParaGuardar['rut_especificos'],
                'rut_excluidos' => $datosParaGuardar['rut_excluidos'],
                'condicion_fecha_ingreso_id' => $datosParaGuardar['condicion_fecha_ingreso_id'],
                'fecha_comparacion_ingreso' => $datosParaGuardar['fecha_comparacion_ingreso'],
                'observacion_documento_id' => $datosParaGuardar['observacion_documento_id'],
                'formato_documento_id' => $datosParaGuardar['formato_documento_id'],
                'documento_relacionado_id' => $datosParaGuardar['documento_relacionado_id'],
                'tipo_vencimiento_id' => $datosParaGuardar['tipo_vencimiento_id'],
                'dias_validez_documento' => $datosParaGuardar['dias_validez_documento'],
                'dias_aviso_vencimiento' => $datosParaGuardar['dias_aviso_vencimiento'] ?? 30,
                'valida_emision' => $datosParaGuardar['valida_emision'],
                'valida_vencimiento' => $datosParaGuardar['valida_vencimiento'],
                'configuracion_validacion_id' => $datosParaGuardar['configuracion_validacion_id'],
                'restringe_acceso' => $datosParaGuardar['restringe_acceso'],
                'afecta_porcentaje_cumplimiento' => $datosParaGuardar['afecta_porcentaje_cumplimiento'],
                'documento_es_perseguidor' => $datosParaGuardar['documento_es_perseguidor'],
                'mostrar_historico_documento' => $datosParaGuardar['mostrar_historico_documento'],
                'permite_ver_nacionalidad_trabajador' => $datosParaGuardar['permite_ver_nacionalidad_trabajador'] ?? false,
                'permite_modificar_nacionalidad_trabajador' => $datosParaGuardar['permite_modificar_nacionalidad_trabajador'] ?? false,
                'permite_ver_fecha_nacimiento_trabajador' => $datosParaGuardar['permite_ver_fecha_nacimiento_trabajador'] ?? false,
                'permite_modificar_fecha_nacimiento_trabajador' => $datosParaGuardar['permite_modificar_fecha_nacimiento_trabajador'] ?? false,
                'is_active' => $datosParaGuardar['is_active'],
            ]);

            // Sincronizar relaciones según la entidad
            $entidad = $this->getNombreEntidadSeleccionada();
            if ($entidad === 'PERSONA') {
                $regla->cargosAplica()->sync($this->cargosSeleccionados ?? []);
                $regla->nacionalidadesAplica()->sync($this->nacionalidadesSeleccionadas ?? []);
            } elseif ($entidad === 'VEHICULO') {
                $regla->tiposVehiculoAplica()->sync($this->tiposVehiculoSeleccionados ?? []);
            } elseif ($entidad === 'MAQUINARIA') {
                $regla->tiposMaquinariaAplica()->sync($this->tiposMaquinariaSeleccionados ?? []);
            } elseif ($entidad === 'EMBARCACION') {
                $regla->tiposEmbarcacionAplica()->sync($this->tiposEmbarcacionSeleccionados ?? []);
            }
            // Para 'EMPRESA' no hay relaciones adicionales de este tipo a sincronizar
            
            $unidadesParaSincronizar = [];
            if (!empty($datosParaGuardar['unidadesSeleccionadas'])) {
                foreach ($datosParaGuardar['unidadesSeleccionadas'] as $unidadSet) { 
                    if (!empty($unidadSet['final_uo_id'])) { $unidadesParaSincronizar[] = $unidadSet['final_uo_id']; }
                }
            }
            $regla->unidadesOrganizacionales()->sync(array_unique($unidadesParaSincronizar));
            
            if (!empty($datosParaGuardar['criterios'])) {
                foreach ($datosParaGuardar['criterios'] as $criterioData) { 
                    if (!empty($criterioData['criterio_evaluacion_id'])) { $regla->criterios()->create($criterioData); }
                }
            }

            DB::commit(); 
            session()->flash('success', 'Regla documental creada exitosamente.'); 
            $this->closeModal();
            $this->resetInputFields(); 
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e; 
        } catch (\Exception $e) {
            DB::rollBack(); 
            $errorMessage = 'Ocurrió un error al crear la regla documental.';
            Log::error($errorMessage . ' Detalles: ' . $e->getMessage() . ' en ' . $e->getFile() . ' línea ' . $e->getLine() . ' Trace: ' . $e->getTraceAsString());
            if (app()->environment('local')) { $errorMessage .= ' Detalles: ' . $e->getMessage(); }
            session()->flash('error', $errorMessage);
        }
    }

    // _getUoHierarchyPath sin cambios
    private function _getUoHierarchyPath($unidadFinalId) { /* ... igual que antes ... */ 
        $path = [ 'uo_nivel1_id' => null, 'uo_nivel2_id' => null, 'uo_nivel3_id' => null, 'uo_nivel4_id' => null, 'final_uo_id' => $unidadFinalId, ];
        $currentUo = UnidadOrganizacionalMandante::with('parent.parent.parent')->find($unidadFinalId);
        if (!$currentUo) { Log::warning("No se encontró la UO con ID: {$unidadFinalId} en _getUoHierarchyPath"); return $path; }
        $hierarchy = []; $tempUo = $currentUo;
        while ($tempUo) { array_unshift($hierarchy, $tempUo->id); $tempUo = $tempUo->parent; }
        if (isset($hierarchy[0])) $path['uo_nivel1_id'] = $hierarchy[0];
        if (isset($hierarchy[1])) $path['uo_nivel2_id'] = $hierarchy[1];
        if (isset($hierarchy[2])) $path['uo_nivel3_id'] = $hierarchy[2];
        if (isset($hierarchy[3])) $path['uo_nivel4_id'] = $hierarchy[3];
        return $path;
    }

    public function edit($id) {
        $this->resetInputFields(); 
        $regla = ReglaDocumental::with([
            'unidadesOrganizacionales', 
            'criterios', 
            'cargosAplica', 
            'nacionalidadesAplica',
            'tiposVehiculoAplica',      // <--- NUEVO
            'tiposMaquinariaAplica',   // <--- NUEVO
            'tiposEmbarcacionAplica'   // <--- NUEVO
        ])->find($id); 

        if ($regla) {
            $this->reglaDocumentalId = $regla->id;
            $this->mandante_id = $regla->mandante_id;
            $this->updatedMandanteId($regla->mandante_id); 
            $this->tipo_entidad_controlada_id = $regla->tipo_entidad_controlada_id;
            $this->actualizarNombreEntidadSeleccionada(); // Asegurar que el nombre de entidad esté disponible
            
            $this->nombre_documento_id = $regla->nombre_documento_id;
            $this->valor_nominal_documento = $regla->valor_nominal_documento;
            $this->aplica_empresa_condicion_id = $regla->aplica_empresa_condicion_id;
            $this->rut_especificos = $regla->rut_especificos;
            $this->rut_excluidos = $regla->rut_excluidos;
            $this->condicion_fecha_ingreso_id = $regla->condicion_fecha_ingreso_id;
            $this->fecha_comparacion_ingreso = $regla->fecha_comparacion_ingreso ? $regla->fecha_comparacion_ingreso->format('Y-m-d') : null;
            $this->observacion_documento_id = $regla->observacion_documento_id;
            $this->formato_documento_id = $regla->formato_documento_id;
            $this->documento_relacionado_id = $regla->documento_relacionado_id;
            $this->tipo_vencimiento_id = $regla->tipo_vencimiento_id;
            $this->updatedTipoVencimientoId($regla->tipo_vencimiento_id);
            $this->dias_validez_documento = $regla->dias_validez_documento;
            $this->dias_aviso_vencimiento = $regla->dias_aviso_vencimiento;
            $this->valida_emision = (bool) $regla->valida_emision;
            $this->valida_vencimiento = (bool) $regla->valida_vencimiento;
            $this->configuracion_validacion_id = $regla->configuracion_validacion_id;
            $this->restringe_acceso = (bool) $regla->restringe_acceso;
            $this->afecta_porcentaje_cumplimiento = (bool) $regla->afecta_porcentaje_cumplimiento;
            $this->documento_es_perseguidor = (bool) $regla->documento_es_perseguidor;
            $this->mostrar_historico_documento = (bool) $regla->mostrar_historico_documento;
            $this->is_active = (bool) $regla->is_active;

            $entidad = $this->getNombreEntidadSeleccionada();
            if ($entidad === 'PERSONA') {
                $this->aplica_persona_condicion_id = $regla->aplica_persona_condicion_id;
                $this->cargosSeleccionados = $regla->cargosAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->nacionalidadesSeleccionadas = $regla->nacionalidadesAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
                $this->permite_ver_nacionalidad_trabajador = (bool) $regla->permite_ver_nacionalidad_trabajador;
                $this->permite_modificar_nacionalidad_trabajador = (bool) $regla->permite_modificar_nacionalidad_trabajador;
                $this->permite_ver_fecha_nacimiento_trabajador = (bool) $regla->permite_ver_fecha_nacimiento_trabajador;
                $this->permite_modificar_fecha_nacimiento_trabajador = (bool) $regla->permite_modificar_fecha_nacimiento_trabajador;
            } elseif ($entidad === 'VEHICULO') {
                $this->tiposVehiculoSeleccionados = $regla->tiposVehiculoAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            } elseif ($entidad === 'MAQUINARIA') {
                $this->tiposMaquinariaSeleccionados = $regla->tiposMaquinariaAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            } elseif ($entidad === 'EMBARCACION') {
                $this->tiposEmbarcacionSeleccionados = $regla->tiposEmbarcacionAplica->pluck('id')->map(fn($id) => (string)$id)->toArray();
            }

            $this->unidadesSeleccionadas = [];
            if ($regla->unidadesOrganizacionales->isNotEmpty()) {
                foreach ($regla->unidadesOrganizacionales as $uoSeleccionada) {
                    $this->unidadesSeleccionadas[] = $this->_getUoHierarchyPath($uoSeleccionada->id);
                }
            } else { $this->agregarUnidadSeleccionada(); }

            $this->criterios = [];
            if ($regla->criterios->isNotEmpty()) {
                foreach ($regla->criterios as $criterio) {
                    $this->criterios[] = [
                        'criterio_evaluacion_id' => $criterio->criterio_evaluacion_id,
                        'sub_criterio_id' => $criterio->sub_criterio_id,
                        'texto_rechazo_id' => $criterio->texto_rechazo_id,
                        'aclaracion_criterio_id' => $criterio->aclaracion_criterio_id,
                    ];
                }
            } else { $this->agregarCriterio(); }
            
            $this->modoEdicion = true;
            $this->openModal();
        } else {
            session()->flash('error', 'No se encontró la regla documental para editar.');
            Log::warning("Intento de editar regla no existente con ID: {$id}");
        }
    }
    
    public function update() {
        $validatedData = $this->validate();
        $datosParaActualizar = $this->prepararDatosParaDB($validatedData);

        if ($this->reglaDocumentalId) {
            try {
                DB::beginTransaction();
                $regla = ReglaDocumental::find($this->reglaDocumentalId);
                if (!$regla) {
                    throw new \Exception("Regla documental con ID {$this->reglaDocumentalId} no encontrada para actualizar.");
                }
                $regla->update([
                    'mandante_id' => $datosParaActualizar['mandante_id'],
                    'tipo_entidad_controlada_id' => $datosParaActualizar['tipo_entidad_controlada_id'],
                    'nombre_documento_id' => $datosParaActualizar['nombre_documento_id'],
                    'valor_nominal_documento' => $datosParaActualizar['valor_nominal_documento'] ?? 1,
                    'aplica_empresa_condicion_id' => $datosParaActualizar['aplica_empresa_condicion_id'],
                    'aplica_persona_condicion_id' => $datosParaActualizar['aplica_persona_condicion_id'],
                    'rut_especificos' => $datosParaActualizar['rut_especificos'],
                    'rut_excluidos' => $datosParaActualizar['rut_excluidos'],
                    'condicion_fecha_ingreso_id' => $datosParaActualizar['condicion_fecha_ingreso_id'],
                    'fecha_comparacion_ingreso' => $datosParaActualizar['fecha_comparacion_ingreso'],
                    'observacion_documento_id' => $datosParaActualizar['observacion_documento_id'],
                    'formato_documento_id' => $datosParaActualizar['formato_documento_id'],
                    'documento_relacionado_id' => $datosParaActualizar['documento_relacionado_id'],
                    'tipo_vencimiento_id' => $datosParaActualizar['tipo_vencimiento_id'],
                    'dias_validez_documento' => $datosParaActualizar['dias_validez_documento'],
                    'dias_aviso_vencimiento' => $datosParaActualizar['dias_aviso_vencimiento'] ?? 30,
                    'valida_emision' => $datosParaActualizar['valida_emision'],
                    'valida_vencimiento' => $datosParaActualizar['valida_vencimiento'],
                    'configuracion_validacion_id' => $datosParaActualizar['configuracion_validacion_id'],
                    'restringe_acceso' => $datosParaActualizar['restringe_acceso'],
                    'afecta_porcentaje_cumplimiento' => $datosParaActualizar['afecta_porcentaje_cumplimiento'],
                    'documento_es_perseguidor' => $datosParaActualizar['documento_es_perseguidor'],
                    'mostrar_historico_documento' => $datosParaActualizar['mostrar_historico_documento'],
                    'permite_ver_nacionalidad_trabajador' => $datosParaActualizar['permite_ver_nacionalidad_trabajador'] ?? false,
                    'permite_modificar_nacionalidad_trabajador' => $datosParaActualizar['permite_modificar_nacionalidad_trabajador'] ?? false,
                    'permite_ver_fecha_nacimiento_trabajador' => $datosParaActualizar['permite_ver_fecha_nacimiento_trabajador'] ?? false,
                    'permite_modificar_fecha_nacimiento_trabajador' => $datosParaActualizar['permite_modificar_fecha_nacimiento_trabajador'] ?? false,
                    'is_active' => $datosParaActualizar['is_active'],
                ]);

                // Sincronizar relaciones según la entidad
                $entidad = $this->getNombreEntidadSeleccionada();
                
                // Siempre desvincular todas las relaciones específicas de tipo para evitar datos huérfanos si la entidad cambia
                $regla->cargosAplica()->detach();
                $regla->nacionalidadesAplica()->detach();
                $regla->tiposVehiculoAplica()->detach();
                $regla->tiposMaquinariaAplica()->detach();
                $regla->tiposEmbarcacionAplica()->detach();

                if ($entidad === 'PERSONA') {
                    $regla->cargosAplica()->sync($this->cargosSeleccionados ?? []);
                    $regla->nacionalidadesAplica()->sync($this->nacionalidadesSeleccionadas ?? []);
                } elseif ($entidad === 'VEHICULO') {
                    $regla->tiposVehiculoAplica()->sync($this->tiposVehiculoSeleccionados ?? []);
                } elseif ($entidad === 'MAQUINARIA') {
                    $regla->tiposMaquinariaAplica()->sync($this->tiposMaquinariaSeleccionados ?? []);
                } elseif ($entidad === 'EMBARCACION') {
                    $regla->tiposEmbarcacionAplica()->sync($this->tiposEmbarcacionSeleccionados ?? []);
                }

                $unidadesParaSincronizar = [];
                if(!empty($datosParaActualizar['unidadesSeleccionadas'])){
                    foreach ($datosParaActualizar['unidadesSeleccionadas'] as $unidadSet) {
                        if (!empty($unidadSet['final_uo_id'])) { $unidadesParaSincronizar[] = $unidadSet['final_uo_id']; }
                    }
                }
                $regla->unidadesOrganizacionales()->sync(array_unique($unidadesParaSincronizar));

                $regla->criterios()->delete();
                if (!empty($datosParaActualizar['criterios'])) {
                    foreach ($datosParaActualizar['criterios'] as $criterioData) {
                        if (!empty($criterioData['criterio_evaluacion_id'])) { $regla->criterios()->create($criterioData); }
                    }
                }
                DB::commit();
                session()->flash('success', 'Regla documental actualizada exitosamente.');
                $this->closeModal();
                $this->resetInputFields(); 
            } catch (\Illuminate\Validation\ValidationException $e) {
                DB::rollBack();
                throw $e;
            } catch (\Exception $e) {
                DB::rollBack();
                $errorMessage = 'Ocurrió un error al actualizar la regla documental.';
                Log::error($errorMessage . ' Detalles: ' . $e->getMessage() . ' en ' . $e->getFile() . ' línea ' . $e->getLine() . ' Trace: ' . $e->getTraceAsString());
                if (app()->environment('local')) { $errorMessage .= ' Detalles: ' . $e->getMessage(); }
                session()->flash('error', $errorMessage);
            }
        } else {
             session()->flash('error', 'ID de regla no encontrado para actualizar.');
             Log::error("Intento de actualizar regla sin ID válido. ID actual: " . $this->reglaDocumentalId);
        }
    }

    // toggleActivo sin cambios
    public function toggleActivo($id) { /* ... igual que antes ... */ 
        $regla = ReglaDocumental::find($id);
        if ($regla) {
            try { $regla->is_active = !$regla->is_active; $regla->save(); $accion = $regla->is_active ? 'activada' : 'desactivada'; session()->flash('success', "Regla documental {$accion} exitosamente.");
            } catch (\Exception $e) { Log::error("Error al cambiar estado de regla ID {$id}: " . $e->getMessage()); session()->flash('error', 'Ocurrió un error al cambiar el estado de la regla.');}
        } else { session()->flash('error', 'Regla documental no encontrada.');}
    }

    // confirmarEliminacion sin cambios
    public function confirmarEliminacion($id) { /* ... igual que antes ... */ 
        $regla = ReglaDocumental::with('nombreDocumento')->find($id);
        if ($regla) { $this->reglaIdParaEliminar = $regla->id; $this->nombreReglaParaEliminar = "Regla para el documento '" . ($regla->nombreDocumento->nombre ?? 'Desconocido') . "' (ID: {$regla->id})"; $this->showConfirmDeleteModal = true;
        } else { session()->flash('error', 'Regla documental no encontrada para eliminar.'); Log::warning("Intento de confirmar eliminación para regla no existente ID: {$id}");}
    }

    public function deleteRegla()
    {
        $regla = ReglaDocumental::find($this->reglaIdParaEliminar);

        if ($regla) {
            try {
                DB::beginTransaction();
                $regla->criterios()->delete();
                $regla->unidadesOrganizacionales()->detach();
                $regla->cargosAplica()->detach(); 
                $regla->nacionalidadesAplica()->detach(); 
                $regla->tiposVehiculoAplica()->detach();     // <--- NUEVO
                $regla->tiposMaquinariaAplica()->detach();  // <--- NUEVO
                $regla->tiposEmbarcacionAplica()->detach(); // <--- NUEVO
                $regla->delete(); 
                DB::commit();
                session()->flash('success', 'Regla documental eliminada exitosamente.');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error al eliminar regla ID {$this->reglaIdParaEliminar}: " . $e->getMessage());
                session()->flash('error', 'Ocurrió un error al eliminar la regla documental.');
            }
        } else {
            session()->flash('error', 'Regla documental no encontrada para eliminar.');
            Log::warning("Intento de eliminar regla no existente con ID desde modal: {$this->reglaIdParaEliminar}");
        }
        $this->showConfirmDeleteModal = false;
        $this->reglaIdParaEliminar = null;
        $this->nombreReglaParaEliminar = null;
    }

    // render() sin cambios en su lógica principal, solo en las relaciones eager-loaded si es necesario (ya están ok)
    public function render()
    {
        $query = ReglaDocumental::query()
            ->select('reglas_documentales.*') 
            ->with([
                'mandante:id,razon_social',
                'tipoEntidadControlada:id,nombre_entidad',
                'nombreDocumento:id,nombre',
                'unidadesOrganizacionales:id,nombre_unidad',
                'cargosAplica:id,nombre_cargo', // Útil si la entidad es Persona
                'nacionalidadesAplica:id,nombre', // Útil si la entidad es Persona
                'tiposVehiculoAplica:id,nombre', // Útil si la entidad es Vehiculo
                'tiposMaquinariaAplica:id,nombre', // Útil si la entidad es Maquinaria
                'tiposEmbarcacionAplica:id,nombre' // Útil si la entidad es Embarcacion
            ]);

        if (!empty($this->filtroMandanteId)) { $query->where('reglas_documentales.mandante_id', $this->filtroMandanteId); }
        if (!empty($this->filtroTipoEntidadId)) { $query->where('reglas_documentales.tipo_entidad_controlada_id', $this->filtroTipoEntidadId); }
        if (!empty($this->filtroNombreDocumento)) { $query->join('nombre_documentos', 'reglas_documentales.nombre_documento_id', '=', 'nombre_documentos.id')->where('nombre_documentos.nombre', 'like', '%' . $this->filtroNombreDocumento . '%');}

        if ($this->sortBy === 'mandantes.razon_social') { $query->join('mandantes', 'reglas_documentales.mandante_id', '=', 'mandantes.id')->orderBy('mandantes.razon_social', $this->sortDirection);
        } elseif ($this->sortBy === 'tipos_entidad_controlable.nombre_entidad') { $query->join('tipos_entidad_controlable', 'reglas_documentales.tipo_entidad_controlada_id', '=', 'tipos_entidad_controlable.id')->orderBy('tipos_entidad_controlable.nombre_entidad', $this->sortDirection);
        } elseif ($this->sortBy === 'nombre_documentos.nombre') { if (empty($this->filtroNombreDocumento)) { $query->join('nombre_documentos', 'reglas_documentales.nombre_documento_id', '=', 'nombre_documentos.id'); } $query->orderBy('nombre_documentos.nombre', $this->sortDirection);
        } else { $query->orderBy($this->sortBy, $this->sortDirection); }
        
        if ($this->sortBy !== 'reglas_documentales.id') { $query->orderBy('reglas_documentales.id', 'desc'); }

        $reglas = $query->paginate(10);

        return view('livewire.gestion-reglas-documentales', [
            'reglas' => $reglas,
            'listaMandantes' => $this->mandantes,
            'listaTiposEntidad' => $this->tiposEntidadControlable, 
        ])->layout('layouts.app');
    }
}