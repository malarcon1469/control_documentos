<?php

use Illuminate\Support\Facades\Route;

// --- Importaciones de Componentes para ASEM_Admin ---
use App\Livewire\GestionListadosUniversalesHub;
// Catálogos Universales
use App\Livewire\ListarNombreDocumentos;
use App\Livewire\GestionRubros;
use App\Livewire\GestionTiposEmpresaLegal;
use App\Livewire\GestionNacionalidades;
use App\Livewire\GestionTiposCondicionPersonal;
use App\Livewire\GestionSexos;
use App\Livewire\GestionEstadosCiviles;
use App\Livewire\GestionEtnias;
use App\Livewire\GestionNivelesEducacionales;
use App\Livewire\GestionCriteriosEvaluacion;
use App\Livewire\GestionSubCriterios;
use App\Livewire\GestionCondicionesFechaIngreso;
use App\Livewire\GestionConfiguracionesValidacion;
use App\Livewire\GestionTextosRechazo;
use App\Livewire\GestionAclaracionesCriterio;
use App\Livewire\GestionObservacionesDocumento;
use App\Livewire\GestionTiposCarga;
use App\Livewire\GestionTiposVencimiento;
use App\Livewire\GestionTiposEntidadControlable;
use App\Livewire\GestionTiposCondicion; 
use App\Livewire\GestionRangosCantidadTrabajadores;
use App\Livewire\GestionMutualidades;
use App\Livewire\GestionRegiones;
use App\Livewire\GestionComunas;
use App\Livewire\GestionFormatosMuestra;
use App\Livewire\GestionTiposVehiculo; 
use App\Livewire\GestionTiposMaquinaria; 
use App\Livewire\GestionTiposEmbarcacion; // <-- AÑADIDO PARA TIPOS DE EMBARCACIÓN
// Gestión Principal de Entidades por ASEM
use App\Livewire\GestionMandantes;
use App\Livewire\GestionUnidadesOrganizacionalesMandante;
use App\Livewire\GestionCargosMandante;
use App\Livewire\GestionContratistas;
use App\Livewire\GestionReglasDocumentales;

// --- Importaciones de Componentes para Contratista_Admin ---
use App\Livewire\FichaContratista;
use App\Livewire\GestionTrabajadoresContratista;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// --- RUTAS PARA ASEM_Admin ---
Route::prefix('gestion')->middleware(['auth', 'role:ASEM_Admin'])->name('gestion.')->group(function () {
    Route::get('/listados-universales', GestionListadosUniversalesHub::class)->name('listados.hub');

    // Catálogos / Listados Universales
    Route::get('/documentos', ListarNombreDocumentos::class)->name('documentos');
    Route::get('/rubros', GestionRubros::class)->name('rubros');
    Route::get('/tipos-empresa-legal', GestionTiposEmpresaLegal::class)->name('tipos-empresa-legal');
    Route::get('/nacionalidades', GestionNacionalidades::class)->name('nacionalidades');
    Route::get('/tipos-condicion-personal', GestionTiposCondicionPersonal::class)->name('tipos-condicion-personal');
    Route::get('/sexos', GestionSexos::class)->name('sexos');
    Route::get('/estados-civiles', GestionEstadosCiviles::class)->name('estados-civiles');
    Route::get('/etnias', GestionEtnias::class)->name('etnias');
    Route::get('/niveles-educacionales', GestionNivelesEducacionales::class)->name('niveles-educacionales');
    Route::get('/criterios-evaluacion', GestionCriteriosEvaluacion::class)->name('criterios-evaluacion');
    Route::get('/sub-criterios', GestionSubCriterios::class)->name('sub-criterios');
    Route::get('/condiciones-fecha-ingreso', GestionCondicionesFechaIngreso::class)->name('condiciones-fecha-ingreso');
    Route::get('/configuraciones-validacion', GestionConfiguracionesValidacion::class)->name('configuraciones-validacion');
    Route::get('/textos-rechazo', GestionTextosRechazo::class)->name('textos-rechazo');
    Route::get('/aclaraciones-criterio', GestionAclaracionesCriterio::class)->name('aclaraciones-criterio');
    Route::get('/observaciones-documento', GestionObservacionesDocumento::class)->name('observaciones-documento');
    Route::get('/tipos-carga', GestionTiposCarga::class)->name('tipos-carga');
    Route::get('/tipos-vencimiento', GestionTiposVencimiento::class)->name('tipos-vencimiento');
    Route::get('/tipos-entidad-controlable', GestionTiposEntidadControlable::class)->name('tipos-entidad-controlable');
    Route::get('/tipos-condicion', GestionTiposCondicion::class)->name('tipos-condicion');
    Route::get('/rangos-cantidad-trabajadores', GestionRangosCantidadTrabajadores::class)->name('rangos-cantidad-trabajadores');
    Route::get('/mutualidades', GestionMutualidades::class)->name('mutualidades');
    Route::get('/regiones', GestionRegiones::class)->name('regiones');
    Route::get('/comunas', GestionComunas::class)->name('comunas');
    Route::get('/formatos-muestra', GestionFormatosMuestra::class)->name('formatos-muestra');
    Route::get('/tipos-vehiculo', GestionTiposVehiculo::class)->name('tipos-vehiculo'); 
    Route::get('/tipos-maquinaria', GestionTiposMaquinaria::class)->name('tipos-maquinaria'); 
    Route::get('/tipos-embarcacion', GestionTiposEmbarcacion::class)->name('tipos-embarcacion'); // <-- RUTA AÑADIDA
    
    // Gestión Principal de Entidades
    Route::get('/mandantes', GestionMandantes::class)->name('mandantes');
    Route::get('/unidades-organizacionales-mandante', GestionUnidadesOrganizacionalesMandante::class)->name('unidades-organizacionales-mandante');
    Route::get('/cargos-mandante', GestionCargosMandante::class)->name('cargos-mandante');
    Route::get('/contratistas', GestionContratistas::class)->name('contratistas');
    Route::get('/reglas-documentales', GestionReglasDocumentales::class)->name('reglas-documentales');
});

// --- RUTAS PARA Contratista_Admin ---
Route::prefix('contratista')->middleware(['auth', 'role:Contratista_Admin'])->name('contratista.')->group(function () {
    Route::get('/mi-ficha', FichaContratista::class)->name('mi-ficha');
    Route::get('/trabajadores', GestionTrabajadoresContratista::class)->name('trabajadores');
});

Route::get('/gestion/documentos/consulta', ListarNombreDocumentos::class) 
    ->middleware(['auth', 'role:ASEM_Admin|Contratista_Admin']) 
    ->name('gestion.documentos.consulta');

require __DIR__.'/auth.php';