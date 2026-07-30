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

    public function test_platform_admin_can_download_a_support_attachment(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $ticket = \App\Models\SupportTicket::create([
            'user_id' => $owner->id,
            'reference' => \App\Models\SupportTicket::generateReference(),
            'subject' => 'Question',
            'category' => array_key_first(\App\Models\SupportTicket::CATEGORIES),
            'status' => array_key_first(\App\Models\SupportTicket::STATUSES),
        ]);
        $message = $ticket->messages()->create([
            'sender_type' => User::class,
            'sender_id' => $owner->id,
            'content' => 'Bonjour',
        ]);
        Storage::disk('local')->put('support-attachments/capture.png', 'image');
        $attachment = $message->attachments()->create([
            'filename' => 'capture.png',
            'path' => 'support-attachments/capture.png',
            'mime_type' => 'image/png',
            'size' => 5,
        ]);

        // Traiter un ticket suppose de voir ses pièces jointes.
        $this->actingAs($admin)
            ->get(route('files.support-attachment', $attachment->id))
            ->assertOk();

        // Mais un utilisateur tiers reste refusé.
        $this->actingAs(User::factory()->create())
            ->get(route('files.support-attachment', $attachment->id))
            ->assertForbidden();
    }

    public function test_platform_admin_cannot_download_hr_documents(): void
    {
        $owner = User::factory()->create();
        $document = $this->documentFor($owner);

        // Le statut d'administrateur n'ouvre PAS les données personnelles des
        // salariés de nos clients : aucune vue d'administration ne les expose.
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('files.employee-document', $document->id))
            ->assertForbidden();
    }

    private function receiptFor(User $owner, ?int $employeeId = null): \App\Models\HR\ExpenseReceipt
    {
        $employee = $employeeId
            ? \App\Models\HR\Employee::withoutGlobalScopes()->find($employeeId)
            : \App\Models\HR\Employee::factory()->create(['user_id' => $owner->id]);

        $category = \App\Models\HR\ExpenseCategory::create([
            'user_id' => $owner->id,
            'name' => 'Déplacements',
        ]);

        $report = \App\Models\HR\ExpenseReport::create([
            'user_id' => $owner->id,
            'employee_id' => $employee->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'vendor' => 'Taxi',
            'amount_ht' => 100,
            'vat_rate' => 17,
            'amount_vat' => 17,
            'amount_ttc' => 117,
            'status' => 'pending',
        ]);

        Storage::disk('local')->put('hr/expense-receipts/recu.pdf', 'justificatif');

        return \App\Models\HR\ExpenseReceipt::create([
            'expense_report_id' => $report->id,
            'file_path' => 'hr/expense-receipts/recu.pdf',
            'original_name' => 'recu.pdf',
        ]);
    }

    public function test_expense_receipt_is_owner_only(): void
    {
        $owner = User::factory()->create();
        $receipt = $this->receiptFor($owner);

        $this->actingAs($owner)
            ->get(route('files.expense-receipt', $receipt->id))
            ->assertOk();

        $this->actingAs(User::factory()->create())
            ->get(route('files.expense-receipt', $receipt->id))
            ->assertForbidden();
    }

    public function test_employee_photo_is_visible_to_the_company_but_not_to_outsiders(): void
    {
        $employer = User::factory()->create();
        $colleagueAccount = User::factory()->create();

        Storage::disk('local')->put('hr/photos/portrait.jpg', 'image');
        $employee = Employee::factory()->create([
            'user_id' => $employer->id,
            'photo_path' => 'hr/photos/portrait.jpg',
        ]);
        // Un collègue de la même entreprise : le trombinoscope affiche l'équipe.
        Employee::factory()->create([
            'user_id' => $employer->id,
            'account_id' => $colleagueAccount->id,
        ]);

        $this->actingAs($employer)
            ->get(route('files.employee-photo', $employee->id))
            ->assertOk();

        $this->actingAs($colleagueAccount)
            ->get(route('files.employee-photo', $employee->id))
            ->assertOk();

        // Une autre entreprise n'a rien à voir ici.
        $this->actingAs(User::factory()->create())
            ->get(route('files.employee-photo', $employee->id))
            ->assertForbidden();
    }

    public function test_deleting_a_ticket_removes_its_attachment_files(): void
    {
        $owner = User::factory()->create();

        $ticket = \App\Models\SupportTicket::create([
            'user_id' => $owner->id,
            'reference' => \App\Models\SupportTicket::generateReference(),
            'subject' => 'À supprimer',
            'category' => array_key_first(\App\Models\SupportTicket::CATEGORIES),
            'status' => array_key_first(\App\Models\SupportTicket::STATUSES),
        ]);
        $message = $ticket->messages()->create([
            'sender_type' => User::class,
            'sender_id' => $owner->id,
            'content' => 'Voici le fichier',
        ]);
        Storage::disk('local')->put('support-attachments/a-supprimer.png', 'image');
        $message->attachments()->create([
            'filename' => 'a-supprimer.png',
            'path' => 'support-attachments/a-supprimer.png',
            'mime_type' => 'image/png',
            'size' => 5,
        ]);

        $this->assertTrue(Storage::disk('local')->exists('support-attachments/a-supprimer.png'));

        $ticket->delete();

        // Sans nettoyage, le fichier survivrait sans aucune ligne pour le retrouver.
        $this->assertFalse(
            Storage::disk('local')->exists('support-attachments/a-supprimer.png'),
            'Le fichier doit disparaître avec le ticket.'
        );
    }

    public function test_soft_deleting_an_expense_report_keeps_its_receipt(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $receipt = $this->receiptFor($owner);
        $report = $receipt->expenseReport;

        $report->delete(); // soft delete : la note peut être restaurée

        $this->assertTrue(
            Storage::disk('local')->exists('hr/expense-receipts/recu.pdf'),
            'Un soft delete ne doit pas détruire le justificatif.'
        );

        $report->forceDelete();

        $this->assertFalse(
            Storage::disk('local')->exists('hr/expense-receipts/recu.pdf'),
            'Une suppression définitive doit emporter le justificatif.'
        );
    }
}
