<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Mail\ReminderMail;
use App\Models\EmailSettings;
use App\Models\Invoice;
use App\Models\InvoiceEmail;
use App\Services\EmailProviderService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvoiceEmailController extends Controller
{
    public function __construct(
        protected EmailProviderService $emailProviderService
    ) {}
    /**
     * Send an invoice by email.
     */
    public function send(Request $request, Invoice $invoice)
    {
        // Ensure the invoice belongs to the current user
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        // Only send finalized invoices
        if ($invoice->isDraft()) {
            return back()->withErrors(['invoice' => __('app.invoice_email_flash.error_draft')]);
        }

        $validated = $request->validate([
            'recipient_email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string|max:5000',
            'send_copy_to_self' => 'boolean',
        ]);

        $recipientEmail = $validated['recipient_email'];
        $subject = $validated['subject'];
        $customMessage = $validated['message'] ?? null;
        $sendCopyToSelf = $validated['send_copy_to_self'] ?? false;

        try {
            // Get the mailer for this user (uses their configured provider)
            $mailer = $this->emailProviderService->getMailerForUser($request->user());

            // Send the email
            $mail = new InvoiceMail($invoice, $customMessage);
            $mail->subject($subject);

            $mailer->to($recipientEmail)->send($mail);

            // Send copy to self if requested
            if ($sendCopyToSelf) {
                $mailer->to($request->user()->email)->send($mail);
            }

            // Determine email type
            $type = InvoiceEmail::TYPE_MANUAL;
            if (!$invoice->emails()->exists()) {
                $type = InvoiceEmail::TYPE_INITIAL;
            }

            // Record the email
            $invoiceEmail = $invoice->emails()->create([
                'type' => $type,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'body' => $customMessage,
                'status' => InvoiceEmail::STATUS_SENT,
                'sent_at' => now(),
            ]);

            // Mark invoice as sent if it was finalized
            if ($invoice->status === Invoice::STATUS_FINALIZED) {
                $invoice->update([
                    'status' => Invoice::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            }

            // Audit log
            if (method_exists($invoice, 'logAudit')) {
                $invoice->logAudit('invoice_email_sent', [
                    'email_id' => $invoiceEmail->id,
                    'recipient' => $recipientEmail,
                    'type' => $type,
                ]);
            }

            return back()->with('success', __('app.invoice_email_flash.sent'));
        } catch (\Exception $e) {
            // Record the failed email
            $invoice->emails()->create([
                'type' => InvoiceEmail::TYPE_MANUAL,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'body' => $customMessage,
                'status' => InvoiceEmail::STATUS_FAILED,
                'sent_at' => now(),
            ]);

            report($e);

            return back()->withErrors(['email' => __('app.invoice_email_flash.error_email_send', ['error' => $e->getMessage()])]);
        }
    }

    /**
     * Get email history for an invoice.
     */
    public function history(Request $request, Invoice $invoice)
    {
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'emails' => $invoice->emails()->get()->map(fn($email) => [
                'id' => $email->id,
                'type' => $email->type,
                'type_label' => $email->type_label,
                'recipient_email' => $email->recipient_email,
                'subject' => $email->subject,
                'status' => $email->status,
                'sent_at' => $email->sent_at?->format('d/m/Y H:i'),
                'is_reminder' => $email->isReminder(),
            ]),
        ]);
    }

    /**
     * Show email settings page.
     */
    public function settings(Request $request)
    {
        $settings = EmailSettings::getOrCreate($request->user());

        return Inertia::render('Settings/EmailSettings', [
            'settings' => [
                'id' => $settings->id,
                'default_message' => $settings->default_message,
                'signature' => $settings->signature,
                'send_copy_to_self' => $settings->send_copy_to_self,
                'reminders_enabled' => $settings->reminders_enabled,
                'reminder_levels' => $settings->reminder_levels ?? EmailSettings::defaultReminderLevels(),
            ],
        ]);
    }

    /**
     * Update email settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'default_message' => 'nullable|string|max:5000',
            'signature' => 'nullable|string|max:1000',
            'send_copy_to_self' => 'boolean',
            'reminders_enabled' => 'boolean',
            'reminder_levels' => 'nullable|array',
            'reminder_levels.*.enabled' => 'boolean',
            'reminder_levels.*.days_after_due' => 'integer|min:1|max:365',
            'reminder_levels.*.subject' => 'nullable|string|max:255',
            'reminder_levels.*.body' => 'nullable|string|max:5000',
        ]);

        $settings = EmailSettings::getOrCreate($request->user());
        $settings->update($validated);

        return back()->with('success', __('app.invoice_email_flash.settings_saved'));
    }

    /**
     * Send a payment reminder for an invoice.
     */
    public function sendReminder(Request $request, Invoice $invoice)
    {
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        // Only send reminders for sent/unpaid invoices
        if (!in_array($invoice->status, [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT])) {
            return back()->withErrors(['invoice' => __('app.invoice_email_flash.error_reminder_invalid_status')]);
        }

        // Check if invoice is excluded from reminders
        if ($invoice->exclude_from_reminders) {
            return back()->withErrors(['invoice' => __('app.invoice_email_flash.error_reminder_excluded')]);
        }

        $validated = $request->validate([
            'level' => 'required|integer|min:1|max:3',
            'recipient_email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $level = $validated['level'];
        $recipientEmail = $validated['recipient_email'];
        $subject = $validated['subject'];
        $message = $validated['message'];

        try {
            // Get the mailer for this user (uses their configured provider)
            $mailer = $this->emailProviderService->getMailerForUser($request->user());

            // Send the reminder email
            $mail = new ReminderMail($invoice, $level, $message);
            $mail->subject($subject);

            $mailer->to($recipientEmail)->send($mail);

            // Determine reminder type
            $type = match ($level) {
                1 => InvoiceEmail::TYPE_REMINDER_1,
                2 => InvoiceEmail::TYPE_REMINDER_2,
                3 => InvoiceEmail::TYPE_REMINDER_3,
                default => InvoiceEmail::TYPE_REMINDER_1,
            };

            // Record the email
            $invoiceEmail = $invoice->emails()->create([
                'type' => $type,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'body' => $message,
                'status' => InvoiceEmail::STATUS_SENT,
                'sent_at' => now(),
            ]);

            // Audit log
            if (method_exists($invoice, 'logAudit')) {
                $invoice->logAudit('invoice_reminder_sent', [
                    'email_id' => $invoiceEmail->id,
                    'recipient' => $recipientEmail,
                    'level' => $level,
                ]);
            }

            return back()->with('success', __('app.invoice_email_flash.reminder_sent'));
        } catch (\Exception $e) {
            $invoice->emails()->create([
                'type' => match ($level) {
                    1 => InvoiceEmail::TYPE_REMINDER_1,
                    2 => InvoiceEmail::TYPE_REMINDER_2,
                    3 => InvoiceEmail::TYPE_REMINDER_3,
                    default => InvoiceEmail::TYPE_REMINDER_1,
                },
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'body' => $message,
                'status' => InvoiceEmail::STATUS_FAILED,
                'sent_at' => now(),
            ]);

            report($e);

            return back()->withErrors(['email' => __('app.invoice_email_flash.error_reminder_send')]);
        }
    }

    /**
     * Toggle exclude from reminders for an invoice.
     */
    public function toggleExcludeFromReminders(Request $request, Invoice $invoice)
    {
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        // Since we can't directly update exclude_from_reminders on finalized invoices
        // We need to bypass the immutability check - this is allowed for reminder settings
        // Update using query builder to bypass model events
        Invoice::where('id', $invoice->id)->update([
            'exclude_from_reminders' => !$invoice->exclude_from_reminders,
        ]);

        $invoice->refresh();

        return back()->with('success', $invoice->exclude_from_reminders
            ? __('app.invoice_email_flash.reminder_excluded')
            : __('app.invoice_email_flash.reminder_included')
        );
    }
}
