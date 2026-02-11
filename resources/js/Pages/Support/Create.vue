<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

const { t } = useTranslations();

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    category: 'general',
    subject: '',
    message: '',
    attachment: null,
});

const fileInput = ref(null);
const fileName = ref('');

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.attachment = file;
        fileName.value = file.name;
    }
};

const removeFile = () => {
    form.attachment = null;
    fileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    form.post(route('support.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="t('new_support_request')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('support.index')"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('new_support_request') }}
                </h1>
            </div>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <!-- Category -->
                <div class="mb-6">
                    <InputLabel for="category" :value="t('category')" required />
                    <select
                        id="category"
                        v-model="form.category"
                        class="mt-1 block w-full rounded-xl border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm sm:leading-6"
                    >
                        <option v-for="(label, value) in categories" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.category" class="mt-2" />
                </div>

                <!-- Subject -->
                <div class="mb-6">
                    <InputLabel for="subject" :value="t('subject')" required />
                    <TextInput
                        id="subject"
                        v-model="form.subject"
                        type="text"
                        class="mt-1 block w-full"
                        :placeholder="t('subject_placeholder')"
                        required
                    />
                    <InputError :message="form.errors.subject" class="mt-2" />
                </div>

                <!-- Message -->
                <div class="mb-6">
                    <InputLabel for="message" :value="t('message')" required />
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="6"
                        class="mt-1 block w-full rounded-xl border-0 py-2 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:placeholder:text-slate-500 sm:text-sm sm:leading-6"
                        :placeholder="t('message_placeholder')"
                        required
                    />
                    <InputError :message="form.errors.message" class="mt-2" />
                </div>

                <!-- Attachment -->
                <div class="mb-6">
                    <InputLabel for="attachment" :value="t('attachment_optional')" />
                    <div class="mt-1">
                        <div v-if="fileName" class="flex items-center justify-between rounded-xl bg-slate-50 p-3 dark:bg-slate-700">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ fileName }}</span>
                            </div>
                            <button
                                type="button"
                                @click="removeFile"
                                class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-600"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </div>
                        <label
                            v-else
                            class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 p-6 text-slate-500 hover:border-primary-400 hover:text-primary-600 dark:border-slate-600 dark:hover:border-primary-500 dark:hover:text-primary-400"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span class="text-sm">{{ t('add_attachment') }}</span>
                            <input
                                ref="fileInput"
                                type="file"
                                id="attachment"
                                class="hidden"
                                @change="handleFileChange"
                                accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                            />
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t('attachment_hint') }}
                    </p>
                    <InputError :message="form.errors.attachment" class="mt-2" />
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('support.index')"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        {{ t('cancel') }}
                    </Link>
                    <PrimaryButton :disabled="form.processing">
                        {{ t('send_request') }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
