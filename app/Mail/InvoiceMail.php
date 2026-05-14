<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice,
        public ?string $customMessage = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        app()->setLocale($this->resolveLocale());

        $key = $this->invoice->is_credit_note ? 'app.mail_subject_credit_note' : 'app.mail_subject_invoice';

        return new Envelope(
            subject: __($key, ['number' => $this->invoice->number]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $template = $this->resolveLocale() === 'pt' ? 'emails.pt.invoice' : 'emails.invoice';

        return new Content(
            markdown: $template,
            with: [
                'invoice' => $this->invoice,
                'seller' => $this->invoice->seller_snapshot,
                'buyer' => $this->invoice->buyer_snapshot,
                'customMessage' => $this->customMessage,
                'isCreditNote' => $this->invoice->is_credit_note,
            ],
        );
    }

    /**
     * Resolve the locale of the email recipient (the client).
     */
    protected function resolveLocale(): string
    {
        return $this->invoice->client?->locale ?? app()->getLocale();
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdfService = app(InvoicePdfService::class);
        $type = $this->invoice->is_credit_note ? 'avoir' : 'facture';
        $filename = "{$type}-{$this->invoice->number}.pdf";

        return [
            Attachment::fromData(
                fn () => $pdfService->getContent($this->invoice),
                $filename
            )->withMime('application/pdf'),
        ];
    }
}
