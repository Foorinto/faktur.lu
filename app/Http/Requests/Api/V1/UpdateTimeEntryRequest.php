<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
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
        return [
            'client_id' => 'sometimes|exists:clients,id',
            'project_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'duration' => 'nullable|string|regex:/^\d+:\d{2}$/',
            'duration_seconds' => 'nullable|integer|min:0',
            'hourly_rate' => 'nullable|numeric|min:0|max:9999.9999',
            'started_at' => 'nullable|date',
            'stopped_at' => 'nullable|date|after:started_at',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.exists' => 'Le client sélectionné n\'existe pas.',
            'duration.regex' => 'Le format de la durée doit être HH:MM.',
            'stopped_at.after' => 'La date de fin doit être après la date de début.',
        ];
    }
}
