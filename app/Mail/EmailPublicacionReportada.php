<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailPublicacionReportada extends Mailable
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
            subject: 'Una publicación tuya fue reportada',
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.publicacionReportada',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
