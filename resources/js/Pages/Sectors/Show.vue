<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import SectorInterestForm from '@/Components/SectorInterestForm.vue';
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    /** Slug du métier : infirmier, artisan… */
    metier: { type: String, required: true },
    /** Clé du secteur, reliée à User::BUSINESS_SECTORS. */
    sector: { type: String, required: true },
});

/**
 * Le contenu vit dans les fichiers de langue, sous `sector_pages.<metier>`,
 * comme les pages « alternative à ». Rien n'est écrit en dur ici : la page est
 * un gabarit, et en ajouter une se réduit à une entrée de contrôleur plus des
 * traductions.
 */
const cle = (suffixe) => `sector_pages.${props.metier}.${suffixe}`;

/** Points listés — trois arguments, tirés de ce qui EXISTE déjà. */
const arguments_ = ['point_1', 'point_2', 'point_3'];
</script>

<template>
    <SeoHead
        :title="t(cle('page_title'))"
        :description="t(cle('meta_description'))"
    />

    <MarketingLayout>
        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">
                {{ t(cle('h1')) }}
            </h1>

            <p class="mt-4 text-lg text-slate-600 dark:text-slate-300">
                {{ t(cle('intro')) }}
            </p>

            <ul class="mt-8 space-y-4">
                <li v-for="point in arguments_" :key="point" class="flex gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-slate-700 dark:text-slate-300">{{ t(cle(point)) }}</span>
                </li>
            </ul>

            <!-- Dire ce qui n'existe pas encore.
                 Une page métier laisse espérer un outil taillé pour le métier.
                 Le laisser croire ferait venir des gens déçus, et fausserait la
                 mesure qu'on cherche précisément à obtenir. -->
            <p class="mt-8 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
                {{ t(cle('honesty')) }}
            </p>

            <div class="mt-10">
                <SectorInterestForm :sector="sector" />
            </div>

            <p class="mt-8 text-sm text-slate-500 dark:text-slate-400">
                {{ t('sector_pages.cta_generic') }}
                <Link :href="route('register')" class="font-medium text-primary-600 hover:underline">
                    {{ t('sector_pages.cta_link') }}
                </Link>
            </p>
        </div>
    </MarketingLayout>
</template>
