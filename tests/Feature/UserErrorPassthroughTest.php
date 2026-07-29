<?php

namespace Tests\Feature;

use App\Exceptions\ImmutableInvoiceException;
use App\Support\UserError;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

/**
 * Les messages déjà destinés à l'utilisateur ne doivent pas être masqués derrière
 * un code de référence : les appelants attrapent souvent `\Exception` largement.
 */
class UserErrorPassthroughTest extends TestCase
{
    public function test_validation_message_is_shown_as_is(): void
    {
        $e = ValidationException::withMessages([
            'settings' => 'Les paramètres de l\'entreprise doivent être configurés avant de finaliser une facture.',
        ]);

        $message = UserError::report($e, 'invoice.finalize');

        $this->assertSame(
            'Les paramètres de l\'entreprise doivent être configurés avant de finaliser une facture.',
            $message
        );
        $this->assertStringNotContainsString('code', strtolower($message));
    }

    public function test_domain_exception_message_is_shown_as_is(): void
    {
        // UserFacingException est une interface : on passe par une implémentation réelle.
        $message = UserError::report(new ImmutableInvoiceException('Cette facture est verrouillée.'), 'invoice.update');

        $this->assertSame('Cette facture est verrouillée.', $message);
    }

    public function test_technical_exception_is_still_masked_behind_a_reference(): void
    {
        $message = UserError::report(new RuntimeException('SQLSTATE[HY000]: colonne introuvable'), 'invoice.finalize');

        // Le détail technique ne fuit pas, et un code de référence est fourni.
        $this->assertStringNotContainsString('SQLSTATE', $message);
        $this->assertMatchesRegularExpression('/[A-Z2-9]{4}-[A-Z2-9]{4}/', $message);
    }
}
