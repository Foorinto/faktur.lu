<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Déplace les fichiers contenant des données personnelles du disque public
 * (servi directement par le serveur web) vers le disque privé.
 *
 * Les chemins stockés en base sont relatifs au disque, donc inchangés : seul
 * l'emplacement physique bouge. À rejouer sans risque, la commande ignore ce
 * qui est déjà en place.
 */
class PrivatizePersonalFiles extends Command
{
    protected $signature = 'files:privatize {--dry-run : Liste les fichiers sans rien déplacer}';

    protected $description = 'Déplace les fichiers personnels (RH, support) du disque public vers le disque privé';

    /** Répertoires concernés. Logos, QR de paiement et visuels de blog restent publics. */
    private const DIRECTORIES = [
        'hr/documents',
        'hr/evaluations',
        'hr/expense-receipts',
        'hr/photos',
        'support-attachments',
    ];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $dryRun = (bool) $this->option('dry-run');

        $moved = 0;
        $skipped = 0;

        foreach (self::DIRECTORIES as $directory) {
            if (! $public->exists($directory)) {
                continue;
            }

            foreach ($public->allFiles($directory) as $path) {
                if ($private->exists($path)) {
                    $this->line("  = déjà privé : {$path}");
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("  → à déplacer : {$path}");
                    $moved++;

                    continue;
                }

                $private->put($path, $public->get($path));
                $public->delete($path);

                $this->line("  ✔ déplacé : {$path}");
                $moved++;
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info("{$moved} fichier(s) à déplacer, {$skipped} déjà privé(s). Relancez sans --dry-run pour appliquer.");

            return self::SUCCESS;
        }

        $this->info("{$moved} fichier(s) déplacé(s), {$skipped} déjà privé(s).");

        if ($moved === 0 && $skipped === 0) {
            $this->comment('Aucun fichier personnel sur le disque public : rien à faire.');
        }

        return self::SUCCESS;
    }
}
