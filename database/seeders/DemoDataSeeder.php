<?php

namespace Database\Seeders;

use App\Models\Accountant;
use App\Models\AccountantInvitation;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Expense;
use App\Models\HR\Employee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crée un jeu de données démo complet pour tester les 4 types d'accès :
 *   - Utilisateur principal (propriétaire)
 *   - Comptable (via portail accountant)
 *   - Employé (via portail HR)
 *   - Collaborateur (via portail organization)
 *
 * Idempotent : si les comptes démo existent déjà, ils sont effacés et recréés.
 * Mot de passe commun à tous les comptes : Demo2026!
 */
class DemoDataSeeder extends Seeder
{
    private const PASSWORD = 'Demo2026!';

    private const OWNER_EMAIL = 'demo-owner@faktur.lu';
    private const ACCOUNTANT_EMAIL = 'demo-accountant@faktur.lu';
    private const EMPLOYEE_EMAIL = 'demo-employee@faktur.lu';
    private const COLLAB_EMAIL = 'demo-collab@faktur.lu';

    public function run(): void
    {
        $this->command->warn('Nettoyage des données démo existantes...');
        $this->cleanup();

        DB::transaction(function () {
            $this->command->info('1/8 — Création du propriétaire...');
            $owner = $this->createOwner();
            Auth::login($owner);

            $this->command->info('2/8 — Settings entreprise + Organization...');
            $this->createBusinessSettings($owner);
            $org = Organization::create([
                'user_id' => $owner->id,
                'name' => 'Demo Studio Sàrl',
                'slug' => 'demo-studio',
                'settings' => json_encode(['theme' => 'default']),
            ]);

            $this->command->info('3/8 — Clients (8)...');
            $clients = $this->createClients();

            $this->command->info('4/8 — Projets et time entries...');
            $projects = $this->createProjects($clients);
            $this->createTimeEntries($clients, $projects);

            $this->command->info('5/8 — Factures (drafts + finalisées + payées + avoir)...');
            $this->createInvoices($clients);

            $this->command->info('6/8 — Devis...');
            $this->createQuotes($clients);

            $this->command->info('7/8 — Dépenses...');
            $this->createExpenses();

            $this->command->info('8/8 — Accès comptable, employé et collaborateur...');
            $this->createAccountantAccess($owner);
            Auth::logout();

            $employee = $this->createEmployeeAccess($owner);
            $collab = $this->createCollaboratorAccess($owner, $org);
        });

        $this->printCredentials();
    }

    private function cleanup(): void
    {
        $emails = [self::OWNER_EMAIL, self::EMPLOYEE_EMAIL, self::COLLAB_EMAIL];
        $userIds = User::withoutGlobalScope('user')->whereIn('email', $emails)->pluck('id')->toArray();

        if (!empty($userIds)) {
            // Ordre critique : enfants AVANT parents (contraintes FK)
            $invoiceIds = DB::table('invoices')->whereIn('user_id', $userIds)->pluck('id')->toArray();
            $quoteIds = DB::table('quotes')->whereIn('user_id', $userIds)->pluck('id')->toArray();
            $orgIds = DB::table('organizations')->whereIn('user_id', $userIds)->pluck('id')->toArray();

            if (!empty($invoiceIds)) DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->delete();
            if (!empty($quoteIds))   DB::table('quote_items')->whereIn('quote_id', $quoteIds)->delete();

            DB::table('time_entries')->whereIn('user_id', $userIds)->delete();
            DB::table('projects')->whereIn('user_id', $userIds)->delete();
            DB::table('invoices')->whereIn('user_id', $userIds)->delete();
            DB::table('quotes')->whereIn('user_id', $userIds)->delete();
            DB::table('expenses')->whereIn('user_id', $userIds)->delete();
            DB::table('clients')->whereIn('user_id', $userIds)->delete();
            DB::table('accountant_invitations')->whereIn('user_id', $userIds)->delete();
            DB::table('accountant_user')->whereIn('user_id', $userIds)->delete();
            if (!empty($orgIds)) DB::table('organization_members')->whereIn('organization_id', $orgIds)->delete();
            DB::table('organization_members')->whereIn('user_id', $userIds)->delete();
            DB::table('organizations')->whereIn('user_id', $userIds)->delete();
            DB::table('business_settings')->whereIn('user_id', $userIds)->delete();
            DB::table('employees')->whereIn('user_id', $userIds)->delete();
            DB::table('employees')->whereIn('account_id', $userIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }

        DB::table('accountants')->where('email', self::ACCOUNTANT_EMAIL)->delete();
    }

    private function createOwner(): User
    {
        $owner = User::create([
            'name' => 'Alex Demo (Propriétaire)',
            'email' => self::OWNER_EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'locale' => 'fr',
        ]);

        $owner->forceFill([
            'is_active' => true,
            'account_status' => 'active',
            'trial_ends_at' => now()->addYears(10),
            'onboarding_completed_at' => now()->subMonths(6),
        ])->save();

        return $owner;
    }

    private function createBusinessSettings(User $owner): BusinessSettings
    {
        return BusinessSettings::create([
            'user_id' => $owner->id,
            'company_name' => 'Demo Studio Sàrl',
            'legal_name' => 'Demo Studio S.à r.l.',
            'address' => '24, Rue du Stade John F. Kennedy',
            'postal_code' => 'L-3502',
            'city' => 'Dudelange',
            'country_code' => 'LU',
            'vat_number' => 'LU12345678',
            'matricule' => '20221234567',
            'rcs_number' => 'B123456',
            'iban' => 'LU280019400644750000',
            'bic' => 'BCEELULL',
            'bank_name' => 'BCEE Luxembourg',
            'vat_regime' => 'normal',
            'phone' => '+352 661 123 456',
            'email' => 'contact@demo-studio.lu',
            'default_hourly_rate' => 65,
            'default_invoice_footer' => 'Merci pour votre confiance !',
            'default_vat_mention' => 'none',
            'default_pdf_color' => '#7c3aed',
            'activity_type' => 'services',
            'number_format' => 'INV-{YYYY}-{####}',
            'invoice_prefix' => 'INV',
            'credit_note_prefix' => 'CN',
            'quote_prefix' => 'QT',
            'invoice_starting_number' => 1,
            'credit_note_starting_number' => 1,
            'quote_starting_number' => 1,
            'number_padding' => 4,
            'show_email_on_invoice' => true,
            'show_phone_on_invoice' => true,
        ]);
    }

    private function createClients(): array
    {
        $defs = [
            ['name' => 'Café Lëtzebuerg Sàrl', 'type' => 'company', 'country' => 'LU', 'vat' => 'LU98765432', 'city' => 'Luxembourg', 'rate' => 70],
            ['name' => 'Banque Privée Européenne SA', 'type' => 'company', 'country' => 'LU', 'vat' => 'LU11223344', 'city' => 'Luxembourg', 'rate' => 95],
            ['name' => 'Atelier Müller GmbH', 'type' => 'company', 'country' => 'DE', 'vat' => 'DE123456789', 'city' => 'Trier', 'rate' => 75],
            ['name' => 'Studio Créatif SARL', 'type' => 'company', 'country' => 'FR', 'vat' => 'FR45123456789', 'city' => 'Metz', 'rate' => 65],
            ['name' => 'Belgium Tech BV', 'type' => 'company', 'country' => 'BE', 'vat' => 'BE0123456789', 'city' => 'Bruxelles', 'rate' => 70],
            ['name' => 'Marie Dupont', 'type' => 'individual', 'country' => 'LU', 'vat' => null, 'city' => 'Esch-sur-Alzette', 'rate' => 50],
            ['name' => 'Acme USA Inc.', 'type' => 'company', 'country' => 'US', 'vat' => null, 'city' => 'New York', 'rate' => 85],
            ['name' => 'Boulangerie du Coin Sàrl', 'type' => 'company', 'country' => 'LU', 'vat' => 'LU55667788', 'city' => 'Differdange', 'rate' => 55],
        ];

        $clients = [];
        foreach ($defs as $i => $d) {
            $clients[] = Client::create([
                'name' => $d['name'],
                'email' => 'contact-' . ($i + 1) . '@demo-clients.lu',
                'address' => (10 + $i) . ', rue Démo',
                'postal_code' => $d['country'] === 'LU' ? 'L-' . (1000 + $i * 100) : (10000 + $i * 100) . '',
                'city' => $d['city'],
                'country_code' => $d['country'],
                'vat_number' => $d['vat'],
                'type' => $d['type'],
                'currency' => 'EUR',
                'default_hourly_rate' => $d['rate'],
                'locale' => match ($d['country']) { 'DE' => 'de', 'US' => 'en', default => 'fr' },
                'status' => 'active',
                'source' => 'manual',
            ]);
        }
        return $clients;
    }

    private function createProjects(array $clients): array
    {
        $projects = [];
        $defs = [
            ['title' => 'Refonte site web Café Lëtzebuerg', 'client' => 0, 'status' => 'in_progress', 'color' => '#7c3aed'],
            ['title' => 'App mobile Banque Privée', 'client' => 1, 'status' => 'in_progress', 'color' => '#3b82f6'],
            ['title' => 'Identité visuelle Atelier Müller', 'client' => 2, 'status' => 'completed', 'color' => '#10b981', 'archived' => true],
        ];
        foreach ($defs as $i => $d) {
            $projects[] = Project::create([
                'user_id' => Auth::id(),
                'client_id' => $clients[$d['client']]->id,
                'title' => $d['title'],
                'description' => 'Projet démo n°' . ($i + 1),
                'status' => $d['status'],
                'color' => $d['color'],
                'due_date' => now()->addMonths(2 - $i),
                'budget_hours' => 80,
                'sort_order' => $i,
                'is_archived' => $d['archived'] ?? false,
                'created_by' => Auth::id(),
            ]);
        }
        return $projects;
    }

    private function createTimeEntries(array $clients, array $projects): void
    {
        for ($i = 0; $i < 15; $i++) {
            $project = $projects[$i % count($projects)];
            $start = now()->subDays(rand(1, 60))->setTime(rand(8, 16), 0);
            $hours = rand(1, 6);

            TimeEntry::create([
                'user_id' => Auth::id(),
                'client_id' => $project->client_id,
                'project_id' => $project->id,
                'project_name' => $project->title,
                'description' => 'Session de travail #' . ($i + 1),
                'started_at' => $start,
                'stopped_at' => $start->copy()->addHours($hours),
                'duration_seconds' => $hours * 3600,
                'hourly_rate' => 65,
                'is_billed' => $i < 8,
            ]);
        }
    }

    private function createInvoices(array $clients): void
    {
        $cfg = [
            ['status' => 'draft', 'days_ago' => 3, 'paid' => false, 'sent' => false],
            ['status' => 'draft', 'days_ago' => 1, 'paid' => false, 'sent' => false],
            ['status' => 'draft', 'days_ago' => 0, 'paid' => false, 'sent' => false],
            ['status' => 'finalized', 'days_ago' => 8, 'paid' => false, 'sent' => false],
            ['status' => 'finalized', 'days_ago' => 12, 'paid' => false, 'sent' => false],
            ['status' => 'sent', 'days_ago' => 20, 'paid' => false, 'sent' => true],
            ['status' => 'sent', 'days_ago' => 35, 'paid' => false, 'sent' => true],
            ['status' => 'sent', 'days_ago' => 45, 'paid' => false, 'sent' => true],
            ['status' => 'paid', 'days_ago' => 60, 'paid' => true, 'sent' => true],
            ['status' => 'paid', 'days_ago' => 75, 'paid' => true, 'sent' => true],
            ['status' => 'paid', 'days_ago' => 95, 'paid' => true, 'sent' => true],
            ['status' => 'paid', 'days_ago' => 120, 'paid' => true, 'sent' => true],
            ['status' => 'paid', 'days_ago' => 150, 'paid' => true, 'sent' => true],
            ['status' => 'paid', 'days_ago' => 180, 'paid' => true, 'sent' => true],
        ];

        $seq = 1;
        $year = now()->year;
        $paidInvoiceForCredit = null;

        foreach ($cfg as $i => $c) {
            $client = $clients[$i % count($clients)];
            $issuedAt = now()->subDays($c['days_ago']);

            // Détermine mention TVA selon client
            $vatMention = match (true) {
                $client->country_code === 'LU' => 'none',
                in_array($client->country_code, ['DE', 'FR', 'BE']) && $client->vat_number => 'reverse_charge',
                in_array($client->country_code, ['DE', 'FR', 'BE']) => 'intra_eu',
                default => 'export',
            };
            $defaultVat = $vatMention === 'none' ? 17 : 0;

            // Crée toujours en DRAFT d'abord pour que les hooks d'items calculent les totaux.
            // CalculateInvoiceTotalsAction skippe les factures non-draft (immutabilité comptable).
            $invoice = Invoice::create([
                'user_id' => Auth::id(),
                'client_id' => $client->id,
                'title' => 'Prestations ' . $issuedAt->locale('fr')->isoFormat('MMMM YYYY'),
                'status' => 'draft',
                'type' => 'invoice',
                'currency' => 'EUR',
                'issued_at' => $issuedAt->toDateString(),
                'due_at' => $issuedAt->copy()->addDays(30)->toDateString(),
                'vat_mention' => $vatMention,
            ]);

            // 1 à 3 lignes — les hooks calculent les totaux pendant que la facture est encore draft
            $lineCount = rand(1, 3);
            for ($l = 0; $l < $lineCount; $l++) {
                $qty = rand(2, 20);
                $price = rand(40, 120);
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'title' => match ($l % 3) {
                        0 => 'Développement web',
                        1 => 'Conseil & stratégie',
                        default => 'Design UI/UX',
                    },
                    'description' => 'Prestation détaillée ligne ' . ($l + 1),
                    'quantity' => $qty,
                    'unit' => 'hour',
                    'unit_price' => $price,
                    'vat_rate' => $defaultVat,
                    'sort_order' => $l,
                ]);
            }

            // Transition vers le statut final si nécessaire (autorisé car originalStatus=draft)
            if ($c['status'] !== 'draft') {
                $invoice->update([
                    'status' => $c['status'],
                    'number' => sprintf('INV-%d-%04d', $year, $seq),
                    'sequence_number' => $seq,
                    'finalized_at' => $issuedAt,
                    'sent_at' => $c['sent'] ? $issuedAt->copy()->addDays(1) : null,
                    'paid_at' => $c['paid'] ? $issuedAt->copy()->addDays(rand(10, 28)) : null,
                    'seller_snapshot' => $this->buildSellerSnapshot(),
                    'buyer_snapshot' => $this->buildBuyerSnapshot($client),
                ]);
                $seq++;
            }

            if ($c['status'] === 'paid' && $paidInvoiceForCredit === null) {
                $paidInvoiceForCredit = $invoice->refresh();
            }
        }

        // Avoir sur une facture payée — même pattern : draft → items → transition
        if ($paidInvoiceForCredit) {
            $credit = Invoice::create([
                'user_id' => Auth::id(),
                'client_id' => $paidInvoiceForCredit->client_id,
                'title' => 'Avoir sur ' . $paidInvoiceForCredit->number,
                'status' => 'draft',
                'type' => 'credit_note',
                'credit_note_for' => $paidInvoiceForCredit->id,
                'credit_note_reason' => 'commercial_gesture',
                'currency' => 'EUR',
                'issued_at' => now()->subDays(40)->toDateString(),
                'due_at' => now()->subDays(10)->toDateString(),
                'vat_mention' => $paidInvoiceForCredit->vat_mention,
            ]);

            InvoiceItem::create([
                'invoice_id' => $credit->id,
                'title' => 'Remise commerciale',
                'description' => 'Geste commercial sur facture ' . $paidInvoiceForCredit->number,
                'quantity' => 1,
                'unit' => 'piece',
                'unit_price' => 150,
                'vat_rate' => $paidInvoiceForCredit->vat_mention === 'none' ? 17 : 0,
                'sort_order' => 0,
            ]);

            $credit->update([
                'status' => 'finalized',
                'number' => sprintf('CN-%d-%04d', $year, 1),
                'sequence_number' => 1,
                'finalized_at' => now()->subDays(40),
                'seller_snapshot' => $this->buildSellerSnapshot(),
                'buyer_snapshot' => $this->buildBuyerSnapshot($paidInvoiceForCredit->client),
            ]);
        }
    }

    private function createQuotes(array $clients): void
    {
        $cfg = [
            ['status' => 'draft', 'days_ago' => 2],
            ['status' => 'sent', 'days_ago' => 7],
            ['status' => 'accepted', 'days_ago' => 30],
            ['status' => 'declined', 'days_ago' => 45],
            ['status' => 'expired', 'days_ago' => 90],
        ];

        $seq = 1;
        $year = now()->year;

        foreach ($cfg as $i => $c) {
            $client = $clients[$i % count($clients)];
            $issuedAt = now()->subDays($c['days_ago']);
            $isSent = in_array($c['status'], ['sent', 'accepted', 'declined', 'expired']);

            $quote = Quote::create([
                'user_id' => Auth::id(),
                'client_id' => $client->id,
                'reference' => $isSent ? sprintf('QT-%d-%04d', $year, $seq) : null,
                'sequence_number' => $isSent ? $seq : null,
                'status' => $c['status'],
                'currency' => 'EUR',
                'valid_until' => $issuedAt->copy()->addDays(30)->toDateString(),
                'vat_mention' => $client->country_code === 'LU' ? 'none' : ($client->vat_number ? 'reverse_charge' : 'intra_eu'),
                'sent_at' => $isSent ? $issuedAt : null,
                'accepted_at' => $c['status'] === 'accepted' ? $issuedAt->copy()->addDays(3) : null,
                'declined_at' => $c['status'] === 'declined' ? $issuedAt->copy()->addDays(5) : null,
                'seller_snapshot' => $isSent ? $this->buildSellerSnapshot() : null,
                'buyer_snapshot' => $isSent ? $this->buildBuyerSnapshot($client) : null,
                'created_at' => $issuedAt,
            ]);

            if ($isSent) {
                $seq++;
            }

            QuoteItem::create([
                'quote_id' => $quote->id,
                'title' => 'Forfait projet',
                'description' => 'Estimation pour le projet',
                'quantity' => rand(20, 60),
                'unit' => 'hour',
                'unit_price' => rand(60, 90),
                'vat_rate' => $client->country_code === 'LU' ? 17 : 0,
                'sort_order' => 0,
            ]);
        }
    }

    private function createExpenses(): void
    {
        $defs = [
            ['provider' => 'OVH SAS', 'cat' => 'hosting', 'amount' => 89.90],
            ['provider' => 'Microsoft 365', 'cat' => 'software', 'amount' => 12.50],
            ['provider' => 'Adobe Creative Cloud', 'cat' => 'software', 'amount' => 59.99],
            ['provider' => 'CFL', 'cat' => 'transport', 'amount' => 35.00],
            ['provider' => 'Cactus', 'cat' => 'office_supplies', 'amount' => 124.30],
            ['provider' => 'POST Luxembourg', 'cat' => 'telecom', 'amount' => 45.00],
            ['provider' => 'Restaurant Aux Anciennes Tanneries', 'cat' => 'meals', 'amount' => 78.50],
            ['provider' => 'GitHub', 'cat' => 'software', 'amount' => 19.00],
            ['provider' => 'Office Center', 'cat' => 'office_supplies', 'amount' => 67.80],
            ['provider' => 'Esso Station', 'cat' => 'transport', 'amount' => 65.00],
            ['provider' => 'Cloudflare', 'cat' => 'hosting', 'amount' => 25.00],
            ['provider' => 'LinkedIn Premium', 'cat' => 'marketing', 'amount' => 29.99],
            ['provider' => 'Brevo', 'cat' => 'marketing', 'amount' => 25.00],
        ];

        foreach ($defs as $i => $d) {
            $vat = 17;
            $ttc = $d['amount'];
            $ht = round($ttc / (1 + $vat / 100), 2);

            Expense::create([
                'user_id' => Auth::id(),
                'date' => now()->subDays(rand(5, 180))->toDateString(),
                'provider_name' => $d['provider'],
                'category' => $d['cat'],
                'amount_ht' => $ht,
                'vat_rate' => $vat,
                'amount_vat' => round($ttc - $ht, 2),
                'amount_ttc' => $ttc,
                'description' => 'Dépense ' . $d['provider'],
                'is_deductible' => true,
                'payment_method' => ['card', 'bank_transfer', 'cash'][array_rand(['card', 'bank_transfer', 'cash'])],
                'reference' => 'EXP-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    private function createAccountantAccess(User $owner): void
    {
        // Compte comptable pré-créé (invitation acceptée)
        $accountant = Accountant::create([
            'email' => self::ACCOUNTANT_EMAIL,
            'name' => 'Sophie Comptable',
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
        ]);

        // Lien comptable ↔ user
        DB::table('accountant_user')->insert([
            'accountant_id' => $accountant->id,
            'user_id' => $owner->id,
            'status' => 'active',
            'granted_at' => now()->subDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Invitation marquée comme acceptée (historique)
        AccountantInvitation::create([
            'user_id' => $owner->id,
            'email' => self::ACCOUNTANT_EMAIL,
            'name' => 'Sophie Comptable',
            'token' => Str::random(64),
            'status' => 'accepted',
            'accepted_at' => now()->subDays(30),
            'expires_at' => now()->subDays(23),
        ]);
    }

    private function createEmployeeAccess(User $owner): User
    {
        $employeeUser = User::create([
            'name' => 'Julie Employée',
            'email' => self::EMPLOYEE_EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'locale' => 'fr',
        ]);

        $employeeUser->forceFill([
            'is_active' => true,
            'account_status' => 'active',
            'trial_ends_at' => now()->addYears(10),
        ])->save();

        // Record HR Employee lié au compte owner
        // BelongsToUser auto-assigne user_id sur create — on bypasse via forceCreate + login owner
        Auth::login($owner);
        Employee::create([
            'first_name' => 'Julie',
            'last_name' => 'Employée',
            'email_pro' => self::EMPLOYEE_EMAIL,
            'phone' => '+352 661 234 567',
            'birth_date' => '1992-04-15',
            'nationality' => 'LU',
            'address' => '12, Avenue de la Liberté',
            'city' => 'Luxembourg',
            'postal_code' => 'L-1930',
            'country' => 'LU',
            'contract_type' => 'CDI',
            'contract_start' => now()->subMonths(18)->toDateString(),
            'job_title' => 'Designer UI',
            'work_location' => 'office',
            'salary_gross' => 4200,
            'salary_currency' => 'EUR',
            'status' => 'active',
            'account_id' => $employeeUser->id,
            'portal_activated_at' => now()->subMonths(18),
        ]);
        Auth::logout();

        return $employeeUser;
    }

    private function createCollaboratorAccess(User $owner, Organization $org): User
    {
        $collabUser = User::create([
            'name' => 'Marc Collaborateur',
            'email' => self::COLLAB_EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'locale' => 'fr',
        ]);

        $collabUser->forceFill([
            'is_active' => true,
            'account_status' => 'active',
            'trial_ends_at' => now()->addYears(10),
        ])->save();

        OrganizationMember::create([
            'organization_id' => $org->id,
            'user_id' => $collabUser->id,
            'role' => OrganizationMember::ROLE_COLLABORATOR,
            'invited_at' => now()->subDays(45),
            'joined_at' => now()->subDays(40),
        ]);

        return $collabUser;
    }

    private function buildSellerSnapshot(): string
    {
        return json_encode([
            'company_name' => 'Demo Studio Sàrl',
            'address' => '24, Rue du Stade John F. Kennedy',
            'postal_code' => 'L-3502',
            'city' => 'Dudelange',
            'country' => 'LU',
            'vat_number' => 'LU12345678',
            'matricule' => '20221234567',
            'rcs_number' => 'B123456',
            'iban' => 'LU280019400644750000',
            'bic' => 'BCEELULL',
            'email' => 'contact@demo-studio.lu',
            'phone' => '+352 661 123 456',
        ]);
    }

    private function buildBuyerSnapshot(Client $client): string
    {
        return json_encode([
            'name' => $client->name,
            'address' => $client->address,
            'postal_code' => $client->postal_code,
            'city' => $client->city,
            'country' => $client->country_code,
            'vat_number' => $client->vat_number,
            'email' => $client->email,
        ]);
    }

    private function printCredentials(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║  DONNÉES DÉMO CRÉÉES — CREDENTIALS                        ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('Mot de passe commun à tous les comptes : ' . self::PASSWORD);
        $this->command->info('');
        $this->command->info('1. PROPRIÉTAIRE (utilisateur principal)');
        $this->command->info('   URL    : /login');
        $this->command->info('   Email  : ' . self::OWNER_EMAIL);
        $this->command->info('');
        $this->command->info('2. COMPTABLE (portail fiduciaire)');
        $this->command->info('   URL    : /comptable/login');
        $this->command->info('   Email  : ' . self::ACCOUNTANT_EMAIL);
        $this->command->info('');
        $this->command->info('3. EMPLOYÉ (portail HR)');
        $this->command->info('   URL    : /login');
        $this->command->info('   Email  : ' . self::EMPLOYEE_EMAIL);
        $this->command->info('   (sera redirigé automatiquement vers le portail employé)');
        $this->command->info('');
        $this->command->info('4. COLLABORATEUR (organization member)');
        $this->command->info('   URL    : /login');
        $this->command->info('   Email  : ' . self::COLLAB_EMAIL);
        $this->command->info('   (sera redirigé automatiquement vers le portail collaborateur)');
        $this->command->info('');
        $this->command->info('Volume de données généré pour le propriétaire :');
        $this->command->info('  - 8 clients (LU, DE, FR, BE, US — mix B2B/B2C)');
        $this->command->info('  - 3 projets (2 actifs + 1 archivé)');
        $this->command->info('  - 15 time entries');
        $this->command->info('  - 14 factures + 1 avoir (drafts + finalisées + envoyées + payées)');
        $this->command->info('  - 5 devis (draft, sent, accepted, declined, expired)');
        $this->command->info('  - 13 dépenses');
        $this->command->info('');
    }
}
