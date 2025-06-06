<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailRecibisteOferta extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;
    public $usuario;
    public $monto;
    public $prenda;

    public function __construct($correo, $usuario, $monto, $prenda)
    {
        $this->correo = $correo;
        $this->usuario = $usuario;
        $this->monto = $monto;
        $this->prenda = $prenda;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
            subject: '¡Tienes una nueva oferta!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recibisteOferta',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
