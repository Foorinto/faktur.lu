<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    node: { type: Object, required: true },
    depth: { type: Number, default: 0 },
});
</script>

<template>
    <div class="flex flex-col items-center">
        <!-- Node card -->
        <Link
            :href="route('hr.employees.show', node.id)"
            class="group relative rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm transition-all hover:shadow-md hover:border-primary-300 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-primary-600"
            :class="depth === 0 ? 'min-w-[180px]' : 'min-w-[160px]'"
        >
            <!-- Photo or initials -->
            <div class="mx-auto mb-2" :class="depth === 0 ? 'h-16 w-16' : 'h-12 w-12'">
                <img
                    v-if="node.photo_path"
                    :src="`/storage/${node.photo_path}`"
                    :alt="node.full_name"
                    class="rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-700"
                    :class="depth === 0 ? 'h-16 w-16' : 'h-12 w-12'"
                />
                <div
                    v-else
                    class="flex items-center justify-center rounded-full bg-primary-100 ring-2 ring-primary-50 dark:bg-primary-900/30 dark:ring-primary-900/50"
                    :class="depth === 0 ? 'h-16 w-16' : 'h-12 w-12'"
                >
                    <span
                        class="font-semibold text-primary-600 dark:text-primary-400"
                        :class="depth === 0 ? 'text-base' : 'text-sm'"
                    >
                        {{ node.first_name?.charAt(0) }}{{ node.last_name?.charAt(0) }}
                    </span>
                </div>
            </div>

            <!-- Name -->
            <h3 class="text-sm font-semibold text-slate-900 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400">
                {{ node.full_name }}
            </h3>

            <!-- Job title -->
            <p v-if="node.job_title" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                {{ node.job_title }}
            </p>

            <!-- Department -->
            <p v-if="node.department" class="mt-1 flex items-center justify-center gap-1 text-xs text-slate-500 dark:text-slate-400">
                <span
                    v-if="node.department.color"
                    class="inline-block h-2 w-2 rounded-full"
                    :style="{ backgroundColor: node.department.color }"
                ></span>
                {{ node.department.name }}
            </p>

            <!-- Subordinates count -->
            <p v-if="node.children && node.children.length > 0" class="mt-1 text-xs font-medium text-primary-500 dark:text-primary-400">
                {{ node.children.length }} subordonné{{ node.children.length > 1 ? 's' : '' }}
            </p>
        </Link>

        <!-- Connector line down from parent -->
        <div v-if="node.children && node.children.length > 0" class="flex flex-col items-center">
            <div class="h-6 w-px bg-slate-300 dark:bg-slate-600"></div>

            <!-- Horizontal connector -->
            <div class="relative flex items-start">
                <!-- Horizontal line spanning all children -->
                <div
                    v-if="node.children.length > 1"
                    class="absolute top-0 h-px bg-slate-300 dark:bg-slate-600"
                    :style="{
                        left: `calc(50% / ${node.children.length})`,
                        right: `calc(50% / ${node.children.length})`,
                    }"
                ></div>

                <!-- Children -->
                <div class="flex gap-6">
                    <div
                        v-for="child in node.children"
                        :key="child.id"
                        class="flex flex-col items-center"
                    >
                        <!-- Vertical connector from horizontal line to child -->
                        <div class="h-6 w-px bg-slate-300 dark:bg-slate-600"></div>

                        <!-- Recursive child node -->
                        <OrgNode :node="child" :depth="depth + 1" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
