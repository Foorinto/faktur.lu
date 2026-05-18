<?php

namespace App\Http\Requests\HR;

use App\Models\HR\HrEvent;
use Illuminate\Foundation\Http\FormRequest;

class StoreHrEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', 'string', 'in:'.implode(',', HrEvent::TYPES)],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_all_day' => ['boolean'],
            'location_type' => ['required', 'string', 'in:'.implode(',', HrEvent::LOCATION_TYPES)],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id', 'required_if:location_type,room'],
            'address' => ['nullable', 'string', 'max:500', 'required_if:location_type,address'],
            'video_url' => ['nullable', 'url', 'max:500', 'required_if:location_type,video'],
            'description' => ['nullable', 'string', 'max:5000'],
            'participant_employee_ids' => ['nullable', 'array'],
            'participant_employee_ids.*' => ['integer', 'exists:employees,id'],
            'participant_external_emails' => ['nullable', 'array', 'max:20'],
            'participant_external_emails.*' => ['email', 'max:255'],
        ];
    }
}
