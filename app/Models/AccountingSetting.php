<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSetting extends Model
{
    use BelongsToUser;

    public const DEFAULT_VAT_ACCOUNTS = [
        '17' => '461100',
        '14' => '461400',
        '8' => '461800',
        '3' => '461300',
    ];

    protected $fillable = [
        'user_id',
        'sales_account',
        'vat_collected_accounts',
        'clients_account',
        'bank_account',
        'sales_journal',
        'client_prefix',
    ];

    protected $casts = [
        'vat_collected_accounts' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create accounting settings for a user with sensible defaults.
     */
    public static function getForUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'sales_account' => '702000',
                'vat_collected_accounts' => self::DEFAULT_VAT_ACCOUNTS,
                'clients_account' => '411000',
                'bank_account' => '512000',
                'sales_journal' => 'VE',
                'client_prefix' => 'C',
            ]
        );
    }

    /**
     * Get the VAT account for a given rate.
     */
    public function getVatAccount(float $rate): string
    {
        $accounts = $this->vat_collected_accounts ?? self::DEFAULT_VAT_ACCOUNTS;
        $key = (string) intval($rate);

        return $accounts[$key] ?? $accounts[array_key_first($accounts)] ?? '461100';
    }

    /**
     * Get the accounting ID for a client (custom or auto-generated).
     */
    public function getClientAccountingId(Client $client): string
    {
        if ($client->accounting_id) {
            return $client->accounting_id;
        }

        return $this->client_prefix . str_pad($client->id, 5, '0', STR_PAD_LEFT);
    }
}
