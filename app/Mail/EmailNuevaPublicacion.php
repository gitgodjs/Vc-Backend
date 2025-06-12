<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailNuevaPublicacion extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;
    public $prenda;

    public function __construct($correo, $prenda)
    {
        $this->correo = $correo;
        $this->prenda = $prenda;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
            subject: '¡Gracias por publicar con nosotros!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nuevaPublicacion',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
