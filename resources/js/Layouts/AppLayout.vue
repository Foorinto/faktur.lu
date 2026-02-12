<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const showingSidebar = ref(true);
const page = usePage();

const stopImpersonation = () => {
    router.post(route('admin.impersonation.stop'));
};

const navigation = computed(() => [
    { name: t('dashboard'), href: 'dashboard', icon: 'chart-bar' },
    { name: t('clients'), href: 'clients.index', icon: 'users' },
    { name: t('quotes'), href: 'quotes.index', icon: 'clipboard-list' },
    { name: t('invoices'), href: 'invoices.index', icon: 'document-text' },
    { name: t('expenses'), href: 'expenses.index', icon: 'credit-card' },
    { name: t('time_tracking'), href: 'time-entries.index', icon: 'clock' },
    { name: t('projects'), href: 'projects.index', icon: 'folder' },
    { name: t('revenue_book'), href: 'reports.revenue-book', icon: 'book-open' },
    { name: t('faia_export'), href: 'exports.audit.index', icon: 'document-download' },
    { name: t('archive'), href: 'archive.index', icon: 'archive' },
    { name: t('settings'), href: 'settings.business.edit', icon: 'cog' },
]);

const isCurrentRoute = (routeName) => {
    try {
        return route().current(routeName) || route().current(routeName + '.*');
    } catch {
        return false;
    }
};

const routeExists = (routeName) => {
    try {
        route(routeName);
        return true;
    } catch {
        return false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
        <!-- Background decorations -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-500/5 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -left-40 w-96 h-96 bg-accent-blue/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 right-1/3 w-80 h-80 bg-accent-pink/5 rounded-full blur-3xl"></div>
        </div>

        <!-- Impersonation Banner -->
        <div
            v-if="page.props.impersonating"
            class="fixed top-0 left-0 right-0 z-[100] bg-primary-600 px-4 py-2 text-center text-sm text-white"
        >
            <span>
                {{ t('logged_in_as') }} <strong>{{ page.props.impersonating.user_name }}</strong>
            </span>
            <button
                @click="stopImpersonation"
                class="ml-4 rounded-lg bg-white px-3 py-1 text-sm font-medium text-primary-600 hover:bg-primary-50 transition-colors"
            >
                {{ t('return_to_admin') }}
            </button>
        </div>

        <!-- Sidebar -->
        <aside
            :class="[
                showingSidebar ? 'translate-x-0' : '-translate-x-full',
                page.props.impersonating ? 'top-10' : 'top-0',
                'fixed inset-y-0 left-0 z-50 w-64 transform bg-white border-r border-slate-200 transition-transform duration-300 ease-in-out dark:bg-slate-800 dark:border-slate-700 lg:translate-x-0',
            ]"
        >
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-16 items-center justify-between border-b border-slate-200 px-4 dark:border-slate-700">
                    <Link :href="route('dashboard')" class="flex items-center space-x-2.5">
                        <div class="bg-primary-500 p-2 rounded-xl">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-slate-900 dark:text-white">faktur.lu</span>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 space-y-1 px-3 py-4">
                    <template v-for="item in navigation" :key="item.name">
                        <Link
                            v-if="routeExists(item.href)"
                            :href="route(item.href)"
                            :class="[
                                isCurrentRoute(item.href)
                                    ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200',
                                'group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
                            ]"
                        >
                            <!-- Dashboard Icon -->
                            <svg v-if="item.icon === 'chart-bar'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Users Icon -->
                            <svg v-else-if="item.icon === 'users'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Clipboard List Icon (Devis) -->
                            <svg v-else-if="item.icon === 'clipboard-list'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <!-- Document Icon -->
                            <svg v-else-if="item.icon === 'document-text'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <!-- Credit Card Icon -->
                            <svg v-else-if="item.icon === 'credit-card'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <!-- Clock Icon -->
                            <svg v-else-if="item.icon === 'clock'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Folder Icon (Projects) -->
                            <svg v-else-if="item.icon === 'folder'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <!-- Book Open Icon (Recettes) -->
                            <svg v-else-if="item.icon === 'book-open'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <!-- Document Download Icon (Export FAIA) -->
                            <svg v-else-if="item.icon === 'document-download'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <!-- Archive Icon (Archivage) -->
                            <svg v-else-if="item.icon === 'archive'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            <!-- Cog Icon -->
                            <svg v-else-if="item.icon === 'cog'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ item.name }}
                        </Link>
                        <span
                            v-else
                            class="group flex cursor-not-allowed items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 dark:text-slate-600"
                        >
                            <!-- Icons same as above but grayed out -->
                            <svg v-if="item.icon === 'chart-bar'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'users'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'clipboard-list'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <svg v-else-if="item.icon === 'document-text'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'credit-card'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <svg v-else-if="item.icon === 'clock'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'folder'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'document-download'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'archive'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            <svg v-else-if="item.icon === 'cog'" class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ item.name }}
                        </span>
                    </template>
                </nav>

                <!-- User menu at bottom -->
                <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                    <Dropdown align="top-left" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                class="flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors dark:text-slate-300 dark:hover:bg-slate-700"
                            >
                                <div class="relative flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-900/30">
                                    <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                    <span
                                        v-if="$page.props.unreadSupportCount > 0"
                                        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-pink-500 text-[10px] font-bold text-white"
                                    >
                                        {{ $page.props.unreadSupportCount > 9 ? '9+' : $page.props.unreadSupportCount }}
                                    </span>
                                </div>
                                <div class="ml-3 flex-1 text-left">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $page.props.auth.user.name }}
                                    </p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ $page.props.auth.user.email }}
                                    </p>
                                </div>
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">
                                {{ t('profile') }}
                            </DropdownLink>
                            <DropdownLink :href="route('support.index')" class="flex items-center justify-between">
                                <span>{{ t('support') }}</span>
                                <span
                                    v-if="$page.props.unreadSupportCount > 0"
                                    class="ml-2 inline-flex items-center justify-center rounded-full bg-pink-500 px-2 py-0.5 text-xs font-medium text-white"
                                >
                                    {{ $page.props.unreadSupportCount }}
                                </span>
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                {{ t('logout') }}
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </aside>

        <!-- Mobile sidebar backdrop -->
        <div
            v-show="showingSidebar"
            class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden"
            @click="showingSidebar = false"
        ></div>

        <!-- Main content -->
        <div :class="['lg:pl-64 relative', page.props.impersonating ? 'pt-10' : '']">
            <!-- Top bar -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 backdrop-blur-md px-4 dark:border-slate-700 dark:bg-slate-800/80 sm:px-6 lg:px-8">
                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="text-slate-500 hover:text-slate-700 lg:hidden dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                    @click="showingSidebar = !showingSidebar"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page title slot -->
                <div class="flex-1 lg:ml-0">
                    <slot name="header" />
                </div>

                <!-- Right side actions -->
                <div class="flex items-center space-x-4">
                    <ThemeToggle />
                </div>
            </header>

            <!-- Page content -->
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
