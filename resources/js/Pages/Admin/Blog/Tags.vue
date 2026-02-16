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
    tags: Array,
});

const showModal = ref(false);
const editingTag = ref(null);

const form = useForm({
    name: '',
    slug: '',
});

const openCreateModal = () => {
    editingTag.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (tag) => {
    editingTag.value = tag;
    form.name = tag.name;
    form.slug = tag.slug;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingTag.value = null;
    form.reset();
};

const submit = () => {
    if (editingTag.value) {
        form.put(route('admin.blog-tags.update', editingTag.value.slug), {
            onSuccess: closeModal,
        });
    } else {
        form.post(route('admin.blog-tags.store'), {
            onSuccess: closeModal,
        });
    }
};

const deleteTag = (tag) => {
    const message = tag.posts_count > 0
        ? `Supprimer le tag "${tag.name}" ? Il sera retiré de ${tag.posts_count} article(s).`
        : `Supprimer le tag "${tag.name}" ?`;

    if (confirm(message)) {
        router.delete(route('admin.blog-tags.destroy', tag.slug));
    }
};
</script>

<template>
    <Head title="Blog - Tags" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.blog.index')" class="text-slate-400 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-white">Tags du blog</h1>
                </div>
                <PrimaryButton @click="openCreateModal">
                    Nouveau tag
                </PrimaryButton>
            </div>
        </template>

        <!-- Tags list -->
        <div class="rounded-xl bg-slate-800 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-700 text-slate-400">
                    <tr>
                        <th class="px-6 py-4 font-medium">Nom</th>
                        <th class="px-6 py-4 font-medium">Slug</th>
                        <th class="px-6 py-4 font-medium">Articles</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <tr v-for="tag in tags" :key="tag.id" class="hover:bg-slate-700/50">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full bg-purple-600/20 px-3 py-1 text-sm font-medium text-purple-400">
                                {{ tag.name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-400 font-mono text-xs">
                            {{ tag.slug }}
                        </td>
                        <td class="px-6 py-4 text-slate-300">
                            {{ tag.posts_count }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <button
                                    @click="openEditModal(tag)"
                                    class="text-slate-400 hover:text-white"
                                    title="Modifier"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteTag(tag)"
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
                    <tr v-if="tags.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                            Aucun tag. Vous pouvez en créer en éditant un article.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ editingTag ? 'Modifier le tag' : 'Nouveau tag' }}
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
                            maxlength="50"
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
                            maxlength="50"
                        />
                        <InputError :message="form.errors.slug" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="closeModal">
                            Annuler
                        </SecondaryButton>
                        <PrimaryButton :disabled="form.processing">
                            {{ editingTag ? 'Mettre à jour' : 'Créer' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>
