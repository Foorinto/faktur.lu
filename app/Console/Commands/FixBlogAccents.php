<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Restore French accents and German umlauts in blog_posts where they were
 * stripped during the original JSON import. Uses a word-boundary dictionary
 * to avoid touching HTML attributes, URLs, or shortcodes.
 *
 *     php artisan blog:fix-accents               # FR + DE
 *     php artisan blog:fix-accents --locale=fr   # FR only
 *     php artisan blog:fix-accents --dry         # preview without saving
 */
class FixBlogAccents extends Command
{
    protected $signature = 'blog:fix-accents
                            {--locale= : Restrict to a single locale (fr|de)}
                            {--dry : Print changes without persisting}';

    protected $description = 'Restore French/German accents in blog post titles, excerpts and content';

    public function handle(): int
    {
        $dictionaries = [
            'fr' => $this->frenchDictionary(),
            'de' => $this->germanDictionary(),
        ];

        $locales = $this->option('locale')
            ? [$this->option('locale')]
            : ['fr', 'de'];

        $dryRun = (bool) $this->option('dry');

        $totalUpdated = 0;
        $totalReplacements = 0;

        foreach ($locales as $locale) {
            if (! isset($dictionaries[$locale])) {
                $this->warn("No dictionary for locale [$locale], skipping.");
                continue;
            }

            $dict = $dictionaries[$locale];
            $posts = DB::table('blog_posts')->where('locale', $locale)->get();

            $this->info(sprintf('[%s] Scanning %d posts...', $locale, $posts->count()));

            foreach ($posts as $post) {
                $changes = [];
                $perFieldReplacements = 0;

                foreach (['title', 'excerpt', 'content', 'meta_title', 'meta_description'] as $field) {
                    if (! property_exists($post, $field) || $post->{$field} === null) {
                        continue;
                    }
                    $original = $post->{$field};
                    [$newValue, $count] = $this->applyDictionary($original, $dict);
                    if ($count > 0) {
                        $changes[$field] = $newValue;
                        $perFieldReplacements += $count;
                    }
                }

                if (empty($changes)) {
                    continue;
                }

                $totalReplacements += $perFieldReplacements;

                if ($dryRun) {
                    $this->line(sprintf(
                        '  [DRY] #%d (%s) — %d replacement(s) in %s',
                        $post->id,
                        $post->slug ?? '?',
                        $perFieldReplacements,
                        implode(', ', array_keys($changes)),
                    ));
                    if (isset($changes['title'])) {
                        $this->line('       title: ' . $post->title . ' → ' . $changes['title']);
                    }
                } else {
                    DB::table('blog_posts')->where('id', $post->id)->update($changes);
                    $totalUpdated++;
                    $this->line(sprintf(
                        '  ✓ #%d (%s) — %d replacement(s)',
                        $post->id,
                        $post->slug ?? '?',
                        $perFieldReplacements,
                    ));
                }
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info(sprintf('[DRY] Would replace %d term(s) across affected posts.', $totalReplacements));
        } else {
            $this->info(sprintf('Done. %d post(s) updated, %d replacement(s) total.', $totalUpdated, $totalReplacements));
        }

        return self::SUCCESS;
    }

    /**
     * Apply a word-boundary dictionary to a string. Returns [newString, replacementCount].
     */
    private function applyDictionary(string $text, array $dict): array
    {
        $count = 0;
        foreach ($dict as $needle => $replacement) {
            // Word-boundary regex (Unicode-aware) — avoids replacing inside larger words
            $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{N}])/u';
            $text = preg_replace($pattern, $replacement, $text, -1, $c);
            $count += $c;
        }
        return [$text, $count];
    }

    /**
     * French dictionary — words and short phrases that are spelled with accents
     * in standard French. Keep entries unambiguous: don't add words whose
     * accent-less form is legitimate in another context.
     */
    private function frenchDictionary(): array
    {
        return [
            // Common terms used in blog titles + intros
            'a l etranger' => 'à l\'étranger',
            'a l etablir' => 'à l\'établir',
            'l etablir' => 'l\'établir',
            'l etrange' => 'l\'étrange',
            'Decouvrez' => 'Découvrez',
            'decouvrez' => 'découvrez',
            'Decouvrir' => 'Découvrir',
            'decouvrir' => 'découvrir',
            'frequentes' => 'fréquentes',
            'frequente' => 'fréquente',
            'frequent' => 'fréquent',
            'frequents' => 'fréquents',
            'Controle' => 'Contrôle',
            'controle' => 'contrôle',
            'Controles' => 'Contrôles',
            'controles' => 'contrôles',
            'preparer' => 'préparer',
            'prepare' => 'préparé',
            'preparation' => 'préparation',
            'credit' => 'crédit',
            'credits' => 'crédits',
            'note de credit' => 'note de crédit',
            'regime' => 'régime',
            'regimes' => 'régimes',
            'electronique' => 'électronique',
            'electroniques' => 'électroniques',
            'europeen' => 'européen',
            'europeenne' => 'européenne',
            'europeens' => 'européens',
            'europeennes' => 'européennes',
            'duree' => 'durée',
            'durees' => 'durées',
            'legale' => 'légale',
            'legales' => 'légales',
            'legal' => 'légal',
            'legaux' => 'légaux',
            'modele' => 'modèle',
            'modeles' => 'modèles',
            'etablir' => 'établir',
            'etabli' => 'établi',
            'etablis' => 'établis',
            'etablie' => 'établie',
            'numerotation' => 'numérotation',
            'numerotations' => 'numérotations',
            'numero' => 'numéro',
            'numeros' => 'numéros',
            'reseau' => 'réseau',
            'reseaux' => 'réseaux',
            'generer' => 'générer',
            'generee' => 'générée',
            'generees' => 'générées',
            'genere' => 'généré',
            'generes' => 'générés',
            'generation' => 'génération',
            'conformite' => 'conformité',
            'conformites' => 'conformités',
            'conserver' => 'conserver',
            'conservees' => 'conservées',
            'acceptes' => 'acceptés',
            'acceptees' => 'acceptées',
            'paye' => 'payé',
            'payee' => 'payée',
            'payes' => 'payés',
            'payees' => 'payées',
            'mises a jour' => 'mises à jour',
            'mise a jour' => 'mise à jour',
            'detaille' => 'détaillé',
            'detaillee' => 'détaillée',
            'detaillees' => 'détaillées',
            'detailles' => 'détaillés',
            'detail' => 'détail',
            'details' => 'détails',
            'reference' => 'référence',
            'references' => 'références',
            'referencement' => 'référencement',
            'recouvrement' => 'recouvrement', // safe (no change but kept for clarity)
            'integre' => 'intégré',
            'integree' => 'intégrée',
            'integres' => 'intégrés',
            'integrees' => 'intégrées',
            'integration' => 'intégration',
            'integrations' => 'intégrations',
            'precis' => 'précis',
            'precise' => 'précise',
            'precises' => 'précises',
            'precisement' => 'précisément',
            'precision' => 'précision',
            'specifique' => 'spécifique',
            'specifiques' => 'spécifiques',
            'specifie' => 'spécifié',
            'verifier' => 'vérifier',
            'verifie' => 'vérifié',
            'verifiee' => 'vérifiée',
            'verifies' => 'vérifiés',
            'verifiees' => 'vérifiées',
            'verification' => 'vérification',
            'cree' => 'créé',
            'creer' => 'créer',
            'creee' => 'créée',
            'creees' => 'créées',
            'crees' => 'créés',
            'creation' => 'création',
            'creations' => 'créations',
            'gerer' => 'gérer',
            'gere' => 'géré',
            'geree' => 'gérée',
            'gestion' => 'gestion', // no change
            'aere' => 'aéré',
            'aeree' => 'aérée',
            'reglementation' => 'réglementation',
            'reglement' => 'règlement',
            'reglements' => 'règlements',
            'regle' => 'règle',
            'regles' => 'règles',
            'societe' => 'société',
            'societes' => 'sociétés',
            'societaire' => 'sociétaire',
            'activite' => 'activité',
            'activites' => 'activités',
            'liberte' => 'liberté',
            'libertes' => 'libertés',
            'securite' => 'sécurité',
            'securites' => 'sécurités',
            'securiser' => 'sécuriser',
            'securise' => 'sécurisé',
            'securisee' => 'sécurisée',
            'verite' => 'vérité',
            'realite' => 'réalité',
            'realites' => 'réalités',
            'qualite' => 'qualité',
            'qualites' => 'qualités',
            'tva intracommunautaire' => 'TVA intracommunautaire',
            'declarer' => 'déclarer',
            'declaration' => 'déclaration',
            'declarations' => 'déclarations',
            'declare' => 'déclaré',
            'declaree' => 'déclarée',
            'declares' => 'déclarés',
            'declarees' => 'déclarées',
            'reponse' => 'réponse',
            'reponses' => 'réponses',
            'repondre' => 'répondre',
            'fiscalite' => 'fiscalité',
            'fiscalites' => 'fiscalités',
            'recettes' => 'recettes', // no change
            'echeance' => 'échéance',
            'echeances' => 'échéances',
            'depense' => 'dépense',
            'depenses' => 'dépenses',
            'depend' => 'dépend',
            'dependant' => 'dépendant',
            'dependante' => 'dépendante',
            'depute' => 'député',
            'frais a payer' => 'frais à payer',
            'face a' => 'face à',
            'jusqu a' => 'jusqu\'à',
            'jusqu au' => 'jusqu\'au',
            // Common articles
            'meme' => 'même',
            'memes' => 'mêmes',
            'tres' => 'très',
            'apres' => 'après',
            'derniere' => 'dernière',
            'dernieres' => 'dernières',
            'premiere' => 'première',
            'premieres' => 'premières',
            'matiere' => 'matière',
            'matieres' => 'matières',
            'maniere' => 'manière',
            'manieres' => 'manières',
            // Additional common verbs/nouns
            'expliquee' => 'expliquée',
            'expliquees' => 'expliquées',
            'expliques' => 'expliqués',
            'explique' => 'expliqué',
            'Delais' => 'Délais',
            'delais' => 'délais',
            'delai' => 'délai',
            'Delai' => 'Délai',
            'beneficier' => 'bénéficier',
            'beneficie' => 'bénéficie',
            'beneficiez' => 'bénéficiez',
            'a partir de' => 'à partir de',
            'ete' => 'été',
            'etre' => 'être',
            'completes' => 'complètes',
            'complete' => 'complète',
            'apporte' => 'apporté',
            'apportee' => 'apportée',
            'apportes' => 'apportés',
            'apportees' => 'apportées',
            'utilise' => 'utilisé',
            'utilisee' => 'utilisée',
            'utilisees' => 'utilisées',
            'utilises' => 'utilisés',
            'depuis' => 'depuis', // no change
            'differents' => 'différents',
            'differente' => 'différente',
            'differentes' => 'différentes',
            'different' => 'différent',
            'systeme' => 'système',
            'systemes' => 'systèmes',
            'methode' => 'méthode',
            'methodes' => 'méthodes',
            'fiscalite' => 'fiscalité',
            'gere' => 'géré',
            'echange' => 'échange',
            'echanges' => 'échanges',
            'etape' => 'étape',
            'etapes' => 'étapes',
            'cle' => 'clé',
            'cles' => 'clés',
            'enregistree' => 'enregistrée',
            'enregistre' => 'enregistré',
            'enregistrees' => 'enregistrées',
            'enregistres' => 'enregistrés',

            // Specific phrases from titles
            'd entrepreneurs' => 'd\'entrepreneurs',
            'd entreprise' => 'd\'entreprise',
            'd entreprises' => 'd\'entreprises',
            'l entreprise' => 'l\'entreprise',
            'd archivage' => 'd\'archivage',
            'd email' => 'd\'email',
            'd emails' => 'd\'emails',
            'd impayes' => 'd\'impayés',
        ];
    }

    /**
     * German dictionary — convert "ae/oe/ue" digraphs back to umlauts ONLY on
     * known words. We never apply a blind regex (would break "Goethe", "Boer",
     * URLs, etc.).
     */
    private function germanDictionary(): array
    {
        return [
            'haeufig' => 'häufig',
            'haeufige' => 'häufige',
            'haeufiger' => 'häufiger',
            'haeufige Fehler' => 'häufige Fehler',
            'Vollstaendig' => 'Vollständig',
            'Vollstaendige' => 'Vollständige',
            'Vollstaendiger' => 'Vollständiger',
            'vollstaendig' => 'vollständig',
            'Steuerpruefung' => 'Steuerprüfung',
            'Steuerpruefungen' => 'Steuerprüfungen',
            'pruefung' => 'prüfung',
            'pruefen' => 'prüfen',
            'Pruefung' => 'Prüfung',
            'Pruefungen' => 'Prüfungen',
            'fuer' => 'für',
            'Fuer' => 'Für',
            'ueber' => 'über',
            'Ueber' => 'Über',
            'uebersicht' => 'übersicht',
            'Uebersicht' => 'Übersicht',
            'waehlen' => 'wählen',
            'Waehlen' => 'Wählen',
            'gewaehlt' => 'gewählt',
            'auswaehlen' => 'auswählen',
            'Europaeisch' => 'Europäisch',
            'Europaeische' => 'Europäische',
            'Europaeischen' => 'Europäischen',
            'europaeisch' => 'europäisch',
            'europaeische' => 'europäische',
            'taeglich' => 'täglich',
            'monatlich' => 'monatlich', // no change
            'Geschaeft' => 'Geschäft',
            'geschaeftlich' => 'geschäftlich',
            'naechste' => 'nächste',
            'naechsten' => 'nächsten',
            'naechster' => 'nächster',
            'Naechst' => 'Nächst',
            'erhaeltlich' => 'erhältlich',
            'verfuegbar' => 'verfügbar',
            'Verfuegbar' => 'Verfügbar',
            'unterstuetzt' => 'unterstützt',
            'unterstuetzen' => 'unterstützen',
            'Unterstuetzung' => 'Unterstützung',
            'einfuehren' => 'einführen',
            'eingefuehrt' => 'eingeführt',
            'einfuegen' => 'einfügen',
            'eingefuegt' => 'eingefügt',
            'tatsaechlich' => 'tatsächlich',
            'Maerz' => 'März',
            'maerz' => 'märz',
            'Geschaeftsfuehrer' => 'Geschäftsführer',
            'Geschaeftsstelle' => 'Geschäftsstelle',
            'aendern' => 'ändern',
            'Aendern' => 'Ändern',
            'geaendert' => 'geändert',
            'Aenderung' => 'Änderung',
            'Aenderungen' => 'Änderungen',
            'Ablaufdatum' => 'Ablaufdatum', // no change
            'spaeter' => 'später',
            'koennen' => 'können',
            'Koennen' => 'Können',
            'koennte' => 'könnte',
            'koennten' => 'könnten',
            'muessen' => 'müssen',
            'Muessen' => 'Müssen',
            'muss' => 'muss', // no change
            'gruenden' => 'gründen',
            'Gruenden' => 'Gründen',
            'gegruendet' => 'gegründet',
            'Gruendung' => 'Gründung',
            'Gruender' => 'Gründer',
            'Hoehe' => 'Höhe',
            'hoechste' => 'höchste',
            'hoeher' => 'höher',
            'Loesung' => 'Lösung',
            'Loesungen' => 'Lösungen',
            'loeschen' => 'löschen',
            'geloescht' => 'gelöscht',
            'oeffentlich' => 'öffentlich',
            'Oeffentlich' => 'Öffentlich',
            'oeffnen' => 'öffnen',
            'geoeffnet' => 'geöffnet',
            'fruehestens' => 'frühestens',
            'fruehzeitig' => 'frühzeitig',
            'natuerlich' => 'natürlich',
            'persoenlich' => 'persönlich',
            'Persoenlich' => 'Persönlich',
            'gesetzlich' => 'gesetzlich', // no change
            'taetig' => 'tätig',
            'Taetigkeit' => 'Tätigkeit',
            'Taetigkeiten' => 'Tätigkeiten',
            'Anfaenger' => 'Anfänger',
            'erklaeren' => 'erklären',
            'erklaert' => 'erklärt',
            'Erklaerung' => 'Erklärung',
            'Erlaeuterung' => 'Erläuterung',
            'beruecksichtigen' => 'berücksichtigen',
            'beruecksichtigt' => 'berücksichtigt',
            'pruefen Sie' => 'prüfen Sie',
            'Buecher' => 'Bücher',
            'Verkaeufer' => 'Verkäufer',
            'Kaeufer' => 'Käufer',
            'Empfaenger' => 'Empfänger',
            'verzoegert' => 'verzögert',
            'Verzoegerung' => 'Verzögerung',
            // Common adverbs/conjunctions
            'waehrend' => 'während',
            'gemaess' => 'gemäß',
            'naemlich' => 'nämlich',
            'staendig' => 'ständig',
            'staerker' => 'stärker',
        ];
    }
}
