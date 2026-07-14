<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_keeps_basic_formatting(): void
    {
        $out = HtmlSanitizer::clean('<p><strong>Merci</strong> pour votre <em>confiance</em></p>');
        $this->assertStringContainsString('<strong>Merci</strong>', $out);
        $this->assertStringContainsString('<em>confiance</em>', $out);
    }

    public function test_strips_script_tag_and_content(): void
    {
        $out = HtmlSanitizer::clean('<p>Bonjour <script>alert(1)</script>fin</p>');
        $this->assertStringNotContainsString('script', $out);
        $this->assertStringNotContainsString('alert', $out);
        $this->assertStringContainsString('Bonjour', $out);
        $this->assertStringContainsString('fin', $out);
    }

    public function test_removes_event_handler_attributes(): void
    {
        $out = HtmlSanitizer::clean('<p onclick="steal()">coucou</p>');
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringContainsString('coucou', $out);
    }

    public function test_drops_javascript_url_but_keeps_https_link(): void
    {
        $bad = HtmlSanitizer::clean('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript:', $bad);

        $good = HtmlSanitizer::clean('<a href="https://faktur.lu">site</a>');
        $this->assertStringContainsString('href="https://faktur.lu"', $good);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $good);
    }

    public function test_keeps_lists(): void
    {
        $out = HtmlSanitizer::clean('<ul><li>un</li><li>deux</li></ul>');
        $this->assertStringContainsString('<ul>', $out);
        $this->assertStringContainsString('<li>un</li>', $out);
    }

    public function test_plain_text_line_breaks_preserved(): void
    {
        // Historical footers are plain text (no tags) → escape + keep line breaks.
        $out = HtmlSanitizer::clean("Merci pour votre confiance !\nÀ bientôt");
        $this->assertStringContainsString('Merci pour votre confiance', $out);
        $this->assertStringContainsString('<br', $out);
    }

    public function test_plain_text_with_special_chars_is_escaped(): void
    {
        $out = HtmlSanitizer::clean('Devis & facture 5 < 10');
        $this->assertStringContainsString('&amp;', $out);
        $this->assertStringContainsString('&lt;', $out);
    }

    public function test_empty_returns_empty(): void
    {
        $this->assertSame('', HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean('   '));
    }

    public function test_unwraps_disallowed_tag_but_keeps_text(): void
    {
        $out = HtmlSanitizer::clean('<div><table><tr><td>gardé</td></tr></table></div>');
        $this->assertStringNotContainsString('<table', $out);
        $this->assertStringContainsString('gardé', $out);
    }
}
