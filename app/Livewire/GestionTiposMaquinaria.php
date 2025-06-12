<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoMaquinaria; // Cambiado
use Livewire\WithPagination;

class GestionTiposMaquinaria extends Component // Cambiado nombre de clase
{
    use WithPagination;

    public $nombre, $descripcion, $tipo_maquinaria_id, $is_active = true; // Cambiado
    public $isOpen = false;
    public $searchTerm = '';
    public $filterByStatus = ''; 

    protected function rules() 
    {
        return [
            'nombre' => 'required|string|max:255|unique:tipos_maquinaria,nombre' . ($this->tipo_maquinaria_id ? ',' . $this->tipo_maquinaria_id : ''), // Cambiada tabla y campo id
            'descripcion' => 'nullable|string|max:65535',
            'is_active' => 'boolean'
        ];
    }

    protected $validationAttributes = [
        'nombre' => 'Nombre del Tipo de Maquinaria', // Cambiado
        'descripcion' => 'Descripción',
        'is_active' => 'Estado'
    ];

    public function mount()
    {
        // Puedes inicializar algo aquí si es necesario
    }

    public function render()
    {
        $query = TipoMaquinaria::query(); // Cambiado

        if ($this->searchTerm) {
            $query->where('nombre', 'like', '%' . $this->searchTerm . '%');
        }

        if ($this->filterByStatus !== '') {
            $query->where('is_active', $this->filterByStatus === '1');
        }

        $tiposMaquinaria = $query->orderBy('nombre', 'asc')->paginate(10); // Cambiado
        return view('livewire.gestion-tipos-maquinaria', ['tiposMaquinaria' => $tiposMaquinaria])->layout('layouts.app'); // Cambiada variable y vista
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
        $this->tipo_maquinaria_id = null; // Cambiado
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate($this->rules(), [], $this->validationAttributes); 

        TipoMaquinaria::updateOrCreate(['id' => $this->tipo_maquinaria_id], [ // Cambiado
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash(
            'success',
            $this->tipo_maquinaria_id ? 'Tipo de Maquinaria actualizado exitosamente.' : 'Tipo de Maquinaria creado exitosamente.' // Cambiado
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $tipoMaquinaria = TipoMaquinaria::findOrFail($id); // Cambiado
        $this->tipo_maquinaria_id = $id; // Cambiado
        $this->nombre = $tipoMaquinaria->nombre; 
        $this->descripcion = $tipoMaquinaria->descripcion; 
        $this->is_active = $tipoMaquinaria->is_active; 
        $this->openModal();
    }

    public function toggleStatus($id)
    {
        $tipoMaquinaria = TipoMaquinaria::findOrFail($id); // Cambiado
        $tipoMaquinaria->is_active = !$tipoMaquinaria->is_active; 
        $tipoMaquinaria->save(); 

        $status = $tipoMaquinaria->is_active ? 'activado' : 'desactivado'; 
        session()->flash('success', "Tipo de Maquinaria {$status} exitosamente."); // Cambiado
    }
}