<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Les IBAN sont chiffrés au repos (RGPD) mais lisibles de façon transparente.
 */
class IbanEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_iban_is_encrypted_at_rest_and_transparent_on_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $iban = 'LU280019400644750000';
        $settings = BusinessSettings::factory()->create(['iban' => $iban]);

        // Lecture via le modèle : IBAN en clair (déchiffrement transparent).
        $this->assertSame($iban, $settings->fresh()->iban);
        // Le snapshot (factures finalisées) contient l'IBAN en clair, comme sur la facture.
        $this->assertSame($iban, $settings->fresh()->toSnapshot()['iban']);

        // En base : la valeur brute est chiffrée (≠ clair) et se déchiffre vers l'original.
        $raw = DB::table('business_settings')->where('id', $settings->id)->value('iban');
        $this->assertNotSame($iban, $raw);
        $this->assertStringNotContainsString($iban, $raw);
        $this->assertSame($iban, Crypt::decryptString($raw));
    }
}
