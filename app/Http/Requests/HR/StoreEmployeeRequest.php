<?php

namespace App\Http\Requests\HR;

use App\Models\HR\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'birth_date', 'contract_end', 'trial_end_date',
            'department_id', 'manager_id', 'salary_gross',
        ];

        foreach ($nullableFields as $field) {
            if ($this->input($field) === '' || $this->input($field) === null) {
                $this->merge([$field => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email_pro' => ['nullable', 'email', 'max:255'],
            'email_perso' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_perso' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:5'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:5'],
            'contract_type' => ['required', Rule::in(Employee::CONTRACT_TYPES)],
            'contract_start' => ['required', 'date'],
            'contract_end' => ['nullable', 'date', 'after_or_equal:contract_start'],
            'trial_end_date' => ['nullable', 'date', 'after_or_equal:contract_start'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'work_location' => ['nullable', 'string', 'max:100'],
            'salary_gross' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'benefits' => ['nullable', 'array'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['nullable', 'string', 'max:100'],
            'emergency_contact.phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact.relation' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::in(Employee::STATUSES)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
