<?php

namespace Tests\Unit;

use App\Models\InvoiceItem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LineDiscountTest extends TestCase
{
    /**
     * @return array<string, array{float, float, string, float, float, string, string, string}>
     */
    public static function lineCases(): array
    {
        // qty, price, discount_type, discount_value, vat_rate, expected_ht, expected_vat, expected_ttc
        return [
            'sans remise'              => [2, 100, 'percent', 0,     17, '200.0000', '34.0000', '234.0000'],
            'remise 10%'               => [2, 100, 'percent', 10,    17, '180.0000', '30.6000', '210.6000'],
            'remise 50 EUR'            => [2, 100, 'amount',  50,     17, '150.0000', '25.5000', '175.5000'],
            'montant > brut (cap)'     => [1, 100, 'amount',  150,    17, '0.0000',   '0.0000',  '0.0000'],
            'pourcent > 100 (cap 100)' => [1, 100, 'percent', 150,    17, '0.0000',   '0.0000',  '0.0000'],
            'TVA 3% + remise 8%'       => [10, 10, 'percent', 8,      3,  '92.0000',  '2.7600',  '94.7600'],
            'remise 33,33%'            => [1, 100, 'percent', 33.33,  17, '66.6700',  '11.3339', '78.0039'],
            'remise montant 0 = neutre'=> [3, 30,  'amount',  0,      8,  '90.0000',  '7.2000',  '97.2000'],
        ];
    }

    #[DataProvider('lineCases')]
    public function test_line_amounts_with_discount(
        float $qty,
        float $price,
        string $discountType,
        float $discountValue,
        float $vatRate,
        string $expectedHt,
        string $expectedVat,
        string $expectedTtc,
    ): void {
        $item = new InvoiceItem([
            'quantity' => $qty,
            'unit_price' => $price,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'vat_rate' => $vatRate,
        ]);

        $item->calculateAmounts();

        $this->assertSame($expectedHt, (string) $item->total_ht, 'total_ht');
        $this->assertSame($expectedVat, (string) $item->total_vat, 'total_vat');
        $this->assertSame($expectedTtc, (string) $item->total_ttc, 'total_ttc');
    }

    public function test_discount_never_produces_negative_line(): void
    {
        $item = new InvoiceItem([
            'quantity' => 1,
            'unit_price' => 100,
            'discount_type' => 'amount',
            'discount_value' => 99999,
            'vat_rate' => 17,
        ]);

        $item->calculateAmounts();

        $this->assertSame('0.0000', (string) $item->total_ht);
        $this->assertSame('0.0000', (string) $item->total_ttc);
    }
}
