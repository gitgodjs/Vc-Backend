<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailVerificacionSolicitada extends Mailable
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
            subject: 'Solicitud de verificación enviada',
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verificacionSolicitada',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
