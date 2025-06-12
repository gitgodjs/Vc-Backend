<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailCuentaReportada extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;

    public function __construct($correo)
    {
        $this->correo = $correo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
            subject: 'Tu cuenta ha sido reportada',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cuentaReportada',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
