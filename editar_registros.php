<?php
session_start();
if (!isset($_SESSION["admin"])) { header("Location: ../index.php"); exit; }
include "../bd.php";

$mensaje="";

// Guardar cambios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"];
    $placas = $_POST["placas"];
    $entrada = $_POST["entrada"];
    $salida = $_POST["salida"];
    $tiempo = $_POST["tiempo"];
    $total = $_POST["total"];

    mysqli_query($conn, "UPDATE registros SET placas='$placas', hora_entrada='$entrada',
        hora_salida='$salida', tiempo_min='$tiempo', total='$total'
        WHERE id='$id'");

    $mensaje = "Registro actualizado correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Registros</title>
<style>
    body { font-family:'Segoe UI'; background:#eef1f7; padding:20px; }
    table { width:100%; background:white; margin-top:20px; border-collapse: collapse; }
    th,td { padding:10px; border-bottom:1px solid #ddd; }
    th { background:#4dabf7; color:white; }
    input { padding:6px; border-radius:6px; border:1px solid #ccc; }
    button { padding:6px 10px; border:none; background:#4dabf7; color:white; border-radius:8px; }
    .msg { background:#d4edda; padding:10px; border-radius:8px; color:#155724; margin-top:10px; }
</style>
</head>
<body>

<h1>Editar Registros</h1>
<a href="admin.php" class="btn">⬅ Regresar</a>

<?php if ($mensaje): ?>
<div class="msg"><?= $mensaje ?></div>
<?php endif; ?>

<table>
<tr>
    <th>ID</th>
    <th>Placas</th>
    <th>Entrada</th>
    <th>Salida</th>
    <th>Tiempo</th>
    <th>Total</th>
    <th>Guardar</th>
</tr>

<?php
$reg = mysqli_query($conn, "SELECT * FROM registros");

while ($r = mysqli_fetch_assoc($reg)) {
    echo "
    <tr>
        <form method='POST'>
            <td>{$r['id']} <input type='hidden' name='id' value='{$r['id']}'></td>
            <td><input name='placas' value='{$r['placas']}'></td>
            <td><input type='datetime-local' name='entrada' value='{$r['hora_entrada']}'></td>
            <td><input type='datetime-local' name='salida' value='{$r['hora_salida']}'></td>
            <td><input name='tiempo' value='{$r['tiempo_min']}'></td>
            <td><input name='total' value='{$r['total']}'></td>
            <td><button>Guardar</button></td>
        </form>
    </tr>";
}
?>

</table>

</body>
</html>
