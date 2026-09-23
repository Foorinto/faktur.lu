<?php

namespace App\Security;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * La chaîne d'empreintes du journal d'audit (FEAT-125).
 *
 * Chaque entrée reçoit une empreinte SHA-256 de son contenu et de l'empreinte
 * de l'entrée précédente. Modifier ou supprimer une entrée après coup casse la
 * chaîne, et verify() dit où. Le scellement est asynchrone (audit:seal, chaque
 * minute) plutôt qu'à l'insertion : pas de verrou ni de contention sur le
 * chemin des requêtes, un seul scelleur, et la chaîne suit toujours l'ordre
 * des id. Le prix : une entrée reste non scellée jusqu'à une minute.
 *
 * Ce que la chaîne ne voit pas seule : la suppression des dernières entrées
 * (la tête recule sans rien casser). C'est le rôle de l'export hors site, dont
 * chaque manifeste fige la tête du moment ; verify() le contrôle aussi.
 *
 * Après une purge de rétention, une ancre garde l'empreinte de la dernière
 * entrée supprimée : la chaîne repart de là.
 *
 * La forme canonique reste reconstructible par un tiers depuis l'export :
 * les colonnes dans un ordre fixe, telles que la base les rend (les JSON
 * restent des chaînes), les entiers en entiers.
 */
class AuditChain
{
    public const ALGORITHM = 'sha256';

    public static function canonical(object $row, ?string $previousHash): string
    {
        return json_encode([
            'id' => (int) $row->id,
            'user_id' => $row->user_id === null ? null : (int) $row->user_id,
            'action' => (string) $row->action,
            'auditable_type' => $row->auditable_type,
            'auditable_id' => $row->auditable_id === null ? null : (int) $row->auditable_id,
            'old_values' => $row->old_values,
            'new_values' => $row->new_values,
            'ip_address' => $row->ip_address,
            'user_agent' => $row->user_agent,
            'status' => (string) $row->status,
            'metadata' => $row->metadata,
            'created_at' => (string) $row->created_at,
            'previous_hash' => $previousHash,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function hashRow(object $row, ?string $previousHash): string
    {
        return hash(self::ALGORITHM, self::canonical($row, $previousHash));
    }

    /**
     * Signature d'un jeu de champs avec la clé de l'application : ce qui rend
     * les manifestes d'export et les ancres infalsifiables sans la clé.
     */
    public static function sign(array $fields): string
    {
        ksort($fields);

        return hash_hmac(self::ALGORITHM, json_encode($fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), (string) config('app.key'));
    }

    /**
     * Les champs signés d'une trace d'export, typés de la même façon à
     * l'écriture et à la relecture : MariaDB rend les entiers en chaînes,
     * SQLite en entiers, et la signature ne doit dépendre ni de l'un ni de
     * l'autre.
     */
    public static function exportFields(object|array $row): array
    {
        $row = (array) $row;

        return [
            'from_id' => (int) $row['from_id'],
            'to_id' => (int) $row['to_id'],
            'entries' => (int) $row['entries'],
            'head_hash' => (string) $row['head_hash'],
            'file_name' => (string) $row['file_name'],
            'file_sha256' => (string) $row['file_sha256'],
            'remote_path' => $row['remote_path'] === null || $row['remote_path'] === '' ? null : (string) $row['remote_path'],
            'encrypted' => (int) (bool) $row['encrypted'],
            'exported_at' => (string) $row['exported_at'],
        ];
    }

    /** Scelle les entrées qui ne le sont pas encore, dans l'ordre des id. */
    public function sealPending(int $batch = 1000): int
    {
        $precedente = $this->headHash();
        $scellees = 0;

        DB::table('audit_logs')
            ->whereNull('hash')
            ->orderBy('id')
            ->chunkById($batch, function ($lignes) use (&$precedente, &$scellees) {
                foreach ($lignes as $ligne) {
                    $empreinte = self::hashRow($ligne, $precedente);

                    DB::table('audit_logs')->where('id', $ligne->id)->update([
                        'previous_hash' => $precedente,
                        'hash' => $empreinte,
                    ]);

                    $precedente = $empreinte;
                    $scellees++;
                }
            });

        return $scellees;
    }

    /** La dernière entrée scellée (id et empreinte), ou null. */
    public function head(): ?object
    {
        return DB::table('audit_logs')->whereNotNull('hash')->orderByDesc('id')->first(['id', 'hash', 'created_at']);
    }

    /** L'empreinte dont la prochaine entrée doit partir : la tête, sinon l'ancre, sinon rien. */
    public function headHash(): ?string
    {
        return $this->head()?->hash ?? $this->latestAnchor()?->last_pruned_hash;
    }

    public function latestAnchor(): ?object
    {
        return DB::table('audit_chain_anchors')->orderByDesc('id')->first();
    }

    /** Entrées non scellées : combien, et l'âge de la plus ancienne en minutes. */
    public function pending(): array
    {
        $plusAncienne = DB::table('audit_logs')->whereNull('hash')->min('created_at');

        return [
            'count' => DB::table('audit_logs')->whereNull('hash')->count(),
            'oldest_minutes' => $plusAncienne ? (int) now()->diffInMinutes(Carbon::parse($plusAncienne), true) : 0,
        ];
    }

    /**
     * Parcourt toute la chaîne. Une entrée non scellée n'est un défaut que si
     * une entrée scellée la suit : à la fin, ce sont celles de la dernière
     * minute. Contrôle aussi chaque export : l'entrée qu'il désigne comme tête
     * doit exister et porter la même empreinte, sinon la queue a été coupée.
     *
     * @return array{ok: bool, checked: int, pending: int, break_id: ?int, reason: ?string, head_id: ?int, head_hash: ?string}
     */
    public function verify(int $batch = 1000): array
    {
        $ancre = $this->latestAnchor();
        $precedente = $ancre?->last_pruned_hash;
        $attenduApres = $ancre?->last_pruned_id;

        $resultat = ['ok' => true, 'checked' => 0, 'pending' => 0, 'break_id' => null, 'reason' => null, 'head_id' => null, 'head_hash' => null];
        $enAttente = 0;

        $casser = function (int $id, string $motif) use (&$resultat): bool {
            $resultat['ok'] = false;
            $resultat['break_id'] = $id;
            $resultat['reason'] = $motif;

            return false; // arrête chunkById
        };

        DB::table('audit_logs')
            ->orderBy('id')
            ->chunkById($batch, function ($lignes) use (&$precedente, &$attenduApres, &$resultat, &$enAttente, $casser) {
                foreach ($lignes as $ligne) {
                    if ($attenduApres !== null && $ligne->id <= $attenduApres) {
                        return $casser($ligne->id, 'entrée antérieure à l\'ancre de purge');
                    }

                    if ($ligne->hash === null) {
                        $enAttente++;

                        continue;
                    }

                    if ($enAttente > 0) {
                        return $casser($ligne->id, 'entrée scellée après une entrée non scellée');
                    }

                    if ($ligne->previous_hash !== $precedente) {
                        return $casser($ligne->id, 'chaîne rompue : l\'entrée précédente manque ou a changé');
                    }

                    if (self::hashRow($ligne, $precedente) !== $ligne->hash) {
                        return $casser($ligne->id, 'contenu modifié après scellement');
                    }

                    $precedente = $ligne->hash;
                    $resultat['checked']++;
                    $resultat['head_id'] = $ligne->id;
                    $resultat['head_hash'] = $ligne->hash;
                }
            });

        $resultat['pending'] = $enAttente;

        if ($resultat['ok']) {
            $this->verifyExports($resultat, $ancre);
        }

        return $resultat;
    }

    private function verifyExports(array &$resultat, ?object $ancre): void
    {
        foreach (DB::table('audit_chain_exports')->orderBy('id')->get() as $export) {
            if (! hash_equals(self::sign(self::exportFields($export)), (string) $export->signature)) {
                $resultat['ok'] = false;
                $resultat['break_id'] = (int) $export->to_id;
                $resultat['reason'] = "export #{$export->id} : signature invalide, la trace d'export a été modifiée";

                return;
            }

            if ($ancre !== null && $export->to_id <= $ancre->last_pruned_id) {
                continue; // purgé depuis, l'ancre en répond
            }

            $empreinte = DB::table('audit_logs')->where('id', $export->to_id)->value('hash');

            if ($empreinte !== $export->head_hash) {
                $resultat['ok'] = false;
                $resultat['break_id'] = (int) $export->to_id;
                $resultat['reason'] = "export #{$export->id} : l'entrée #{$export->to_id} exportée comme tête manque ou a changé";

                return;
            }
        }
    }

    /**
     * Supprime les entrées plus anciennes que la rétention, à condition
     * qu'elles soient scellées et exportées, et pose une ancre. On coupe un
     * préfixe contigu d'id : la chaîne repart de l'ancre.
     *
     * @return array{deleted: int, anchor_id: ?int}
     */
    public function prune(int $retentionDays): array
    {
        $limite = now()->subDays($retentionDays)->format('Y-m-d H:i:s');

        $premiereRecente = DB::table('audit_logs')->where('created_at', '>=', $limite)->min('id');
        $coupe = $premiereRecente ? (int) $premiereRecente - 1 : (int) DB::table('audit_logs')->max('id');
        $exporteJusqua = (int) DB::table('audit_chain_exports')->max('to_id');
        $coupe = min($coupe, $exporteJusqua);

        if ($coupe <= 0) {
            return ['deleted' => 0, 'anchor_id' => null];
        }

        $derniere = DB::table('audit_logs')->where('id', '<=', $coupe)->orderByDesc('id')->first(['id', 'hash']);

        if ($derniere === null || $derniere->hash === null) {
            return ['deleted' => 0, 'anchor_id' => null];
        }

        return DB::transaction(function () use ($coupe, $derniere) {
            $supprimees = DB::table('audit_logs')->where('id', '<=', $coupe)->delete();

            $ancre = DB::table('audit_chain_anchors')->insertGetId([
                'last_pruned_id' => $derniere->id,
                'last_pruned_hash' => $derniere->hash,
                'pruned_count' => $supprimees,
                'pruned_at' => now(),
            ]);

            return ['deleted' => $supprimees, 'anchor_id' => $ancre];
        });
    }
}
