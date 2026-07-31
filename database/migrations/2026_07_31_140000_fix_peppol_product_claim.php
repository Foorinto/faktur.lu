<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « peppol-b2g-luxembourg-guide-complet-2026 » — correction du
 * claim produit dans les cinq langues.
 *
 * CE QUI EST CORRIGÉ. L'article décrivait la transmission Peppol comme
 * une fonctionnalité disponible : « Cliquez sur "Envoyer via Peppol" »,
 * « transmise via notre Access Point », et surtout « le plan Essentiel
 * inclut un quota mensuel d'envois Peppol, le plan Pro offre des envois
 * illimités ». Formulée ainsi, la page promet à un client payant une
 * fonction facturée qu'il ne peut pas encore utiliser.
 *
 * ÉTAT RÉEL, vérifié dans le code :
 * - `config/peppol.php` : `enabled => env('PEPPOL_ENABLED', false)` et
 *   `provider => env('PEPPOL_PROVIDER', 'simulation')` par défaut ;
 * - FEAT-096 « Peppol go-live émission — passage production + onboarding
 *   multi-tenant » est toujours au backlog, de même que FEAT-097
 *   (réception) ;
 * - la plomberie existe bel et bien : route POST send-peppol, gating
 *   `plan.feature:peppol_transmission`, quotas `max_peppol_per_month`
 *   (10 puis illimité) — les chiffres annoncés étaient donc exacts, mais
 *   décrivaient une capacité non encore mise en production.
 *
 * CE QUI EST DISPONIBLE AUJOURD'HUI, et qui reste une vraie valeur :
 * l'EXPORT du fichier Peppol BIS 3.0 / UBL conforme
 * (`PeppolExportController`, feature de plan `peppol_export`, route GET
 * invoices/{invoice}/peppol). Le fichier peut être transmis par le canal
 * de votre choix, notamment le point d'accès du CTIE que les organismes
 * publics utilisent quand ils n'ont pas le leur.
 *
 * Le texte distingue donc désormais ce qui est livré de ce qui est en
 * cours, plutôt que de présenter les deux comme acquis. C'est la même
 * correction que celle apportée au comparatif de logiciels le
 * 2026-07-31 : ne pas cocher une case qui n'est pas cochée.
 *
 * FAITS JURIDIQUES VÉRIFIÉS et confirmés exacts, laissés inchangés :
 * loi du 13 décembre 2021 relative à la facturation électronique dans le
 * cadre des marchés publics et des contrats de concession ; calendrier
 * échelonné du 18 mai 2022 (grands opérateurs), 18 octobre 2022 (taille
 * moyenne) et 18 mars 2023 (petits opérateurs) ; absence de seuil de
 * montant ; usage du réseau Peppol par le secteur public.
 *
 * ⚠️ RESTE À FAIRE sur cet article : les traductions allemande et
 * luxembourgeoise ne font que 3 845 et 3 395 caractères contre 11 349 en
 * français — la pire dérive du blog, avec 5 H2 contre 9 et aucun H3.
 * Une reconstruction intégrale est nécessaire, traitée séparément.
 */
return new class extends Migration
{
    private const KEY = 'peppol-b2g-luxembourg-guide-complet-2026';

    /** Titre du H2 à remplacer, par langue. */
    private const ANCHORS = [
        'fr' => 'Comment envoyer une facture Peppol depuis faktur.lu ?',
        'de' => 'Peppol-Rechnungen mit faktur.lu senden',
        'en' => 'How to send a Peppol invoice from faktur.lu?',
        'lb' => 'Peppol-Rechnunge mat faktur.lu schécken',
        'pt' => 'Como enviar uma fatura Peppol a partir do faktur.lu?',
    ];

    public function up(): void
    {
        foreach (self::ANCHORS as $locale => $h2) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            // De ce H2 jusqu'au H2 suivant (ou la fin du contenu).
            $pattern = '~<h2>' . preg_quote($h2, '~') . '</h2>.*?(?=<h2>|$)~s';

            if (! preg_match($pattern, $post->content)) {
                continue; // ancre absente : ne rien toucher
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => preg_replace($pattern, $this->section($locale), $post->content, 1),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Contenu rédactionnel : la version antérieure annonçait comme
        // disponible une transmission qui ne l'est pas encore.
    }

    private function section(string $l): string
    {
        return match ($l) {
            'fr' => <<<'HTML'
<h2>Peppol et faktur.lu : ce qui est disponible aujourd'hui</h2>

<p>Nous préférons être précis sur ce point plutôt que de cocher une case.</p>

<p><strong>Disponible dès maintenant :</strong> faktur.lu génère le <strong>fichier Peppol BIS Billing 3.0 (UBL)</strong> de vos factures, conforme au format exigé par le secteur public luxembourgeois. Vous le récupérez depuis la facture et le transmettez par le canal de votre choix — notamment le point d'accès du <strong>CTIE</strong>, qu'utilisent les organismes publics n'ayant pas le leur.</p>

<p><strong>En cours de mise en production :</strong> la transmission automatique via un point d'accès intégré, qui vous évitera cette étape manuelle. Le format est prêt côté technique, le déploiement pour l'ensemble des comptes ne l'est pas encore. Nous mettrons cette page à jour le jour où ce sera le cas.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">En attendant, vous êtes en règle</p>
    <p>L'obligation légale porte sur le <strong>format</strong> de la facture et sa transmission électronique, pas sur l'outil utilisé pour l'acheminer. Un fichier BIS 3.0 conforme, déposé sur le canal de votre acheteur public, satisfait l'obligation.</p>
</div>

HTML,
            'de' => <<<'HTML'
<h2>Peppol und faktur.lu: was heute verfügbar ist</h2>

<p>Wir sind an dieser Stelle lieber genau, als ein Häkchen zu setzen.</p>

<p><strong>Ab sofort verfügbar:</strong> faktur.lu erzeugt die <strong>Peppol-BIS-Billing-3.0-Datei (UBL)</strong> Ihrer Rechnungen, konform mit dem vom luxemburgischen öffentlichen Sektor verlangten Format. Sie laden sie aus der Rechnung herunter und übermitteln sie über den Kanal Ihrer Wahl — insbesondere über den Zugangspunkt des <strong>CTIE</strong>, den öffentliche Stellen ohne eigenen Zugangspunkt nutzen.</p>

<p><strong>In Produktivsetzung:</strong> die automatische Übermittlung über einen integrierten Zugangspunkt, die Ihnen diesen manuellen Schritt erspart. Das Format steht technisch bereit, der Rollout für alle Konten noch nicht. Wir aktualisieren diese Seite, sobald es so weit ist.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">In der Zwischenzeit sind Sie regelkonform</p>
    <p>Die gesetzliche Pflicht betrifft das <strong>Format</strong> der Rechnung und ihre elektronische Übermittlung, nicht das Werkzeug, mit dem sie zugestellt wird. Eine konforme BIS-3.0-Datei, über den Kanal Ihres öffentlichen Auftraggebers eingereicht, erfüllt die Pflicht.</p>
</div>

HTML,
            'en' => <<<'HTML'
<h2>Peppol and faktur.lu: what is available today</h2>

<p>We would rather be precise here than tick a box.</p>

<p><strong>Available now:</strong> faktur.lu generates the <strong>Peppol BIS Billing 3.0 (UBL) file</strong> for your invoices, compliant with the format required by the Luxembourg public sector. You download it from the invoice and submit it through the channel of your choice — notably the <strong>CTIE</strong> access point, which public bodies without their own access point use.</p>

<p><strong>Being rolled out:</strong> automatic transmission through an integrated access point, which will remove that manual step. The format is technically ready; the rollout across all accounts is not. We will update this page the day it is.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">In the meantime, you are compliant</p>
    <p>The legal obligation concerns the <strong>format</strong> of the invoice and its electronic transmission, not the tool used to deliver it. A compliant BIS 3.0 file, submitted through your public buyer's channel, satisfies the obligation.</p>
</div>

HTML,
            'lb' => <<<'HTML'
<h2>Peppol a faktur.lu: wat haut disponibel ass</h2>

<p>Mir si bei dësem Punkt léiwer präzis, wéi datt mir eng Case ukräizen.</p>

<p><strong>Ab elo disponibel:</strong> faktur.lu generéiert de <strong>Peppol-BIS-Billing-3.0-Fichier (UBL)</strong> vun Äre Rechnungen, konform mam Format, dat de Lëtzebuerger ëffentleche Secteur verlaangt. Dir luet en aus der Rechnung erof an iwwerdroet en iwwer de Kanal vun Ärer Wiel — besonnesch iwwer den Zougangspunkt vum <strong>CTIE</strong>, deen ëffentlech Organismen ouni eegene Punkt notzen.</p>

<p><strong>An der Produktivsetzung:</strong> déi automatesch Iwwerdroung iwwer en integréierten Zougangspunkt, déi Iech dëse manuelle Schrëtt erspuert. D'Format ass technesch prett, den Deploiement fir all Konten nach net. Mir aktualiséieren dës Säit, soubal et souwäit ass.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Bis dohinner sidd Dir a Rei</p>
    <p>D'gesetzlech Flicht betrëfft d'<strong>Format</strong> vun der Rechnung an hir elektronesch Iwwerdroung, net d'Instrument, mat deem se zougestallt gëtt. E konforme BIS-3.0-Fichier, iwwer de Kanal vun Ärem ëffentlechen Optraggeber agereecht, erfëllt d'Flicht.</p>
</div>

HTML,
            'pt' => <<<'HTML'
<h2>Peppol e faktur.lu: o que está disponível hoje</h2>

<p>Preferimos ser precisos neste ponto a assinalar uma caixa.</p>

<p><strong>Disponível desde já:</strong> o faktur.lu gera o <strong>ficheiro Peppol BIS Billing 3.0 (UBL)</strong> das suas faturas, conforme ao formato exigido pelo setor público luxemburguês. Descarrega-o a partir da fatura e transmite-o pelo canal da sua escolha — nomeadamente o ponto de acesso do <strong>CTIE</strong>, que os organismos públicos sem ponto próprio utilizam.</p>

<p><strong>Em entrada em produção:</strong> a transmissão automática através de um ponto de acesso integrado, que lhe poupará esse passo manual. O formato está tecnicamente pronto, a implementação para todas as contas ainda não. Atualizaremos esta página no dia em que estiver.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">Entretanto, está em conformidade</p>
    <p>A obrigação legal incide sobre o <strong>formato</strong> da fatura e a sua transmissão eletrónica, não sobre a ferramenta usada para a entregar. Um ficheiro BIS 3.0 conforme, submetido pelo canal do seu comprador público, cumpre a obrigação.</p>
</div>

HTML,
            default => '',
        };
    }
};
