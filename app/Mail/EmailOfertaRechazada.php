<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailOfertaRechazada extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;
    public $usuario;
    public $prenda;

    public function __construct($correo, $usuario, $prenda)
    {
        $this->correo = $correo;
        $this->usuario = $usuario;
        $this->prenda = $prenda;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
            subject: 'Tu oferta fue rechazada',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ofertaRechazada',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
