<?php

namespace Tests\Feature\Security;

use App\Models\Expense;
use App\Models\HR\Employee;
use App\Models\HR\ExpenseReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HIGH-1 — suppression inter-tenant des justificatifs de notes de frais.
 * HIGH-6 — justificatifs de DÉPENSES servis depuis le disque public, énumérables.
 */
class ExpenseReceiptIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function proUser(): User
    {
        // Essai générique = fonctionnalités Pro (dont hr_module).
        return User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function reportWithReceipt(User $owner): array
    {
        $employee = Employee::factory()->create(['user_id' => $owner->id]);
        $category = \App\Models\HR\ExpenseCategory::create([
            'user_id' => $owner->id,
            'name' => 'Déplacements',
        ]);

        $report = ExpenseReport::create([
            'user_id' => $owner->id,
            'employee_id' => $employee->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'vendor' => 'Fournisseur',
            'amount_ht' => 100,
            'vat_rate' => 17,
            'status' => 'pending',
        ]);

        Storage::disk('local')->put('hr/expense-receipts/recu.pdf', 'justificatif confidentiel');
        $receipt = $report->receipts()->create([
            'file_path' => 'hr/expense-receipts/recu.pdf',
            'original_name' => 'recu.pdf',
        ]);

        return [$report, $receipt];
    }

    /** HIGH-1 : un autre tenant ne peut pas supprimer un reçu en croisant les id. */
    public function test_another_tenant_cannot_delete_a_receipt_via_id_crossing(): void
    {
        $a = $this->proUser();
        [$reportA, $receiptA] = $this->reportWithReceipt($a);

        $b = $this->proUser();
        [$reportB] = $this->reportWithReceipt($b);

        // B utilise SA note + le reçu de A.
        $this->actingAs($b)
            ->delete(route('hr.expenses.receipts.destroy', [
                'expenseReport' => $reportB->id,
                'expenseReceipt' => $receiptA->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseHas('expense_receipts', ['id' => $receiptA->id]);
        $this->assertTrue(Storage::disk('local')->exists('hr/expense-receipts/recu.pdf'));
    }

    /** HIGH-6 : nouveau justificatif de dépense stocké sur le disque PRIVÉ. */
    public function test_new_expense_attachment_is_stored_on_the_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $user = $this->proUser();
        $expense = Expense::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur',
            'category' => array_key_first(Expense::getCategories()),
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);

        $expense->addMedia(UploadedFile::fake()->image('facture.jpg'))
            ->toMediaCollection('attachments');

        $media = $expense->fresh()->getFirstMedia('attachments');
        $this->assertSame('local', $media->disk, 'Le justificatif doit aller sur le disque privé');
    }

    /** HIGH-6 : l'URL exposée est une route authentifiée, pas /storage public. */
    public function test_attachment_url_is_an_authenticated_route(): void
    {
        Storage::fake('local');

        $user = $this->proUser();
        $expense = Expense::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur',
            'category' => array_key_first(Expense::getCategories()),
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);
        $expense->addMedia(UploadedFile::fake()->image('facture.jpg'))
            ->toMediaCollection('attachments');

        $url = $expense->fresh()->attachment_url;
        $this->assertStringContainsString('/fichiers/justificatif-depense/', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    /** HIGH-6 : un autre tenant ne peut pas télécharger le justificatif. */
    public function test_another_tenant_cannot_download_the_expense_attachment(): void
    {
        Storage::fake('local');

        $owner = $this->proUser();
        $expense = Expense::create([
            'user_id' => $owner->id,
            'date' => now()->toDateString(),
            'provider_name' => 'Fournisseur',
            'category' => array_key_first(Expense::getCategories()),
            'amount_ht' => 100,
            'vat_rate' => 17,
        ]);
        $expense->addMedia(UploadedFile::fake()->image('facture.jpg'))
            ->toMediaCollection('attachments');

        $this->actingAs($this->proUser())
            ->get(route('files.expense-attachment', $expense->id))
            ->assertNotFound();
    }
}
