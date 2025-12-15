<?php
session_start();

if (!isset($_SESSION["admin"])) {
    echo "
    <div style='font-family:Segoe UI;text-align:center;padding:50px;'>
        <h2> Acceso restringido</h2>
        <p>Debes iniciar sesión para acceder al panel de administración.</p>
        <a href='../index.php' 
           style='display:inline-block;margin-top:10px;padding:10px 18px;background:#0d6efd;color:#fff;
                  border-radius:8px;text-decoration:none;font-weight:bold;'>
           Iniciar Sesión
        </a>
    </div>";
    exit;
}


require "bd.php";

$mensaje = "";


if (isset($_POST["eliminar_todo"])) {
    mysqli_query($conn,"UPDATE cajones SET estado='libre'");
    mysqli_query($conn,"DELETE FROM registros");
    $mensaje="Todos los registros han sido eliminados.";
}


if (isset($_POST["eliminar_id"])) {
    $id=(int)$_POST["eliminar_id"];
    $q=mysqli_query($conn,"SELECT cajon_id FROM registros WHERE id=$id");
    if($q && mysqli_num_rows($q)>0){
        $cj=mysqli_fetch_assoc($q)["cajon_id"];
        mysqli_query($conn,"UPDATE cajones SET estado='libre' WHERE id=$cj");
    }
    mysqli_query($conn,"DELETE FROM registros WHERE id=$id");
    $mensaje="Registro eliminado.";
}


$res=mysqli_query($conn,"
    SELECT r.id,r.ticket_code,r.tipo_auto,c.numero AS cajon,r.hora_entrada,r.hora_salida,r.total
    FROM registros r
    LEFT JOIN cajones c ON c.id=r.cajon_id
    ORDER BY r.id DESC
");
$registros=mysqli_fetch_all($res,MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Administrativo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="/js/html2pdf.bundle.js"></script>

<style>
body{font-family:'Segoe UI';background:#eef1f7;margin:0;}
.sidebar{width:250px;height:100vh;position:fixed;background:linear-gradient(180deg,#1a2a3a,#0d1117);color:white;padding-top:20px;}
.sidebar a{display:block;padding:14px;font-size:17px;color:#d0d6e0;text-decoration:none;border-left:4px solid transparent;}
.sidebar a:hover{background:rgba(255,255,255,.08);border-left:4px solid #4dabf7;color:#fff;}
.content{margin-left:250px;padding:35px;}
.card-dashboard{background:white;padding:25px;border-radius:15px;width:250px;box-shadow:0 3px 10px rgba(0,0,0,.09);}
.card-dashboard:hover{transform:translateY(-4px);}
.table-wrapper{background:white;padding:20px;border-radius:12px;box-shadow:0 3px 10px rgba(0,0,0,.08);}
</style>
</head>

<body>

<div class="sidebar">
    <h3 class="text-center">ADMIN PANEL</h3>
    <a href="admin.php"> Inicio</a>
    <a href="tarifas.php"> Actualizar Tarifas</a>
    <a href="cajones.php"> Gestionar Cajones</a>
    <a href="logout.php"> Cerrar Sesión</a>
</div>

<div class="content">

    <?php if($mensaje): ?>
    <div class="alert alert-info"><?= $mensaje ?></div>
    <?php endif; ?>

    <h1>Bienvenido Administrador</h1>
    <p>Control general del estacionamiento.</p>

    <div style="display:flex;gap:20px;margin-bottom:30px;">

        <div class="card-dashboard">
            <h4>Tarifas</h4><p>Ajustar tarifas Auto/Moto</p>
            <a href="tarifas.php" class="btn btn-primary w-100">Configurar</a>
        </div>

        <div class="card-dashboard">
            <h4>Cajones</h4><p>Control y disponibilidad</p>
            <a href="cajones.php" class="btn btn-primary w-100">Administrar</a>
        </div>

    </div>

 
    <div class="table-wrapper" id="registrosWrapper">

        <div class="d-flex justify-content-between mb-3">
            <h3>Últimos registros</h3>

            <div class="d-flex gap-2">
                <form method="POST" onsubmit="return confirm('¿Eliminar TODOS los registros?')">
                    <button class="btn btn-danger btn-sm" name="eliminar_todo">🗑 Eliminar Todo</button>
                </form>
                <button class="btn btn-secondary btn-sm" id="btnPdfRegistros"> Generar PDF</button>
            </div>
        </div>

        <table class="table table-hover align-middle" id="tablaR">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Ticket</th><th>Tipo</th><th>Cajón</th>
                    <th>Entrada</th><th>Salida</th><th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($registros as $r): ?>
                <tr>
                    <td><?= $r["id"] ?></td>
                    <td><?= $r["ticket_code"] ?></td>
                    <td><?= $r["tipo_auto"] ?></td>
                    <td><?= $r["cajon"] ?? "—" ?></td>
                    <td><?= $r["hora_entrada"] ?></td>
                    <td><?= $r["hora_salida"] ?: "—" ?></td>
                    <td><?= $r["total"] ? "$".number_format($r["total"],2) : "—" ?></td>

                    <td>
                        <div class="d-flex gap-1">
                            <a href="editar_registros.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">✏ Editar</a>
                            <form method="POST" onsubmit="return confirm('¿Eliminar registro?')">
                                <input type="hidden" name="eliminar_id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">🗑 Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

document.getElementById("btnPdfRegistros").onclick = () => {

    document.querySelectorAll("th:last-child, td:last-child").forEach(e=>e.style.display="none");

    html2pdf()
    .from(document.getElementById("registrosWrapper"))
    .set({filename:"registros.pdf",html2canvas:{scale:2},jsPDF:{orientation:"landscape"}})
    .save()
    .then(()=>{
        document.querySelectorAll("th:last-child, td:last-child").forEach(e=>e.style.display="table-cell");
    });
};
</script>

</body>
</html>
