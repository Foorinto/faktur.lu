<?php

namespace App\Services\Peppol;

class PeppolTransmissionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $documentId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $responseData = [],
    ) {}

    /**
     * Create a successful result.
     */
    public static function success(string $documentId, array $responseData = []): self
    {
        return new self(
            success: true,
            documentId: $documentId,
            responseData: $responseData,
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(string $errorMessage, array $responseData = []): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            responseData: $responseData,
        );
    }

    /**
     * Check if the transmission was successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the transmission failed.
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }
}
