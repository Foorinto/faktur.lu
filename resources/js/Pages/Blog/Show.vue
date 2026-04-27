<script setup>
import { Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useTranslations } from '@/Composables/useTranslations';

const { localizedRoute, currentLocale } = useLocalizedRoute();
const { t } = useTranslations();

const props = defineProps({
    post: Object,
    relatedPosts: Array,
});

const formatDate = (date) => {
    const localeMap = { 'fr': 'fr-FR', 'de': 'de-DE', 'en': 'en-GB', 'lb': 'lb-LU' };
    return new Date(date).toLocaleDateString(localeMap[currentLocale()] || 'fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const shareUrl = () => window.location.href;
const shareTitle = () => props.post.title;

const shareOnTwitter = () => {
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareTitle())}&url=${encodeURIComponent(shareUrl())}`, '_blank');
};

const shareOnLinkedIn = () => {
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl())}`, '_blank');
};

const shareOnFacebook = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl())}`, '_blank');
};
</script>

<template>
    <SeoHead
        :title="`${post.meta_title} | faktur.lu`"
        :description="post.meta_description"
        :canonical-path="`/blog/${post.slug}`"
        :image="post.cover_image_url"
        type="article"
    />

    <MarketingLayout>
        <article class="bg-white">
            <!-- Hero -->
            <div class="relative">
                <div v-if="post.cover_image_url" class="aspect-[21/9] overflow-hidden">
                    <img
                        :src="post.cover_image_url"
                        :alt="post.title"
                        loading="eager"
                        class="h-full w-full object-cover"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                </div>
                <div v-else class="bg-gradient-to-br from-primary-400 to-primary-600 py-20"></div>

                <div class="absolute inset-0 flex items-end">
                    <div class="mx-auto max-w-4xl px-4 pb-10 sm:px-6 lg:px-8 w-full">
                        <!-- Breadcrumbs -->
                        <nav class="mb-5">
                            <ol class="flex items-center gap-2 text-sm text-white/80">
                                <li>
                                    <Link href="/" class="hover:text-white transition-colors">Accueil</Link>
                                </li>
                                <li class="text-white/40">›</li>
                                <li>
                                    <Link :href="localizedRoute('blog.index')" class="hover:text-white transition-colors">Blog</Link>
                                </li>
                                <li v-if="post.category" class="text-white/40">›</li>
                                <li v-if="post.category">
                                    <Link :href="localizedRoute('blog.category', post.category.slug)" class="hover:text-white transition-colors">
                                        {{ post.category.name }}
                                    </Link>
                                </li>
                            </ol>
                        </nav>

                        <h1 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl leading-tight">
                            {{ post.title }}
                        </h1>

                        <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-white/90 text-sm">
                            <span v-if="post.author" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ post.author.name }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(post.published_at) }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ post.reading_time }} min de lecture
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
                <!-- Tags -->
                <div v-if="post.tags && post.tags.length > 0" class="mb-8 flex flex-wrap gap-2">
                    <Link
                        v-for="tag in post.tags"
                        :key="tag.slug"
                        :href="localizedRoute('blog.tag', tag.slug)"
                        class="inline-block rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700 hover:bg-primary-100 transition-colors"
                    >
                        #{{ tag.name }}
                    </Link>
                </div>

                <!-- Article content -->
                <div
                    class="prose prose-lg max-w-none
                        prose-headings:font-bold prose-headings:text-slate-900 prose-headings:mt-10 prose-headings:mb-4
                        prose-h2:text-2xl prose-h2:border-b prose-h2:border-gray-200 prose-h2:pb-3
                        prose-h3:text-xl prose-h3:text-slate-800
                        prose-p:text-slate-600 prose-p:leading-relaxed prose-p:mb-6
                        prose-a:text-primary-500 prose-a:font-medium prose-a:no-underline hover:prose-a:underline
                        prose-strong:text-slate-900 prose-strong:font-semibold
                        prose-ul:my-6 prose-ul:space-y-2
                        prose-ol:my-6 prose-ol:space-y-2
                        prose-li:text-slate-600 prose-li:leading-relaxed
                        prose-img:rounded-2xl prose-img:shadow-lg prose-img:my-8
                        prose-blockquote:border-l-4 prose-blockquote:border-primary-500 prose-blockquote:bg-slate-50 prose-blockquote:py-4 prose-blockquote:px-6 prose-blockquote:rounded-r-xl prose-blockquote:not-italic
                        prose-table:my-8 prose-table:overflow-hidden prose-table:rounded-xl prose-table:border prose-table:border-gray-200
                        prose-th:bg-slate-100 prose-th:text-slate-900 prose-th:font-semibold
                        prose-td:border-gray-200
                        prose-code:text-primary-500 prose-code:bg-primary-500/10 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:font-normal prose-code:before:content-none prose-code:after:content-none
                        prose-pre:bg-slate-900 prose-pre:text-slate-100 prose-pre:rounded-xl prose-pre:shadow-lg"
                    v-html="post.content"
                />

                <!-- Share -->
                <div class="mt-12 border-t border-gray-200 pt-8">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4">{{ t('blog.share_title') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        <button
                            @click="shareOnTwitter"
                            class="flex items-center gap-2 rounded-lg bg-[#1DA1F2] px-4 py-2 text-white hover:bg-[#1a8cd8] transition-colors"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                            </svg>
                            Twitter
                        </button>
                        <button
                            @click="shareOnLinkedIn"
                            class="flex items-center gap-2 rounded-lg bg-[#0077B5] px-4 py-2 text-white hover:bg-[#006097] transition-colors"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                            </svg>
                            LinkedIn
                        </button>
                        <button
                            @click="shareOnFacebook"
                            class="flex items-center gap-2 rounded-lg bg-[#1877F2] px-4 py-2 text-white hover:bg-[#166fe5] transition-colors"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                            Facebook
                        </button>
                    </div>
                </div>

                <!-- CTA -->
                <div class="mt-12 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 p-8 text-center text-white">
                    <h3 class="text-2xl font-bold mb-3">{{ t('blog.ready_title') }}</h3>
                    <p class="text-white/90 mb-6 max-w-xl mx-auto">
                        {{ t('blog.ready_subtitle') }}
                    </p>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-lg bg-white px-6 py-3 font-semibold text-primary-600 hover:bg-primary-50 transition-colors shadow-md"
                    >
                        {{ t('blog.cta_button') }}
                    </Link>
                </div>
            </div>

            <!-- Related posts -->
            <div v-if="relatedPosts && relatedPosts.length > 0" class="bg-slate-50 py-16 border-t border-gray-200">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">{{ t('blog.related_articles') }}</h2>
                    <div class="w-12 h-1 bg-primary-500 rounded-full mb-8"></div>

                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="relatedPost in relatedPosts"
                            :key="relatedPost.slug"
                            class="group bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-xl border border-gray-100 transition-all"
                        >
                            <Link :href="localizedRoute('blog.show', relatedPost.slug)" class="block">
                                <div v-if="relatedPost.cover_image_url" class="aspect-[16/9] overflow-hidden">
                                    <img
                                        :src="relatedPost.cover_image_url"
                                        :alt="relatedPost.title"
                                        loading="lazy"
                                        class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                </div>
                                <div v-else class="aspect-[16/9] bg-gradient-to-br from-primary-400 to-primary-600"></div>

                                <div class="p-6">
                                    <div class="flex items-center gap-2 text-xs text-slate-500 mb-3">
                                        <span v-if="relatedPost.category" class="inline-block rounded-full bg-primary-50 px-2.5 py-0.5 font-medium text-primary-700">{{ relatedPost.category }}</span>
                                        <span v-if="relatedPost.category" class="text-slate-300">•</span>
                                        <span>{{ formatDate(relatedPost.published_at) }}</span>
                                    </div>
                                    <h3 class="font-semibold text-slate-900 group-hover:text-primary-600 transition-colors leading-snug">
                                        {{ relatedPost.title }}
                                    </h3>
                                </div>
                            </Link>
                        </article>
                    </div>
                </div>
            </div>
        </article>
    </MarketingLayout>
</template>

<style>
.prose img {
    margin-left: auto;
    margin-right: auto;
}
</style>
