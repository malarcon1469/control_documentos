<?php

namespace App\Livewire;

use App\Models\EstadoCivil;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionEstadosCiviles extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $estadoCivilId;
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
                'min:3',
                'max:255',
                Rule::unique('estados_civiles', 'nombre')->ignore($this->estadoCivilId),
            ],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del estado civil es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.unique' => 'Este nombre de estado civil ya existe.',
    ];

    public function render()
    {
        $estadosCiviles = EstadoCivil::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-estados-civiles', [
            'estadosCiviles' => $estadosCiviles,
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
        $estadoCivil = EstadoCivil::findOrFail($id);
        $this->estadoCivilId = $id;
        $this->nombre = $estadoCivil->nombre;
        $this->is_active = $estadoCivil->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        EstadoCivil::updateOrCreate(['id' => $this->estadoCivilId], [
            'nombre' => $this->nombre,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->estadoCivilId ? 'Estado Civil actualizado.' : 'Estado Civil creado.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $estadoCivil = EstadoCivil::findOrFail($id);
        $estadoCivil->is_active = !$estadoCivil->is_active;
        $estadoCivil->save();
        session()->flash('message', 'Estado del Estado Civil actualizado.');
    }

    public function delete($id)
    {
        $estadoCivil = EstadoCivil::find($id);
        if ($estadoCivil) {
            // Futuro: Verificar si está en uso antes de desactivar/eliminar
            // if ($estadoCivil->trabajadores()->count() > 0) {
            //     session()->flash('error', 'Este estado civil está en uso y no puede ser desactivado/eliminado.');
            //     return;
            // }
            $estadoCivil->is_active = false; // Solo desactivar
            $estadoCivil->save();
            session()->flash('message', 'Estado Civil desactivado.');
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
        $this->estadoCivilId = null;
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