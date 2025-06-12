<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class EmailReportasteCuenta extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;
    public $nombre_reportado;

    public function __construct($correo, $nombre_reportado)
    {
        $this->correo = $correo;
        $this->nombre_reportado = $nombre_reportado;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
            subject: 'Reportaste una cuenta',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reportasteCuenta',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
