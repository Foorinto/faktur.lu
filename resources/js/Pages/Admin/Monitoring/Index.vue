<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    metrics: Object,
    timeSeries: Array,
    thresholds: Object,
    period: String,
});

const selectedPeriod = ref(props.period);
const isRefreshing = ref(false);
let refreshInterval = null;

const periods = [
    { value: '1h', label: '1 heure' },
    { value: '24h', label: '24 heures' },
    { value: '7d', label: '7 jours' },
    { value: '30d', label: '30 jours' },
];

const changePeriod = (period) => {
    selectedPeriod.value = period;
    router.get(route('admin.monitoring'), { period }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const refresh = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['metrics', 'timeSeries'],
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

const getAlertColor = (level) => {
    switch (level) {
        case 'critical':
            return 'bg-red-500';
        case 'warning':
            return 'bg-yellow-500';
        default:
            return 'bg-green-500';
    }
};

const getAlertTextColor = (level) => {
    switch (level) {
        case 'critical':
            return 'text-red-400';
        case 'warning':
            return 'text-yellow-400';
        default:
            return 'text-green-400';
    }
};

const formatMs = (ms) => {
    if (ms >= 1000) {
        return `${(ms / 1000).toFixed(2)}s`;
    }
    return `${Math.round(ms)}ms`;
};

const formatBytes = (mb) => {
    if (mb >= 1024) {
        return `${(mb / 1024).toFixed(2)} GB`;
    }
    return `${mb} MB`;
};

const overallHealth = computed(() => {
    const alerts = props.metrics?.alerts || {};
    const levels = Object.values(alerts);
    if (levels.includes('critical')) return 'critical';
    if (levels.includes('warning')) return 'warning';
    return 'ok';
});

const healthLabel = computed(() => {
    switch (overallHealth.value) {
        case 'critical':
            return 'Critique';
        case 'warning':
            return 'Attention';
        default:
            return 'OK';
    }
});

onMounted(() => {
    refreshInterval = setInterval(refresh, 30000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <Head title="Monitoring" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-white">Monitoring</h1>
                <div class="flex items-center gap-3">
                    <!-- Period selector -->
                    <div class="flex rounded-lg bg-slate-700 p-1">
                        <button
                            v-for="p in periods"
                            :key="p.value"
                            :class="[
                                'rounded-md px-3 py-1 text-sm font-medium transition-colors',
                                selectedPeriod === p.value
                                    ? 'bg-purple-600 text-white'
                                    : 'text-slate-400 hover:text-white',
                            ]"
                            @click="changePeriod(p.value)"
                        >
                            {{ p.label }}
                        </button>
                    </div>
                    <SecondaryButton
                        :disabled="isRefreshing"
                        @click="refresh"
                    >
                        <svg v-if="isRefreshing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isRefreshing ? 'Actualisation...' : 'Actualiser' }}
                    </SecondaryButton>
                </div>
            </div>
        </template>

        <!-- Health Overview -->
        <div class="mb-6 rounded-xl bg-slate-800 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div :class="['h-4 w-4 rounded-full', getAlertColor(overallHealth)]" />
                    <div>
                        <h2 class="text-lg font-semibold text-white">État de santé global</h2>
                        <p :class="['text-sm', getAlertTextColor(overallHealth)]">{{ healthLabel }}</p>
                    </div>
                </div>
                <div class="text-right text-sm text-slate-400">
                    Période : {{ periods.find(p => p.value === selectedPeriod)?.label }}
                    <br>
                    Auto-refresh : 30s
                </div>
            </div>

            <!-- Alert indicators -->
            <div class="mt-4 flex flex-wrap gap-2">
                <div
                    v-for="(level, key) in metrics.alerts"
                    :key="key"
                    :class="[
                        'flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium',
                        level === 'critical' ? 'bg-red-500/20 text-red-400' :
                        level === 'warning' ? 'bg-yellow-500/20 text-yellow-400' :
                        'bg-green-500/20 text-green-400',
                    ]"
                >
                    <div :class="['h-2 w-2 rounded-full', getAlertColor(level)]" />
                    {{ key.replace('_', ' ') }}
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
            <!-- Request Performance -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Performance Requêtes</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Requêtes totales</dt>
                        <dd class="text-white font-mono">{{ metrics.requests?.total?.toLocaleString() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Req/min</dt>
                        <dd class="text-white font-mono">{{ metrics.requests?.requests_per_minute }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Temps moyen</dt>
                        <dd :class="[
                            'font-mono',
                            metrics.alerts?.response_time === 'critical' ? 'text-red-400' :
                            metrics.alerts?.response_time === 'warning' ? 'text-yellow-400' : 'text-white'
                        ]">
                            {{ formatMs(metrics.requests?.avg_response_time) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">P95</dt>
                        <dd class="text-white font-mono">{{ formatMs(metrics.requests?.p95_response_time) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">P99</dt>
                        <dd class="text-white font-mono">{{ formatMs(metrics.requests?.p99_response_time) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Erreurs (5xx)</dt>
                        <dd :class="[
                            'font-mono',
                            metrics.alerts?.error_rate === 'critical' ? 'text-red-400' :
                            metrics.alerts?.error_rate === 'warning' ? 'text-yellow-400' : 'text-green-400'
                        ]">
                            {{ metrics.requests?.error_count }} ({{ metrics.requests?.error_rate }}%)
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Database Stats -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Base de données</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Requêtes/page (moy)</dt>
                        <dd :class="[
                            'font-mono',
                            metrics.alerts?.query_count === 'critical' ? 'text-red-400' :
                            metrics.alerts?.query_count === 'warning' ? 'text-yellow-400' : 'text-white'
                        ]">
                            {{ metrics.database?.avg_queries_per_request }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Requêtes/page (max)</dt>
                        <dd class="text-white font-mono">{{ metrics.database?.max_queries_per_request }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Temps SQL (moy)</dt>
                        <dd class="text-white font-mono">{{ formatMs(metrics.database?.avg_query_time_ms) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Temps SQL (max)</dt>
                        <dd class="text-white font-mono">{{ formatMs(metrics.database?.max_query_time_ms) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Taille BDD</dt>
                        <dd class="text-white font-mono">{{ metrics.database?.database_size_mb }} MB</dd>
                    </div>
                </dl>
            </div>

            <!-- System Stats -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Système</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Disque utilisé</dt>
                        <dd :class="[
                            'font-mono',
                            metrics.alerts?.disk_usage === 'critical' ? 'text-red-400' :
                            metrics.alerts?.disk_usage === 'warning' ? 'text-yellow-400' : 'text-white'
                        ]">
                            {{ metrics.system?.disk_used_percent }}%
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Espace libre</dt>
                        <dd class="text-white font-mono">{{ metrics.system?.disk_free_gb }} GB</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Stockage app</dt>
                        <dd class="text-white font-mono">{{ metrics.system?.storage_size_mb }} MB</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">PHP</dt>
                        <dd class="text-white">{{ metrics.system?.php_version }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Limite mémoire</dt>
                        <dd class="text-white">{{ metrics.system?.php_memory_limit }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Laravel</dt>
                        <dd class="text-white">{{ metrics.system?.laravel_version }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Application Stats -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Application</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Utilisateurs totaux</dt>
                        <dd class="text-white font-mono">{{ metrics.application?.users_total }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Actifs (24h)</dt>
                        <dd class="text-white font-mono">{{ metrics.application?.users_active_24h }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Actifs (7j)</dt>
                        <dd class="text-white font-mono">{{ metrics.application?.users_active_7d }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Factures (aujourd'hui)</dt>
                        <dd class="text-white font-mono">{{ metrics.application?.invoices_today }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Factures (semaine)</dt>
                        <dd class="text-white font-mono">{{ metrics.application?.invoices_week }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Jobs & Queue -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Jobs & Queue</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Jobs en attente</dt>
                        <dd class="text-white font-mono">{{ metrics.application?.jobs_pending }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Jobs échoués (24h)</dt>
                        <dd :class="[
                            'font-mono',
                            metrics.alerts?.failed_jobs === 'critical' ? 'text-red-400' :
                            metrics.alerts?.failed_jobs === 'warning' ? 'text-yellow-400' : 'text-green-400'
                        ]">
                            {{ metrics.application?.jobs_failed_24h }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Métriques stockées</dt>
                        <dd class="text-white font-mono">{{ metrics.application?.metrics_count?.toLocaleString() }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Thresholds Reference -->
            <div class="rounded-xl bg-slate-800 p-6">
                <h2 class="mb-4 text-lg font-semibold text-white">Seuils d'alerte</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Temps réponse</dt>
                        <dd class="text-slate-300">
                            <span class="text-yellow-400">{{ thresholds.response_time.warning }}ms</span> /
                            <span class="text-red-400">{{ thresholds.response_time.critical }}ms</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Requêtes SQL</dt>
                        <dd class="text-slate-300">
                            <span class="text-yellow-400">{{ thresholds.query_count.warning }}</span> /
                            <span class="text-red-400">{{ thresholds.query_count.critical }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Disque</dt>
                        <dd class="text-slate-300">
                            <span class="text-yellow-400">{{ thresholds.disk_usage.warning }}%</span> /
                            <span class="text-red-400">{{ thresholds.disk_usage.critical }}%</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Jobs échoués</dt>
                        <dd class="text-slate-300">
                            <span class="text-yellow-400">{{ thresholds.failed_jobs.warning }}</span> /
                            <span class="text-red-400">{{ thresholds.failed_jobs.critical }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-400">Taux erreur</dt>
                        <dd class="text-slate-300">
                            <span class="text-yellow-400">{{ thresholds.error_rate.warning }}%</span> /
                            <span class="text-red-400">{{ thresholds.error_rate.critical }}%</span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Slowest Requests Table -->
        <div class="mt-6 rounded-xl bg-slate-800 p-6">
            <h2 class="mb-4 text-lg font-semibold text-white">Requêtes les plus lentes</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-700 text-slate-400">
                            <th class="pb-3 font-medium">URL</th>
                            <th class="pb-3 font-medium">Méthode</th>
                            <th class="pb-3 font-medium text-right">Temps</th>
                            <th class="pb-3 font-medium text-right">Requêtes SQL</th>
                            <th class="pb-3 font-medium text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        <tr
                            v-for="(req, index) in metrics.requests?.slowest"
                            :key="index"
                            class="text-slate-300"
                        >
                            <td class="py-3 font-mono text-xs max-w-xs truncate">{{ req.url }}</td>
                            <td class="py-3">
                                <span :class="[
                                    'rounded px-2 py-0.5 text-xs font-medium',
                                    req.method === 'GET' ? 'bg-green-500/20 text-green-400' :
                                    req.method === 'POST' ? 'bg-blue-500/20 text-blue-400' :
                                    req.method === 'PUT' ? 'bg-yellow-500/20 text-yellow-400' :
                                    req.method === 'DELETE' ? 'bg-red-500/20 text-red-400' :
                                    'bg-slate-500/20 text-slate-400'
                                ]">
                                    {{ req.method }}
                                </span>
                            </td>
                            <td class="py-3 text-right font-mono" :class="req.response_time_ms > 2000 ? 'text-red-400' : req.response_time_ms > 500 ? 'text-yellow-400' : ''">
                                {{ formatMs(req.response_time_ms) }}
                            </td>
                            <td class="py-3 text-right font-mono" :class="req.query_count > 50 ? 'text-red-400' : req.query_count > 20 ? 'text-yellow-400' : ''">
                                {{ req.query_count }}
                            </td>
                            <td class="py-3 text-right">
                                <span :class="[
                                    'rounded px-2 py-0.5 text-xs font-medium',
                                    req.status_code >= 500 ? 'bg-red-500/20 text-red-400' :
                                    req.status_code >= 400 ? 'bg-yellow-500/20 text-yellow-400' :
                                    'bg-green-500/20 text-green-400'
                                ]">
                                    {{ req.status_code }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!metrics.requests?.slowest?.length">
                            <td colspan="5" class="py-8 text-center text-slate-500">
                                Aucune donnée disponible pour cette période
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
