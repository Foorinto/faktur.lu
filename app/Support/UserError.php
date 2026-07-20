<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns a technical exception into a clear, user-facing message while logging the
 * full technical detail under a short reference code.
 *
 * The user sees something like:
 *   "Une erreur inattendue s'est produite. Si le problème persiste, contactez le
 *    support en indiquant le code : A7X4-K2P9."
 *
 * and the same code is written to the log next to the real exception, so support
 * can retrace the exact error from a single code the user quotes.
 *
 * Use for UNEXPECTED/technical failures. Domain exceptions whose message is already
 * user-friendly (e.g. "Cette facture est verrouillée") should keep showing their own
 * message and must NOT go through this helper.
 */
class UserError
{
    /**
     * Characters used for reference codes — no ambiguous glyphs (0/O, 1/I/L).
     */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Log $e under a fresh reference code and return a friendly, translated message
     * that embeds that same code.
     *
     * @param  Throwable   $e        The technical exception to hide from the user.
     * @param  string      $context  Short machine tag for the log (e.g. "invoice.finalize").
     * @param  string|null $intro    Optional user-facing intro sentence (already translated),
     *                               e.g. "L'envoi de l'email a échoué." Prepended to the code sentence.
     */
    public static function report(Throwable $e, string $context, ?string $intro = null): string
    {
        $ref = self::generateReference();

        Log::error("[UserError {$ref}] {$context}: {$e->getMessage()}", [
            'ref' => $ref,
            'context' => $context,
            'exception' => $e,
        ]);

        $codeSentence = __('app.error_generic_with_ref', ['ref' => $ref]);

        return $intro ? trim($intro) . ' ' . $codeSentence : $codeSentence;
    }

    /**
     * Generate a short, human-quotable reference like "A7X4-K2P9".
     */
    public static function generateReference(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            if ($i === 4) {
                $out .= '-';
            }
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }
}
