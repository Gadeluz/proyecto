<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION["admin"])) { 
    header("Location: index.php");
    exit; 
}

include "bd.php";

$mensaje = "";

$tarifaAuto = 0;
$tarifaMoto = 0;

$resTar = mysqli_query($conn, "SELECT precio, precio_por_hora FROM tarifas WHERE id = 1");
if ($resTar && mysqli_num_rows($resTar) > 0) {
    $rowTar = mysqli_fetch_assoc($resTar);
    $tarifaAuto = $rowTar["precio"];
    $tarifaMoto = $rowTar["precio_por_hora"];
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nueva = $_POST["precio"];
    $tipo  = $_POST["tipo_auto"] ?? "";

    if ($tipo === "Auto") {
      
        $sqlUpd = "UPDATE tarifas SET precio = '$nueva' WHERE id = 1";
        mysqli_query($conn, $sqlUpd);
        $mensaje = "Tarifa de Auto actualizada correctamente.";
    } elseif ($tipo === "Moto") {
       
        $sqlUpd = "UPDATE tarifas SET precio_por_hora = '$nueva' WHERE id = 1";
        mysqli_query($conn, $sqlUpd);
        $mensaje = "Tarifa de Moto actualizada correctamente.";
    } else {
        $mensaje = "Debes seleccionar un tipo de vehículo.";
    }

    $resTar = mysqli_query($conn, "SELECT precio, precio_por_hora FROM tarifas WHERE id = 1");
    if ($resTar && mysqli_num_rows($resTar) > 0) {
        $rowTar = mysqli_fetch_assoc($resTar);
        $tarifaAuto = $rowTar["precio"];
        $tarifaMoto = $rowTar["precio_por_hora"];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Actualizar Tarifas</title>
<style>
    body { font-family: 'Segoe UI'; background:#eef1f7; padding:20px; }
    .card { background:white; padding:25px; max-width:450px; border-radius:16px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
    input, select { padding:10px; width:100%; margin-top:10px; border-radius:10px; border:1px solid #ccc; }
    button { padding:10px; background:#4dabf7; color:white; border:none; border-radius:10px; margin-top:10px; cursor:pointer; }
    button:hover { background:#1c7ed6; }
    .msg { background:#d4edda; padding:10px; border-radius:8px; color:#155724; margin-top:10px; }
    .btn-back { display:inline-block; margin-bottom:15px; padding:8px 14px; background:#6c757d; color:white; text-decoration:none; border-radius:8px; }
    .btn-back:hover { background:#495057; }
    .label-strong { font-weight:600; margin-top:10px; display:block; }
</style>
</head>
<body>

<h1>Actualizar Tarifas</h1>
<a href="admin.php" class="btn-back">⬅ Regresar</a>

<div class="card">

    <p><strong>Tarifas actuales:</strong></p>
    <p> Auto: <strong>$<?= number_format($tarifaAuto, 2) ?></strong> por hora</p>
    <p> Moto: <strong>$<?= number_format($tarifaMoto, 2) ?></strong> por hora</p>
    <hr>

    <form method="POST">
        <label class="label-strong">Tipo de vehículo a actualizar:</label>
        <select name="tipo_auto" required>
            <option value="">Seleccionar...</option>
            <option value="Auto">Auto</option>
            <option value="Moto">Moto</option>
        </select>

        <label class="label-strong">Nuevo precio por hora:</label>
        <input type="number" step="0.01" name="precio" required>

        <button type="submit">Actualizar</button>
    </form>

    <?php if ($mensaje): ?>
    <div class="msg"><?= $mensaje ?></div>
    <?php endif; ?>

</div>

</body>
</html>
