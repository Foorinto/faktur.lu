<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ToastNotification from '@/Components/ToastNotification.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();
const page = usePage();
const user = computed(() => page.props.auth?.user);
const mobileMenuOpen = ref(false);

const navigation = [
    { name: t('collaborator_dashboard'), href: 'collaborator.dashboard', icon: 'home' },
    { name: t('my_projects'), href: 'collaborator.projects.index', icon: 'folder' },
    { name: t('my_time'), href: 'collaborator.time.index', icon: 'clock' },
];

const isActive = (routeName) => route().current(routeName + '*');

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-surface-dark">
        <!-- Header -->
        <header class="bg-white dark:bg-surface-card shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <Link :href="route('collaborator.dashboard')" class="flex items-center">
                            <ApplicationLogo size="sm" />
                        </Link>
                        <span class="text-sm text-slate-500 dark:text-slate-400 hidden sm:inline">{{ t('collaborator_space') }}</span>
                    </div>

                    <!-- Desktop navigation -->
                    <nav class="hidden md:flex items-center space-x-1">
                        <Link
                            v-for="item in navigation"
                            :key="item.href"
                            :href="route(item.href)"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="isActive(item.href.replace('.index', ''))
                                ? 'bg-accent-rose text-white dark:bg-accent-rose dark:text-white'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-gray-50 dark:text-slate-300 dark:hover:bg-gray-800'"
                        >
                            {{ item.name }}
                        </Link>
                    </nav>

                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-slate-600 dark:text-slate-300 hidden sm:inline">{{ user?.name }}</span>
                        <button
                            @click="logout"
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-slate-600"
                        >
                            {{ t('logout') }}
                        </button>

                        <!-- Mobile menu button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile navigation -->
                <nav v-if="mobileMenuOpen" class="md:hidden pb-4 space-y-1">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="route(item.href)"
                        class="block px-3 py-2 rounded-lg text-sm font-medium"
                        :class="isActive(item.href.replace('.index', ''))
                            ? 'bg-accent-rose text-white'
                            : 'text-slate-600 hover:bg-gray-50'"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Flash messages (toasts fixes en haut a droite) -->
        <ToastNotification />

        <!-- Main content -->
        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-surface-card border-t border-gray-200 dark:border-gray-700 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                    faktur.lu - {{ t('collaborator_space') }}
                </p>
            </div>
        </footer>
    </div>
</template>
