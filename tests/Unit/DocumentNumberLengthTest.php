<?php

namespace Tests\Unit;

use App\Services\DocumentNumberFormatter;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Guards the invoice/quote identifier column width against the number formatter.
 *
 * Regression context: DocumentNumberFormatter allows custom templates that expand
 * well past the old VARCHAR(20) columns, which caused SQLSTATE[22001] (1406) on
 * finalize in production. Columns were widened to VARCHAR(191); these tests assert
 * a realistic rich format overflows 20 chars but stays within 191.
 */
class DocumentNumberLengthTest extends TestCase
{
    public function test_rich_custom_format_exceeds_old_20_char_column(): void
    {
        $number = DocumentNumberFormatter::format(
            '{prefix}-{year}-{month}-{client_name}-{number}',
            [
                'prefix' => 'F',
                'sequence' => 1,
                'padding' => 3,
                'date' => Carbon::parse('2026-07-18'),
                'client_name' => 'GAMS asbl',
            ],
        );

        // e.g. "F-2026-07-gams-asbl-001" — longer than the old VARCHAR(20).
        $this->assertGreaterThan(20, strlen($number));
        $this->assertStringContainsString('gams-asbl', $number);
    }

    public function test_worst_case_output_fits_new_191_column(): void
    {
        // Template is capped at 100 chars (validateTemplate) and {client_name} slug at 20.
        // Even a template packed with {client_name} placeholders stays well under 191.
        $packed = str_repeat('{client_name}-', 6) . '{number}'; // 6 * up-to-20 + literals
        $number = DocumentNumberFormatter::format($packed, [
            'prefix' => 'F',
            'sequence' => 999999,
            'padding' => 6,
            'date' => Carbon::parse('2026-07-18'),
            'client_name' => 'Association Nationale Des Freelances Du Luxembourg',
        ]);

        $this->assertLessThanOrEqual(191, strlen($number));
    }
}
