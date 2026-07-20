<?php

namespace App\Exceptions;

/**
 * Marker interface for exceptions whose message is a clear, translated,
 * user-facing domain message (e.g. "This invoice is locked").
 *
 * The global exception handler (bootstrap/app.php) lets these through untouched
 * instead of masking them behind a generic "unexpected error + reference code",
 * so the user keeps seeing the helpful message.
 */
interface UserFacingException
{
}
