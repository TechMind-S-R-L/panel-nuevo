<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$serviciosPendientes = ControladorServicios::ctrMostrarServiciosPendientesPago();
$serviciosCobrados = ControladorServicios::ctrMostrarServiciosCobrados();
$serviciosPendientes = is_array($serviciosPendientes) ? $serviciosPendientes : array();
$serviciosCobrados = is_array($serviciosCobrados) ? $serviciosCobrados : array();

$totalPendientePagoServicios = array_sum(array_map(function($servicio){
  list($montoACobrar) = datosCobroServicio($servicio);
  return (float)$montoACobrar;
}, $serviciosPendientes));

$totalCobradoPagoServicios = array_sum(array_map(function($servicio){
  return (float)($servicio["total"] ?? 0);
}, $serviciosCobrados));

function tmCobroServicioEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function etiquetaCobroServicio($servicio){
  $estadoServicio = $servicio["estado_servicio"] ?? "";
  $esSoftware = ($servicio["tipo_servicio"] ?? "") == "Desarrollo de software";
  if($esSoftware && ($servicio["estado_pago"] ?? "") == "pendiente_adelanto"){
    $proyecto = ControladorProyectos::ctrMostrarProyectoPorServicio($servicio["id"]);
    if($proyecto && (float)($proyecto["pago_adelanto"] ?? 0) > 0){
      return '<span class="label label-warning">Adelanto parcial</span>';
    }
  }
  if(($servicio["estado_pago"] ?? "") == "adelanto_pagado"){
    return '<span class="label label-primary">Adelanto cobrado</span>';
  }
  if(($servicio["estado_pago"] ?? "") == "pendiente_final"){
    return '<span class="label label-warning">Pendiente pago final</span>';
  }
  if(($servicio["estado_pago"] ?? "") == "aprobado"){
    if(($servicio["tipo_servicio"] ?? "") == "Soporte tecnico en taller" && $estadoServicio == "pagado_retiro"){
      return '<span class="label label-info">Pagado - retirar en almacen</span>';
    }
    if($estadoServicio == "completado"){
      return '<span class="label label-success">Completado</span>';
    }
    return '<span class="label label-primary">Cobrado</span>';
  }
  return '<span class="label label-warning">Pendiente de cobro</span>';
}

function claseCobroServicio($servicio){
  $estadoPago = $servicio["estado_pago"] ?? "";
  $estadoServicio = $servicio["estado_servicio"] ?? "";

  if($estadoPago == "aprobado" && $estadoServicio == "completado"){
    return "success";
  }
  if($estadoPago == "aprobado" || $estadoPago == "adelanto_pagado"){
    return "info";
  }
  if($estadoPago == "pendiente_final"){
    return "warning";
  }
  return "warning";
}

function datosCobroServicio($servicio){
  $esSoftware = ($servicio["tipo_servicio"] ?? "") == "Desarrollo de software";
  $proyectoSoftware = $esSoftware ? ControladorProyectos::ctrMostrarProyectoPorServicio($servicio["id"]) : null;
  $montoACobrar = (float)$servicio["total"];
  $conceptoCobro = "Total";
  $desarrollador = "";

  if($esSoftware && $proyectoSoftware){
    $desarrollador = $proyectoSoftware["desarrollador"] ?? "";
    if(($servicio["estado_pago"] ?? "") == "pendiente_final"){
      $montoACobrar = (float)$proyectoSoftware["saldo_pendiente"];
      $conceptoCobro = "Saldo final";
    }else if(($servicio["estado_pago"] ?? "") == "pendiente_adelanto"){
      $adelantoPactado = (float)($proyectoSoftware["monto_adelanto"] ?? 0);
      $adelantoPagado = (float)($proyectoSoftware["pago_adelanto"] ?? 0);
      $montoACobrar = max(0, $adelantoPactado - $adelantoPagado);
      if($montoACobrar <= 0){
        $montoACobrar = $adelantoPactado;
      }
      $conceptoCobro = ($adelantoPagado > 0 ? "Saldo de adelanto " : "Adelanto ").number_format((float)$proyectoSoftware["porcentaje_adelanto"], 2)."%";
    }
  }

  return array($montoACobrar, $conceptoCobro, $proyectoSoftware, $desarrollador);
}

function infoTallerCobroServicio($servicio){
  if(($servicio["tipo_servicio"] ?? "") != "Soporte tecnico en taller"){
    return array(
      "equipo" => "",
      "diagnostico" => "",
      "notificacion" => "",
      "reparacion" => "",
      "repuestos" => "",
      "garantia" => "",
      "evidencias" => "[]"
    );
  }

  $equipo = ControladorServicios::ctrMostrarEquipoTaller($servicio["id"]);
  $repuestos = ControladorServicios::ctrMostrarRepuestosTaller($servicio["id"]);
  if(!is_array($repuestos)){
    $repuestos = array();
  }
  $resumenRepuestos = array();
  foreach($repuestos as $repuesto){
    if(($repuesto["estado"] ?? "") == "entregado"){
      $resumenRepuestos[] = ($repuesto["descripcion"] ?? "Repuesto")." x ".(int)($repuesto["cantidad"] ?? 0)." - Bs ".number_format((float)($repuesto["subtotal"] ?? 0), 2);
    }
  }

  $evidencias = json_decode($equipo["evidencias_tecnicas"] ?? "[]", true);
  if(!is_array($evidencias)){
    $evidencias = array();
  }

  return array(
    "equipo" => trim(($equipo["tipo_equipo"] ?? "")." ".($equipo["marca"] ?? "")." ".($equipo["modelo"] ?? "")),
    "diagnostico" => $equipo["diagnostico_tecnico"] ?? "",
    "notificacion" => $equipo["detalle_notificacion"] ?? "",
    "reparacion" => $equipo["reparacion_realizada"] ?? "",
    "repuestos" => count($resumenRepuestos) ? implode("\n", $resumenRepuestos) : ($equipo["repuestos_detalle"] ?? ""),
    "garantia" => $equipo["garantia_detalle"] ?? "",
    "evidencias" => json_encode($evidencias)
  );
}

function accionesCobroServicio($servicio, $modo){
  $idServicio = (int)$servicio["id"];
  $esTaller = ($servicio["tipo_servicio"] ?? "") == "Soporte tecnico en taller";
  $esSoftware = ($servicio["tipo_servicio"] ?? "") == "Desarrollo de software";
  list($montoACobrar, $conceptoCobro, $proyectoSoftware) = datosCobroServicio($servicio);
  $manoObraActual = (float)($servicio["costo_mano_obra"] ?? 0);
  $baseRepuestos = $esTaller ? max(0, (float)$servicio["total"] - $manoObraActual) : 0;
  $infoTaller = infoTallerCobroServicio($servicio);
  $pagosServicio = $esSoftware ? ModeloServicios::mdlMostrarPagosServicio($idServicio) : array();
  $ultimoPagoSoftware = count($pagosServicio) ? $pagosServicio[0] : null;
  $html = "";

  if($modo == "pendientes"){
    if($esSoftware){
      $html .= '<button class="btn btn-default btnImprimirContratoSoftware" idServicio="'.$idServicio.'" title="Imprimir contrato de software"><i class="fa fa-file-text-o"></i> Contrato</button>';
      if((float)($proyectoSoftware["pago_adelanto"] ?? 0) > 0){
        $html .= ' <button class="btn btn-primary btnImprimirPagoSoftware" idServicio="'.$idServicio.'" idPago="'.($ultimoPagoSoftware ? (int)$ultimoPagoSoftware["id"] : 0).'" title="Reimprimir ultimo pago parcial de adelanto"><i class="fa fa-print"></i> Pago parcial</button>';
      }
    }else{
      $html .= '<button class="btn btn-default btnImprimirBoletaServicio" idServicio="'.$idServicio.'" title="Imprimir boleta para caja"><i class="fa fa-print"></i> Boleta</button>';
    }

    $html .= '<button class="btn btn-success btnCobrarServicio"
          idServicio="'.$idServicio.'"
          totalServicio="'.tmCobroServicioEsc($montoACobrar).'"
          baseRepuestos="'.tmCobroServicioEsc($baseRepuestos).'"
          manoObraActual="'.tmCobroServicioEsc($manoObraActual).'"
          esTaller="'.($esTaller ? "1" : "0").'"
          tallerEquipo="'.tmCobroServicioEsc($infoTaller["equipo"]).'"
          tallerDiagnostico="'.tmCobroServicioEsc($infoTaller["diagnostico"]).'"
          tallerNotificacion="'.tmCobroServicioEsc($infoTaller["notificacion"]).'"
          tallerReparacion="'.tmCobroServicioEsc($infoTaller["reparacion"]).'"
          tallerRepuestos="'.tmCobroServicioEsc($infoTaller["repuestos"]).'"
          tallerGarantia="'.tmCobroServicioEsc($infoTaller["garantia"]).'"
          tallerEvidencias="'.tmCobroServicioEsc($infoTaller["evidencias"]).'"
          esSoftware="'.($esSoftware ? "1" : "0").'"
          estadoPago="'.tmCobroServicioEsc($servicio["estado_pago"] ?? "").'"
          pagoParcial="'.($esSoftware && ($servicio["estado_pago"] ?? "") == "pendiente_adelanto" ? "1" : "0").'"
          title="Registrar cobro del servicio">
          <i class="fa fa-check"></i> Cobrar
        </button>';
  }else{
    $html .= '<button class="btn btn-primary btnImprimirNotaVentaServicio" idServicio="'.$idServicio.'" title="Reimprimir nota de venta para el cliente">
          <i class="fa fa-file-text-o"></i> Nota cliente
        </button>
        <button class="btn btn-default btnImprimirBoletaServicio" idServicio="'.$idServicio.'" title="Reimprimir boleta de caja">
          <i class="fa fa-print"></i> Boleta caja
        </button>';

    if($esTaller){
      $html .= '<button class="btn btn-default btnImprimirDetalleTaller" idServicio="'.$idServicio.'" title="Imprimir detalle de soporte para almacen/cliente">
            <i class="fa fa-wrench"></i> Detalle taller
          </button>';
    }
    if($esSoftware){
      $html .= '<button class="btn btn-default btnImprimirContratoSoftware" idServicio="'.$idServicio.'" title="Reimprimir contrato de software">
            <i class="fa fa-file-text"></i> Contrato
          </button>
          <button class="btn btn-default btnImprimirPagoSoftware" idServicio="'.$idServicio.'" idPago="'.($ultimoPagoSoftware ? (int)$ultimoPagoSoftware["id"] : 0).'" title="Reimprimir comprobante de pago de software">
            <i class="fa fa-print"></i> Pago
          </button>';
    }
  }

  return $html;
}

function renderTarjetasCobrosServicio($servicios, $modo){
  if(count($servicios) == 0){
    echo '<div class="cobro-servicio-empty">No hay registros en esta pestana.</div>';
    return;
  }

  foreach($servicios as $servicio){
    $cliente = ControladorClientes::ctrMostrarClientes("id", $servicio["id_cliente"]);
    $tecnico = !empty($servicio["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"]) : null;
    $esSoftware = ($servicio["tipo_servicio"] ?? "") == "Desarrollo de software";
    list($montoACobrar, $conceptoCobro, $proyectoSoftware, $desarrollador) = datosCobroServicio($servicio);
    $tecnicoNombre = $tecnico["nombre"] ?? ($esSoftware ? ($desarrollador ?: "Se asigna al cobrar") : "Sin asignar");
    $clienteNombre = $cliente["nombre"] ?? "Sin cliente";
    $servicioNombre = trim(($servicio["tipo_servicio"] ?? "")." / ".($servicio["tipo_instalacion"] ?? ""), " /");
    $totalFormateado = "Bs ".number_format($montoACobrar, 2);
    $estadoClase = claseCobroServicio($servicio);
    $estadoTexto = strip_tags(etiquetaCobroServicio($servicio));
    $fecha = $servicio["fecha"] ?? ($servicio["fecha_pago"] ?? "");
    $acciones = accionesCobroServicio($servicio, $modo);
    $proceso = $modo == "pendientes" ? "Caja para cobro" : "Cobro completado";
    $busqueda = strtolower(($servicio["codigo"] ?? "")." ".$clienteNombre." ".$tecnicoNombre." ".$servicioNombre." ".$estadoTexto." ".$proceso." ".$conceptoCobro." ".$totalFormateado);

    echo '<article class="cobro-servicio-card estado-'.$estadoClase.'" tabindex="0"
        data-search="'.tmCobroServicioEsc($busqueda).'"
        data-codigo="'.tmCobroServicioEsc($servicio["codigo"]).'"
        data-cliente="'.tmCobroServicioEsc($clienteNombre).'"
        data-tecnico="'.tmCobroServicioEsc($tecnicoNombre).'"
        data-servicio="'.tmCobroServicioEsc($servicioNombre).'"
        data-total="'.tmCobroServicioEsc($totalFormateado).'"
        data-concepto="'.tmCobroServicioEsc($conceptoCobro).'"
        data-estado="'.tmCobroServicioEsc($estadoTexto).'"
        data-fecha="'.tmCobroServicioEsc($fecha).'"
        data-proceso="'.tmCobroServicioEsc($proceso).'"
        data-estado-clase="'.tmCobroServicioEsc($estadoClase).'">
        <div class="cobro-servicio-card-top">
          <span class="cobro-servicio-code"><i class="fa fa-wrench"></i> '.tmCobroServicioEsc($servicio["codigo"]).'</span>
          '.etiquetaCobroServicio($servicio).'
        </div>
        <h3>'.tmCobroServicioEsc($clienteNombre).'</h3>
        <div class="cobro-servicio-total">'.$totalFormateado.'<span>'.tmCobroServicioEsc($conceptoCobro).'</span></div>
        <div class="cobro-servicio-info">
          <div><span>Servicio</span><strong>'.tmCobroServicioEsc($servicioNombre).'</strong></div>
          <div><span>Tecnico</span><strong>'.tmCobroServicioEsc($tecnicoNombre).'</strong></div>
          <div><span>Fecha</span><strong>'.tmCobroServicioEsc($fecha ?: "-").'</strong></div>
          <div><span>Proceso</span><strong>'.tmCobroServicioEsc($proceso).'</strong></div>
        </div>
        <div class="cobro-servicio-card-footer">
          <span><i class="fa fa-mouse-pointer"></i> Ver detalle y acciones</span>
          <i class="fa fa-chevron-right"></i>
        </div>
        <div class="cobro-servicio-actions-template" style="display:none">'.$acciones.'</div>
      </article>';
  }
}
?>

<div class="content-wrapper pagos-servicios-wrapper">
  <style>
    .pagos-servicios-wrapper .content{padding-top:10px;}
    .pagos-servicios-hero{
      position:relative;
      border:1px solid rgba(184,205,232,.62);
      border-radius:18px;
      background:linear-gradient(135deg,rgba(16,43,59,.92),rgba(23,107,155,.86));
      color:#fff;
      padding:18px 20px;
      margin-bottom:14px;
      overflow:hidden;
      box-shadow:0 18px 38px rgba(15,23,42,.12);
    }
    .pagos-servicios-hero:after{
      content:"";
      position:absolute;
      right:-58px;
      top:-72px;
      width:210px;
      height:210px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .pagos-servicios-hero h1{
      position:relative;
      z-index:1;
      margin:0 0 5px;
      font-size:24px;
      font-weight:950;
    }
    .pagos-servicios-hero p{
      position:relative;
      z-index:1;
      margin:0;
      max-width:800px;
      color:rgba(255,255,255,.86);
      font-size:13px;
      font-weight:750;
      line-height:1.35;
    }
    .pagos-servicios-kpis{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:10px;
      margin-bottom:12px;
    }
    .pagos-servicios-kpi{
      border:1px solid rgba(184,205,232,.66);
      border-radius:15px;
      background:rgba(255,255,255,.72);
      padding:12px;
      display:flex;
      align-items:center;
      gap:10px;
      box-shadow:0 12px 26px rgba(15,23,42,.06);
    }
    .pagos-servicios-kpi i{
      width:36px;
      height:36px;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      background:linear-gradient(135deg,#176b9b,#36aee2);
      box-shadow:0 8px 18px rgba(23,107,155,.22);
      flex:0 0 auto;
    }
    .pagos-servicios-kpi span{
      display:block;
      color:#6b7d91;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pagos-servicios-kpi strong{
      display:block;
      color:#162235;
      font-size:18px;
      font-weight:950;
      line-height:1.15;
      margin-top:2px;
    }
    .cobro-servicios-panel{
      border:1px solid rgba(184,205,232,.68);
      border-radius:17px;
      background:rgba(255,255,255,.62);
      box-shadow:0 16px 38px rgba(15,23,42,.07);
      overflow:hidden;
    }
    .pagos-servicios-toolbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding:12px;
      border-bottom:1px solid rgba(184,205,232,.58);
      background:rgba(255,255,255,.45);
    }
    .pagos-servicios-toolbar h3{
      margin:0;
      color:#1d2b3d;
      font-size:16px;
      font-weight:950;
    }
    .pagos-servicios-search{
      position:relative;
      width:min(360px,100%);
    }
    .pagos-servicios-search i{
      position:absolute;
      left:12px;
      top:50%;
      transform:translateY(-50%);
      color:#7b8fa5;
    }
    .pagos-servicios-search input{
      width:100%;
      height:36px;
      border:1px solid rgba(184,205,232,.82);
      border-radius:11px;
      background:rgba(255,255,255,.78);
      padding:0 12px 0 34px;
      outline:none;
      font-weight:800;
      color:#25364a;
    }
    .cobro-servicios-panel .nav-tabs{
      border-bottom:1px solid rgba(184,205,232,.62);
      padding:0 12px;
      background:rgba(255,255,255,.42);
    }
    .cobro-servicios-panel .nav-tabs>li>a{
      border:0;
      border-radius:0;
      color:#52657a;
      font-weight:900;
      padding:12px 14px;
    }
    .cobro-servicios-panel .nav-tabs>li.active>a,
    .cobro-servicios-panel .nav-tabs>li.active>a:hover,
    .cobro-servicios-panel .nav-tabs>li.active>a:focus{
      border:0;
      border-bottom:3px solid #3c8dbc;
      color:#173b5d;
      background:transparent;
    }
    .cobro-servicios-panel .tab-content{padding:12px;}
    .cobro-servicio-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(218px,1fr));
      gap:10px;
    }
    .cobro-servicio-card{
      position:relative;
      min-height:224px;
      border:1px solid rgba(184,205,232,.72);
      border-radius:14px;
      background:rgba(255,255,255,.82);
      padding:11px;
      display:flex;
      flex-direction:column;
      gap:8px;
      cursor:pointer;
      overflow:hidden;
      box-shadow:0 12px 28px rgba(15,23,42,.06);
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .cobro-servicio-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#f39c12;
    }
    .cobro-servicio-card.estado-info:before{background:#3c8dbc;}
    .cobro-servicio-card.estado-success:before{background:#00a65a;}
    .cobro-servicio-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 18px 36px rgba(15,23,42,.12);
    }
    .cobro-servicio-card-top{
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:flex-start;
      padding-left:4px;
    }
    .cobro-servicio-card .label{
      max-width:104px;
      white-space:normal;
      line-height:1.16;
      padding:4px 7px;
      border-radius:999px;
      font-size:8.8px;
      font-weight:900;
      color:#fff !important;
      text-align:center;
    }
    .cobro-servicio-code{
      display:inline-flex;
      align-items:center;
      gap:5px;
      color:#176b9b;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .cobro-servicio-card h3{
      margin:0;
      color:#1f2d3d;
      font-size:14px;
      font-weight:950;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .cobro-servicio-total{
      padding:8px;
      border-radius:10px;
      background:linear-gradient(135deg,rgba(60,141,188,.12),rgba(0,172,214,.10));
      color:#176b9b;
      font-size:18px;
      font-weight:950;
      line-height:1.1;
    }
    .cobro-servicio-total span{
      display:block;
      margin-top:3px;
      color:#60717f;
      font-size:9.5px;
      font-weight:900;
      text-transform:uppercase;
    }
    .cobro-servicio-info{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:6px;
    }
    .cobro-servicio-info div{
      border:1px solid #edf2f6;
      background:#f8fbfd;
      border-radius:8px;
      padding:6px;
      min-height:40px;
    }
    .cobro-servicio-info span{
      display:block;
      margin-bottom:3px;
      color:#7b8b96;
      font-size:8.8px;
      font-weight:900;
      text-transform:uppercase;
    }
    .cobro-servicio-info strong{
      display:block;
      color:#263845;
      font-size:10.5px;
      font-weight:850;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .cobro-servicio-card-footer{
      margin-top:auto;
      padding-top:7px;
      border-top:1px solid #edf2f6;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:8px;
      color:#176b9b;
      font-size:10px;
      font-weight:900;
    }
    .cobro-servicio-empty{
      grid-column:1 / -1;
      min-height:150px;
      border:1px dashed #c6d4df;
      border-radius:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#7d8c96;
      background:rgba(248,251,253,.72);
      font-weight:850;
      text-align:center;
      padding:18px;
    }
    .cobro-servicio-modal .modal-dialog{width:min(820px,calc(100vw - 36px));}
    .cobro-servicio-modal .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      background:#f6f9fc;
      box-shadow:0 24px 64px rgba(10,30,45,.30);
    }
    .cobro-servicio-modal .modal-header{
      position:relative;
      color:#fff;
      border:0;
      padding:14px 18px;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 62%,#36aee2 100%);
      overflow:hidden;
    }
    .cobro-servicio-modal.estado-warning .modal-header{background:linear-gradient(135deg,#f39c12,#d98200);}
    .cobro-servicio-modal.estado-success .modal-header{background:linear-gradient(135deg,#00a65a,#087a46);}
    .cobro-servicio-modal.estado-info .modal-header{background:linear-gradient(135deg,#176b9b,#36aee2);}
    .cobro-servicio-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-38px;
      top:-56px;
      width:154px;
      height:154px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .cobro-servicio-modal-title{
      position:relative;
      z-index:2;
      display:flex;
      align-items:center;
      gap:10px;
      max-width:88%;
    }
    .cobro-servicio-modal-icon{
      width:40px;
      height:40px;
      flex:0 0 40px;
      border-radius:11px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.18);
      border:1px solid rgba(255,255,255,.20);
      font-size:18px;
    }
    .cobro-servicio-modal-kicker{
      display:inline-block;
      margin-bottom:3px;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
      letter-spacing:.04em;
      color:#eff9ff;
    }
    .cobro-servicio-modal h4{margin:0;font-size:18px;font-weight:950;line-height:1.2;}
    .cobro-servicio-modal .close{
      position:relative;
      z-index:3;
      color:#fff;
      opacity:.9;
      text-shadow:none;
      width:32px;
      height:32px;
      border-radius:50%;
      background:rgba(255,255,255,.18);
      line-height:30px;
      font-size:24px;
    }
    .cobro-servicio-modal .modal-body{padding:12px;}
    .cobro-servicio-detail-total{
      margin-bottom:8px;
      padding:10px;
      border:1px solid #dce8f1;
      border-radius:12px;
      background:linear-gradient(135deg,#fff,rgba(232,244,252,.92));
      color:#176b9b;
      font-size:22px;
      font-weight:950;
      box-shadow:0 10px 24px rgba(22,49,64,.06);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }
    .cobro-servicio-detail-total span{
      display:block;
      color:#60717f;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .cobro-servicio-detail-total strong{white-space:nowrap;}
    .cobro-servicio-detail-grid{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:7px;
    }
    .cobro-servicio-detail-item{
      border:1px solid #e2ebf2;
      border-radius:9px;
      background:#fff;
      padding:8px;
      min-height:58px;
    }
    .cobro-servicio-detail-item span{
      display:block;
      margin-bottom:4px;
      color:#7b8b96;
      font-size:9.5px;
      font-weight:900;
      text-transform:uppercase;
    }
    .cobro-servicio-detail-item strong{
      color:#263845;
      font-size:12px;
      font-weight:850;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .cobro-servicio-modal-actions{
      margin-top:8px;
      padding:8px;
      border:1px solid #dce8f1;
      border-radius:9px;
      background:#fff;
      display:flex;
      flex-wrap:wrap;
      justify-content:flex-end;
      gap:7px;
    }
    .cobro-servicio-modal-actions:before{
      content:"Acciones disponibles";
      margin-right:auto;
      align-self:center;
      color:#60717f;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .cobro-servicio-modal-actions .btn{
      border-radius:8px;
      font-weight:900;
      padding:6px 9px;
      font-size:12px;
    }
    .taller-caja-report{
      margin-bottom:14px;
      border:1px solid rgba(14,165,233,.24);
      border-radius:14px;
      background:linear-gradient(135deg, rgba(239,248,255,.94), rgba(255,255,255,.82));
      padding:12px;
    }
    .taller-caja-report h5{
      margin:0 0 10px;
      font-weight:900;
      color:#0f3554;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .taller-report-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:8px;
    }
    .taller-report-box{
      border:1px solid rgba(180,205,230,.7);
      border-radius:10px;
      background:rgba(255,255,255,.72);
      padding:9px;
      min-height:62px;
    }
    .taller-report-box span{
      display:block;
      font-size:10px;
      font-weight:900;
      color:#64748b;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .taller-report-box strong{
      display:block;
      font-size:12px;
      color:#172033;
      white-space:pre-line;
      overflow-wrap:anywhere;
    }
    .taller-report-evidence{
      margin-top:9px;
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(86px,1fr));
      gap:8px;
    }
    .taller-report-evidence a{
      display:block;
      border:1px solid rgba(180,205,230,.75);
      border-radius:10px;
      background:#fff;
      padding:4px;
    }
    .taller-report-evidence img{
      width:100%;
      height:68px;
      object-fit:cover;
      border-radius:8px;
      display:block;
    }
    .modal-cobro-servicio-form .modal-dialog{width:min(720px,calc(100vw - 36px));}
    .modal-cobro-servicio-form .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 26px 70px rgba(15,23,42,.28);
    }
    .modal-cobro-servicio-form .modal-header{
      position:relative;
      border:0;
      color:#fff;
      padding:14px 18px;
      background:linear-gradient(135deg,#176b9b,#36aee2);
      overflow:hidden;
    }
    .modal-cobro-servicio-form .modal-header:after{
      content:"";
      position:absolute;
      right:-42px;
      top:-66px;
      width:150px;
      height:150px;
      border-radius:50%;
      background:rgba(255,255,255,.14);
    }
    .modal-cobro-servicio-title{
      position:relative;
      z-index:1;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .modal-cobro-servicio-title>i{
      width:40px;
      height:40px;
      border-radius:13px;
      background:rgba(255,255,255,.18);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:18px;
    }
    .modal-cobro-servicio-title h4{
      margin:0;
      font-size:19px;
      font-weight:950;
    }
    .modal-cobro-servicio-title span{
      display:block;
      color:rgba(255,255,255,.86);
      font-size:11px;
      font-weight:800;
    }
    .modal-cobro-servicio-form .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.92;
      text-shadow:none;
    }
    .modal-cobro-servicio-form .modal-body{
      padding:14px;
      background:#f5f8fc;
    }
    .modal-cobro-servicio-form .form-group label{
      color:#60748b;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
    }
    .modal-cobro-servicio-form .form-control,
    .modal-cobro-servicio-form .input-group-addon{
      border-color:#dbe7f0;
      box-shadow:none;
      font-weight:850;
    }
    .modal-cobro-servicio-form .modal-footer{
      border-top:1px solid #e4eef5;
      background:#fff;
      padding:10px 14px;
    }
    .modal-cobro-servicio-form .modal-footer .btn{
      border-radius:9px;
      font-weight:900;
      padding:7px 12px;
    }
    body.tm-dark-mode .cobro-servicios-panel,
    body.dark-mode .cobro-servicios-panel,
    body.tm-dark-mode .pagos-servicios-kpi,
    body.dark-mode .pagos-servicios-kpi,
    body.tm-dark-mode .cobro-servicio-card,
    body.dark-mode .cobro-servicio-card,
    body.tm-dark-mode .cobro-servicio-detail-total,
    body.dark-mode .cobro-servicio-detail-total,
    body.tm-dark-mode .cobro-servicio-detail-item,
    body.dark-mode .cobro-servicio-detail-item,
    body.tm-dark-mode .cobro-servicio-modal-actions,
    body.dark-mode .cobro-servicio-modal-actions,
    body.tm-dark-mode .taller-caja-report,
    body.dark-mode .taller-caja-report,
    body.tm-dark-mode .taller-report-box,
    body.dark-mode .taller-report-box{
      background:rgba(15,27,48,.72);
      border-color:rgba(147,197,253,.18);
      color:#edf5ff;
    }
    body.tm-dark-mode .pagos-servicios-toolbar,
    body.dark-mode .pagos-servicios-toolbar,
    body.tm-dark-mode .cobro-servicios-panel .nav-tabs,
    body.dark-mode .cobro-servicios-panel .nav-tabs{background:rgba(15,27,48,.38);}
    body.tm-dark-mode .pagos-servicios-toolbar h3,
    body.dark-mode .pagos-servicios-toolbar h3,
    body.tm-dark-mode .pagos-servicios-kpi strong,
    body.dark-mode .pagos-servicios-kpi strong,
    body.tm-dark-mode .cobro-servicio-card h3,
    body.dark-mode .cobro-servicio-card h3,
    body.tm-dark-mode .cobro-servicio-info strong,
    body.dark-mode .cobro-servicio-info strong,
    body.tm-dark-mode .cobro-servicio-detail-item strong,
    body.dark-mode .cobro-servicio-detail-item strong,
    body.tm-dark-mode .taller-caja-report h5,
    body.dark-mode .taller-caja-report h5,
    body.tm-dark-mode .taller-report-box strong,
    body.dark-mode .taller-report-box strong{
      color:#fff;
    }
    body.tm-dark-mode .cobro-servicio-info div,
    body.dark-mode .cobro-servicio-info div{background:rgba(15,27,48,.58);border-color:rgba(147,197,253,.18);}
    body.tm-dark-mode .cobro-servicio-modal .modal-body,
    body.dark-mode .cobro-servicio-modal .modal-body,
    body.tm-dark-mode .modal-cobro-servicio-form .modal-body,
    body.dark-mode .modal-cobro-servicio-form .modal-body{background:#0d1729;}
    body.tm-dark-mode .modal-cobro-servicio-form .modal-footer,
    body.dark-mode .modal-cobro-servicio-form .modal-footer{background:rgba(15,27,48,.92);border-color:rgba(147,197,253,.18);}
    @media(max-width:767px){
      .pagos-servicios-kpis{grid-template-columns:1fr;}
      .pagos-servicios-toolbar{align-items:flex-start;flex-direction:column;}
      .cobro-servicio-grid{grid-template-columns:1fr;}
      .cobro-servicio-detail-grid{grid-template-columns:1fr;}
      .taller-report-grid{grid-template-columns:1fr;}
    }
  </style>

  <section class="content-header">
    <h1>Cobro de servicios</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Cobro de servicios</li>
    </ol>
  </section>
  <section class="content">
    <div class="pagos-servicios-hero">
      <h1><i class="fa fa-credit-card"></i> Caja de servicios</h1>
      <p>Revise servicios pendientes, cobre adelantos o saldos finales y consulte los documentos de taller y software desde un solo lugar.</p>
    </div>

    <div class="pagos-servicios-kpis">
      <div class="pagos-servicios-kpi">
        <i class="fa fa-clock-o"></i>
        <div><span>Pendientes de cobro</span><strong><?php echo count($serviciosPendientes); ?> servicio(s)</strong></div>
      </div>
      <div class="pagos-servicios-kpi">
        <i class="fa fa-money"></i>
        <div><span>Total pendiente</span><strong>Bs <?php echo number_format($totalPendientePagoServicios, 2); ?></strong></div>
      </div>
      <div class="pagos-servicios-kpi">
        <i class="fa fa-check-circle"></i>
        <div><span>Cobrado registrado</span><strong>Bs <?php echo number_format($totalCobradoPagoServicios, 2); ?></strong></div>
      </div>
    </div>

    <div class="cobro-servicios-panel nav-tabs-custom">
      <div class="pagos-servicios-toolbar">
        <h3><i class="fa fa-list-alt"></i> Seguimiento de cobros</h3>
        <div class="pagos-servicios-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarPagoServiciosCards" placeholder="Buscar por codigo, cliente, servicio o estado">
        </div>
      </div>
      <ul class="nav nav-tabs">
        <li class="active"><a href="#tabServiciosPendientesCobro" data-toggle="tab">Pendientes de cobro <span class="badge bg-yellow"><?php echo count($serviciosPendientes); ?></span></a></li>
        <li><a href="#tabServiciosCobrados" data-toggle="tab">Cobros completados <span class="badge bg-green"><?php echo count($serviciosCobrados); ?></span></a></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane active" id="tabServiciosPendientesCobro">
          <div class="cobro-servicio-grid"><?php renderTarjetasCobrosServicio($serviciosPendientes, "pendientes"); ?></div>
        </div>
        <div class="tab-pane" id="tabServiciosCobrados">
          <div class="cobro-servicio-grid"><?php renderTarjetasCobrosServicio($serviciosCobrados, "cobrados"); ?></div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleCobroServicio" class="modal fade cobro-servicio-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="cobro-servicio-modal-title">
          <div class="cobro-servicio-modal-icon"><i class="fa fa-credit-card"></i></div>
          <div>
            <span class="cobro-servicio-modal-kicker" id="cobroServicioModalEstado">Detalle</span>
            <h4 id="cobroServicioModalTitulo">Servicio</h4>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div class="cobro-servicio-detail-total"><span id="cobroServicioModalConcepto">Total</span><strong id="cobroServicioModalTotal">Bs 0.00</strong></div>
        <div class="cobro-servicio-detail-grid">
          <div class="cobro-servicio-detail-item"><span>Cliente</span><strong id="cobroServicioModalCliente">-</strong></div>
          <div class="cobro-servicio-detail-item"><span>Tecnico</span><strong id="cobroServicioModalTecnico">-</strong></div>
          <div class="cobro-servicio-detail-item"><span>Servicio</span><strong id="cobroServicioModalServicio">-</strong></div>
          <div class="cobro-servicio-detail-item"><span>Fecha</span><strong id="cobroServicioModalFecha">-</strong></div>
          <div class="cobro-servicio-detail-item"><span>Proceso</span><strong id="cobroServicioModalProceso">-</strong></div>
          <div class="cobro-servicio-detail-item"><span>Codigo servicio</span><strong id="cobroServicioModalCodigo">-</strong></div>
        </div>
        <div class="cobro-servicio-modal-actions" id="cobroServicioModalAcciones"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalCobrarServicio" class="modal fade modal-cobro-servicio-form" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <div class="modal-cobro-servicio-title">
        <i class="fa fa-money"></i>
        <div>
          <h4>Registrar pago de servicio</h4>
          <span>Confirme el importe, metodo y referencia antes de completar el cobro.</span>
        </div>
      </div>
    </div>
    <div class="modal-body">
      <input type="hidden" id="cobroIdServicio">
      <input type="hidden" id="cobroServicioEsTaller">
      <input type="hidden" id="cobroServicioPermiteParcial" value="0">
      <div class="alert alert-info" id="mensajeRetiroAlmacen" style="display:none">Al confirmar el cobro se generara la nota de venta para el cliente. Con esa nota el cliente debe pasar por almacen para retirar su equipo.</div>
      <div class="alert alert-warning" id="mensajeAdelantoParcial" style="display:none">Este adelanto permite pago parcial. Si el cliente paga menos, el proyecto seguira pendiente hasta completar el adelanto pactado.</div>
      <div id="grupoServicioTallerInforme" class="taller-caja-report" style="display:none">
        <h5><i class="fa fa-clipboard"></i> Informe tecnico final para calcular cobro</h5>
        <div class="taller-report-grid">
          <div class="taller-report-box"><span>Equipo</span><strong id="tallerInformeEquipo">-</strong></div>
          <div class="taller-report-box"><span>Diagnostico</span><strong id="tallerInformeDiagnostico">-</strong></div>
          <div class="taller-report-box"><span>Notificacion al cliente</span><strong id="tallerInformeNotificacion">-</strong></div>
          <div class="taller-report-box"><span>Trabajo correctivo realizado</span><strong id="tallerInformeReparacion">-</strong></div>
          <div class="taller-report-box"><span>Repuestos usados</span><strong id="tallerInformeRepuestos">-</strong></div>
          <div class="taller-report-box"><span>Garantia / observaciones</span><strong id="tallerInformeGarantia">-</strong></div>
        </div>
        <div class="taller-report-evidence" id="tallerInformeEvidencias"></div>
      </div>
      <div id="grupoServicioTallerCostos" style="display:none">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group"><label>Repuestos usados</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="text" class="form-control" id="cobroServicioRepuestos" readonly></div></div>
          </div>
          <div class="col-md-6">
            <div class="form-group"><label>Mano de obra correctiva</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control" id="cobroServicioManoObra" min="0" step="0.01"></div></div>
          </div>
        </div>
        <div class="alert alert-info">Caja define la mano de obra revisando el informe tecnico y las evidencias. Los repuestos se suman automaticamente al total.</div>
      </div>
      <div class="form-group"><label>Total</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="text" class="form-control" id="cobroServicioTotal" readonly></div></div>
      <div class="form-group"><label>Metodo de pago</label><select class="form-control" id="cobroServicioMetodo"><option value="Efectivo">Efectivo</option><option value="QR">QR</option><option value="Tarjeta Credito">Tarjeta Credito</option><option value="Tarjeta Debito">Tarjeta Debito</option></select></div>
      <div class="form-group" id="grupoServicioRecibido"><label>Monto recibido</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control" id="cobroServicioRecibido" min="0" step="0.01"></div></div>
      <div class="form-group"><label>Cambio</label><div class="input-group"><span class="input-group-addon">Bs</span><input type="text" class="form-control" id="cobroServicioCambio" readonly></div></div>
      <div class="form-group" id="grupoServicioTransaccion" style="display:none"><label>Codigo / referencia</label><input type="text" class="form-control" id="cobroServicioTransaccion"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="btnConfirmarCobroServicio">Confirmar cobro y generar nota</button></div>
  </div></div>
</div>

<script>
$(function(){
  $('[title]').tooltip({container:'body'});
});

function filtrarPagoServiciosCards(){
  var termino = ($("#buscarPagoServiciosCards").val() || "").toString().toLowerCase();

  $(".cobro-servicio-grid").each(function(){
    var $grid = $(this);
    var visibles = 0;
    $grid.find(".cobro-servicio-empty.busqueda-vacia").remove();

    $grid.find(".cobro-servicio-card").each(function(){
      var $card = $(this);
      var coincide = !termino || (($card.data("search") || $card.text()).toString().toLowerCase().indexOf(termino) !== -1);
      $card.toggle(coincide);
      if(coincide){
        visibles++;
      }
    });

    if(visibles === 0 && $grid.find(".cobro-servicio-card").length > 0){
      $grid.append('<div class="cobro-servicio-empty busqueda-vacia"><i class="fa fa-search"></i>&nbsp; No hay cobros que coincidan con la busqueda.</div>');
    }
  });
}

$(document).on("input", "#buscarPagoServiciosCards", filtrarPagoServiciosCards);
$(document).on("shown.bs.tab", 'a[data-toggle="tab"]', filtrarPagoServiciosCards);

$(document).on("click", ".cobro-servicio-card", function(e){
  if($(e.target).closest("button, a, .btn").length){
    return;
  }

  var $card = $(this);
  var estadoClase = $card.data("estado-clase") || "info";

  $("#modalDetalleCobroServicio")
    .removeClass("estado-warning estado-info estado-success")
    .addClass("estado-" + estadoClase);
  $("#cobroServicioModalEstado").text($card.data("estado") || "Detalle");
  $("#cobroServicioModalTitulo").text("Servicio " + ($card.data("codigo") || ""));
  $("#cobroServicioModalConcepto").text($card.data("concepto") || "Total");
  $("#cobroServicioModalTotal").text($card.data("total") || "Bs 0.00");
  $("#cobroServicioModalCliente").text($card.data("cliente") || "-");
  $("#cobroServicioModalTecnico").text($card.data("tecnico") || "-");
  $("#cobroServicioModalServicio").text($card.data("servicio") || "-");
  $("#cobroServicioModalFecha").text($card.data("fecha") || "-");
  $("#cobroServicioModalProceso").text($card.data("proceso") || "-");
  $("#cobroServicioModalCodigo").text($card.data("codigo") || "-");
  $("#cobroServicioModalAcciones").html($card.find(".cobro-servicio-actions-template").html());
  $("#modalDetalleCobroServicio").modal("show");
});

$(document).on("keydown", ".cobro-servicio-card", function(e){
  if(e.key === "Enter" || e.key === " "){
    e.preventDefault();
    $(this).trigger("click");
  }
});

$(document).on("click", ".btnImprimirBoletaServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});

$(document).on("click", ".btnImprimirNotaVentaServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/nota-venta-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});

$(document).on("click", ".btnImprimirDetalleTaller", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-taller.php?idServicio=" + $(this).attr("idServicio") + "&tipo=correctivo", "_blank");
});

$(document).on("click", ".btnImprimirContratoSoftware", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/contrato-software.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});

$(document).on("click", ".btnImprimirPagoSoftware", function(e){
  e.preventDefault();
  e.stopPropagation();
  var idPago = Number($(this).attr("idPago")) || 0;
  var url = "extensiones/tcpdf/pdf/boleta-software-pago.php?idServicio=" + $(this).attr("idServicio");
  if(idPago > 0){
    url += "&idPago=" + idPago;
  }
  window.open(url, "_blank");
});

function textoInformeTaller(valor){
  valor = $.trim(valor || "");
  return valor.length ? valor : "-";
}

function pintarInformeTallerCobro($boton){
  $("#tallerInformeEquipo").text(textoInformeTaller($boton.attr("tallerEquipo")));
  $("#tallerInformeDiagnostico").text(textoInformeTaller($boton.attr("tallerDiagnostico")));
  $("#tallerInformeNotificacion").text(textoInformeTaller($boton.attr("tallerNotificacion")));
  $("#tallerInformeReparacion").text(textoInformeTaller($boton.attr("tallerReparacion")));
  $("#tallerInformeRepuestos").text(textoInformeTaller($boton.attr("tallerRepuestos")));
  $("#tallerInformeGarantia").text(textoInformeTaller($boton.attr("tallerGarantia")));

  var evidencias = [];
  try{
    evidencias = JSON.parse($boton.attr("tallerEvidencias") || "[]");
  }catch(error){
    evidencias = [];
  }

  var $contenedor = $("#tallerInformeEvidencias").empty();
  if(!evidencias.length){
    $contenedor.append($("<div>").addClass("text-muted").text("Sin evidencias fotograficas adjuntas."));
    return;
  }

  evidencias.forEach(function(item, indice){
    var archivo = item && item.archivo ? item.archivo : "";
    if(!archivo){ return; }
    $("<a>")
      .attr("href", archivo)
      .attr("target", "_blank")
      .attr("title", "Ver evidencia " + (indice + 1))
      .append($("<img>").attr("src", archivo).attr("alt", "Evidencia tecnica " + (indice + 1)))
      .appendTo($contenedor);
  });
}

$(document).on("click", ".btnCobrarServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  var $boton = $(this);
  var total = Number($(this).attr("totalServicio")) || 0;
  var esTaller = $(this).attr("esTaller") === "1";
  var permiteParcial = $(this).attr("pagoParcial") === "1";
  var repuestos = Number($(this).attr("baseRepuestos")) || 0;
  var manoObra = Number($(this).attr("manoObraActual")) || 0;

  $("#modalDetalleCobroServicio").modal("hide");
  $("#cobroIdServicio").val($(this).attr("idServicio"));
  $("#cobroServicioEsTaller").val(esTaller ? "1" : "0");
  $("#cobroServicioPermiteParcial").val(permiteParcial ? "1" : "0");
  $("#mensajeRetiroAlmacen").toggle(esTaller);
  $("#mensajeAdelantoParcial").toggle(permiteParcial);
  $("#grupoServicioTallerInforme").toggle(esTaller);
  $("#grupoServicioTallerCostos").toggle(esTaller);
  if(esTaller){
    pintarInformeTallerCobro($boton);
  }
  $("#cobroServicioRepuestos").val(repuestos.toFixed(2));
  $("#cobroServicioManoObra").val(manoObra.toFixed(2));
  $("#cobroServicioTotal").val((esTaller ? repuestos + manoObra : total).toFixed(2));
  $("#cobroServicioRecibido").val($("#cobroServicioTotal").val());
  $("#cobroServicioCambio").val("0.00");
  $("#cobroServicioTransaccion").val("");
  $("#cobroServicioMetodo").val("Efectivo").trigger("change");
  $("#modalCobrarServicio").modal("show");
});

$("#cobroServicioMetodo").on("change", function(){
  var total = Number($("#cobroServicioTotal").val()) || 0;
  var permiteParcial = $("#cobroServicioPermiteParcial").val() === "1";
  if($(this).val() === "Efectivo" || permiteParcial){
    $("#grupoServicioRecibido").show();
    $("#grupoServicioTransaccion").toggle($(this).val() !== "Efectivo");
  }else{
    $("#grupoServicioRecibido").hide();
    $("#grupoServicioTransaccion").show();
    $("#cobroServicioRecibido").val(total.toFixed(2));
  }
  calcularCambioServicio();
});

$("#cobroServicioRecibido").on("input change", calcularCambioServicio);
$("#cobroServicioManoObra").on("input change", function(){
  var repuestos = Number($("#cobroServicioRepuestos").val()) || 0;
  var manoObra = Number($(this).val()) || 0;
  var total = repuestos + manoObra;
  $("#cobroServicioTotal").val(total.toFixed(2));
  $("#cobroServicioRecibido").val(total.toFixed(2));
  calcularCambioServicio();
});
function calcularCambioServicio(){
  var total = Number($("#cobroServicioTotal").val()) || 0;
  var recibido = Number($("#cobroServicioRecibido").val()) || 0;
  $("#cobroServicioCambio").val(Math.max(0, recibido - total).toFixed(2));
}

$("#btnConfirmarCobroServicio").on("click", function(){
  var total = Number($("#cobroServicioTotal").val()) || 0;
  var recibido = Number($("#cobroServicioRecibido").val()) || 0;
  var permiteParcial = $("#cobroServicioPermiteParcial").val() === "1";
  if(permiteParcial && recibido <= 0){
    swal({type:"error", title:"Ingrese el monto recibido", confirmButtonText:"Cerrar"});
    return;
  }
  if(!permiteParcial && $("#cobroServicioMetodo").val() === "Efectivo" && recibido < total){
    swal({type:"error", title:"Monto insuficiente", confirmButtonText:"Cerrar"});
    return;
  }
  window.location = "index.php?ruta=pagos-servicios&aprobarPagoServicio=" + $("#cobroIdServicio").val() +
    "&metodoPago=" + encodeURIComponent($("#cobroServicioMetodo").val()) +
    "&montoRecibido=" + encodeURIComponent(recibido) +
    "&manoObraServicio=" + encodeURIComponent($("#cobroServicioManoObra").val() || "0") +
    "&codigoTransaccion=" + encodeURIComponent($("#cobroServicioTransaccion").val());
});
</script>

<?php ControladorServicios::ctrAprobarPagoServicio(); ?>
