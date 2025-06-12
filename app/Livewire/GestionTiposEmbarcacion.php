<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoEmbarcacion; // Cambiado
use Livewire\WithPagination;

class GestionTiposEmbarcacion extends Component // Cambiado nombre de clase
{
    use WithPagination;

    public $nombre, $descripcion, $tipo_embarcacion_id, $is_active = true; // Cambiado
    public $isOpen = false;
    public $searchTerm = '';
    public $filterByStatus = ''; 

    protected function rules() 
    {
        return [
            'nombre' => 'required|string|max:255|unique:tipos_embarcacion,nombre' . ($this->tipo_embarcacion_id ? ',' . $this->tipo_embarcacion_id : ''), // Cambiada tabla y campo id
            'descripcion' => 'nullable|string|max:65535',
            'is_active' => 'boolean'
        ];
    }

    protected $validationAttributes = [
        'nombre' => 'Nombre del Tipo de Embarcación', // Cambiado
        'descripcion' => 'Descripción',
        'is_active' => 'Estado'
    ];

    public function mount()
    {
        // Puedes inicializar algo aquí si es necesario
    }

    public function render()
    {
        $query = TipoEmbarcacion::query(); // Cambiado

        if ($this->searchTerm) {
            $query->where('nombre', 'like', '%' . $this->searchTerm . '%');
        }

        if ($this->filterByStatus !== '') {
            $query->where('is_active', $this->filterByStatus === '1');
        }

        $tiposEmbarcacion = $query->orderBy('nombre', 'asc')->paginate(10); // Cambiado
        return view('livewire.gestion-tipos-embarcacion', ['tiposEmbarcacion' => $tiposEmbarcacion])->layout('layouts.app'); // Cambiada variable y vista
    }
    
    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function updatingFilterByStatus()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetErrorBag(); 
    }

    private function resetInputFields()
    {
        $this->nombre = '';
        $this->descripcion = '';
        $this->tipo_embarcacion_id = null; // Cambiado
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate($this->rules(), [], $this->validationAttributes); 

        TipoEmbarcacion::updateOrCreate(['id' => $this->tipo_embarcacion_id], [ // Cambiado
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash(
            'success',
            $this->tipo_embarcacion_id ? 'Tipo de Embarcación actualizado exitosamente.' : 'Tipo de Embarcación creado exitosamente.' // Cambiado
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $tipoEmbarcacion = TipoEmbarcacion::findOrFail($id); // Cambiado
        $this->tipo_embarcacion_id = $id; // Cambiado
        $this->nombre = $tipoEmbarcacion->nombre; 
        $this->descripcion = $tipoEmbarcacion->descripcion; 
        $this->is_active = $tipoEmbarcacion->is_active; 
        $this->openModal();
    }

    public function toggleStatus($id)
    {
        $tipoEmbarcacion = TipoEmbarcacion::findOrFail($id); // Cambiado
        $tipoEmbarcacion->is_active = !$tipoEmbarcacion->is_active; 
        $tipoEmbarcacion->save(); 

        $status = $tipoEmbarcacion->is_active ? 'activado' : 'desactivado'; 
        session()->flash('success', "Tipo de Embarcación {$status} exitosamente."); // Cambiado
    }
}