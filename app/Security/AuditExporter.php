<?php

namespace App\Security;

use App\Services\BackupService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * La copie hors site du journal d'audit (FEAT-125).
 *
 * Chaque nuit, les entrées scellées depuis le dernier export partent dans un
 * fichier JSON Lines compressé, accompagné d'un manifeste : bornes, nombre,
 * empreinte de tête, somme du fichier, le tout signé avec la clé de
 * l'application. Le fichier est chiffré avec la clé des sauvegardes et déposé
 * par rclone dans un sous-dossier du dépôt des sauvegardes ; le manifeste, qui
 * ne contient que des nombres et des empreintes, part en clair.
 *
 * Un tiers muni du fichier et du manifeste peut refaire la chaîne ; le
 * serveur, lui, ne peut plus effacer ses traces après coup : la copie de la
 * veille est ailleurs, et verify() exige que la tête exportée existe encore.
 */
class AuditExporter
{
    public function __construct(private AuditChain $chain, private BackupService $backups) {}

    /**
     * @return array{from_id: int, to_id: int, entries: int, file: string, encrypted: bool, remote: ?string}|null
     */
    public function run(): ?array
    {
        $depuis = ((int) DB::table('audit_chain_exports')->max('to_id')) + 1;
        $tete = $this->chain->head();

        if ($tete === null || (int) $tete->id < $depuis) {
            return null;
        }

        $jusqua = (int) $tete->id;
        $dossier = rtrim((string) config('audit.export.local_path'), '/');

        if (! is_dir($dossier) && ! mkdir($dossier, 0700, true) && ! is_dir($dossier)) {
            throw new RuntimeException("Impossible de créer {$dossier}");
        }

        $nom = sprintf('audit-%s-%d-%d', now()->format('Y-m-d-His'), $depuis, $jusqua);
        $fichier = "{$dossier}/{$nom}.jsonl.gz";

        $entrees = $this->ecrire($fichier, $depuis, $jusqua);
        $somme = hash_file('sha256', $fichier);

        $chiffre = false;
        if (config('backup.encryption_key')) {
            $fichier = $this->backups->encrypt($fichier);
            unlink(substr($fichier, 0, -4));
            $chiffre = true;
        }

        $manifeste = [
            'from_id' => $depuis,
            'to_id' => $jusqua,
            'entries' => $entrees,
            'head_hash' => $tete->hash,
            'file_name' => basename($fichier),
            'file_sha256' => $somme,
            'encrypted' => $chiffre,
            'exported_at' => now()->format('Y-m-d H:i:s'),
        ];
        $manifeste['algorithm'] = AuditChain::ALGORITHM;
        $manifeste['signature'] = AuditChain::sign(AuditChain::exportFields($manifeste + ['remote_path' => null]));
        $cheminManifeste = "{$dossier}/{$nom}.manifest.json";
        file_put_contents($cheminManifeste, json_encode($manifeste, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $distant = null;
        if (config('audit.export.cloud')) {
            if (! $chiffre) {
                throw new RuntimeException('Export hors site refusé : pas de clé de chiffrement (BACKUP_ENCRYPTION_KEY), le journal contient des données personnelles.');
            }
            $sousDossier = (string) config('audit.export.cloud_subdir', 'audit');
            $this->backups->uploadToCloud($fichier, $sousDossier);
            $this->backups->uploadToCloud($cheminManifeste, $sousDossier);
            $distant = config('backup.cloud.remote').':'.rtrim((string) config('backup.cloud.path'), '/').'/'.$sousDossier;
        }

        $ligne = [
            'from_id' => $depuis,
            'to_id' => $jusqua,
            'entries' => $entrees,
            'head_hash' => $tete->hash,
            'file_name' => basename($fichier),
            'file_sha256' => $somme,
            'remote_path' => $distant,
            'encrypted' => $chiffre,
            'exported_at' => $manifeste['exported_at'],
        ];
        $ligne['signature'] = AuditChain::sign(AuditChain::exportFields($ligne));
        DB::table('audit_chain_exports')->insert($ligne);

        $this->purgerLesAnciensFichiersLocaux($dossier);

        return ['from_id' => $depuis, 'to_id' => $jusqua, 'entries' => $entrees, 'file' => $fichier, 'encrypted' => $chiffre, 'remote' => $distant];
    }

    private function ecrire(string $fichier, int $depuis, int $jusqua): int
    {
        $gz = gzopen($fichier, 'wb9');

        if ($gz === false) {
            throw new RuntimeException("Impossible d'écrire {$fichier}");
        }

        $entrees = 0;

        DB::table('audit_logs')
            ->whereBetween('id', [$depuis, $jusqua])
            ->whereNotNull('hash')
            ->orderBy('id')
            ->chunkById(1000, function ($lignes) use ($gz, &$entrees) {
                foreach ($lignes as $ligne) {
                    gzwrite($gz, json_encode([
                        'id' => (int) $ligne->id,
                        'user_id' => $ligne->user_id === null ? null : (int) $ligne->user_id,
                        'action' => $ligne->action,
                        'auditable_type' => $ligne->auditable_type,
                        'auditable_id' => $ligne->auditable_id === null ? null : (int) $ligne->auditable_id,
                        'old_values' => $ligne->old_values,
                        'new_values' => $ligne->new_values,
                        'ip_address' => $ligne->ip_address,
                        'user_agent' => $ligne->user_agent,
                        'status' => $ligne->status,
                        'metadata' => $ligne->metadata,
                        'created_at' => $ligne->created_at,
                        'previous_hash' => $ligne->previous_hash,
                        'hash' => $ligne->hash,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
                    $entrees++;
                }
            });

        gzclose($gz);

        return $entrees;
    }

    private function purgerLesAnciensFichiersLocaux(string $dossier): void
    {
        $limite = now()->subDays((int) config('audit.export.local_retention_days', 30))->getTimestamp();

        foreach (glob("{$dossier}/audit-*") ?: [] as $fichier) {
            if (filemtime($fichier) < $limite) {
                @unlink($fichier);
            }
        }
    }
}
