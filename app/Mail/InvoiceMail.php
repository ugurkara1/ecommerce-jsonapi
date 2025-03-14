<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $pdfPath;

    //Mail sınıfını başltırken invoice ve pdfPath parametrelerini alır

    public function __construct($invoice, $pdfPath)
    {
        $this->invoice = $invoice;
        $this->pdfPath = $pdfPath;
    }

    //Envelope: Mailin başlık (subject) bilgisini tanımlar

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sipariş Faturası #' . $this->invoice->invoice_number,
        );
    }
    // Attachments: Mail ile birlikte ek olarak gönderilecek dosyayı tanımlar

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->invoice->invoice_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }

    /**
     * Build: Mailin içeriğini oluşturur
     * Burada, email için bir view (HTML görünümü) kullanırız
     */
    public function build()
    {
        return $this->view('emails.invoice');
    }
}
