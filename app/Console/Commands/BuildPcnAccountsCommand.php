<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Génère la liste des comptes de charges du plan comptable normalisé.
 *
 * Le PCN est fixé par règlement grand-ducal (12 septembre 2019, applicable aux
 * exercices ouverts depuis le 1er janvier 2020) et **il n'existe aucune API**.
 * La seule source exploitable est un classeur publié par la CNC :
 *
 *   https://cnc.lu/wp-content/uploads/2024/07/PCN_2009_PCN2020-20190909_Diffusion_v1-2.xlsx
 *
 * Cette commande le lit une fois et produit un fichier JSON versionné dans le
 * dépôt. Elle ne tourne pas à l'exécution : elle sert à régénérer le fichier le
 * jour — lointain — d'un nouveau règlement. L'application n'a donc aucune
 * dépendance réseau, et le classeur de 3,7 Mo n'a pas à être commité.
 *
 * Usage :
 *   php artisan pcn:build --file=/chemin/vers/PCN_2009_PCN2020.xlsx
 */
class BuildPcnAccountsCommand extends Command
{
    protected $signature = 'pcn:build
                            {--file= : Chemin du classeur CNC (.xlsx)}
                            {--out= : Fichier de sortie (défaut : resources/data/pcn-class<classe>.json)}
                            {--class=6 : Classe du plan à extraire (6 = charges, 7 = produits)}';

    protected $description = 'Génère resources/data/pcn-class{$classe}.json depuis le classeur du plan comptable de la CNC';

    /** Colonne « Regroupement (R) ou Imputation (I) » de la feuille PCN2020. */
    private const COL_RI = 4;

    public function handle(): int
    {
        // La classe est un paramètre depuis que les articles portent un compte
        // de produits : le catalogue livré ne couvrait que les charges, et
        // aucun compte de la classe 7 n'était donc vérifiable.
        $classe = (string) $this->option('class');

        if (! in_array($classe, ['6', '7'], true)) {
            $this->error('La classe doit valoir 6 (charges) ou 7 (produits).');

            return self::FAILURE;
        }

        $source = $this->option('file');

        if (! $source || ! is_readable($source)) {
            $this->error('Classeur introuvable. Téléchargez-le puis passez --file=');
            $this->line('  https://cnc.lu/wp-content/uploads/2024/07/PCN_2009_PCN2020-20190909_Diffusion_v1-2.xlsx');

            return self::FAILURE;
        }

        $reader = IOFactory::createReaderForFile($source);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($source);

        $pcn2020 = $spreadsheet->getSheetByName('PCN2020');
        $pcnAll = $spreadsheet->getSheetByName('PCN_All');

        if (! $pcn2020 || ! $pcnAll) {
            $this->error('Feuilles « PCN2020 » et « PCN_All » attendues : le format du classeur a changé.');

            return self::FAILURE;
        }

        // Libellés allemands et anglais, indexés par numéro de compte.
        $translations = [];
        foreach ($pcnAll->toArray(null, true, true, false) as $row) {
            $ref = trim((string) ($row[6] ?? ''));
            if ($ref === '' || ! ctype_digit($ref)) {
                continue;
            }
            $translations[$ref] = [
                'de' => trim((string) ($row[8] ?? '')),
                'en' => trim((string) ($row[9] ?? '')),
            ];
        }

        // Tous les intitulés de classe 6, regroupements compris : ils servent à
        // nommer le parent de chaque compte imputable.
        $labels = [];
        $rows = $pcn2020->toArray(null, true, true, false);

        foreach ($rows as $row) {
            $ref = trim((string) ($row[0] ?? ''));
            if ($ref !== '' && ctype_digit($ref) && str_starts_with($ref, $classe)) {
                $labels[$ref] = trim((string) ($row[1] ?? ''));
            }
        }

        $accounts = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $ref = trim((string) ($row[0] ?? ''));

            if ($ref === '' || ! ctype_digit($ref) || ! str_starts_with($ref, $classe)) {
                continue;
            }

            // Seuls les comptes IMPUTABLES sont retenus. « 611 Loyers et charges
            // locatives » est un titre : aucune écriture ne peut y aller. Les
            // proposer serait une invitation à l'erreur.
            if (strtoupper(trim((string) ($row[self::COL_RI] ?? ''))) !== 'I') {
                $skipped++;

                continue;
            }

            $accounts[] = [
                'ref' => $ref,
                'parent' => $this->parentLabel($ref, $labels),
                'fr' => trim((string) ($row[1] ?? '')),
                'de' => $translations[$ref]['de'] ?? '',
                'en' => $translations[$ref]['en'] ?? '',
            ];
        }

        usort($accounts, fn ($a, $b) => strcmp($a['ref'], $b['ref']));

        $out = $this->option('out') ?: resource_path('data/pcn-class6.json');
        @mkdir(dirname($out), 0755, true);
        file_put_contents($out, json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $this->info(count($accounts).' comptes imputables écrits dans '.$out);
        $this->line('  ('.$skipped.' regroupements écartés)');

        return self::SUCCESS;
    }

    /**
     * Libellé du compte parent — le plus long numéro qui préfixe celui-ci.
     *
     * Il donne le contexte à l'écran : « Locations et leasing opérationnel
     * immobiliers » seul est ambigu ; sous « Loyers et charges locatives », il
     * est limpide.
     */
    private function parentLabel(string $ref, array $labels): string
    {
        for ($len = strlen($ref) - 1; $len >= 2; $len--) {
            $candidate = substr($ref, 0, $len);
            if (isset($labels[$candidate]) && $labels[$candidate] !== '') {
                return $labels[$candidate];
            }
        }

        return '';
    }
}
