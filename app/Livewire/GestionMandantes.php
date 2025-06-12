<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Mandante; // Modelo Mandante
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Gestión de Empresas Mandantes')]
class GestionMandantes extends Component
{
    use WithPagination;

    // Propiedades para el formulario y el modal
    public bool $mostrarModal = false;
    public ?Mandante $mandanteActual;
    public string $razon_social = '';
    public string $rut = '';
    public string $persona_contacto_nombre = '';
    public string $persona_contacto_email = '';
    public string $persona_contacto_telefono = '';
    public bool $is_active = true;

    // Propiedades para filtros
    public string $filtroRazonSocial = '';
    public string $filtroRut = '';
    public string $filtroEstado = 'todos'; // 'todos', 'activos', 'inactivos'

    protected function rules()
    {
        $mandanteId = $this->mandanteActual?->id ?? 'NULL';
        return [
            'razon_social' => "required|string|min:3|max:255", // La razón social no necesita ser única globalmente
            'rut' => "required|string|max:20|unique:mandantes,rut,{$mandanteId},id", // RUT sí debe ser único
            'persona_contacto_nombre' => 'required|string|max:255',
            'persona_contacto_email' => 'required|email|max:255',
            'persona_contacto_telefono' => 'required|string|max:50',
            'is_active' => 'required|boolean',
        ];
    }

    protected $messages = [
        'razon_social.required' => 'La razón social es obligatoria.',
        'rut.required' => 'El RUT es obligatorio.',
        'rut.unique' => 'Este RUT ya está registrado para otro mandante.',
        'persona_contacto_nombre.required' => 'El nombre del contacto es obligatorio.',
        'persona_contacto_email.required' => 'El email del contacto es obligatorio.',
        'persona_contacto_email.email' => 'El formato del email no es válido.',
        'persona_contacto_telefono.required' => 'El teléfono del contacto es obligatorio.',
    ];

    // Resetear paginación al filtrar
    public function updatedFiltroRazonSocial() { $this->resetPage(); }
    public function updatedFiltroRut() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }

    public function mount()
    {
        $this->mandanteActual = new Mandante();
    }

    public function render()
    {
        $query = Mandante::query();

        if (!empty($this->filtroRazonSocial)) {
            $query->where('razon_social', 'like', '%' . $this->filtroRazonSocial . '%');
        }
        if (!empty($this->filtroRut)) {
            $query->where('rut', 'like', '%' . $this->filtroRut . '%');
        }

        if ($this->filtroEstado === 'activos') {
            $query->where('is_active', true);
        } elseif ($this->filtroEstado === 'inactivos') {
            $query->where('is_active', false);
        }

        $mandantes = $query->orderBy('razon_social', 'asc')->paginate(10);

        return view('livewire.gestion-mandantes', [
            'mandantes' => $mandantes,
        ]);
    }

    public function abrirModalParaCrear()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->mandanteActual = new Mandante();
        $this->razon_social = '';
        $this->rut = '';
        $this->persona_contacto_nombre = '';
        $this->persona_contacto_email = '';
        $this->persona_contacto_telefono = '';
        $this->is_active = true;
        $this->mostrarModal = true;
    }

    public function abrirModalParaEditar(Mandante $mandante)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $this->resetValidation();
        $this->mandanteActual = $mandante;
        $this->razon_social = $mandante->razon_social;
        $this->rut = $mandante->rut;
        $this->persona_contacto_nombre = $mandante->persona_contacto_nombre;
        $this->persona_contacto_email = $mandante->persona_contacto_email;
        $this->persona_contacto_telefono = $mandante->persona_contacto_telefono;
        $this->is_active = $mandante->is_active;
        $this->mostrarModal = true;
    }

    public function guardarMandante()
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        $validatedData = $this->validate();
        
        $this->mandanteActual->fill($validatedData);
        $this->mandanteActual->save();

        if ($this->mandanteActual->wasRecentlyCreated) {
            session()->flash('success', 'Empresa Mandante creada exitosamente.');
        } else {
            session()->flash('success', 'Empresa Mandante actualizada exitosamente.');
        }
        
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->resetValidation();
        $this->razon_social = '';
        $this->rut = '';
        $this->persona_contacto_nombre = '';
        $this->persona_contacto_email = '';
        $this->persona_contacto_telefono = '';
        $this->is_active = true;
        $this->mandanteActual = new Mandante();
    }

    public function confirmarAlternarEstado(Mandante $mandante)
    {
        if (!Auth::user()->hasRole('ASEM_Admin')) {
            session()->flash('error', 'No tiene permisos para realizar esta acción.');
            return;
        }
        // Podríamos añadir una lógica aquí para verificar si el mandante tiene contratistas activos
        // o requisitos definidos antes de permitir desactivarlo, pero por ahora lo haremos simple.
        $nuevoEstado = !$mandante->is_active;
        $mandante->update(['is_active' => $nuevoEstado]);
        session()->flash('success', 'Estado de la Empresa Mandante actualizado exitosamente.');
    }
}