<?php

return [
    'landing' => [
        'page_title' => 'Facturation simple pour le Luxembourg',

        'nav' => [
            'features' => 'Fonctionnalités',
            'how_it_works' => 'Comment ça marche',
            'pricing' => 'Tarifs',
            'faq' => 'FAQ',
            'faia_validator' => 'Validateur FAIA',
            'dashboard' => 'Tableau de bord',
            'login' => 'Connexion',
            'create_account' => 'Créer un compte',
        ],

        'hero' => [
            'badge' => 'Conforme FAIA Luxembourg',
            'title_1' => 'Facturez ',
            'title_2' => 'simplement',
            'title_3' => ' au Luxembourg',
            'subtitle' => 'Créez des factures conformes à la législation luxembourgeoise en quelques clics. Export FAIA pour vos contrôles fiscaux.',
            'cta_start' => 'Commencer gratuitement',
            'cta_login' => 'Déjà inscrit ?',
            'badge_faia' => 'Export FAIA',
            'badge_secure' => 'Données sécurisées',
        ],

        'preview' => [
            'invoice' => 'Facture',
            'paid' => 'Payée',
            'web_dev' => 'Développement web',
            'ui_design' => 'Design UI/UX',
            'vat' => 'TVA 17%',
            'total_ttc' => 'Total TTC',
            'this_month' => 'Ce mois',
            'invoices_paid' => 'Factures payées',
        ],

        'trust' => [
            'compliant_with' => 'Conforme aux normes',
            'luxembourg_aed' => 'AED Luxembourg',
            'faia_format' => 'Format FAIA',
            'gdpr' => 'RGPD',
            'intra_vat' => 'TVA intracommunautaire',
        ],

        'features' => [
            'title' => 'FONCTIONNALITÉS',
            'heading' => 'Tout ce dont vous avez besoin',
            'subtitle' => 'Des outils puissants pour gérer votre facturation au quotidien',
            'items' => [
                'invoicing' => [
                    'title' => 'Factures conformes',
                    'description' => 'Créez des factures respectant la législation luxembourgeoise avec numérotation séquentielle.',
                ],
                'clients' => [
                    'title' => 'Gestion clients',
                    'description' => 'Gérez vos clients avec leurs informations TVA intracommunautaire.',
                ],
                'quotes' => [
                    'title' => 'Devis professionnels',
                    'description' => 'Créez des devis et convertissez-les en factures en un clic.',
                ],
                'credit_notes' => [
                    'title' => 'Notes de crédit',
                    'description' => 'Générez des avoirs liés à vos factures originales.',
                ],
                'time_tracking' => [
                    'title' => 'Suivi du temps',
                    'description' => 'Tracez votre temps et facturez vos heures automatiquement.',
                ],
                'faia' => [
                    'title' => 'Export FAIA',
                    'description' => 'Exportez vos données au format FAIA pour les contrôles fiscaux.',
                ],
            ],
        ],

        'how_it_works' => [
            'title' => 'COMMENT ÇA MARCHE',
            'heading' => 'Simple comme 1, 2, 3',
            'subtitle' => 'Commencez à facturer en quelques minutes',
            'steps' => [
                'step1' => [
                    'title' => 'Créez votre compte',
                    'description' => 'Inscrivez-vous gratuitement et configurez votre entreprise.',
                ],
                'step2' => [
                    'title' => 'Ajoutez vos clients',
                    'description' => 'Importez ou créez vos fiches clients avec leurs informations.',
                ],
                'step3' => [
                    'title' => 'Facturez !',
                    'description' => 'Créez et envoyez vos factures professionnelles en quelques clics.',
                ],
            ],
            'cta' => 'Créer mon compte gratuit',
        ],

        'stats' => [
            'faia_compliant' => 'Conforme FAIA',
            'vat_luxembourg' => 'TVA Luxembourg',
            'unlimited_invoices' => 'Factures illimitées*',
            'online_access' => 'Accès en ligne',
        ],

        'pricing' => [
            'title' => 'TARIFS',
            'heading' => 'Des prix simples et transparents',
            'subtitle' => 'Commencez gratuitement, évoluez selon vos besoins',
            'popular' => 'POPULAIRE',
            'start' => 'Commencer',
            'sign_up' => 'S\'inscrire',
            'coming_soon' => 'Bientôt disponible',
            'plans' => [
                'discovery' => [
                    'name' => 'Starter',
                    'description' => 'Pour démarrer',
                    'price' => 'Gratuit',
                    'features' => [
                        '2 clients maximum',
                        '2 factures/mois',
                        '2 devis/mois',
                    ],
                ],
                'professional' => [
                    'name' => 'Pro',
                    'description' => 'Pour les indépendants',
                    'price' => '7€/mois',
                    'features' => [
                        'Clients illimités',
                        'Factures illimitées',
                        'Export FAIA (contrôle fiscal)',
                        'Archivage PDF 10 ans',
                    ],
                ],
                'enterprise' => [
                    'name' => 'Entreprise',
                    'description' => 'Pour les équipes',
                    'price' => 'Sur mesure',
                    'features' => [
                        'Tout de Pro',
                        'Multi-utilisateurs',
                        'API & intégrations',
                        'Support dédié',
                    ],
                ],
            ],
        ],

        'faq' => [
            'title' => 'FAQ',
            'heading' => 'Questions fréquentes',
            'subtitle' => 'Tout ce que vous devez savoir',
            'items' => [
                'faia' => [
                    'question' => 'Qu\'est-ce que le format FAIA ?',
                    'answer' => 'Le FAIA (Fichier d\'Audit Informatisé AED) est un format standardisé exigé par l\'Administration des contributions directes du Luxembourg lors des contrôles fiscaux. Faktur.lu génère automatiquement ce fichier conforme.',
                ],
                'compliant' => [
                    'question' => 'Mes factures sont-elles conformes ?',
                    'answer' => 'Oui, toutes les factures générées par faktur.lu respectent les mentions légales obligatoires au Luxembourg : numéro de TVA, numérotation séquentielle, mentions légales, etc.',
                ],
                'credit_notes' => [
                    'question' => 'Comment gérer les avoirs ?',
                    'answer' => 'Vous pouvez créer des notes de crédit liées à vos factures originales. Le système maintient la traçabilité complète pour vos obligations comptables.',
                ],
                'time_tracking' => [
                    'question' => 'Comment fonctionne le suivi du temps ?',
                    'answer' => 'Lancez un chronomètre ou saisissez vos heures manuellement. Convertissez ensuite vos entrées de temps en lignes de facture en un clic.',
                ],
                'security' => [
                    'question' => 'Mes données sont-elles sécurisées ?',
                    'answer' => 'Vos données sont hébergées en Europe, chiffrées et sauvegardées quotidiennement. Nous sommes conformes au RGPD.',
                ],
                'free_trial' => [
                    'question' => 'Puis-je essayer gratuitement ?',
                    'answer' => 'Oui ! Le plan Starter est gratuit pour toujours avec jusqu\'à 2 clients et 2 factures par mois. Idéal pour démarrer.',
                ],
            ],
        ],

        'cta' => [
            'heading' => 'Prêt à simplifier votre facturation ?',
            'subtitle' => 'Rejoignez les entrepreneurs luxembourgeois qui font confiance à faktur.lu',
            'button' => 'Créer mon compte gratuit',
        ],

        'footer' => [
            'description' => 'La facturation simple et conforme pour les indépendants au Luxembourg.',
            'product' => 'Produit',
            'compliance' => 'Conformité',
            'faia_export' => 'Export FAIA',
            'vat_luxembourg' => 'TVA 17%',
            'gdpr' => 'RGPD',
            'faia_compliant' => 'Conforme aux exigences fiscales luxembourgeoises',
            'copyright' => '© 2026 faktur.lu. Tous droits réservés.',
        ],
    ],
];
