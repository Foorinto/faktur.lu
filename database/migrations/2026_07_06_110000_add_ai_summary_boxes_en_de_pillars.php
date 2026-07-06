<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO / IA — réplique l'encart "En bref" AI-readable (cf. migration FR
 * 2026_07_06_100000) sur les versions EN + DE des mêmes articles (piliers +
 * comparatif). Objectif : featured snippets + citabilité IA sur les 2 langues
 * à trafic. translation_key partagé entre locales -> mêmes clés, filtre locale.
 *
 * Idempotent (marqueur data-ai-summary), guardé, additif. Aucun impact code/route/DB-schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        $out = fn ($s) => print($s . "\n");

        $box = function (string $label, array $items): string {
            $lis = implode('', array_map(fn ($i) => '<li>' . $i . '</li>', $items));

            return '<div data-ai-summary class="bg-slate-50 border border-slate-200 rounded-xl p-5 my-4">'
                . '<p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">' . $label . '</p>'
                . '<ul class="list-disc pl-5 space-y-1 text-slate-700 text-sm">' . $lis . '</ul>'
                . '</div>';
        };

        $data = [
            'en' => [
                'guide-complet-facturation-luxembourg-2026' => $box('Key points', [
                    'Every invoice must carry the mandatory particulars of <strong>Article 63 LIVA</strong> (seller/buyer identity, VAT numbers, unique sequential number, rates and amounts).',
                    '<strong>4 VAT rates</strong> in Luxembourg: 17% (standard), 14% (intermediate), 8% (reduced), 3% (super-reduced).',
                    '<strong>Continuous sequential numbering</strong>, with no gaps or duplicates.',
                    '<strong>10-year retention</strong>; the <strong>FAIA 2.01</strong> file can be requested by the AED during an audit.',
                    '<strong>VAT exemption (franchise)</strong> available below <strong>&euro;50,000 net/year</strong> (threshold in force since 2025).',
                ]),
                'mentions-obligatoires-facture-luxembourg' => $box('Key points', [
                    'Mandatory particulars fall under <strong>Article 63 LIVA</strong>: parties&rsquo; identity and address, VAT numbers, date, <strong>unique sequential number</strong>, description, net base, rate, VAT amount, gross total.',
                    'A <strong>non-compliant invoice</strong> can be rejected by the client and trigger an <strong>AED fine (&euro;250 to &euro;10,000, art. 77 LIVA)</strong>.',
                    'Conditional particulars: <strong>reverse charge</strong> (art. 17 LIVA / art. 196 Directive), franchise, exemptions.',
                    '<strong>10-year retention</strong> of invoices and accounting records.',
                ]),
                'choisir-logiciel-facturation-luxembourg-comparatif' => $box('Key points', [
                    '<strong>6 criteria</strong> to choose: Luxembourg compliance (FAIA 2.01, art. 63 particulars, 4 VAT rates, &euro;50,000 franchise), full cycle (quotes, credit notes, recurring), ease of use, all-inclusive pricing, security (EU hosting, 2FA), accountant integration.',
                    'The <strong>#1 filter</strong>: the <strong>FAIA 2.01</strong> export, requestable by the AED during an audit &mdash; a tool that cannot generate it is disqualifying.',
                    'Beware headline prices: compare what is <strong>actually included</strong> (FAIA, Peppol, number of invoices), not just the advertised rate.',
                ]),
            ],
            'de' => [
                'guide-complet-facturation-luxembourg-2026' => $box('Kurz gefasst', [
                    'Jede Rechnung muss die <strong>Pflichtangaben nach Artikel 63 LIVA</strong> enthalten (Identit&auml;t von Verk&auml;ufer/K&auml;ufer, USt-IdNr., fortlaufende eindeutige Nummer, S&auml;tze und Betr&auml;ge).',
                    '<strong>4 MwSt-S&auml;tze</strong> in Luxemburg: 17 % (Normalsatz), 14 % (Zwischensatz), 8 % (erm&auml;&szlig;igt), 3 % (stark erm&auml;&szlig;igt).',
                    '<strong>Kontinuierliche fortlaufende Nummerierung</strong>, ohne L&uuml;cken oder Duplikate.',
                    '<strong>10 Jahre Aufbewahrung</strong>; die <strong>FAIA-2.01</strong>-Datei kann von der AED bei einer Pr&uuml;fung verlangt werden.',
                    '<strong>MwSt-Befreiung</strong> m&ouml;glich unter <strong>50 000 &euro; netto/Jahr</strong> (Schwelle seit 2025 in Kraft).',
                ]),
                'mentions-obligatoires-facture-luxembourg' => $box('Kurz gefasst', [
                    'Die Pflichtangaben ergeben sich aus <strong>Artikel 63 LIVA</strong>: Identit&auml;t und Anschrift der Parteien, USt-IdNr., Datum, <strong>eindeutige fortlaufende Nummer</strong>, Bezeichnung, Nettobasis, Satz, MwSt-Betrag, Bruttosumme.',
                    'Eine <strong>nicht konforme Rechnung</strong> kann vom Kunden abgelehnt werden und eine <strong>AED-Geldbu&szlig;e (250 &euro; bis 10 000 &euro;, Art. 77 LIVA)</strong> ausl&ouml;sen.',
                    'Bedingte Angaben: <strong>Reverse-Charge (Steuerschuldumkehr)</strong> (Art. 17 LIVA / Art. 196 Richtlinie), Befreiung, Steuerbefreiungen.',
                    '<strong>10 Jahre Aufbewahrung</strong> der Rechnungen und Buchungsbelege.',
                ]),
                'choisir-logiciel-facturation-luxembourg-comparatif' => $box('Kurz gefasst', [
                    '<strong>6 Kriterien</strong> f&uuml;r die Wahl: Luxemburg-Konformit&auml;t (FAIA 2.01, Pflichtangaben Art. 63, 4 MwSt-S&auml;tze, Befreiung 50 000 &euro;), vollst&auml;ndiger Zyklus (Angebote, Gutschriften, wiederkehrende Rechnungen), Einfachheit, All-inclusive-Preis, Sicherheit (EU-Hosting, 2FA), Integration mit dem Treuh&auml;nder.',
                    'Der <strong>wichtigste Filter</strong>: der <strong>FAIA-2.01</strong>-Export, von der AED bei Pr&uuml;fungen verlangt &mdash; ein Tool ohne diese Funktion ist ausgeschlossen.',
                    'Vorsicht bei Lockpreisen: vergleichen Sie, was <strong>tats&auml;chlich enthalten</strong> ist (FAIA, Peppol, Anzahl der Rechnungen), nicht nur den beworbenen Preis.',
                ]),
            ],
        ];

        foreach ($data as $locale => $summaries) {
            foreach ($summaries as $tk => $html) {
                $post = DB::table('blog_posts')->where('locale', $locale)->where('translation_key', $tk)->first();
                if (! $post) { $out("  [$locale/$tk] ⚠️ article NON TROUVÉ"); continue; }
                if (str_contains($post->content, 'data-ai-summary')) { $out("  [$locale/$tk] ⏭ encart déjà présent"); continue; }

                DB::table('blog_posts')->where('id', $post->id)->update([
                    'content' => $html . "\n" . $post->content,
                    'updated_at' => now(),
                ]);
                $out("  [$locale/$tk] ✅ encart ajouté");
            }
        }
    }

    public function down(): void {}
};
