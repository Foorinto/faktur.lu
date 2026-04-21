import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { usePage } from '@inertiajs/vue3';
import { useMatomo } from '@/Composables/useMatomo';

/**
 * Configuration des tours par écran.
 * Chaque tour est défini par une clé et un tableau d'étapes.
 */
const tours = {
    dashboard: {
        steps: [
            {
                element: '[data-tour="dashboard-kpis"]',
                popover: {
                    title: '📊 Vos indicateurs clés',
                    description: 'Retrouvez ici un aperçu en temps réel de votre chiffre d\'affaires, factures impayées et tendances mensuelles.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="nav-clients"]',
                popover: {
                    title: '👥 Gestion des clients',
                    description: 'Centralisez tous vos clients et prospects. Vous pouvez aussi importer une liste depuis Excel/CSV.',
                    side: 'right',
                },
            },
            {
                element: '[data-tour="nav-billing"]',
                popover: {
                    title: '📄 Facturation',
                    description: 'Créez vos factures et devis conformes au Luxembourg. Conversion devis → facture en un clic.',
                    side: 'right',
                },
            },
            {
                element: '[data-tour="nav-accounting"]',
                popover: {
                    title: '💼 Comptabilité',
                    description: 'Exportez vos données vers Sage BOB, FID-Manager et générez votre fichier FAIA pour les contrôles fiscaux.',
                    side: 'right',
                },
            },
            {
                element: '[data-tour="nav-settings"]',
                popover: {
                    title: '⚙️ Paramètres',
                    description: 'Configurez votre entreprise, vos informations bancaires, et personnalisez vos factures.',
                    side: 'right',
                },
            },
            {
                element: '[data-tour="user-menu"]',
                popover: {
                    title: '🎉 C\'est tout !',
                    description: 'Vous pouvez relancer ce tour à tout moment depuis votre menu utilisateur. Bon démarrage avec faktur.lu !',
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
                    title: '➕ Nouveau client',
                    description: 'Cliquez ici pour ajouter un nouveau client, un prospect, ou importer une liste depuis Excel/CSV.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                element: '[data-tour="clients-tabs"]',
                popover: {
                    title: '📋 Statuts',
                    description: 'Filtrez vos contacts par statut : prospects, clients actifs, inactifs, etc.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="clients-search"]',
                popover: {
                    title: '🔍 Recherche rapide',
                    description: 'Trouvez instantanément un client par nom, email ou numéro de TVA.',
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
                    title: '➕ Nouvelle facture',
                    description: 'Créez une facture conforme au Luxembourg en quelques secondes.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                element: '[data-tour="invoices-filters"]',
                popover: {
                    title: '🎯 Filtres',
                    description: 'Filtrez vos factures par statut : brouillon, envoyée, payée, en retard.',
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
                    title: '➕ Nouveau devis',
                    description: 'Créez un devis professionnel que vous pourrez convertir en facture en un clic une fois accepté.',
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
                    title: '➕ Nouvelle dépense',
                    description: 'Enregistrez vos dépenses professionnelles. La TVA déductible est calculée automatiquement.',
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
                    title: '➕ Nouveau projet',
                    description: 'Créez un projet, assignez-le à un client, suivez le budget et les heures passées.',
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
                    title: '⏱️ Timer',
                    description: 'Lancez un chronomètre pour suivre votre temps en temps réel, ou ajoutez des entrées manuellement.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="time-entries"]',
                popover: {
                    title: '📋 Historique',
                    description: 'Consultez toutes vos entrées de temps et convertissez-les en lignes de facture en un clic.',
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
                    title: '💼 Module comptabilité',
                    description: 'Le module comptabilité regroupe le livre des recettes, l\'export FAIA, les exports comptables et le récapitulatif fiscal.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="revenue-book-year"]',
                popover: {
                    title: '📅 Sélection de l\'année',
                    description: 'Choisissez l\'année fiscale à consulter. Le livre des recettes est généré automatiquement à partir de vos factures finalisées.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="revenue-book-export"]',
                popover: {
                    title: '📥 Export',
                    description: 'Exportez votre livre des recettes au format PDF ou Excel pour votre comptable ou pour vos archives.',
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
                    title: '🛡️ Export FAIA',
                    description: 'Le FAIA (Fichier d\'Audit Informatisé AED) est obligatoire pour les contrôles fiscaux au Luxembourg. Ce fichier XML contient l\'ensemble de votre comptabilité.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="faia-generate"]',
                popover: {
                    title: '⚙️ Génération',
                    description: 'Sélectionnez la période et générez votre fichier FAIA en un clic. Le fichier sera conforme au format XML 2.01 exigé par l\'AED.',
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
                    title: '💼 Exports pour votre comptable',
                    description: 'Exportez vos données directement dans les formats utilisés par les fiduciaires luxembourgeoises : Sage BOB, FID-Manager, CSV, Excel.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="accounting-export-format"]',
                popover: {
                    title: '📋 Choisir le format',
                    description: 'Sélectionnez le format correspondant au logiciel de votre comptable ou fiduciaire.',
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
                    title: '📊 Récapitulatif fiscal annuel',
                    description: 'Synthèse annuelle de vos revenus, TVA collectée et déductible. Utile pour préparer votre déclaration fiscale.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="fiscal-summary-year"]',
                popover: {
                    title: '📅 Année fiscale',
                    description: 'Sélectionnez l\'année à analyser. Les chiffres sont calculés automatiquement à partir de vos factures et dépenses.',
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
                    title: '👨‍💼 Module RH',
                    description: 'Le module RH regroupe la gestion des employés, congés, notes de frais, départements et évaluations.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="hr-stats"]',
                popover: {
                    title: '📊 Vos indicateurs RH',
                    description: 'Aperçu en temps réel : nombre d\'employés actifs, congés en attente, masse salariale.',
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
                    title: '➕ Ajouter un employé',
                    description: 'Créez une fiche employé avec contrat, salaire, coordonnées et documents associés.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                element: '[data-tour="hr-employees-list"]',
                popover: {
                    title: '👥 Liste des employés',
                    description: 'Consultez tous vos employés. Cliquez sur une fiche pour voir les détails, contrats et historique.',
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
                    title: '🏖️ Demandes de congés',
                    description: 'Consultez et approuvez les demandes de congés de vos employés. Les soldes sont mis à jour automatiquement.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="hr-leaves-calendar"]',
                popover: {
                    title: '📅 Calendrier',
                    description: 'Visualisez tous les congés sur un calendrier d\'équipe pour anticiper les absences.',
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
                    title: '📅 Calendrier d\'équipe',
                    description: 'Vue d\'ensemble de tous les congés approuvés et en attente. Filtrez par employé ou département.',
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
                    title: '💳 Notes de frais employés',
                    description: 'Centralisez les notes de frais soumises par vos employés. Approuvez ou rejetez en un clic.',
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
                    title: '👤 Identité',
                    description: 'Renseignez les informations personnelles de l\'employé : nom, prénom, date de naissance.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="hr-employee-form-contract"]',
                popover: {
                    title: '📝 Contrat',
                    description: 'Type de contrat (CDI, CDD), poste, salaire, date d\'embauche.',
                    side: 'top',
                },
            },
            {
                element: '[data-tour="hr-employee-form-submit"]',
                popover: {
                    title: '✅ Créer l\'employé',
                    description: 'Une fois créé, vous pourrez ajouter des documents, gérer ses congés et préparer ses évaluations.',
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
                    title: '🏢 Informations entreprise',
                    description: 'Configurez le nom, la TVA, l\'adresse et les informations légales de votre entreprise.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="settings-logo"]',
                popover: {
                    title: '🖼️ Logo',
                    description: 'Ajoutez votre logo pour le faire apparaître automatiquement sur vos factures.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="settings-bank"]',
                popover: {
                    title: '🏦 Coordonnées bancaires',
                    description: 'Ajoutez votre IBAN pour que vos clients puissent vous payer facilement.',
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
                    title: '🏢 Nom du client',
                    description: 'Commencez par le nom ou la raison sociale. Les autres champs peuvent être remplis plus tard.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="client-form-type"]',
                popover: {
                    title: '👥 Type de client',
                    description: 'B2B pour les entreprises, B2C pour les particuliers. Cela détermine les règles de TVA appliquées.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="client-form-vat"]',
                popover: {
                    title: '🛡️ Numéro de TVA',
                    description: 'Pour les clients intracommunautaires, saisissez le numéro TVA : il sera validé automatiquement via le service VIES.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="client-form-submit"]',
                popover: {
                    title: '✅ Enregistrer',
                    description: 'Cliquez ici pour créer le client. Vous pourrez ensuite créer des factures et devis pour lui.',
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
                    title: '👤 Sélectionnez le client',
                    description: 'Choisissez le client à facturer. Les informations et règles de TVA seront chargées automatiquement.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="invoice-form-dates"]',
                popover: {
                    title: '📅 Dates',
                    description: 'Date d\'émission et date d\'échéance. Par défaut : aujourd\'hui et dans 30 jours.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="invoice-form-items"]',
                popover: {
                    title: '📋 Lignes de facture',
                    description: 'Ajoutez les prestations ou produits à facturer. Vous pouvez utiliser vos produits pré-enregistrés.',
                    side: 'top',
                },
            },
            {
                element: '[data-tour="invoice-form-submit"]',
                popover: {
                    title: '💾 Enregistrer le brouillon',
                    description: 'Votre facture sera enregistrée comme brouillon. Vous pourrez la finaliser et l\'envoyer ensuite.',
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
                    title: '👤 Sélectionnez le client',
                    description: 'Choisissez le client pour qui faire ce devis.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="quote-form-items"]',
                popover: {
                    title: '📋 Lignes du devis',
                    description: 'Détaillez les prestations proposées. Une fois accepté, vous pourrez convertir ce devis en facture en un clic.',
                    side: 'top',
                },
            },
            {
                element: '[data-tour="quote-form-submit"]',
                popover: {
                    title: '💾 Créer le devis',
                    description: 'Votre devis sera enregistré comme brouillon. Vous pourrez ensuite le finaliser et l\'envoyer au client.',
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
                    title: '💶 Montant',
                    description: 'Saisissez le montant TTC de la dépense. La TVA sera calculée automatiquement selon le taux choisi.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="expense-form-category"]',
                popover: {
                    title: '📂 Catégorie',
                    description: 'Classez votre dépense par catégorie pour faciliter le suivi et l\'export comptable.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="expense-form-receipt"]',
                popover: {
                    title: '📎 Justificatif',
                    description: 'Joignez votre justificatif (PDF, photo) pour conserver une trace du reçu.',
                    side: 'top',
                },
            },
            {
                element: '[data-tour="expense-form-submit"]',
                popover: {
                    title: '✅ Enregistrer',
                    description: 'Votre dépense sera ajoutée. Vous retrouverez la TVA déductible dans vos rapports.',
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
                    title: '📁 Nom du projet',
                    description: 'Donnez un nom clair à votre projet.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="project-form-client"]',
                popover: {
                    title: '👤 Client',
                    description: 'Associez le projet à un client pour faciliter la facturation des heures passées.',
                    side: 'bottom',
                },
            },
            {
                element: '[data-tour="project-form-submit"]',
                popover: {
                    title: '✅ Créer le projet',
                    description: 'Une fois créé, vous pourrez ajouter des tâches et suivre le temps passé.',
                    side: 'top',
                    align: 'end',
                },
            },
        ],
    },
};

/**
 * Composable pour gérer les tours interactifs.
 */
export function useTour() {
    const { tour: trackTour } = useMatomo();

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
            nextBtnText: 'Suivant →',
            prevBtnText: '← Précédent',
            doneBtnText: 'Terminer',
            progressText: 'Étape {{current}} sur {{total}}',
            onDestroyed: () => {
                markSeen(tourKey);
                trackTour.completed(tourKey);
            },
            steps: config.steps,
        });

        driverObj.drive();
    };

    const resetTours = () => {
        Object.keys(tours).forEach(key => {
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
