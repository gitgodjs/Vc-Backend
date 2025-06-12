<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nueva publicación</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: auto; padding: 20px; background-color: #fff; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        p { color: #666; font-size: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡Gracias por publicar con nosotros, {{ $correo }}!</h1>
        <p>Publicaste <strong>"{{ $prenda }}"</strong>. Agradecemos tu confianza en <span style="color:#864a00;">Vintage Clothes</span>, ya está visible para todos los usuarios.</p>
    </div>
</body>
</html>
