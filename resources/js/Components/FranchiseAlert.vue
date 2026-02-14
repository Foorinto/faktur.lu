<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    franchiseAlert: {
        type: Object,
        required: true,
    },
});

// Dismissal state management with localStorage
const DISMISSAL_STORAGE_KEY = 'franchise_alert_dismissed';
const DISMISSAL_DURATION_DAYS = 7;

const isDismissed = ref(false);

onMounted(() => {
    checkDismissalStatus();
});

const checkDismissalStatus = () => {
    const dismissedData = localStorage.getItem(DISMISSAL_STORAGE_KEY);
    if (dismissedData) {
        try {
            const { timestamp, status } = JSON.parse(dismissedData);
            const dismissedAt = new Date(timestamp);
            const now = new Date();
            const daysSinceDismissal = (now - dismissedAt) / (1000 * 60 * 60 * 24);

            // If dismissed for current status and within 7 days
            if (daysSinceDismissal < DISMISSAL_DURATION_DAYS && status === props.franchiseAlert.status) {
                isDismissed.value = true;
            } else {
                // Clear expired dismissal
                localStorage.removeItem(DISMISSAL_STORAGE_KEY);
            }
        } catch (e) {
            localStorage.removeItem(DISMISSAL_STORAGE_KEY);
        }
    }
};

const dismiss = () => {
    isDismissed.value = true;
    localStorage.setItem(DISMISSAL_STORAGE_KEY, JSON.stringify({
        timestamp: new Date().toISOString(),
        status: props.franchiseAlert.status,
    }));
};

const shouldShow = computed(() => {
    return props.franchiseAlert?.show && !isDismissed.value;
});

const isWarning = computed(() => props.franchiseAlert?.status === 'warning');
const isExceeded = computed(() => props.franchiseAlert?.status === 'exceeded');

const alertClasses = computed(() => {
    if (isExceeded.value) {
        return 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
    }
    return 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800';
});

const iconClasses = computed(() => {
    if (isExceeded.value) {
        return 'text-red-600 dark:text-red-400';
    }
    return 'text-amber-600 dark:text-amber-400';
});

const titleClasses = computed(() => {
    if (isExceeded.value) {
        return 'text-red-800 dark:text-red-200';
    }
    return 'text-amber-800 dark:text-amber-200';
});

const textClasses = computed(() => {
    if (isExceeded.value) {
        return 'text-red-700 dark:text-red-300';
    }
    return 'text-amber-700 dark:text-amber-300';
});

const linkClasses = computed(() => {
    if (isExceeded.value) {
        return 'text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100';
    }
    return 'text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100';
});

const dismissButtonClasses = computed(() => {
    if (isExceeded.value) {
        return 'text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-200 focus:ring-red-500';
    }
    return 'text-amber-500 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-200 focus:ring-amber-500';
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-LU', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0);
};

const alertTitle = computed(() => {
    if (isExceeded.value) {
        return 'Seuil de franchise TVA dépassé';
    }
    return 'Attention : Approche du seuil de franchise TVA';
});

const alertMessage = computed(() => {
    if (isExceeded.value) {
        return 'Vous avez dépassé le seuil de franchise TVA. Vous devez vous enregistrer comme assujetti TVA auprès de l\'administration fiscale.';
    }
    return 'Vous approchez du seuil de franchise TVA. Préparez-vous à passer au régime assujetti.';
});

const taxAuthorityMessage = computed(() => {
    const authority = props.franchiseAlert?.tax_authority;
    if (!authority) return '';
    return `Contactez ${authority.name} (${authority.full_name}) pour effectuer les démarches nécessaires.`;
});
</script>

<template>
    <div
        v-if="shouldShow"
        :class="['rounded-2xl border p-4 mb-6', alertClasses]"
        role="alert"
    >
        <div class="flex">
            <!-- Alert Icon -->
            <div class="flex-shrink-0">
                <svg :class="['h-5 w-5', iconClasses]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
            </div>

            <!-- Content -->
            <div class="ml-3 flex-1">
                <h3 :class="['text-sm font-semibold', titleClasses]">
                    {{ alertTitle }}
                </h3>

                <!-- Progress info -->
                <div :class="['mt-2 text-sm', textClasses]">
                    <p>
                        {{ alertMessage }}
                    </p>

                    <!-- Stats -->
                    <div class="mt-3 flex flex-wrap gap-4 text-sm">
                        <div>
                            <span class="font-medium">CA annuel :</span>
                            {{ formatCurrency(franchiseAlert.yearly_revenue) }}
                        </div>
                        <div>
                            <span class="font-medium">Seuil :</span>
                            {{ formatCurrency(franchiseAlert.threshold) }}
                        </div>
                        <div v-if="!isExceeded">
                            <span class="font-medium">Restant :</span>
                            {{ formatCurrency(franchiseAlert.remaining_amount) }}
                        </div>
                        <div>
                            <span class="font-medium">Utilisation :</span>
                            {{ franchiseAlert.percentage_used }}%
                        </div>
                    </div>

                    <!-- Tax authority info -->
                    <p class="mt-3">
                        {{ taxAuthorityMessage }}
                    </p>

                    <!-- Legal reference -->
                    <p class="mt-2 text-xs opacity-75">
                        Référence : {{ franchiseAlert.legal_reference }}
                    </p>

                    <!-- Actions -->
                    <div class="mt-4 flex flex-wrap gap-4">
                        <Link
                            :href="route('settings.business.edit')"
                            :class="['text-sm font-medium flex items-center gap-1 transition-colors', linkClasses]"
                        >
                            Modifier mes paramètres entreprise
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </Link>
                        <a
                            v-if="franchiseAlert.tax_authority?.url"
                            :href="franchiseAlert.tax_authority.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            :class="['text-sm font-medium flex items-center gap-1 transition-colors', linkClasses]"
                        >
                            Site {{ franchiseAlert.tax_authority.name }}
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dismiss button -->
            <div class="ml-auto pl-3">
                <button
                    type="button"
                    @click="dismiss"
                    :class="['rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors', dismissButtonClasses]"
                    title="Masquer cette alerte pendant 7 jours"
                >
                    <span class="sr-only">Masquer</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
