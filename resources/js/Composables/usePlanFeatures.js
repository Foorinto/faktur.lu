import { usePage } from '@inertiajs/vue3';

/**
 * Mapping feature -> plan minimum requis.
 * Doit rester aligne avec database/seeders/PlansSeeder.php.
 */
const FEATURE_TO_MIN_PLAN = {
    // Free (toujours accessible)
    invoices: 'free',
    quotes: 'free',
    clients: 'free',
    expenses: 'free',
    '2fa': 'free',
    faia_export: 'free',

    // Essentiel
    projects: 'essentiel',
    time_tracking: 'essentiel',
    accounting_portal: 'essentiel',
    accounting_exports: 'essentiel',
    peppol_export: 'essentiel',

    // Pro
    hr_module: 'pro',
    crm: 'pro',
    peppol_transmission: 'pro',
    pdf_archive: 'pro',
    email_reminders: 'pro',
    no_branding: 'pro',
    priority_support: 'pro',
    organizations: 'pro',
    facturx: 'pro',
    advanced_reporting: 'pro',
};

const PLAN_DISPLAY_NAME = {
    free: 'Gratuit',
    essentiel: 'Essentiel',
    pro: 'Pro',
};

const PLAN_RANK = {
    free: 0,
    essentiel: 1,
    pro: 2,
};

export function usePlanFeatures() {
    /**
     * Retourne true si l'utilisateur a acces a la feature sur son plan actuel
     * (ou pendant un trial actif qui donne acces a Pro).
     */
    const hasFeature = (feature) => {
        const auth = usePage().props.auth?.user;
        if (!auth) return false;

        // Trial actif = acces Pro
        if (auth.is_on_trial) return true;
        if (auth.is_pro) return true;

        const minPlan = FEATURE_TO_MIN_PLAN[feature] ?? 'pro';
        const userPlan = auth.is_essentiel ? 'essentiel' : auth.is_free ? 'free' : 'free';

        return PLAN_RANK[userPlan] >= PLAN_RANK[minPlan];
    };

    /**
     * Retourne le nom affichable du plan minimum requis pour une feature.
     * Ex: 'Essentiel', 'Pro'.
     */
    const minPlanFor = (feature) => {
        const planKey = FEATURE_TO_MIN_PLAN[feature] ?? 'pro';
        return PLAN_DISPLAY_NAME[planKey] ?? 'Pro';
    };

    /**
     * Retourne true si la feature est verrouillee pour l'utilisateur courant.
     */
    const isLocked = (feature) => !hasFeature(feature);

    /**
     * Retourne le plan actuel de l'utilisateur (clef simple).
     */
    const currentPlan = () => {
        const auth = usePage().props.auth?.user;
        if (!auth) return 'free';
        if (auth.is_on_trial) return 'pro';
        if (auth.is_pro) return 'pro';
        if (auth.is_essentiel) return 'essentiel';
        return 'free';
    };

    return {
        hasFeature,
        isLocked,
        minPlanFor,
        currentPlan,
    };
}
