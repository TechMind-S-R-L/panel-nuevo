<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "vendedor" && $_SESSION["rol"] != "cajero"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$servicios = ControladorServicios::ctrMostrarServicios();
$servicios = is_array($servicios) ? $servicios : array();

function tmServicioTexto($valor, $fallback = "-"){
  $valor = trim((string)$valor);
  return htmlspecialchars($valor !== "" ? $valor : $fallback, ENT_QUOTES, "UTF-8");
}

function tmServicioFecha($valor){
  if(empty($valor) || $valor == "0000-00-00" || $valor == "0000-00-00 00:00:00"){
    return "-";
  }
  $timestamp = strtotime($valor);
  return $timestamp ? date("d/m/Y H:i", $timestamp) : $valor;
}

function tmServicioEstadoTexto($estado){
  $mapa = array(
    "pendiente" => "Pendiente",
    "pendiente_almacen" => "Pendiente almacen",
    "pendiente_adelanto" => "Pendiente adelanto",
    "pendiente_final" => "Pendiente pago final",
    "pendiente_retiro" => "Pendiente retiro",
    "asignado" => "Asignado",
    "en_almacen" => "En almacen",
    "atendiendo" => "Atendiendo",
    "retiro_solicitado" => "Retiro solicitado",
    "en_proceso" => "En proceso",
    "diagnosticado" => "Diagnosticado",
    "autorizado" => "Autorizado",
    "rep_solicitado" => "Repuesto solicitado",
    "rep_entregado" => "Repuesto entregado",
    "reparado" => "Reparado",
    "retorno_almacen" => "Retorno a almacen",
    "listo_cobro" => "Listo para cobro",
    "pagado_retiro" => "Pagado para retiro",
    "en_desarrollo" => "En desarrollo",
    "pagado_final" => "Pago final cobrado",
    "completado" => "Completado",
    "cancelado" => "Cancelado"
  );
  return $mapa[$estado] ?? ucwords(str_replace("_", " ", (string)$estado));
}

function tmServicioPagoTexto($estado){
  $mapa = array(
    "pendiente" => "Pendiente de cobro",
    "pendiente_adelanto" => "Pendiente adelanto",
    "pendiente_final" => "Pendiente pago final",
    "pendiente_retiro" => "Pendiente retiro",
    "adelanto_pagado" => "Adelanto pagado",
    "aprobado" => "Aprobado",
    "rechazado" => "Rechazado"
  );
  return $mapa[$estado] ?? ucwords(str_replace("_", " ", (string)$estado));
}

function tmServicioBadgeClase($estado, $tipo = "servicio"){
  if(in_array($estado, array("completado", "aprobado", "pagado_final"))){
    return "success";
  }
  if(in_array($estado, array("cancelado", "rechazado"))){
    return "danger";
  }
  if(in_array($estado, array("pendiente", "pendiente_almacen", "pendiente_adelanto", "pendiente_final", "pendiente_retiro", "listo_cobro"))){
    return "warning";
  }
  if(in_array($estado, array("adelanto_pagado", "en_desarrollo", "en_proceso", "atendiendo", "asignado", "autorizado", "diagnosticado", "rep_solicitado", "rep_entregado", "reparado", "retorno_almacen", "pagado_retiro"))){
    return "info";
  }
  return $tipo == "pago" ? "primary" : "default";
}

function tmServicioGrupo($servicio){
  $estadoServicio = $servicio["estado_servicio"] ?? "";
  $estadoPago = $servicio["estado_pago"] ?? "";

  if($estadoServicio == "cancelado" || $estadoPago == "rechazado"){
    return "cancelados";
  }
  if($estadoServicio == "completado"){
    return "completados";
  }
  if(strpos($estadoPago, "pendiente") === 0 || strpos($estadoServicio, "pendiente") === 0 || $estadoServicio == "listo_cobro"){
    return "pendientes";
  }
  return "proceso";
}

$serviciosVista = array();
$contadores = array("pendientes" => 0, "proceso" => 0, "completados" => 0, "cancelados" => 0);

foreach($servicios as $key => $servicio){
  $cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
  $tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;
  $grupo = tmServicioGrupo($servicio);
  $contadores[$grupo]++;
  $serviciosVista[] = array(
    "indice" => $key + 1,
    "servicio" => $servicio,
    "cliente" => $cliente,
    "tecnico" => $tecnico,
    "grupo" => $grupo
  );
}
?>

<div class="content-wrapper servicio-admin-page">
  <style>
    .servicio-admin-page{background:#eef3f7 !important;}
    .servicios-hero{
      background:#163140;
      color:#fff;
      padding:18px 20px;
      border-radius:8px;
      margin-bottom:16px;
      display:flex;
      justify-content:space-between;
      gap:16px;
      align-items:center;
      flex-wrap:wrap;
      box-shadow:0 14px 34px rgba(22,49,64,.12);
    }
    .servicios-hero h2{margin:0 0 6px;font-size:24px;font-weight:800;}
    .servicios-hero p{margin:0;color:#c8d7df;}
    .servicios-hero-stat{
      background:rgba(255,255,255,.1);
      border:1px solid rgba(255,255,255,.18);
      border-radius:8px;
      padding:10px 14px;
      min-width:150px;
      text-align:center;
    }
    .servicios-hero-stat span{display:block;font-size:11px;font-weight:800;text-transform:uppercase;color:#dceaf1;}
    .servicios-hero-stat strong{display:block;font-size:26px;line-height:1;color:#fff;}
    .servicio-panel{
      background:rgba(255,255,255,.92);
      border:1px solid #dbe5ec;
      border-radius:10px;
      box-shadow:0 12px 30px rgba(22,49,64,.08);
      overflow:hidden;
    }
    .servicio-toolbar{
      padding:14px 16px;
      border-bottom:1px solid #e5edf2;
      display:flex;
      justify-content:space-between;
      gap:12px;
      align-items:center;
      flex-wrap:wrap;
    }
    .servicio-toolbar h3{margin:0;font-size:18px;font-weight:800;color:#1f2d3d;}
    .servicio-search{
      position:relative;
      flex:1 1 320px;
      max-width:520px;
    }
    .servicio-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#6f8190;
    }
    .servicio-search input{
      height:42px;
      padding-left:38px;
      border:1px solid #d7e1e8;
      border-radius:7px;
      box-shadow:none;
    }
    .servicio-tabs{
      padding:0 16px;
      border-bottom:1px solid #e5edf2;
      background:#fbfdff;
    }
    .servicio-tabs.nav-tabs>li>a{
      border:0;
      border-radius:0;
      color:#60717f;
      font-weight:800;
      padding:14px 10px;
    }
    .servicio-tabs.nav-tabs>li.active>a,
    .servicio-tabs.nav-tabs>li.active>a:focus,
    .servicio-tabs.nav-tabs>li.active>a:hover{
      border:0;
      border-bottom:3px solid #3c8dbc;
      color:#163140;
      background:transparent;
    }
    .servicio-tab-count{
      display:inline-flex;
      min-width:22px;
      height:22px;
      border-radius:999px;
      align-items:center;
      justify-content:center;
      margin-left:5px;
      background:#eaf5fb;
      color:#176b9b;
      font-size:11px;
      font-weight:900;
    }
    .servicio-card-grid{
      padding:12px;
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(245px, 1fr));
      gap:10px;
    }
    .servicio-card{
      position:relative;
      border:1px solid #dbe5ec;
      border-radius:10px;
      background:#fff;
      box-shadow:0 10px 26px rgba(22,49,64,.07);
      padding:11px;
      min-height:212px;
      cursor:pointer;
      display:flex;
      flex-direction:column;
      gap:9px;
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      overflow:hidden;
    }
    .servicio-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#3c8dbc;
    }
    .servicio-card.estado-pendientes:before{background:#f39c12;}
    .servicio-card.estado-proceso:before{background:#00c0ef;}
    .servicio-card.estado-completados:before{background:#00a65a;}
    .servicio-card.estado-cancelados:before{background:#dd4b39;}
    .servicio-card:hover{
      border-color:#3c8dbc;
      box-shadow:0 16px 34px rgba(22,49,64,.14);
      transform:translateY(-2px);
    }
    .servicio-card-head{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:9px;
      padding-left:4px;
    }
    .servicio-card-code{
      display:inline-flex;
      align-items:center;
      gap:6px;
      color:#176b9b;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .servicio-card h4{
      margin:4px 0 0;
      color:#1f2d3d;
      font-size:14px;
      font-weight:900;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .servicio-card-total{
      min-width:78px;
      text-align:right;
      color:#163140;
      font-size:15px;
      font-weight:900;
      line-height:1.1;
    }
    .servicio-card-total span{
      display:block;
      color:#7b8b96;
      font-size:10px;
      text-transform:uppercase;
      font-weight:800;
    }
    .servicio-card-body{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:6px;
    }
    .servicio-card-item{
      border:1px solid #edf2f6;
      background:#f8fbfd;
      border-radius:8px;
      padding:7px;
      min-height:50px;
    }
    .servicio-card-item.full{grid-column:1 / -1;}
    .servicio-card-item span{
      display:block;
      color:#7b8b96;
      font-size:10px;
      text-transform:uppercase;
      font-weight:900;
      margin-bottom:4px;
    }
    .servicio-card-item strong,
    .servicio-card-item p{
      margin:0;
      color:#263845;
      font-size:11px;
      font-weight:800;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .servicio-card-footer{
      margin-top:auto;
      padding-top:8px;
      border-top:1px solid #edf2f6;
      display:grid;
      grid-template-columns:1fr;
      gap:7px;
    }
    .servicio-card-footer .label{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      max-width:100%;
      min-height:24px;
      white-space:normal;
      line-height:1.1;
      padding:5px 8px;
      border-radius:999px;
      margin:0 4px 4px 0;
    }
    .servicio-actions{
      width:100%;
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(34px, 1fr));
      align-items:center;
      gap:5px;
    }
    .servicio-actions .btn{
      width:100%;
      border-radius:7px;
      font-size:11px;
      font-weight:800;
      padding:6px 7px;
      overflow:hidden;
    }
    .servicio-empty{
      grid-column:1 / -1;
      min-height:140px;
      border:1px dashed #c6d4df;
      border-radius:8px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:#f8fbfd;
      color:#7d8c96;
      font-weight:800;
      text-align:center;
      padding:18px;
    }
    .servicio-detail-modal .modal-dialog{max-width:900px;width:92%;}
    .servicio-detail-modal .modal-content{
      border:0;
      border-radius:16px;
      overflow:hidden;
      box-shadow:0 28px 80px rgba(10,30,45,.34);
      background:#f4f8fb;
    }
    .servicio-detail-modal .modal-header{
      position:relative;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 62%,#36aee2 100%);
      color:#fff;
      border:0;
      padding:17px 22px;
      min-height:92px;
      overflow:hidden;
    }
    .servicio-detail-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-58px;
      top:-78px;
      width:210px;
      height:210px;
      border-radius:50%;
      background:rgba(255,255,255,.11);
    }
    .servicio-detail-modal .modal-header .close{
      position:relative;
      z-index:3;
      color:#fff;
      opacity:.9;
      text-shadow:none;
      width:32px;
      height:32px;
      border-radius:50%;
      background:rgba(255,255,255,.16);
      line-height:30px;
    }
    .servicio-detail-modal .modal-body{
      padding:14px;
    }
    .servicio-modal-title{
      position:relative;
      z-index:2;
      display:flex;
      align-items:center;
      gap:12px;
      max-width:88%;
    }
    .servicio-modal-icon{
      width:46px;
      height:46px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.18);
      box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
      font-size:20px;
      flex:0 0 46px;
    }
    .servicio-modal-title h4{margin:0;font-size:20px;font-weight:900;line-height:1.2;overflow-wrap:anywhere;}
    .servicio-modal-title p{margin:3px 0 0;color:#eaf7ff;font-weight:700;}
    .servicio-modal-kicker{
      display:inline-flex;
      align-items:center;
      gap:6px;
      min-height:24px;
      padding:3px 9px;
      border-radius:999px;
      background:rgba(255,255,255,.16);
      color:#eff9ff;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      letter-spacing:.04em;
      margin-bottom:4px;
    }
    .servicio-modal-summary{
      display:grid;
      grid-template-columns:1.35fr 1fr 1fr 1fr;
      gap:8px;
      margin-bottom:10px;
    }
    .servicio-summary-card{
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:10px;
      min-height:66px;
      box-shadow:0 8px 18px rgba(22,49,64,.05);
    }
    .servicio-summary-card span,
    .servicio-detail-box span,
    .servicio-modal-text-card span,
    .servicio-modal-block-title{
      display:block;
      color:#7b8b96;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .servicio-summary-card strong,
    .servicio-summary-card p{
      color:#263845;
      font-size:13px;
      font-weight:900;
      margin:0;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .servicio-summary-card.total strong{
      color:#176b9b;
      font-size:18px;
    }
    .servicio-modal-status-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:8px;
      flex-wrap:wrap;
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:9px 10px;
      margin-bottom:10px;
    }
    .servicio-status-group{
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
    }
    .servicio-modal-pill{
      display:inline-flex;
      align-items:center;
      gap:7px;
      min-height:28px;
      padding:5px 9px;
      border-radius:999px;
      font-size:11px;
      font-weight:900;
      color:#fff;
      background:#3c8dbc;
    }
    .servicio-modal-pill.warning{background:#f39c12;}
    .servicio-modal-pill.success{background:#00a65a;}
    .servicio-modal-pill.info{background:#00acd6;}
    .servicio-modal-pill.danger{background:#dd4b39;}
    .servicio-modal-pill.default{background:#607d8b;}
    .servicio-modal-section{
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:11px;
      margin-top:10px;
      box-shadow:0 8px 18px rgba(22,49,64,.04);
    }
    .servicio-detail-grid{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:8px;
    }
    .servicio-detail-box{
      border:1px solid #e2ebf2;
      border-radius:9px;
      background:#f8fbfd;
      padding:9px 10px;
      min-height:62px;
    }
    .servicio-detail-box.full{grid-column:1 / -1;}
    .servicio-detail-box strong,
    .servicio-detail-box p{
      color:#263845;
      font-size:13px;
      font-weight:800;
      margin:0;
      overflow-wrap:anywhere;
      white-space:pre-wrap;
    }
    .servicio-modal-text-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
      margin-top:10px;
    }
    .servicio-modal-text-card{
      border:1px solid #e2ebf2;
      border-radius:10px;
      background:#fbfdff;
      padding:10px;
      min-height:92px;
    }
    .servicio-modal-text-card.full{grid-column:1 / -1;}
    .servicio-modal-text-card p{
      margin:0;
      color:#263845;
      font-weight:800;
      line-height:1.45;
      white-space:pre-wrap;
      overflow-wrap:anywhere;
    }
    .servicio-modal-actions{
      margin-top:10px;
      padding:10px;
      border:1px solid #dce8f1;
      border-radius:12px;
      background:#fff;
      display:flex;
      justify-content:flex-end;
      gap:6px;
      flex-wrap:wrap;
    }
    .servicio-modal-actions:before{
      content:"Acciones disponibles";
      margin-right:auto;
      color:#60717f;
      font-size:12px;
      font-weight:900;
      text-transform:uppercase;
      align-self:center;
    }
    .servicio-modal-actions .btn{
      border-radius:8px;
      font-weight:900;
      padding:6px 10px;
    }
    @media(max-width:991px){
      .servicio-modal-summary{grid-template-columns:1fr 1fr;}
      .servicio-detail-grid{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:767px){
      .servicio-card-grid{grid-template-columns:1fr;padding:12px;}
      .servicio-modal-summary,
      .servicio-modal-text-grid{grid-template-columns:1fr;}
      .servicio-detail-grid{grid-template-columns:1fr;}
      .servicios-hero-stat{width:100%;}
      .servicio-tabs.nav-tabs>li{float:none;}
    }
  </style>

  <section class="content-header">
    <h1>Administrar servicios</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Administrar servicios</li>
    </ol>
  </section>

  <section class="content">
    <div class="servicios-hero">
      <div>
        <h2>Servicios registrados</h2>
        <p>Revise el avance, el cobro y los documentos emitidos para cada servicio.</p>
      </div>
      <div class="servicios-hero-stat">
        <span>Total servicios</span>
        <strong><?php echo count($serviciosVista); ?></strong>
      </div>
    </div>

    <div class="servicio-panel">
      <div class="servicio-toolbar">
        <h3><i class="fa fa-wrench"></i> Seguimiento de servicios</h3>
        <div class="servicio-search">
          <i class="fa fa-search"></i>
          <input type="text" class="form-control" id="buscarServicioCards" placeholder="Buscar por codigo, cliente, tecnico o servicio">
        </div>
      </div>

      <ul class="nav nav-tabs servicio-tabs" role="tablist">
        <li class="active"><a href="#serviciosPendientes" data-toggle="tab">Pendientes <span class="servicio-tab-count"><?php echo $contadores["pendientes"]; ?></span></a></li>
        <li><a href="#serviciosProceso" data-toggle="tab">En atencion <span class="servicio-tab-count"><?php echo $contadores["proceso"]; ?></span></a></li>
        <li><a href="#serviciosCompletados" data-toggle="tab">Completados <span class="servicio-tab-count"><?php echo $contadores["completados"]; ?></span></a></li>
        <?php if($contadores["cancelados"] > 0): ?>
          <li><a href="#serviciosCancelados" data-toggle="tab">Cancelados <span class="servicio-tab-count"><?php echo $contadores["cancelados"]; ?></span></a></li>
        <?php endif; ?>
      </ul>

      <div class="tab-content">
        <?php
          $tabsServicio = array(
            "pendientes" => array("id" => "serviciosPendientes", "empty" => "No hay servicios pendientes de cobro o asignacion."),
            "proceso" => array("id" => "serviciosProceso", "empty" => "No hay servicios en atencion."),
            "completados" => array("id" => "serviciosCompletados", "empty" => "No hay servicios completados."),
            "cancelados" => array("id" => "serviciosCancelados", "empty" => "No hay servicios cancelados.")
          );
        ?>
        <?php foreach($tabsServicio as $grupoServicio => $tabServicio): ?>
          <?php if($grupoServicio == "cancelados" && $contadores["cancelados"] == 0) continue; ?>
          <div class="tab-pane <?php echo $grupoServicio == "pendientes" ? "active" : ""; ?>" id="<?php echo $tabServicio["id"]; ?>">
            <div class="servicio-card-grid">
              <?php if($contadores[$grupoServicio] == 0): ?>
                <div class="servicio-empty"><?php echo $tabServicio["empty"]; ?></div>
              <?php endif; ?>

              <?php foreach($serviciosVista as $itemServicio): ?>
                <?php
                  if($itemServicio["grupo"] != $grupoServicio) continue;

                  $servicio = $itemServicio["servicio"];
                  $cliente = $itemServicio["cliente"];
                  $tecnico = $itemServicio["tecnico"];
                  $estadoPago = $servicio["estado_pago"] ?? "";
                  $estadoServicio = $servicio["estado_servicio"] ?? "";
                  $clienteNombre = $cliente["nombre"] ?? "Sin cliente";
                  $tecnicoNombre = $tecnico["nombre"] ?? "Pendiente de asignacion";
                  $servicioTexto = trim(($servicio["tipo_servicio"] ?? "")." / ".($servicio["tipo_instalacion"] ?? ""));
                  $searchServicio = strtolower(trim(($servicio["codigo"] ?? "")." ".$clienteNombre." ".$tecnicoNombre." ".$servicioTexto." ".$estadoPago." ".$estadoServicio));

                  $detalleServicio = array(
                    "id" => (int)$servicio["id"],
                    "codigo" => (string)($servicio["codigo"] ?? ""),
                    "cliente" => (string)$clienteNombre,
                    "tecnico" => (string)$tecnicoNombre,
                    "servicio" => (string)($servicio["tipo_servicio"] ?? ""),
                    "tipo" => (string)($servicio["tipo_instalacion"] ?? ""),
                    "camaras" => (string)($servicio["cantidad_camaras"] ?? 0),
                    "metros" => (string)($servicio["metros_distancia"] ?? 0),
                    "canalizacion" => (string)($servicio["metros_canalizacion"] ?? 0),
                    "total" => "Bs ".number_format((float)($servicio["total"] ?? 0), 2),
                    "pago" => tmServicioPagoTexto($estadoPago),
                    "estado" => tmServicioEstadoTexto($estadoServicio),
                    "direccion" => (string)($servicio["direccion_instalacion"] ?? ""),
                    "referencia" => (string)($servicio["referencia"] ?? ""),
                    "ubicacion" => trim(($servicio["latitud"] ?? "").", ".($servicio["longitud"] ?? ""), ", "),
                    "preguntas" => (string)($servicio["preguntas_cliente"] ?? ""),
                    "diagnostico" => (string)($servicio["diagnostico_inicial"] ?? ""),
                    "observaciones" => (string)($servicio["observaciones"] ?? ""),
                    "fecha" => tmServicioFecha($servicio["fecha"] ?? ""),
                    "fecha_pago" => tmServicioFecha($servicio["fecha_pago"] ?? "")
                  );
                  $detalleJson = htmlspecialchars(json_encode($detalleServicio, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8");
                ?>
                <article class="servicio-card servicioCardDetalle estado-<?php echo $grupoServicio; ?>" data-search="<?php echo htmlspecialchars($searchServicio, ENT_QUOTES, "UTF-8"); ?>" data-servicio="<?php echo $detalleJson; ?>">
                  <div class="servicio-card-head">
                    <div>
                      <span class="servicio-card-code"><i class="fa fa-hashtag"></i> <?php echo tmServicioTexto($servicio["codigo"] ?? ""); ?></span>
                      <h4><?php echo tmServicioTexto($servicioTexto); ?></h4>
                    </div>
                    <div class="servicio-card-total">
                      <span>Total</span>
                      Bs <?php echo number_format((float)($servicio["total"] ?? 0), 2); ?>
                    </div>
                  </div>

                  <div class="servicio-card-body">
                    <div class="servicio-card-item full">
                      <span>Cliente</span>
                      <strong><?php echo tmServicioTexto($clienteNombre); ?></strong>
                    </div>
                    <div class="servicio-card-item">
                      <span>Tecnico</span>
                      <p><?php echo tmServicioTexto($tecnicoNombre); ?></p>
                    </div>
                    <div class="servicio-card-item">
                      <span>Fecha</span>
                      <p><?php echo tmServicioTexto(tmServicioFecha($servicio["fecha"] ?? "")); ?></p>
                    </div>
                  </div>

                  <div class="servicio-card-footer">
                    <div>
                      <span class="label label-<?php echo tmServicioBadgeClase($estadoPago, "pago"); ?>"><?php echo tmServicioTexto(tmServicioPagoTexto($estadoPago)); ?></span>
                      <span class="label label-<?php echo tmServicioBadgeClase($estadoServicio); ?>"><?php echo tmServicioTexto(tmServicioEstadoTexto($estadoServicio)); ?></span>
                    </div>
                    <div class="servicio-actions">
                      <button class="btn btn-default btnImprimirBoletaServicio" title="Imprimir boleta de servicio" idServicio="<?php echo (int)$servicio["id"]; ?>"><i class="fa fa-print"></i> Boleta</button>
                      <?php if($estadoPago == "aprobado"): ?>
                        <button class="btn btn-success btnImprimirNotaServicio" title="Imprimir nota de venta" idServicio="<?php echo (int)$servicio["id"]; ?>"><i class="fa fa-file-text-o"></i> Nota</button>
                        <button class="btn btn-info btnImprimirOrdenServicio" title="Imprimir orden de servicio" idServicio="<?php echo (int)$servicio["id"]; ?>"><i class="fa fa-wrench"></i> Orden</button>
                      <?php endif; ?>
                      <?php if($_SESSION["perfil"] == "Administrador"): ?>
                        <button class="btn btn-danger btnEliminarServicio" title="Eliminar todo el registro del servicio" idServicio="<?php echo (int)$servicio["id"]; ?>"><i class="fa fa-trash"></i></button>
                      <?php endif; ?>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleServicioAdmin" class="modal fade servicio-detail-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="servicio-modal-title">
          <div class="servicio-modal-icon"><i class="fa fa-wrench"></i></div>
          <div>
            <span class="servicio-modal-kicker" id="servicioModalCodigo">Servicio</span>
            <h4 id="servicioModalTitulo">Detalle del servicio</h4>
            <p id="servicioModalSubtitulo">Tipo de servicio</p>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div class="servicio-modal-summary">
          <div class="servicio-summary-card">
            <span>Cliente</span>
            <strong id="servicioModalCliente">-</strong>
          </div>
          <div class="servicio-summary-card">
            <span>Tecnico asignado</span>
            <p id="servicioModalTecnico">-</p>
          </div>
          <div class="servicio-summary-card total">
            <span>Total</span>
            <strong id="servicioModalTotal">-</strong>
          </div>
          <div class="servicio-summary-card">
            <span>Fecha registro</span>
            <p id="servicioModalFecha">-</p>
          </div>
        </div>

        <div class="servicio-modal-status-row">
          <div class="servicio-status-group">
            <span class="servicio-modal-pill default" id="servicioModalPago">-</span>
            <span class="servicio-modal-pill default" id="servicioModalEstado">-</span>
          </div>
          <div class="servicio-status-group">
            <span class="servicio-modal-pill default" id="servicioModalFechaPago">Cobro: -</span>
          </div>
        </div>

        <div class="servicio-modal-section">
          <span class="servicio-modal-block-title">Datos del servicio</span>
          <div class="servicio-detail-grid">
            <div class="servicio-detail-box"><span>Camaras</span><p id="servicioModalCamaras">-</p></div>
            <div class="servicio-detail-box"><span>Metros distancia</span><p id="servicioModalMetros">-</p></div>
            <div class="servicio-detail-box"><span>Metros canalizacion</span><p id="servicioModalCanalizacion">-</p></div>
          </div>
        </div>

        <div class="servicio-modal-text-grid">
          <div class="servicio-modal-text-card full"><span>Direccion / referencia</span><p id="servicioModalDireccion">-</p></div>
          <div class="servicio-modal-text-card"><span>Preguntas al cliente</span><p id="servicioModalPreguntas">-</p></div>
          <div class="servicio-modal-text-card"><span>Diagnostico inicial / alcance</span><p id="servicioModalDiagnostico">-</p></div>
          <div class="servicio-modal-text-card full"><span>Observaciones</span><p id="servicioModalObservaciones">-</p></div>
        </div>

        <div class="servicio-modal-actions" id="servicioModalAcciones"></div>
      </div>
    </div>
  </div>
</div>

<script>
function tmServicioValor(valor){
  return valor && String(valor).trim() !== "" ? valor : "-";
}

function tmServicioClasePill(texto){
  texto = (texto || "").toLowerCase();
  if(texto.indexOf("pendiente") !== -1 || texto.indexOf("solicitado") !== -1 || texto.indexOf("esperando") !== -1){
    return "warning";
  }
  if(texto.indexOf("aprobado") !== -1 || texto.indexOf("completado") !== -1 || texto.indexOf("pagado") !== -1 || texto.indexOf("entregado") !== -1){
    return "success";
  }
  if(texto.indexOf("cancelado") !== -1 || texto.indexOf("rechazado") !== -1 || texto.indexOf("anulado") !== -1){
    return "danger";
  }
  if(texto.indexOf("proceso") !== -1 || texto.indexOf("atencion") !== -1 || texto.indexOf("desarrollo") !== -1 || texto.indexOf("asignado") !== -1){
    return "info";
  }
  return "default";
}

function tmServicioPintarPill($elemento, texto, icono){
  var valor = tmServicioValor(texto);
  var clase = tmServicioClasePill(valor);
  $elemento
    .removeClass("warning success info danger default")
    .addClass(clase)
    .html('<i class="fa ' + icono + '"></i> ' + valor);
}

function tmServicioSetDetalle(detalle){
  $("#servicioModalCodigo").html('<i class="fa fa-hashtag"></i> ' + tmServicioValor(detalle.codigo));
  $("#servicioModalTitulo").text(tmServicioValor(detalle.servicio));
  $("#servicioModalSubtitulo").text(tmServicioValor(detalle.tipo));
  $("#servicioModalCliente").text(tmServicioValor(detalle.cliente));
  $("#servicioModalTecnico").text(tmServicioValor(detalle.tecnico));
  $("#servicioModalTotal").text(tmServicioValor(detalle.total));
  tmServicioPintarPill($("#servicioModalPago"), detalle.pago, "fa-money");
  tmServicioPintarPill($("#servicioModalEstado"), detalle.estado, "fa-wrench");
  $("#servicioModalFecha").text(tmServicioValor(detalle.fecha));
  $("#servicioModalFechaPago").html('<i class="fa fa-calendar-check-o"></i> Cobro: ' + tmServicioValor(detalle.fecha_pago));
  $("#servicioModalCamaras").text(tmServicioValor(detalle.camaras));
  $("#servicioModalMetros").text(tmServicioValor(detalle.metros));
  $("#servicioModalCanalizacion").text(tmServicioValor(detalle.canalizacion));
  $("#servicioModalDireccion").text("Direccion: " + tmServicioValor(detalle.direccion) + "\nReferencia: " + tmServicioValor(detalle.referencia) + "\nUbicacion: " + tmServicioValor(detalle.ubicacion));
  $("#servicioModalPreguntas").text(tmServicioValor(detalle.preguntas));
  $("#servicioModalDiagnostico").text(tmServicioValor(detalle.diagnostico));
  $("#servicioModalObservaciones").text(tmServicioValor(detalle.observaciones));
}

$(document).on("click", ".servicioCardDetalle", function(e){
  if($(e.target).closest("button, a, .btn").length){
    return;
  }

  var detalle = $(this).attr("data-servicio");
  try{
    detalle = JSON.parse(detalle);
  }catch(error){
    detalle = {};
  }

  tmServicioSetDetalle(detalle);
  $("#servicioModalAcciones").html($(this).find(".servicio-actions").html());
  $("#modalDetalleServicioAdmin").modal("show");
});

$(document).on("input", "#buscarServicioCards", function(){
  var busqueda = ($(this).val() || "").toLowerCase().trim();

  $(".servicioCardDetalle").each(function(){
    var coincide = ($(this).attr("data-search") || "").indexOf(busqueda) !== -1;
    $(this).toggle(coincide);
  });
});

$(document).on("click", ".btnImprimirBoletaServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});

$(document).on("click", ".btnImprimirOrdenServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/orden-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});

$(document).on("click", ".btnImprimirNotaServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/nota-venta-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});

$(document).on("click", ".btnEliminarServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  e.stopImmediatePropagation();
  var idServicio = $(this).attr("idServicio");
  swal({
    title: "Eliminar servicio completo?",
    text: "Se eliminaran tambien equipo de taller y repuestos vinculados.",
    type: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonText: "Cancelar",
    confirmButtonText: "Si, eliminar"
  }).then(function(result){
    if(result.value){
      window.location = "index.php?ruta=administrar-servicios&eliminarServicio=" + idServicio;
    }
  });
});
</script>

<?php ControladorServicios::ctrEliminarServicioCompleto(); ?>
