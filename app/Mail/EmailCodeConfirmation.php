<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class EmailCodeConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $correo;
    public $codigo;

    /**
     * Crear una nueva instancia de mensaje.
     *
     * @param  string  $correo
     * @param  string  $codigo
     * @return void
     */
    public function __construct($correo, $codigo)
    {
        $this->correo = $correo;
        $this->codigo = $codigo;
    }

    /**
     * Obtener el sobre del mensaje (envelope).
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('vintagec2025@gmail.com', 'Vintage Clothes'),
            subject: 'Verificación mediante código',
        );
    }

    /**
     * Obtener la definición del contenido del mensaje.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.codigoVerificacion',  // Vista del correo
        );
    }

    /**
     * Obtener los archivos adjuntos para el mensaje.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
