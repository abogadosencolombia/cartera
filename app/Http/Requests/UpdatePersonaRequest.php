<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $personaId = $this->route('persona')->id;

        return [
            'nombre_completo' => 'required|string|max:255',
            'tipo_documento' => 'required|string|max:255',
            'numero_documento' => ['required', 'string', 'max:255', Rule::unique(Persona::class)->ignore($personaId)],
            'telefono_fijo' => 'nullable|string|max:255',
            'celular_1' => 'required|string|max:255',
            'celular_2' => 'nullable|string|max:255',
            'correo_1' => 'required|email|max:255',
            'correo_2' => 'nullable|email|max:255',
            'direccion' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'empresa' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'social_links'         => ['nullable','array','max:50'],
            'social_links.*.label' => ['nullable','string','max:50'],
            'social_links.*.url'   => ['nullable','url','max:2048'],
        ];
    }
}
