<?php

namespace App\Services;

use App\Mail\FlaggedAccountFirstEmailNotification;
use App\Models\Invoice;
use App\Models\InvoiceEmail;
use App\Models\User;
use App\Support\UserError;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Protections contre les inscriptions frauduleuses (FEAT-138).
 *
 * Septembre 2026 : des comptes créés avec des adresses jetables et des noms
 * de marques usurpées utilisaient l'essai pour envoyer, depuis notre
 * domaine, des factures de phishing crédibles. Trois défenses :
 *   1. refuser les adresses jetables à l'inscription ;
 *   2. repérer les noms de marques (signalement du compte à l'inscription,
 *      refus franc du nom d'entreprise, celui qui figure sur le PDF) ;
 *   3. plafonner les envois de documents par nos serveurs pendant l'essai.
 *
 * ⚠️ Jamais `auth()` ici : le service sert aussi en dehors d'une requête
 * (commande, job). L'utilisateur est toujours passé en paramètre.
 */
class AbuseProtectionService
{
    /** Chemin sur le disque `local` (storage/app/private). */
    public const DISPOSABLE_LIST_PATH = 'abuse/disposable-domains.txt';

    /** @var array<string, true>|null */
    private ?array $disposable = null;

    public function __construct(private readonly EmailProviderService $emailProviders) {}

    // --- 1. Adresses jetables ---------------------------------------------

    /**
     * Le domaine de l'adresse, ou l'un de ses parents, est-il jetable ?
     * « x.mailinator.com » l'est autant que « mailinator.com ».
     */
    public function isDisposableEmail(string $email): bool
    {
        if (! str_contains($email, '@')) {
            return false;
        }

        $domaines = $this->disposableDomains();
        $parties = explode('.', strtolower(trim(Str::afterLast($email, '@'))));

        while (count($parties) >= 2) {
            if (isset($domaines[implode('.', $parties)])) {
                return true;
            }

            array_shift($parties);
        }

        return false;
    }

    /**
     * Le socle de la configuration, plus la liste téléchargée si elle existe.
     * Lue une fois par instance : quelques centaines de kilo-octets, relus
     * seulement quand une inscription arrive.
     *
     * @return array<string, true>
     */
    public function disposableDomains(): array
    {
        if ($this->disposable !== null) {
            return $this->disposable;
        }

        $domaines = self::parseDomainList(implode("\n", config('abuse.disposable_seed', [])));
        $disque = Storage::disk('local');

        if ($disque->exists(self::DISPOSABLE_LIST_PATH)) {
            $domaines = array_merge($domaines, self::parseDomainList((string) $disque->get(self::DISPOSABLE_LIST_PATH)));
        }

        return $this->disposable = array_fill_keys($domaines, true);
    }

    /**
     * Une ligne par domaine ; lignes vides, commentaires et lignes qui ne
     * ressemblent pas à un domaine ignorés.
     *
     * @return list<string>
     */
    public static function parseDomainList(string $contenu): array
    {
        $domaines = [];

        foreach (preg_split('/\R/', $contenu) as $ligne) {
            $ligne = strtolower(trim($ligne));

            if ($ligne !== '' && preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/', $ligne)) {
                $domaines[] = $ligne;
            }
        }

        return $domaines;
    }

    // --- 2. Noms de marques ---------------------------------------------------

    /**
     * La marque que le texte imite, ou null.
     *
     * Insensible à la casse, aux accents, à la ponctuation et aux lettres
     * espacées (« V-i-n-t-e-d », « U.P.S. »), ainsi qu'aux chiffres mis pour
     * des lettres (« V1nted », « P@yPal »). Voir config/abuse.php pour la
     * différence entre `words` et `embedded`.
     */
    public function matchesBrand(?string $texte): ?string
    {
        if ($texte === null || trim($texte) === '') {
            return null;
        }

        foreach ($this->variantes($texte) as $espace) {
            $colle = str_replace(' ', '', $espace);

            foreach (config('abuse.brands.words', []) as $marque) {
                $motif = preg_quote($this->normaliser($marque), '/');

                if (preg_match('/(^| )'.$motif.'( |$)/', $espace)) {
                    return $marque;
                }
            }

            foreach (config('abuse.brands.embedded', []) as $marque) {
                if (str_contains($colle, str_replace(' ', '', $this->normaliser($marque)))) {
                    return $marque;
                }
            }
        }

        return null;
    }

    /**
     * Signale le compte si son nom imite une marque. Rien n'est bloqué : le
     * nom de la personne n'apparaît pas sur les factures, et un faux positif
     * ne doit pas empêcher un vrai client de s'inscrire.
     */
    public function flagIfBrandName(User $user): ?string
    {
        $marque = $this->matchesBrand($user->name);

        if ($marque !== null) {
            $this->flag($user, 'brand_name:'.$marque);
        }

        return $marque;
    }

    /**
     * Marque le compte « à vérifier ». La première raison est conservée :
     * c'est celle qui a déclenché l'alerte. Aucun mail n'est envoyé au
     * compte, pour ne pas prévenir un fraudeur.
     */
    public function flag(User $user, string $raison): void
    {
        if ($user->flagged_for_review) {
            return;
        }

        // Champs hors $fillable, comme trial_ends_at et is_active.
        $user->forceFill([
            'flagged_for_review' => true,
            'flagged_reason' => Str::limit($raison, 250, ''),
            'flagged_at' => now(),
        ])->save();

        Log::warning('Compte signalé pour vérification.', ['user_id' => $user->id, 'raison' => $raison]);
    }

    /**
     * La raison d'un signalement, lisible par un humain (mails et page
     * d'administration). « brand_name:vinted » → « Nom proche d'une marque :
     * vinted ».
     */
    public static function describeReason(?string $raison): string
    {
        if ($raison === null || $raison === '') {
            return '-';
        }

        [$type, $detail] = array_pad(explode(':', $raison, 2), 2, '');

        return match ($type) {
            'brand_name' => __('app.abuse_reason_brand_name', ['brand' => $detail]),
            'company_name' => __('app.abuse_reason_company_name', ['brand' => $detail]),
            default => $raison,
        };
    }

    /**
     * Forme comparable : minuscules sans accents, tout ce qui n'est ni lettre
     * ni chiffre devient une espace, et les lettres isolées sont recollées
     * (« v i n t e d » → « vinted »).
     */
    private function normaliser(string $texte): string
    {
        $texte = preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($texte)));
        $jetons = array_values(array_filter(explode(' ', $texte), fn (string $j) => $j !== ''));

        $resultat = [];
        $lettres = '';

        foreach ($jetons as $jeton) {
            if (strlen($jeton) === 1) {
                $lettres .= $jeton;

                continue;
            }

            if ($lettres !== '') {
                $resultat[] = $lettres;
                $lettres = '';
            }

            $resultat[] = $jeton;
        }

        if ($lettres !== '') {
            $resultat[] = $lettres;
        }

        return implode(' ', $resultat);
    }

    /**
     * Le texte tel quel, puis avec les chiffres et symboles lus comme des
     * lettres. « 1 » peut valoir « i » (V1nted) ou « l » (Paypa1).
     *
     * @return list<string>
     */
    private function variantes(string $texte): array
    {
        $lettres = ['0' => 'o', '3' => 'e', '4' => 'a', '5' => 's', '7' => 't', '@' => 'a', '$' => 's', '!' => 'i'];

        return array_values(array_unique([
            $this->normaliser($texte),
            $this->normaliser(strtr($texte, $lettres + ['1' => 'i'])),
            $this->normaliser(strtr($texte, $lettres + ['1' => 'l'])),
        ]));
    }

    // --- 3. Envois de documents pendant l'essai -------------------------------

    /**
     * Le plafond du jour, ou null quand le compte n'est pas concerné : hors
     * essai (abonné ou revenu en Gratuit, qui a ses propres quotas), ou quand
     * il envoie par son propre fournisseur. C'est notre domaine qu'on protège.
     *
     * @return array{limit: int, used: int}|null
     */
    public function trialDocumentEmailQuota(User $user): ?array
    {
        if (! $user->isOnTrial() || ! $this->emailProviders->usesPlatformMailer($user)) {
            return null;
        }

        return [
            'limit' => (int) config('abuse.trial_daily_document_emails', 5),
            'used' => $this->documentEmailsSentSince($user, now()->startOfDay()),
        ];
    }

    public function canSendDocumentEmail(User $user): bool
    {
        $quota = $this->trialDocumentEmailQuota($user);

        return $quota === null || $quota['used'] < $quota['limit'];
    }

    /**
     * Documents réellement partis (statut « envoyé ») depuis une date, ou
     * depuis toujours. Les factures supprimées comptent : supprimer ce qu'on
     * vient d'envoyer ne doit pas rendre de place.
     */
    public function documentEmailsSentSince(User $user, ?\DateTimeInterface $depuis = null): int
    {
        return InvoiceEmail::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_emails.invoice_id')
            ->where('invoices.user_id', $user->id)
            ->where('invoice_emails.status', InvoiceEmail::STATUS_SENT)
            ->when($depuis, fn ($q) => $q->where('invoice_emails.sent_at', '>=', $depuis))
            ->count();
    }

    /**
     * Prévient l'administrateur quand un compte signalé envoie son premier
     * document : c'est le moment où une usurpation devient un dommage.
     * Rien n'est envoyé au compte.
     */
    public function notifyFlaggedAccountFirstEmail(User $user, Invoice $invoice, string $destinataire): void
    {
        if (! $user->flagged_for_review || $this->documentEmailsSentSince($user) !== 1) {
            return;
        }

        $admin = config('admin.support_email');

        if (! $admin) {
            return;
        }

        try {
            Mail::to($admin)->send(new FlaggedAccountFirstEmailNotification($user, $invoice, $destinataire));
        } catch (\Throwable $e) {
            UserError::report($e, 'abuse.flagged_first_email');
        }
    }
}
