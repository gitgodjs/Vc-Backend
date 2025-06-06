<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailVerificacionRechazada extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;

    /**
     * Create a new message instance.
     */
    public function __construct($correo)
    {
        $this->correo = $correo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
            subject: 'Verificación rechazada',
        );
    }

    /**
     * Get the content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verificacionRechazada',
        );
    }

    /**
     * Get attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
