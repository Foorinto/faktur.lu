<?php

namespace Tests\Unit\Services;

use App\Services\DocumentNumberFormatter;
use Carbon\Carbon;
use Tests\TestCase;

class DocumentNumberFormatterTest extends TestCase
{
    public function test_default_legacy_format_produces_expected_number(): void
    {
        $result = DocumentNumberFormatter::format('{prefix}-{year}-{number}', [
            'prefix' => 'F',
            'sequence' => 1,
            'padding' => 3,
            'date' => Carbon::parse('2026-05-11'),
        ]);

        $this->assertSame('F-2026-001', $result);
    }

    public function test_credit_note_default_prefix(): void
    {
        $result = DocumentNumberFormatter::format('{prefix}-{year}-{number}', [
            'prefix' => 'AV',
            'sequence' => 7,
            'padding' => 3,
            'date' => Carbon::parse('2026-05-11'),
        ]);

        $this->assertSame('AV-2026-007', $result);
    }

    public function test_reordered_placeholders_with_client_name(): void
    {
        $result = DocumentNumberFormatter::format('{year}-{number}-{prefix}-{client_name}', [
            'prefix' => 'F',
            'sequence' => 48,
            'padding' => 3,
            'date' => Carbon::parse('2026-05-11'),
            'client_name' => 'Acme Corp',
        ]);

        $this->assertSame('2026-048-F-acme-corp', $result);
    }

    public function test_yy_month_day_format(): void
    {
        $result = DocumentNumberFormatter::format('{yy}{month}{day}-{number}', [
            'prefix' => 'F',
            'sequence' => 1,
            'padding' => 4,
            'date' => Carbon::parse('2026-05-11'),
        ]);

        $this->assertSame('260511-0001', $result);
    }

    public function test_custom_padding(): void
    {
        $result = DocumentNumberFormatter::format('{prefix}-{number}', [
            'prefix' => 'F',
            'sequence' => 7,
            'padding' => 5,
            'date' => Carbon::parse('2026-05-11'),
        ]);

        $this->assertSame('F-00007', $result);
    }

    public function test_empty_client_name_does_not_leave_trailing_separator(): void
    {
        $result = DocumentNumberFormatter::format('{prefix}-{year}-{number}-{client_name}', [
            'prefix' => 'F',
            'sequence' => 1,
            'padding' => 3,
            'date' => Carbon::parse('2026-05-11'),
            'client_name' => '',
        ]);

        $this->assertSame('F-2026-001', $result);
    }

    public function test_empty_client_name_in_middle_does_not_leave_double_separator(): void
    {
        $result = DocumentNumberFormatter::format('{prefix}-{client_name}-{year}-{number}', [
            'prefix' => 'F',
            'sequence' => 1,
            'padding' => 3,
            'date' => Carbon::parse('2026-05-11'),
            'client_name' => '',
        ]);

        $this->assertSame('F-2026-001', $result);
    }

    public function test_client_name_is_slugified_and_truncated(): void
    {
        $slug = DocumentNumberFormatter::slugClientName('Société Anonyme De Construction Du Grand-Duché SARL');

        $this->assertLessThanOrEqual(20, strlen($slug));
        $this->assertSame('societe-anonyme-de-c', $slug);
    }

    public function test_client_name_does_not_end_with_dash_after_truncation(): void
    {
        // 19 chars + trailing space would slug to 'long-name-with-space-' and we
        // expect the dash to be trimmed.
        $slug = DocumentNumberFormatter::slugClientName('long-name-with-space-trail');

        $this->assertSame('long-name-with-space', $slug);
        $this->assertStringEndsNotWith('-', $slug);
    }

    public function test_default_padding_when_not_provided(): void
    {
        $result = DocumentNumberFormatter::format('{number}', [
            'sequence' => 5,
            'date' => Carbon::parse('2026-05-11'),
        ]);

        $this->assertSame('005', $result);
    }

    public function test_validate_legacy_template_passes(): void
    {
        $this->assertSame([], DocumentNumberFormatter::validateTemplate('{prefix}-{year}-{number}'));
    }

    public function test_validate_missing_number_placeholder_fails(): void
    {
        $errors = DocumentNumberFormatter::validateTemplate('{prefix}-{year}');

        $this->assertContains('missing_number_placeholder', $errors);
    }

    public function test_validate_unknown_placeholder_fails(): void
    {
        $errors = DocumentNumberFormatter::validateTemplate('{prefix}-{number}-{unknown}');

        $this->assertContains('unknown_placeholder:unknown', $errors);
    }

    public function test_validate_empty_template_fails(): void
    {
        $errors = DocumentNumberFormatter::validateTemplate('');

        $this->assertContains('empty_template', $errors);
    }

    public function test_validate_too_long_template_fails(): void
    {
        $errors = DocumentNumberFormatter::validateTemplate(str_repeat('{number}', 30));

        $this->assertContains('too_long', $errors);
    }

    public function test_preview_all_returns_invoice_credit_note_and_quote(): void
    {
        $previews = DocumentNumberFormatter::previewAll([
            'number_format' => '{prefix}-{year}-{number}',
            'invoice_prefix' => 'F',
            'credit_note_prefix' => 'AV',
            'quote_prefix' => 'DEV',
            'invoice_starting_number' => null,
            'credit_note_starting_number' => null,
            'quote_starting_number' => null,
            'number_padding' => 3,
        ]);

        $year = Carbon::now()->format('Y');

        $this->assertSame("F-{$year}-001", $previews['invoice']);
        $this->assertSame("AV-{$year}-001", $previews['credit_note']);
        $this->assertSame("DEV-{$year}-001", $previews['quote']);
    }

    public function test_preview_all_respects_starting_numbers(): void
    {
        $previews = DocumentNumberFormatter::previewAll([
            'number_format' => '{prefix}-{year}-{number}',
            'invoice_prefix' => 'F',
            'credit_note_prefix' => 'AV',
            'quote_prefix' => 'DEV',
            'invoice_starting_number' => 48,
            'credit_note_starting_number' => null,
            'quote_starting_number' => 12,
            'number_padding' => 3,
        ]);

        $year = Carbon::now()->format('Y');

        $this->assertSame("F-{$year}-048", $previews['invoice']);
        $this->assertSame("AV-{$year}-001", $previews['credit_note']);
        $this->assertSame("DEV-{$year}-012", $previews['quote']);
    }
}
