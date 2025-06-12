<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TipoVehiculo; // Cambiado de Rubro a TipoVehiculo
use Livewire\WithPagination;

class GestionTiposVehiculo extends Component // Cambiado nombre de clase
{
    use WithPagination;

    public $nombre, $descripcion, $tipo_vehiculo_id, $is_active = true; // Cambiado rubro_id a tipo_vehiculo_id
    public $isOpen = false;
    public $searchTerm = '';
    public $filterByStatus = ''; // Todos, '1' para Activos, '0' para Inactivos

    protected function rules() // Se convierte a método para manejar la edición en la regla unique
    {
        return [
            'nombre' => 'required|string|max:255|unique:tipos_vehiculo,nombre' . ($this->tipo_vehiculo_id ? ',' . $this->tipo_vehiculo_id : ''), // Cambiada tabla y campo id
            'descripcion' => 'nullable|string|max:65535',
            'is_active' => 'boolean'
        ];
    }


    protected $validationAttributes = [
        'nombre' => 'Nombre del Tipo de Vehículo', // Cambiado
        'descripcion' => 'Descripción',
        'is_active' => 'Estado'
    ];

    public function mount()
    {
        // Puedes inicializar algo aquí si es necesario
    }

    public function render()
    {
        $query = TipoVehiculo::query(); // Cambiado

        if ($this->searchTerm) {
            $query->where('nombre', 'like', '%' . $this->searchTerm . '%');
        }

        if ($this->filterByStatus !== '') {
            $query->where('is_active', $this->filterByStatus === '1');
        }

        $tiposVehiculo = $query->orderBy('nombre', 'asc')->paginate(10); // Cambiado
        return view('livewire.gestion-tipos-vehiculo', ['tiposVehiculo' => $tiposVehiculo])->layout('layouts.app'); // Cambiada variable y vista
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
        $this->tipo_vehiculo_id = null; // Cambiado
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate($this->rules(), [], $this->validationAttributes); // Llama al método rules()

        TipoVehiculo::updateOrCreate(['id' => $this->tipo_vehiculo_id], [ // Cambiado
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash(
            'success',
            $this->tipo_vehiculo_id ? 'Tipo de Vehículo actualizado exitosamente.' : 'Tipo de Vehículo creado exitosamente.' // Cambiado
        );

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $tipoVehiculo = TipoVehiculo::findOrFail($id); // Cambiado
        $this->tipo_vehiculo_id = $id; // Cambiado
        $this->nombre = $tipoVehiculo->nombre; // Cambiado
        $this->descripcion = $tipoVehiculo->descripcion; // Cambiado
        $this->is_active = $tipoVehiculo->is_active; // Cambiado
        $this->openModal();
    }

    public function toggleStatus($id)
    {
        $tipoVehiculo = TipoVehiculo::findOrFail($id); // Cambiado
        $tipoVehiculo->is_active = !$tipoVehiculo->is_active; // Cambiado
        $tipoVehiculo->save(); // Cambiado

        $status = $tipoVehiculo->is_active ? 'activado' : 'desactivado'; // Cambiado
        session()->flash('success', "Tipo de Vehículo {$status} exitosamente."); // Cambiado
    }
}