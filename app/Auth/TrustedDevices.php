<?php

namespace App\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * « Se souvenir de cet appareil » : le code par e-mail n'est redemandé que sur
 * un navigateur inconnu, ou après trente jours.
 *
 * Le navigateur garde un jeton aléatoire dans un cookie chiffré ; la base n'en
 * garde que l'empreinte. Le jeton est lié au compte : un cookie posé pour un
 * compte ne vaut rien pour un autre. Tout tombe au changement de mot de passe,
 * au gel du compte et à la désactivation du code par e-mail.
 */
class TrustedDevices
{
    public const COOKIE = 'trusted_device';

    public const DAYS = 30;

    public function isTrusted(User $user, Request $request): bool
    {
        [$id, $jeton] = $this->lire($request->cookie(self::COOKIE));

        if ($jeton === null || $id !== (int) $user->getKey()) {
            return false;
        }

        $appareil = TrustedDevice::query()
            ->where('user_id', $user->getKey())
            ->where('token', hash('sha256', $jeton))
            ->first();

        if ($appareil === null || $appareil->expires_at->isPast()) {
            return false;
        }

        $appareil->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    public function remember(User $user, Request $request): void
    {
        $jeton = Str::random(48);

        TrustedDevice::create([
            'user_id' => $user->getKey(),
            'token' => hash('sha256', $jeton),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::DAYS),
        ]);

        // Chemin, domaine et « secure » suivent la configuration de session ;
        // le cookie est chiffré par le middleware comme tous les autres.
        Cookie::queue(cookie(
            self::COOKIE,
            $user->getKey().'|'.$jeton,
            self::DAYS * 24 * 60,
            null,
            null,
            null,
            true,
            false,
            'lax',
        ));
    }

    /** Tout oublier pour ce compte, cookie courant compris s'il est le sien. */
    public function forgetAll(User $user): void
    {
        TrustedDevice::where('user_id', $user->getKey())->delete();

        $requete = request();

        if ($requete !== null) {
            [$id] = $this->lire($requete->cookie(self::COOKIE));

            if ($id === (int) $user->getKey()) {
                Cookie::queue(Cookie::forget(self::COOKIE));
            }
        }
    }

    /** @return array{0: ?int, 1: ?string} */
    private function lire(mixed $cookie): array
    {
        if (! is_string($cookie) || ! str_contains($cookie, '|')) {
            return [null, null];
        }

        [$id, $jeton] = explode('|', $cookie, 2);

        if (! ctype_digit($id) || $jeton === '') {
            return [null, null];
        }

        return [(int) $id, $jeton];
    }
}
