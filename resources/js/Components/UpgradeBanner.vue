<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const dismissed = ref(false);

const upgrade = computed(() => page.props.flash?.upgrade_required);

const planAccent = computed(() => {
    if (upgrade.value?.min_plan === 'Pro') {
        return {
            bg: 'bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600',
            border: 'border-violet-700',
            badge: 'bg-white text-violet-700',
        };
    }
    return {
        bg: 'bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500',
        border: 'border-orange-600',
        badge: 'bg-white text-orange-700',
    };
});

// Reset dismiss when a new upgrade message arrives
watch(upgrade, (val) => {
    if (val) dismissed.value = false;
});
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
    >
        <div
            v-if="upgrade && !dismissed"
            :class="['relative shadow-xl border-b-2', planAccent.border, planAccent.bg]"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <!-- Icône cadenas -->
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-white/20 backdrop-blur flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>

                    <!-- Texte -->
                    <div class="flex-1 text-white">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wide opacity-90">Plan requis</span>
                            <span :class="['px-2 py-0.5 rounded-full text-xs font-bold', planAccent.badge]">
                                🔒 {{ upgrade.min_plan }}
                            </span>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold leading-tight">
                            {{ upgrade.feature_label }} — réservé au plan {{ upgrade.min_plan }}
                        </h3>
                        <p class="text-sm sm:text-base opacity-95 mt-1">
                            {{ upgrade.message }}
                        </p>
                    </div>

                    <!-- CTA -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <Link
                            :href="route('subscription.index')"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-white text-slate-900 font-bold text-sm rounded-xl hover:bg-slate-100 shadow-lg transition-all hover:scale-105"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            Voir les plans
                        </Link>
                        <button
                            type="button"
                            class="p-2 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition-colors"
                            aria-label="Fermer"
                            @click="dismissed = true"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
