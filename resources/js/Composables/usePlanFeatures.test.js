import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Accès aux fonctionnalités selon le plan.
 *
 * Ce module décide de ce que l'interface propose ou verrouille. Une erreur dans
 * un sens ouvre une fonctionnalité payante à tous ; dans l'autre, elle la
 * refuse à quelqu'un qui l'a payée. Les deux se découvrent tard : rien ne
 * plante, l'écran est simplement faux.
 *
 * Le serveur reste seul juge — les routes sont gardées par `plan.feature`.
 * Ce qui se teste ici, c'est la cohérence de l'affichage avec cette garde.
 */

const utilisateur = vi.hoisted(() => ({ valeur: null }));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: utilisateur.valeur } } }),
}));

const { usePlanFeatures } = await import('./usePlanFeatures');

/** Les drapeaux tels que le serveur les sérialise sur `auth.user`. */
const compte = (surcharges = {}) => ({
    is_free: false,
    is_essentiel: false,
    is_pro: false,
    is_on_trial: false,
    accounting_portal_grandfathered: false,
    ...surcharges,
});

beforeEach(() => {
    utilisateur.valeur = null;
});

describe('accès selon le plan', () => {
    it('refuse tout à un visiteur non connecté', () => {
        const { hasFeature, currentPlan } = usePlanFeatures();

        expect(hasFeature('invoices')).toBe(false);
        expect(currentPlan()).toBe('free');
    });

    it('donne au plan Gratuit ce qui lui revient, et rien de plus', () => {
        utilisateur.valeur = compte({ is_free: true });
        const { hasFeature } = usePlanFeatures();

        expect(hasFeature('invoices')).toBe(true);
        expect(hasFeature('faia_export')).toBe(true);
        expect(hasFeature('accounting_exports')).toBe(false);
        expect(hasFeature('hr_module')).toBe(false);
    });

    it('ouvre l’Essentiel sans donner le Pro', () => {
        utilisateur.valeur = compte({ is_essentiel: true });
        const { hasFeature, currentPlan } = usePlanFeatures();

        expect(currentPlan()).toBe('essentiel');
        expect(hasFeature('accounting_exports')).toBe(true);
        expect(hasFeature('peppol_export')).toBe(true);

        // La transmission Peppol est du Pro, l'export ne l'est pas : les deux
        // se ressemblent assez pour être confondus.
        expect(hasFeature('peppol_transmission')).toBe(false);
        expect(hasFeature('hr_module')).toBe(false);
    });

    it('ouvre tout au plan Pro', () => {
        utilisateur.valeur = compte({ is_pro: true });
        const { hasFeature, currentPlan } = usePlanFeatures();

        expect(currentPlan()).toBe('pro');
        expect(hasFeature('hr_module')).toBe(true);
        expect(hasFeature('advanced_reporting')).toBe(true);
    });

    it('donne l’accès Pro pendant l’essai', () => {
        utilisateur.valeur = compte({ is_free: true, is_on_trial: true });
        const { hasFeature, currentPlan } = usePlanFeatures();

        expect(currentPlan()).toBe('pro');
        expect(hasFeature('hr_module')).toBe(true);
    });

    /**
     * Le portail comptable a été fermé au plan Gratuit après coup. Les comptes
     * qui l'utilisaient déjà le gardent : leur retirer un accès en cours
     * d'usage serait une régression pour eux, pas une correction.
     */
    it('respecte l’antériorité sur le portail comptable', () => {
        utilisateur.valeur = compte({ is_free: true, accounting_portal_grandfathered: true });
        const { hasFeature } = usePlanFeatures();

        expect(hasFeature('accounting_portal')).toBe(true);

        // L'antériorité ne vaut que pour cette fonctionnalité-là.
        expect(hasFeature('projects')).toBe(false);
    });

    /**
     * Une fonctionnalité absente de la table est traitée comme du Pro. C'est le
     * bon défaut : ajouter une fonctionnalité en oubliant de la déclarer la
     * verrouille — visible, et corrigeable — plutôt que de l'ouvrir à tous.
     */
    it('verrouille une fonctionnalité inconnue plutôt que de l’ouvrir', () => {
        utilisateur.valeur = compte({ is_essentiel: true });
        const { hasFeature, minPlanFor } = usePlanFeatures();

        expect(hasFeature('fonctionnalite_jamais_declaree')).toBe(false);
        expect(minPlanFor('fonctionnalite_jamais_declaree')).toBe('Pro');
    });
});

describe('plan minimum affiché', () => {
    it('nomme le plan requis pour la mise en avant', () => {
        const { minPlanFor } = usePlanFeatures();

        expect(minPlanFor('invoices')).toBe('Gratuit');
        expect(minPlanFor('accounting_exports')).toBe('Essentiel');
        expect(minPlanFor('hr_module')).toBe('Pro');
    });

    it('isLocked est bien l’inverse de hasFeature', () => {
        utilisateur.valeur = compte({ is_essentiel: true });
        const { hasFeature, isLocked } = usePlanFeatures();

        for (const feature of ['invoices', 'accounting_exports', 'hr_module']) {
            expect(isLocked(feature)).toBe(!hasFeature(feature));
        }
    });
});
