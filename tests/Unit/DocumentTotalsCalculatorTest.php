<?php

namespace Tests\Unit;

use App\Services\DocumentTotalsCalculator;
use PHPUnit\Framework\TestCase;

class DocumentTotalsCalculatorTest extends TestCase
{
    private function item(float $rate, string $ht): object
    {
        return (object) ['vat_rate' => $rate, 'total_ht' => $ht];
    }

    private function discount(string $type, string $value): object
    {
        return (object) ['type' => $type, 'value' => $value];
    }

    private function calc(): DocumentTotalsCalculator
    {
        return new DocumentTotalsCalculator();
    }

    public function test_no_discount_sums_lines(): void
    {
        $r = $this->calc()->compute([$this->item(17, '200.0000')], []);
        $this->assertSame('200.0000', $r['total_ht']);
        $this->assertSame('34.0000', $r['total_vat']);
        $this->assertSame('234.0000', $r['total_ttc']);
    }

    public function test_percent_global_discount_single_rate(): void
    {
        $r = $this->calc()->compute([$this->item(17, '200.0000')], [$this->discount('percent', '10')]);
        $this->assertSame('180.0000', $r['total_ht']);
        $this->assertSame('30.6000', $r['total_vat']);
        $this->assertSame('210.6000', $r['total_ttc']);
    }

    public function test_amount_global_discount_single_rate(): void
    {
        $r = $this->calc()->compute([$this->item(17, '200.0000')], [$this->discount('amount', '50')]);
        $this->assertSame('150.0000', $r['total_ht']);
        $this->assertSame('25.5000', $r['total_vat']);
    }

    public function test_amount_discount_ventilated_across_rates(): void
    {
        // 100 @ 17 % + 100 @ 3 %, discount 50 € → split 25/25
        $r = $this->calc()->compute(
            [$this->item(17, '100.0000'), $this->item(3, '100.0000')],
            [$this->discount('amount', '50')]
        );
        $this->assertSame('150.0000', $r['total_ht']);
        // 75×17% + 75×3% = 12.75 + 2.25 = 15.00
        $this->assertSame('15.0000', $r['total_vat']);
        $this->assertSame('12.7500', $r['rates']['17.00']['vat']);
        $this->assertSame('2.2500', $r['rates']['3.00']['vat']);
    }

    public function test_percent_discount_ventilated_across_rates(): void
    {
        $r = $this->calc()->compute(
            [$this->item(17, '100.0000'), $this->item(3, '100.0000')],
            [$this->discount('percent', '10')]
        );
        $this->assertSame('180.0000', $r['total_ht']);
        $this->assertSame('18.0000', $r['total_vat']); // 90×17% + 90×3%
    }

    public function test_multiple_discounts_stack(): void
    {
        // 10 % (=20) + 20 € = 40 off 200
        $r = $this->calc()->compute(
            [$this->item(17, '200.0000')],
            [$this->discount('percent', '10'), $this->discount('amount', '20')]
        );
        $this->assertSame('160.0000', $r['total_ht']);
        $this->assertSame('27.2000', $r['total_vat']);
    }

    public function test_discount_capped_at_subtotal(): void
    {
        $r = $this->calc()->compute([$this->item(17, '100.0000')], [$this->discount('amount', '999')]);
        $this->assertSame('0.0000', $r['total_ht']);
        $this->assertSame('0.0000', $r['total_vat']);
    }

    public function test_ventilation_reconciles_exactly_with_awkward_split(): void
    {
        // 100 @ 17 % + 50 @ 3 %, discount 33.33 € — remainder handled on last rate
        $r = $this->calc()->compute(
            [$this->item(17, '100.0000'), $this->item(3, '50.0000')],
            [$this->discount('amount', '33.33')]
        );
        $this->assertSame('116.6700', $r['total_ht']); // 150 - 33.33
        // Σ net bases must equal total_ht exactly
        $sum = bcadd($r['rates']['17.00']['net_base'], $r['rates']['3.00']['net_base'], 4);
        $this->assertSame('116.6700', $sum);
    }
}
