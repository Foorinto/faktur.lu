<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chiffre les IBAN au repos (RGPD).
 *
 * Colonnes concernées :
 *  - business_settings.iban (IBAN de l'utilisateur, pour recevoir les virements)
 *  - employees.bank_iban    (IBAN des salariés, module RH)
 *
 * Le BIC reste en clair : c'est un identifiant de banque public, pas une donnée personnelle.
 *
 * ⚠️ Le chiffrement s'appuie sur APP_KEY. Si APP_KEY est perdue/régénérée,
 *    les IBAN deviennent illisibles. Ne jamais régénérer APP_KEY en production.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Élargir les colonnes : une valeur chiffrée dépasse largement varchar(34).
        Schema::table('business_settings', function (Blueprint $table) {
            $table->text('iban')->change();
        });

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->text('bank_iban')->nullable()->change();
            });
        }

        // 2) Chiffrer les valeurs existantes (idempotent : on saute ce qui est déjà chiffré).
        DB::table('business_settings')->select('id', 'iban')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $enc = $this->encryptIfNeeded($row->iban);
                if ($enc !== $row->iban) {
                    DB::table('business_settings')->where('id', $row->id)->update(['iban' => $enc]);
                }
            }
        });

        if (Schema::hasTable('employees')) {
            DB::table('employees')->select('id', 'bank_iban')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $enc = $this->encryptIfNeeded($row->bank_iban);
                    if ($enc !== $row->bank_iban) {
                        DB::table('employees')->where('id', $row->id)->update(['bank_iban' => $enc]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Déchiffrer avant de rétrécir les colonnes.
        DB::table('business_settings')->select('id', 'iban')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('business_settings')->where('id', $row->id)
                    ->update(['iban' => $this->decryptIfNeeded($row->iban)]);
            }
        });

        if (Schema::hasTable('employees')) {
            DB::table('employees')->select('id', 'bank_iban')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('employees')->where('id', $row->id)
                        ->update(['bank_iban' => $this->decryptIfNeeded($row->bank_iban)]);
                }
            });
        }

        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('iban', 34)->change();
        });

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('bank_iban', 34)->nullable()->change();
            });
        }
    }

    private function encryptIfNeeded(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            Crypt::decryptString($value);

            return $value; // déjà chiffré → on ne touche pas
        } catch (DecryptException) {
            return Crypt::encryptString($value);
        }
    }

    private function decryptIfNeeded(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value; // déjà en clair
        }
    }
};
