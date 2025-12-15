<?php

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado</title>

    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet"
    >

    <style>
        body {
            background: #0d1117;
            font-family: 'Segoe UI', sans-serif;
        }

        .card-error {
            max-width: 450px;
            margin: 100px auto;
            padding: 25px;
            border-radius: 15px;
            background: #161b22;
            border: 1px solid #dc3545;
            color: white;
            box-shadow: 0 0 10px #ff4d4d55;
        }

        .btn-custom {
            background: #dc3545;
            border: none;
            font-weight: bold;
        }

        .btn-custom:hover {
            background: #b52a37;
        }
    </style>

</head>
<body>

    <div class="card-error text-center">
        <h2 class="text-danger">❌ Acceso Denegado</h2>
        <p>No puedes acceder al panel del administrador sin iniciar sesión.</p>

        <a href="../index.php" class="btn btn-custom mt-3">Ir al Login</a>
    </div>

</body>
</html>
