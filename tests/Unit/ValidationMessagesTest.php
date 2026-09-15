<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Les règles de validation utilisées par l'application doivent avoir un message
 * dans chaque langue : sinon l'utilisateur voit la clé brute
 * (« validation.gt.numeric ») à la place d'une phrase.
 */
class ValidationMessagesTest extends TestCase
{
    private const LOCALES = ['fr', 'de', 'en', 'lb', 'pt'];

    private const KEYS = [
        'validation.gt.numeric',
        'validation.required_with',
        'validation.current_password',
        'validation.integer',
        'validation.before_or_equal',
        'validation.date',
        'validation.in',
        'validation.boolean',
        'validation.required',
        'validation.numeric',
        'validation.min.numeric',
        'validation.max.numeric',
        'validation.exists',
    ];

    public function test_validation_rules_have_a_message_in_every_locale(): void
    {
        foreach (self::LOCALES as $locale) {
            foreach (self::KEYS as $key) {
                $this->assertNotSame($key, __($key, [], $locale), "Message manquant : {$key} en {$locale}");
            }
        }
    }
}
