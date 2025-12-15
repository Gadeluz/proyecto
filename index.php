<?php
session_start();

// Si ya había sesión iniciada (por cookie o sesión normal), entra directo
if (isset($_SESSION["admin"])) {
    header("Location: admin.php");
    exit;
}

// Si existe la cookie, reconstruimos la sesión automáticamente
if (isset($_COOKIE["admin_session"])) {
    $_SESSION["admin"] = [
        "usuario" => $_COOKIE["admin_session"],
        "role" => "admin"
    ];

    header("Location: admin.php");
    exit;
}

// Usuario y contraseña quemados
$ADMIN_USER = "admin";
$ADMIN_PASS = "12345";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario  = $_POST["usuario"];
    $password = $_POST["password"];

    if ($usuario === $ADMIN_USER && $password === $ADMIN_PASS) {

        // Crea la sesión
        $_SESSION["admin"] = [
            "usuario" => $usuario,
            "role" => "admin"
        ];

        // Crea cookie que dura 7 días
        setcookie("admin_session", $usuario, time() + (86400 * 7), "/");

        header("Location: admin.php");
        exit;

    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Administrador</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: radial-gradient(circle at top, #001430, #000814 80%);
            color: #b2e4ff;
            font-family: "Segoe UI", sans-serif;
        }

        .login-card {
            background: rgba(0, 25, 60, 0.55);
            border: 2px solid #00bfff;
            border-radius: 22px;
            padding: 35px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 25px #009dffcc;
            backdrop-filter: blur(10px);
        }

        h3 {
            color: #6bd6ff;
            text-shadow: 0 0 10px #00eaff,0 0 20px #00c8ff,0 0 35px #00aaff;
        }

        label {
            color: #a8daff;
            text-shadow: 0 0 4px #00bfff;
            display: block;
            text-align: center;
            font-size: 1.1rem;
        }

        input {
            background: #001a33 !important;
            color: #c4ecff !important;
            border: 1px solid #00cfff !important;
            box-shadow: 0 0 12px #0084ff66 inset;
            border-radius: 10px !important;
        }

        button {
            width: 100%;
            background: linear-gradient(90deg, #009cff, #00e1ff);
            border: none;
            color: #00111f;
            font-weight: bold;
            padding: 12px;
            font-size: 1.1rem;
            border-radius: 12px;
            box-shadow: 0 0 12px #00eaffaa;
            text-shadow: 0 0 6px #ffffffaa;
            transition: 0.2s;
        }

        button:hover {
            box-shadow: 0 0 25px #00eaff;
            transform: scale(1.03);
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="login-card">

            <h3 class="text-center mb-4">Acceso Administrador</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <label>Usuario</label>
                <input type="text" name="usuario" class="form-control mb-3" required>

                <label>Contraseña</label>
                <input type="password" name="password" class="form-control mb-4" required>

                <button type="submit">Ingresar</button>

            </form>
        </div>
    </div>

</body>
</html>
