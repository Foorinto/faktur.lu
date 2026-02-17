<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useTranslations } from '@/Composables/useTranslations';

const { localizedRoute, currentLocale, availableLocales } = useLocalizedRoute();
const { t } = useTranslations();

const mobileMenuOpen = ref(false);
const langMenuOpen = ref(false);
const langMenuRef = ref(null);
const page = usePage();

const canLogin = page.props.canLogin ?? true;
const canRegister = page.props.canRegister ?? true;

// Locale flags/emojis
const localeFlags = {
    fr: '🇫🇷',
    de: '🇩🇪',
    en: '🇬🇧',
    lb: '🇱🇺',
};

// Switch locale
const switchLocale = (newLocale) => {
    langMenuOpen.value = false;
    // Navigate to the same page but with new locale
    router.visit(route('locale.switch', { locale: newLocale }), {
        preserveState: false,
    });
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (langMenuRef.value && !langMenuRef.value.contains(event.target)) {
        langMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200">
            <nav class="mx-auto max-w-6xl px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <Link :href="localizedRoute('home')" class="flex items-center space-x-2.5">
                        <div class="bg-[#9b5de5] p-2 rounded-xl">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-bold text-slate-900">faktur.lu</span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-6">
                        <Link :href="localizedRoute('home') + '#features'" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.features') }}
                        </Link>
                        <Link :href="localizedRoute('home') + '#how-it-works'" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.how_it_works') }}
                        </Link>
                        <Link :href="localizedRoute('home') + '#pricing'" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.pricing') }}
                        </Link>
                        <Link :href="localizedRoute('home') + '#faq'" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.faq') }}
                        </Link>
                        <Link :href="localizedRoute('faia-validator')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.faia_validator') }}
                        </Link>
                        <Link :href="localizedRoute('blog.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            {{ t('landing.nav.blog') }}
                        </Link>
                    </div>

                    <!-- Desktop CTA -->
                    <div class="hidden md:flex items-center space-x-4">
                        <!-- Language Selector -->
                        <div ref="langMenuRef" class="relative">
                            <button
                                @click.stop="langMenuOpen = !langMenuOpen"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                            >
                                <span class="text-base">{{ localeFlags[currentLocale()] }}</span>
                                <span class="uppercase text-xs">{{ currentLocale() }}</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': langMenuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Dropdown -->
                            <Transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-if="langMenuOpen"
                                    class="absolute right-0 mt-2 w-40 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50"
                                >
                                    <button
                                        v-for="(name, code) in availableLocales()"
                                        :key="code"
                                        @click="switchLocale(code)"
                                        :class="[
                                            'w-full flex items-center gap-2.5 px-3 py-2 text-sm transition-colors',
                                            currentLocale() === code
                                                ? 'bg-[#9b5de5]/10 text-[#9b5de5] font-medium'
                                                : 'text-slate-700 hover:bg-slate-50'
                                        ]"
                                    >
                                        <span class="text-base">{{ localeFlags[code] }}</span>
                                        <span>{{ name }}</span>
                                        <svg v-if="currentLocale() === code" class="w-4 h-4 ml-auto" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            {{ t('landing.nav.dashboard') }}
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                            >
                                {{ t('landing.nav.login') }}
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-[#9b5de5] hover:bg-[#8b4ed5] text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
                            >
                                {{ t('landing.nav.free_trial') }}
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile menu -->
                <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-slate-200 mt-4">
                    <div class="flex flex-col space-y-3">
                        <Link :href="localizedRoute('home') + '#features'" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.features') }}</Link>
                        <Link :href="localizedRoute('home') + '#how-it-works'" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.how_it_works') }}</Link>
                        <Link :href="localizedRoute('home') + '#pricing'" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.pricing') }}</Link>
                        <Link :href="localizedRoute('home') + '#faq'" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.faq') }}</Link>
                        <Link :href="localizedRoute('faia-validator')" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.faia_validator') }}</Link>
                        <Link :href="localizedRoute('blog.index')" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.blog') }}</Link>

                        <!-- Mobile Language Selector -->
                        <div class="pt-3 border-t border-slate-100">
                            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-2">{{ t('landing.nav.language') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="(name, code) in availableLocales()"
                                    :key="code"
                                    @click="switchLocale(code)"
                                    :class="[
                                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                        currentLocale() === code
                                            ? 'bg-[#9b5de5] text-white'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                    ]"
                                >
                                    <span>{{ localeFlags[code] }}</span>
                                    <span class="uppercase text-xs">{{ code }}</span>
                                </button>
                            </div>
                        </div>

                        <template v-if="!$page.props.auth?.user">
                            <Link :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">{{ t('landing.nav.login') }}</Link>
                            <Link v-if="canRegister" :href="route('register')" class="bg-[#9b5de5] text-white text-sm font-semibold px-5 py-3 rounded-xl text-center">{{ t('landing.nav.free_trial') }}</Link>
                        </template>
                    </div>
                </div>
            </nav>
        </header>

        <!-- Main content with padding for fixed header -->
        <main class="pt-20">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 py-12 bg-white">
            <div class="mx-auto max-w-6xl px-6 lg:px-8">
                <div class="grid md:grid-cols-5 gap-8 mb-8">
                    <div class="md:col-span-2">
                        <Link :href="localizedRoute('home')" class="flex items-center space-x-2.5 mb-4">
                            <div class="bg-[#9b5de5] p-2 rounded-xl">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="font-bold text-slate-900">faktur.lu</span>
                        </Link>
                        <p class="text-slate-600 text-sm max-w-xs">
                            {{ t('landing.footer.tagline') }}
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">{{ t('landing.footer.product') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link :href="localizedRoute('home') + '#features'" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.features') }}</Link></li>
                            <li><Link :href="localizedRoute('home') + '#pricing'" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.pricing') }}</Link></li>
                            <li><Link :href="localizedRoute('home') + '#faq'" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.faq') }}</Link></li>
                            <li><Link :href="localizedRoute('faia-validator')" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.faia_validator') }}</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">{{ t('landing.footer.resources') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link :href="localizedRoute('blog.index')" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.blog') }}</Link></li>
                            <li><Link :href="localizedRoute('faia-validator')" class="text-slate-600 hover:text-slate-900">{{ t('landing.nav.faia_validator') }}</Link></li>
                            <li><Link :href="localizedRoute('legal.privacy')" class="text-slate-600 hover:text-slate-900">{{ t('landing.footer.gdpr') }}</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">{{ t('landing.footer.legal') }}</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link :href="localizedRoute('legal.mentions')" class="text-slate-600 hover:text-slate-900">{{ t('landing.footer.legal_notice') }}</Link></li>
                            <li><Link :href="localizedRoute('legal.privacy')" class="text-slate-600 hover:text-slate-900">{{ t('landing.footer.privacy') }}</Link></li>
                            <li><Link :href="localizedRoute('legal.terms')" class="text-slate-600 hover:text-slate-900">{{ t('landing.footer.terms') }}</Link></li>
                            <li><Link :href="localizedRoute('legal.cookies')" class="text-slate-600 hover:text-slate-900">{{ t('landing.footer.cookies') }}</Link></li>
                        </ul>
                    </div>
                </div>
                <div class="pt-8 border-t border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#00f5d4]/10 text-[#00a896] text-xs font-medium">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            FAIA
                        </span>
                        {{ t('landing.footer.aed_compliant') }}
                    </div>

                    <!-- Footer Language Selector -->
                    <div class="flex items-center gap-1">
                        <button
                            v-for="(name, code) in availableLocales()"
                            :key="code"
                            @click="switchLocale(code)"
                            :class="[
                                'px-2 py-1 text-sm rounded transition-colors',
                                currentLocale() === code
                                    ? 'text-[#9b5de5] font-medium'
                                    : 'text-slate-400 hover:text-slate-600'
                            ]"
                            :title="name"
                        >
                            {{ localeFlags[code] }}
                        </button>
                    </div>

                    <p class="text-sm text-slate-500">
                        &copy; {{ new Date().getFullYear() }} faktur.lu - {{ t('landing.footer.all_rights') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
