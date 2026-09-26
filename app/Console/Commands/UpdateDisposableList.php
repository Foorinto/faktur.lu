<?php

namespace App\Console\Commands;

use App\Services\AbuseProtectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Télécharge la liste communautaire des domaines d'adresses jetables
 * (FEAT-138), utilisée à l'inscription par NotDisposableEmail.
 *
 * Un téléchargement raté, ou une réponse qui ne ressemble pas à la liste
 * (page d'erreur, fichier tronqué), garde la dernière liste connue : une
 * liste vide ouvrirait l'inscription à tous les domaines jetables sans que
 * personne ne le voie. Écriture atomique, pour qu'une inscription ne lise
 * jamais un fichier à moitié écrit.
 */
class UpdateDisposableList extends Command
{
    protected $signature = 'abuse:update-disposable-list';

    protected $description = "Met à jour la liste des domaines d'adresses jetables refusés à l'inscription";

    public function handle(): int
    {
        $url = (string) config('abuse.disposable_list_url');
        $minimum = (int) config('abuse.disposable_list_min_lines', 1000);
        $disque = Storage::disk('local');
        $chemin = AbuseProtectionService::DISPOSABLE_LIST_PATH;

        try {
            $reponse = Http::timeout(15)->get($url);
        } catch (\Throwable $e) {
            return $this->conserver("téléchargement impossible ({$e->getMessage()})");
        }

        if (! $reponse->successful()) {
            return $this->conserver("réponse HTTP {$reponse->status()}");
        }

        $domaines = array_values(array_unique(AbuseProtectionService::parseDomainList($reponse->body())));

        if (count($domaines) < $minimum) {
            return $this->conserver(count($domaines)." domaines reconnus, moins que le minimum de {$minimum}");
        }

        sort($domaines);

        $temporaire = $chemin.'.tmp';
        $disque->put($temporaire, implode("\n", $domaines)."\n");

        if (! @rename($disque->path($temporaire), $disque->path($chemin))) {
            $disque->delete($temporaire);

            return $this->conserver("écriture impossible dans {$chemin}");
        }

        $this->info(count($domaines).' domaines jetables enregistrés.');
        Log::info('Liste des domaines jetables mise à jour.', ['domaines' => count($domaines)]);

        return self::SUCCESS;
    }

    private function conserver(string $motif): int
    {
        $existe = Storage::disk('local')->exists(AbuseProtectionService::DISPOSABLE_LIST_PATH);
        $message = "Liste des domaines jetables non mise à jour : {$motif}. "
            .($existe ? 'La liste précédente reste en service.' : 'Seul le socle de config/abuse.php est en service.');

        $this->warn($message);
        Log::warning($message);

        return self::FAILURE;
    }
}
