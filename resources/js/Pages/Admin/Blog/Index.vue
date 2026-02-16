<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    posts: Object,
    categories: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const category = ref(props.filters.category);

const applyFilters = debounce(() => {
    router.get(route('admin.blog.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        category: category.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status, category], applyFilters);

const deletePost = (post) => {
    if (confirm(`Supprimer l'article "${post.title}" ?`)) {
        router.delete(route('admin.blog.destroy', post.slug));
    }
};

const duplicatePost = (post) => {
    router.post(route('admin.blog.duplicate', post.slug));
};

const statusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        published: 'Publié',
        archived: 'Archivé',
    };
    return labels[status] || status;
};

const statusClass = (status) => {
    const classes = {
        draft: 'bg-yellow-500/20 text-yellow-400',
        published: 'bg-green-500/20 text-green-400',
        archived: 'bg-slate-500/20 text-slate-400',
    };
    return classes[status] || '';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Blog - Articles" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-white">Blog</h1>
                <div class="flex gap-2">
                    <Link :href="route('admin.blog-categories.index')">
                        <SecondaryButton>Catégories</SecondaryButton>
                    </Link>
                    <Link :href="route('admin.blog-tags.index')">
                        <SecondaryButton>Tags</SecondaryButton>
                    </Link>
                    <Link :href="route('admin.blog.create')">
                        <PrimaryButton>Nouvel article</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Stats -->
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-sm text-slate-400">Total</p>
                <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-sm text-slate-400">Publiés</p>
                <p class="text-2xl font-bold text-green-400">{{ stats.published }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-sm text-slate-400">Brouillons</p>
                <p class="text-2xl font-bold text-yellow-400">{{ stats.draft }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
            <input
                v-model="search"
                type="text"
                placeholder="Rechercher..."
                class="rounded-lg border-slate-600 bg-slate-700 px-4 py-2 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
            />
            <select
                v-model="status"
                class="rounded-lg border-slate-600 bg-slate-700 px-4 py-2 text-white focus:border-purple-500 focus:ring-purple-500"
            >
                <option value="">Tous les statuts</option>
                <option value="draft">Brouillon</option>
                <option value="published">Publié</option>
                <option value="archived">Archivé</option>
            </select>
            <select
                v-model="category"
                class="rounded-lg border-slate-600 bg-slate-700 px-4 py-2 text-white focus:border-purple-500 focus:ring-purple-500"
            >
                <option value="">Toutes les catégories</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
        </div>

        <!-- Table -->
        <div class="rounded-xl bg-slate-800 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-700 text-slate-400">
                    <tr>
                        <th class="px-6 py-4 font-medium">Titre</th>
                        <th class="px-6 py-4 font-medium">Catégorie</th>
                        <th class="px-6 py-4 font-medium">Statut</th>
                        <th class="px-6 py-4 font-medium">Date</th>
                        <th class="px-6 py-4 font-medium">Vues</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <tr v-for="post in posts.data" :key="post.id" class="hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-white">{{ post.title }}</div>
                            <div class="text-xs text-slate-400">/blog/{{ post.slug }}</div>
                        </td>
                        <td class="px-6 py-4 text-slate-300">
                            {{ post.category?.name || '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusClass(post.status)]">
                                {{ statusLabel(post.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-300">
                            {{ formatDate(post.published_at || post.created_at) }}
                        </td>
                        <td class="px-6 py-4 text-slate-300">
                            {{ post.views_count }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <a
                                    v-if="post.status === 'published'"
                                    :href="route('blog.show', post.slug)"
                                    target="_blank"
                                    class="text-slate-400 hover:text-white"
                                    title="Voir"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <Link
                                    :href="route('admin.blog.edit', post.slug)"
                                    class="text-slate-400 hover:text-white"
                                    title="Modifier"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </Link>
                                <button
                                    @click="duplicatePost(post)"
                                    class="text-slate-400 hover:text-white"
                                    title="Dupliquer"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deletePost(post)"
                                    class="text-red-400 hover:text-red-300"
                                    title="Supprimer"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="posts.data.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            Aucun article trouvé
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="posts.links && posts.links.length > 3" class="mt-4 flex justify-center gap-1">
            <template v-for="link in posts.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :class="[
                        'px-3 py-2 rounded text-sm',
                        link.active
                            ? 'bg-purple-600 text-white'
                            : 'bg-slate-700 text-slate-300 hover:bg-slate-600',
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-3 py-2 text-sm text-slate-500"
                    v-html="link.label"
                />
            </template>
        </div>
    </AdminLayout>
</template>
