import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { usePage } from '@inertiajs/vue3';
import { useMatomo } from '@/Composables/useMatomo';
import { useTranslations } from '@/Composables/useTranslations';

/**
 * Construit la configuration des tours en utilisant la fonction de traduction.
 * Les sélecteurs CSS et options (side/align) restent en dur ; seuls les
 * libellés (title/description) sont récupérés via i18n.
 *
 * @param {(key: string) => string} t - Fonction de traduction
 */
function getTours(t) {
    return {
        dashboard: {
            steps: [
                {
                    element: '[data-tour="dashboard-kpis"]',
                    popover: {
                        title: t('tour.dashboard.kpis.title'),
                        description: t('tour.dashboard.kpis.description'),
                        side: 'bottom',
                        align: 'start',
                    },
                },
                {
                    element: '[data-tour="nav-clients"]',
                    popover: {
                        title: t('tour.dashboard.clients.title'),
                        description: t('tour.dashboard.clients.description'),
                        side: 'right',
                    },
                },
                {
                    element: '[data-tour="nav-billing"]',
                    popover: {
                        title: t('tour.dashboard.billing.title'),
                        description: t('tour.dashboard.billing.description'),
                        side: 'right',
                    },
                },
                {
                    element: '[data-tour="nav-accounting"]',
                    popover: {
                        title: t('tour.dashboard.accounting.title'),
                        description: t('tour.dashboard.accounting.description'),
                        side: 'right',
                    },
                },
                {
                    element: '[data-tour="nav-settings"]',
                    popover: {
                        title: t('tour.dashboard.settings.title'),
                        description: t('tour.dashboard.settings.description'),
                        side: 'right',
                    },
                },
                {
                    element: '[data-tour="user-menu"]',
                    popover: {
                        title: t('tour.dashboard.menu.title'),
                        description: t('tour.dashboard.menu.description'),
                        side: 'top',
                        align: 'start',
                    },
                },
            ],
        },

        clients: {
            steps: [
                {
                    element: '[data-tour="clients-new-btn"]',
                    popover: {
                        title: t('tour.clients.new.title'),
                        description: t('tour.clients.new.description'),
                        side: 'bottom',
                        align: 'end',
                    },
                },
                {
                    element: '[data-tour="clients-tabs"]',
                    popover: {
                        title: t('tour.clients.tabs.title'),
                        description: t('tour.clients.tabs.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="clients-search"]',
                    popover: {
                        title: t('tour.clients.search.title'),
                        description: t('tour.clients.search.description'),
                        side: 'bottom',
                    },
                },
            ],
        },

        invoices: {
            steps: [
                {
                    element: '[data-tour="invoices-new-btn"]',
                    popover: {
                        title: t('tour.invoices.new.title'),
                        description: t('tour.invoices.new.description'),
                        side: 'bottom',
                        align: 'end',
                    },
                },
                {
                    element: '[data-tour="invoices-filters"]',
                    popover: {
                        title: t('tour.invoices.filters.title'),
                        description: t('tour.invoices.filters.description'),
                        side: 'bottom',
                    },
                },
            ],
        },

        quotes: {
            steps: [
                {
                    element: '[data-tour="quotes-new-btn"]',
                    popover: {
                        title: t('tour.quotes.new.title'),
                        description: t('tour.quotes.new.description'),
                        side: 'bottom',
                        align: 'end',
                    },
                },
            ],
        },

        expenses: {
            steps: [
                {
                    element: '[data-tour="expenses-new-btn"]',
                    popover: {
                        title: t('tour.expenses.new.title'),
                        description: t('tour.expenses.new.description'),
                        side: 'bottom',
                        align: 'end',
                    },
                },
            ],
        },

        projects: {
            steps: [
                {
                    element: '[data-tour="projects-new-btn"]',
                    popover: {
                        title: t('tour.projects.new.title'),
                        description: t('tour.projects.new.description'),
                        side: 'bottom',
                        align: 'end',
                    },
                },
            ],
        },

        timeTracking: {
            steps: [
                {
                    element: '[data-tour="time-timer"]',
                    popover: {
                        title: t('tour.timeTracking.timer.title'),
                        description: t('tour.timeTracking.timer.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="time-entries"]',
                    popover: {
                        title: t('tour.timeTracking.entries.title'),
                        description: t('tour.timeTracking.entries.description'),
                        side: 'top',
                    },
                },
            ],
        },

        revenueBook: {
            steps: [
                {
                    element: '[data-tour="accounting-nav"]',
                    popover: {
                        title: t('tour.revenueBook.module.title'),
                        description: t('tour.revenueBook.module.description'),
                        side: 'bottom',
                        align: 'start',
                    },
                },
                {
                    element: '[data-tour="revenue-book-year"]',
                    popover: {
                        title: t('tour.revenueBook.year.title'),
                        description: t('tour.revenueBook.year.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="revenue-book-export"]',
                    popover: {
                        title: t('tour.revenueBook.export.title'),
                        description: t('tour.revenueBook.export.description'),
                        side: 'bottom',
                        align: 'end',
                    },
                },
            ],
        },

        faiaExport: {
            steps: [
                {
                    element: '[data-tour="faia-intro"]',
                    popover: {
                        title: t('tour.faiaExport.intro.title'),
                        description: t('tour.faiaExport.intro.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="faia-generate"]',
                    popover: {
                        title: t('tour.faiaExport.generate.title'),
                        description: t('tour.faiaExport.generate.description'),
                        side: 'top',
                    },
                },
            ],
        },

        accountingExport: {
            steps: [
                {
                    element: '[data-tour="accounting-export-intro"]',
                    popover: {
                        title: t('tour.accountingExport.intro.title'),
                        description: t('tour.accountingExport.intro.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="accounting-export-format"]',
                    popover: {
                        title: t('tour.accountingExport.format.title'),
                        description: t('tour.accountingExport.format.description'),
                        side: 'top',
                    },
                },
            ],
        },

        fiscalSummary: {
            steps: [
                {
                    element: '[data-tour="fiscal-summary-intro"]',
                    popover: {
                        title: t('tour.fiscalSummary.intro.title'),
                        description: t('tour.fiscalSummary.intro.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="fiscal-summary-year"]',
                    popover: {
                        title: t('tour.fiscalSummary.year.title'),
                        description: t('tour.fiscalSummary.year.description'),
                        side: 'bottom',
                    },
                },
            ],
        },

        hrDashboard: {
            steps: [
                {
                    element: '[data-tour="hr-nav"]',
                    popover: {
                        title: t('tour.hrDashboard.module.title'),
                        description: t('tour.hrDashboard.module.description'),
                        side: 'bottom',
                        align: 'start',
                    },
                },
                {
                    element: '[data-tour="hr-stats"]',
                    popover: {
                        title: t('tour.hrDashboard.stats.title'),
                        description: t('tour.hrDashboard.stats.description'),
                        side: 'bottom',
                    },
                },
            ],
        },

        hrEmployees: {
            steps: [
                {
                    element: '[data-tour="hr-employees-new"]',
                    popover: {
                        title: t('tour.hrEmployees.new.title'),
                        description: t('tour.hrEmployees.new.description'),
                        side: 'bottom',
                        align: 'end',
                    },
                },
                {
                    element: '[data-tour="hr-employees-list"]',
                    popover: {
                        title: t('tour.hrEmployees.list.title'),
                        description: t('tour.hrEmployees.list.description'),
                        side: 'top',
                    },
                },
            ],
        },

        hrLeaves: {
            steps: [
                {
                    element: '[data-tour="hr-leaves-list"]',
                    popover: {
                        title: t('tour.hrLeaves.list.title'),
                        description: t('tour.hrLeaves.list.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="hr-leaves-calendar"]',
                    popover: {
                        title: t('tour.hrLeaves.calendar.title'),
                        description: t('tour.hrLeaves.calendar.description'),
                        side: 'bottom',
                    },
                },
            ],
        },

        hrLeavesCalendar: {
            steps: [
                {
                    element: '[data-tour="hr-calendar-view"]',
                    popover: {
                        title: t('tour.hrLeavesCalendar.view.title'),
                        description: t('tour.hrLeavesCalendar.view.description'),
                        side: 'top',
                    },
                },
            ],
        },

        hrExpenses: {
            steps: [
                {
                    element: '[data-tour="hr-expenses-list"]',
                    popover: {
                        title: t('tour.hrExpenses.list.title'),
                        description: t('tour.hrExpenses.list.description'),
                        side: 'bottom',
                    },
                },
            ],
        },

        hrEmployeeCreate: {
            steps: [
                {
                    element: '[data-tour="hr-employee-form-name"]',
                    popover: {
                        title: t('tour.hrEmployeeCreate.identity.title'),
                        description: t('tour.hrEmployeeCreate.identity.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="hr-employee-form-contract"]',
                    popover: {
                        title: t('tour.hrEmployeeCreate.contract.title'),
                        description: t('tour.hrEmployeeCreate.contract.description'),
                        side: 'top',
                    },
                },
                {
                    element: '[data-tour="hr-employee-form-submit"]',
                    popover: {
                        title: t('tour.hrEmployeeCreate.submit.title'),
                        description: t('tour.hrEmployeeCreate.submit.description'),
                        side: 'top',
                        align: 'end',
                    },
                },
            ],
        },

        settings: {
            steps: [
                {
                    element: '[data-tour="settings-company"]',
                    popover: {
                        title: t('tour.settings.company.title'),
                        description: t('tour.settings.company.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="settings-logo"]',
                    popover: {
                        title: t('tour.settings.logo.title'),
                        description: t('tour.settings.logo.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="settings-bank"]',
                    popover: {
                        title: t('tour.settings.bank.title'),
                        description: t('tour.settings.bank.description'),
                        side: 'bottom',
                    },
                },
            ],
        },

        clientCreate: {
            steps: [
                {
                    element: '[data-tour="client-form-name"]',
                    popover: {
                        title: t('tour.clientCreate.name.title'),
                        description: t('tour.clientCreate.name.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="client-form-type"]',
                    popover: {
                        title: t('tour.clientCreate.type.title'),
                        description: t('tour.clientCreate.type.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="client-form-vat"]',
                    popover: {
                        title: t('tour.clientCreate.vat.title'),
                        description: t('tour.clientCreate.vat.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="client-form-submit"]',
                    popover: {
                        title: t('tour.clientCreate.submit.title'),
                        description: t('tour.clientCreate.submit.description'),
                        side: 'top',
                        align: 'end',
                    },
                },
            ],
        },

        invoiceCreate: {
            steps: [
                {
                    element: '[data-tour="invoice-form-client"]',
                    popover: {
                        title: t('tour.invoiceCreate.client.title'),
                        description: t('tour.invoiceCreate.client.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="invoice-form-dates"]',
                    popover: {
                        title: t('tour.invoiceCreate.dates.title'),
                        description: t('tour.invoiceCreate.dates.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="invoice-form-items"]',
                    popover: {
                        title: t('tour.invoiceCreate.items.title'),
                        description: t('tour.invoiceCreate.items.description'),
                        side: 'top',
                    },
                },
                {
                    element: '[data-tour="invoice-form-submit"]',
                    popover: {
                        title: t('tour.invoiceCreate.submit.title'),
                        description: t('tour.invoiceCreate.submit.description'),
                        side: 'top',
                        align: 'end',
                    },
                },
            ],
        },

        quoteCreate: {
            steps: [
                {
                    element: '[data-tour="quote-form-client"]',
                    popover: {
                        title: t('tour.quoteCreate.client.title'),
                        description: t('tour.quoteCreate.client.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="quote-form-items"]',
                    popover: {
                        title: t('tour.quoteCreate.items.title'),
                        description: t('tour.quoteCreate.items.description'),
                        side: 'top',
                    },
                },
                {
                    element: '[data-tour="quote-form-submit"]',
                    popover: {
                        title: t('tour.quoteCreate.submit.title'),
                        description: t('tour.quoteCreate.submit.description'),
                        side: 'top',
                        align: 'end',
                    },
                },
            ],
        },

        expenseCreate: {
            steps: [
                {
                    element: '[data-tour="expense-form-amount"]',
                    popover: {
                        title: t('tour.expenseCreate.amount.title'),
                        description: t('tour.expenseCreate.amount.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="expense-form-category"]',
                    popover: {
                        title: t('tour.expenseCreate.category.title'),
                        description: t('tour.expenseCreate.category.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="expense-form-receipt"]',
                    popover: {
                        title: t('tour.expenseCreate.receipt.title'),
                        description: t('tour.expenseCreate.receipt.description'),
                        side: 'top',
                    },
                },
                {
                    element: '[data-tour="expense-form-submit"]',
                    popover: {
                        title: t('tour.expenseCreate.submit.title'),
                        description: t('tour.expenseCreate.submit.description'),
                        side: 'top',
                        align: 'end',
                    },
                },
            ],
        },

        projectCreate: {
            steps: [
                {
                    element: '[data-tour="project-form-name"]',
                    popover: {
                        title: t('tour.projectCreate.name.title'),
                        description: t('tour.projectCreate.name.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="project-form-client"]',
                    popover: {
                        title: t('tour.projectCreate.client.title'),
                        description: t('tour.projectCreate.client.description'),
                        side: 'bottom',
                    },
                },
                {
                    element: '[data-tour="project-form-submit"]',
                    popover: {
                        title: t('tour.projectCreate.submit.title'),
                        description: t('tour.projectCreate.submit.description'),
                        side: 'top',
                        align: 'end',
                    },
                },
            ],
        },
    };
}

/**
 * Liste statique des clés de tours, utilisée pour resetTours() sans avoir
 * à instancier toute la configuration traduite.
 */
const TOUR_KEYS = [
    'dashboard',
    'clients',
    'invoices',
    'quotes',
    'expenses',
    'projects',
    'timeTracking',
    'revenueBook',
    'faiaExport',
    'accountingExport',
    'fiscalSummary',
    'hrDashboard',
    'hrEmployees',
    'hrLeaves',
    'hrLeavesCalendar',
    'hrExpenses',
    'hrEmployeeCreate',
    'settings',
    'clientCreate',
    'invoiceCreate',
    'quoteCreate',
    'expenseCreate',
    'projectCreate',
];

/**
 * Composable pour gérer les tours interactifs.
 */
export function useTour() {
    const { tour: trackTour } = useMatomo();
    const { t } = useTranslations();

    const getUserId = () => {
        try {
            return usePage().props.auth?.user?.id ?? 'guest';
        } catch {
            return 'guest';
        }
    };

    const getStorageKey = (tourKey) => `tour-u${getUserId()}-${tourKey}-seen`;

    // Nettoyage unique des anciennes cles (format pre-scope utilisateur)
    const MIGRATION_KEY = 'tour-migration-v2';
    if (typeof window !== 'undefined' && !localStorage.getItem(MIGRATION_KEY)) {
        Object.keys(localStorage)
            .filter(k => k.startsWith('tour-') && !k.startsWith('tour-u') && k !== MIGRATION_KEY)
            .forEach(k => localStorage.removeItem(k));
        localStorage.setItem(MIGRATION_KEY, '1');
    }

    const hasSeen = (tourKey) => {
        return localStorage.getItem(getStorageKey(tourKey)) === '1';
    };

    const markSeen = (tourKey) => {
        localStorage.setItem(getStorageKey(tourKey), '1');
    };

    const startTour = (tourKey, force = false) => {
        // Récupère la configuration traduite à chaque lancement pour avoir
        // la locale fraîche (utile si l'utilisateur a changé de langue).
        const tours = getTours(t);
        const config = tours[tourKey];
        if (!config) {
            console.warn(`Tour "${tourKey}" non trouvé`);
            return;
        }

        if (!force && hasSeen(tourKey)) {
            return;
        }

        // Vérifier que les éléments existent avant de lancer
        const firstStep = config.steps[0];
        if (firstStep?.element && !document.querySelector(firstStep.element)) {
            return; // Élément pas prêt, ne pas lancer
        }

        trackTour.started(tourKey);

        const driverObj = driver({
            showProgress: config.steps.length > 1,
            allowClose: true,
            overlayOpacity: 0.6,
            popoverClass: 'faktur-tour-popover',
            nextBtnText: t('tour.ui.next'),
            prevBtnText: t('tour.ui.prev'),
            doneBtnText: t('tour.ui.done'),
            progressText: t('tour.ui.progress'),
            onDestroyed: () => {
                markSeen(tourKey);
                trackTour.completed(tourKey);
            },
            steps: config.steps,
        });

        driverObj.drive();
    };

    const resetTours = () => {
        TOUR_KEYS.forEach(key => {
            localStorage.removeItem(getStorageKey(key));
        });
    };

    const resetTour = (tourKey) => {
        localStorage.removeItem(getStorageKey(tourKey));
    };

    // Alias pour la rétrocompatibilité
    const startDashboardTour = (force = false) => startTour('dashboard', force);

    return {
        startTour,
        resetTours,
        resetTour,
        hasSeen,
        startDashboardTour,
    };
}
