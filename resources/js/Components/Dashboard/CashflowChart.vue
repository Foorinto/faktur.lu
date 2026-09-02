<script setup>
import { ref, computed, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

const { t } = useTranslations();

const props = defineProps({
    forecast: { type: Object, required: true },
});

const selectedPeriod = ref(props.forecast.period_days || 90);
const forecastData = ref(props.forecast);

// ⚠️ `forecastData` est une COPIE de la prop, prise une fois. Après
// l'enregistrement d'un relevé, Inertia renvoie un tableau de bord à jour,
// mais sans cette veille la courbe garderait l'ancien calcul : l'utilisateur
// saisirait son solde et ne verrait rien bouger — exactement le reproche
// qu'on est en train de corriger.
watch(() => props.forecast, (nouvelle) => {
    forecastData.value = nouvelle;
});
const hoveredDay = ref(null);
const isLoading = ref(false);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-LU', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

const changePeriod = async (days) => {
    selectedPeriod.value = days;
    isLoading.value = true;
    try {
        const response = await axios.get(route('dashboard.cashflow-forecast'), { params: { days } });
        forecastData.value = response.data.data;
    } catch {
        // silently fail, keep current data
    } finally {
        isLoading.value = false;
    }
};

// Timeline filtered to selected period
const visibleTimeline = computed(() => {
    return forecastData.value.timeline.slice(0, selectedPeriod.value + 1);
});

// Chart dimensions (SVG internal units)
const chartWidth = 800;
const chartHeight = 240;

// Y-axis range
const yMax = computed(() => {
    if (!visibleTimeline.value.length) return 1;
    const vals = visibleTimeline.value.flatMap(d => [d.cumulative_income, d.cumulative_expense, d.net_cash]);
    return Math.max(...vals, 1) * 1.1;
});

const yMin = computed(() => {
    if (!visibleTimeline.value.length) return 0;
    const vals = visibleTimeline.value.map(d => d.net_cash);
    const min = Math.min(...vals, 0);
    return min < 0 ? min * 1.1 : 0;
});

const yRange = computed(() => yMax.value - yMin.value || 1);

const toX = (index) => {
    const len = visibleTimeline.value.length;
    return len <= 1 ? chartWidth / 2 : (index / (len - 1)) * chartWidth;
};

const toY = (value) => {
    return chartHeight - ((value - yMin.value) / yRange.value) * chartHeight;
};

// Zero line Y position
const zeroY = computed(() => toY(0));

// SVG polyline points
const incomeLinePoints = computed(() => {
    return visibleTimeline.value.map((d, i) => `${toX(i)},${toY(d.cumulative_income)}`).join(' ');
});

const expenseLinePoints = computed(() => {
    return visibleTimeline.value.map((d, i) => `${toX(i)},${toY(d.cumulative_expense)}`).join(' ');
});

const netCashLinePoints = computed(() => {
    return visibleTimeline.value.map((d, i) => `${toX(i)},${toY(d.net_cash)}`).join(' ');
});

// Area fill (net cash to zero line)
const netCashAreaPoints = computed(() => {
    if (!visibleTimeline.value.length) return '';
    const line = visibleTimeline.value.map((d, i) => `${toX(i)},${toY(d.net_cash)}`);
    const z = zeroY.value;
    return `0,${z} ${line.join(' ')} ${chartWidth},${z}`;
});

// Y-axis ticks (5 ticks)
const yAxisTicks = computed(() => {
    const ticks = [];
    const step = yRange.value / 4;
    for (let i = 0; i <= 4; i++) {
        ticks.push(yMin.value + step * i);
    }
    return ticks;
});

const formatShortCurrency = (val) => {
    const abs = Math.abs(val);
    const sign = val < 0 ? '-' : '';
    if (abs >= 1000) return `${sign}${Math.round(abs / 1000)}k`;
    return `${sign}${Math.round(abs)}`;
};

// X-axis labels (sample ~8 labels)
const xAxisLabels = computed(() => {
    const tl = visibleTimeline.value;
    if (tl.length <= 8) return tl;
    const step = Math.max(1, Math.floor(tl.length / 7));
    return tl.filter((_, i) => i % step === 0 || i === tl.length - 1);
});

// Hover zone width
const hoverZoneWidth = computed(() => {
    return visibleTimeline.value.length > 1 ? chartWidth / visibleTimeline.value.length : chartWidth;
});

// Summary values
const summary = computed(() => forecastData.value.summary);
const releve = computed(() => forecastData.value.opening_balance);

const formatDate = (iso) => {
    if (!iso) return '';
    const [a, m, j] = iso.split('-');
    return `${j}/${m}/${a}`;
};

const saisieOuverte = ref(false);

const formulaire = useForm({
    amount: null,
    balance_date: new Date().toISOString().slice(0, 10),
    label: null,
});

const enregistrerLeSolde = () => {
    formulaire.post(route('bank-balance.store'), {
        preserveScroll: true,
        onSuccess: () => {
            saisieOuverte.value = false;
            formulaire.reset('amount', 'label');
        },
    });
};

const summaryCards = computed(() => {
    const cards = [
        // « Position actuelle » avait été RETIRÉE le 2026-08-31 : la prévision
        // partait de zéro, sans connaître le compte en banque, et le jour 0
        // valait toujours à peu près « moins une journée de dépenses ». Un
        // client l'avait lue comme un découvert de 4 € alors qu'il avait
        // 4 749 € d'encaissements.
        //
        // Elle revient ici, parce qu'elle dit enfin quelque chose de vrai : le
        // relevé saisi, plus les encaissements réels, moins les dépenses
        // réelles. Et elle ne s'affiche QUE dans ce cas — sans relevé, le
        // chiffre serait de nouveau un faux solde.
        { label: t('todays_balance'), value: summary.value.current, show: forecastData.value.has_balance },
        { label: t('days_30'), value: summary.value.days_30, show: summary.value.days_30 !== null },
        { label: t('days_60'), value: summary.value.days_60, show: selectedPeriod.value >= 60 && summary.value.days_60 !== null },
        { label: t('days_90'), value: summary.value.days_90, show: selectedPeriod.value >= 90 && summary.value.days_90 !== null },
    ];
    return cards.filter(c => c.show);
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    {{ t('cashflow_forecast') }}
                </h3>
                <div v-if="forecastData.has_data" class="flex items-center gap-1">
                    <button
                        v-for="period in [30, 60, 90]"
                        :key="period"
                        @click="changePeriod(period)"
                        :disabled="isLoading"
                        :class="[
                            'rounded-lg px-3 py-1 text-xs font-medium transition-colors',
                            selectedPeriod === period
                                ? 'bg-primary-500 text-white'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-gray-800 dark:text-slate-400 dark:hover:bg-gray-700'
                        ]"
                    >
                        {{ period }}j
                    </button>
                </div>
            </div>

            <!--
                Le relevé bancaire : point de départ du calcul, et donc la
                première chose à montrer. Sans lui, tous les chiffres en
                dessous sont des variations et non des soldes.
            -->
            <div class="mt-4">
                <div
                    v-if="releve"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 px-3 py-2 dark:bg-gray-800/50"
                >
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ t('bank_balance_statement_of').replace(':date', formatDate(releve.date)) }}
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ formatCurrency(releve.amount) }}</span>
                    </p>
                    <button
                        type="button"
                        @click="saisieOuverte = !saisieOuverte"
                        class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                    >
                        {{ t('update_bank_balance') }}
                    </button>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-900/40 dark:bg-amber-900/10"
                >
                    <p class="text-xs text-amber-800 dark:text-amber-200">
                        {{ t('cashflow_needs_balance') }}
                    </p>
                    <button
                        type="button"
                        @click="saisieOuverte = !saisieOuverte"
                        class="mt-1.5 text-xs font-semibold text-amber-900 underline hover:no-underline dark:text-amber-100"
                    >
                        {{ t('record_bank_balance') }}
                    </button>
                </div>

                <form
                    v-if="saisieOuverte"
                    @submit.prevent="enregistrerLeSolde"
                    class="mt-2 rounded-xl border border-slate-200 p-3 dark:border-gray-700"
                >
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('bank_balance_help') }}</p>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <label class="block">
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ t('bank_balance_amount') }}</span>
                            <input
                                v-model="formulaire.amount"
                                type="number"
                                step="0.01"
                                required
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </label>
                        <label class="block">
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ t('bank_balance_date') }}</span>
                            <input
                                v-model="formulaire.balance_date"
                                type="date"
                                required
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </label>
                        <label class="block">
                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ t('bank_balance_label_field') }}</span>
                            <input
                                v-model="formulaire.label"
                                type="text"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </label>
                    </div>

                    <div v-if="Object.keys(formulaire.errors).length" class="mt-2 text-xs text-pink-600 dark:text-pink-400">
                        <p v-for="(message, champ) in formulaire.errors" :key="champ">{{ message }}</p>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <button
                            type="submit"
                            :disabled="formulaire.processing"
                            class="rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-600 disabled:opacity-50"
                        >
                            {{ t('save') }}
                        </button>
                        <button
                            type="button"
                            @click="saisieOuverte = false"
                            class="text-xs text-slate-500 hover:underline dark:text-slate-400"
                        >
                            {{ t('cancel') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Le graphique n'a de sens qu'avec des données ; le relevé, lui,
                 doit pouvoir se saisir avant la première facture. -->
            <template v-if="forecastData.has_data">

            <!-- Summary Cards -->
            <div class="mt-4 grid grid-cols-2 gap-3" :class="summaryCards.length >= 4 ? 'sm:grid-cols-4' : 'sm:grid-cols-3'">
                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/50"
                >
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ card.label }}</p>
                    <p
                        :class="[
                            'mt-1 text-lg font-bold',
                            card.value >= 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-pink-600 dark:text-pink-400'
                        ]"
                    >
                        {{ formatCurrency(card.value) }}
                    </p>
                </div>
            </div>

            <!-- Chart -->
            <div class="relative mt-6" :class="{ 'opacity-50': isLoading }">
                <div class="flex">
                    <!-- Y-axis labels -->
                    <div class="flex flex-col-reverse justify-between pr-2 text-right h-60 w-12 sm:w-14 flex-shrink-0">
                        <span
                            v-for="tick in yAxisTicks"
                            :key="tick"
                            class="text-xs text-slate-400 dark:text-slate-500 leading-none"
                        >
                            {{ formatShortCurrency(tick) }}€
                        </span>
                    </div>

                    <!-- SVG Chart -->
                    <div class="flex-1 relative">
                        <svg
                            :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                            class="w-full h-60"
                            preserveAspectRatio="none"
                        >
                            <!-- Grid lines -->
                            <line
                                v-for="tick in yAxisTicks"
                                :key="'grid-' + tick"
                                x1="0"
                                :y1="toY(tick)"
                                :x2="chartWidth"
                                :y2="toY(tick)"
                                class="stroke-slate-100 dark:stroke-gray-800"
                                stroke-width="1"
                            />

                            <!-- Zero line (if negative values) -->
                            <line
                                v-if="yMin < 0"
                                x1="0"
                                :y1="zeroY"
                                :x2="chartWidth"
                                :y2="zeroY"
                                class="stroke-slate-300 dark:stroke-gray-600"
                                stroke-width="1.5"
                                stroke-dasharray="4"
                            />

                            <!-- Net cash area fill -->
                            <polygon
                                v-if="netCashAreaPoints"
                                :points="netCashAreaPoints"
                                class="fill-primary-100/50 dark:fill-primary-900/20"
                            />

                            <!-- Income line (cumulative) -->
                            <polyline
                                v-if="incomeLinePoints"
                                :points="incomeLinePoints"
                                fill="none"
                                class="stroke-emerald-500"
                                stroke-width="2"
                            />

                            <!-- Expense line (cumulative, dashed) -->
                            <polyline
                                v-if="expenseLinePoints"
                                :points="expenseLinePoints"
                                fill="none"
                                class="stroke-pink-400"
                                stroke-width="2"
                                stroke-dasharray="6 3"
                            />

                            <!-- Net cash line -->
                            <polyline
                                v-if="netCashLinePoints"
                                :points="netCashLinePoints"
                                fill="none"
                                class="stroke-primary-500"
                                stroke-width="2.5"
                            />

                            <!-- Hover zones -->
                            <rect
                                v-for="(point, i) in visibleTimeline"
                                :key="'hover-' + i"
                                :x="i * hoverZoneWidth"
                                y="0"
                                :width="hoverZoneWidth"
                                :height="chartHeight"
                                fill="transparent"
                                @mouseenter="hoveredDay = { ...point, index: i }"
                                @mouseleave="hoveredDay = null"
                            />

                            <!-- Hover vertical line -->
                            <line
                                v-if="hoveredDay"
                                :x1="toX(hoveredDay.index)"
                                y1="0"
                                :x2="toX(hoveredDay.index)"
                                :y2="chartHeight"
                                class="stroke-slate-300 dark:stroke-gray-600"
                                stroke-width="1"
                                stroke-dasharray="3"
                            />

                            <!-- Hover dot -->
                            <circle
                                v-if="hoveredDay"
                                :cx="toX(hoveredDay.index)"
                                :cy="toY(hoveredDay.net_cash)"
                                r="4"
                                class="fill-primary-500"
                            />
                        </svg>

                        <!-- Tooltip -->
                        <div
                            v-if="hoveredDay"
                            class="absolute z-10 whitespace-nowrap rounded-lg bg-slate-900 px-3 py-2 text-xs text-white shadow-lg dark:bg-white dark:text-slate-900 pointer-events-none"
                            :style="{
                                left: `${Math.min(Math.max(toX(hoveredDay.index) / chartWidth * 100, 10), 80)}%`,
                                top: '0px',
                                transform: 'translateX(-50%)',
                            }"
                        >
                            <div class="font-medium">{{ hoveredDay.label }}</div>
                            <div class="mt-1 space-y-0.5">
                                <div class="flex justify-between gap-4">
                                    <span class="text-emerald-300 dark:text-emerald-600">{{ t('expected_income') }}</span>
                                    <span>{{ formatCurrency(hoveredDay.cumulative_income) }}</span>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <span class="text-pink-300 dark:text-pink-600">{{ t('expected_expenses') }}</span>
                                    <span>{{ formatCurrency(hoveredDay.cumulative_expense) }}</span>
                                </div>
                                <div class="flex justify-between gap-4 border-t border-slate-700 dark:border-slate-200 pt-1 font-medium">
                                    <span>{{ t('net_cash') }}</span>
                                    <span :class="hoveredDay.net_cash >= 0 ? 'text-emerald-300 dark:text-emerald-600' : 'text-pink-300 dark:text-pink-600'">
                                        {{ formatCurrency(hoveredDay.net_cash) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- X-axis labels -->
                <div class="ml-12 sm:ml-14 flex justify-between mt-2">
                    <span
                        v-for="point in xAxisLabels"
                        :key="point.date"
                        class="text-xs text-slate-400 dark:text-slate-500"
                    >
                        {{ point.label }}
                    </span>
                </div>
            </div>

            <!-- Legend -->
            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-0.5 w-4 bg-emerald-500 rounded"></span>
                    {{ t('expected_income') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-0.5 w-4 border-t-2 border-dashed border-pink-400"></span>
                    {{ t('expected_expenses') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-0.5 w-4 bg-primary-500 rounded"></span>
                    {{ t('net_cash') }}
                </span>
                <span class="ml-auto text-slate-400 dark:text-slate-500">
                    {{ t('monthly_avg_expense') }}: {{ formatCurrency(forecastData.totals.monthly_expense_average) }}
                </span>
            </div>

            <!--
                L'avertissement ne vaut plus que sans relevé. Avec un relevé,
                la courbe montre un vrai solde, et répéter « hors solde
                bancaire » deviendrait faux.
            -->
            <p v-if="!forecastData.has_balance" class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                {{ t('cashflow_excludes_bank_balance') }}
            </p>

            <!--
                L'hypothèse la plus généreuse du calcul, dite à voix haute.
                Une facture en retard est projetée à demain ; sur un compte
                réel dont les onze factures impayées étaient toutes en retard,
                cela supposait 29 907 € rentrant sous vingt-quatre heures, et
                expliquait à soi seul le bond du mois suivant. Le chiffre n'est
                pas faux, mais il ne doit pas se lire comme une promesse.
            -->
            <p v-if="forecastData.overdue_total > 0" class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                {{ t('cashflow_overdue_included').replace(':amount', formatCurrency(forecastData.overdue_total)) }}
            </p>

            </template>
        </div>
    </div>
</template>
