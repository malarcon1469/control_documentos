<?php

namespace App\Livewire;

use App\Models\SubCriterio;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionSubCriterios extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $subCriterioId;
    public $nombre; // Esta es la propiedad que se enlaza al formulario y se valida
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
            // Aquí es donde se verifica la unicidad.
            // El primer argumento de Rule::unique es el nombre de la tabla ('sub_criterios').
            // El segundo argumento es el nombre de la columna en esa tabla para verificar la unicidad ('nombre').
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('sub_criterios', 'nombre')->ignore($this->subCriterioId),
            ],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del sub-criterio es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        'nombre.unique' => 'Este nombre de sub-criterio ya existe.',
    ];

    public function render()
    {
        $subCriterios = SubCriterio::query()
            ->when($this->search, function ($query) {
                // Aquí también, el where debe coincidir con el nombre de la columna en la BD
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-sub-criterios', [
            'subCriterios' => $subCriterios,
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
        $subCriterio = SubCriterio::findOrFail($id);
        $this->subCriterioId = $id;
        $this->nombre = $subCriterio->nombre; // Asignar el valor desde el modelo
        $this->is_active = $subCriterio->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate(); // Esto ejecutará las reglas de arriba

        SubCriterio::updateOrCreate(['id' => $this->subCriterioId], [
            'nombre' => $this->nombre, // Asegúrate que este 'nombre' es el que espera la BD
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->subCriterioId ? 'Sub-Criterio actualizado correctamente.' : 'Sub-Criterio creado correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $subCriterio = SubCriterio::findOrFail($id);
        $subCriterio->is_active = !$subCriterio->is_active;
        $subCriterio->save();
        session()->flash('message', 'Estado del Sub-Criterio actualizado.');
    }

    public function delete($id)
    {
        $subCriterio = SubCriterio::find($id);
        if ($subCriterio) {
            $subCriterio->is_active = false;
            $subCriterio->save();
            session()->flash('message', 'Sub-Criterio desactivado.');
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
        $this->subCriterioId = null;
        $this->nombre = '';
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