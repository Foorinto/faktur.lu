<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Validates that a model ID belongs to the authenticated user.
 * Works with models that use the BelongsToUser trait (global scope).
 */
class BelongsToAuthUser implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param class-string<Model> $modelClass The model class to check
     */
    public function __construct(
        protected string $modelClass
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // The global scope on the model will automatically filter by user_id
        if (!$this->modelClass::find($value)) {
            $fail('L\'élément sélectionné n\'existe pas ou ne vous appartient pas.');
        }
    }
}
