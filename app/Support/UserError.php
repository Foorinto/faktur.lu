<?php

namespace App\Support;

use App\Exceptions\UserFacingException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
        // Filet : les appelants attrapent souvent `\Exception` largement, ce qui
        // faisait passer ici des exceptions dont le message est DÉJÀ destiné à
        // l'utilisateur (« la facture doit contenir au moins une ligne »).
        // Les masquer derrière un code de référence transforme une erreur
        // actionnable en impasse — on les laisse donc parler d'elles-mêmes.
        if ($userMessage = self::userFacingMessage($e)) {
            return $userMessage;
        }

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
     * Message déjà rédigé pour l'utilisateur, s'il y en a un.
     * Ces exceptions ne sont pas des défaillances techniques : ne pas les
     * journaliser en ERROR, ce sont des saisies incomplètes attendues.
     */
    private static function userFacingMessage(Throwable $e): ?string
    {
        if ($e instanceof ValidationException) {
            $first = collect($e->errors())->flatten()->first();

            return $first ?: $e->getMessage();
        }

        if ($e instanceof UserFacingException) {
            return $e->getMessage();
        }

        return null;
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
