<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useMatomo } from '@/Composables/useMatomo';

const { onboarding } = useMatomo();

const props = defineProps({
    currentStep: { type: String, default: 'company' },
    business: { type: Object, default: null },
});

const stepOrder = ['company', 'branding', 'client', 'invoice', 'success'];
const step = ref(props.currentStep);

onMounted(() => {
    onboarding.started();
});
const stepIndex = computed(() => stepOrder.indexOf(step.value));
const progress = computed(() => ((stepIndex.value + 1) / stepOrder.length) * 100);

// Forms data
const companyForm = ref({
    company_name: props.business?.company_name || '',
    vat_number: props.business?.vat_number || '',
    address: props.business?.address || '',
    postal_code: props.business?.postal_code || '',
    city: props.business?.city || '',
    country_code: props.business?.country_code || 'LU',
});

const brandingForm = ref({
    logo: null,
    default_pdf_color: props.business?.default_pdf_color || '#9b5de5',
});

const clientForm = ref({
    name: '',
    email: '',
    address: '',
    postal_code: '',
    city: '',
    country_code: 'LU',
    vat_number: '',
});

const invoiceForm = ref({
    title: '',
    unit_price: '',
    vat_rate: 17,
});

const lastClientId = ref(null);
const lastInvoiceId = ref(null);
const submitting = ref(false);
const errors = ref({});

const csrfToken = () => document.querySelector('meta[name="csrf-token"]').content;

const apiCall = async (url, body, isFormData = false) => {
    submitting.value = true;
    errors.value = {};
    try {
        const headers = {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        };
        if (!isFormData) {
            headers['Content-Type'] = 'application/json';
        }
        const response = await fetch(url, {
            method: 'POST',
            headers,
            body: isFormData ? body : JSON.stringify(body),
        });
        const data = await response.json();
        if (!response.ok) {
            console.error('Onboarding error:', data);
            const messages = [];
            if (data.errors) {
                Object.entries(data.errors).forEach(([field, msgs]) => {
                    messages.push(`${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`);
                });
            }
            errors.value = data.errors || {};
            errors.value.general = messages.length ? messages.join(' | ') : (data.message || 'Erreur');
            return null;
        }
        return data;
    } catch (e) {
        errors.value = { general: e.message };
        return null;
    } finally {
        submitting.value = false;
    }
};

const submitCompany = async () => {
    const data = await apiCall(route('onboarding.company'), companyForm.value);
    if (data) {
        onboarding.stepCompleted('company');
        step.value = 'branding';
    }
};

const submitBranding = async () => {
    const formData = new FormData();
    if (brandingForm.value.logo) formData.append('logo', brandingForm.value.logo);
    formData.append('default_pdf_color', brandingForm.value.default_pdf_color);
    const data = await apiCall(route('onboarding.branding'), formData, true);
    if (data) {
        onboarding.stepCompleted('branding');
        step.value = 'client';
    }
};

const submitClient = async () => {
    const data = await apiCall(route('onboarding.client'), clientForm.value);
    if (data) {
        onboarding.stepCompleted('client');
        lastClientId.value = data.client_id;
        step.value = 'invoice';
    }
};

const submitInvoice = async () => {
    const data = await apiCall(route('onboarding.invoice'), {
        client_id: lastClientId.value,
        ...invoiceForm.value,
    });
    if (data) {
        onboarding.stepCompleted('invoice');
        onboarding.completed();
        lastInvoiceId.value = data.invoice_id;
        step.value = 'success';
    }
};

const skipStep = async () => {
    const order = stepOrder;
    let next = order[stepIndex.value + 1] || 'success';

    onboarding.stepSkipped(step.value);

    // Si on skip l'étape client, on skip automatiquement l'étape facture
    if (step.value === 'client' && next === 'invoice') {
        next = 'success';
    }

    if (next === 'success') {
        await apiCall(route('onboarding.complete'), {});
        onboarding.completed();
    }
    step.value = next;
};

const skipAll = async () => {
    if (confirm('Êtes-vous sûr de vouloir passer l\'onboarding ? Vous pourrez configurer votre entreprise plus tard depuis les paramètres.')) {
        await apiCall(route('onboarding.skip'), {});
        onboarding.skipped();
        router.visit(route('dashboard'));
    }
};

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        errors.value = { logo: 'Le fichier est trop volumineux (' + (file.size / 1024 / 1024).toFixed(1) + ' Mo). Maximum : 2 Mo.' };
        e.target.value = '';
        return;
    }
    errors.value = {};
    brandingForm.value.logo = file;
};

const goToDashboard = () => router.visit(route('dashboard'));
const goToInvoice = () => router.visit(route('invoices.edit', lastInvoiceId.value));
</script>

<template>
    <Head title="Bienvenue chez faktur.lu" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-primary-50 dark:from-gray-900 dark:to-gray-800">
        <!-- Header -->
        <header class="bg-white dark:bg-surface-card border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
                <ApplicationLogo size="sm" />
                <button
                    v-if="step !== 'success'"
                    @click="skipAll"
                    class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                >
                    Passer pour le moment
                </button>
            </div>
        </header>

        <!-- Progress bar -->
        <div class="bg-white dark:bg-surface-card border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3">
                <div class="flex justify-between items-center mb-2">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide">
                        Étape {{ stepIndex + 1 }} sur {{ stepOrder.length }}
                    </p>
                    <p class="text-xs text-slate-500">{{ Math.round(progress) }}%</p>
                </div>
                <div class="h-2 bg-slate-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-primary-500 transition-all duration-500" :style="{ width: progress + '%' }"></div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6">

                <!-- ÉTAPE 1 : ENTREPRISE -->
                <div v-if="step === 'company'" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-500/10 mb-4">
                            <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Bienvenue !</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">Commençons par configurer votre entreprise</p>
                    </div>

                    <form @submit.prevent="submitCompany" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom de l'entreprise *</label>
                            <input v-model="companyForm.company_name" type="text" required class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Ma Société Sàrl" />
                            <p v-if="errors.company_name" class="text-xs text-red-600 mt-1">{{ errors.company_name[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Numéro de TVA</label>
                            <input v-model="companyForm.vat_number" type="text" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="LU12345678" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Adresse</label>
                            <input v-model="companyForm.address" type="text" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="1 rue de Luxembourg" />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Code postal</label>
                                <input v-model="companyForm.postal_code" type="text" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="L-1234" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ville</label>
                                <input v-model="companyForm.city" type="text" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Luxembourg" />
                            </div>
                        </div>

                        <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                        <div class="pt-4">
                            <button type="submit" :disabled="submitting" class="w-full px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50 transition-colors">
                                {{ submitting ? 'Sauvegarde...' : 'Continuer →' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ÉTAPE 2 : BRANDING -->
                <div v-if="step === 'branding'" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-500/10 mb-4">
                            <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                        </div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Personnalisez votre marque</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">Logo et couleur pour vos factures (optionnel)</p>
                    </div>

                    <form @submit.prevent="submitBranding" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Logo de l'entreprise</label>
                            <input type="file" accept="image/jpeg,image/png,image/svg+xml,image/webp" @change="handleLogoUpload" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-500/10 file:text-primary-500 hover:file:bg-primary-500/20 cursor-pointer" />
                            <p class="text-xs text-slate-500 mt-1">PNG, JPG, SVG ou WEBP. Maximum 2 Mo.</p>
                            <p v-if="errors.logo" class="text-xs text-red-600 mt-1">{{ Array.isArray(errors.logo) ? errors.logo[0] : errors.logo }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Couleur principale (PDF)</label>
                            <div class="flex items-center gap-3">
                                <input v-model="brandingForm.default_pdf_color" type="color" class="h-12 w-20 rounded-lg cursor-pointer border-gray-300" />
                                <input v-model="brandingForm.default_pdf_color" type="text" class="flex-1 border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                            </div>
                        </div>

                        <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="skipStep" class="flex-1 px-6 py-3 border border-gray-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800">
                                Passer
                            </button>
                            <button type="submit" :disabled="submitting" class="flex-1 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50">
                                {{ submitting ? 'Sauvegarde...' : 'Continuer →' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ÉTAPE 3 : PREMIER CLIENT -->
                <div v-if="step === 'client'" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-500/10 mb-4">
                            <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Ajoutez votre premier client</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">Quelques infos suffisent pour commencer</p>
                    </div>

                    <form @submit.prevent="submitClient" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nom du client *</label>
                            <input v-model="clientForm.name" type="text" required class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="ACME Corp" />
                            <p v-if="errors.name" class="text-xs text-red-600 mt-1">{{ errors.name[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Email</label>
                            <input v-model="clientForm.email" type="email" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="contact@acme.com" />
                            <p v-if="errors.email" class="text-xs text-red-600 mt-1">{{ errors.email[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Adresse</label>
                            <input v-model="clientForm.address" type="text" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">CP</label>
                                <input v-model="clientForm.postal_code" type="text" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ville</label>
                                <input v-model="clientForm.city" type="text" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" />
                            </div>
                        </div>

                        <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="skipStep" class="flex-1 px-6 py-3 border border-gray-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800">
                                Passer
                            </button>
                            <button type="submit" :disabled="submitting" class="flex-1 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50">
                                {{ submitting ? 'Création...' : 'Continuer →' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ÉTAPE 4 : PREMIÈRE FACTURE -->
                <div v-if="step === 'invoice'" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-500/10 mb-4">
                            <svg class="w-7 h-7 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Créez votre première facture</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">Un exemple simple pour démarrer</p>
                    </div>

                    <form @submit.prevent="submitInvoice" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Description du service *</label>
                            <input v-model="invoiceForm.title" type="text" required class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="Prestation de conseil" />
                            <p v-if="errors.title" class="text-xs text-red-600 mt-1">{{ errors.title[0] }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Montant HT *</label>
                                <input v-model="invoiceForm.unit_price" type="number" step="0.01" min="0" required class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500" placeholder="1000" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">TVA (%)</label>
                                <select v-model="invoiceForm.vat_rate" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-primary-500 focus:border-primary-500">
                                    <option :value="17">17%</option>
                                    <option :value="14">14%</option>
                                    <option :value="8">8%</option>
                                    <option :value="3">3%</option>
                                    <option :value="0">0%</option>
                                </select>
                            </div>
                        </div>

                        <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="skipStep" class="flex-1 px-6 py-3 border border-gray-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800">
                                Passer
                            </button>
                            <button type="submit" :disabled="submitting" class="flex-1 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50">
                                {{ submitting ? 'Création...' : 'Créer ma facture →' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ÉTAPE 5 : SUCCÈS -->
                <div v-if="step === 'success'" class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 dark:bg-emerald-900/30 mb-6">
                        <svg class="w-10 h-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    </div>

                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Félicitations !</h1>
                    <p class="text-slate-600 dark:text-slate-400 mb-8">
                        Votre compte est configuré. Vous êtes prêt à facturer en toute sérénité.
                    </p>

                    <div class="bg-primary-50 dark:bg-primary-900/20 rounded-xl p-6 mb-8 text-left">
                        <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Prochaines étapes</h3>
                        <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                            <li class="flex items-start gap-2">
                                <span class="text-primary-500">→</span>
                                Personnalisez davantage vos factures dans les paramètres
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-500">→</span>
                                Importez vos clients existants depuis Excel/CSV
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-500">→</span>
                                Configurez votre compte bancaire pour les paiements
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-500">→</span>
                                Invitez votre comptable (plan Pro)
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button v-if="lastInvoiceId" @click="goToInvoice" class="flex-1 px-6 py-3 border border-gray-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800">
                            Voir ma facture
                        </button>
                        <button @click="goToDashboard" class="flex-1 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl">
                            Accéder au tableau de bord
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
