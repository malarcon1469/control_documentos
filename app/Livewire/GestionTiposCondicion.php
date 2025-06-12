<?php

namespace App\Livewire;

use App\Models\TipoCondicion;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout; // <-- AÑADIR ESTE IMPORT

#[Layout('layouts.app')] // <-- AÑADIR ESTA LÍNEA, indica que use el layout en resources/views/layouts/app.blade.php
class GestionTiposCondicion extends Component
{
    use WithPagination;

    // Propiedades para el modal y el formulario
    public $isOpen = false;
    public $tipoCondicionId;
    public $nombre;
    public $descripcion;
    public $is_active = true;

    // Propiedades para filtros y búsqueda
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
                'min:3',
                'max:255',
                Rule::unique('tipos_condicion', 'nombre')->ignore($this->tipoCondicionId),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del tipo de condición es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        'nombre.unique' => 'Este nombre de tipo de condición ya existe.',
        'descripcion.max' => 'La descripción no puede exceder los 1000 caracteres.',
    ];

    public function render()
    {
        $tiposCondicion = TipoCondicion::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-tipos-condicion', [
            'tiposCondicion' => $tiposCondicion,
        ]);
        // Ya no es necesario ->layout('layouts.app') aquí si usas el atributo #[Layout] arriba
    }

    public function create()
    {
        $this->resetInputFields();
        $this->is_active = true;
        $this->openModal();
    }

    public function edit($id)
    {
        $tipoCondicion = TipoCondicion::findOrFail($id);
        $this->tipoCondicionId = $id;
        $this->nombre = $tipoCondicion->nombre;
        $this->descripcion = $tipoCondicion->descripcion;
        $this->is_active = $tipoCondicion->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        TipoCondicion::updateOrCreate(['id' => $this->tipoCondicionId], [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->tipoCondicionId ? 'Tipo de Condición actualizado correctamente.' : 'Tipo de Condición creado correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $tipoCondicion = TipoCondicion::findOrFail($id);
        $tipoCondicion->is_active = !$tipoCondicion->is_active;
        $tipoCondicion->save();

        session()->flash('message', 'Estado del Tipo de Condición actualizado.');
    }

    public function delete($id)
    {
        $this_tc = TipoCondicion::find($id);
        if ($this_tc) {
             $this_tc->is_active = false;
             $this_tc->save();
            session()->flash('message', 'Tipo de Condición desactivado.');
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
        $this->tipoCondicionId = null;
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