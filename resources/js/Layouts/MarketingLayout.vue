<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const mobileMenuOpen = ref(false);
const page = usePage();

const canLogin = page.props.canLogin ?? true;
const canRegister = page.props.canRegister ?? true;
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-lg border-b border-slate-200">
            <nav class="mx-auto max-w-6xl px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center space-x-2.5">
                        <div class="bg-[#9b5de5] p-2 rounded-xl">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-bold text-slate-900">faktur.lu</span>
                    </Link>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <Link href="/#features" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Fonctionnalités
                        </Link>
                        <Link href="/#pricing" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Tarifs
                        </Link>
                        <Link :href="route('blog.index')" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                            Blog
                        </Link>
                        <Link :href="route('faia-validator')" class="text-sm font-medium text-[#9b5de5] hover:text-[#8b4ed5] transition-colors">
                            Validateur FAIA
                        </Link>
                    </div>

                    <!-- Desktop CTA -->
                    <div class="hidden md:flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                            >
                                Connexion
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-[#9b5de5] hover:bg-[#8b4ed5] text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors"
                            >
                                Essai gratuit
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
                        <Link href="/#features" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Fonctionnalités</Link>
                        <Link href="/#pricing" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Tarifs</Link>
                        <Link :href="route('blog.index')" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Blog</Link>
                        <Link :href="route('faia-validator')" @click="mobileMenuOpen = false" class="text-sm font-medium text-[#9b5de5] hover:text-[#8b4ed5] py-2">Validateur FAIA</Link>
                        <template v-if="!$page.props.auth?.user">
                            <Link :href="route('login')" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2">Connexion</Link>
                            <Link v-if="canRegister" :href="route('register')" class="bg-[#9b5de5] text-white text-sm font-semibold px-5 py-3 rounded-xl text-center">Essai gratuit</Link>
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
                        <Link href="/" class="flex items-center space-x-2.5 mb-4">
                            <div class="bg-[#9b5de5] p-2 rounded-xl">
                                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="font-bold text-slate-900">faktur.lu</span>
                        </Link>
                        <p class="text-slate-600 text-sm max-w-xs">
                            Logiciel de facturation conçu pour les freelances et PME au Luxembourg. Conforme FAIA.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">Produit</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/#features" class="text-slate-600 hover:text-slate-900">Fonctionnalités</Link></li>
                            <li><Link href="/#pricing" class="text-slate-600 hover:text-slate-900">Tarifs</Link></li>
                            <li><Link href="/#faq" class="text-slate-600 hover:text-slate-900">FAQ</Link></li>
                            <li><Link :href="route('faia-validator')" class="text-slate-600 hover:text-slate-900">Validateur FAIA</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">Ressources</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link :href="route('blog.index')" class="text-slate-600 hover:text-slate-900">Blog</Link></li>
                            <li class="text-slate-600">Guide FAIA</li>
                            <li class="text-slate-600">TVA Luxembourg</li>
                            <li class="text-slate-600">Conformité RGPD</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-slate-900 mb-4">Légal</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link :href="route('legal.mentions')" class="text-slate-600 hover:text-slate-900">Mentions légales</Link></li>
                            <li><Link :href="route('legal.privacy')" class="text-slate-600 hover:text-slate-900">Confidentialité</Link></li>
                            <li><Link :href="route('legal.terms')" class="text-slate-600 hover:text-slate-900">CGU / CGV</Link></li>
                            <li><Link :href="route('legal.cookies')" class="text-slate-600 hover:text-slate-900">Cookies</Link></li>
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
                        Conforme aux exigences de l'AED Luxembourg
                    </div>
                    <p class="text-sm text-slate-500">
                        &copy; {{ new Date().getFullYear() }} faktur.lu - Tous droits réservés
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
