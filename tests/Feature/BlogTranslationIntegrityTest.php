<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invariants d'appariement des traductions.
 *
 * Contexte : la production a servi 27 articles dont le `translation_key`
 * designait un autre article que celui du slug. Comme toutes les migrations
 * editoriales ciblent `translation_key` + `locale`, elles ecrivaient alors dans
 * la mauvaise ligne, sans jamais lever d'erreur — le defaut n'etait visible
 * qu'en comparant le contenu servi au contenu attendu.
 *
 * Ces tests verrouillent les invariants verifiables localement. Ils ne
 * remplacent pas la comparaison avec la production apres deploiement.
 */
class BlogTranslationIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function article(string $slug, string $locale, string $key): BlogPost
    {
        return BlogPost::create([
            'title' => ucfirst($slug),
            'slug' => $slug,
            'content' => '<p>Contenu de '.$slug.'</p>',
            'locale' => $locale,
            'translation_key' => $key,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function test_un_slug_n_appartient_qu_a_un_seul_groupe_de_traduction(): void
    {
        $this->article('article-a', 'fr', 'groupe-a');
        $this->article('artikel-a', 'de', 'groupe-a');

        $duplicates = BlogPost::query()
            ->selectRaw('slug')
            ->groupBy('slug')
            ->havingRaw('count(distinct translation_key) > 1')
            ->pluck('slug');

        $this->assertEmpty(
            $duplicates,
            'Un meme slug est rattache a plusieurs translation_key : '.$duplicates->implode(', ')
        );
    }

    public function test_un_groupe_de_traduction_n_a_pas_deux_fois_la_meme_langue(): void
    {
        $this->article('article-b', 'fr', 'groupe-b');
        $this->article('artikel-b', 'de', 'groupe-b');

        $collisions = BlogPost::query()
            ->selectRaw('translation_key, locale')
            ->groupBy('translation_key', 'locale')
            ->havingRaw('count(*) > 1')
            ->get();

        $this->assertCount(
            0,
            $collisions,
            'Un groupe de traduction contient deux articles dans la meme langue.'
        );
    }

    public function test_une_permutation_de_cles_est_detectee_par_comparaison_a_la_reference(): void
    {
        // Reproduit la forme exacte du defaut de production : deux articles
        // echangent leur translation_key. Les invariants structurels restent
        // satisfaits — d'ou l'invisibilite du bug — mais le slug ne correspond
        // plus a son groupe. Seule une reference externe le revele.
        $a = $this->article('pflichtangaben-rechnung-luxemburg', 'de', 'mentions-obligatoires-facture-luxembourg');
        $b = $this->article('einzelunternehmen-deutschland-2026', 'de', 'creer-entreprise-individuelle-allemagne');

        $reference = [
            $a->slug => $a->translation_key,
            $b->slug => $b->translation_key,
        ];

        // Les invariants structurels tiennent malgre la permutation.
        $a->update(['translation_key' => $reference[$b->slug]]);
        $b->update(['translation_key' => $reference[$a->slug]]);

        $collisions = BlogPost::query()
            ->selectRaw('translation_key, locale')
            ->groupBy('translation_key', 'locale')
            ->havingRaw('count(*) > 1')
            ->get();

        $this->assertCount(0, $collisions, 'La permutation ne doit pas creer de collision structurelle.');

        // Seule la comparaison a la reference la revele.
        $drifted = BlogPost::query()
            ->whereIn('slug', array_keys($reference))
            ->get()
            ->filter(fn (BlogPost $p) => $reference[$p->slug] !== $p->translation_key);

        $this->assertCount(2, $drifted, 'Une permutation de translation_key doit etre detectee.');
    }
}
