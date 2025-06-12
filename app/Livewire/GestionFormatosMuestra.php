<?php

namespace App\Livewire;

use App\Models\FormatoDocumentoMuestra;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; // Para subida de archivos
use Illuminate\Support\Facades\Storage; // Para manejo de archivos
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GestionFormatosMuestra extends Component
{
    use WithPagination, WithFileUploads;

    public $isOpen = false;
    public $formatoId;
    public $nombre;
    public $descripcion;
    public $archivo_pdf; // Para el nuevo archivo subido
    public $archivo_pdf_existente_ruta; // Para mostrar/mantener el archivo actual
    public $nombre_archivo_original_existente;
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
                Rule::unique('formatos_documento_muestra', 'nombre')->ignore($this->formatoId),
            ],
            'descripcion' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];

        if ($this->archivo_pdf) { // Si se está subiendo un nuevo archivo
            $rules['archivo_pdf'] = 'required|file|mimes:pdf|max:10240'; // PDF, máximo 10MB
        } elseif (!$this->formatoId) { // Si es un nuevo registro y no hay archivo
            $rules['archivo_pdf'] = 'required|file|mimes:pdf|max:10240';
        }

        return $rules;
    }

    protected $messages = [
        'nombre.required' => 'El nombre del formato es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
        'nombre.unique' => 'Este nombre de formato ya existe.',
        'descripcion.max' => 'La descripción no puede exceder los 1000 caracteres.',
        'archivo_pdf.required' => 'El archivo PDF es obligatorio.',
        'archivo_pdf.file' => 'Debe seleccionar un archivo.',
        'archivo_pdf.mimes' => 'El archivo debe ser de tipo PDF.',
        'archivo_pdf.max' => 'El archivo PDF no debe exceder los 10MB.',
    ];

    public function render()
    {
        $formatos = FormatoDocumentoMuestra::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%')
                      ->orWhere('nombre_archivo_original', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.gestion-formatos-muestra', [
            'formatos' => $formatos,
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
        $formato = FormatoDocumentoMuestra::findOrFail($id);
        $this->formatoId = $id;
        $this->nombre = $formato->nombre;
        $this->descripcion = $formato->descripcion;
        $this->archivo_pdf_existente_ruta = $formato->ruta_archivo; // Guardar ruta del archivo actual
        $this->nombre_archivo_original_existente = $formato->nombre_archivo_original;
        $this->is_active = $formato->is_active;
        $this->archivo_pdf = null; // Limpiar el campo de nuevo archivo
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        $data = [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'is_active' => $this->is_active,
        ];

        $currentFormato = $this->formatoId ? FormatoDocumentoMuestra::find($this->formatoId) : null;

        if ($this->archivo_pdf) {
            // Eliminar archivo antiguo si existe y se está actualizando con uno nuevo
            if ($currentFormato && $currentFormato->ruta_archivo) {
                if (Storage::disk('public')->exists($currentFormato->ruta_archivo)) {
                    Storage::disk('public')->delete($currentFormato->ruta_archivo);
                }
            }
            // Guardar nuevo archivo
            $nombreOriginal = $this->archivo_pdf->getClientOriginalName();
            // Crear un nombre de archivo único para evitar colisiones y caracteres extraños
            $nombreArchivoUnico = uniqid('formato_') . '_' . time() . '.' . $this->archivo_pdf->getClientOriginalExtension();
            $rutaArchivo = $this->archivo_pdf->storeAs('formatos_muestra', $nombreArchivoUnico, 'public');

            $data['ruta_archivo'] = $rutaArchivo;
            $data['nombre_archivo_original'] = $nombreOriginal;
        }

        FormatoDocumentoMuestra::updateOrCreate(['id' => $this->formatoId], $data);

        session()->flash('message',
            $this->formatoId ? 'Formato de Muestra actualizado correctamente.' : 'Formato de Muestra creado correctamente.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function toggleActive($id)
    {
        $formato = FormatoDocumentoMuestra::findOrFail($id);
        $formato->is_active = !$formato->is_active;
        $formato->save();
        session()->flash('message', 'Estado del Formato de Muestra actualizado.');
    }

    public function delete($id)
    {
        // Para una eliminación real que dispare el evento 'deleting' del modelo:
        // $formato = FormatoDocumentoMuestra::findOrFail($id);
        // $formato->delete();
        // session()->flash('message', 'Formato de Muestra eliminado y archivo asociado borrado.');

        // Por ahora, solo desactivamos
        $formato = FormatoDocumentoMuestra::find($id);
        if ($formato) {
            $formato->is_active = false;
            $formato->save();
            session()->flash('message', 'Formato de Muestra desactivado.');
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
        $this->formatoId = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->archivo_pdf = null;
        $this->archivo_pdf_existente_ruta = null;
        $this->nombre_archivo_original_existente = null;
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

    // Para limpiar el input de archivo si se cierra el modal
    public function updatedIsOpen($value)
    {
        if (!$value) {
            $this->archivo_pdf = null;
        }
    }
}
