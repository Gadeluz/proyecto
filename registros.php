<?php
session_start();
if (!isset($_SESSION["admin"])) { header("Location: ../index.php"); exit; }
include "../bd.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registros del Estacionamiento</title>
<style>
    body { font-family: 'Segoe UI'; background:#eef1f7; margin:0; padding:20px; }
    h1 { color:#1a2a3a; }
    table { width:100%; border-collapse: collapse; margin-top:20px; background:white; }
    th, td { padding:12px; border-bottom:1px solid #ddd; }
    th { background:#4dabf7; color:white; }
    tr:hover { background:#f1f1f1; }
    a.btn { padding:10px 15px; background:#4dabf7; color:white; border-radius:8px; text-decoration:none; }
</style>
</head>
<body>

<h1>Registros del Estacionamiento</h1>
<a href="admin.php" class="btn">⬅ Regresar</a>

<table>
    <tr>
        <th>ID</th>
        <th>Placas</th>
        <th>Entrada</th>
        <th>Salida</th>
        <th>Tiempo (min)</th>
        <th>Total</th>
        <th>Cajón</th>
    </tr>

<?php
$consulta = mysqli_query($conn, "SELECT * FROM registros ORDER BY id DESC");

while ($fila = mysqli_fetch_assoc($consulta)) {
    echo "<tr>
        <td>{$fila['id']}</td>
        <td>{$fila['placas']}</td>
        <td>{$fila['hora_entrada']}</td>
        <td>{$fila['hora_salida']}</td>
        <td>{$fila['tiempo_min']}</td>
        <td>\${$fila['total']}</td>
        <td>{$fila['id_cajon']}</td>
    </tr>";
}
?>

</table>

</body>
</html>
