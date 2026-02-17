<script setup>
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useTranslations } from '@/Composables/useTranslations';

const { localizedRoute, currentLocale } = useLocalizedRoute();
const { t } = useTranslations();

const props = defineProps({
    posts: Object,
    categories: Array,
    recentPosts: Array,
});

const formatDate = (date) => {
    const localeMap = { 'fr': 'fr-FR', 'de': 'de-DE', 'en': 'en-GB', 'lb': 'lb-LU' };
    return new Date(date).toLocaleDateString(localeMap[currentLocale()] || 'fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <SeoHead
        :title="t('blog.page_title')"
        :description="t('blog.meta_description')"
        canonical-path="/blog"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="text-center mb-12">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                        {{ t('blog.title') }}
                    </h1>
                    <p class="mt-4 text-lg text-gray-600">
                        {{ t('blog.subtitle') }}
                    </p>
                </div>

                <div class="grid gap-8 lg:grid-cols-4">
                    <!-- Main content -->
                    <div class="lg:col-span-3">
                        <!-- Posts grid -->
                        <div class="grid gap-8 md:grid-cols-2">
                            <article
                                v-for="post in posts.data"
                                :key="post.id"
                                class="group bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-shadow"
                            >
                                <Link :href="localizedRoute('blog.show', post.slug)">
                                    <div v-if="post.cover_image" class="aspect-[16/9] overflow-hidden">
                                        <img
                                            :src="post.cover_image.startsWith('http') ? post.cover_image : `/storage/${post.cover_image}`"
                                            :alt="post.title"
                                            class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                    </div>
                                    <div v-else class="aspect-[16/9] bg-gradient-to-br from-[#9b5de5] to-[#7c3aed] flex items-center justify-center">
                                        <svg class="h-16 w-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                        </svg>
                                    </div>

                                    <div class="p-6">
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                                            <span v-if="post.category" class="text-[#9b5de5] font-medium">
                                                {{ post.category.name }}
                                            </span>
                                            <span v-if="post.category">•</span>
                                            <time :datetime="post.published_at">
                                                {{ formatDate(post.published_at) }}
                                            </time>
                                        </div>

                                        <h2 class="text-xl font-semibold text-gray-900 group-hover:text-[#9b5de5] transition-colors">
                                            {{ post.title }}
                                        </h2>

                                        <p v-if="post.excerpt" class="mt-3 text-gray-600 line-clamp-3">
                                            {{ post.excerpt }}
                                        </p>

                                        <div class="mt-4 flex items-center text-[#9b5de5] font-medium">
                                            {{ t('blog.read_more') }}
                                            <svg class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </Link>
                            </article>
                        </div>

                        <!-- Empty state -->
                        <div v-if="posts.data.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">{{ t('blog.no_posts') }}</h3>
                            <p class="mt-1 text-gray-500">{{ t('blog.posts_coming_soon') }}</p>
                        </div>

                        <!-- Pagination -->
                        <nav v-if="posts.links && posts.links.length > 3" class="mt-8 flex justify-center gap-1">
                            <template v-for="link in posts.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                        link.active
                                            ? 'bg-[#9b5de5] text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-100',
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-4 py-2 text-sm text-gray-400"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>

                    <!-- Sidebar -->
                    <aside class="space-y-8">
                        <!-- Categories -->
                        <div class="bg-white rounded-2xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('blog.categories') }}</h3>
                            <ul class="space-y-2">
                                <li v-for="category in categories" :key="category.id">
                                    <Link
                                        :href="localizedRoute('blog.category', category.slug)"
                                        class="flex items-center justify-between text-gray-600 hover:text-[#9b5de5] transition-colors"
                                    >
                                        <span>{{ category.name }}</span>
                                        <span class="text-sm text-gray-400">{{ category.posts_count }}</span>
                                    </Link>
                                </li>
                            </ul>
                            <p v-if="categories.length === 0" class="text-sm text-gray-500">
                                {{ t('blog.no_categories') }}
                            </p>
                        </div>

                        <!-- Recent posts -->
                        <div class="bg-white rounded-2xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('blog.recent_posts') }}</h3>
                            <ul class="space-y-4">
                                <li v-for="post in recentPosts" :key="post.id">
                                    <Link
                                        :href="localizedRoute('blog.show', post.slug)"
                                        class="block text-gray-600 hover:text-[#9b5de5] transition-colors"
                                    >
                                        <span class="font-medium">{{ post.title }}</span>
                                        <span class="block text-sm text-gray-400 mt-1">
                                            {{ formatDate(post.published_at) }}
                                        </span>
                                    </Link>
                                </li>
                            </ul>
                            <p v-if="recentPosts.length === 0" class="text-sm text-gray-500">
                                {{ t('blog.no_recent_posts') }}
                            </p>
                        </div>

                        <!-- CTA -->
                        <div class="bg-gradient-to-br from-[#9b5de5] to-[#7c3aed] rounded-2xl p-6 text-white">
                            <h3 class="text-lg font-semibold mb-2">{{ t('blog.cta_title') }}</h3>
                            <p class="text-white/80 text-sm mb-4">
                                {{ t('blog.cta_subtitle') }}
                            </p>
                            <Link
                                :href="route('register')"
                                class="inline-block bg-white text-[#9b5de5] font-medium px-4 py-2 rounded-lg hover:bg-[#9b5de5]/10 transition-colors"
                            >
                                {{ t('blog.cta_button') }}
                            </Link>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
