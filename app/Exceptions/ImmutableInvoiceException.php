<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImmutableInvoiceException extends Exception implements UserFacingException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('app.error_invoice_locked'));
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
