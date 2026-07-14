<?php

namespace App\Support;

/**
 * Minimal, dependency-free HTML sanitizer for rich-text fields (invoice/quote
 * footer messages authored via the TipTap editor).
 *
 * Whitelists a small set of formatting tags/attributes and strips everything
 * else (scripts, styles, event handlers, javascript: URLs). Rendered raw with
 * {!! !!} in the PDF blade AND shown to accountants via the portal preview, so
 * it must never let through executable or unexpected markup.
 *
 * Backward compatible: plain-text input (no tags — the historical format) is
 * escaped and its line breaks preserved.
 */
class HtmlSanitizer
{
    /** Tags allowed through (produced by TipTap StarterKit + Underline + Link). */
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'hr', 'span', 'a',
    ];

    /** Attributes allowed, per tag. */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
    ];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Historical plain-text footers (no tags) → escape + keep line breaks.
        if (! preg_match('/<[a-z!\/][^>]*>/i', $html)) {
            return nl2br(e($html));
        }

        $dom = new \DOMDocument();
        // Wrap so the fragment parses; suppress libxml warnings on partial HTML.
        $wrapped = '<?xml encoding="UTF-8"><div>' . $html . '</div>';
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (! $root) {
            return '';
        }

        self::sanitizeNode($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }

    private static function sanitizeNode(\DOMNode $node): void
    {
        // Iterate over a snapshot: we mutate the tree while walking it.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            if (! $child instanceof \DOMElement) {
                // Comments, processing instructions, etc. → drop.
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Disallowed tag: drop scripts/styles entirely, otherwise unwrap
                // (keep the text content) so we never lose visible copy.
                if (in_array($tag, ['script', 'style'], true)) {
                    $node->removeChild($child);
                } else {
                    self::sanitizeNode($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
                continue;
            }

            // Allowed tag: strip every attribute not explicitly whitelisted.
            $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if (! in_array($name, $allowed, true)) {
                    $child->removeAttribute($attr->nodeName);
                    continue;
                }
                if ($name === 'href' && ! self::isSafeUrl($attr->nodeValue)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }

            // Harden external links.
            if ($tag === 'a' && $child->getAttribute('href') !== '') {
                $child->setAttribute('rel', 'noopener noreferrer nofollow');
            }

            self::sanitizeNode($child);
        }
    }

    private static function isSafeUrl(?string $url): bool
    {
        $url = trim((string) $url);

        return (bool) preg_match('~^(https?:|mailto:|/|#)~i', $url);
    }
}
