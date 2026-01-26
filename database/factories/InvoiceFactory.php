<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'number' => null,
            'status' => Invoice::STATUS_DRAFT,
            'type' => Invoice::TYPE_INVOICE,
            'credit_note_for' => null,
            'seller_snapshot' => null,
            'buyer_snapshot' => null,
            'total_ht' => 0,
            'total_vat' => 0,
            'total_ttc' => 0,
            'issued_at' => null,
            'due_at' => null,
            'finalized_at' => null,
            'sent_at' => null,
            'paid_at' => null,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'currency' => 'EUR',
        ];
    }

    /**
     * Indicate that the invoice is finalized.
     */
    public function finalized(): static
    {
        return $this->state(function (array $attributes) {
            $issuedAt = $this->faker->dateTimeBetween('-6 months', 'now');
            $year = $issuedAt->format('Y');
            // Use uuid to guarantee uniqueness
            $uniqueNum = str_pad(abs(crc32(uniqid())) % 1000, 3, '0', STR_PAD_LEFT);
            $number = $year . '-' . $uniqueNum;

            return [
                'status' => Invoice::STATUS_FINALIZED,
                'number' => $number,
                'issued_at' => $issuedAt,
                'due_at' => (clone $issuedAt)->modify('+30 days'),
                'finalized_at' => $issuedAt,
                'seller_snapshot' => [
                    'company_name' => 'Test Company SARL',
                    'legal_name' => 'Test Legal Name',
                    'address_line1' => '123 Test Street',
                    'postal_code' => 'L-1234',
                    'city' => 'Luxembourg',
                    'country' => 'Luxembourg',
                    'matricule' => '12345678901',
                    'vat_number' => 'LU12345678',
                    'vat_regime' => 'normal',
                ],
                'buyer_snapshot' => [
                    'name' => $this->faker->company(),
                    'company_name' => $this->faker->company(),
                    'address_line1' => $this->faker->streetAddress(),
                    'postal_code' => $this->faker->postcode(),
                    'city' => $this->faker->city(),
                    'country' => $this->faker->country(),
                    'vat_number' => 'LU' . $this->faker->numerify('########'),
                ],
                'total_ht' => $this->faker->randomFloat(2, 100, 5000),
                'total_vat' => $this->faker->randomFloat(2, 17, 850),
                'total_ttc' => $this->faker->randomFloat(2, 117, 5850),
            ];
        });
    }

    /**
     * Indicate that the invoice has been sent.
     */
    public function sent(): static
    {
        return $this->finalized()->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_SENT,
            'sent_at' => $this->faker->dateTimeBetween($attributes['issued_at'] ?? '-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the invoice has been paid.
     */
    public function paid(): static
    {
        return $this->sent()->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => $this->faker->dateTimeBetween($attributes['sent_at'] ?? '-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is cancelled.
     */
    public function cancelled(): static
    {
        return $this->finalized()->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_CANCELLED,
        ]);
    }

    /**
     * Indicate that this is a credit note.
     */
    public function creditNote(): static
    {
        return $this->finalized()->state(fn (array $attributes) => [
            'type' => Invoice::TYPE_CREDIT_NOTE,
            'total_ht' => -abs($attributes['total_ht']),
            'total_vat' => -abs($attributes['total_vat']),
            'total_ttc' => -abs($attributes['total_ttc']),
        ]);
    }

    /**
     * Create invoice with items.
     */
    public function withItems(int $count = 3): static
    {
        return $this->has(
            \App\Models\InvoiceItem::factory()->count($count),
            'items'
        );
    }
}
