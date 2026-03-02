<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useAvatarColor } from '@/Composables/useAvatarColor';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const { t } = useTranslations();
const { getAvatarClasses } = useAvatarColor();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const mobileMenuOpen = ref(false);

const navigation = [
    { name: () => t('employee_portal.dashboard'), href: 'employee-portal.dashboard', icon: 'home' },
    { name: () => t('employee_portal.my_leaves'), href: 'employee-portal.leaves.index', icon: 'calendar' },
    { name: () => t('employee_portal.my_expenses'), href: 'employee-portal.expenses.index', icon: 'receipt' },
    { name: () => t('employee_portal.my_documents'), href: 'employee-portal.documents.index', icon: 'folder' },
    { name: () => t('employee_portal.my_evaluations'), href: 'employee-portal.evaluations.index', icon: 'clipboard' },
    { name: () => t('employee_portal.my_profile'), href: 'employee-portal.profile.edit', icon: 'user' },
];

const isActive = (routeName) => route().current(routeName + '*') || route().current(routeName);

const logout = () => {
    router.post(route('logout'));
};

const userInitial = computed(() => user.value?.name?.charAt(0)?.toUpperCase() || '?');
</script>

<template>
    <div class="min-h-screen flex flex-col bg-gray-50 dark:bg-surface-dark">
        <!-- Header -->
        <header class="bg-white dark:bg-surface-card border-b border-gray-200 dark:border-gray-700">
            <!-- Top bar: logo + user -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <Link :href="route('employee-portal.dashboard')" class="flex items-center gap-3">
                        <ApplicationLogo size="sm" />
                        <span class="hidden sm:inline text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded-full">{{ t('employee_portal.nav_title') }}</span>
                    </Link>

                    <div class="flex items-center gap-3">
                        <ThemeToggle />

                        <!-- User menu -->
                        <div class="hidden sm:flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div :class="['flex h-8 w-8 items-center justify-center rounded-full', getAvatarClasses(user?.name)]">
                                    <span class="text-sm font-bold">{{ userInitial }}</span>
                                </div>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300 max-w-[150px] truncate">{{ user?.name }}</span>
                            </div>
                            <button
                                @click="logout"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-gray-50 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-gray-800 transition-colors"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                {{ t('logout') }}
                            </button>
                        </div>

                        <!-- Mobile menu button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop navigation tabs -->
            <nav class="hidden md:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex gap-1 -mb-px">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="route(item.href)"
                        class="flex items-center gap-1.5 px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="isActive(item.href.replace('.index', '').replace('.edit', ''))
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'"
                    >
                        <!-- Home -->
                        <svg v-if="item.icon === 'home'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <!-- Calendar -->
                        <svg v-else-if="item.icon === 'calendar'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <!-- Receipt -->
                        <svg v-else-if="item.icon === 'receipt'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                        <!-- Folder -->
                        <svg v-else-if="item.icon === 'folder'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <!-- Clipboard -->
                        <svg v-else-if="item.icon === 'clipboard'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <!-- User -->
                        <svg v-else-if="item.icon === 'user'" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ item.name() }}
                    </Link>
                </div>
            </nav>

            <!-- Mobile navigation -->
            <nav v-if="mobileMenuOpen" class="md:hidden border-t border-gray-200 dark:border-gray-700">
                <div class="px-4 py-3 space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="route(item.href)"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="isActive(item.href.replace('.index', '').replace('.edit', ''))
                            ? 'bg-accent-rose text-white dark:bg-accent-rose dark:text-white'
                            : 'text-slate-600 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-gray-800'"
                        @click="mobileMenuOpen = false"
                    >
                        <!-- Home -->
                        <svg v-if="item.icon === 'home'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <!-- Calendar -->
                        <svg v-else-if="item.icon === 'calendar'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <!-- Receipt -->
                        <svg v-else-if="item.icon === 'receipt'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                        <!-- Folder -->
                        <svg v-else-if="item.icon === 'folder'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <!-- Clipboard -->
                        <svg v-else-if="item.icon === 'clipboard'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <!-- User -->
                        <svg v-else-if="item.icon === 'user'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ item.name() }}
                    </Link>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div :class="['flex h-8 w-8 items-center justify-center rounded-full', getAvatarClasses(user?.name)]">
                            <span class="text-sm font-bold">{{ userInitial }}</span>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ user?.name }}</span>
                    </div>
                    <button @click="logout" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400">
                        {{ t('logout') }}
                    </button>
                </div>
            </nav>
        </header>

        <!-- Flash messages -->
        <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="flex items-center rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-4">
                <svg class="h-5 w-5 text-emerald-500 dark:text-emerald-400 mr-3 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ $page.props.flash.success }}</p>
            </div>
        </div>
        <div v-if="$page.props.flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="flex items-center rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 p-4">
                <svg class="h-5 w-5 text-rose-500 dark:text-rose-400 mr-3 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-rose-700 dark:text-rose-400">{{ $page.props.flash.error }}</p>
            </div>
        </div>

        <!-- Main content -->
        <main class="flex-1 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-sm text-slate-400 dark:text-slate-500">
                    faktur.lu - {{ t('employee_portal.nav_title') }}
                </p>
            </div>
        </footer>
    </div>
</template>
