<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

date_default_timezone_set("America/Mexico_City");
require("bd.php");


$TOTAL_ESPACIOS = 200;


$ocupados = 0;
$result = $conn->query("SELECT COUNT(*) AS cant FROM cajones WHERE estado='ocupado'");
if ($result) {
    $ocupados = (int)$result->fetch_assoc()['cant'];
}
$disponibles = $TOTAL_ESPACIOS - $ocupados;


$tarifaAuto = 20;  
$tarifaMoto = 15;

$sqlTarifas = "SELECT precio, precio_por_hora FROM tarifas ORDER BY id ASC LIMIT 1";
if ($resTar = $conn->query($sqlTarifas)) {
    if ($rowTar = $resTar->fetch_assoc()) {
        if ($rowTar['precio'] !== null) {
            $tarifaAuto = (float)$rowTar['precio'];
        }
        if ($rowTar['precio_por_hora'] !== null) {
            $tarifaMoto = (float)$rowTar['precio_por_hora'];
        }
    }
}

$ticketData = null;


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['tipo_auto'])) {

    $tipo_auto = $_POST['tipo_auto'];

 
    $tarifa = ($tipo_auto == "Auto") ? $tarifaAuto : $tarifaMoto;

    
    $ticket_code = uniqid("TCK");

  
    $resCajon = $conn->query("SELECT id, numero FROM cajones WHERE estado='libre' ORDER BY numero ASC LIMIT 1");
    $cajon = $resCajon ? $resCajon->fetch_assoc() : null;

    if (!$cajon) {
        die("<h2 style='color:red;text-align:center'>No hay cajones disponibles</h2>");
    }

    $cajon_id     = (int)$cajon['id'];
    $cajon_numero = $cajon['numero'];

    
    $conn->query("UPDATE cajones SET estado='ocupado' WHERE id = $cajon_id");

    
    $placa = 'N/A';

    
    $sqlInsert = "
        INSERT INTO registros (cajon_id, tipo_auto, placa, hora_entrada, ticket_code)
        VALUES ($cajon_id, '$tipo_auto', '$placa', NOW(), '$ticket_code')
    ";

    if (!$conn->query($sqlInsert)) {
        die("Error al registrar entrada: " . $conn->error);
    }

    $registro_id = $conn->insert_id;
    $hora_actual = date("Y-m-d H:i:s");

   
    $ticketData = [
        "ticket_code"  => $ticket_code,
        "registro_id"  => $registro_id,
        "cajon_id"     => $cajon_id,
        "cajon_numero" => $cajon_numero,
        "tipo_auto"    => $tipo_auto,
        "tarifa"       => $tarifa,
        "hora"         => $hora_actual
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Entrada Estacionamiento</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="/js/html2pdf.bundle.js"></script>

<style>
body {
    background: linear-gradient(135deg, #6cc6ff, #c9e8ff);
    font-family: Arial, sans-serif;
}

.card {
    border-radius: 20px;
}

#ticketPDF {
    display: none;
    width: 300px;
    padding: 20px;
    background: white;
    border-radius: 15px;
    color: #000;
    font-size: 14px;
}
</style>
</head>
<body>

<?php if (!$ticketData): ?>

<header class="bg-dark text-white text-center p-4">
    <h1> Estacionamiento Central</h1>
    <p>Control de Acceso - Entrada</p>
</header>

<div class="container mt-5">
    <div class="card shadow p-4" style="max-width: 600px; margin:auto;">

        <h3 class="text-center text-primary">Registrar Entrada</h3>
        <hr>

        <p><strong>Hora Actual:</strong> <span id="hora" class="text-success fw-bold"></span></p>

        <p><strong>Espacios Disponibles:</strong> 
            <span class="badge bg-success"><?= $disponibles ?></span>
        </p>

        <div class="alert alert-info mt-3">
            <strong>Tarifas actuales:</strong><br>
             Auto: <strong>$<?= number_format($tarifaAuto, 2) ?></strong> por hora <br>
             Moto: <strong>$<?= number_format($tarifaMoto, 2) ?></strong> por hora
        </div>

        <form method="POST">
            <label class="mt-3 fw-bold">Tipo de Vehículo:</label>
            <select name="tipo_auto" class="form-select" required <?= $disponibles <= 0 ? 'disabled' : '' ?>>
                <option value="">Seleccionar...</option>
                <option value="Auto">Auto</option>
                <option value="Moto">Moto</option>
            </select>

            <button class="btn btn-primary w-100 mt-4" <?= $disponibles <= 0 ? 'disabled' : '' ?>>
                Generar Ticket
            </button>
        </form>

        <?php if ($disponibles <= 0): ?>
            <div class="alert alert-danger text-center mt-3 fw-bold">
                Estacionamiento lleno
            </div>
        <?php endif; ?>

        <hr class="mt-4">
        <p class="text-center">
             <strong>Contacto:</strong> 55-1234-5678<br>
             <strong>Horario:</strong> 24 horas
        </p>

    </div>
</div>

<?php else: ?>


<div id="ticketPDF">
    <h3 style="text-align:center; margin-bottom:10px;">🎟 Ticket de Entrada</h3>
    <p><strong>Código Ticket:</strong> <span id="t_ticket"></span></p>
    <p><strong>ID Registro:</strong> <span id="t_registro"></span></p>
   
    <p><strong>Cajón:</strong> <span id="t_cajon"></span></p>
    <p><strong>Vehículo:</strong> <span id="t_tipo"></span></p>
    <p><strong>Tarifa:</strong> <span id="t_tarifa"></span></p>
    <p><strong>Hora Entrada:</strong> <span id="t_hora"></span></p>
    <p style="margin-top:15px; text-align:center;">Gracias por su visita</p>
</div>

<script>
const ticketData = <?= json_encode($ticketData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

window.addEventListener("DOMContentLoaded", () => {
    const d = ticketData;

    document.getElementById("t_ticket").textContent   = d.ticket_code;
    document.getElementById("t_registro").textContent = d.registro_id;
    document.getElementById("t_cajon").textContent    = d.cajon_numero;
    document.getElementById("t_tipo").textContent     = d.tipo_auto;
    document.getElementById("t_tarifa").textContent   = "$" + d.tarifa + " MXN/h";
    document.getElementById("t_hora").textContent     = d.hora;

    const element = document.getElementById("ticketPDF");
    element.style.display = "block";

    html2pdf().set({
        margin: 5,
        filename: "ticket_" + d.ticket_code + ".pdf",
        html2canvas: { scale: 2 },
        jsPDF: { unit: "mm", format: "a7", orientation: "portrait" }
    })
    .from(element)
    .save()
    .then(() => {
        element.style.display = "none";
        window.location.href = "entrada.php";
    });
});
</script>

<?php endif; ?>

<script>

setInterval(() => {
    const h = new Date().toLocaleTimeString();
    if (document.getElementById("hora"))
        document.getElementById("hora").innerText = h;
}, 1000);
</script>

</body>
</html>
