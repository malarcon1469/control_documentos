<?php

namespace App\Livewire;

use App\Models\Contratista;
use App\Models\User;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use App\Models\Region;
use App\Models\Comuna;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB; // Asegúrate que DB esté importado para transacciones
use Livewire\Component;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException; // Asegúrate que esté importada
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class FichaContratista extends Component
{
    public Contratista $contratista;
    public User $adminUser;

    // Propiedades del Contratista (editables por el contratista)
    public $nombre_fantasia;
    public $direccion_calle, $direccion_numero, $comuna_id, $selected_region_id;
    public $telefono_empresa, $email_empresa_contratista;

    // Propiedades del Contratista (informativas)
    public $razon_social_info, $rut_contratista_info, $tipo_inscripcion_info;
    public $tipo_empresa_legal_info, $rubro_info, $rango_cantidad_info, $mutualidad_info;

    // Propiedades del Representante Legal (editables)
    public $rep_legal_nombres, $rep_legal_apellido_paterno, $rep_legal_apellido_materno;
    public $rep_legal_rut, $rep_legal_telefono, $rep_legal_email;

    // Propiedades para editar datos del propio Usuario Administrador
    public $admin_user_name_actual;
    public $admin_email_actual;
    public $admin_current_password, $admin_new_password, $admin_new_password_confirmation;

    // Listas para Selects
    public $regiones = [];
    public $comunasDisponibles = [];
    // No necesitamos las _All aquí ya que mostramos la info directa del $this->contratista->relation->nombre

    // Para el mensaje junto al botón
    public $formStatusMessage = '';
    public $formStatusType = '';

    public function mount()
    {
        $this->adminUser = Auth::user();
        if (!$this->adminUser->contratista_id) {
            session()->flash('error', 'Usuario no está asociado a una empresa contratista.');
            return redirect()->route('dashboard');
        }

        $this->contratista = Contratista::with([
            'comuna.region', 'tipoEmpresaLegal', 'rubro',
            'rangoCantidadTrabajadores', 'mutualidad'
        ])->findOrFail($this->adminUser->contratista_id);

        $this->nombre_fantasia = $this->contratista->nombre_fantasia;
        $this->direccion_calle = $this->contratista->direccion_calle;
        $this->direccion_numero = $this->contratista->direccion_numero;
        if ($this->contratista->comuna_id) {
            $this->selected_region_id = $this->contratista->comuna->region_id;
        }
        $this->comuna_id = $this->contratista->comuna_id;
        $this->telefono_empresa = $this->contratista->telefono_empresa;
        $this->email_empresa_contratista = $this->contratista->email_empresa;

        $this->razon_social_info = $this->contratista->razon_social;
        $this->rut_contratista_info = $this->contratista->rut;
        $this->tipo_inscripcion_info = $this->contratista->tipo_inscripcion;
        $this->tipo_empresa_legal_info = $this->contratista->tipoEmpresaLegal?->nombre;
        $this->rubro_info = $this->contratista->rubro?->nombre;
        $this->rango_cantidad_info = $this->contratista->rangoCantidadTrabajadores?->nombre;
        $this->mutualidad_info = $this->contratista->mutualidad?->nombre;

        $this->rep_legal_nombres = $this->contratista->rep_legal_nombres;
        $this->rep_legal_apellido_paterno = $this->contratista->rep_legal_apellido_paterno;
        $this->rep_legal_apellido_materno = $this->contratista->rep_legal_apellido_materno;
        $this->rep_legal_rut = $this->contratista->rep_legal_rut;
        $this->rep_legal_telefono = $this->contratista->rep_legal_telefono;
        $this->rep_legal_email = $this->contratista->rep_legal_email;

        $this->admin_user_name_actual = $this->adminUser->name;
        $this->admin_email_actual = $this->adminUser->email;

        $this->regiones = Region::where('is_active', true)->orderBy('nombre')->get();
        if ($this->selected_region_id) {
            $this->comunasDisponibles = Comuna::where('region_id', $this->selected_region_id)
                                            ->where('is_active', true)->orderBy('nombre')->get();
        }
    }

    public function rules()
    {
        return [
            'nombre_fantasia' => 'nullable|string|max:255',
            'direccion_calle' => 'required|string|max:255',
            'direccion_numero' => 'nullable|string|max:50',
            'selected_region_id' => 'required|exists:regiones,id',
            'comuna_id' => 'required|exists:comunas,id',
            'telefono_empresa' => 'nullable|string|max:20',
            'email_empresa_contratista' => ['required', 'email', 'max:255', Rule::unique('contratistas', 'email_empresa')->ignore($this->contratista->id)],

            'rep_legal_nombres' => 'required|string|max:255',
            'rep_legal_apellido_paterno' => 'required|string|max:255',
            'rep_legal_apellido_materno' => 'nullable|string|max:255',
            'rep_legal_rut' => ['nullable', 'string', 'max:12'/*, new RutRule*/],
            'rep_legal_telefono' => 'nullable|string|max:20',
            'rep_legal_email' => 'nullable|email|max:255',

            'admin_email_actual' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->adminUser->id)],
            'admin_current_password' => 'nullable|string|current_password',
            'admin_new_password' => 'nullable|required_with:admin_current_password|string|min:8|confirmed',
        ];
    }

    protected $messages = [
        'email_empresa_contratista.unique' => 'El email de la empresa ya está en uso por otro contratista.',
        'admin_email_actual.unique' => 'El nuevo email de administrador ya está en uso.',
        'admin_current_password.current_password' => 'La contraseña actual no es correcta.',
        'admin_new_password.required_with' => 'La nueva contraseña es requerida si ingresa la contraseña actual.',
         '*.required' => 'Este campo es obligatorio.',
    ];

    public function updatedSelectedRegionId($region_id)
    {
        if (!empty($region_id)) {
            $this->comunasDisponibles = Comuna::where('region_id', $region_id)->where('is_active', true)->orderBy('nombre')->get();
        } else {
            $this->comunasDisponibles = [];
        }
        $this->comuna_id = null;
    }

    public function updateFicha()
    {
        $this->resetFormStatus();

        try {
            $validatedData = $this->validate();

            DB::transaction(function () use ($validatedData) { // Pasar $validatedData a la clausura
                // Actualizar Contratista
                $this->contratista->nombre_fantasia = $this->nombre_fantasia; // O $validatedData['nombre_fantasia'] si prefieres
                $this->contratista->direccion_calle = $this->direccion_calle;
                $this->contratista->direccion_numero = $this->direccion_numero;
                $this->contratista->comuna_id = $this->comuna_id;
                $this->contratista->telefono_empresa = $this->telefono_empresa;
                $this->contratista->email_empresa = $this->email_empresa_contratista;

                $this->contratista->rep_legal_nombres = $this->rep_legal_nombres;
                $this->contratista->rep_legal_apellido_paterno = $this->rep_legal_apellido_paterno;
                $this->contratista->rep_legal_apellido_materno = $this->rep_legal_apellido_materno;
                $this->contratista->rep_legal_rut = $this->rep_legal_rut;
                $this->contratista->rep_legal_telefono = $this->rep_legal_telefono;
                $this->contratista->rep_legal_email = $this->rep_legal_email;
                $this->contratista->save();

                // Actualizar datos del Usuario Administrador (el logueado)
                $userChanged = false;
                if ($this->adminUser->email !== $this->admin_email_actual) { // O $validatedData['admin_email_actual']
                    $this->adminUser->email = $this->admin_email_actual;
                    $this->adminUser->email_verified_at = null;
                    $userChanged = true;
                }

                if (!empty($this->admin_current_password) && !empty($this->admin_new_password)) { // O $validatedData['admin_new_password']
                    $this->adminUser->password = Hash::make($this->admin_new_password);
                    $userChanged = true;
                }

                if ($userChanged) {
                    $this->adminUser->save();
                }

                $this->admin_current_password = null;
                $this->admin_new_password = null;
                $this->admin_new_password_confirmation = null;

                $this->formStatusMessage = '¡Ficha actualizada correctamente!';
                $this->formStatusType = 'success';
                session()->flash('message', 'Ficha de la empresa actualizada.');
            });
        } catch (ValidationException $e) {
            $this->formStatusMessage = 'Faltan campos obligatorios o hay errores de validación.';
            $this->formStatusType = 'error';
            // No relanzar la excepción si solo queremos mostrar mensajes
        } catch (\Exception $e) {
            $this->formStatusMessage = 'Ocurrió un error inesperado al actualizar.'; // Simplificado: ' . $e->getMessage();
            $this->formStatusType = 'error';
            session()->flash('error', 'Error inesperado: ' . $e->getMessage());
        }
    }

    public function resetFormStatus()
    {
        $this->formStatusMessage = '';
        $this->formStatusType = '';
    }

    public function updated($propertyName)
    {
        // Lista de propiedades que no deben resetear el mensaje
        $nonResettableProperties = [
            'formStatusMessage', 'formStatusType', 'contratista', 'adminUser', 
            'regiones', 'comunasDisponibles'
        ];

        if (!in_array($propertyName, $nonResettableProperties)) {
            $this->resetFormStatus();
        }
        // Validar solo el campo modificado para dar feedback inmediato si se desea (puede ser molesto)
        // try {
        //     $this->validateOnly($propertyName);
        // } catch (ValidationException $e) {
        //     // No hacer nada aquí, los errores se mostrarán por campo
        // }
    }

    public function render()
    {
        return view('livewire.ficha-contratista');
    }
}