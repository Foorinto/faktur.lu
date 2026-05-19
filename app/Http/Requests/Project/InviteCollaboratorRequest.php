<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class InviteCollaboratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
