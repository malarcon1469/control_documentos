<?php

namespace App\Livewire;

use App\Models\NivelEducacional; // Asegúrate de importar el modelo
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionNivelesEducacionales extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $nivelId;
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
                Rule::unique('niveles_educacionales', 'nombre')->ignore($this->nivelId),
            ],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre del nivel educacional es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.unique' => 'Este nombre de nivel educacional ya existe.',
    ];

    public function render()
    {
        $niveles = NivelEducacional::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-niveles-educacionales', [
            'niveles' => $niveles,
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
        $nivel = NivelEducacional::findOrFail($id);
        $this->nivelId = $id;
        $this->nombre = $nivel->nombre;
        $this->is_active = $nivel->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        NivelEducacional::updateOrCreate(['id' => $this->nivelId], [
            'nombre' => $this->nombre,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->nivelId ? 'Nivel Educacional actualizado.' : 'Nivel Educacional creado.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $nivel = NivelEducacional::findOrFail($id);
        $nivel->is_active = !$nivel->is_active;
        $nivel->save();
        session()->flash('message', 'Estado del Nivel Educacional actualizado.');
    }

    public function delete($id)
    {
        $nivel = NivelEducacional::find($id);
        if ($nivel) {
            // Futuro: Verificar si está en uso antes de desactivar/eliminar
            $nivel->is_active = false; // Solo desactivar
            $nivel->save();
            session()->flash('message', 'Nivel Educacional desactivado.');
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
        $this->nivelId = null;
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