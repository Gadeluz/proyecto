<?php
session_start();

if (!isset($_SESSION["admin"])) {
    echo "
    <div style='font-family:Segoe UI;text-align:center;padding:50px;'>
        <h2> Acceso denegado</h2>
        <p>No puedes entrar a esta sección sin iniciar sesión.</p>
        <a href='../index.php' 
           style='display:inline-block;margin-top:10px;padding:10px 18px;background:#198754;color:white;
                  border-radius:8px;text-decoration:none;font-weight:bold;'>
            Iniciar Sesión
        </a>
    </div>";
    exit;
}
include "bd.php";


if (isset($_POST["cambiar"])) {
    $id     = (int)$_POST["id"];
    $estado = $_POST["estado"]; 

    
    if ($estado === "libre" || $estado === "ocupado") {
        $sql = "UPDATE cajones SET estado='$estado' WHERE id=$id";
        mysqli_query($conn, $sql);
    }
}


if (isset($_POST["agregar"])) {
    $resMax = mysqli_query($conn, "SELECT COALESCE(MAX(numero),0) AS maxnum FROM cajones");
    $rowMax = mysqli_fetch_assoc($resMax);
    $siguienteNumero = (int)$rowMax["maxnum"] + 1;

    $sqlIns = "INSERT INTO cajones (numero, estado) VALUES ($siguienteNumero, 'libre')";
    mysqli_query($conn, $sqlIns);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestionar Cajones</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body { font-family:'Segoe UI', sans-serif; background:#eef1f7; padding:20px; }
    .card-wrapper { background:#fff; border-radius:16px; box-shadow:0 4px 10px rgba(0,0,0,0.08); padding:20px; }
    table { width:100%; border-collapse: collapse; }
    th,td { padding:10px; border-bottom:1px solid #dee2e6; }
    th { background:#0d6efd; color:white; }
    select,button { padding:6px 10px; border-radius:8px; border:1px solid #ced4da; }
    .btn-back { display:inline-block; margin-bottom:15px; padding:6px 12px; background:#6c757d; color:#fff; border-radius:8px; text-decoration:none; }
    .btn-back:hover { background:#495057; color:#fff; }
</style>
</head>
<body>

<h1 class="mb-3">Gestionar Cajones</h1>
<a href="admin.php" class="btn-back">⬅ Regresar</a>

<div class="card-wrapper mt-2">

    <form method="POST" class="mb-3">
        <button name="agregar" class="btn btn-success btn-sm"> Agregar Cajón</button>
    </form>

    <div class="table-responsive">
        <table>
            <tr>
                <th>ID</th>
                <th>Número</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>

            <?php
            $query = mysqli_query($conn, "SELECT id, numero, estado FROM cajones ORDER BY numero ASC");

            while ($c = mysqli_fetch_assoc($query)) {
                $id      = (int)$c['id'];
                $numero  = (int)$c['numero'];
                $estado  = $c['estado']; 

                echo "<tr>
                    <td>{$id}</td>
                    <td>{$numero}</td>
                    <td>" . htmlspecialchars($estado) . "</td>
                    <td>
                        <form method='POST' style='display:flex; gap:10px; align-items:center;'>
                            <input type='hidden' name='id' value='{$id}'>
                            <select name='estado'>
                                <option value='libre' " . ($estado=='libre'?'selected':'') . ">Libre</option>
                                <option value='ocupado' " . ($estado=='ocupado'?'selected':'') . ">Ocupado</option>
                            </select>
                            <button name='cambiar' class='btn btn-primary btn-sm'>Guardar</button>
                        </form>
                    </td>
                </tr>";
            }
            ?>
        </table>
    </div>
</div>

</body>
</html>
