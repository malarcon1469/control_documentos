<?php

namespace App\Livewire;

use App\Models\Etnia; // Asegúrate de importar el modelo Etnia
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionEtnias extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $etniaId;
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
                Rule::unique('etnias', 'nombre')->ignore($this->etniaId),
            ],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la etnia/pueblo originario es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.unique' => 'Este nombre de etnia/pueblo originario ya existe.',
    ];

    public function render()
    {
        $etnias = Etnia::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-etnias', [
            'etnias' => $etnias,
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
        $etnia = Etnia::findOrFail($id);
        $this->etniaId = $id;
        $this->nombre = $etnia->nombre;
        $this->is_active = $etnia->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        Etnia::updateOrCreate(['id' => $this->etniaId], [
            'nombre' => $this->nombre,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->etniaId ? 'Etnia/Pueblo Originario actualizado.' : 'Etnia/Pueblo Originario creado.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $etnia = Etnia::findOrFail($id);
        $etnia->is_active = !$etnia->is_active;
        $etnia->save();
        session()->flash('message', 'Estado de la Etnia/Pueblo Originario actualizado.');
    }

    public function delete($id)
    {
        $etnia = Etnia::find($id);
        if ($etnia) {
            // Futuro: Verificar si está en uso antes de desactivar/eliminar
            $etnia->is_active = false; // Solo desactivar
            $etnia->save();
            session()->flash('message', 'Etnia/Pueblo Originario desactivado.');
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
        $this->etniaId = null;
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