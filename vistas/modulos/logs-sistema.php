<?php

if(($_SESSION["perfil"] ?? "") != "Administrador"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$logsSistema = ControladorLogs::ctrMostrarLogs();

function tmLogEsc($valor){
  return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function tmLogFecha($fecha, $formato = "d/m/Y H:i"){
  $time = strtotime((string)$fecha);
  return $time ? date($formato, $time) : tmLogEsc($fecha);
}

function tmLogFechaData($fecha){
  $time = strtotime((string)$fecha);
  return $time ? date("Y-m-d", $time) : "";
}

function tmLogAccionTexto($accion){
  $accion = trim((string)$accion);
  return $accion !== "" ? ucwords(str_replace("_", " ", $accion)) : "Sin accion";
}

function tmLogBadgeClass($accion){
  $accion = strtolower((string)$accion);

  if(strpos($accion, "inicio") !== false || strpos($accion, "login") !== false || strpos($accion, "crear") !== false || strpos($accion, "registrar") !== false){
    return "success";
  }

  if(strpos($accion, "editar") !== false || strpos($accion, "actualizar") !== false || strpos($accion, "aprobar") !== false || strpos($accion, "cobrar") !== false){
    return "warning";
  }

  if(strpos($accion, "borrar") !== false || strpos($accion, "eliminar") !== false || strpos($accion, "bloqueado") !== false || strpos($accion, "rechazar") !== false || strpos($accion, "error") !== false){
    return "danger";
  }

  return "info";
}

function tmLogIcon($accion){
  $accion = strtolower((string)$accion);

  if(strpos($accion, "inicio") !== false || strpos($accion, "login") !== false){
    return "fa-sign-in";
  }
  if(strpos($accion, "crear") !== false || strpos($accion, "registrar") !== false){
    return "fa-plus";
  }
  if(strpos($accion, "editar") !== false || strpos($accion, "actualizar") !== false){
    return "fa-pencil";
  }
  if(strpos($accion, "borrar") !== false || strpos($accion, "eliminar") !== false){
    return "fa-trash";
  }
  if(strpos($accion, "bloqueado") !== false || strpos($accion, "error") !== false){
    return "fa-warning";
  }

  return "fa-history";
}

$totalLogs = is_array($logsSistema) ? count($logsSistema) : 0;
$logsHoy = 0;
$logsLogin = 0;
$accionesLog = array();
$modulosLog = array();
$usuariosLog = array();
$ipsLog = array();
$fechaHoy = date("Y-m-d");

if(is_array($logsSistema)){
  foreach($logsSistema as $logResumen){
    $fechaLog = tmLogFechaData($logResumen["fecha"] ?? "");
    $accionLog = trim((string)($logResumen["accion"] ?? ""));
    $moduloLog = trim((string)($logResumen["modulo"] ?? ""));
    $usuarioLog = trim((string)($logResumen["usuario"] ?? "Sistema"));
    $ipLog = trim((string)($logResumen["ip"] ?? ""));

    if($fechaLog === $fechaHoy){
      $logsHoy++;
    }

    if(strpos(strtolower($accionLog), "login") !== false || strpos(strtolower($accionLog), "inicio") !== false){
      $logsLogin++;
    }

    if($accionLog !== ""){
      $accionesLog[$accionLog] = true;
    }
    if($moduloLog !== ""){
      $modulosLog[$moduloLog] = true;
    }
    if($usuarioLog !== ""){
      $usuariosLog[$usuarioLog] = true;
    }
    if($ipLog !== ""){
      $ipsLog[$ipLog] = true;
    }
  }
}

$accionesLog = array_keys($accionesLog);
$modulosLog = array_keys($modulosLog);
sort($accionesLog);
sort($modulosLog);

?>

<div class="content-wrapper logs-page">
<style>
  .logs-page{
    background:transparent !important;
  }

  .logs-page .content{
    padding-top:10px;
  }

  .tm-logs-shell{
    color:#13243d;
    font-family:"Segoe UI",Roboto,Arial,sans-serif;
  }

  .tm-logs-hero{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:16px;
    padding:19px 21px;
    margin-bottom:14px;
    border:1px solid rgba(255,255,255,.24);
    border-radius:20px;
    background:linear-gradient(135deg,rgba(10,47,68,.96),rgba(18,145,205,.92));
    color:#fff;
    box-shadow:0 18px 42px rgba(13,54,89,.18);
    position:relative;
    overflow:hidden;
  }

  .tm-logs-hero:after{
    content:"";
    position:absolute;
    width:220px;
    height:220px;
    right:-70px;
    bottom:-100px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
  }

  .tm-logs-hero h2{
    margin:0 0 5px;
    font-size:25px;
    line-height:1.1;
    font-weight:900;
  }

  .tm-logs-hero p{
    margin:0;
    max-width:780px;
    color:rgba(255,255,255,.9);
    font-size:13px;
    font-weight:700;
  }

  .tm-logs-hero-chip{
    position:relative;
    z-index:1;
    min-width:142px;
    border:1px solid rgba(255,255,255,.28);
    border-radius:16px;
    padding:11px 13px;
    background:rgba(255,255,255,.14);
    text-align:right;
  }

  .tm-logs-hero-chip span{
    display:block;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    color:rgba(255,255,255,.82);
  }

  .tm-logs-hero-chip b{
    display:block;
    font-size:25px;
    line-height:1;
    font-weight:900;
  }

  .tm-logs-kpis{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin-bottom:14px;
  }

  .tm-log-kpi{
    min-height:92px;
    display:grid;
    grid-template-columns:42px minmax(0,1fr);
    gap:11px;
    align-items:center;
    padding:13px;
    border:1px solid rgba(39,114,187,.14);
    border-radius:17px;
    background:rgba(255,255,255,.76);
    box-shadow:0 8px 20px rgba(20,80,135,.06);
  }

  .tm-log-kpi i{
    width:42px;
    height:42px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    background:linear-gradient(135deg,#1d75d1,#0bb4dc);
    font-size:18px;
  }

  .tm-log-kpi span{
    display:block;
    color:#667b91;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-log-kpi b{
    display:block;
    color:#13243d;
    font-size:25px;
    line-height:1.05;
    font-weight:900;
  }

  .tm-logs-panel{
    border:1px solid rgba(176,207,232,.8);
    border-radius:18px;
    background:rgba(255,255,255,.78);
    box-shadow:0 8px 22px rgba(32,77,118,.06);
    overflow:hidden;
  }

  .tm-logs-toolbar{
    display:grid;
    grid-template-columns:minmax(220px,1fr) 150px 160px 145px 145px 130px;
    gap:10px;
    align-items:end;
    padding:14px;
    border-bottom:1px solid #dce9f7;
    background:rgba(246,250,254,.78);
  }

  .tm-log-field label{
    display:block;
    margin:0 0 5px;
    color:#5d7289;
    font-size:10px;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-log-field .form-control{
    height:38px;
    border-radius:11px;
    border-color:#d9e7f5;
    box-shadow:none;
    color:#223951;
    font-size:12px;
    font-weight:800;
  }

  .tm-log-search{
    position:relative;
  }

  .tm-log-search i{
    position:absolute;
    left:12px;
    bottom:11px;
    color:#168fd0;
  }

  .tm-log-search .form-control{
    padding-left:36px;
  }

  .tm-logs-table-wrap{
    padding:14px;
  }

  .tm-logs-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    table-layout:fixed;
    background:rgba(255,255,255,.82);
    border:1px solid #dce9f7;
    border-radius:16px;
    overflow:hidden;
  }

  .tm-logs-table th{
    background:#edf5ff;
    color:#124c7d;
    border-bottom:1px solid #cfe2f5;
    padding:11px 9px;
    font-size:11px;
    line-height:1.15;
    font-weight:900;
    text-transform:uppercase;
    vertical-align:middle;
  }

  .tm-logs-table td{
    border-bottom:1px solid #e5eef8;
    border-right:1px solid #edf3fa;
    padding:10px 9px;
    color:#243a54;
    font-size:12px;
    line-height:1.28;
    font-weight:700;
    vertical-align:middle;
    overflow-wrap:anywhere;
  }

  .tm-logs-table tr:last-child td{
    border-bottom:0;
  }

  .tm-logs-table th:last-child,
  .tm-logs-table td:last-child{
    border-right:0;
  }

  .tm-logs-table tbody tr:hover td{
    background:rgba(234,246,255,.8);
  }

  .tm-logs-col-index{width:46px;text-align:center;}
  .tm-logs-col-date{width:118px;}
  .tm-logs-col-user{width:190px;}
  .tm-logs-col-role{width:110px;}
  .tm-logs-col-ip{width:122px;}
  .tm-logs-col-action{width:142px;}
  .tm-logs-col-module{width:132px;}
  .tm-logs-col-detail{width:auto;}

  .tm-log-index{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:28px;
    height:28px;
    border-radius:10px;
    background:#eef7ff;
    color:#176ca8;
    font-size:11px;
    font-weight:900;
  }

  .tm-log-date strong{
    display:block;
    color:#14243a;
    font-size:12px;
  }

  .tm-log-date span{
    display:block;
    color:#73879e;
    font-size:11px;
    font-weight:800;
  }

  .tm-log-user{
    display:flex;
    align-items:center;
    gap:9px;
    min-width:0;
  }

  .tm-log-avatar{
    flex:0 0 auto;
    width:32px;
    height:32px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#1d75d1,#0bb4dc);
    color:#fff;
    font-size:12px;
    font-weight:900;
  }

  .tm-log-user b{
    display:block;
    color:#14243a;
    font-size:12px;
    overflow-wrap:anywhere;
  }

  .tm-log-user span{
    display:block;
    color:#6c8197;
    font-size:11px;
    font-weight:800;
  }

  .tm-log-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:5px;
    max-width:100%;
    border-radius:999px;
    padding:6px 9px;
    color:#fff;
    font-size:10px;
    line-height:1.1;
    font-weight:900;
    text-transform:uppercase;
    overflow-wrap:anywhere;
  }

  .tm-log-badge.success{background:#10b981;}
  .tm-log-badge.warning{background:#f59e0b;}
  .tm-log-badge.danger{background:#ef4444;}
  .tm-log-badge.info{background:#0ea5e9;}

  .tm-log-module{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    max-width:100%;
    padding:6px 9px;
    border-radius:999px;
    background:#eef7ff;
    color:#155f97;
    border:1px solid #d3e9fb;
    font-size:10px;
    line-height:1.1;
    font-weight:900;
    text-transform:uppercase;
    overflow-wrap:anywhere;
  }

  .tm-log-detail{
    display:block;
    max-height:64px;
    overflow:auto;
    padding-right:4px;
  }

  .tm-log-empty{
    padding:26px !important;
    text-align:center;
    color:#6b8199;
    font-weight:900;
  }

  .tm-logs-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    padding:0 14px 14px;
    color:#637890;
    font-size:12px;
    font-weight:800;
  }

  .tm-logs-reset{
    border:0;
    border-radius:10px;
    background:#edf5ff;
    color:#155f97;
    padding:8px 11px;
    font-size:12px;
    font-weight:900;
  }

  .tm-logs-pager{
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
  }

  .tm-logs-page-btn{
    border:0;
    border-radius:10px;
    background:#125f9d;
    color:#fff;
    min-height:32px;
    padding:7px 11px;
    font-size:12px;
    font-weight:900;
  }

  .tm-logs-page-btn:disabled{
    opacity:.45;
    cursor:not-allowed;
  }

  .tm-logs-page-label{
    color:#415b76;
    font-size:12px;
    font-weight:900;
  }

  body.tm-dark-mode .tm-log-kpi,
  body.tm-dark-mode .tm-logs-panel,
  body.tm-dark-mode .tm-logs-table,
  body.tm-dark-mode .tm-logs-toolbar{
    background:rgba(14,29,52,.66);
    border-color:rgba(120,158,205,.28);
    color:#eef6ff;
  }

  body.tm-dark-mode .tm-log-kpi b,
  body.tm-dark-mode .tm-log-date strong,
  body.tm-dark-mode .tm-log-user b{
    color:#fff;
  }

  body.tm-dark-mode .tm-log-kpi span,
  body.tm-dark-mode .tm-log-field label,
  body.tm-dark-mode .tm-log-date span,
  body.tm-dark-mode .tm-log-user span,
  body.tm-dark-mode .tm-logs-footer{
    color:#c9dcf0;
  }

  body.tm-dark-mode .tm-log-field .form-control{
    background:#0c1a2d;
    border-color:#28425f;
    color:#eef6ff;
  }

  body.tm-dark-mode .tm-logs-table th{
    background:rgba(29,64,103,.92);
    border-color:rgba(120,158,205,.24);
    color:#eaf5ff;
  }

  body.tm-dark-mode .tm-logs-table td{
    color:#eef6ff;
    border-color:rgba(120,158,205,.18);
  }

  body.tm-dark-mode .tm-logs-table tbody tr:hover td{
    background:rgba(27,86,137,.24);
  }

  body.tm-dark-mode .tm-log-module,
  body.tm-dark-mode .tm-log-index,
  body.tm-dark-mode .tm-logs-reset{
    background:rgba(30,73,115,.62);
    border-color:rgba(120,158,205,.26);
    color:#eaf5ff;
  }

  body.tm-dark-mode .tm-logs-page-label{
    color:#d6e8fa;
  }

  @media(max-width:1200px){
    .tm-logs-toolbar{
      grid-template-columns:1fr 1fr 1fr;
    }

    .tm-log-search{
      grid-column:1 / -1;
    }
  }

  @media(max-width:991px){
    .tm-logs-kpis{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .tm-logs-table{
      min-width:980px;
    }

    .tm-logs-table-wrap{
      overflow-x:auto;
    }
  }

  @media(max-width:640px){
    .tm-logs-hero,
    .tm-logs-toolbar,
    .tm-logs-kpis{
      grid-template-columns:1fr;
    }

    .tm-logs-footer{
      align-items:flex-start;
      flex-direction:column;
    }
  }
</style>

  <section class="content-header">
    <h1>Logs del sistema</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Logs del sistema</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-logs-shell">
      <div class="tm-logs-hero">
        <div>
          <h2>Auditoria del sistema</h2>
          <p>Consulta los movimientos registrados por usuario, rol, IP, accion, modulo y detalle. Esta vista esta pensada para seguimiento interno y control de cambios.</p>
        </div>
        <div class="tm-logs-hero-chip">
          <span>Ultimos registros</span>
          <b><?php echo $totalLogs; ?></b>
        </div>
      </div>

      <div class="tm-logs-kpis">
        <div class="tm-log-kpi">
          <i class="fa fa-history"></i>
          <div><span>Total logs</span><b><?php echo $totalLogs; ?></b></div>
        </div>
        <div class="tm-log-kpi">
          <i class="fa fa-calendar-check-o"></i>
          <div><span>Registrados hoy</span><b><?php echo $logsHoy; ?></b></div>
        </div>
        <div class="tm-log-kpi">
          <i class="fa fa-sign-in"></i>
          <div><span>Ingresos / login</span><b><?php echo $logsLogin; ?></b></div>
        </div>
        <div class="tm-log-kpi">
          <i class="fa fa-desktop"></i>
          <div><span>IPs detectadas</span><b><?php echo count($ipsLog); ?></b></div>
        </div>
      </div>

      <div class="tm-logs-panel">
        <div class="tm-logs-toolbar">
          <div class="tm-log-field tm-log-search">
            <label>Buscar</label>
            <i class="fa fa-search"></i>
            <input type="text" class="form-control" id="tmLogBuscar" placeholder="Usuario, IP, modulo, accion o detalle">
          </div>

          <div class="tm-log-field">
            <label>Accion</label>
            <select class="form-control" id="tmLogAccion">
              <option value="">Todas</option>
              <?php foreach($accionesLog as $accionFiltro): ?>
                <option value="<?php echo tmLogEsc(strtolower($accionFiltro)); ?>"><?php echo tmLogEsc(tmLogAccionTexto($accionFiltro)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="tm-log-field">
            <label>Modulo</label>
            <select class="form-control" id="tmLogModulo">
              <option value="">Todos</option>
              <?php foreach($modulosLog as $moduloFiltro): ?>
                <option value="<?php echo tmLogEsc(strtolower($moduloFiltro)); ?>"><?php echo tmLogEsc($moduloFiltro); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="tm-log-field">
            <label>Desde</label>
            <input type="date" class="form-control" id="tmLogFechaDesde">
          </div>

          <div class="tm-log-field">
            <label>Hasta</label>
            <input type="date" class="form-control" id="tmLogFechaHasta">
          </div>

          <div class="tm-log-field">
            <label>Mostrar</label>
            <select class="form-control" id="tmLogTamano">
              <option value="25">25</option>
              <option value="50" selected>50</option>
              <option value="100">100</option>
              <option value="0">Todos</option>
            </select>
          </div>
        </div>

        <div class="tm-logs-table-wrap">
          <table class="tm-logs-table" id="tmLogsSistemaTabla">
            <thead>
              <tr>
                <th class="tm-logs-col-index">#</th>
                <th class="tm-logs-col-date">Fecha</th>
                <th class="tm-logs-col-user">Usuario</th>
                <th class="tm-logs-col-role">Rol</th>
                <th class="tm-logs-col-ip">IP</th>
                <th class="tm-logs-col-action">Accion</th>
                <th class="tm-logs-col-module">Modulo</th>
                <th class="tm-logs-col-detail">Detalle</th>
              </tr>
            </thead>
            <tbody>
              <?php if($totalLogs == 0): ?>
                <tr class="tm-log-empty-row"><td class="tm-log-empty" colspan="8">Todavia no hay logs registrados.</td></tr>
              <?php endif; ?>

              <?php foreach($logsSistema as $key => $log): ?>
                <?php
                  $usuario = trim((string)($log["usuario"] ?? "")) ?: "Sistema";
                  $rol = trim((string)($log["rol"] ?? "")) ?: "-";
                  $ip = trim((string)($log["ip"] ?? "")) ?: "-";
                  $accion = trim((string)($log["accion"] ?? "")) ?: "sin_accion";
                  $modulo = trim((string)($log["modulo"] ?? "")) ?: "-";
                  $detalle = trim((string)($log["detalle"] ?? "")) ?: "-";
                  $fecha = $log["fecha"] ?? "";
                  $fechaData = tmLogFechaData($fecha);
                  $inicial = strtoupper(substr($usuario, 0, 1));
                  $estiloInicial = $key >= 50 ? ' style="display:none"' : '';
                ?>
                <tr class="tm-log-row"<?php echo $estiloInicial; ?>
                  data-accion="<?php echo tmLogEsc(strtolower($accion)); ?>"
                  data-modulo="<?php echo tmLogEsc(strtolower($modulo)); ?>"
                  data-fecha="<?php echo tmLogEsc($fechaData); ?>">
                  <td class="tm-logs-col-index"><span class="tm-log-index"><?php echo $key + 1; ?></span></td>
                  <td class="tm-logs-col-date">
                    <div class="tm-log-date">
                      <strong><?php echo tmLogFecha($fecha, "d/m/Y"); ?></strong>
                      <span><?php echo tmLogFecha($fecha, "H:i:s"); ?></span>
                    </div>
                  </td>
                  <td class="tm-logs-col-user">
                    <div class="tm-log-user">
                      <span class="tm-log-avatar"><?php echo tmLogEsc($inicial ?: "S"); ?></span>
                      <div>
                        <b><?php echo tmLogEsc($usuario); ?></b>
                        <span>ID: <?php echo tmLogEsc($log["id_usuario"] ?? "-"); ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="tm-logs-col-role"><?php echo tmLogEsc($rol); ?></td>
                  <td class="tm-logs-col-ip"><?php echo tmLogEsc($ip); ?></td>
                  <td class="tm-logs-col-action">
                    <span class="tm-log-badge <?php echo tmLogBadgeClass($accion); ?>">
                      <i class="fa <?php echo tmLogIcon($accion); ?>"></i> <?php echo tmLogEsc(tmLogAccionTexto($accion)); ?>
                    </span>
                  </td>
                  <td class="tm-logs-col-module"><span class="tm-log-module"><?php echo tmLogEsc($modulo); ?></span></td>
                  <td class="tm-logs-col-detail"><span class="tm-log-detail"><?php echo tmLogEsc($detalle); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="tm-logs-footer">
          <span id="tmLogResultado">Mostrando <?php echo $totalLogs; ?> de <?php echo $totalLogs; ?> registro(s)</span>
          <div class="tm-logs-pager">
            <button type="button" class="tm-logs-page-btn" id="tmLogAnterior"><i class="fa fa-chevron-left"></i> Anterior</button>
            <span class="tm-logs-page-label" id="tmLogPagina">Pagina 1</span>
            <button type="button" class="tm-logs-page-btn" id="tmLogSiguiente">Siguiente <i class="fa fa-chevron-right"></i></button>
            <button type="button" class="tm-logs-reset" id="tmLogLimpiar"><i class="fa fa-eraser"></i> Limpiar filtros</button>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  (function(){
    var buscar = document.getElementById("tmLogBuscar");
    var accion = document.getElementById("tmLogAccion");
    var modulo = document.getElementById("tmLogModulo");
    var desde = document.getElementById("tmLogFechaDesde");
    var hasta = document.getElementById("tmLogFechaHasta");
    var tamano = document.getElementById("tmLogTamano");
    var limpiar = document.getElementById("tmLogLimpiar");
    var resultado = document.getElementById("tmLogResultado");
    var anterior = document.getElementById("tmLogAnterior");
    var siguiente = document.getElementById("tmLogSiguiente");
    var paginaTexto = document.getElementById("tmLogPagina");
    var filas = Array.prototype.slice.call(document.querySelectorAll("#tmLogsSistemaTabla .tm-log-row"));
    var total = filas.length;
    var paginaActual = 1;
    var filtroTimer = null;

    filas.forEach(function(fila){
      fila.tmSearchCache = (fila.textContent || "").toLowerCase();
    });

    function obtenerFiltradas(){
      var texto = (buscar.value || "").toLowerCase().trim();
      var accionValor = (accion.value || "").toLowerCase();
      var moduloValor = (modulo.value || "").toLowerCase();
      var desdeValor = desde.value || "";
      var hastaValor = hasta.value || "";

      return filas.filter(function(fila){
        var okTexto = !texto || (fila.tmSearchCache || "").indexOf(texto) !== -1;
        var okAccion = !accionValor || fila.getAttribute("data-accion") === accionValor;
        var okModulo = !moduloValor || fila.getAttribute("data-modulo") === moduloValor;
        var fecha = fila.getAttribute("data-fecha") || "";
        var okDesde = !desdeValor || (fecha && fecha >= desdeValor);
        var okHasta = !hastaValor || (fecha && fecha <= hastaValor);
        return okTexto && okAccion && okModulo && okDesde && okHasta;
      });
    }

    function renderizarLogs(resetPagina){
      if(resetPagina){
        paginaActual = 1;
      }

      var filtradas = obtenerFiltradas();
      var tamanoValor = parseInt(tamano.value || "50", 10);
      var usarTodo = tamanoValor === 0;
      var paginas = usarTodo ? 1 : Math.max(1, Math.ceil(filtradas.length / tamanoValor));

      if(paginaActual > paginas){
        paginaActual = paginas;
      }

      var inicio = usarTodo ? 0 : (paginaActual - 1) * tamanoValor;
      var fin = usarTodo ? filtradas.length : inicio + tamanoValor;
      var visiblesSet = new Set(filtradas.slice(inicio, fin));

      filas.forEach(function(fila){
        fila.style.display = visiblesSet.has(fila) ? "" : "none";
      });

      var desdeMostrado = filtradas.length ? inicio + 1 : 0;
      var hastaMostrado = Math.min(fin, filtradas.length);

      resultado.textContent = "Mostrando " + desdeMostrado + "-" + hastaMostrado + " de " + filtradas.length + " filtrado(s). Total cargado: " + total;
      paginaTexto.textContent = "Pagina " + paginaActual + " de " + paginas;
      anterior.disabled = paginaActual <= 1;
      siguiente.disabled = paginaActual >= paginas;
    }

    function programarFiltro(){
      window.clearTimeout(filtroTimer);
      filtroTimer = window.setTimeout(function(){
        renderizarLogs(true);
      }, 120);
    }

    [buscar, accion, modulo, desde, hasta, tamano].forEach(function(control){
      if(control){
        control.addEventListener("input", programarFiltro);
        control.addEventListener("change", programarFiltro);
      }
    });

    if(anterior){
      anterior.addEventListener("click", function(){
        paginaActual--;
        renderizarLogs(false);
      });
    }

    if(siguiente){
      siguiente.addEventListener("click", function(){
        paginaActual++;
        renderizarLogs(false);
      });
    }

    if(limpiar){
      limpiar.addEventListener("click", function(){
        buscar.value = "";
        accion.value = "";
        modulo.value = "";
        desde.value = "";
        hasta.value = "";
        tamano.value = "50";
        renderizarLogs(true);
      });
    }

    renderizarLogs(true);
  })();
</script>
