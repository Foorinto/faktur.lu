<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';

const props = defineProps({
    post: Object,
    relatedPosts: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
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
    <Head>
        <title>{{ post.meta_title }} | faktur.lu</title>
        <meta name="description" :content="post.meta_description" />
        <meta property="og:title" :content="post.meta_title" />
        <meta property="og:description" :content="post.meta_description" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="route('blog.show', post.slug)" />
        <meta v-if="post.cover_image_url" property="og:image" :content="post.cover_image_url" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="post.meta_title" />
        <meta name="twitter:description" :content="post.meta_description" />
    </Head>

    <MarketingLayout>
        <article class="bg-white">
            <!-- Hero -->
            <div class="relative">
                <div v-if="post.cover_image_url" class="aspect-[21/9] overflow-hidden">
                    <img
                        :src="post.cover_image_url"
                        :alt="post.title"
                        class="h-full w-full object-cover"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                </div>
                <div v-else class="bg-gradient-to-br from-[#9b5de5] to-[#7c3aed] py-20"></div>

                <div class="absolute inset-0 flex items-end">
                    <div class="mx-auto max-w-4xl px-4 pb-8 sm:px-6 lg:px-8 w-full">
                        <!-- Breadcrumbs -->
                        <nav class="mb-4">
                            <ol class="flex items-center gap-2 text-sm text-white/80">
                                <li>
                                    <Link href="/" class="hover:text-white">Accueil</Link>
                                </li>
                                <li>/</li>
                                <li>
                                    <Link :href="route('blog.index')" class="hover:text-white">Blog</Link>
                                </li>
                                <li v-if="post.category">/</li>
                                <li v-if="post.category">
                                    <Link :href="route('blog.category', post.category.slug)" class="hover:text-white">
                                        {{ post.category.name }}
                                    </Link>
                                </li>
                            </ol>
                        </nav>

                        <h1 class="text-3xl font-bold text-white sm:text-4xl lg:text-5xl">
                            {{ post.title }}
                        </h1>

                        <div class="mt-4 flex flex-wrap items-center gap-4 text-white/80">
                            <span v-if="post.author">Par {{ post.author.name }}</span>
                            <span>{{ formatDate(post.published_at) }}</span>
                            <span>{{ post.reading_time }} min de lecture</span>
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
                        :href="route('blog.tag', tag.slug)"
                        class="inline-block rounded-full bg-[#9b5de5]/20 px-3 py-1 text-sm font-medium text-[#9b5de5] hover:bg-purple-200 transition-colors"
                    >
                        #{{ tag.name }}
                    </Link>
                </div>

                <!-- Article content -->
                <div
                    class="prose prose-lg prose-purple max-w-none prose-headings:font-semibold prose-a:text-[#9b5de5] prose-img:rounded-xl"
                    v-html="post.content"
                />

                <!-- Share -->
                <div class="mt-12 border-t pt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Partager cet article</h3>
                    <div class="flex gap-3">
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
                <div class="mt-12 rounded-2xl bg-gradient-to-br from-[#9b5de5] to-[#7c3aed] p-8 text-center text-white">
                    <h3 class="text-2xl font-bold mb-2">Prêt à simplifier votre facturation ?</h3>
                    <p class="text-[#9b5de5]/20 mb-6">
                        Créez des factures conformes au Luxembourg en quelques clics avec faktur.lu
                    </p>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-lg bg-white px-6 py-3 font-semibold text-[#9b5de5] hover:bg-[#9b5de5]/10 transition-colors"
                    >
                        Essai gratuit 14 jours
                    </Link>
                </div>
            </div>

            <!-- Related posts -->
            <div v-if="relatedPosts && relatedPosts.length > 0" class="bg-gray-50 py-12">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-8">Articles similaires</h2>

                    <div class="grid gap-8 md:grid-cols-3">
                        <article
                            v-for="relatedPost in relatedPosts"
                            :key="relatedPost.slug"
                            class="group bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-lg transition-shadow"
                        >
                            <Link :href="route('blog.show', relatedPost.slug)">
                                <div v-if="relatedPost.cover_image_url" class="aspect-[16/9] overflow-hidden">
                                    <img
                                        :src="relatedPost.cover_image_url"
                                        :alt="relatedPost.title"
                                        class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    />
                                </div>
                                <div v-else class="aspect-[16/9] bg-gradient-to-br from-[#9b5de5] to-[#7c3aed]"></div>

                                <div class="p-6">
                                    <div class="text-sm text-gray-500 mb-2">
                                        <span v-if="relatedPost.category" class="text-[#9b5de5]">{{ relatedPost.category }}</span>
                                        <span v-if="relatedPost.category"> • </span>
                                        {{ formatDate(relatedPost.published_at) }}
                                    </div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-[#9b5de5] transition-colors">
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
