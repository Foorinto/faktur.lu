<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();

const navigation = [
    { name: 'Dashboard', href: 'admin.dashboard', icon: 'chart-bar' },
    { name: 'Utilisateurs', href: 'admin.users.index', icon: 'users' },
    { name: 'Support', href: 'admin.support.index', icon: 'support' },
    { name: 'Maintenance', href: 'admin.maintenance', icon: 'cog' },
];

const sidebarOpen = ref(false);

const logout = () => {
    router.post(route('admin.logout'));
};

const isCurrentRoute = (routeName) => {
    return route().current(routeName);
};
</script>

<template>
    <div class="min-h-screen bg-slate-900">
        <!-- Mobile sidebar backdrop -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/80 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 transform bg-slate-800 transition-transform duration-300 ease-in-out lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo -->
            <div class="flex h-16 items-center justify-center border-b border-slate-700">
                <span class="text-xl font-bold text-purple-400">faktur.lu Admin</span>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-4">
                <ul class="space-y-2">
                    <li v-for="item in navigation" :key="item.name">
                        <Link
                            :href="route(item.href)"
                            :class="[
                                'flex items-center rounded-lg px-4 py-3 text-sm font-medium transition-colors',
                                isCurrentRoute(item.href)
                                    ? 'bg-purple-600 text-white'
                                    : 'text-slate-300 hover:bg-slate-700 hover:text-white',
                            ]"
                        >
                            <!-- Icons -->
                            <svg v-if="item.icon === 'chart-bar'" class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <svg v-else-if="item.icon === 'users'" class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            <svg v-else-if="item.icon === 'support'" class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                            <svg v-else-if="item.icon === 'cog'" class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- Logout button -->
            <div class="absolute bottom-0 left-0 right-0 border-t border-slate-700 p-4">
                <button
                    @click="logout"
                    class="flex w-full items-center rounded-lg px-4 py-3 text-sm font-medium text-slate-300 transition-colors hover:bg-slate-700 hover:text-white"
                >
                    <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Déconnexion
                </button>
            </div>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-700 bg-slate-800 px-4 lg:px-6">
                <!-- Mobile menu button -->
                <button
                    @click="sidebarOpen = true"
                    class="text-slate-400 hover:text-white lg:hidden"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page title slot -->
                <div class="flex-1">
                    <slot name="header" />
                </div>

                <!-- Admin badge -->
                <span class="rounded-full bg-purple-600 px-3 py-1 text-xs font-medium text-white">
                    Admin
                </span>
            </header>

            <!-- Page content -->
            <main class="p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
