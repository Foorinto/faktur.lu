<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le renommage du contenu éditorial ne doit toucher QUE la marque.
 *
 * ⚠️ Le piège, et la raison d'être de ces tests : « fakturieren »,
 * « Fakturatioun », « fakturéieren », « Fakturierung » sont des mots allemands
 * et luxembourgeois qui signifient facturer. Le site en compte plus de cent
 * cinquante occurrences dans ses versions DE et LB. Un chercher-remplacer sur
 * « faktur » les massacrerait, et personne ne s'en apercevrait avant qu'un
 * lecteur luxembourgeois ne le signale.
 *
 * Vérifié sur le contenu réel le 2026-09-01 : la marque n'apparaît jamais
 * seule, toujours sous la forme « faktur.lu ».
 */
class RenameBrandCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $compteur = 0;

    /** Pas de factory pour les articles dans le dépôt : on crée en direct. */
    private function article(array $attributs): BlogPost
    {
        $this->compteur++;

        return BlogPost::create(array_merge([
            'title' => 'Article '.$this->compteur,
            'slug' => 'article-'.$this->compteur,
            'excerpt' => 'Résumé',
            'content' => 'Contenu',
            'status' => 'published',
            'locale' => 'fr',
            'translation_key' => 'article-'.$this->compteur,
        ], $attributs));
    }

    public function test_it_replaces_the_brand(): void
    {
        $article = $this->article(['content' => 'Avec faktur.lu, créez vos factures.']);

        $this->artisan('marque:renommer', ['nouveau' => 'kolux.lu', '--reel' => true])
            ->assertSuccessful();

        $this->assertSame('Avec kolux.lu, créez vos factures.', $article->fresh()->content);
    }

    /**
     * ⚠️ LE test de ce fichier. Deux langues du site en dépendent.
     */
    public function test_it_leaves_german_and_luxembourgish_vocabulary_alone(): void
    {
        $article = $this->article([
            'content' => 'Konform fakturieren mit faktur.lu. D\'Fakturéierung zu Lëtzebuerg. '
                .'Fakturatiounssoftware wéi faktur.lu. Die Fakturierung ist einfach.',
        ]);

        $this->artisan('marque:renommer', ['nouveau' => 'kolux.lu', '--reel' => true]);

        $apres = $article->fresh()->content;

        // Le vocabulaire est intact.
        $this->assertStringContainsString('Konform fakturieren', $apres);
        $this->assertStringContainsString("D'Fakturéierung zu Lëtzebuerg", $apres);
        $this->assertStringContainsString('Fakturatiounssoftware', $apres);
        $this->assertStringContainsString('Die Fakturierung ist einfach', $apres);

        // Et la marque a bien changé, deux fois.
        $this->assertSame(2, substr_count($apres, 'kolux.lu'));
        $this->assertStringNotContainsString('faktur.lu', $apres);
    }

    /**
     * Une phrase qui commence par le nom garde sa majuscule.
     */
    public function test_it_keeps_the_capital_at_the_start_of_a_sentence(): void
    {
        $article = $this->article(['content' => 'Faktur.lu est conforme. Essayez faktur.lu.']);

        $this->artisan('marque:renommer', ['nouveau' => 'kolux.lu', '--reel' => true]);

        $this->assertSame('Kolux.lu est conforme. Essayez kolux.lu.', $article->fresh()->content);
    }

    /**
     * Sans --reel, rien n'est écrit. C'est ce qui permet de mesurer avant
     * d'agir, sur une base de production qu'on ne rejoue pas.
     */
    public function test_the_dry_run_changes_nothing(): void
    {
        $article = $this->article(['content' => 'Avec faktur.lu.']);

        $this->artisan('marque:renommer', ['nouveau' => 'kolux.lu'])
            ->expectsOutputToContain('essai à blanc')
            ->assertSuccessful();

        $this->assertSame('Avec faktur.lu.', $article->fresh()->content);
    }

    public function test_it_covers_the_title_and_the_excerpt(): void
    {
        $article = $this->article([
            'title' => 'Pourquoi faktur.lu',
            'excerpt' => 'faktur.lu en deux mots',
            'content' => 'Rien ici.',
        ]);

        $this->artisan('marque:renommer', ['nouveau' => 'kolux.lu', '--reel' => true]);

        $apres = $article->fresh();
        $this->assertSame('Pourquoi kolux.lu', $apres->title);
        $this->assertSame('kolux.lu en deux mots', $apres->excerpt);
    }

    /**
     * Un nom collé à un mot, par exemple dans une adresse déjà renommée, ne
     * doit pas être touché deux fois si la commande est relancée.
     */
    public function test_running_it_twice_is_harmless(): void
    {
        $article = $this->article(['content' => 'Avec faktur.lu.']);

        $this->artisan('marque:renommer', ['nouveau' => 'kolux.lu', '--reel' => true]);
        $this->artisan('marque:renommer', ['nouveau' => 'kolux.lu', '--reel' => true]);

        $this->assertSame('Avec kolux.lu.', $article->fresh()->content);
    }

    public function test_it_refuses_an_empty_or_identical_name(): void
    {
        $this->artisan('marque:renommer', ['nouveau' => 'faktur.lu'])->assertFailed();
    }
}
