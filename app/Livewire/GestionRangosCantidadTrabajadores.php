<?php

namespace App\Livewire;

use App\Models\RangoCantidadTrabajadores;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionRangosCantidadTrabajadores extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $rangoId;
    public $nombre;
    public $descripcion;
    public $is_active = true;

    public $search = '';
    public $perPage = 10;
    public $sortBy = 'nombre';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortBy' => ['except' => 'nombre'],
        'sortDirection' => ['except' => 'asc'],
    ];

    protected function rules()
    {
        return [
            'nombre' => [
                'required',
                'string',
                'min:1', // Puede ser "1-10", "500+" etc.
                'max:255',
                Rule::unique('rangos_cantidad_trabajadores', 'nombre')->ignore($this->rangoId),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del rango es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 1 caracter.',
        'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        'nombre.unique' => 'Este nombre de rango ya existe.',
        'descripcion.max' => 'La descripción no puede exceder los 1000 caracteres.',
    ];

    public function render()
    {
        $rangos = RangoCantidadTrabajadores::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-rangos-cantidad-trabajadores', [
            'rangos' => $rangos,
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
        $rango = RangoCantidadTrabajadores::findOrFail($id);
        $this->rangoId = $id;
        $this->nombre = $rango->nombre;
        $this->descripcion = $rango->descripcion;
        $this->is_active = $rango->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        RangoCantidadTrabajadores::updateOrCreate(['id' => $this->rangoId], [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->rangoId ? 'Rango de cantidad de trabajadores actualizado.' : 'Rango de cantidad de trabajadores creado.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $rango = RangoCantidadTrabajadores::findOrFail($id);
        $rango->is_active = !$rango->is_active;
        $rango->save();
        session()->flash('message', 'Estado del rango actualizado.');
    }

    public function delete($id) // Considerar si realmente se borra o solo se desactiva
    {
        $rango = RangoCantidadTrabajadores::find($id);
        if ($rango) {
             // Opción 1: Desactivar en lugar de borrar
             $rango->is_active = false;
             $rango->save();
             session()->flash('message', 'Rango de cantidad de trabajadores desactivado.');
            // Opción 2: Borrar (Si no hay dependencias o se manejan cascadas)
            // $rango->delete();
            // session()->flash('message', 'Rango de cantidad de trabajadores eliminado.');
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
        $this->rangoId = null;
        $this->nombre = '';
        $this->descripcion = '';
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