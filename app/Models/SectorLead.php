<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Manifestation d'intérêt laissée sur une page sectorielle.
 *
 * Sert à décider quel pack métier mérite d'être construit : le pack santé
 * demanderait treize jours, dans un secteur où nous n'avons aucun contact.
 * Quelques réponses écrites de la main d'un infirmier valent mieux qu'une
 * intuition.
 *
 * La colonne `email` reste nullable en base bien que le formulaire l'exige : une
 * contrainte de validation se change sans migration, et rien ne dit qu'un futur
 * canal de recueil aura la même règle.
 *
 * ⚠️ Aucun scope de cloisonnement : ces enregistrements n'appartiennent à
 * personne — ils sont déposés par des visiteurs anonymes et lus uniquement
 * depuis l'administration.
 */
class SectorLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector',
        'email',
        'message',
        'locale',
        'wants_newsletter',
    ];

    protected function casts(): array
    {
        return [
            'wants_newsletter' => 'boolean',
        ];
    }

    /** Libellé du secteur en français, pour l'administration. */
    public function sectorLabel(): string
    {
        return __("app.business_sectors.{$this->sector}.label", [], 'fr');
    }
}
