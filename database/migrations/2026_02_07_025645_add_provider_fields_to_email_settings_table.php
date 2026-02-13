<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('email_settings', 'provider')) {
            return;
        }

        Schema::table('email_settings', function (Blueprint $table) {
            $table->string('provider')->default('faktur')->after('user_id'); // faktur, smtp, postmark, resend
            $table->text('provider_config')->nullable()->after('provider'); // JSON chiffré
            $table->string('from_address')->nullable()->after('provider_config');
            $table->string('from_name')->nullable()->after('from_address');
            $table->boolean('provider_verified')->default(false)->after('from_name');
            $table->timestamp('last_test_at')->nullable()->after('provider_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'provider_config',
                'from_address',
                'from_name',
                'provider_verified',
                'last_test_at',
            ]);
        });
    }
};
