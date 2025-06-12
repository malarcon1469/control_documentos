<?php

namespace App\Livewire;

use App\Models\Region;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionRegiones extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $regionId;
    public $nombre;
    public $is_active = true; // Aunque no se usará mucho para desactivar, lo mantenemos

    public $search = '';
    public $perPage = 10; // Chile tiene 16 regiones, así que la paginación podría ser opcional aquí
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
                'min:5', // "Aysén" es corta, pero en general son más largas
                'max:255',
                Rule::unique('regiones', 'nombre')->ignore($this->regionId),
            ],
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la región es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 5 caracteres.',
        'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        'nombre.unique' => 'Este nombre de región ya existe.',
    ];

    public function render()
    {
        $regiones = Region::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-regiones', [
            'regiones' => $regiones,
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
        $region = Region::findOrFail($id);
        $this->regionId = $id;
        $this->nombre = $region->nombre;
        $this->is_active = $region->is_active;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        Region::updateOrCreate(['id' => $this->regionId], [
            'nombre' => $this->nombre,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->regionId ? 'Región actualizada correctamente.' : 'Región creada correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $region = Region::findOrFail($id);
        // Prevenir desactivar si tiene comunas activas asociadas (Lógica futura opcional)
        // if (!$region->is_active && $region->comunas()->where('is_active', true)->exists()) {
        //     session()->flash('error', 'No se puede desactivar la región porque tiene comunas activas asociadas.');
        //     return;
        // }
        $region->is_active = !$region->is_active;
        $region->save();
        session()->flash('message', 'Estado de la región actualizado.');
    }

    public function delete($id) // Desactivar en lugar de eliminar
    {
        $region = Region::withCount(['comunas' => function ($query) {
            $query->where('is_active', true);
        }])->find($id);

        if ($region) {
            // if ($region->comunas_count > 0) { // comunas_count es el alias de withCount
            //     session()->flash('error', 'No se puede desactivar la región porque tiene comunas activas asociadas.');
            //     return;
            // }
            $region->is_active = false;
            $region->save();
            session()->flash('message', 'Región desactivada. Asegúrate de desactivar/reasignar comunas asociadas si es necesario.');
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
        $this->regionId = null;
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