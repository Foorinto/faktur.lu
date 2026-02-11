<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    categories: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    priorities: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    message: '',
    is_internal: false,
    status: null,
});

const selectedStatus = ref(props.ticket.status);
const selectedPriority = ref(props.ticket.priority);

const submit = () => {
    form.post(route('admin.support.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const updateTicket = () => {
    router.put(route('admin.support.update', props.ticket.id), {
        status: selectedStatus.value,
        priority: selectedPriority.value,
    }, {
        preserveScroll: true,
    });
};

const markResolved = () => {
    selectedStatus.value = 'resolved';
    router.put(route('admin.support.update', props.ticket.id), {
        status: 'resolved',
    }, {
        preserveScroll: true,
    });
};

const canMarkResolved = computed(() => {
    return !['resolved', 'closed'].includes(selectedStatus.value);
});

const deleteTicket = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce ticket ? Cette action est irréversible.')) {
        router.delete(route('admin.support.destroy', props.ticket.id));
    }
};

const getStatusBadgeClass = (status) => {
    const classes = {
        new: 'bg-purple-100 text-purple-700',
        open: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-yellow-100 text-yellow-700',
        waiting: 'bg-orange-100 text-orange-700',
        resolved: 'bg-emerald-100 text-emerald-700',
        closed: 'bg-slate-100 text-slate-700',
    };
    return classes[status] || classes.closed;
};

const getPriorityBadgeClass = (priority) => {
    const classes = {
        urgent: 'bg-red-100 text-red-700',
        high: 'bg-orange-100 text-orange-700',
        normal: 'bg-blue-100 text-blue-700',
        low: 'bg-slate-100 text-slate-700',
    };
    return classes[priority] || classes.normal;
};

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>

<template>
    <Head :title="ticket.reference + ' - Support Admin'" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.support.index')"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-700 hover:text-white"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-white">{{ ticket.reference }}</h1>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Ticket header -->
                <div class="rounded-xl bg-slate-800 p-6 border border-slate-700">
                    <h2 class="text-lg font-medium text-white mb-2">
                        {{ ticket.subject }}
                    </h2>
                    <div class="flex flex-wrap gap-4 text-sm text-slate-400">
                        <span>{{ categories[ticket.category] }}</span>
                        <span>Créé le {{ formatDateTime(ticket.created_at) }}</span>
                    </div>
                </div>

                <!-- Messages -->
                <div class="space-y-4">
                    <div
                        v-for="message in ticket.messages"
                        :key="message.id"
                        :class="[
                            'rounded-xl p-4 border',
                            message.is_internal
                                ? 'bg-yellow-900/20 border-yellow-700'
                                : message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'
                                    ? 'bg-purple-900/20 border-purple-700'
                                    : 'bg-slate-800 border-slate-700'
                        ]"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                :class="[
                                    'h-8 w-8 rounded-full flex items-center justify-center text-sm font-medium',
                                    message.is_internal
                                        ? 'bg-yellow-600 text-white'
                                        : message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'
                                            ? 'bg-purple-600 text-white'
                                            : 'bg-slate-600 text-slate-300'
                                ]"
                            >
                                <template v-if="message.is_internal">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 3a9.956 9.956 0 00-4.744 1.194L3.28 2.22zM7.752 6.69l1.092 1.092a2.5 2.5 0 013.374 3.373l1.091 1.092a4 4 0 00-5.557-5.557z" clip-rule="evenodd" />
                                        <path d="M10.748 13.93l2.523 2.523a9.987 9.987 0 01-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 010-1.186A10.007 10.007 0 012.839 6.02L6.07 9.252a4 4 0 004.678 4.678z" />
                                    </svg>
                                </template>
                                <template v-else-if="message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-5.5-2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM10 12a5.99 5.99 0 00-4.793 2.39A6.483 6.483 0 0010 16.5a6.483 6.483 0 004.793-2.11A5.99 5.99 0 0010 12z" clip-rule="evenodd" />
                                    </svg>
                                </template>
                                <template v-else>
                                    {{ message.sender?.name?.charAt(0)?.toUpperCase() || 'U' }}
                                </template>
                            </div>
                            <div class="flex-1">
                                <span
                                    :class="[
                                        'text-sm font-medium',
                                        message.is_internal
                                            ? 'text-yellow-400'
                                            : message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession'
                                                ? 'text-purple-400'
                                                : 'text-white'
                                    ]"
                                >
                                    {{ message.is_internal ? 'Note interne' : (message.sender_type === 'admin' || message.sender_type === 'App\\Models\\AdminSession' ? 'Admin' : (message.sender?.name || 'Utilisateur')) }}
                                </span>
                                <span class="text-xs text-slate-400 ml-2">
                                    {{ formatDateTime(message.created_at) }}
                                </span>
                            </div>
                        </div>
                        <div class="pl-10 text-sm text-slate-300 whitespace-pre-wrap">
                            {{ message.content }}
                        </div>

                        <!-- Attachments -->
                        <div v-if="message.attachments && message.attachments.length > 0" class="mt-3 pl-10">
                            <div v-for="attachment in message.attachments" :key="attachment.id" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 px-3 py-1.5 text-sm">
                                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z" clip-rule="evenodd" />
                                </svg>
                                <a :href="attachment.url" target="_blank" class="text-purple-400 hover:text-purple-300">
                                    {{ attachment.filename }}
                                </a>
                                <span class="text-slate-500">{{ attachment.size_formatted }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reply form -->
                <div class="rounded-xl bg-slate-800 p-6 border border-slate-700">
                    <h3 class="text-lg font-medium text-white mb-4">Répondre</h3>
                    <form @submit.prevent="submit">
                        <textarea
                            v-model="form.message"
                            rows="4"
                            class="block w-full rounded-lg border-0 bg-slate-700 py-2 px-3 text-white placeholder:text-slate-400 focus:ring-2 focus:ring-purple-500 sm:text-sm"
                            placeholder="Votre réponse..."
                            required
                        />

                        <div class="mt-4 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-slate-300">
                                <input
                                    type="checkbox"
                                    v-model="form.is_internal"
                                    class="rounded border-slate-600 bg-slate-700 text-yellow-600 focus:ring-yellow-500"
                                />
                                <span>Note interne (invisible pour l'utilisateur)</span>
                            </label>

                            <div class="flex gap-2">
                                <button
                                    v-if="canMarkResolved"
                                    type="button"
                                    @click="markResolved"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
                                >
                                    Marquer résolu
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-500 disabled:opacity-50"
                                >
                                    Envoyer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- User info -->
                <div class="rounded-xl bg-slate-800 p-6 border border-slate-700">
                    <h3 class="text-lg font-medium text-white mb-4">Utilisateur</h3>
                    <div class="space-y-3">
                        <div>
                            <div class="text-sm text-slate-400">Nom</div>
                            <div class="text-white">{{ ticket.user?.name || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-slate-400">Email</div>
                            <div class="text-white">{{ ticket.user?.email || '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Ticket details -->
                <div class="rounded-xl bg-slate-800 p-6 border border-slate-700">
                    <h3 class="text-lg font-medium text-white mb-4">Détails</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Statut</label>
                            <select
                                v-model="selectedStatus"
                                @change="updateTicket"
                                class="block w-full rounded-lg border-0 bg-slate-700 py-2 pl-3 pr-10 text-white focus:ring-2 focus:ring-purple-500 sm:text-sm"
                            >
                                <option v-for="(label, value) in statuses" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-slate-400 mb-1">Priorité</label>
                            <select
                                v-model="selectedPriority"
                                @change="updateTicket"
                                class="block w-full rounded-lg border-0 bg-slate-700 py-2 pl-3 pr-10 text-white focus:ring-2 focus:ring-purple-500 sm:text-sm"
                            >
                                <option v-for="(label, value) in priorities" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <div class="text-sm text-slate-400">Catégorie</div>
                            <div class="text-white">{{ categories[ticket.category] }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-slate-400">Créé le</div>
                            <div class="text-white">{{ formatDateTime(ticket.created_at) }}</div>
                        </div>
                        <div v-if="ticket.first_response_at">
                            <div class="text-sm text-slate-400">1ère réponse</div>
                            <div class="text-white">{{ formatDateTime(ticket.first_response_at) }}</div>
                        </div>
                        <div v-if="ticket.resolved_at">
                            <div class="text-sm text-slate-400">Résolu le</div>
                            <div class="text-white">{{ formatDateTime(ticket.resolved_at) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Actions dangereuses -->
                <div class="rounded-xl bg-slate-800 p-6 border border-slate-700">
                    <h3 class="text-lg font-medium text-white mb-4">Zone de danger</h3>
                    <button
                        type="button"
                        @click="deleteTicket"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                        </svg>
                        Supprimer ce ticket
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
