<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { useTranslations } from '@/Composables/useTranslations';
import debounce from 'lodash/debounce';
import RowAction from '@/Components/RowAction.vue';

const { t } = useTranslations();

const props = defineProps({
    posts: Object,
    categories: Array,
    filters: Object,
    stats: Object,
});

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const category = ref(props.filters.category);
const locale = ref(props.filters.locale);

const locales = {
    fr: 'Français',
    de: 'Deutsch',
    en: 'English',
    lb: 'Lëtzebuergesch',
};

const localeFlags = {
    fr: '🇫🇷',
    de: '🇩🇪',
    en: '🇬🇧',
    lb: '🇱🇺',
    pt: '🇵🇹',
};

const applyFilters = debounce(() => {
    router.get(route('admin.blog.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        category: category.value || undefined,
        locale: locale.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, status, category, locale], applyFilters);

const deletePost = (post) => {
    if (confirm(t('admin_blog_confirm_delete', { title: post.title }))) {
        router.delete(route('admin.blog.destroy', post.slug));
    }
};

const duplicatePost = (post) => {
    router.post(route('admin.blog.duplicate', post.slug));
};

const statusLabel = (status) => {
    const labels = {
        draft: t('admin_blog_status_draft'),
        published: t('admin_blog_status_published'),
        archived: t('admin_blog_status_archived'),
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
    <Head :title="t('admin_blog_head_title')" />

    <AdminLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-white">{{ t('admin_blog_title') }}</h1>
        </template>
        <template #header-actions>
            <Link :href="route('admin.blog-categories.index')">
                <SecondaryButton>{{ t('admin_blog_categories') }}</SecondaryButton>
            </Link>
            <Link :href="route('admin.blog-tags.index')">
                <SecondaryButton>{{ t('admin_blog_tags') }}</SecondaryButton>
            </Link>
            <Link :href="route('admin.blog.create')">
                <PrimaryButton>{{ t('admin_blog_new_post') }}</PrimaryButton>
            </Link>
        </template>

        <!-- Stats -->
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-sm text-slate-400">{{ t('admin_total') }}</p>
                <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-sm text-slate-400">{{ t('admin_blog_stat_published') }}</p>
                <p class="text-2xl font-bold text-green-400">{{ stats.published }}</p>
            </div>
            <div class="rounded-xl bg-slate-800 p-4">
                <p class="text-sm text-slate-400">{{ t('admin_blog_stat_drafts') }}</p>
                <p class="text-2xl font-bold text-yellow-400">{{ stats.draft }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-4">
            <input
                v-model="search"
                type="text"
                :placeholder="t('admin_search')"
                class="rounded-lg border-slate-600 bg-slate-700 px-4 py-2 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
            />
            <select
                v-model="status"
                class="rounded-lg border-slate-600 bg-slate-700 px-4 py-2 text-white focus:border-purple-500 focus:ring-purple-500"
            >
                <option value="">{{ t('admin_blog_all_statuses') }}</option>
                <option value="draft">{{ t('admin_blog_status_draft') }}</option>
                <option value="published">{{ t('admin_blog_status_published') }}</option>
                <option value="archived">{{ t('admin_blog_status_archived') }}</option>
            </select>
            <select
                v-model="category"
                class="rounded-lg border-slate-600 bg-slate-700 px-4 py-2 text-white focus:border-purple-500 focus:ring-purple-500"
            >
                <option value="">{{ t('admin_blog_all_categories') }}</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
            <select
                v-model="locale"
                class="rounded-lg border-slate-600 bg-slate-700 px-4 py-2 text-white focus:border-purple-500 focus:ring-purple-500"
            >
                <option value="">{{ t('admin_blog_all_languages') }}</option>
                <option v-for="(name, code) in locales" :key="code" :value="code">
                    {{ localeFlags[code] }} {{ name }}
                </option>
            </select>
        </div>

        <!-- Table -->
        <div class="rounded-xl bg-slate-800 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-700 text-slate-400">
                    <tr>
                        <th class="px-6 py-4 font-medium">{{ t('admin_blog_th_title') }}</th>
                        <th class="px-6 py-4 font-medium">{{ t('admin_blog_th_locale') }}</th>
                        <th class="px-6 py-4 font-medium">{{ t('admin_blog_th_category') }}</th>
                        <th class="px-6 py-4 font-medium">{{ t('admin_blog_th_status') }}</th>
                        <th class="px-6 py-4 font-medium">{{ t('admin_blog_th_date') }}</th>
                        <th class="px-6 py-4 font-medium">{{ t('admin_blog_th_views') }}</th>
                        <th class="px-6 py-4 font-medium text-right">{{ t('admin_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <tr v-for="post in posts.data" :key="post.id" class="hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-white">{{ post.title }}</div>
                            <div class="text-xs text-slate-400">/{{ post.locale || 'fr' }}/blog/{{ post.slug }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span :title="locales[post.locale] || locales.fr" class="text-lg">
                                {{ localeFlags[post.locale] || '🇫🇷' }}
                            </span>
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
                            <div class="flex items-center justify-end gap-1">
                                <RowAction
                                    v-if="post.status === 'published'"
                                    icon="view"
                                    :label="t('admin_view')"
                                    :href="route('blog.show', post.slug)"
                                />
                                <RowAction
                                    icon="edit"
                                    tone="primary"
                                    :label="t('admin_edit')"
                                    :href="route('admin.blog.edit', post.slug)"
                                />
                                <RowAction
                                    icon="duplicate"
                                    :label="t('admin_duplicate')"
                                    @click="duplicatePost(post)"
                                />
                                <RowAction
                                    icon="delete"
                                    tone="danger"
                                    :label="t('admin_delete')"
                                    @click="deletePost(post)"
                                />
                            </div>
                        </td>
                    </tr>
                    <tr v-if="posts.data.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            {{ t('admin_blog_no_posts') }}
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
