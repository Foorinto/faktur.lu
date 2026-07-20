<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DocumentNumberFormatter
{
    public const PLACEHOLDERS = [
        '{prefix}',
        '{year}',
        '{yy}',
        '{month}',
        '{day}',
        '{number}',
        '{client_name}',
    ];

    public const DEFAULT_TEMPLATE = '{prefix}-{year}-{number}';

    /**
     * Apply a number template with the given context.
     *
     *   $context = [
     *       'prefix'      => 'F',
     *       'sequence'    => 48,
     *       'padding'     => 3,
     *       'date'        => CarbonInterface (defaults to now()),
     *       'client_name' => 'Acme Corp' (optional),
     *   ];
     */
    public static function format(string $template, array $context): string
    {
        $date = $context['date'] ?? Carbon::now();
        if (! $date instanceof CarbonInterface) {
            $date = Carbon::parse($date);
        }

        $padding = max(1, (int) ($context['padding'] ?? 3));
        $sequence = (int) ($context['sequence'] ?? 1);

        $clientSlug = '';
        if (! empty($context['client_name'])) {
            $clientSlug = self::slugClientName($context['client_name']);
        }

        $paddedSequence = str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);

        $replacements = [
            '{prefix}' => $context['prefix'] ?? '',
            '{year}' => $date->format('Y'),
            '{yy}' => $date->format('y'),
            '{month}' => $date->format('m'),
            '{day}' => $date->format('d'),
            '{number}' => $paddedSequence,
            '{client_name}' => $clientSlug,
        ];

        $result = self::cleanupEmptySegments(strtr($template, $replacements));

        // Safety net: the sequence MUST appear in the number so two documents can
        // never collide. If the template omitted {number} or used an unknown
        // placeholder (e.g. legacy "{YYYY}"/"{####}"), the rendered string would be
        // constant → duplicate-key crash. Append the sequence to guarantee uniqueness.
        if (! str_contains($result, $paddedSequence)) {
            $result = $result === '' ? $paddedSequence : $result . '-' . $paddedSequence;
        }

        return $result;
    }

    /**
     * Build a preview of all three document numbers for the live UI preview.
     *
     *   $settings = [
     *       'number_format'  => '{prefix}-{year}-{number}',
     *       'invoice_prefix' => 'F',
     *       'credit_note_prefix' => 'AV',
     *       'quote_prefix'   => 'DEV',
     *       'invoice_starting_number'     => null,
     *       'credit_note_starting_number' => null,
     *       'quote_starting_number'       => null,
     *       'number_padding' => 3,
     *   ];
     */
    public static function previewAll(array $settings, ?string $sampleClientName = null): array
    {
        $template = $settings['number_format'] ?? self::DEFAULT_TEMPLATE;
        $padding = $settings['number_padding'] ?? 3;
        $date = Carbon::now();

        $build = function (string $prefix, ?int $startingNumber) use ($template, $padding, $date, $sampleClientName) {
            return self::format($template, [
                'prefix' => $prefix,
                'sequence' => $startingNumber ?? 1,
                'padding' => $padding,
                'date' => $date,
                'client_name' => $sampleClientName,
            ]);
        };

        return [
            'invoice' => $build(
                $settings['invoice_prefix'] ?? 'F',
                $settings['invoice_starting_number'] ?? null,
            ),
            'credit_note' => $build(
                $settings['credit_note_prefix'] ?? 'AV',
                $settings['credit_note_starting_number'] ?? null,
            ),
            'quote' => $build(
                $settings['quote_prefix'] ?? 'DEV',
                $settings['quote_starting_number'] ?? null,
            ),
        ];
    }

    /**
     * Validate that a template only uses known placeholders and stays under a sensible length.
     * Returns an array of error strings; empty if valid.
     */
    public static function validateTemplate(string $template): array
    {
        $errors = [];

        if (trim($template) === '') {
            $errors[] = 'empty_template';
            return $errors;
        }

        if (strlen($template) > 100) {
            $errors[] = 'too_long';
        }

        // Detect unknown {placeholders}
        preg_match_all('/\{([a-z_]+)\}/i', $template, $matches);
        $known = array_map(fn ($p) => trim($p, '{}'), self::PLACEHOLDERS);
        foreach ($matches[1] as $found) {
            if (! in_array($found, $known, true)) {
                $errors[] = 'unknown_placeholder:' . $found;
            }
        }

        // A template MUST contain {number}, otherwise the sequence is not visible
        if (! str_contains($template, '{number}')) {
            $errors[] = 'missing_number_placeholder';
        }

        return $errors;
    }

    /**
     * Slug-ify a client name for inclusion in a document number.
     * Keep it ASCII-safe, lowercase, max 20 chars, no leading/trailing dashes.
     */
    public static function slugClientName(string $name): string
    {
        $slug = Str::slug($name, '-');
        if (strlen($slug) > 20) {
            $slug = rtrim(substr($slug, 0, 20), '-');
        }
        return $slug;
    }

    /**
     * Remove empty segments that appear when an optional placeholder (typically
     * {client_name}) resolves to an empty string. For instance the template
     * "{prefix}-{year}-{number}-{client_name}" with no client should produce
     * "F-2026-001" instead of "F-2026-001-".
     */
    private static function cleanupEmptySegments(string $value): string
    {
        // Collapse repeated separators (e.g. "F--2026", "F//2026")
        $value = preg_replace('/([\-_\/.])\1+/', '$1', $value);
        // Trim leading/trailing separators
        return trim($value, "-_/. \t\n\r\0\x0B");
    }
}
