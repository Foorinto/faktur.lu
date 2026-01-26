<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImmutableInvoiceException extends Exception
{
    public function __construct(string $message = 'Cette facture est verrouillée et ne peut pas être modifiée.')
    {
        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'immutable_invoice',
        ], 403);
    }
}
