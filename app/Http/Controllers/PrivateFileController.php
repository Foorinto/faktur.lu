<?php

namespace App\Http\Controllers;

use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\ExpenseReceipt;
use App\Models\SupportAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sert les fichiers contenant des données personnelles (contrats, pièces
 * d'identité, évaluations, justificatifs, photos, pièces jointes support).
 *
 * Ces fichiers vivent sur le disque privé, hors du répertoire web : ils ne sont
 * donc accessibles que par ici, après authentification ET vérification de
 * propriété. Auparavant ils étaient servis directement par le serveur web
 * depuis /storage/... : l'URL constituait le seul contrôle d'accès, ce qui
 * n'en est pas un au sens du RGPD (art. 32) et ne laissait aucune trace d'accès.
 */
class PrivateFileController extends Controller
{
    /** Document RH : contrat, pièce d'identité, pièce jointe d'évaluation. */
    public function employeeDocument(int $document): StreamedResponse
    {
        // Résolution sans le scope BelongsToUser : le salarié qui consulte son
        // propre document via le portail n'est pas le propriétaire de la fiche.
        // La vérification de propriété est faite explicitement juste après.
        $document = EmployeeDocument::withoutGlobalScopes()->findOrFail($document);

        abort_unless($this->ownsEmployeeRecord($document->user_id, $document->employee_id), 403);

        return $this->stream($document->file_path, $document->original_name ?? $document->name);
    }

    /** Justificatif d'une note de frais. */
    public function expenseReceipt(int $receipt): StreamedResponse
    {
        $receipt = ExpenseReceipt::findOrFail($receipt);
        $report = $receipt->expenseReport()->withoutGlobalScopes()->first();
        abort_if($report === null, 404);
        abort_unless($this->ownsEmployeeRecord($report->user_id, $report->employee_id), 403);

        return $this->stream($receipt->file_path, basename((string) $receipt->file_path));
    }

    /**
     * Photo d'un salarié.
     * Visible par l'employeur et par les salariés de la même entreprise :
     * le trombinoscope et l'organigramme affichent les collègues.
     */
    public function employeePhoto(int $employee): StreamedResponse
    {
        $employee = Employee::withoutGlobalScopes()->findOrFail($employee);

        abort_unless($this->belongsToSameCompany((int) $employee->user_id), 403);

        return $this->stream($employee->photo_path, basename((string) $employee->photo_path), inline: true);
    }

    /** Pièce jointe d'un ticket de support. */
    public function supportAttachment(int $attachment): StreamedResponse
    {
        $attachment = SupportAttachment::findOrFail($attachment);
        $ticket = $attachment->message?->ticket;
        abort_if($ticket === null, 404);

        // Le propriétaire du ticket, ou un administrateur de la plateforme : le
        // panneau d'administration affiche déjà le contenu complet des tickets,
        // traiter une demande suppose d'en voir les pièces jointes.
        //
        // Cette tolérance est VOLONTAIREMENT limitée au support. Les fichiers RH
        // (contrats, pièces d'identité, évaluations) restent inaccessibles aux
        // administrateurs : aucune vue d'administration ne les expose, et y
        // donner accès serait une intrusion injustifiée dans les données
        // personnelles des salariés de nos clients.
        $user = auth()->user();
        $isOwner = $user !== null && (int) $ticket->user_id === (int) $user->id;
        $isAdmin = $user !== null && (bool) $user->is_admin;

        abort_unless($isOwner || $isAdmin, 403);

        return $this->stream($attachment->path, $attachment->filename);
    }

    /**
     * L'employeur propriétaire de la fiche, ou le salarié lui-même via le
     * portail (son compte est relié à sa fiche par account_id).
     */
    private function ownsEmployeeRecord(?int $ownerId, ?int $employeeId): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($ownerId !== null && (int) $ownerId === (int) $user->id) {
            return true;
        }

        $linked = $user->linkedEmployee;

        return $linked !== null && $employeeId !== null && (int) $linked->id === (int) $employeeId;
    }

    /** Employeur propriétaire, ou salarié rattaché à cette même entreprise. */
    private function belongsToSameCompany(int $ownerId): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($ownerId === (int) $user->id) {
            return true;
        }

        $linked = $user->linkedEmployee;

        return $linked !== null && (int) $linked->user_id === $ownerId;
    }

    /** Diffuse le fichier depuis le disque privé, ou 404 s'il a disparu. */
    private function stream(?string $path, ?string $downloadName, bool $inline = false): StreamedResponse
    {
        abort_if(blank($path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $name = $downloadName ?: basename($path);

        return $inline
            ? $disk->response($path, $name)
            : $disk->download($path, $name);
    }
}
