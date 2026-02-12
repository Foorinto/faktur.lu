<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import { watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: false,
            codeBlock: false,
            code: false,
            blockquote: false,
            horizontalRule: false,
        }),
        Underline,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none min-h-[100px] px-3 py-2 text-slate-900',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
    if (editor.value && newValue !== editor.value.getHTML()) {
        editor.value.commands.setContent(newValue || '', false);
    }
});
</script>

<template>
    <div class="rich-text-editor rounded-lg border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800 overflow-hidden">
        <!-- Toolbar -->
        <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5 dark:border-slate-600 dark:bg-slate-700/50">
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="[
                    'rounded p-1.5 transition-colors',
                    editor.isActive('bold')
                        ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                        : 'text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 dark:hover:text-slate-200'
                ]"
                title="Gras (Ctrl+B)"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 11h4.5a2.5 2.5 0 1 0 0-5H8v5Zm10 4.5a4.5 4.5 0 0 1-4.5 4.5H6V4h6.5a4.5 4.5 0 0 1 3.256 7.606A4.498 4.498 0 0 1 18 15.5ZM8 13v5h5.5a2.5 2.5 0 1 0 0-5H8Z"/>
                </svg>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="[
                    'rounded p-1.5 transition-colors',
                    editor.isActive('italic')
                        ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                        : 'text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 dark:hover:text-slate-200'
                ]"
                title="Italique (Ctrl+I)"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15 20H7v-2h2.927l2.116-12H9V4h8v2h-2.927l-2.116 12H15v2Z"/>
                </svg>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleUnderline().run()"
                :class="[
                    'rounded p-1.5 transition-colors',
                    editor.isActive('underline')
                        ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                        : 'text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 dark:hover:text-slate-200'
                ]"
                title="Souligné (Ctrl+U)"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 3v9a4 4 0 1 0 8 0V3h2v9a6 6 0 1 1-12 0V3h2ZM4 20h16v2H4v-2Z"/>
                </svg>
            </button>

            <div class="mx-1 h-5 w-px bg-slate-300 dark:bg-slate-600"></div>

            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="[
                    'rounded p-1.5 transition-colors',
                    editor.isActive('bulletList')
                        ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                        : 'text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 dark:hover:text-slate-200'
                ]"
                title="Liste à puces"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 4h13v2H8V4ZM4.5 6.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 7a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm0 6.9a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3ZM8 11h13v2H8v-2Zm0 7h13v2H8v-2Z"/>
                </svg>
            </button>
            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="[
                    'rounded p-1.5 transition-colors',
                    editor.isActive('orderedList')
                        ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                        : 'text-slate-500 hover:bg-slate-200 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-600 dark:hover:text-slate-200'
                ]"
                title="Liste numérotée"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8 4h13v2H8V4ZM5 3v3h1v1H3V6h1V4H3V3h2Zm-2 7h3.5v1H4v1h1.5v1H3v-4h3v1H4v.5H3Zm2 5H3v1h2v1H3v1h2v1H3v1h3v-5H5ZM8 11h13v2H8v-2Zm0 7h13v2H8v-2Z"/>
                </svg>
            </button>
        </div>

        <!-- Editor content -->
        <div class="bg-white">
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>

<style>
/* Style the editor content area */
.rich-text-editor .ProseMirror {
    min-height: 100px;
    outline: none;
}

.rich-text-editor .ProseMirror p {
    margin: 0.5em 0;
}

.rich-text-editor .ProseMirror p:first-child {
    margin-top: 0;
}

.rich-text-editor .ProseMirror p:last-child {
    margin-bottom: 0;
}

.rich-text-editor .ProseMirror ul,
.rich-text-editor .ProseMirror ol {
    padding-left: 1.5em;
    margin: 0.5em 0;
}

.rich-text-editor .ProseMirror li {
    margin: 0.25em 0;
}

.rich-text-editor .ProseMirror ul {
    list-style-type: disc;
}

.rich-text-editor .ProseMirror ol {
    list-style-type: decimal;
}

/* Placeholder */
.rich-text-editor .ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
</style>
