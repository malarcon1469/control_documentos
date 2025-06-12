<?php

namespace App\Livewire;

use App\Models\Comuna;
use App\Models\Region; // Necesitamos importar el modelo Region
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionComunas extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $comunaId;
    public $nombre;
    public $region_id; // Para el select de la región
    public $is_active = true;

    public $regionesDisponibles = []; // Para poblar el select de regiones

    public $search = '';
    public $searchRegion = ''; // Para filtrar por región
    public $perPage = 10;
    public $sortBy = 'comunas.nombre'; // Necesitamos especificar la tabla para evitar ambigüedad
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'searchRegion' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortBy' => ['except' => 'comunas.nombre'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount()
    {
        $this->regionesDisponibles = Region::where('is_active', true)->orderBy('nombre')->get();
    }

    protected function rules()
    {
        return [
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('comunas', 'nombre')
                    ->where(function ($query) {
                        return $query->where('region_id', $this->region_id);
                    })
                    ->ignore($this->comunaId),
            ],
            'region_id' => 'required|exists:regiones,id',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la comuna es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.unique' => 'Esta comuna ya existe en la región seleccionada.',
        'region_id.required' => 'Debe seleccionar una región.',
        'region_id.exists' => 'La región seleccionada no es válida.',
    ];

    public function render()
    {
        $comunas = Comuna::query()
            ->with('region') // Cargar la relación para mostrar el nombre de la región
            ->join('regiones', 'comunas.region_id', '=', 'regiones.id') // Join para poder ordenar/filtrar por nombre de región
            ->select('comunas.*', 'regiones.nombre as nombre_region') // Seleccionar columnas necesarias
            ->when($this->search, function ($query) {
                $query->where('comunas.nombre', 'like', '%' . $this->search . '%');
            })
            ->when($this->searchRegion, function ($query) {
                $query->where('comunas.region_id', $this->searchRegion);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-comunas', [
            'comunas' => $comunas,
            'todasLasRegionesParaFiltro' => $this->regionesDisponibles, // para el filtro de la tabla
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->is_active = true;
        // $this->regionesDisponibles = Region::where('is_active', true)->orderBy('nombre')->get(); // Ya se carga en mount
        $this->openModal();
    }

    public function edit($id)
    {
        $comuna = Comuna::findOrFail($id);
        $this->comunaId = $id;
        $this->nombre = $comuna->nombre;
        $this->region_id = $comuna->region_id;
        $this->is_active = $comuna->is_active;
        // $this->regionesDisponibles = Region::where('is_active', true)->orderBy('nombre')->get(); // Ya se carga en mount
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        Comuna::updateOrCreate(['id' => $this->comunaId], [
            'nombre' => $this->nombre,
            'region_id' => $this->region_id,
            'is_active' => $this->is_active,
        ]);

        session()->flash('message',
            $this->comunaId ? 'Comuna actualizada correctamente.' : 'Comuna creada correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $comuna = Comuna::findOrFail($id);
        $comuna->is_active = !$comuna->is_active;
        $comuna->save();
        session()->flash('message', 'Estado de la comuna actualizado.');
    }

    public function delete($id)
    {
        $comuna = Comuna::find($id);
        if ($comuna) {
             $comuna->is_active = false;
             $comuna->save();
            session()->flash('message', 'Comuna desactivada.');
            // Para eliminar completamente:
            // if ($comuna->contratistas()->exists()) { // Asumiendo que tienes una relación 'contratistas' en el modelo Comuna
            //     session()->flash('error', 'No se puede eliminar la comuna porque está asignada a uno o más contratistas.');
            //     return;
            // }
            // $comuna->delete();
            // session()->flash('message', 'Comuna eliminada.');
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
        $this->comunaId = null;
        $this->nombre = '';
        $this->region_id = null;
        $this->is_active = true;
    }

    public function sortBy($field)
    {
        // Adaptar para ordenar por nombre de región
        if ($field === 'nombre_region') {
            $field = 'regiones.nombre';
        } elseif (!str_contains($field, '.')) { // Si no es un campo con alias (como regiones.nombre), prefijar con comunas.
            $field = 'comunas.' . $field;
        }


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
    public function updatingSearchRegion()
    {
        $this->resetPage();
    }
}