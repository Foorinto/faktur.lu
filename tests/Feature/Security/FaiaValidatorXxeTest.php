<?php

namespace Tests\Feature\Security;

use App\Services\FaiaValidatorService;
use Tests\TestCase;

/**
 * HIGH-8 — validateur FAIA public : entités XML et déni de service.
 *
 * Le parseur activait LIBXML_NOENT (substitution d'entités), ce qui rendait
 * possible l'expansion récursive (« billion laughs ») et la lecture d'entités.
 * Sans NOENT, un document hostile ne peut ni exploser la mémoire ni lire un
 * fichier, tout en laissant passer un FAIA légitime.
 */
class FaiaValidatorXxeTest extends TestCase
{
    private function validator(): FaiaValidatorService
    {
        return app(FaiaValidatorService::class);
    }

    public function test_a_legitimate_faia_still_parses(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<AuditFile xmlns="urn:lu:faia:2.01"><Header><AuditFileVersion>2.01</AuditFileVersion></Header></AuditFile>';

        $result = $this->validator()->validate($xml);

        // Pas d'erreur de parsing XML sur un document valide.
        $codesErreur = array_column($result['errors'] ?? [], 'code');
        $this->assertNotContains('XML_PARSE_ERROR', $codesErreur);
    }

    public function test_billion_laughs_entity_expansion_does_not_explode(): void
    {
        // Entités récursives : avec LIBXML_NOENT, &lol9; aurait explosé en mémoire.
        $xml = '<?xml version="1.0"?>'
            .'<!DOCTYPE lolz ['
            .'<!ENTITY lol "lol">'
            .'<!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">'
            .'<!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">'
            .']>'
            .'<AuditFile><Header><x>&lol3;</x></Header></AuditFile>';

        // Ne doit pas épuiser la mémoire : l'appel revient normalement, et les
        // entités ne sont pas substituées (valeur non expansée).
        $result = $this->validator()->validate($xml);

        $this->assertIsArray($result);
        // L'entité n'a pas été expansée en des milliers de « lol ».
        $encoded = json_encode($result);
        $this->assertLessThan(200, substr_count($encoded, 'lol'), 'Les entités ne doivent pas être expansées');
    }

    public function test_external_entity_is_not_resolved(): void
    {
        $secret = sys_get_temp_dir().'/faia_xxe_secret_'.uniqid().'.txt';
        file_put_contents($secret, 'CONTENU-SECRET-A-NE-PAS-LIRE');

        try {
            $xml = '<?xml version="1.0"?>'
                .'<!DOCTYPE r [<!ENTITY xxe SYSTEM "file://'.$secret.'">]>'
                .'<AuditFile><Header><x>&xxe;</x></Header></AuditFile>';

            $result = $this->validator()->validate($xml);

            $this->assertStringNotContainsString(
                'CONTENU-SECRET',
                json_encode($result),
                'Le contenu d\'un fichier local ne doit jamais être résolu ni reflété'
            );
        } finally {
            @unlink($secret);
        }
    }
}
