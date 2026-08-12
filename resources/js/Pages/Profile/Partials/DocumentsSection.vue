<script setup>
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

/**
 * Documents contractuels du compte.
 *
 * Le DPA public est un modèle vierge. Celui-ci est l'exemplaire du client :
 * ses coordonnées, et la trace de son acceptation. C'est la pièce qu'on lui
 * demandera le jour d'un audit, et il doit pouvoir la sortir seul.
 */
const props = defineProps({
    dpa: { type: Object, required: true },
});

const dateAcceptation = computed(() => {
    if (!props.dpa.accepted_at) return null;

    return new Date(props.dpa.accepted_at).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
});

// Un compte antérieur à la mise en place de la trace n'a pas de date. Le taire
// laisserait croire à un document non accepté ; le dire permet de comprendre.
const traceManquante = computed(() => !props.dpa.accepted_at);

// Acceptation par renvoi : l'utilisateur a coché les conditions générales, dont
// l'article 10.5 intègre le DPA. La distinction se lit sur le document, elle
// doit se lire ici aussi.
const parRenvoi = computed(() => props.dpa.method === 'terms');
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                {{ t('profile_documents_title') }}
            </h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ t('profile_documents_subtitle') }}
            </p>
        </header>

        <div
            class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700"
        >
            <div class="min-w-0">
                <p class="font-medium text-slate-900 dark:text-white">
                    {{ t('profile_documents_dpa_name') }}
                </p>
                <p v-if="dateAcceptation" class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                    {{
                        parRenvoi
                            ? t('profile_documents_dpa_accepted_via_terms', { date: dateAcceptation, version: dpa.version })
                            : t('profile_documents_dpa_accepted', { date: dateAcceptation, version: dpa.version })
                    }}
                </p>
                <p v-else class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                    {{ t('profile_documents_dpa_no_trace') }}
                </p>

                <p
                    v-if="!traceManquante && dpa.version !== dpa.current_version"
                    class="mt-1 text-xs text-amber-700 dark:text-amber-500"
                >
                    {{ t('profile_documents_dpa_outdated', { version: dpa.current_version }) }}
                </p>
            </div>

            <!-- Lien HTML ordinaire : la réponse est un fichier, pas une page.
                 Un Link Inertia attendrait une réponse Inertia et le clic
                 resterait sans effet. -->
            <a
                :href="route('profile.dpa')"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-700"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                </svg>
                {{ t('profile_documents_download') }}
            </a>
        </div>
    </section>
</template>
