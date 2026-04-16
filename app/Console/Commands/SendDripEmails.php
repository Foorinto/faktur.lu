<?php

namespace App\Console\Commands;

use App\Mail\DripCampaignEmail;
use App\Models\DripEmail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDripEmails extends Command
{
    protected $signature = 'drip:send';
    protected $description = 'Envoie les emails de la campagne drip aux utilisateurs en période d\'essai';

    /**
     * Drip sequence: day => email_key
     */
    protected array $sequence = [
        0  => 'welcome',
        1  => 'first_invoice',
        3  => 'features',
        5  => 'social_proof',
        7  => 'mid_trial',
        10 => 'pricing',
        12 => 'urgency',
        13 => 'last_chance',
        15 => 'expired_offer',
        30 => 'review_request',
    ];

    public function handle(): int
    {
        $users = User::query()
            ->where('drip_unsubscribed', false)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->where('account_status', '!=', 'cancelled')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $result = $this->processUser($user);
            $sent += $result['sent'];
            $skipped += $result['skipped'];
        }

        $this->info("Drip campaign: {$sent} emails envoyés, {$skipped} ignorés.");

        return 0;
    }

    protected function processUser(User $user): array
    {
        $sent = 0;
        $skipped = 0;

        // Skip if user has an active paid subscription
        if ($user->subscribed('default') && !$user->onTrial('default')) {
            return compact('sent', 'skipped');
        }

        $daysSinceRegistration = (int) $user->created_at->diffInDays(now());

        foreach ($this->sequence as $day => $emailKey) {
            // Not time yet for this email
            if ($daysSinceRegistration < $day) {
                continue;
            }

            // Already sent
            if (DripEmail::wasHandled($user->id, $emailKey)) {
                continue;
            }

            // Don't send multiple emails at once — send only the most recent due one
            // Skip if there's a more recent email that should be sent
            if ($this->hasNewerPendingEmail($user->id, $daysSinceRegistration, $day)) {
                // Record as skipped (user missed this window)
                DripEmail::record($user->id, $emailKey, 'skipped');
                $skipped++;
                continue;
            }

            // Apply smart skip rules
            if ($this->shouldSkip($user, $emailKey)) {
                DripEmail::record($user->id, $emailKey, 'skipped');
                $skipped++;
                continue;
            }

            $this->sendDripEmail($user, $emailKey);
            $sent++;

            // Only send one email per user per run
            break;
        }

        return compact('sent', 'skipped');
    }

    protected function hasNewerPendingEmail(int $userId, int $daysSinceRegistration, int $currentDay): bool
    {
        foreach ($this->sequence as $day => $emailKey) {
            if ($day > $currentDay && $day <= $daysSinceRegistration && !DripEmail::wasHandled($userId, $emailKey)) {
                return true;
            }
        }

        return false;
    }

    protected function shouldSkip(User $user, string $emailKey): bool
    {
        return match ($emailKey) {
            // Skip "first invoice" email if user already created one
            'first_invoice' => $user->userInvoices()->exists(),
            // Skip pricing/urgency/last_chance if already subscribed
            'pricing', 'urgency', 'last_chance' => $user->subscribed('default'),
            // Skip expired offer if user subscribed before trial ended
            'expired_offer' => $user->subscribed('default') || !$user->isTrialExpired(),
            // Skip review request if user never created an invoice
            'review_request' => !$user->userInvoices()->exists(),
            default => false,
        };
    }

    protected function sendDripEmail(User $user, string $emailKey): void
    {
        $locale = $user->locale ?? 'fr';
        $translations = $this->getEmailContent($emailKey, $locale, $user);

        try {
            Mail::to($user->email)->send(new DripCampaignEmail(
                user: $user,
                emailKey: $emailKey,
                emailSubject: $translations['subject'],
                emailBody: $translations['body'],
            ));

            DripEmail::record($user->id, $emailKey, 'sent');

            Log::info("Drip email '{$emailKey}' sent to {$user->email}");
        } catch (\Exception $e) {
            DripEmail::record($user->id, $emailKey, 'failed');

            Log::error("Drip email '{$emailKey}' failed for {$user->email}: {$e->getMessage()}");
        }
    }

    protected function getEmailContent(string $emailKey, string $locale, User $user): array
    {
        $daysRemaining = max(0, $user->trialDaysRemaining() ?? 0);
        $name = $user->name;

        $content = $this->emailTemplates($locale);

        if (!isset($content[$emailKey])) {
            return ['subject' => '', 'body' => ''];
        }

        $template = $content[$emailKey];

        // Replace variables
        $replacements = [
            ':name' => $name,
            ':days' => $daysRemaining,
            ':app_url' => config('app.url'),
        ];

        return [
            'subject' => str_replace(array_keys($replacements), array_values($replacements), $template['subject']),
            'body' => str_replace(array_keys($replacements), array_values($replacements), $template['body']),
        ];
    }

    protected function emailTemplates(string $locale): array
    {
        $templates = [
            'fr' => [
                'welcome' => [
                    'subject' => 'Bienvenue sur faktur.lu ! Voici comment démarrer',
                    'body' => "Merci d'avoir créé votre compte sur faktur.lu !\n\nVotre essai gratuit de **14 jours** vient de commencer. Pendant cette période, vous avez accès à toutes les fonctionnalités.\n\n**Pour bien démarrer :**\n\n1. **Complétez vos informations** — Ajoutez votre entreprise, logo et coordonnées bancaires\n2. **Créez votre premier client** — Renseignez ses informations et numéro de TVA\n3. **Envoyez votre première facture** — Conforme au Luxembourg, numérotation automatique\n\nVotre fichier FAIA sera généré automatiquement à chaque facture. Prêt pour le contrôle fiscal !",
                ],
                'first_invoice' => [
                    'subject' => 'Avez-vous créé votre première facture ?',
                    'body' => "Vous avez créé votre compte hier et nous espérons que tout se passe bien !\n\n**Saviez-vous que créer une facture prend moins de 2 minutes ?**\n\n- Sélectionnez votre client (ou créez-en un)\n- Ajoutez vos lignes de facturation\n- La TVA à 17% est calculée automatiquement\n- Cliquez sur \"Finaliser\" puis \"Envoyer par email\"\n\nVotre facture sera conforme aux exigences luxembourgeoises, avec numérotation séquentielle et mentions légales obligatoires.\n\n**Astuce** : Importez vos clients existants en masse via un fichier Excel ou CSV.",
                ],
                'features' => [
                    'subject' => 'Découvrez ce que faktur.lu peut faire pour vous',
                    'body' => "Vous utilisez faktur.lu depuis quelques jours. Voici des fonctionnalités que vous n'avez peut-être pas encore découvertes :\n\n**Suivi du temps** — Lancez un chronomètre, puis convertissez vos heures en facture en un clic.\n\n**Gestion de projets** — Organisez vos tâches en Kanban, suivez vos budgets par projet.\n\n**Devis professionnels** — Créez des devis et transformez-les en facture instantanément quand le client accepte.\n\n**Export FAIA** — Généré automatiquement, conforme au format 2.01 de l'AED. Validez-le avec notre validateur gratuit.\n\n**Portail comptable** — Invitez votre fiduciaire pour qu'elle accède à vos données en lecture seule.",
                ],
                'social_proof' => [
                    'subject' => 'Pourquoi les indépendants luxembourgeois choisissent faktur.lu',
                    'body' => "Depuis son lancement, faktur.lu aide des indépendants et PME au Luxembourg à simplifier leur facturation.\n\n**Ce qu'ils apprécient le plus :**\n\n- \"Plus besoin de me soucier du FAIA, c'est automatique\" — *Consultant IT*\n- \"J'ai gagné 2h par semaine sur ma facturation\" — *Architecte indépendant*\n- \"Mon comptable adore le portail, il a tout sous la main\" — *Freelance marketing*\n\n**Pourquoi choisir faktur.lu ?**\n\n- Le seul logiciel conçu spécifiquement pour le Luxembourg\n- FAIA automatique à chaque facture\n- 4 langues (FR, DE, EN, LB)\n- À partir de 5€/mois seulement",
                ],
                'mid_trial' => [
                    'subject' => 'Mi-parcours : où en êtes-vous avec faktur.lu ?',
                    'body' => "Vous êtes à la moitié de votre essai gratuit. C'est le bon moment pour faire le point !\n\n**Checklist mi-parcours :**\n\n- [ ] Informations entreprise complétées\n- [ ] Premier client créé\n- [ ] Première facture envoyée\n- [ ] Logo et branding personnalisés\n- [ ] Paramètres TVA vérifiés\n- [ ] Export FAIA testé\n\nSi vous n'avez pas encore tout exploré, pas de panique ! Il vous reste **:days jours** pour tester toutes les fonctionnalités.\n\n**Besoin d'aide ?** Contactez-nous via le support intégré, nous répondons sous 24h.",
                ],
                'pricing' => [
                    'subject' => 'Quel plan faktur.lu est fait pour vous ?',
                    'body' => "Votre essai se termine dans **:days jours**. Voici nos plans pour continuer à facturer sereinement :\n\n**Gratuit** — 0€/mois\n- 5 clients, 3 factures/mois\n- Idéal pour tester\n\n**Essentiel** — 5€/mois\n- 100 clients, 50 factures/mois\n- Projets, suivi du temps, portail comptable\n- Export FAIA inclus\n\n**Pro** — 15€/mois\n- Tout illimité\n- CRM, module RH, Peppol, archivage PDF/A\n\n**Économisez 20%** avec la facturation annuelle.\n\nTous les plans incluent l'export FAIA et la conformité luxembourgeoise.",
                ],
                'urgency' => [
                    'subject' => 'Plus que 2 jours d\'essai faktur.lu',
                    'body' => "Votre essai gratuit se termine dans **2 jours**.\n\nAprès cette date, votre compte passera en mode gratuit limité (5 clients, 3 factures/mois). Vos données seront conservées, mais certaines fonctionnalités ne seront plus accessibles.\n\n**Pour continuer sans interruption**, choisissez votre plan dès maintenant.\n\nÀ partir de **5€/mois** seulement pour le plan Essentiel.",
                ],
                'last_chance' => [
                    'subject' => 'Dernier jour d\'essai — ne perdez pas vos avantages',
                    'body' => "C'est votre **dernier jour** d'essai gratuit sur faktur.lu.\n\nDemain, votre compte passera en mode gratuit limité. Vos données sont en sécurité, mais vous perdrez l'accès aux fonctionnalités avancées.\n\n**Souscrivez aujourd'hui** pour continuer à profiter de :\n- Clients et factures en nombre\n- Export FAIA automatique\n- Suivi du temps et projets\n- Portail comptable\n\nLe plan Essentiel à **5€/mois** est souvent tout ce qu'il faut pour un indépendant.",
                ],
                'expired_offer' => [
                    'subject' => 'Votre essai est terminé — une offre spéciale pour vous',
                    'body' => "Votre essai gratuit est terminé, mais vos données sont toujours là !\n\nVotre compte est actuellement en mode gratuit limité. Vous pouvez reprendre exactement où vous en étiez en souscrivant à un plan.\n\n**Offre de bienvenue :** Profitez de la facturation annuelle et économisez l'équivalent de 2 mois.\n\n- **Essentiel** : 5€/mois → 50€/an (au lieu de 60€)\n- **Pro** : 15€/mois → 150€/an (au lieu de 180€)\n\nVos factures, clients et paramètres sont intacts. Il suffit de choisir votre plan pour reprendre.",
                ],
                'review_request' => [
                    'subject' => 'Votre avis compte — aidez d\'autres entrepreneurs luxembourgeois',
                    'body' => "Cela fait maintenant 30 jours que vous utilisez faktur.lu et nous esperons que l'outil vous simplifie la vie !\n\n**Pourriez-vous nous accorder 2 minutes ?**\n\nVotre avis sur Google aide d'autres independants et PME au Luxembourg a decouvrir faktur.lu. Chaque avis compte enormement pour une petite entreprise comme la notre.\n\n[**Laisser un avis sur Google**](:app_url)\n\nMerci infiniment pour votre confiance et votre soutien !",
                ],
            ],
            'en' => [
                'welcome' => [
                    'subject' => 'Welcome to faktur.lu! Here\'s how to get started',
                    'body' => "Thank you for creating your account on faktur.lu!\n\nYour **14-day** free trial has just started. During this period, you have access to all features.\n\n**To get started:**\n\n1. **Complete your information** — Add your company, logo and bank details\n2. **Create your first client** — Enter their information and VAT number\n3. **Send your first invoice** — Luxembourg-compliant, automatic numbering\n\nYour FAIA file will be automatically generated with each invoice. Ready for tax audits!",
                ],
                'first_invoice' => [
                    'subject' => 'Have you created your first invoice?',
                    'body' => "You created your account yesterday and we hope everything is going well!\n\n**Did you know that creating an invoice takes less than 2 minutes?**\n\n- Select your client (or create one)\n- Add your invoice lines\n- VAT at 17% is calculated automatically\n- Click \"Finalize\" then \"Send by email\"\n\nYour invoice will comply with Luxembourg requirements, with sequential numbering and mandatory legal mentions.\n\n**Tip**: Import your existing clients in bulk via an Excel or CSV file.",
                ],
                'features' => [
                    'subject' => 'Discover what faktur.lu can do for you',
                    'body' => "You've been using faktur.lu for a few days. Here are features you might not have discovered yet:\n\n**Time tracking** — Start a timer, then convert your hours into an invoice with one click.\n\n**Project management** — Organize your tasks in Kanban, track your budgets per project.\n\n**Professional quotes** — Create quotes and convert them to invoices instantly when the client accepts.\n\n**FAIA export** — Automatically generated, compliant with AED format 2.01. Validate it with our free validator.\n\n**Accountant portal** — Invite your fiduciary to access your data in read-only mode.",
                ],
                'social_proof' => [
                    'subject' => 'Why Luxembourg freelancers choose faktur.lu',
                    'body' => "Since its launch, faktur.lu helps freelancers and SMEs in Luxembourg simplify their invoicing.\n\n**What they appreciate most:**\n\n- \"No more worrying about FAIA, it's automatic\" — *IT Consultant*\n- \"I saved 2 hours per week on my invoicing\" — *Independent Architect*\n- \"My accountant loves the portal, everything is at hand\" — *Marketing Freelancer*\n\n**Why choose faktur.lu?**\n\n- The only software designed specifically for Luxembourg\n- Automatic FAIA with every invoice\n- 4 languages (FR, DE, EN, LB)\n- From only 5€/month",
                ],
                'mid_trial' => [
                    'subject' => 'Mid-trial: how are you doing with faktur.lu?',
                    'body' => "You're halfway through your free trial. It's a good time to check in!\n\n**Mid-trial checklist:**\n\n- [ ] Company information completed\n- [ ] First client created\n- [ ] First invoice sent\n- [ ] Logo and branding customized\n- [ ] VAT settings verified\n- [ ] FAIA export tested\n\nIf you haven't explored everything yet, don't worry! You have **:days days** left to test all features.\n\n**Need help?** Contact us via the built-in support, we respond within 24 hours.",
                ],
                'pricing' => [
                    'subject' => 'Which faktur.lu plan is right for you?',
                    'body' => "Your trial ends in **:days days**. Here are our plans to keep invoicing smoothly:\n\n**Free** — 0€/month\n- 5 clients, 3 invoices/month\n- Ideal for testing\n\n**Essentiel** — 5€/month\n- 100 clients, 50 invoices/month\n- Projects, time tracking, accountant portal\n- FAIA export included\n\n**Pro** — 15€/month\n- Everything unlimited\n- CRM, HR module, Peppol, PDF/A archiving\n\n**Save 20%** with annual billing.\n\nAll plans include FAIA export and Luxembourg compliance.",
                ],
                'urgency' => [
                    'subject' => 'Only 2 days left on your faktur.lu trial',
                    'body' => "Your free trial ends in **2 days**.\n\nAfter this date, your account will switch to the limited free plan (5 clients, 3 invoices/month). Your data will be preserved, but some features won't be accessible.\n\n**To continue without interruption**, choose your plan now.\n\nFrom only **5€/month** for the Essentiel plan.",
                ],
                'last_chance' => [
                    'subject' => 'Last day of trial — don\'t lose your benefits',
                    'body' => "This is your **last day** of free trial on faktur.lu.\n\nTomorrow, your account will switch to the limited free plan. Your data is safe, but you'll lose access to advanced features.\n\n**Subscribe today** to keep enjoying:\n- More clients and invoices\n- Automatic FAIA export\n- Time tracking and projects\n- Accountant portal\n\nThe Essentiel plan at **5€/month** is often all a freelancer needs.",
                ],
                'expired_offer' => [
                    'subject' => 'Your trial has ended — a special offer for you',
                    'body' => "Your free trial is over, but your data is still there!\n\nYour account is currently on the limited free plan. You can pick up right where you left off by subscribing to a plan.\n\n**Welcome offer:** Take advantage of annual billing and save the equivalent of 2 months.\n\n- **Essentiel**: 5€/month → 50€/year (instead of 60€)\n- **Pro**: 15€/month → 150€/year (instead of 180€)\n\nYour invoices, clients and settings are intact. Just choose your plan to resume.",
                ],
                'review_request' => [
                    'subject' => 'Your opinion matters — help other Luxembourg entrepreneurs',
                    'body' => "You've been using faktur.lu for 30 days now and we hope it's making your life easier!\n\n**Could you spare 2 minutes?**\n\nYour Google review helps other freelancers and SMEs in Luxembourg discover faktur.lu. Every review means a lot to a small company like ours.\n\n[**Leave a Google review**](:app_url)\n\nThank you so much for your trust and support!",
                ],
            ],
            'de' => [
                'welcome' => [
                    'subject' => 'Willkommen bei faktur.lu! So starten Sie',
                    'body' => "Vielen Dank fuer die Erstellung Ihres Kontos auf faktur.lu!\n\nIhre **14-taegige** kostenlose Testphase hat begonnen. Waehrend dieser Zeit haben Sie Zugang zu allen Funktionen.\n\n**So starten Sie:**\n\n1. **Vervollstaendigen Sie Ihre Angaben** — Fuegen Sie Ihr Unternehmen, Logo und Bankdaten hinzu\n2. **Erstellen Sie Ihren ersten Kunden** — Geben Sie seine Daten und MwSt-Nummer ein\n3. **Senden Sie Ihre erste Rechnung** — Luxemburg-konform, automatische Nummerierung\n\nIhre FAIA-Datei wird bei jeder Rechnung automatisch generiert. Bereit fuer die Steuerpruefung!",
                ],
                'first_invoice' => [
                    'subject' => 'Haben Sie Ihre erste Rechnung erstellt?',
                    'body' => "Sie haben gestern Ihr Konto erstellt und wir hoffen, dass alles gut laeuft!\n\n**Wussten Sie, dass die Erstellung einer Rechnung weniger als 2 Minuten dauert?**\n\n- Waehlen Sie Ihren Kunden (oder erstellen Sie einen)\n- Fuegen Sie Ihre Rechnungsposten hinzu\n- Die MwSt von 17% wird automatisch berechnet\n- Klicken Sie auf \"Finalisieren\" und dann \"Per E-Mail senden\"\n\n**Tipp**: Importieren Sie bestehende Kunden per Excel- oder CSV-Datei.",
                ],
                'features' => [
                    'subject' => 'Entdecken Sie, was faktur.lu fuer Sie tun kann',
                    'body' => "Sie nutzen faktur.lu seit einigen Tagen. Hier sind Funktionen, die Sie vielleicht noch nicht entdeckt haben:\n\n**Zeiterfassung** — Starten Sie einen Timer und wandeln Sie Ihre Stunden mit einem Klick in eine Rechnung um.\n\n**Projektverwaltung** — Organisieren Sie Ihre Aufgaben in Kanban, verfolgen Sie Budgets pro Projekt.\n\n**Professionelle Angebote** — Erstellen Sie Angebote und wandeln Sie sie sofort in Rechnungen um.\n\n**FAIA-Export** — Automatisch generiert, konform mit AED-Format 2.01.\n\n**Buchhalterportal** — Laden Sie Ihre Treuhandgesellschaft ein.",
                ],
                'social_proof' => [
                    'subject' => 'Warum luxemburgische Freiberufler faktur.lu waehlen',
                    'body' => "faktur.lu hilft Freiberuflern und KMU in Luxemburg, ihre Rechnungsstellung zu vereinfachen.\n\n**Was sie am meisten schaetzen:**\n\n- \"Keine Sorgen mehr wegen FAIA, es ist automatisch\" — *IT-Berater*\n- \"Ich spare 2 Stunden pro Woche bei der Rechnungsstellung\" — *Freier Architekt*\n- \"Mein Buchhalter liebt das Portal\" — *Marketing-Freelancer*\n\n**Warum faktur.lu?**\n\n- Die einzige Software speziell fuer Luxemburg\n- Automatisches FAIA bei jeder Rechnung\n- 4 Sprachen (FR, DE, EN, LB)\n- Ab nur 5 EUR/Monat",
                ],
                'mid_trial' => [
                    'subject' => 'Halbzeit: Wie laeuft es mit faktur.lu?',
                    'body' => "Sie sind in der Mitte Ihrer kostenlosen Testphase. Zeit fuer eine Zwischenbilanz!\n\n**Halbzeit-Checkliste:**\n\n- [ ] Unternehmensdaten vervollstaendigt\n- [ ] Erster Kunde erstellt\n- [ ] Erste Rechnung gesendet\n- [ ] Logo und Branding angepasst\n- [ ] MwSt-Einstellungen geprueft\n- [ ] FAIA-Export getestet\n\nSie haben noch **:days Tage** zum Testen aller Funktionen.\n\n**Brauchen Sie Hilfe?** Kontaktieren Sie uns ueber den integrierten Support.",
                ],
                'pricing' => [
                    'subject' => 'Welcher faktur.lu-Plan passt zu Ihnen?',
                    'body' => "Ihre Testphase endet in **:days Tagen**. Unsere Plaene:\n\n**Kostenlos** — 0 EUR/Monat\n- 5 Kunden, 3 Rechnungen/Monat\n\n**Essentiel** — 5 EUR/Monat\n- 100 Kunden, 50 Rechnungen/Monat\n- Projekte, Zeiterfassung, Buchhalterportal\n\n**Pro** — 15 EUR/Monat\n- Alles unbegrenzt\n- CRM, HR-Modul, Peppol, PDF/A-Archivierung\n\n**Sparen Sie 20%** mit jaehrlicher Abrechnung.",
                ],
                'urgency' => [
                    'subject' => 'Nur noch 2 Tage Testphase bei faktur.lu',
                    'body' => "Ihre kostenlose Testphase endet in **2 Tagen**.\n\nDanach wechselt Ihr Konto zum limitierten kostenlosen Plan (5 Kunden, 3 Rechnungen/Monat). Ihre Daten bleiben erhalten.\n\n**Fuer nahtlose Weiternutzung** waehlen Sie jetzt Ihren Plan.\n\nAb nur **5 EUR/Monat** fuer den Essentiel-Plan.",
                ],
                'last_chance' => [
                    'subject' => 'Letzter Tag der Testphase',
                    'body' => "Heute ist Ihr **letzter Tag** der kostenlosen Testphase.\n\nMorgen wechselt Ihr Konto zum limitierten kostenlosen Plan. Ihre Daten sind sicher.\n\n**Abonnieren Sie heute** fuer:\n- Mehr Kunden und Rechnungen\n- Automatischen FAIA-Export\n- Zeiterfassung und Projekte\n- Buchhalterportal\n\nDer Essentiel-Plan ab **5 EUR/Monat**.",
                ],
                'expired_offer' => [
                    'subject' => 'Ihre Testphase ist beendet — ein Sonderangebot fuer Sie',
                    'body' => "Ihre Testphase ist vorbei, aber Ihre Daten sind noch da!\n\n**Willkommensangebot:** Jaehrliche Abrechnung und 2 Monate sparen.\n\n- **Essentiel**: 5 EUR/Monat → 50 EUR/Jahr (statt 60 EUR)\n- **Pro**: 15 EUR/Monat → 150 EUR/Jahr (statt 180 EUR)\n\nIhre Rechnungen, Kunden und Einstellungen sind intakt.",
                ],
                'review_request' => [
                    'subject' => 'Ihre Meinung zaehlt — helfen Sie anderen luxemburgischen Unternehmern',
                    'body' => "Sie nutzen faktur.lu seit 30 Tagen und wir hoffen, dass es Ihnen die Arbeit erleichtert!\n\n**Koennten Sie uns 2 Minuten schenken?**\n\nIhre Google-Bewertung hilft anderen Freiberuflern und KMU in Luxemburg, faktur.lu zu entdecken. Jede Bewertung bedeutet uns sehr viel.\n\n[**Google-Bewertung abgeben**](:app_url)\n\nVielen Dank fuer Ihr Vertrauen und Ihre Unterstuetzung!",
                ],
            ],
            'lb' => [
                'welcome' => [
                    'subject' => 'Wëllkomm bei faktur.lu! Esou fänkt Dir un',
                    'body' => "Merci datt Dir Äre Kont op faktur.lu erstallt hutt!\n\nÄr gratis **14-Deeg** Testphas huet ugefaangen. An dëser Zäit hutt Dir Zougang zu alle Funktiounen.\n\n**Fir unzefänken:**\n\n1. **Vervollstännegt Är Informatiounen** — Firmnumm, Logo a Bankdonnéeën\n2. **Erstellt Ären éischte Client** — Donnéeën an TVA-Nummer\n3. **Schéckt Är éischt Rechnung** — Lëtzebuerg-konform, automatesch Nummeréierung\n\nÄre FAIA-Fichier gëtt automatesch generéiert!",
                ],
                'first_invoice' => [
                    'subject' => 'Hutt Dir Är éischt Rechnung erstallt?',
                    'body' => "Dir hutt gëschter Äre Kont erstallt. **Wousst Dir datt eng Rechnung erstellen manner wéi 2 Minutte brauch?**\n\n- Wielt Äre Client\n- Füügt Är Rechnungsposten bäi\n- D'TVA vun 17% gëtt automatesch berechent\n- Klickt op \"Finaliséieren\" an \"Per E-Mail schécken\"\n\n**Tipp**: Importéiert Är bestehend Clienten per Excel oder CSV.",
                ],
                'features' => [
                    'subject' => 'Entdeckt wat faktur.lu fir Iech maache kann',
                    'body' => "Dir benotzt faktur.lu zanter e puer Deeg. Hei sinn Funktiounen déi Dir vläicht nach net entdeckt hutt:\n\n**Zäiterfassung** — Start en Timer, da wandelt Är Stonnen mat engem Klick an eng Rechnung ëm.\n\n**Projektverwaltung** — Organiséiert Är Aufgaben a Kanban.\n\n**Professionell Devis** — Erstellt Devis a wandelt se direkt a Rechnungen ëm.\n\n**FAIA-Export** — Automatesch generéiert, konform mam AED-Format 2.01.\n\n**Comptablesportal** — Invitéiert Är Treihandgesellschaft.",
                ],
                'social_proof' => [
                    'subject' => 'Firwat lëtzebuerger Freelancer faktur.lu wielen',
                    'body' => "faktur.lu hëlleft Freelanceren a KMU zu Lëtzebuerg hir Fakturatioun ze vereinfachen.\n\n**Wat si am meeschte schätzen:**\n\n- \"Keng Suergen méi wéinst FAIA, et ass automatesch\"\n- \"Ech spueren 2 Stonnen d'Woch bei der Fakturatioun\"\n- \"Mäi Comptable léift d'Portal\"\n\n**Firwat faktur.lu?**\n\n- Déi eenzeg Software speziell fir Lëtzebuerg\n- Automatescht FAIA bei all Rechnung\n- 4 Sproochen (FR, DE, EN, LB)\n- Vun nëmmen 5 EUR/Mount un",
                ],
                'mid_trial' => [
                    'subject' => 'Hallschent: Wéi leeft et mat faktur.lu?',
                    'body' => "Dir sidd an der Mëtt vun Ärer gratis Testphas.\n\n**Hallschent-Checklëscht:**\n\n- [ ] Firmeninformatiounen vervollstännegt\n- [ ] Éischte Client erstallt\n- [ ] Éischt Rechnung geschéckt\n- [ ] Logo a Branding ugepasst\n- [ ] TVA-Astellungen gepréift\n- [ ] FAIA-Export getest\n\nDir hutt nach **:days Deeg** fir all Funktiounen ze testen.",
                ],
                'pricing' => [
                    'subject' => 'Wéi ee faktur.lu-Plang passt zu Iech?',
                    'body' => "Är Testphas leeft a **:days Deeg** of.\n\n**Gratis** — 0 EUR/Mount\n- 5 Clienten, 3 Rechnungen/Mount\n\n**Essentiel** — 5 EUR/Mount\n- 100 Clienten, 50 Rechnungen/Mount\n\n**Pro** — 15 EUR/Mount\n- Alles onlimitéiert\n\n**Spart 20%** mat jäerlecher Ofrechnong.",
                ],
                'urgency' => [
                    'subject' => 'Nëmmen nach 2 Deeg Testphas bei faktur.lu',
                    'body' => "Är gratis Testphas leeft a **2 Deeg** of.\n\nDono wiesselt Äre Kont op de limitéierten gratis Plang. Är Donnéeë bleiwen erhalen.\n\nVun nëmmen **5 EUR/Mount** un fir den Essentiel-Plang.",
                ],
                'last_chance' => [
                    'subject' => 'Leschten Dag vun der Testphas',
                    'body' => "Haut ass Ären **leschten Dag** vun der gratis Testphas.\n\nMuer wiesselt Äre Kont. Är Donnéeë sinn sécher.\n\n**Abonnéiert haut** fir:\n- Méi Clienten a Rechnungen\n- Automateschen FAIA-Export\n- Zäiterfassung a Projeten\n- Comptablesportal\n\nDen Essentiel-Plang vun **5 EUR/Mount** un.",
                ],
                'expired_offer' => [
                    'subject' => 'Är Testphas ass eriwwer — e Spezialangebot fir Iech',
                    'body' => "Är Testphas ass eriwwer, mee Är Donnéeë sinn nach do!\n\n**Wëllkommensangebot:** Jäerlech Ofrechnong a 2 Méint spueren.\n\n- **Essentiel**: 5 EUR/Mount → 50 EUR/Joer\n- **Pro**: 15 EUR/Mount → 150 EUR/Joer\n\nÄr Rechnungen, Clienten an Astellunge sinn intakt.",
                ],
                'review_request' => [
                    'subject' => 'Är Meenung zielt — hëlleft aneren lëtzebuerger Entrepreneuren',
                    'body' => "Dir benotzt faktur.lu zanter 30 Deeg a mir hoffen datt et Iech d'Aarbecht erliichtert!\n\n**Kéint Dir eis 2 Minutten schenken?**\n\nÄr Google-Bewäertung hëlleft anere Freelanceren a KMU zu Lëtzebuerg, faktur.lu z'entdecken. All Bewäertung bedeit eis ganz vill.\n\n[**Google-Bewäertung ofginn**](:app_url)\n\nMerci fir Äert Vertrauen an Är Ënnerstëtzung!",
                ],
            ],
        ];

        return $templates[$locale] ?? $templates['fr'];
    }
}
