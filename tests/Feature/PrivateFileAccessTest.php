<?php

namespace Tests\Feature;

use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Les fichiers contenant des données personnelles (contrats, pièces d'identité,
 * évaluations, photos) ne doivent être servis qu'après authentification ET
 * vérification de propriété. Auparavant l'URL suffisait, ce qui n'est pas un
 * contrôle d'accès.
 */
class PrivateFileAccessTest extends TestCase
{
    use RefreshDatabase;

    private function documentFor(User $owner): EmployeeDocument
    {
        $employee = Employee::factory()->create(['user_id' => $owner->id]);

        Storage::disk('local')->put('hr/documents/contrat.pdf', 'contenu confidentiel');

        return EmployeeDocument::create([
            'user_id' => $owner->id,
            'employee_id' => $employee->id,
            'type' => 'contract',
            'name' => 'Contrat',
            'file_path' => 'hr/documents/contrat.pdf',
            'original_name' => 'contrat.pdf',
        ]);
    }

    public function test_owner_can_download_the_document(): void
    {
        $owner = User::factory()->create();
        $document = $this->documentFor($owner);

        $this->actingAs($owner)
            ->get(route('files.employee-document', $document->id))
            ->assertOk();
    }

    public function test_another_company_cannot_download_the_document(): void
    {
        $owner = User::factory()->create();
        $document = $this->documentFor($owner);

        // Un autre utilisateur authentifié ne doit rien obtenir, même avec l'id exact.
        $this->actingAs(User::factory()->create())
            ->get(route('files.employee-document', $document->id))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_the_document(): void
    {
        $owner = User::factory()->create();
        $document = $this->documentFor($owner);

        $this->get(route('files.employee-document', $document->id))
            ->assertRedirect(); // renvoyé vers la connexion
    }

    public function test_employee_can_download_their_own_document_from_the_portal(): void
    {
        $employer = User::factory()->create();
        $portalAccount = User::factory()->create();

        $employee = Employee::factory()->create([
            'user_id' => $employer->id,
            'account_id' => $portalAccount->id,
        ]);

        Storage::disk('local')->put('hr/documents/fiche.pdf', 'contenu');

        $document = EmployeeDocument::create([
            'user_id' => $employer->id,
            'employee_id' => $employee->id,
            'type' => 'contract',
            'name' => 'Fiche',
            'file_path' => 'hr/documents/fiche.pdf',
            'original_name' => 'fiche.pdf',
        ]);

        // Le salarié n'est pas propriétaire de la fiche, mais c'est SON document.
        $this->actingAs($portalAccount)
            ->get(route('files.employee-document', $document->id))
            ->assertOk();
    }

    public function test_missing_file_returns_404_not_a_server_error(): void
    {
        $owner = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $owner->id]);

        $document = EmployeeDocument::create([
            'user_id' => $owner->id,
            'employee_id' => $employee->id,
            'type' => 'contract',
            'name' => 'Disparu',
            'file_path' => 'hr/documents/inexistant.pdf',
            'original_name' => 'inexistant.pdf',
        ]);

        $this->actingAs($owner)
            ->get(route('files.employee-document', $document->id))
            ->assertNotFound();
    }
}
