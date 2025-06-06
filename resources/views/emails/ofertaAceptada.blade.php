<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>¡Tu oferta fue aceptada!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333333;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            color: #666666;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .highlight {
            color: #864a00;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            background-color: #F06C15;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>¡Buenas noticias, {{ $correo }}!</h1>
        <p><strong>{{ $usuario }}</strong> aceptó tu oferta por <span class="highlight">"{{ $prenda }}"</span>.</p>
        <p>Ingresá a tu cuenta para coordinar la entrega lo antes posible.</p>
    </div>
</body>
</html>
