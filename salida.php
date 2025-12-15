<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

date_default_timezone_set("America/Mexico_City");
require("bd.php");


if (isset($_GET["action"]) && $_GET["action"] === "search") {
    header("Content-Type: application/json; charset=utf-8");

    $term = trim($_GET["term"] ?? "");
    $rows = [];

   
    if ($term === "") {
        $sql = "
            SELECT id, ticket_code
            FROM registros
            WHERE (hora_salida IS NULL OR hora_salida = '0000-00-00 00:00:00')
              AND ticket_code IS NOT NULL
              AND ticket_code <> ''
            ORDER BY id DESC
            LIMIT 20
        ";
        if ($res = $conn->query($sql)) {
            while ($r = $res->fetch_assoc()) $rows[] = $r;
        }
        echo json_encode($rows);
        exit;
    }
    $like = $term . "%";
    $stmt = $conn->prepare("
        SELECT id, ticket_code
        FROM registros
        WHERE ticket_code LIKE ?
          AND ticket_code IS NOT NULL
          AND ticket_code <> ''
        ORDER BY id DESC
        LIMIT 20
    ");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    echo json_encode($rows);
    exit;
}

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

$hora_salida = date("H:i:s");
$fecha_salida = date("d/m/Y");

$tipoVehiculo     = "";
$tarifa           = 0;
$horas_cobradas   = 0;
$total_pagar      = 0;
$cajon_numero     = "";
$hora_entrada_reg = "";
$hora_salida_reg  = "";
$errorMsg         = "";
$infoMsg          = "";

$salidaData = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["buscar"])) {

    $ticket_code = trim($_POST["ticket_code"] ?? "");

    if (!$ticket_code) {
        $errorMsg = "Ingresa el código de ticket.";
    }

    if (!$errorMsg) {

       
        $ticket_code_sql = mysqli_real_escape_string($conn, $ticket_code);

        $sql = "
            SELECT r.*, c.numero AS cajon_numero
            FROM registros r
            JOIN cajones c ON c.id = r.cajon_id
            WHERE r.ticket_code = '$ticket_code_sql'
            LIMIT 1
        ";
        $res = mysqli_query($conn, $sql);

        if ($res && mysqli_num_rows($res) > 0) {

            $row = mysqli_fetch_assoc($res);

            
            if (!is_null($row["hora_salida"]) && $row["hora_salida"] !== "0000-00-00 00:00:00") {
                $errorMsg = "Este ticket ya tiene salida registrada.";
            } else {

                $registro_id  = (int)$row["id"];
                $cajon_numero = $row["cajon_numero"];

                
                $tipoVehiculo = $row["tipo_auto"];

                
                if ($tipoVehiculo === "Auto") {
                    $tarifa = $tarifaAuto;
                } elseif ($tipoVehiculo === "Moto") {
                    $tarifa = $tarifaMoto;
                } else {
                    $errorMsg = "Tipo de vehículo no válido en el registro.";
                }

                if (!$errorMsg) {
                    
                    $entradaDT = new DateTime($row["hora_entrada"]);
                    $salidaDT  = new DateTime(); 

                    $hora_salida_full = $salidaDT->format("Y-m-d H:i:s");

                    
                    $hora_entrada_reg = $row["hora_entrada"];
                    $hora_salida_reg  = $hora_salida_full;

                   
                    $interval = $entradaDT->diff($salidaDT);

                    
                    $horas_cobradas = ($interval->days * 24) + $interval->h;
                    if ($interval->i > 0 || $interval->s > 0) {
                        $horas_cobradas++;
                    }
                    if ($horas_cobradas <= 0) {
                        $horas_cobradas = 1;
                    }

                    $total_pagar = $horas_cobradas * $tarifa;

                   
                    $upd = "
                        UPDATE registros
                        SET hora_salida = '$hora_salida_full',
                            total = $total_pagar
                        WHERE id = $registro_id
                        LIMIT 1
                    ";
                    mysqli_query($conn, $upd);

                 
                    $updCajon = "
                        UPDATE cajones
                        SET estado = 'libre'
                        WHERE id = " . (int)$row["cajon_id"] . "
                        LIMIT 1
                    ";
                    mysqli_query($conn, $updCajon);

                    $infoMsg = "Salida registrada. Total a pagar: $$total_pagar MXN";

                  
                    $salidaData = [
                        "ticket_code"  => $ticket_code,
                        "id_registro"  => $registro_id,
                        "tipo"         => $tipoVehiculo,
                        "tarifa"       => $tarifa,
                        "horas"        => $horas_cobradas,
                        "total"        => $total_pagar,
                        "cajon"        => $cajon_numero,
                        "hora_entrada" => $hora_entrada_reg,
                        "hora_salida"  => $hora_salida_reg,
                        "fecha_salida" => $fecha_salida
                    ];
                }
            }

        } else {
            $errorMsg = "No se encontró un registro con ese código de ticket.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Punto de Venta - Salida</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="/js/html2pdf.bundle.js"></script>

<style>
    body {
        min-height: 100vh;
        background: radial-gradient(circle at top left, #1f3b73, #050816);
        color: #f8f9fa;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    .page-wrapper { padding: 40px 15px; }

    h1 {
        text-align: center;
        font-size: 36px;
        margin-bottom: 30px;
        color: #0d6efd;
        text-shadow: 0 0 12px rgba(13, 110, 253, 0.7);
        font-weight: 700;
    }

    .subtitle { text-align: center; color: #adb5bd; margin-bottom: 35px; }

    .card-glass {
        background: rgba(15, 23, 42, 0.85);
        border-radius: 1rem;
        border: 1px solid rgba(240, 242, 245, 1);
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(16px);
    }

    .label-light { font-weight: 600; font-size: 0.9rem; color: #cbd5f5; }

    .input-id {
        width: 100%;
        padding: 10px 12px;
        border-radius: 0.65rem;
        border: 1px solid #495057;
        font-size: 16px;
    }

    .input-id:focus {
        outline: none;
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25);
    }

    .btn-buscar {
        width: 100%;
        padding: 12px;
        border: none;
        font-size: 17px;
        background: linear-gradient(135deg, #0d6efd, #00b4d8);
        color: #fff;
        font-weight: 600;
        border-radius: 0.75rem;
        cursor: pointer;
        margin-top: 12px;
    }
    .btn-buscar:hover { opacity: 0.9; }

    .btn-ticket {
        width: 100%;
        padding: 12px;
        border: none;
        font-size: 17px;
        background: linear-gradient(135deg, #00f5a0, #00d9f5);
        color: #000;
        font-weight: 600;
        border-radius: 0.75rem;
        cursor: pointer;
        margin-top: 18px;
    }

    .summary-item span:first-child { color: #9ca3af; font-size: 0.9rem; }
    .summary-item span:last-child { font-weight: 600; font-size: 1rem; }

    #ticketSalidaPDF {
        display: none;
        width: 320px;
        padding: 20px;
        background: white;
        border-radius: 15px;
        color: #000;
        font-size: 13px;
        border: 1px solid #ddd;
    }

    .brand-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.7);
        border: 1px solid rgba(148, 163, 184, 0.5);
        font-size: 0.8rem;
        color: #e5e7eb;
    }
</style>
</head>
<body>

<div class="page-wrapper">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h1 class="mb-1">Punto de Venta  Salida</h1>
                <p class="subtitle mb-0">Control de cobro y liberación de cajones en tiempo real</p>
            </div>
            <div class="brand-pill">
                <span> Estacionamiento Central</span>
            </div>
        </div>

        <?php if($errorMsg): ?>
            <div class="alert alert-danger shadow-sm"><?= $errorMsg ?></div>
        <?php endif; ?>

        <?php if($infoMsg): ?>
            <div class="alert alert-success shadow-sm"><?= $infoMsg ?></div>
        <?php endif; ?>

        <div class="row g-4 mt-2">

            <div class="col-lg-4">
                <div class="card card-glass p-4 h-100">
                    <h5 class="text-light mb-3"> Buscar ticket</h5>

                    <form action="" method="POST" class="mb-3">
                        <label class="label-light">Código de ticket (ej. TCK...):</label>

                        
                        <input
                          type="text"
                          id="ticket_code"
                          name="ticket_code"
                          class="input-id mt-1"
                          placeholder="Ingresa el código del ticket"
                          autocomplete="off"
                          required
                        >

                        <div id="tckSuggestions"
                             class="list-group mt-2"
                             style="display:none; max-height:220px; overflow:auto;">
                        </div>

                        <div class="mt-2 text-secondary" style="font-size: 0.9rem;">
                          Empieza a escribir y se mostrarán coincidencias (Ticket + ID).
                        </div>

                        <button class="btn-buscar" name="buscar">Calcular salida</button>
                    </form>

                    <hr class="border-secondary">

                    <h6 class="text-light mb-3"> Resumen de cobro</h6>

                    <div class="d-flex flex-column gap-2">
                        <div class="summary-item d-flex justify-content-between">
                            <span>Tipo de vehículo:</span>
                            <span><?= $tipoVehiculo ? htmlspecialchars($tipoVehiculo) : "—" ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between">
                            <span>Hora entrada:</span>
                            <span><?= $hora_entrada_reg ? $hora_entrada_reg : "—" ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between">
                            <span>Hora salida:</span>
                            <span><?= $hora_salida_reg ? $hora_salida_reg : "—" ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between">
                            <span>Tarifa:</span>
                            <span><?= $tarifa > 0 ? "$".$tarifa." MXN/h" : "—" ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between">
                            <span>Horas cobradas:</span>
                            <span><?= $horas_cobradas > 0 ? $horas_cobradas." h" : "—" ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between">
                            <span>Total a pagar:</span>
                            <span><?= $total_pagar > 0 ? "$".$total_pagar." MXN" : "—" ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between">
                            <span>Cajón:</span>
                            <span><?= $cajon_numero ? htmlspecialchars($cajon_numero) : "—" ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-glass p-4 mb-3">
                    <h5 class="text-light mb-3"> Hora del sistema</h5>
                    <p class="fs-4 mb-0 fw-semibold"><?= $hora_salida ?></p>
                    <small class="text-secondary">Hora actual del sistema</small>
                </div>

                <div class="card card-glass p-4">
                    <h5 class="text-light mb-3"> Fecha de salida</h5>
                    <p class="fs-5 mb-0 fw-semibold"><?= $fecha_salida ?></p>
                    <small class="text-secondary">Registro automático de la fecha</small>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-glass p-4 h-100 d-flex flex-column align-items-center justify-content-between">
                    <div class="text-center">
                        <img src="https://i.postimg.cc/5NfL9tGq/puntoventa.png" class="img-fluid rounded mb-3" alt="Ticket Salida" style="max-width: 260px;">
                        <p class="text-secondary mb-0">Genera y descarga el comprobante<br>de salida para el cliente.</p>
                    </div>

                    <button class="btn-ticket" type="button" id="btnDescargar" disabled>
                        Ticket de salida
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>


<div id="ticketSalidaPDF">
    <h3 style="text-align:center;margin-bottom:10px;">🎟 Ticket de Salida</h3>
    <p><strong>Código Ticket:</strong> <span id="s_ticket"></span></p>
    <p><strong>ID Registro:</strong> <span id="s_id"></span></p>
    <p><strong>Cajón:</strong> <span id="s_cajon"></span></p>
    <p><strong>Vehículo:</strong> <span id="s_tipo"></span></p>
    <p><strong>Hora entrada:</strong> <span id="s_hora_ent"></span></p>
    <p><strong>Hora salida:</strong> <span id="s_hora_sal"></span></p>
    <p><strong>Horas cobradas:</strong> <span id="s_horas"></span></p>
    <p><strong>Tarifa:</strong> <span id="s_tarifa"></span></p>
    <p><strong>Total:</strong> <span id="s_total"></span></p>
    <p><strong>Fecha salida:</strong> <span id="s_fecha"></span></p>
    <p style="margin-top:15px; text-align:center;">Gracias por su visita</p>
</div>

<script>
<?php if ($salidaData): ?>
    const salidaData = <?= json_encode($salidaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
<?php else: ?>
    const salidaData = null;
<?php endif; ?>

function generarPDFSalida(data) {
    document.getElementById("s_ticket").textContent   = data.ticket_code;
    document.getElementById("s_id").textContent       = data.id_registro;
    document.getElementById("s_cajon").textContent    = data.cajon;
    document.getElementById("s_tipo").textContent     = data.tipo;
    document.getElementById("s_horas").textContent    = data.horas + " h";
    document.getElementById("s_tarifa").textContent   = "$" + data.tarifa + " MXN/h";
    document.getElementById("s_total").textContent    = "$" + data.total + " MXN";
    document.getElementById("s_hora_ent").textContent = data.hora_entrada;
    document.getElementById("s_hora_sal").textContent = data.hora_salida;
    document.getElementById("s_fecha").textContent    = data.fecha_salida;

    const ticket = document.getElementById("ticketSalidaPDF");
    ticket.style.display = "block";

    html2pdf().set({
        margin: 5,
        filename: "ticket_salida_" + data.ticket_code + ".pdf",
        html2canvas: { scale: 2 },
        jsPDF: { unit: "mm", format: "a7", orientation: "portrait" }
    })
    .from(ticket)
    .save()
    .then(() => {
        ticket.style.display = "none";
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("btnDescargar");
    if (salidaData && btn) {
        btn.disabled = false;
        btn.addEventListener("click", function() {
            generarPDFSalida(salidaData);
        });
    }
});
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const input = document.getElementById("ticket_code");
  const box = document.getElementById("tckSuggestions");
  if (!input || !box) return;

  let t = null;

  async function cargar(term) {
    const url = `salida.php?action=search&term=${encodeURIComponent(term)}`;
    const res = await fetch(url);
    const data = await res.json();

    if (!Array.isArray(data) || data.length === 0) {
      box.style.display = "none";
      box.innerHTML = "";
      return;
    }

    box.innerHTML = data.map(r => `
      <button type="button"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
              data-ticket="${String(r.ticket_code).replace(/"/g,'&quot;')}">
        <span><strong>${r.ticket_code}</strong></span>
        <span class="badge bg-secondary">ID: ${r.id}</span>
      </button>
    `).join("");

    box.style.display = "block";
  }

  
  input.addEventListener("input", () => {
    const term = input.value.trim();
    clearTimeout(t);
    t = setTimeout(() => cargar(term), 150);
  });

  
  input.addEventListener("focus", () => {
    const term = input.value.trim();
    cargar(term);
  });


  box.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-ticket]");
    if (!btn) return;
    input.value = btn.getAttribute("data-ticket");
    box.style.display = "none";
  });


  document.addEventListener("click", (e) => {
    if (e.target !== input && !box.contains(e.target)) {
      box.style.display = "none";
    }
  });
});
</script>

</body>
</html>
