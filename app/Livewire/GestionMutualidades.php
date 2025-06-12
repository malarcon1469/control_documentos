<?php

namespace App\Livewire;

use App\Models\Mutualidad;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionMutualidades extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $mutualidadId;
    public $nombre;
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
                'min:2',
                'max:255',
                Rule::unique('mutualidades', 'nombre')->ignore($this->mutualidadId),
            ],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la mutualidad es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
        'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        'nombre.unique' => 'Este nombre de mutualidad ya existe.',
    ];

    public function render()
    {
        $mutualidades = Mutualidad::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-mutualidades', [
            'mutualidades' => $mutualidades,
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
        $mutualidad = Mutualidad::findOrFail($id);
        $this->mutualidadId = $id;
        $this->nombre = $mutualidad->nombre;
        $this->is_active = $mutualidad->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        Mutualidad::updateOrCreate(['id' => $this->mutualidadId], [
            'nombre' => $this->nombre,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->mutualidadId ? 'Mutualidad actualizada correctamente.' : 'Mutualidad creada correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $mutualidad = Mutualidad::findOrFail($id);
        $mutualidad->is_active = !$mutualidad->is_active;
        $mutualidad->save();
        session()->flash('message', 'Estado de la mutualidad actualizado.');
    }

    public function delete($id)
    {
        $mutualidad = Mutualidad::find($id);
        if ($mutualidad) {
             $mutualidad->is_active = false;
             $mutualidad->save();
            session()->flash('message', 'Mutualidad desactivada.');
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
        $this->mutualidadId = null;
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