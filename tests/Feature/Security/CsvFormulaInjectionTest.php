<?php

namespace Tests\Feature\Security;

use App\Support\CsvSafe;
use Tests\TestCase;

/**
 * BIL-1 — injection de formules dans les exports CSV/XLSX remis aux comptables.
 *
 * Un nom de client « =HYPERLINK(...) » s'exécutait à l'ouverture du fichier sur
 * le poste du comptable. Les valeurs commençant par = + - @ (ou tab/CR) sont
 * neutralisées par une apostrophe de tête.
 */
class CsvFormulaInjectionTest extends TestCase
{
    public function test_formula_prefixes_are_neutralised(): void
    {
        foreach (['=1+1', '+1', '-1', '@SUM(A1)', "\tx", "\rx"] as $dangereux) {
            $this->assertSame("'".$dangereux, CsvSafe::field($dangereux), "Doit préfixer : {$dangereux}");
        }
    }

    public function test_legitimate_values_are_untouched(): void
    {
        foreach (['ACME SARL', 'Société Générale', 'F-2026-001', 'Achats de marchandises', ''] as $ok) {
            $this->assertSame($ok, CsvSafe::field($ok), "Ne doit pas toucher : {$ok}");
        }
    }

    public function test_generic_csv_formatter_neutralises_a_malicious_client_name(): void
    {
        $formatter = new \App\Services\Accounting\GenericCsvFormatter();
        $method = new \ReflectionMethod($formatter, 'escapeCsvField');
        $method->setAccessible(true);

        $sortie = $method->invoke($formatter, '=cmd|calc');
        $this->assertStringStartsWith("'", $sortie, 'Le formateur CSV doit neutraliser la formule');
    }
}
