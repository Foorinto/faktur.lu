<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reset business_settings.default_invoice_footer to NULL for all accounts that
 * still have the hardcoded French fallback string "Merci pour votre confiance !".
 *
 * Before this migration, the Settings/Business.vue form pre-filled this field
 * with that exact French sentence — so the value was persisted as-is, even when
 * the user never customised it. As a result, invoices rendered in PT/DE/EN/LB
 * still showed "Merci pour votre confiance !" because the getter favoured the
 * stored value over the localized translation.
 *
 * Clearing this exact string lets the Invoice::getEffectiveFooterMessageAttribute
 * fallback to __('invoice.thank_you'), which renders the proper translation
 * ("Obrigado pela sua confiança!", "Vielen Dank für Ihr Vertrauen!", etc.).
 *
 * Users who genuinely customised their footer (anything other than the exact
 * French fallback) keep their value untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $stripped = DB::table('business_settings')
            ->where('default_invoice_footer', 'Merci pour votre confiance !')
            ->update(['default_invoice_footer' => null]);

        if ($stripped > 0) {
            \Illuminate\Support\Facades\Log::info(
                'numbering-footer-cleanup: cleared default_invoice_footer for ' . $stripped . ' account(s)'
            );
        }
    }

    public function down(): void
    {
        // Not reversible: we don't know which accounts had the value set
        // intentionally vs. by the form pre-fill.
    }
};
