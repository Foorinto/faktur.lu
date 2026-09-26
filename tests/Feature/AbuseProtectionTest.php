<?php

namespace Tests\Feature;

use App\Jobs\SendPaymentReminders;
use App\Mail\FlaggedAccountFirstEmailNotification;
use App\Mail\InvoiceMail;
use App\Mail\NewUserRegisteredNotification;
use App\Mail\ReminderMail;
use App\Models\AbuseEvent;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\EmailSettings;
use App\Models\Invoice;
use App\Models\InvoiceEmail;
use App\Models\Plan;
use App\Models\RecurringInvoice;
use App\Models\User;
use App\Services\AbuseProtectionService;
use App\Services\EmailProviderService;
use App\Services\PlanService;
use Database\Seeders\PlansSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Protections contre les inscriptions frauduleuses (FEAT-138).
 *
 * Septembre 2026 : des comptes aux adresses jetables et aux noms de marques
 * usurpées (« Vinted », « Poshmark Support ») envoyaient depuis notre domaine
 * des factures de phishing. Trois défenses et un correctif :
 *   1. adresses jetables refusées à l'inscription ;
 *   2. noms de marques : compte signalé à l'inscription, nom d'entreprise
 *      refusé (celui qui figure sur le PDF et dans les mails) ;
 *   3. cinq documents par jour depuis nos serveurs pendant l'essai ;
 *   4. un compte désactivé ne génère ni ne relance plus rien en ligne de
 *      commande, où le filtre par compte ne s'applique pas.
 */
class AbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Fakt#2026!Secur';

    protected function setUp(): void
    {
        parent::setUp();

        // Sans le seeder, les plans viennent d'une migration ancienne.
        $this->seed(PlansSeeder::class);

        // La liste téléchargée du poste de développement ne doit pas entrer
        // dans les tests : seul le socle de config/abuse.php sert, sauf quand
        // un test écrit sa propre liste.
        Storage::fake('local');

        config(['admin.support_email' => 'admin@faktur.test', 'abuse.trial_daily_document_emails' => 5]);
    }

    // --- Outils -------------------------------------------------------------

    /** @return array<string, mixed> */
    private function inscription(array $surcharge = []): array
    {
        return array_merge([
            'name' => 'Marie Schmit',
            'email' => 'marie@exemple.lu',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'terms' => true,
            'dpa' => true,
            'homepage_url' => '',
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
        ], $surcharge);
    }

    private function trialUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => now()->addDays(10)]);
    }

    private function proUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'trial_ends_at' => now()->subMonth()]);
        Plan::pro()->update(['stripe_price_id_monthly' => 'price_test_pro']);
        $abonnement = $user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => "sub_test_pro_{$user->id}", 'stripe_status' => 'active',
            'stripe_price' => 'price_test_pro', 'quantity' => 1,
        ]);
        $abonnement->items()->create([
            'stripe_id' => "si_test_pro_{$user->id}", 'stripe_product' => 'prod_test_pro',
            'stripe_price' => 'price_test_pro', 'quantity' => 1,
        ]);

        return $user->fresh();
    }

    private function invoiceFor(User $user, array $attributs = []): Invoice
    {
        BusinessSettings::factory()->create(['user_id' => $user->id]);
        $client = Client::factory()->create(['user_id' => $user->id, 'email' => 'client@exemple.lu']);

        return Invoice::factory()->create(array_merge([
            'user_id' => $user->id, 'client_id' => $client->id, 'status' => Invoice::STATUS_FINALIZED,
            'issued_at' => now()->subDays(40), 'due_at' => now()->subDays(10),
        ], $attributs));
    }

    /** Le texte d'un mail rendu, sans balises ni entités. */
    private function texte(string $html): string
    {
        return html_entity_decode(strip_tags($html), ENT_QUOTES);
    }

    private function envoyer(Invoice $invoice)
    {
        return $this->post(route('invoices.send-email', $invoice), [
            'recipient_email' => 'client@exemple.lu',
            'subject' => 'Votre facture',
        ]);
    }

    private function enregistrerEnvois(Invoice $invoice, int $nombre, string $statut = InvoiceEmail::STATUS_SENT, $quand = null): void
    {
        for ($i = 0; $i < $nombre; $i++) {
            $invoice->emails()->create([
                'type' => InvoiceEmail::TYPE_MANUAL, 'recipient_email' => 'client@exemple.lu',
                'subject' => 'Facture', 'status' => $statut, 'sent_at' => $quand ?? now(),
            ]);
        }
    }

    // --- 1. Adresses jetables ------------------------------------------------

    public function test_une_adresse_jetable_est_refusee_a_l_inscription(): void
    {
        $this->post('/register', $this->inscription(['email' => 'arnaque@mailinator.com']))
            ->assertSessionHasErrors(['email' => __('app.validation_disposable_email')]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'arnaque@mailinator.com']);

        // Compté pour le tableau de bord, avec le domaine et jamais l'adresse.
        $this->assertDatabaseHas('abuse_events', ['type' => AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'detail' => 'mailinator.com', 'user_id' => null]);
        $this->assertDatabaseMissing('abuse_events', ['detail' => 'arnaque@mailinator.com']);
    }

    public function test_un_sous_domaine_d_un_domaine_jetable_est_refuse_aussi(): void
    {
        $this->post('/register', $this->inscription(['email' => 'arnaque@boite.yopmail.com']))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_une_adresse_ordinaire_passe(): void
    {
        $this->post('/register', $this->inscription(['email' => 'marie@pt.lu']))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    /** @return array<string, array{string}> */
    public static function domainesReserves(): array
    {
        return [
            'extension .test' => ['jean@example.test'],
            'domaine d exemple' => ['jean@example.com'],
            'sous-domaine d exemple' => ['jean@mail.example.org'],
            'extension .invalid' => ['jean@boite.invalid'],
            'extension .localhost' => ['jean@machine.localhost'],
        ];
    }

    #[DataProvider('domainesReserves')]
    public function test_un_domaine_reserve_est_refuse_a_l_inscription(string $email): void
    {
        $this->post('/register', $this->inscription(['email' => $email]))
            ->assertSessionHasErrors(['email' => __('app.validation_undeliverable_email')]);

        $this->assertGuest();
        $this->assertDatabaseHas('abuse_events', [
            'type' => AbuseEvent::TYPE_RESERVED_DOMAIN,
            'detail' => strtolower(substr($email, strpos($email, '@') + 1)),
        ]);
    }

    public function test_un_domaine_proche_d_un_domaine_reserve_passe(): void
    {
        $protection = app(AbuseProtectionService::class);

        $this->assertFalse($protection->isReservedDomain('a@examples.com'));
        $this->assertFalse($protection->isReservedDomain('a@notexample.com'));
        $this->assertFalse($protection->isReservedDomain('a@testing.lu'));
    }

    public function test_la_liste_telechargee_s_ajoute_au_socle(): void
    {
        Storage::disk('local')->put(AbuseProtectionService::DISPOSABLE_LIST_PATH, "# commentaire\nboite-ephemere.example\n");

        $this->post('/register', $this->inscription(['email' => 'x@boite-ephemere.example']))
            ->assertSessionHasErrors('email');
        $this->post('/register', $this->inscription(['email' => 'y@mailinator.com']))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // --- 2. Noms de marques ---------------------------------------------------

    public function test_un_nom_de_marque_a_l_inscription_signale_le_compte_sans_le_bloquer(): void
    {
        Mail::fake();

        $this->post('/register', $this->inscription(['name' => 'Vinted Support', 'email' => 'vinted@exemple.lu']))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $user = User::where('email', 'vinted@exemple.lu')->firstOrFail();
        $this->assertTrue($user->flagged_for_review);
        $this->assertSame('brand_name:vinted', $user->flagged_reason);
        $this->assertNotNull($user->flagged_at);
        $this->assertDatabaseHas('abuse_events', ['type' => AbuseEvent::TYPE_BRAND_NAME_FLAGGED, 'detail' => 'vinted', 'user_id' => $user->id]);

        // L'administrateur le voit dès l'objet, et dans le corps du mail.
        $avertissement = __('app.email_admin_new_user_flagged', ['reason' => AbuseProtectionService::describeReason('brand_name:vinted')]);
        Mail::assertQueued(NewUserRegisteredNotification::class, function (NewUserRegisteredNotification $mail) use ($user, $avertissement) {
            return $mail->user->is($user)
                && $mail->envelope()->subject === __('app.mail_subject_new_user_flagged', ['name' => 'Vinted Support'])
                && str_contains($this->texte($mail->render()), $avertissement);
        });

        // Rien n'est envoyé au compte lui-même, hors la vérification d'adresse.
        Mail::assertNotQueued(FlaggedAccountFirstEmailNotification::class);
    }

    public function test_un_nom_ordinaire_n_est_pas_signale(): void
    {
        Mail::fake();

        $this->post('/register', $this->inscription())->assertSessionHasNoErrors();

        $user = User::where('email', 'marie@exemple.lu')->firstOrFail();
        $this->assertFalse($user->flagged_for_review);

        Mail::assertQueued(NewUserRegisteredNotification::class, function (NewUserRegisteredNotification $mail) {
            return $mail->envelope()->subject === __('app.mail_subject_new_user_registered', ['name' => 'Marie Schmit'])
                && ! str_contains($this->texte($mail->render()), '⚠️');
        });
    }

    /** @return array<string, array{string, ?string}> */
    public static function noms(): array
    {
        return [
            'marque seule' => ['Vinted', 'vinted'],
            'marque et mot générique' => ['VINTED Support', 'vinted'],
            'lettres séparées' => ['V-i-n-t-e-d', 'vinted'],
            'lettres pointées' => ['U.P.S. Livraisons', 'ups'],
            'chiffre pour une lettre' => ['V1nted Lux', 'vinted'],
            'arobase pour une lettre' => ['P@yPal Services', 'paypal'],
            'un pour un l' => ['Paypa1', 'paypal'],
            'collé à un autre mot' => ['VintedLux Sàrl', 'vinted'],
            'accents' => ['Wällapöp', 'wallapop'],
            'deux mots' => ['Mondial-Relay Point', 'mondial relay'],
            'transporteur court' => ['DHL Express', 'dhl'],
            'mot générique' => ['Service Support', 'support'],
            'compost' => ['Compost SARL', null],
            'administratif' => ['Groupe Administratif', null],
            'amazonie' => ['Amazonie Voyages', null],
            'banquet' => ['Banquet Traiteur', null],
            'supporter' => ['Supporters Club Esch', null],
            'upstream' => ['Upstream Consulting', null],
            'peintre' => ['Peinture Muller', null],
            'vide' => ['', null],
        ];
    }

    #[DataProvider('noms')]
    public function test_la_correspondance_des_marques(string $nom, ?string $attendu): void
    {
        $this->assertSame($attendu, app(AbuseProtectionService::class)->matchesBrand($nom));
    }

    public function test_un_nom_d_entreprise_imitant_une_marque_est_refuse_dans_les_reglages(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        BusinessSettings::factory()->create(['user_id' => $user->id, 'company_name' => 'Atelier Muller', 'legal_name' => 'Atelier Muller Sàrl']);

        $this->put(route('settings.business.update'), $this->reglages(['company_name' => 'V-i-n-t-e-d Lux']))
            ->assertSessionHasErrors(['company_name' => __('app.validation_brand_name')]);

        $this->assertSame('Atelier Muller', BusinessSettings::withoutGlobalScopes()->where('user_id', $user->id)->value('company_name'));
        $this->assertTrue($user->fresh()->flagged_for_review);
        $this->assertSame('company_name:vinted', $user->fresh()->flagged_reason);
        $this->assertDatabaseHas('abuse_events', ['type' => AbuseEvent::TYPE_COMPANY_NAME_REFUSED, 'detail' => 'vinted', 'user_id' => $user->id]);
    }

    public function test_la_raison_sociale_est_controlee_aussi(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        BusinessSettings::factory()->create(['user_id' => $user->id]);

        $this->put(route('settings.business.update'), $this->reglages(['legal_name' => 'PayPal Europe Sàrl']))
            ->assertSessionHasErrors('legal_name');
    }

    public function test_un_nom_deja_enregistre_n_est_pas_bloque_retroactivement(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        BusinessSettings::factory()->create(['user_id' => $user->id, 'company_name' => 'IT Support Lux', 'legal_name' => 'IT Support Lux Sàrl']);

        $this->put(route('settings.business.update'), $this->reglages(['company_name' => 'IT Support Lux', 'legal_name' => 'IT Support Lux Sàrl']))
            ->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->flagged_for_review);

        // Un changement de nom, lui, est contrôlé.
        $this->put(route('settings.business.update'), $this->reglages(['company_name' => 'Support Express', 'legal_name' => 'IT Support Lux Sàrl']))
            ->assertSessionHasErrors('company_name');
    }

    public function test_le_nom_d_entreprise_est_controle_des_l_accueil(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $this->postJson(route('onboarding.company'), [
            'company_name' => 'Poshmark Support',
            'exercise_form' => 'liberal',
        ])->assertStatus(422)->assertJsonValidationErrors('company_name');

        $this->assertTrue($user->fresh()->flagged_for_review);

        $this->postJson(route('onboarding.company'), [
            'company_name' => 'Cabinet Weber',
            'exercise_form' => 'liberal',
        ])->assertOk();
    }

    /** @return array<string, mixed> */
    private function reglages(array $surcharge): array
    {
        return array_merge([
            'current_password' => 'password',
            'company_name' => 'Atelier Muller',
            'legal_name' => 'Atelier Muller Sàrl',
            'address' => '12 rue de la Gare',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'matricule' => '12345678901',
            'iban' => 'LU123456789012345678',
            'bic' => 'BGLLLULL',
            'vat_regime' => 'franchise',
            'exercise_form' => 'liberal',
            'no_establishment_authorization' => true,
            'email' => 'contact@exemple.lu',
        ], $surcharge);
    }

    // --- 3. Envois pendant l'essai -------------------------------------------

    public function test_le_sixieme_envoi_du_jour_est_refuse_pendant_l_essai(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        $invoice = $this->invoiceFor($user);
        $this->actingAs($user);

        for ($i = 1; $i <= 5; $i++) {
            $this->envoyer($invoice)->assertSessionHasNoErrors();
        }

        $this->envoyer($invoice)->assertSessionHasErrors([
            'email' => __('app.trial_email_quota_reached', ['limit' => 5]),
        ]);

        Mail::assertSent(InvoiceMail::class, 5);
        $this->assertSame(1, AbuseEvent::where('type', AbuseEvent::TYPE_TRIAL_QUOTA_REACHED)->where('user_id', $user->id)->count());
    }

    public function test_une_relance_a_la_main_compte_et_est_refusee_au_dela(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        $invoice = $this->invoiceFor($user);
        $this->enregistrerEnvois($invoice, 5);
        $this->actingAs($user);

        $this->post(route('invoices.send-reminder', $invoice), [
            'level' => 1, 'recipient_email' => 'client@exemple.lu', 'subject' => 'Rappel', 'message' => 'Merci de régler.',
        ])->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_un_abonne_n_est_pas_limite(): void
    {
        Mail::fake();
        $user = $this->proUser();
        $invoice = $this->invoiceFor($user);
        $this->enregistrerEnvois($invoice, 5);
        $this->actingAs($user);

        $this->envoyer($invoice)->assertSessionHasNoErrors();

        Mail::assertSent(InvoiceMail::class, 1);
    }

    public function test_un_compte_en_essai_avec_son_propre_fournisseur_n_est_pas_limite(): void
    {
        // Vérifié sur la règle et non par un envoi : sous Mail::fake(), le
        // transport SMTP d'un fournisseur ne peut pas être construit.
        $user = $this->trialUser();
        $invoice = $this->invoiceFor($user);
        $this->enregistrerEnvois($invoice, 5);
        $this->actingAs($user);
        $reglages = EmailSettings::getOrCreate($user);
        $reglages->provider = EmailSettings::PROVIDER_RESEND;
        $reglages->provider_config = ['api_key' => 're_secret'];
        $reglages->save();
        $user = $user->fresh();

        $this->assertFalse(app(EmailProviderService::class)->usesPlatformMailer($user));
        $this->assertNull(app(AbuseProtectionService::class)->trialDocumentEmailQuota($user));
        $this->assertTrue(app(AbuseProtectionService::class)->canSendDocumentEmail($user));
    }

    public function test_un_fournisseur_incomplet_repasse_par_nos_serveurs_et_reste_limite(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        $invoice = $this->invoiceFor($user);
        $this->enregistrerEnvois($invoice, 5);
        $this->actingAs($user);
        $reglages = EmailSettings::getOrCreate($user);
        $reglages->provider = EmailSettings::PROVIDER_RESEND;
        $reglages->provider_config = ['api_key' => ''];
        $reglages->save();

        $this->assertTrue(app(EmailProviderService::class)->usesPlatformMailer($user->fresh()));
        $this->envoyer($invoice)->assertSessionHasErrors('email');
    }

    public function test_seuls_les_envois_reussis_du_jour_comptent(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        $invoice = $this->invoiceFor($user);
        $this->enregistrerEnvois($invoice, 5, InvoiceEmail::STATUS_SENT, now()->subDay());
        $this->enregistrerEnvois($invoice, 5, InvoiceEmail::STATUS_FAILED);
        $this->actingAs($user);

        $this->envoyer($invoice)->assertSessionHasNoErrors();
    }

    public function test_supprimer_une_facture_envoyee_ne_rend_pas_de_place(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        $supprimee = $this->invoiceFor($user);
        $this->enregistrerEnvois($supprimee, 5);
        // Le modèle refuse de supprimer une facture finalisée ; on force la
        // suppression douce en base pour vérifier que le compteur ne s'en
        // laisse pas conter si une facture envoyée disparaissait un jour.
        Invoice::withoutGlobalScopes()->whereKey($supprimee->id)->update(['deleted_at' => now()]);

        $client = Client::withoutGlobalScopes()->where('user_id', $user->id)->first();
        $autre = Invoice::factory()->create([
            'user_id' => $user->id, 'client_id' => $client->id, 'status' => Invoice::STATUS_FINALIZED, 'issued_at' => now(),
        ]);
        $this->actingAs($user);

        $this->envoyer($autre)->assertSessionHasErrors('email');
    }

    public function test_le_premier_envoi_d_un_compte_signale_previent_l_administrateur_une_fois(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        app(AbuseProtectionService::class)->flag($user, 'brand_name:vinted');
        $invoice = $this->invoiceFor($user);
        $this->actingAs($user);

        $this->envoyer($invoice)->assertSessionHasNoErrors();
        $this->envoyer($invoice)->assertSessionHasNoErrors();

        Mail::assertQueued(FlaggedAccountFirstEmailNotification::class, 1);
        Mail::assertQueued(FlaggedAccountFirstEmailNotification::class, function (FlaggedAccountFirstEmailNotification $mail) use ($user) {
            return $mail->hasTo('admin@faktur.test') && $mail->user->is($user) && $mail->recipient === 'client@exemple.lu'
                && str_contains($this->texte($mail->render()), AbuseProtectionService::describeReason('brand_name:vinted'));
        });
    }

    public function test_un_compte_non_signale_ne_previent_personne(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        $invoice = $this->invoiceFor($user);
        $this->actingAs($user);

        $this->envoyer($invoice)->assertSessionHasNoErrors();

        Mail::assertNotQueued(FlaggedAccountFirstEmailNotification::class);
    }

    public function test_le_job_des_relances_respecte_le_plafond_de_l_essai(): void
    {
        Mail::fake();
        $user = $this->trialUser();
        $invoice = $this->invoiceFor($user);
        $this->enregistrerEnvois($invoice, 5);
        EmailSettings::getOrCreate($user)->update(['reminders_enabled' => true]);

        app(SendPaymentReminders::class)->handle(app(EmailProviderService::class), app(PlanService::class));

        Mail::assertNotSent(ReminderMail::class);
        $this->assertSame(0, $invoice->emails()->where('type', InvoiceEmail::TYPE_REMINDER_1)->count());
    }

    // --- 4. Comptes désactivés en ligne de commande -----------------------------

    public function test_le_job_ne_relance_plus_pour_un_compte_desactive(): void
    {
        Mail::fake();
        $user = $this->proUser();
        $this->invoiceFor($user);
        EmailSettings::getOrCreate($user)->update(['reminders_enabled' => true]);
        $user->forceFill(['is_active' => false])->save();

        app(SendPaymentReminders::class)->handle(app(EmailProviderService::class), app(PlanService::class));

        Mail::assertNothingSent();
    }

    public function test_une_recurrence_d_un_compte_desactive_est_ignoree_sans_perdre_l_echeance(): void
    {
        $inactif = $this->proUser();
        $inactif->forceFill(['is_active' => false])->save();
        $actif = $this->proUser();
        $ignoree = $this->recurrenceDue($inactif);
        $generee = $this->recurrenceDue($actif);

        $this->artisan('recurring:generate')->assertExitCode(0);

        $this->assertSame(0, Invoice::withoutUserScope()->where('user_id', $inactif->id)->count());
        $this->assertTrue($ignoree->fresh()->next_invoice_date->isPast());

        $this->assertSame(1, Invoice::withoutUserScope()->where('user_id', $actif->id)->count());
        $this->assertTrue($generee->fresh()->next_invoice_date->isFuture());
    }

    private function recurrenceDue(User $user): RecurringInvoice
    {
        $client = Client::factory()->create(['user_id' => $user->id]);

        return RecurringInvoice::create([
            'user_id' => $user->id, 'client_id' => $client->id, 'title' => 'Forfait mensuel',
            'frequency' => RecurringInvoice::FREQUENCY_MONTHLY, 'next_invoice_date' => now()->subDay()->toDateString(),
            'is_active' => true, 'auto_finalize' => false, 'auto_send' => false,
            'payment_delay_days' => 30, 'currency' => 'EUR',
        ]);
    }

    // --- Tableau de bord d'administration ----------------------------------------

    private function evenement(string $type, ?string $detail, $quand): void
    {
        AbuseEvent::create(['type' => $type, 'detail' => $detail])->forceFill(['created_at' => $quand])->save();
    }

    public function test_le_tableau_de_bord_compte_les_evenements_par_periode(): void
    {
        $this->evenement(AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'yopmail.com', now()->subHours(2));
        $this->evenement(AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'yopmail.com', now()->subDays(3));
        $this->evenement(AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'mailinator.com', now()->subDays(20));
        $this->evenement(AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'ancien.example', now()->subDays(45));
        $this->evenement(AbuseEvent::TYPE_RESERVED_DOMAIN, 'example.test', now()->subHour());
        $signale = $this->trialUser();
        app(AbuseProtectionService::class)->flag($signale, 'brand_name:vinted');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/'.config('admin.url_prefix'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard')
                ->where('abuseStats.comptes_a_verifier', 1)
                ->where('abuseStats.par_type.0', ['type' => AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'h24' => 1, 'j7' => 2, 'j30' => 3])
                ->where('abuseStats.par_type.1', ['type' => AbuseEvent::TYPE_RESERVED_DOMAIN, 'h24' => 1, 'j7' => 1, 'j30' => 1])
                ->where('abuseStats.par_type.4', ['type' => AbuseEvent::TYPE_TRIAL_QUOTA_REACHED, 'h24' => 0, 'j7' => 0, 'j30' => 0])
                ->where('abuseStats.domaines.0', ['domaine' => 'yopmail.com', 'total' => 2])
                ->has('abuseStats.domaines', 3)
            );
    }

    public function test_le_nettoyage_quotidien_purge_les_evenements_de_plus_de_90_jours(): void
    {
        $this->evenement(AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'vieux.example', now()->subDays(91));
        $this->evenement(AbuseEvent::TYPE_DISPOSABLE_EMAIL, 'recent.example', now()->subDays(89));

        $this->artisan('monitoring:cleanup')->assertExitCode(0);

        $this->assertDatabaseMissing('abuse_events', ['detail' => 'vieux.example']);
        $this->assertDatabaseHas('abuse_events', ['detail' => 'recent.example']);
    }

    public function test_un_journal_indisponible_ne_bloque_pas_l_inscription(): void
    {
        // Panne simulée sans toucher au schéma : supprimer la table validerait
        // la transaction du test sous MySQL et laisserait des données aux
        // tests suivants.
        AbuseEvent::creating(fn () => throw new \RuntimeException('journal indisponible'));

        $this->post('/register', $this->inscription(['email' => 'arnaque@mailinator.com']))
            ->assertSessionHasErrors(['email' => __('app.validation_disposable_email')]);
        $this->post('/register', $this->inscription())->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    // --- Liste téléchargée ------------------------------------------------------

    public function test_la_liste_est_mise_a_jour_chaque_nuit(): void
    {
        $evenement = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'abuse:update-disposable-list'));

        $this->assertNotNull($evenement);
        $this->assertSame('30 4 * * *', $evenement->expression);
    }

    public function test_la_commande_enregistre_une_liste_valide(): void
    {
        $domaines = collect(range(1, 1200))->map(fn ($i) => "jetable{$i}.example")->implode("\n");
        Http::fake(['*' => Http::response("# liste\n".$domaines, 200)]);

        $this->artisan('abuse:update-disposable-list')->assertExitCode(0);

        $contenu = Storage::disk('local')->get(AbuseProtectionService::DISPOSABLE_LIST_PATH);
        $this->assertStringContainsString('jetable1200.example', $contenu);
        $this->assertTrue(app(AbuseProtectionService::class)->isDisposableEmail('a@jetable42.example'));
    }

    public function test_une_reponse_suspecte_garde_la_liste_precedente(): void
    {
        Storage::disk('local')->put(AbuseProtectionService::DISPOSABLE_LIST_PATH, "ancienne.example\n");
        Http::fake(['*' => Http::sequence()
            ->push("un.example\ndeux.example\n", 200)
            ->push('Erreur', 500),
        ]);

        $this->artisan('abuse:update-disposable-list')->assertExitCode(1);
        $this->artisan('abuse:update-disposable-list')->assertExitCode(1);

        $this->assertSame("ancienne.example\n", Storage::disk('local')->get(AbuseProtectionService::DISPOSABLE_LIST_PATH));
    }
}
