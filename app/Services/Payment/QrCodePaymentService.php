<?php

namespace App\Services\Payment;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;

class QrCodePaymentService
{
    /**
     * Generate an EPC QR code as a base64 data URI.
     *
     * EPC QR standard (EPC069-12) encodes SEPA Credit Transfer data.
     * Compatible with Payconiq, Digicash, and all EU banking apps.
     *
     * @return string|null Base64 data URI (data:image/png;base64,...) or null if data is insufficient
     */
    public function generateEpcQrCode(
        string $beneficiaryName,
        string $iban,
        string $bic,
        float $amount,
        string $reference,
    ): ?string {
        if (empty($iban) || $amount <= 0) {
            return null;
        }

        $payload = $this->buildEpcPayload($beneficiaryName, $iban, $bic, $amount, $reference);

        if ($payload === null) {
            return null;
        }

        try {
            $qrCode = new QrCode(
                data: $payload,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 300,
                margin: 10,
            );

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            return 'data:image/png;base64,' . base64_encode($result->getString());
        } catch (\Exception $e) {
            Log::error('[QrCodePayment] Failed to generate EPC QR code', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build the EPC QR payload string.
     *
     * Format (EPC069-12):
     * Line 1: Service Tag (BCD)
     * Line 2: Version (002)
     * Line 3: Character set (1 = UTF-8)
     * Line 4: Identification (SCT)
     * Line 5: BIC of beneficiary
     * Line 6: Name of beneficiary (max 70 chars)
     * Line 7: IBAN of beneficiary
     * Line 8: Amount (EUR format)
     * Line 9: Purpose (empty)
     * Line 10: Structured reference (empty)
     * Line 11: Unstructured reference (max 140 chars)
     */
    private function buildEpcPayload(
        string $beneficiaryName,
        string $iban,
        string $bic,
        float $amount,
        string $reference,
    ): ?string {
        // Sanitize IBAN: remove spaces
        $iban = strtoupper(preg_replace('/\s+/', '', $iban));

        // Sanitize BIC: remove spaces
        $bic = strtoupper(preg_replace('/\s+/', '', $bic));

        // Truncate beneficiary name to 70 chars
        $beneficiaryName = mb_substr(trim($beneficiaryName), 0, 70);

        if (empty($beneficiaryName)) {
            return null;
        }

        // Cap amount at EPC max (999999999.99)
        $amount = min($amount, 999999999.99);

        // Format amount: EUR + amount with 2 decimals
        $formattedAmount = 'EUR' . number_format($amount, 2, '.', '');

        // Truncate reference to 140 chars
        $reference = mb_substr(trim($reference), 0, 140);

        $lines = [
            'BCD',                  // Service Tag
            '002',                  // Version
            '1',                    // Character set (UTF-8)
            'SCT',                  // Identification
            $bic,                   // BIC
            $beneficiaryName,       // Beneficiary name
            $iban,                  // IBAN
            $formattedAmount,       // Amount
            '',                     // Purpose (empty)
            '',                     // Structured reference (empty)
            $reference,             // Unstructured reference
        ];

        return implode("\n", $lines);
    }
}
