<script setup>
import MarketingLayout from "@/Layouts/MarketingLayout.vue";
import SeoHead from "@/Components/SeoHead.vue";
import SectorInterestForm from "@/Components/SectorInterestForm.vue";
import { Link } from "@inertiajs/vue3";
import { useTranslations } from "@/Composables/useTranslations";

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
const arguments_ = ["point_1", "point_2", "point_3"];

/** Trois particularités luxembourgeoises propres au métier. */
const contexte = ["context_1", "context_2", "context_3"];
</script>

<template>
    <SeoHead
        :title="t(cle('page_title'))"
        :description="t(cle('meta_description'))"
    />

    <MarketingLayout>
        <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
            <!-- Le but de la page, annoncé avant toute promesse produit.
                 Sans ce bandeau, le lecteur arrivait sur un h1 de page
                 produit, trois arguments et un formulaire, et devait
                 réconcilier lui-même les deux moitiés. -->
            <p
                class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300"
            >
                <span
                    class="h-1.5 w-1.5 rounded-full bg-primary-500"
                    aria-hidden="true"
                ></span>
                {{
                    t("sector_pages.kicker", { metier: t(cle("kicker_label")) })
                }}
            </p>

            <h1
                class="mt-4 text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl"
            >
                {{ t(cle("h1")) }}
            </h1>

            <!-- La raison d'être, en clair et en premier. Elle remplace
                 l'accroche produit, qui redescend en tête de l'argumentaire. -->
            <div class="mt-6 border-l-4 border-primary-500 pl-5">
                <h2
                    class="text-lg font-semibold text-slate-900 dark:text-white"
                >
                    {{ t("sector_pages.purpose_title") }}
                </h2>
                <p class="mt-2 text-slate-700 dark:text-slate-300">
                    {{ t("sector_pages.purpose") }}
                </p>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ t("sector_pages.purpose_effort") }}
                </p>
            </div>

            <!-- Le formulaire est la raison d'être de la page : il vient donc
                 avant les arguments, pas après. Un visiteur qui repart sans
                 avoir lu la liste des fonctionnalités n'a rien coûté ; un
                 visiteur qui repart sans avoir répondu, si. -->
            <div class="mt-8">
                <SectorInterestForm :sector="sector" />
            </div>

            <!-- Ce qui n'existe pas encore, dit haut et tôt.
                 Placé avant l'argumentaire plutôt qu'en bas de page : une
                 page métier laisse espérer un outil taillé pour le métier, et
                 laisser lire trois arguments avant de détromper serait une
                 façon de tromper quand même. -->
            <div
                class="mt-12 rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-900/20"
            >
                <h2
                    class="text-base font-semibold text-amber-900 dark:text-amber-200"
                >
                    {{ t("sector_pages.not_yet") }}
                </h2>
                <p class="mt-2 text-sm text-amber-900 dark:text-amber-200">
                    {{ t(cle("honesty")) }}
                </p>
            </div>

            <h2
                class="mt-12 text-xl font-semibold text-slate-900 dark:text-white"
            >
                {{ t("sector_pages.what_exists") }}
            </h2>

            <p class="mt-3 text-slate-600 dark:text-slate-300">
                {{ t(cle("intro")) }}
            </p>

            <ul class="mt-4 space-y-4">
                <li v-for="point in arguments_" :key="point" class="flex gap-3">
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0 text-primary-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span class="text-slate-700 dark:text-slate-300">{{
                        t(cle(point))
                    }}</span>
                </li>
            </ul>

            <!-- La substance propre au métier. C'est elle qui donne à la page
                 une raison d'exister pour un moteur de recherche : sans elle,
                 cinq pages sectorielles ne sont que cinq variantes du même
                 argumentaire, et Google les traite comme telles. -->
            <h2
                class="mt-12 text-xl font-semibold text-slate-900 dark:text-white"
            >
                {{ t("sector_pages.context_title") }}
            </h2>

            <dl class="mt-4 space-y-5">
                <div
                    v-for="(point, index) in contexte"
                    :key="point"
                    class="flex gap-4"
                >
                    <dt
                        class="shrink-0 text-sm font-semibold tabular-nums text-primary-600 dark:text-primary-400"
                    >
                        {{ index + 1 }}
                    </dt>
                    <dd class="text-slate-700 dark:text-slate-300">
                        {{ t(cle(point)) }}
                    </dd>
                </div>
            </dl>

            <p class="mt-10 text-sm text-slate-500 dark:text-slate-400">
                {{ t("sector_pages.cta_generic") }}
                <Link
                    :href="route('register')"
                    class="font-medium text-primary-600 hover:underline"
                >
                    {{ t("sector_pages.cta_link") }}
                </Link>
            </p>
        </div>
    </MarketingLayout>
</template>
