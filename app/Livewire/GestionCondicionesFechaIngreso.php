<?php

namespace App\Livewire;

use App\Models\CondicionFechaIngreso;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionCondicionesFechaIngreso extends Component
{
    use WithPagination;

    public $isOpen = false;
    public $condicionId;
    public $nombre;
    public $descripcion;
    public $fecha_tope_anterior_o_igual;
    public $fecha_tope_posterior_o_igual;
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
        $rules = [
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('condiciones_fecha_ingreso', 'nombre')->ignore($this->condicionId),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'fecha_tope_anterior_o_igual' => 'nullable|date_format:Y-m-d',
            'fecha_tope_posterior_o_igual' => 'nullable|date_format:Y-m-d',
            'is_active' => 'boolean',
        ];

        if ($this->fecha_tope_anterior_o_igual && $this->fecha_tope_posterior_o_igual) {
            $rules['fecha_tope_posterior_o_igual'] .= '|after_or_equal:fecha_tope_anterior_o_igual';
        }
        
        // Validación para que al menos una fecha sea ingresada si se quiere usar la condición por fecha
        // Esta validación puede ser más compleja o manejarse con una nota en la UI
        // Por ahora, se permite crear una condición sin fechas, aunque su utilidad sería limitada
        // para el propósito descrito. Podría ser útil si el 'nombre' por sí solo tuviera un significado.

        return $rules;
    }

    protected $messages = [
        'nombre.required' => 'El nombre de la condición es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.unique' => 'Este nombre de condición ya existe.',
        'fecha_tope_anterior_o_igual.date_format' => 'El formato de fecha no es válido.',
        'fecha_tope_posterior_o_igual.date_format' => 'El formato de fecha no es válido.',
        'fecha_tope_posterior_o_igual.after_or_equal' => 'La "Fecha Posterior o Igual a" debe ser igual o posterior a la "Fecha Anterior o Igual a".',
    ];

    public function render()
    {
        $condiciones = CondicionFechaIngreso::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-condiciones-fecha-ingreso', [
            'condiciones' => $condiciones,
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
        $condicion = CondicionFechaIngreso::findOrFail($id);
        $this->condicionId = $id;
        $this->nombre = $condicion->nombre;
        $this->descripcion = $condicion->descripcion;
        // Formatear fechas para el input type="date" si es necesario, o confiar en el casting del modelo
        $this->fecha_tope_anterior_o_igual = $condicion->fecha_tope_anterior_o_igual ? $condicion->fecha_tope_anterior_o_igual->format('Y-m-d') : null;
        $this->fecha_tope_posterior_o_igual = $condicion->fecha_tope_posterior_o_igual ? $condicion->fecha_tope_posterior_o_igual->format('Y-m-d') : null;
        $this->is_active = $condicion->is_active;
        $this->openModal();
    }

    public function store()
    {
        $validatedData = $this->validate();
        
        // Asegurar que las fechas vacías se guarden como NULL
        $validatedData['fecha_tope_anterior_o_igual'] = $validatedData['fecha_tope_anterior_o_igual'] ?: null;
        $validatedData['fecha_tope_posterior_o_igual'] = $validatedData['fecha_tope_posterior_o_igual'] ?: null;


        CondicionFechaIngreso::updateOrCreate(['id' => $this->condicionId], $validatedData);

        session()->flash('message',
            $this->condicionId ? 'Condición de Fecha de Ingreso actualizada correctamente.' : 'Condición de Fecha de Ingreso creada correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $condicion = CondicionFechaIngreso::findOrFail($id);
        $condicion->is_active = !$condicion->is_active;
        $condicion->save();
        session()->flash('message', 'Estado de la Condición actualizado.');
    }

    public function delete($id)
    {
        $condicion = CondicionFechaIngreso::find($id);
        if ($condicion) {
            // Considerar si esta condición está en uso antes de permitir "desactivar" o "eliminar"
            // Por ahora, solo desactivamos
            $condicion->is_active = false;
            $condicion->save();
            session()->flash('message', 'Condición de Fecha de Ingreso desactivada.');
            // Para eliminar:
            // $condicion->delete();
            // session()->flash('message', 'Condición de Fecha de Ingreso eliminada.');
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
        $this->condicionId = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->fecha_tope_anterior_o_igual = null;
        $this->fecha_tope_posterior_o_igual = null;
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