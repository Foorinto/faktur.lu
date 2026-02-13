<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    categories: Array,
});

const showModal = ref(false);
const editingCategory = ref(null);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    sort_order: 0,
});

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (category) => {
    editingCategory.value = category;
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description || '';
    form.sort_order = category.sort_order;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCategory.value = null;
    form.reset();
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('admin.blog-categories.update', editingCategory.value.id), {
            onSuccess: closeModal,
        });
    } else {
        form.post(route('admin.blog-categories.store'), {
            onSuccess: closeModal,
        });
    }
};

const deleteCategory = (category) => {
    if (category.posts_count > 0) {
        alert('Impossible de supprimer une catégorie contenant des articles.');
        return;
    }
    if (confirm(`Supprimer la catégorie "${category.name}" ?`)) {
        router.delete(route('admin.blog-categories.destroy', category.id));
    }
};
</script>

<template>
    <Head title="Blog - Catégories" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.blog.index')" class="text-slate-400 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-white">Catégories du blog</h1>
                </div>
                <PrimaryButton @click="openCreateModal">
                    Nouvelle catégorie
                </PrimaryButton>
            </div>
        </template>

        <!-- Categories list -->
        <div class="rounded-xl bg-slate-800 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-700 text-slate-400">
                    <tr>
                        <th class="px-6 py-4 font-medium">Ordre</th>
                        <th class="px-6 py-4 font-medium">Nom</th>
                        <th class="px-6 py-4 font-medium">Slug</th>
                        <th class="px-6 py-4 font-medium">Articles</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <tr v-for="category in categories" :key="category.id" class="hover:bg-slate-700/50">
                        <td class="px-6 py-4 text-slate-300">
                            {{ category.sort_order }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-white">{{ category.name }}</div>
                            <div v-if="category.description" class="text-xs text-slate-400 truncate max-w-xs">
                                {{ category.description }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-400 font-mono text-xs">
                            {{ category.slug }}
                        </td>
                        <td class="px-6 py-4 text-slate-300">
                            {{ category.posts_count }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <button
                                    @click="openEditModal(category)"
                                    class="text-slate-400 hover:text-white"
                                    title="Modifier"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteCategory(category)"
                                    :disabled="category.posts_count > 0"
                                    :class="[
                                        'transition-colors',
                                        category.posts_count > 0
                                            ? 'text-slate-600 cursor-not-allowed'
                                            : 'text-red-400 hover:text-red-300',
                                    ]"
                                    title="Supprimer"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="categories.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            Aucune catégorie. Créez-en une pour organiser vos articles.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingCategory ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}
                </h2>

                <form @submit.prevent="submit" class="mt-6 space-y-4">
                    <div>
                        <InputLabel for="name" value="Nom" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug" />
                        <TextInput
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="auto-généré si vide"
                        />
                        <InputError :message="form.errors.slug" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="sort_order" value="Ordre d'affichage" />
                        <TextInput
                            id="sort_order"
                            v-model="form.sort_order"
                            type="number"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.sort_order" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closeModal">
                            Annuler
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            {{ editingCategory ? 'Mettre à jour' : 'Créer' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>
