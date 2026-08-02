<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Article « Faire un devis professionnel au Luxembourg ».
 *
 * Le fond n'appelait aucune correction : le devis n'est effectivement pas
 * obligatoire au Luxembourg, un devis signé a bien valeur contractuelle, et
 * les revendications produit tiennent toutes. La table `quotes` porte
 * `accepted_at`, `valid_until` et `converted_to_invoice_id`, et
 * QuoteController expose markAsAccepted() et convertToInvoice(). Les devis
 * font partie du plan gratuit, ce qui rend le CTA exact.
 *
 * Seule la parité était en défaut : les quatre traductions n'avaient qu'un
 * lien contre trois. Les deux renvois du français — vers l'article
 * « franchise de TVA » et vers « mentions obligatoires » — n'avaient pas été
 * transposés.
 */
return new class extends Migration
{
    private const KEY = 'faire-devis-professionnel-luxembourg';

    /** locale => [[avant, après], …] */
    private function links(): array
    {
        return [
            'de' => [
                ['<p>Sind Sie von der MwSt. befreit, geben Sie den entsprechenden Hinweis statt der MwSt. an.</p>',
                 '<p>Sind Sie von der MwSt. befreit, geben Sie den entsprechenden Hinweis statt der MwSt. an (siehe unseren Artikel zur <a href="/de/blog/mwst-befreiung-luxemburg-schwelle-pflichten-normalregime">MwSt-Befreiung</a>).</p>'],
                ['stellen Sie die <strong>Rechnung</strong> aus',
                 'stellen Sie die <strong><a href="/de/blog/pflichtangaben-rechnung-luxemburg">Rechnung</a></strong> aus'],
            ],
            'en' => [
                ['<p>If you are under the VAT exemption scheme, state the relevant mention instead of VAT.</p>',
                 '<p>If you are under the VAT exemption scheme, state the relevant mention instead of VAT (see our article on the <a href="/en/blog/vat-exemption-luxembourg-threshold-obligations-normal-regime">VAT exemption</a>).</p>'],
                ['you issue the <strong>invoice</strong>',
                 'you issue the <strong><a href="/en/blog/mandatory-information-invoice-luxembourg">invoice</a></strong>'],
            ],
            'lb' => [
                ["<p>Wann Dir am Befreiungsregime sidd, gitt déi entspriechend Mentioun amplaz vun der TVA un.</p>",
                 "<p>Wann Dir am Befreiungsregime sidd, gitt déi entspriechend Mentioun amplaz vun der TVA un (kuckt eisen Artikel zur <a href=\"/lb/blog/tva-befreiung-letzebuerg-schwellwaert-obligatiounen-normalregime\">TVA-Franchise</a>).</p>"],
                ["stellt Dir d'<strong>Rechnung</strong> aus",
                 "stellt Dir d'<strong><a href=\"/lb/blog/pflichtinformatiounen-rechnung-letzebuerg\">Rechnung</a></strong> aus"],
            ],
            'pt' => [
                ['<p>Se estiver no regime de isenção de IVA, indique a menção correspondente em vez do IVA.</p>',
                 '<p>Se estiver no regime de isenção de IVA, indique a menção correspondente em vez do IVA (veja o nosso artigo sobre a <a href="/pt/blog/isencao-de-iva-no-luxemburgo-limiar-obrigacoes-e-passagem-ao-regime-normal">isenção de IVA</a>).</p>'],
                ['emite a <strong>fatura</strong>',
                 'emite a <strong><a href="/pt/blog/mencoes-obrigatorias-numa-fatura-no-luxemburgo-checklist-completa">fatura</a></strong>'],
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->links() as $locale => $pairs) {
            $post = DB::table('blog_posts')
                ->where('translation_key', self::KEY)
                ->where('locale', $locale)
                ->first(['id', 'content']);

            if (! $post) {
                continue;
            }

            $content = $post->content;
            $applied = 0;

            foreach ($pairs as [$from, $to]) {
                if (str_contains($content, $from)) {
                    $content = str_replace($from, $to, $content);
                    $applied++;
                } else {
                    echo "  !! {$locale} : motif introuvable — ".mb_substr($from, 0, 48)."\n";
                }
            }

            DB::table('blog_posts')->where('id', $post->id)->update([
                'content' => $content,
                'updated_at' => now(),
            ]);

            echo "  {$locale} : {$applied}/2 lien(s)\n";
        }
    }

    public function down(): void
    {
        // Retirer des liens internes valides n'aurait pas de sens.
    }
};
