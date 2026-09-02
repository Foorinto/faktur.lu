<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaJsonLd from '@/Components/SchemaJsonLd.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useToolSchemas } from '@/Composables/useToolSchemas';
import { useMarque } from "@/Composables/useMarque";

const { nom: marqueNom } = useMarque();


const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();
const { breadcrumb, faqPage, webApplication, wikidata } = useToolSchemas();

const ibanInput = ref('');

// Mapping des codes banques luxembourgeoises (3 caractères après LUxx) vers nom + BIC
// Source: https://www.luxembourgforfinance.com/financial-centre/banks/
const LU_BANK_CODES = {
    '001': { name: 'BCEE (Banque et Caisse d\'Épargne de l\'État)', bic: 'BCEELULL' },
    '002': { name: 'BIL (Banque Internationale à Luxembourg)', bic: 'BILLLULL' },
    '003': { name: 'BGL BNP Paribas', bic: 'BGLLLULL' },
    '019': { name: 'Banque de Luxembourg', bic: 'BLUXLULL' },
    '020': { name: 'Banque Raiffeisen', bic: 'CCRALULL' },
    '021': { name: 'BCP Banque Commerciale Portugaise', bic: 'BCPLLULL' },
    '030': { name: 'Banque BCP Luxembourg', bic: 'BCPLLULL' },
    '034': { name: 'Banque Havilland', bic: 'BHIDLULL' },
    '094': { name: 'POST Luxembourg', bic: 'CCPLLULL' },
    '040': { name: 'Banque Carnegie Luxembourg', bic: 'CARLLULL' },
    '041': { name: 'ING Luxembourg', bic: 'CELLLULL' },
    '047': { name: 'Quintet Private Bank', bic: 'KBLXLULL' },
    '050': { name: 'Banque de Patrimoines Privés (KBC)', bic: 'KBLPLULL' },
    '051': { name: 'Société Européenne de Banque', bic: 'SEBKLULL' },
    '052': { name: 'Société Générale Bank & Trust', bic: 'SGABLULL' },
    '060': { name: 'Andbank Luxembourg', bic: 'ANDOLULL' },
    '084': { name: 'East-West United Bank', bic: 'EAWULULL' },
    '110': { name: 'Banco Bradesco Europa', bic: 'BBDELULL' },
    '171': { name: 'Banque Öhman', bic: 'OEHMLULL' },
    '170': { name: 'BSI Banque', bic: 'BSILLULL' },
    '193': { name: 'Banque Degroof Petercam', bic: 'PETELULL' },
    '218': { name: 'Banque Privée Edmond de Rothschild', bic: 'PRIBLULL' },
    '300': { name: 'EFG Bank', bic: 'EFGBLULL' },
    '345': { name: 'Mediobanca International', bic: 'MEDILULL' },
    '460': { name: 'BCP - Banco Comercial Português', bic: 'BCPLLULL' },
    '888': { name: 'Banque Hapoalim', bic: 'POALLULL' },
};

// Algorithme MOD-97 pour valider l'IBAN (norme ISO 13616)
function validateIbanMod97(iban) {
    const cleaned = iban.replace(/\s+/g, '').toUpperCase();
    if (!/^[A-Z]{2}\d{2}[A-Z0-9]+$/.test(cleaned)) return false;

    // Déplacer les 4 premiers caractères à la fin
    const rearranged = cleaned.slice(4) + cleaned.slice(0, 4);
    // Convertir lettres en nombres (A=10, B=11, ..., Z=35)
    const numeric = rearranged
        .split('')
        .map(c => /[A-Z]/.test(c) ? (c.charCodeAt(0) - 55).toString() : c)
        .join('');

    // Vérifier mod 97
    let remainder = 0;
    for (const digit of numeric) {
        remainder = (remainder * 10 + parseInt(digit, 10)) % 97;
    }
    return remainder === 1;
}

const result = computed(() => {
    const cleaned = ibanInput.value.replace(/\s+/g, '').toUpperCase();
    if (!cleaned) return null;

    // Format de base
    const formatOk = /^[A-Z]{2}\d{2}[A-Z0-9]{1,30}$/.test(cleaned);
    if (!formatOk) {
        return { status: 'invalid', reason: 'format', cleaned };
    }

    const country = cleaned.slice(0, 2);
    const checkDigits = cleaned.slice(2, 4);
    const bban = cleaned.slice(4);

    // Vérifier longueur par pays (LU = 20 caractères total)
    const expectedLengths = { LU: 20, BE: 16, FR: 27, DE: 22, IT: 27, ES: 24, NL: 18, PT: 25, GB: 22, CH: 21 };
    if (expectedLengths[country] && cleaned.length !== expectedLengths[country]) {
        return { status: 'invalid', reason: 'length', country, expectedLength: expectedLengths[country], cleaned };
    }

    // Algo MOD-97
    if (!validateIbanMod97(cleaned)) {
        return { status: 'invalid', reason: 'check_digit', cleaned };
    }

    // IBAN valide. Si LU, on extrait la banque
    let bankInfo = null;
    if (country === 'LU') {
        const bankCode = bban.slice(0, 3);
        bankInfo = LU_BANK_CODES[bankCode] || { name: t('tools.iban_validator.unknown_bank'), bic: null };
    }

    // Format pour l'affichage (groupes de 4)
    const formatted = cleaned.match(/.{1,4}/g).join(' ');

    return {
        status: 'valid',
        country,
        checkDigits,
        bban,
        formatted,
        bankInfo,
    };
});

const faqs = computed(() => [
    { q: t('tools.iban_validator.faq.q1'), a: t('tools.iban_validator.faq.a1') },
    { q: t('tools.iban_validator.faq.q2'), a: t('tools.iban_validator.faq.a2') },
    { q: t('tools.iban_validator.faq.q3'), a: t('tools.iban_validator.faq.a3') },
]);

const schemas = computed(() => [
    breadcrumb(t('tools.iban_validator.breadcrumb')),
    faqPage(faqs.value),
    webApplication({
        name: t('tools.iban_validator.title'),
        description: t('tools.iban_validator.meta_description'),
        url: localizedRoute('tools.iban_validator'),
        category: 'FinanceApplication',
        about: [wikidata.luxembourg, wikidata.iban],
    }),
]);

const relatedTools = computed(() => [
    { key: 'vat_calculator', route: localizedRoute('tools.vat_calculator') },
    { key: 'invoice_generator', route: localizedRoute('tools.invoice_generator') },
    { key: 'templates', route: localizedRoute('tools.templates') },
]);
</script>

<template>
    <SeoHead
        :title="t('tools.iban_validator.page_title')"
        :description="t('tools.iban_validator.meta_description')"
        canonical-path="/outils/validateur-iban"
        route-name="tools.iban_validator"
    />
    <SchemaJsonLd :schemas="schemas" />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">{{ marqueNom }}</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <Link :href="localizedRoute('tools')" class="text-slate-500 hover:text-slate-700">{{ t('tools.index.breadcrumb') }}</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('tools.iban_validator.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-12">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 leading-tight mb-4">
                        {{ t('tools.iban_validator.title') }}
                    </h1>
                    <p class="text-lg text-slate-600">
                        {{ t('tools.iban_validator.subtitle') }}
                    </p>
                </div>

                <!-- Validator -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 mb-8">
                    <label class="block text-sm font-medium text-slate-700 mb-2">{{ t('tools.iban_validator.input_label') }}</label>
                    <input
                        v-model="ibanInput"
                        type="text"
                        :placeholder="t('tools.iban_validator.input_placeholder')"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 text-lg font-mono uppercase"
                        autocomplete="off"
                        spellcheck="false"
                    />
                    <p class="mt-2 text-xs text-slate-500">{{ t('tools.iban_validator.input_help') }}</p>

                    <!-- Result -->
                    <div v-if="result" class="mt-6">
                        <!-- Valid IBAN -->
                        <div v-if="result.status === 'valid'" class="rounded-xl p-5 bg-emerald-50 border-2 border-emerald-200">
                            <div class="flex items-start gap-3 mb-4">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-slate-900">{{ t('tools.iban_validator.result.valid_title') }}</h3>
                                    <p class="text-sm text-slate-700 mt-1 font-mono">{{ result.formatted }}</p>
                                </div>
                            </div>
                            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                                <div class="bg-white/60 rounded-lg p-3">
                                    <p class="text-xs text-slate-500 uppercase mb-1">{{ t('tools.iban_validator.result.country') }}</p>
                                    <p class="font-semibold text-slate-900">{{ result.country }}</p>
                                </div>
                                <div v-if="result.bankInfo" class="bg-white/60 rounded-lg p-3">
                                    <p class="text-xs text-slate-500 uppercase mb-1">{{ t('tools.iban_validator.result.bank') }}</p>
                                    <p class="font-semibold text-slate-900">{{ result.bankInfo.name }}</p>
                                </div>
                                <div v-if="result.bankInfo?.bic" class="bg-white/60 rounded-lg p-3 sm:col-span-2">
                                    <p class="text-xs text-slate-500 uppercase mb-1">{{ t('tools.iban_validator.result.bic') }}</p>
                                    <p class="font-semibold text-slate-900 font-mono">{{ result.bankInfo.bic }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Invalid IBAN -->
                        <div v-else class="rounded-xl p-5 bg-red-50 border-2 border-red-200">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-slate-900">{{ t('tools.iban_validator.result.invalid_title') }}</h3>
                                    <p class="text-sm text-slate-700 mt-1">
                                        {{ t(`tools.iban_validator.result.reason_${result.reason}`) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-2xl p-6 sm:p-8 text-center mb-12">
                    <h2 class="text-xl font-bold text-slate-900 mb-2">{{ t('tools.iban_validator.cta_title') }}</h2>
                    <p class="text-slate-600 mb-6">{{ t('tools.iban_validator.cta_subtitle') }}</p>
                    <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                        {{ t('tools.iban_validator.cta_button') }}
                    </Link>
                </div>

                <!-- FAQ -->
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-8">{{ t('tools.iban_validator.faq.title') }}</h2>
                    <div class="space-y-4">
                        <details v-for="(faq, i) in faqs" :key="i" class="group bg-white rounded-xl border border-gray-200">
                            <summary class="flex items-center justify-between cursor-pointer px-6 py-4 font-medium text-slate-900">
                                {{ faq.q }}
                                <svg class="w-5 h-5 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="px-6 pb-4 text-sm text-slate-600 leading-relaxed">
                                {{ faq.a }}
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Related tools (internal linking) -->
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">{{ t('tools.related_title') }}</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <Link
                            v-for="tool in relatedTools"
                            :key="tool.key"
                            :href="tool.route"
                            class="block p-4 rounded-xl border border-gray-200 hover:border-primary-500 hover:shadow-sm transition-all bg-white"
                        >
                            <p class="font-semibold text-slate-900 mb-1">{{ t('tools.index.' + tool.key + '.title') }}</p>
                            <p class="text-sm text-slate-600">{{ t('tools.index.' + tool.key + '.description') }}</p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
