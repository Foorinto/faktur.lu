<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import ReadOnlyBanner from '@/Components/ReadOnlyBanner.vue';
import FreePlanBanner from '@/Components/FreePlanBanner.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import HelpTourButton from '@/Components/HelpTourButton.vue';
import SupportButton from '@/Components/SupportButton.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useAvatarColor } from '@/Composables/useAvatarColor';
import { useTour } from '@/Composables/useTour';

const { t } = useTranslations();
const { getAvatarClasses } = useAvatarColor();
const { startDashboardTour, resetTours } = useTour();
const showingSidebar = ref(false);
const showSearch = ref(false);
const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === 'true');
const page = usePage();

const toggleCollapse = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebar-collapsed', sidebarCollapsed.value);
};

const stopImpersonation = () => {
    router.post(route('admin.impersonation.stop'));
};

const restartTour = () => {
    resetTours();
    if (route().current('dashboard')) {
        startDashboardTour(true);
    } else {
        router.visit(route('dashboard'));
    }
};

const navigation = computed(() => {
    const items = [
        { name: t('dashboard'), href: 'dashboard', icon: 'chart-bar', tour: 'dashboard' },
        { name: t('clients'), href: 'clients.index', icon: 'users', tour: 'clients' },
        { name: t('billing'), href: 'invoices.index', icon: 'document-text', routes: ['quotes', 'invoices', 'recurring-invoices'], tour: 'billing' },
        { name: t('expenses'), href: 'expenses.index', icon: 'credit-card' },
        { name: t('productivity'), href: 'time-entries.index', icon: 'clock', routes: ['time-entries', 'projects'] },
        { name: t('accounting'), href: 'reports.revenue-book', icon: 'calculator', routes: ['reports', 'exports'], tour: 'accounting' },
        { name: t('hr.nav_title'), href: 'hr.dashboard', icon: 'identification' },
        { name: t('archive'), href: 'archive.index', icon: 'archive' },
        { name: t('business'), href: 'settings.business.edit', icon: 'building', routes: ['settings.business', 'settings.organization'], tour: 'settings' },
        { name: t('settings'), href: 'settings.email', icon: 'cog', routes: ['settings.email', 'settings.accountant', 'subscription'] },
    ];

    if (page.props.auth?.user?.is_employee) {
        items.push({ name: t('employee_portal.nav_title'), href: 'employee-portal.dashboard', icon: 'user-circle' });
    }

    return items;
});

const isCurrentRoute = (routeName, routes) => {
    try {
        if (routes) {
            return routes.some(r => route().current(r) || route().current(r + '.*'));
        }
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
    <div class="min-h-screen bg-gray-50 dark:bg-surface-dark">
        <!-- Background decorations -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-500/5 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -left-40 w-96 h-96 bg-accent-blue/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 right-1/3 w-80 h-80 bg-accent-rose/5 rounded-full blur-3xl"></div>
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

        <!-- Read-only Banner (trial expired) -->
        <div
            v-if="page.props.auth?.user?.is_read_only"
            :class="[page.props.impersonating ? 'top-10' : 'top-0']"
            class="fixed left-0 right-0 z-[99]"
        >
            <ReadOnlyBanner />
        </div>

        <!-- Sidebar -->
        <aside
            :class="[
                showingSidebar ? 'translate-x-0' : '-translate-x-full',
                page.props.impersonating ? 'top-10' : 'top-0',
                sidebarCollapsed ? 'w-16' : 'w-64',
                'fixed inset-y-0 left-0 z-50 transform bg-white border-r border-gray-200 transition-all duration-300 ease-in-out dark:bg-surface-card dark:border-gray-700 lg:translate-x-0',
            ]"
        >
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-16 items-center border-b border-gray-200 dark:border-gray-700" :class="sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4'">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo v-if="!sidebarCollapsed" size="sm" />
                        <span v-else class="text-lg font-bold text-primary-500">F</span>
                    </Link>
                    <button
                        v-if="!sidebarCollapsed"
                        @click="toggleCollapse"
                        class="hidden lg:flex items-center justify-center rounded-lg p-1.5 text-slate-400 hover:bg-gray-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-300 transition-colors"
                        :title="t('collapse_sidebar') || 'Réduire'"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 space-y-1 py-4 overflow-y-auto" :class="sidebarCollapsed ? 'px-2' : 'px-3'">
                    <template v-for="item in navigation" :key="item.name">
                        <Link
                            v-if="routeExists(item.href)"
                            :href="route(item.href)"
                            :title="sidebarCollapsed ? item.name : undefined"
                            :data-tour="item.tour ? `nav-${item.tour}` : undefined"
                            :class="[
                                isCurrentRoute(item.href, item.routes)
                                    ? 'bg-accent-rose text-white dark:bg-accent-rose dark:text-white'
                                    : 'text-slate-600 hover:bg-gray-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-gray-800 dark:hover:text-slate-200',
                                sidebarCollapsed
                                    ? 'group flex items-center justify-center rounded-xl p-2.5 text-sm font-medium transition-all duration-200'
                                    : 'group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
                            ]"
                        >
                            <!-- Dashboard Icon -->
                            <svg v-if="item.icon === 'chart-bar'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Users Icon -->
                            <svg v-else-if="item.icon === 'users'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Document Icon (Facturation) -->
                            <svg v-else-if="item.icon === 'document-text'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <!-- Credit Card Icon -->
                            <svg v-else-if="item.icon === 'credit-card'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <!-- Clock Icon (Productivité) -->
                            <svg v-else-if="item.icon === 'clock'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <!-- Calculator Icon (Comptabilité) -->
                            <svg v-else-if="item.icon === 'calculator'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <!-- Identification Icon (RH) -->
                            <svg v-else-if="item.icon === 'identification'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                            </svg>
                            <!-- Archive Icon (Archivage) -->
                            <svg v-else-if="item.icon === 'archive'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            <!-- Building Icon (Entreprise) -->
                            <svg v-else-if="item.icon === 'building'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <!-- Cog Icon -->
                            <svg v-else-if="item.icon === 'cog'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <!-- User Circle Icon (Employee Portal) -->
                            <svg v-else-if="item.icon === 'user-circle'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                        </Link>
                        <span
                            v-else
                            :title="sidebarCollapsed ? item.name : undefined"
                            :class="[
                                sidebarCollapsed
                                    ? 'group flex cursor-not-allowed items-center justify-center rounded-xl p-2.5 text-sm font-medium text-slate-400 dark:text-slate-600'
                                    : 'group flex cursor-not-allowed items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-400 dark:text-slate-600',
                            ]"
                        >
                            <!-- Icons same as above but grayed out -->
                            <svg v-if="item.icon === 'chart-bar'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'users'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'document-text'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'credit-card'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <svg v-else-if="item.icon === 'clock'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'calculator'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'identification'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'archive'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                            <svg v-else-if="item.icon === 'building'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <svg v-else-if="item.icon === 'cog'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'user-circle'" :class="[sidebarCollapsed ? '' : 'mr-3', 'h-5 w-5 flex-shrink-0']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                        </span>
                    </template>
                </nav>

                <!-- Trial Card -->
                <div v-if="page.props.auth?.user?.is_on_trial && !sidebarCollapsed" class="px-3 pb-3">
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm font-semibold text-amber-800 dark:text-amber-200">Période d'essai</span>
                        </div>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mb-1">
                            <strong>{{ page.props.auth.user.trial_days_remaining }}</strong> jours restants
                        </p>
                        <p class="text-xs text-amber-600 dark:text-amber-400 mb-3">
                            Expire le {{ new Date(page.props.auth.user.trial_ends_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' }) }}
                        </p>
                        <Link
                            :href="route('subscription.index')"
                            class="block w-full text-center text-xs font-medium px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white transition-colors"
                        >
                            Choisir un abonnement
                        </Link>
                    </div>
                </div>

                <!-- Expand button when collapsed -->
                <div v-if="sidebarCollapsed" class="hidden lg:flex justify-center px-2 pb-2">
                    <button
                        @click="toggleCollapse"
                        class="flex items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-gray-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-300 transition-colors"
                        :title="'Agrandir'"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- User menu at bottom -->
                <div data-tour="user-menu" class="border-t border-gray-200 dark:border-gray-700" :class="sidebarCollapsed ? 'p-2' : 'p-4'">
                    <Dropdown :align="sidebarCollapsed ? 'top-right' : 'top-left'" width="48">
                        <template #trigger>
                            <button
                                type="button"
                                :class="sidebarCollapsed
                                    ? 'flex w-full items-center justify-center rounded-xl p-2 text-sm font-medium text-slate-700 hover:bg-gray-50 transition-colors dark:text-slate-300 dark:hover:bg-gray-800'
                                    : 'flex w-full items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-gray-50 transition-colors dark:text-slate-300 dark:hover:bg-gray-800'"
                            >
                                <div :class="['relative flex h-9 w-9 items-center justify-center rounded-xl flex-shrink-0', getAvatarClasses($page.props.auth.user.name)]">
                                    <span class="text-sm font-bold">
                                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                    <span
                                        v-if="$page.props.unreadSupportCount > 0"
                                        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-pink-500 text-[10px] font-bold text-white"
                                    >
                                        {{ $page.props.unreadSupportCount > 9 ? '9+' : $page.props.unreadSupportCount }}
                                    </span>
                                </div>
                                <template v-if="!sidebarCollapsed">
                                    <div class="ml-3 flex-1 min-w-0 text-left">
                                        <p class="truncate text-sm font-medium text-slate-900 dark:text-white">
                                            {{ $page.props.auth.user.name }}
                                        </p>
                                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                            {{ $page.props.auth.user.email }}
                                        </p>
                                    </div>
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                    </svg>
                                </template>
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
                            <button
                                @click="restartTour"
                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-slate-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
                            >
                                Relancer le tour guidé
                            </button>
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
        <div :class="[sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-64', 'relative transition-all duration-300', page.props.impersonating ? 'pt-10' : '']">
            <!-- Free Plan Banner -->
            <div
                v-if="page.props.auth?.user?.is_free"
                class="sticky top-0 z-40"
            >
                <FreePlanBanner />
            </div>
            <!-- Top bar -->
            <header :class="[page.props.auth?.user?.is_free ? 'top-[52px]' : 'top-0']" class="sticky z-30 border-b border-gray-200 bg-white/80 backdrop-blur-md dark:border-gray-700 dark:bg-surface-card/80 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center">
                    <!-- Mobile menu button -->
                    <button
                        type="button"
                        class="order-1 flex h-16 items-center pr-3 text-slate-500 hover:text-slate-700 lg:hidden dark:text-slate-400 dark:hover:text-slate-200 transition-colors"
                        @click="showingSidebar = !showingSidebar"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Page title -->
                    <div class="order-2 flex h-16 flex-1 items-center min-w-0">
                        <slot name="header" />
                    </div>

                    <!-- Page actions (line 2 on mobile, inline on sm+) -->
                    <div
                        v-if="$slots['header-actions']"
                        class="order-4 w-full pb-3 sm:order-3 sm:w-auto sm:pb-0 sm:ml-4 sm:flex sm:h-16 sm:items-center sm:flex-shrink-0"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <slot name="header-actions" />
                        </div>
                    </div>

                    <!-- Search + Reminders bell + Theme toggle -->
                    <div class="order-3 flex h-16 items-center flex-shrink-0 ml-4 gap-2 sm:order-4">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg p-2 text-slate-500 hover:bg-gray-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-gray-800 dark:hover:text-slate-300 transition-colors"
                            :title="t('global_search')"
                            @click="showSearch = true"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <span class="hidden text-xs text-slate-400 dark:text-slate-500 sm:inline">
                                <kbd class="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 font-mono text-[10px] dark:border-gray-600 dark:bg-gray-700">⌘K</kbd>
                            </span>
                        </button>
                        <HelpTourButton />
                        <Link
                            v-if="routeExists('reminders.index')"
                            :href="route('reminders.index')"
                            class="relative flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-gray-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-gray-800 dark:hover:text-slate-300 transition-colors"
                            :title="t('crm.reminders')"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span
                                v-if="$page.props.pendingRemindersCount > 0"
                                class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-accent-rose text-[10px] font-bold text-white"
                            >
                                {{ $page.props.pendingRemindersCount > 9 ? '9+' : $page.props.pendingRemindersCount }}
                            </span>
                        </Link>
                        <ThemeToggle />
                    </div>
                </div>
            </header>

            <!-- Flash messages (toasts fixes en haut a droite) -->
            <ToastNotification />

            <!-- Page content -->
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>

    <!-- Global search modal -->
    <GlobalSearchModal :show="showSearch" @close="showSearch = false" @open="showSearch = true" />
    <SupportButton />
</template>
