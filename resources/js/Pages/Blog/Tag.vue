<script setup>
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { localizedRoute, currentLocale } = useLocalizedRoute();

const props = defineProps({
    tag: Object,
    posts: Object,
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
        :title="`#${tag.name} - Blog | faktur.lu`"
        :description="`Articles tagués ${tag.name} sur la facturation au Luxembourg.`"
        :canonical-path="`/blog/tag/${tag.slug}`"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumbs -->
                <nav class="mb-8">
                    <ol class="flex items-center gap-2 text-sm text-gray-500">
                        <li>
                            <Link href="/" class="hover:text-[#9b5de5]">Accueil</Link>
                        </li>
                        <li>/</li>
                        <li>
                            <Link :href="localizedRoute('blog.index')" class="hover:text-[#9b5de5]">Blog</Link>
                        </li>
                        <li>/</li>
                        <li class="text-gray-900 font-medium">#{{ tag.name }}</li>
                    </ol>
                </nav>

                <!-- Header -->
                <div class="text-center mb-12">
                    <div class="inline-block rounded-full bg-[#9b5de5]/20 px-4 py-2 text-[#9b5de5] font-medium mb-4">
                        #{{ tag.name }}
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                        Articles tagués "{{ tag.name }}"
                    </h1>
                </div>

                <!-- Posts list -->
                <div class="space-y-8">
                    <article
                        v-for="post in posts.data"
                        :key="post.id"
                        class="group bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-shadow"
                    >
                        <Link :href="localizedRoute('blog.show', post.slug)" class="flex flex-col sm:flex-row">
                            <div v-if="post.cover_image" class="sm:w-48 aspect-video sm:aspect-square shrink-0 overflow-hidden">
                                <img
                                    :src="post.cover_image.startsWith('http') ? post.cover_image : `/storage/${post.cover_image}`"
                                    :alt="post.title"
                                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                            </div>

                            <div class="p-6 flex-1">
                                <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
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

                                <p v-if="post.excerpt" class="mt-2 text-gray-600 line-clamp-2">
                                    {{ post.excerpt }}
                                </p>
                            </div>
                        </Link>
                    </article>
                </div>

                <!-- Empty state -->
                <div v-if="posts.data.length === 0" class="text-center py-12 bg-white rounded-2xl">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Aucun article avec ce tag</h3>
                    <Link :href="localizedRoute('blog.index')" class="mt-4 inline-block text-[#9b5de5] hover:text-[#9b5de5]">
                        Retour au blog
                    </Link>
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

                <!-- Back link -->
                <div class="mt-8 text-center">
                    <Link
                        :href="localizedRoute('blog.index')"
                        class="inline-flex items-center text-[#9b5de5] hover:text-[#9b5de5] font-medium"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour au blog
                    </Link>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
