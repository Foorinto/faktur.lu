<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    category: Object,
    posts: Object,
    categories: Array,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <Head>
        <title>{{ category.name }} - Blog | faktur.lu</title>
        <meta :content="`Articles de la catégorie ${category.name} sur la facturation au Luxembourg.`" name="description" />
    </Head>

    <GuestLayout>
        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumbs -->
                <nav class="mb-8">
                    <ol class="flex items-center gap-2 text-sm text-gray-500">
                        <li>
                            <Link href="/" class="hover:text-purple-600">Accueil</Link>
                        </li>
                        <li>/</li>
                        <li>
                            <Link :href="route('blog.index')" class="hover:text-purple-600">Blog</Link>
                        </li>
                        <li>/</li>
                        <li class="text-gray-900 font-medium">{{ category.name }}</li>
                    </ol>
                </nav>

                <!-- Header -->
                <div class="mb-12">
                    <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                        {{ category.name }}
                    </h1>
                    <p v-if="category.description" class="mt-4 text-lg text-gray-600">
                        {{ category.description }}
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
                                <Link :href="route('blog.show', post.slug)">
                                    <div v-if="post.cover_image" class="aspect-[16/9] overflow-hidden">
                                        <img
                                            :src="post.cover_image.startsWith('http') ? post.cover_image : `/storage/${post.cover_image}`"
                                            :alt="post.title"
                                            class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                    </div>
                                    <div v-else class="aspect-[16/9] bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                                        <svg class="h-16 w-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                        </svg>
                                    </div>

                                    <div class="p-6">
                                        <div class="text-sm text-gray-500 mb-3">
                                            <time :datetime="post.published_at">
                                                {{ formatDate(post.published_at) }}
                                            </time>
                                        </div>

                                        <h2 class="text-xl font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">
                                            {{ post.title }}
                                        </h2>

                                        <p v-if="post.excerpt" class="mt-3 text-gray-600 line-clamp-3">
                                            {{ post.excerpt }}
                                        </p>

                                        <div class="mt-4 flex items-center text-purple-600 font-medium">
                                            Lire la suite
                                            <svg class="ml-2 h-4 w-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </div>
                                    </div>
                                </Link>
                            </article>
                        </div>

                        <!-- Empty state -->
                        <div v-if="posts.data.length === 0" class="text-center py-12 bg-white rounded-2xl">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">Aucun article dans cette catégorie</h3>
                            <p class="mt-1 text-gray-500">Revenez bientôt pour de nouveaux contenus !</p>
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
                                            ? 'bg-purple-600 text-white'
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
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Catégories</h3>
                            <ul class="space-y-2">
                                <li v-for="cat in categories" :key="cat.id">
                                    <Link
                                        :href="route('blog.category', cat.slug)"
                                        :class="[
                                            'flex items-center justify-between transition-colors',
                                            cat.slug === category.slug
                                                ? 'text-purple-600 font-medium'
                                                : 'text-gray-600 hover:text-purple-600',
                                        ]"
                                    >
                                        <span>{{ cat.name }}</span>
                                        <span class="text-sm text-gray-400">{{ cat.posts_count }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- CTA -->
                        <div class="bg-gradient-to-br from-purple-600 to-indigo-600 rounded-2xl p-6 text-white">
                            <h3 class="text-lg font-semibold mb-2">Essayez faktur.lu</h3>
                            <p class="text-purple-100 text-sm mb-4">
                                Créez vos factures conformes au Luxembourg en quelques clics.
                            </p>
                            <Link
                                :href="route('register')"
                                class="inline-block bg-white text-purple-600 font-medium px-4 py-2 rounded-lg hover:bg-purple-50 transition-colors"
                            >
                                Essai gratuit 14 jours
                            </Link>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
