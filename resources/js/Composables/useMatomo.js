/**
 * Composable pour envoyer des événements à Matomo.
 * Fonctionne uniquement si Matomo est chargé (window._paq disponible).
 */
export function useMatomo() {
    const isAvailable = () => {
        return typeof window !== 'undefined' && Array.isArray(window._paq);
    };

    /**
     * Envoie un événement custom à Matomo.
     *
     * @param {string} category - Catégorie (ex: 'Onboarding', 'Tour', 'Checklist')
     * @param {string} action - Action (ex: 'started', 'step_completed')
     * @param {string|null} name - Nom optionnel (ex: 'step-company')
     * @param {number|null} value - Valeur numérique optionnelle
     */
    const trackEvent = (category, action, name = null, value = null) => {
        if (!isAvailable()) return;

        const event = ['trackEvent', category, action];
        if (name !== null) event.push(name);
        if (value !== null) event.push(value);

        window._paq.push(event);
    };

    /**
     * Envoie un goal atteint à Matomo.
     *
     * @param {number} goalId - ID du goal configuré dans Matomo
     * @param {number|null} revenue - Revenu optionnel associé
     */
    const trackGoal = (goalId, revenue = null) => {
        if (!isAvailable()) return;

        const event = ['trackGoal', goalId];
        if (revenue !== null) event.push(revenue);

        window._paq.push(event);
    };

    /**
     * Suivre un changement de page (utile pour les SPAs).
     */
    const trackPageView = (customUrl = null, customTitle = null) => {
        if (!isAvailable()) return;

        if (customUrl) {
            window._paq.push(['setCustomUrl', customUrl]);
        }
        if (customTitle) {
            window._paq.push(['setDocumentTitle', customTitle]);
        }
        window._paq.push(['trackPageView']);
    };

    // Helpers pour l'onboarding
    const onboarding = {
        started: () => trackEvent('Onboarding', 'started'),
        stepCompleted: (step) => trackEvent('Onboarding', 'step_completed', step),
        stepSkipped: (step) => trackEvent('Onboarding', 'step_skipped', step),
        skipped: () => trackEvent('Onboarding', 'skipped'),
        completed: () => trackEvent('Onboarding', 'completed'),
    };

    // Helpers pour les tours
    const tour = {
        started: (tourKey) => trackEvent('Tour', 'started', tourKey),
        completed: (tourKey) => trackEvent('Tour', 'completed', tourKey),
    };

    // Helpers pour la checklist
    const checklist = {
        taskClicked: (taskKey) => trackEvent('Checklist', 'task_clicked', taskKey),
        dismissed: () => trackEvent('Checklist', 'dismissed'),
        completed: () => trackEvent('Checklist', 'completed'),
    };

    return {
        isAvailable,
        trackEvent,
        trackGoal,
        trackPageView,
        onboarding,
        tour,
        checklist,
    };
}
