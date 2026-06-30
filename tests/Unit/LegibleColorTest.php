<?php

namespace Tests\Unit;

use App\Models\BusinessSettings;
use PHPUnit\Framework\TestCase;

class LegibleColorTest extends TestCase
{
    public function test_white_is_darkened_to_a_legible_grey(): void
    {
        $this->assertSame('#666666', BusinessSettings::legibleColor('#ffffff'));
    }

    public function test_near_white_and_pastels_are_darkened(): void
    {
        foreach (['#f9fafb', '#ffe680', '#cce5ff'] as $light) {
            $this->assertNotSame(
                strtolower(ltrim($light, '#')),
                ltrim(BusinessSettings::legibleColor($light), '#'),
                "{$light} should have been darkened",
            );
        }
    }

    public function test_vivid_legible_colors_are_left_untouched(): void
    {
        foreach (['#7c3aed', '#3b82f6', '#ef4444', '#000000'] as $vivid) {
            $this->assertSame($vivid, BusinessSettings::legibleColor($vivid));
        }
    }

    public function test_color_without_hash_is_handled(): void
    {
        $this->assertSame('#7c3aed', BusinessSettings::legibleColor('7c3aed'));
    }

    public function test_invalid_input_is_returned_unchanged(): void
    {
        $this->assertSame('not-a-color', BusinessSettings::legibleColor('not-a-color'));
    }

    public function test_output_is_always_valid_hex_for_valid_input(): void
    {
        foreach (['#ffffff', '#ffe680', '#123456', '#abcdef'] as $hex) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', BusinessSettings::legibleColor($hex));
        }
    }
}
