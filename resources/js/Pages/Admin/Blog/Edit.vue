<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    post: Object,
    categories: Array,
    tags: Array,
});

const form = useForm({
    title: props.post.title,
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    content: props.post.content,
    category_id: props.post.category_id || '',
    cover_image: null,
    remove_cover: false,
    meta_title: props.post.meta_title || '',
    meta_description: props.post.meta_description || '',
    status: props.post.status,
    published_at: props.post.published_at || '',
    tags: props.post.tags || [],
});

const previewImage = ref(props.post.cover_image_url);

const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.cover_image = file;
        form.remove_cover = false;
        previewImage.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.cover_image = null;
    form.remove_cover = true;
    previewImage.value = null;
};

const submit = () => {
    form.post(route('admin.blog.update', props.post.id), {
        forceFormData: true,
        _method: 'PUT',
    });
};
</script>

<template>
    <Head :title="`Modifier - ${post.title}`" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('admin.blog.index')" class="text-slate-400 hover:text-white">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-white">Modifier l'article</h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
            <!-- Main content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Title -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <div class="mb-4">
                        <InputLabel for="title" value="Titre" class="text-white" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full bg-slate-700 border-slate-600 text-white"
                            required
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug URL" class="text-white" />
                        <TextInput
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            class="mt-1 block w-full bg-slate-700 border-slate-600 text-white"
                        />
                        <InputError :message="form.errors.slug" class="mt-2" />
                    </div>
                </div>

                <!-- Excerpt -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <InputLabel for="excerpt" value="Extrait (résumé)" class="text-white" />
                    <textarea
                        id="excerpt"
                        v-model="form.excerpt"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-slate-600 bg-slate-700 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                        placeholder="Court résumé pour la liste des articles..."
                    ></textarea>
                    <InputError :message="form.errors.excerpt" class="mt-2" />
                </div>

                <!-- Content -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <InputLabel for="content" value="Contenu (HTML)" class="text-white" />
                    <textarea
                        id="content"
                        v-model="form.content"
                        rows="20"
                        class="mt-1 block w-full rounded-lg border-slate-600 bg-slate-700 font-mono text-sm text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                        required
                    ></textarea>
                    <InputError :message="form.errors.content" class="mt-2" />
                    <p class="mt-2 text-sm text-slate-400">
                        Vous pouvez utiliser du HTML pour formater votre article.
                    </p>
                </div>

                <!-- SEO -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h3 class="mb-4 text-lg font-semibold text-white">SEO</h3>

                    <div class="mb-4">
                        <InputLabel for="meta_title" value="Meta Title" class="text-white" />
                        <TextInput
                            id="meta_title"
                            v-model="form.meta_title"
                            type="text"
                            class="mt-1 block w-full bg-slate-700 border-slate-600 text-white"
                            placeholder="Titre pour les moteurs de recherche"
                        />
                        <InputError :message="form.errors.meta_title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="meta_description" value="Meta Description" class="text-white" />
                        <textarea
                            id="meta_description"
                            v-model="form.meta_description"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-slate-600 bg-slate-700 text-white placeholder-slate-400 focus:border-purple-500 focus:ring-purple-500"
                            placeholder="Description pour les moteurs de recherche (160 caractères max)"
                        ></textarea>
                        <InputError :message="form.errors.meta_description" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Publish -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h3 class="mb-4 text-lg font-semibold text-white">Publication</h3>

                    <div class="mb-4">
                        <InputLabel for="status" value="Statut" class="text-white" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-lg border-slate-600 bg-slate-700 text-white focus:border-purple-500 focus:ring-purple-500"
                        >
                            <option value="draft">Brouillon</option>
                            <option value="published">Publié</option>
                            <option value="archived">Archivé</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <InputLabel for="published_at" value="Date de publication" class="text-white" />
                        <TextInput
                            id="published_at"
                            v-model="form.published_at"
                            type="datetime-local"
                            class="mt-1 block w-full bg-slate-700 border-slate-600 text-white"
                        />
                        <InputError :message="form.errors.published_at" class="mt-2" />
                    </div>

                    <div class="flex gap-2">
                        <PrimaryButton :disabled="form.processing" class="flex-1 justify-center">
                            {{ form.processing ? 'Enregistrement...' : 'Mettre à jour' }}
                        </PrimaryButton>
                    </div>

                    <a
                        v-if="post.status === 'published'"
                        :href="route('blog.show', post.slug)"
                        target="_blank"
                        class="mt-3 block text-center text-sm text-purple-400 hover:text-purple-300"
                    >
                        Voir l'article
                    </a>
                </div>

                <!-- Category -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h3 class="mb-4 text-lg font-semibold text-white">Catégorie</h3>

                    <select
                        v-model="form.category_id"
                        class="block w-full rounded-lg border-slate-600 bg-slate-700 text-white focus:border-purple-500 focus:ring-purple-500"
                    >
                        <option value="">Aucune catégorie</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.category_id" class="mt-2" />
                </div>

                <!-- Tags -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h3 class="mb-4 text-lg font-semibold text-white">Tags</h3>

                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="tag in tags"
                            :key="tag.id"
                            :class="[
                                'cursor-pointer rounded-full px-3 py-1 text-sm transition-colors',
                                form.tags.includes(tag.id)
                                    ? 'bg-purple-600 text-white'
                                    : 'bg-slate-700 text-slate-300 hover:bg-slate-600',
                            ]"
                        >
                            <input
                                type="checkbox"
                                :value="tag.id"
                                v-model="form.tags"
                                class="sr-only"
                            />
                            {{ tag.name }}
                        </label>
                    </div>
                    <p v-if="tags.length === 0" class="text-sm text-slate-400">
                        Aucun tag disponible
                    </p>
                    <InputError :message="form.errors.tags" class="mt-2" />
                </div>

                <!-- Cover image -->
                <div class="rounded-xl bg-slate-800 p-6">
                    <h3 class="mb-4 text-lg font-semibold text-white">Image de couverture</h3>

                    <div v-if="previewImage" class="mb-4">
                        <img :src="previewImage" alt="Preview" class="w-full rounded-lg" />
                        <button
                            type="button"
                            @click="removeImage"
                            class="mt-2 text-sm text-red-400 hover:text-red-300"
                        >
                            Supprimer l'image
                        </button>
                    </div>

                    <input
                        type="file"
                        accept="image/*"
                        @change="handleImageUpload"
                        class="block w-full text-sm text-slate-400 file:mr-4 file:rounded-lg file:border-0 file:bg-purple-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-purple-700"
                    />
                    <InputError :message="form.errors.cover_image" class="mt-2" />
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
