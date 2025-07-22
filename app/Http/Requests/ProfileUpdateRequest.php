<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Reglas existentes para la información del perfil
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // --- INICIO: Nuevas reglas para las preferencias de notificación ---
            'preferencias_notificacion' => ['sometimes', 'required', 'array'],
            'preferencias_notificacion.email' => ['required', 'boolean'],
            'preferencias_notificacion.in-app' => ['required', 'boolean'],
            // --- FIN: Nuevas reglas ---
        ];
    }
}
