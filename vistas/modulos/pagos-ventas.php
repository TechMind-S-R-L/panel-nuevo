<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$ventasPendientes = ControladorVentas::ctrMostrarVentasPendientesPago();
$ventasCobradas = ControladorVentas::ctrMostrarVentasCobradas();
$ventasPendientes = is_array($ventasPendientes) ? $ventasPendientes : array();
$ventasCobradas = is_array($ventasCobradas) ? $ventasCobradas : array();

$totalPendientePagoVentas = array_sum(array_map(function($venta){
  return (float)($venta["total"] ?? 0);
}, $ventasPendientes));

$totalCobradoPagoVentas = array_sum(array_map(function($venta){
  return (float)($venta["total"] ?? 0);
}, $ventasCobradas));

function tmPagoVentaEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function etiquetaPagoVenta($venta){
  if(($venta["estado_pago"] ?? "") == "aprobado"){
    if(($venta["estado_despacho"] ?? "") == "entregado"){
      return '<span class="label label-success">Cobrado y entregado</span>';
    }
    return '<span class="label label-primary">Cobrado - pendiente despacho</span>';
  }
  return '<span class="label label-warning">Por cobrar</span>';
}

function clasePagoVenta($venta){
  if(($venta["estado_pago"] ?? "") == "aprobado"){
    if(($venta["estado_despacho"] ?? "") == "entregado"){
      return "success";
    }
    return "info";
  }
  return "warning";
}

function accionesPagoVenta($venta, $modo){
  $idVenta = (int)$venta["id"];
  $codigo = tmPagoVentaEsc($venta["codigo"]);
  $total = tmPagoVentaEsc($venta["total"]);
  $html = "";

  if($modo == "pendientes"){
    $html .= '<button class="btn btn-info btnImprimirBoletaCaja" title="Imprimir boleta para caja" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'">
                <i class="fa fa-print"></i> Boleta caja
              </button>
              <button class="btn btn-success btnAprobarPagoVenta" title="Registrar cobro" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'" totalVenta="'.$total.'">
                <i class="fa fa-check"></i> Cobrar
              </button>';
  }else{
    $html .= '<button class="btn btn-default btnImprimirBoletaCaja" title="Reimprimir boleta de caja" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'">
                <i class="fa fa-print"></i> Caja
              </button>
              <button class="btn btn-primary btnImprimirBoletaDespacho" title="Reimprimir boleta para despacho" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'">
                <i class="fa fa-truck"></i> Despacho
              </button>';

    if(($venta["estado_despacho"] ?? "") == "entregado"){
      $html .= '<button class="btn btn-success btnImprimirFacturaVenta" title="Reimprimir factura o nota de venta" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'">
                  <i class="fa fa-file-text-o"></i> Nota venta
                </button>
                <button class="btn btn-default btnImprimirConformidadVenta" title="Reimprimir conformidad de entrega" idVenta="'.$idVenta.'">
                  <i class="fa fa-check-square-o"></i> Conformidad
                </button>';
    }
  }

  return $html;
}

function renderTarjetasPagosVentas($ventas, $modo){
  if(count($ventas) == 0){
    echo '<div class="pago-venta-empty">No hay ventas en esta pestana.</div>';
    return;
  }

  foreach ($ventas as $venta) {
    $cliente = ControladorClientes::ctrMostrarClientes("id", $venta["id_cliente"]);
    $vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_vendedor"]);
    $cajero = !empty($venta["id_cajero"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_cajero"]) : null;
    $clienteNombre = $cliente["nombre"] ?? "Sin cliente";
    $vendedorNombre = $vendedor["nombre"] ?? "Sin vendedor";
    $cajeroNombre = $cajero["nombre"] ?? "Pendiente";
    $fecha = $venta["fecha_pago"] ?? $venta["fecha"];
    $estadoClase = clasePagoVenta($venta);
    $estadoTexto = strip_tags(etiquetaPagoVenta($venta));
    $totalFormateado = "Bs ".number_format((float)$venta["total"], 2);
    $proceso = $modo == "pendientes" ? "Caja para cobro" : "Cobro registrado";

    $busqueda = strtolower(($venta["codigo"] ?? "")." ".$clienteNombre." ".$vendedorNombre." ".$cajeroNombre." ".$estadoTexto." ".$proceso." ".$totalFormateado);

    echo '<article class="pago-venta-card estado-'.$estadoClase.'" tabindex="0"
        data-search="'.tmPagoVentaEsc($busqueda).'"
        data-codigo="'.tmPagoVentaEsc($venta["codigo"]).'"
        data-cliente="'.tmPagoVentaEsc($clienteNombre).'"
        data-vendedor="'.tmPagoVentaEsc($vendedorNombre).'"
        data-cajero="'.tmPagoVentaEsc($cajeroNombre).'"
        data-total="'.tmPagoVentaEsc($totalFormateado).'"
        data-estado="'.tmPagoVentaEsc($estadoTexto).'"
        data-fecha="'.tmPagoVentaEsc($fecha).'"
        data-proceso="'.tmPagoVentaEsc($proceso).'"
        data-estado-clase="'.tmPagoVentaEsc($estadoClase).'">
        <div class="pago-venta-card-top">
          <span class="pago-venta-code"><i class="fa fa-ticket"></i> '.tmPagoVentaEsc($venta["codigo"]).'</span>
          '.etiquetaPagoVenta($venta).'
        </div>
        <h3>'.tmPagoVentaEsc($clienteNombre).'</h3>
        <div class="pago-venta-total">'.$totalFormateado.'<span>Total venta</span></div>
        <div class="pago-venta-info">
          <div><span>Vendedor</span><strong>'.tmPagoVentaEsc($vendedorNombre).'</strong></div>
          <div><span>Cajero</span><strong>'.tmPagoVentaEsc($cajeroNombre).'</strong></div>
          <div><span>Fecha</span><strong>'.tmPagoVentaEsc($fecha).'</strong></div>
          <div><span>Proceso</span><strong>'.tmPagoVentaEsc($proceso).'</strong></div>
        </div>
        <div class="pago-venta-card-footer">
          <span><i class="fa fa-mouse-pointer"></i> Ver detalle y acciones</span>
          <i class="fa fa-chevron-right"></i>
        </div>
        <div class="pago-venta-actions-template" style="display:none">'.accionesPagoVenta($venta, $modo).'</div>
      </article>';
  }
}

?>
<div class="content-wrapper pagos-ventas-wrapper">
  <style>
    .pagos-ventas-wrapper .content{
      padding-top:10px;
    }
    .pagos-ventas-hero{
      position:relative;
      border:1px solid rgba(184,205,232,.62);
      border-radius:18px;
      background:linear-gradient(135deg, rgba(16,43,59,.92), rgba(23,107,155,.86));
      color:#fff;
      padding:18px 20px;
      margin-bottom:14px;
      overflow:hidden;
      box-shadow:0 18px 38px rgba(15,23,42,.12);
    }
    .pagos-ventas-hero:after{
      content:"";
      position:absolute;
      right:-58px;
      top:-72px;
      width:210px;
      height:210px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .pagos-ventas-hero h1{
      position:relative;
      z-index:1;
      margin:0 0 5px;
      font-size:24px;
      font-weight:950;
    }
    .pagos-ventas-hero p{
      position:relative;
      z-index:1;
      margin:0;
      max-width:780px;
      color:rgba(255,255,255,.86);
      font-size:13px;
      font-weight:750;
      line-height:1.35;
    }
    .pagos-ventas-kpis{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:10px;
      margin-bottom:12px;
    }
    .pagos-ventas-kpi{
      border:1px solid rgba(184,205,232,.66);
      border-radius:15px;
      background:rgba(255,255,255,.72);
      padding:12px;
      display:flex;
      align-items:center;
      gap:10px;
      box-shadow:0 12px 26px rgba(15,23,42,.06);
    }
    .pagos-ventas-kpi i{
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
    .pagos-ventas-kpi span{
      display:block;
      color:#6b7d91;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pagos-ventas-kpi strong{
      display:block;
      color:#162235;
      font-size:18px;
      font-weight:950;
      line-height:1.15;
      margin-top:2px;
    }
    .pagos-ventas-panel{
      border:1px solid rgba(184,205,232,.68);
      border-radius:17px;
      background:rgba(255,255,255,.62);
      box-shadow:0 16px 38px rgba(15,23,42,.07);
      overflow:hidden;
    }
    .pagos-ventas-toolbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding:12px;
      border-bottom:1px solid rgba(184,205,232,.58);
      background:rgba(255,255,255,.45);
    }
    .pagos-ventas-toolbar h3{
      margin:0;
      color:#1d2b3d;
      font-size:16px;
      font-weight:950;
    }
    .pagos-ventas-search{
      position:relative;
      width:min(360px, 100%);
    }
    .pagos-ventas-search i{
      position:absolute;
      left:12px;
      top:50%;
      transform:translateY(-50%);
      color:#7b8fa5;
    }
    .pagos-ventas-search input{
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
    .pagos-ventas-panel .nav-tabs{
      border-bottom:1px solid rgba(184,205,232,.62);
      padding:0 12px;
      background:rgba(255,255,255,.42);
    }
    .pagos-ventas-panel .nav-tabs>li>a{
      border:0;
      border-radius:0;
      color:#52657a;
      font-weight:900;
      padding:12px 14px;
    }
    .pagos-ventas-panel .nav-tabs>li.active>a,
    .pagos-ventas-panel .nav-tabs>li.active>a:hover,
    .pagos-ventas-panel .nav-tabs>li.active>a:focus{
      border:0;
      border-bottom:3px solid #3c8dbc;
      color:#173b5d;
      background:transparent;
    }
    .pagos-ventas-panel .tab-content{padding:12px;}
    .pago-venta-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(218px, 1fr));
      gap:10px;
    }
    .pago-venta-card{
      position:relative;
      min-height:214px;
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
    .pago-venta-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#f39c12;
    }
    .pago-venta-card.estado-info:before{background:#3c8dbc;}
    .pago-venta-card.estado-success:before{background:#00a65a;}
    .pago-venta-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 18px 36px rgba(15,23,42,.12);
    }
    .pago-venta-card-top{
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:flex-start;
      padding-left:4px;
    }
    .pago-venta-card .label{
      max-width:96px;
      white-space:normal;
      line-height:1.18;
      padding:4px 7px;
      border-radius:999px;
      font-size:8.8px;
      font-weight:900;
      color:#fff !important;
      text-align:center;
    }
    .pago-venta-code{
      display:inline-flex;
      align-items:center;
      gap:5px;
      color:#176b9b;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pago-venta-card h3{
      margin:0;
      color:#1f2d3d;
      font-size:14px;
      font-weight:950;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .pago-venta-total{
      padding:8px;
      border-radius:10px;
      background:linear-gradient(135deg,rgba(60,141,188,.12),rgba(0,172,214,.10));
      color:#176b9b;
      font-size:18px;
      font-weight:950;
      line-height:1.1;
    }
    .pago-venta-total span{
      display:block;
      margin-top:3px;
      color:#60717f;
      font-size:9.5px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pago-venta-info{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:6px;
    }
    .pago-venta-info div{
      border:1px solid #edf2f6;
      background:#f8fbfd;
      border-radius:8px;
      padding:6px;
      min-height:40px;
    }
    .pago-venta-info span{
      display:block;
      margin-bottom:3px;
      color:#7b8b96;
      font-size:8.8px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pago-venta-info strong{
      display:block;
      color:#263845;
      font-size:10.5px;
      font-weight:850;
      line-height:1.22;
      overflow-wrap:anywhere;
    }
    .pago-venta-card-footer{
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
    .pago-venta-empty{
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
    .pago-venta-modal .modal-dialog{width:min(820px, calc(100vw - 36px));}
    .pago-venta-modal .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      background:#f6f9fc;
      box-shadow:0 24px 64px rgba(10,30,45,.30);
    }
    .pago-venta-modal .modal-header{
      position:relative;
      color:#fff;
      border:0;
      padding:14px 18px;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 62%,#36aee2 100%);
      overflow:hidden;
    }
    .pago-venta-modal.estado-warning .modal-header{background:linear-gradient(135deg,#f39c12,#d98200);}
    .pago-venta-modal.estado-success .modal-header{background:linear-gradient(135deg,#00a65a,#087a46);}
    .pago-venta-modal.estado-info .modal-header{background:linear-gradient(135deg,#176b9b,#36aee2);}
    .pago-venta-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-36px;
      top:-54px;
      width:154px;
      height:154px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .pago-venta-modal-title{
      position:relative;
      z-index:2;
      display:flex;
      align-items:center;
      gap:10px;
      max-width:88%;
    }
    .pago-venta-modal-icon{
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
    .pago-venta-modal-kicker{
      display:inline-block;
      margin-bottom:3px;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
      letter-spacing:.04em;
      color:#eff9ff;
    }
    .pago-venta-modal h4{margin:0;font-size:18px;font-weight:950;line-height:1.2;}
    .pago-venta-modal .close{
      position:relative;
      z-index:3;
      color:#fff;
      opacity:.9;
      text-shadow:none;
      width:30px;
      height:30px;
      border-radius:50%;
      background:rgba(255,255,255,.18);
      line-height:28px;
      font-size:22px;
    }
    .pago-venta-modal .modal-body{padding:12px;}
    .pago-venta-detail-total{
      margin-bottom:8px;
      padding:10px;
      border:1px solid #dce8f1;
      border-radius:10px;
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
    .pago-venta-detail-total span{
      display:block;
      color:#60717f;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pago-venta-detail-total strong{
      white-space:nowrap;
    }
    .pago-venta-detail-grid{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:7px;
    }
    .pago-venta-detail-item{
      border:1px solid #e2ebf2;
      border-radius:9px;
      background:#fff;
      padding:8px;
      min-height:58px;
    }
    .pago-venta-detail-item span{
      display:block;
      margin-bottom:4px;
      color:#7b8b96;
      font-size:9.5px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pago-venta-detail-item strong{
      color:#263845;
      font-size:12px;
      font-weight:850;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .pago-venta-modal-actions{
      margin-top:8px;
      padding:8px;
      border:1px solid #dce8f1;
      border-radius:9px;
      background:#fff;
      display:flex;
      flex-wrap:wrap;
      justify-content:flex-end;
      gap:6px;
    }
    .pago-venta-modal-actions:before{
      content:"Acciones disponibles";
      margin-right:auto;
      align-self:center;
      color:#60717f;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .pago-venta-modal-actions .btn{
      border-radius:8px;
      font-weight:900;
      padding:6px 9px;
      font-size:12px;
    }
    .modal-cobro-venta .modal-dialog{width:min(620px, calc(100vw - 36px));}
    .modal-cobro-venta .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 26px 70px rgba(15,23,42,.28);
    }
    .modal-cobro-venta .modal-header{
      position:relative;
      border:0;
      color:#fff;
      padding:14px 18px;
      background:linear-gradient(135deg,#176b9b,#36aee2);
      overflow:hidden;
    }
    .modal-cobro-venta .modal-header:after{
      content:"";
      position:absolute;
      right:-42px;
      top:-66px;
      width:150px;
      height:150px;
      border-radius:50%;
      background:rgba(255,255,255,.14);
    }
    .modal-cobro-title{
      position:relative;
      z-index:1;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .modal-cobro-title i{
      width:40px;
      height:40px;
      border-radius:13px;
      background:rgba(255,255,255,.18);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:18px;
    }
    .modal-cobro-title h4{
      margin:0;
      font-size:19px;
      font-weight:950;
    }
    .modal-cobro-title span{
      display:block;
      color:rgba(255,255,255,.86);
      font-size:11px;
      font-weight:800;
    }
    .modal-cobro-venta .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.92;
      text-shadow:none;
    }
    .modal-cobro-venta .modal-body{
      padding:14px;
      background:#f5f8fc;
    }
    .modal-cobro-venta .form-group label{
      color:#60748b;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
    }
    .modal-cobro-venta .form-control,
    .modal-cobro-venta .input-group-addon{
      border-color:#dbe7f0;
      box-shadow:none;
      font-weight:850;
    }
    .modal-cobro-venta .modal-footer{
      border-top:1px solid #e4eef5;
      background:#fff;
      padding:10px 14px;
    }
    .modal-cobro-venta .modal-footer .btn{
      border-radius:9px;
      font-weight:900;
      padding:7px 12px;
    }
    body.tm-dark-mode .pagos-ventas-panel,
    body.dark-mode .pagos-ventas-panel,
    body.tm-dark-mode .pagos-ventas-kpi,
    body.dark-mode .pagos-ventas-kpi,
    body.tm-dark-mode .pago-venta-card,
    body.dark-mode .pago-venta-card,
    body.tm-dark-mode .pago-venta-detail-total,
    body.dark-mode .pago-venta-detail-total,
    body.tm-dark-mode .pago-venta-detail-item,
    body.dark-mode .pago-venta-detail-item,
    body.tm-dark-mode .pago-venta-modal-actions,
    body.dark-mode .pago-venta-modal-actions{
      background:rgba(15,27,48,.72);
      border-color:rgba(147,197,253,.18);
      color:#edf5ff;
    }
    body.tm-dark-mode .pagos-ventas-toolbar,
    body.dark-mode .pagos-ventas-toolbar,
    body.tm-dark-mode .pagos-ventas-panel .nav-tabs,
    body.dark-mode .pagos-ventas-panel .nav-tabs{background:rgba(15,27,48,.38);}
    body.tm-dark-mode .pagos-ventas-toolbar h3,
    body.dark-mode .pagos-ventas-toolbar h3,
    body.tm-dark-mode .pagos-ventas-kpi strong,
    body.dark-mode .pagos-ventas-kpi strong,
    body.tm-dark-mode .pago-venta-card h3,
    body.dark-mode .pago-venta-card h3,
    body.tm-dark-mode .pago-venta-info strong,
    body.dark-mode .pago-venta-info strong,
    body.tm-dark-mode .pago-venta-detail-item strong,
    body.dark-mode .pago-venta-detail-item strong{
      color:#fff;
    }
    body.tm-dark-mode .pago-venta-info div,
    body.dark-mode .pago-venta-info div{background:rgba(15,27,48,.58);border-color:rgba(147,197,253,.18);}
    body.tm-dark-mode .pago-venta-modal .modal-body,
    body.dark-mode .pago-venta-modal .modal-body,
    body.tm-dark-mode .modal-cobro-venta .modal-body,
    body.dark-mode .modal-cobro-venta .modal-body{background:#0d1729;}
    body.tm-dark-mode .modal-cobro-venta .modal-footer,
    body.dark-mode .modal-cobro-venta .modal-footer{background:rgba(15,27,48,.92);border-color:rgba(147,197,253,.18);}
    @media(max-width:767px){
      .pagos-ventas-kpis{grid-template-columns:1fr;}
      .pagos-ventas-toolbar{align-items:flex-start;flex-direction:column;}
      .pago-venta-grid{grid-template-columns:1fr;}
      .pago-venta-detail-grid{grid-template-columns:1fr;}
    }
  </style>

  <section class="content-header">
    <h1>Cobro de ventas</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Cobro de ventas</li>
    </ol>
  </section>

  <section class="content">
    <div class="pagos-ventas-hero">
      <h1><i class="fa fa-credit-card"></i> Caja de ventas</h1>
      <p>Revise ventas pendientes, registre el cobro y reimprima las boletas necesarias sin volver a tablas largas.</p>
    </div>

    <div class="pagos-ventas-kpis">
      <div class="pagos-ventas-kpi">
        <i class="fa fa-clock-o"></i>
        <div><span>Pendientes de cobro</span><strong><?php echo count($ventasPendientes); ?> venta(s)</strong></div>
      </div>
      <div class="pagos-ventas-kpi">
        <i class="fa fa-money"></i>
        <div><span>Total pendiente</span><strong>Bs <?php echo number_format($totalPendientePagoVentas, 2); ?></strong></div>
      </div>
      <div class="pagos-ventas-kpi">
        <i class="fa fa-check-circle"></i>
        <div><span>Cobrado registrado</span><strong>Bs <?php echo number_format($totalCobradoPagoVentas, 2); ?></strong></div>
      </div>
    </div>

    <div class="pagos-ventas-panel nav-tabs-custom">
      <div class="pagos-ventas-toolbar">
        <h3><i class="fa fa-list-alt"></i> Seguimiento de cobros</h3>
        <div class="pagos-ventas-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarPagoVentasCards" placeholder="Buscar por codigo, cliente, cajero o estado">
        </div>
      </div>
      <ul class="nav nav-tabs">
        <li class="active"><a href="#tabVentasPendientesCobro" data-toggle="tab">Pendientes de cobro <span class="badge bg-yellow"><?php echo count($ventasPendientes); ?></span></a></li>
        <li><a href="#tabVentasCobradas" data-toggle="tab">Cobros completados <span class="badge bg-green"><?php echo count($ventasCobradas); ?></span></a></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane active" id="tabVentasPendientesCobro">
          <div class="pago-venta-grid"><?php renderTarjetasPagosVentas($ventasPendientes, "pendientes"); ?></div>
        </div>

        <div class="tab-pane" id="tabVentasCobradas">
          <div class="pago-venta-grid"><?php renderTarjetasPagosVentas($ventasCobradas, "cobradas"); ?></div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalDetallePagoVenta" class="modal fade pago-venta-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="pago-venta-modal-title">
          <div class="pago-venta-modal-icon"><i class="fa fa-credit-card"></i></div>
          <div>
            <span class="pago-venta-modal-kicker" id="pagoVentaModalEstado">Detalle</span>
            <h4 id="pagoVentaModalTitulo">Venta</h4>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div class="pago-venta-detail-total"><span>Total a cobrar</span><strong id="pagoVentaModalTotal">Bs 0.00</strong></div>
        <div class="pago-venta-detail-grid">
          <div class="pago-venta-detail-item"><span>Cliente</span><strong id="pagoVentaModalCliente">-</strong></div>
          <div class="pago-venta-detail-item"><span>Vendedor</span><strong id="pagoVentaModalVendedor">-</strong></div>
          <div class="pago-venta-detail-item"><span>Cajero</span><strong id="pagoVentaModalCajero">-</strong></div>
          <div class="pago-venta-detail-item"><span>Fecha</span><strong id="pagoVentaModalFecha">-</strong></div>
          <div class="pago-venta-detail-item"><span>Proceso</span><strong id="pagoVentaModalProceso">-</strong></div>
          <div class="pago-venta-detail-item"><span>Codigo venta</span><strong id="pagoVentaModalCodigo">-</strong></div>
        </div>
        <div class="pago-venta-modal-actions" id="pagoVentaModalAcciones"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalCobrarVenta" class="modal fade modal-cobro-venta" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="modal-cobro-title">
          <i class="fa fa-money"></i>
          <div>
            <h4>Registrar pago</h4>
            <span>Confirme metodo, recibido y cambio antes de cobrar.</span>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <input type="hidden" id="cobroIdVenta">
        <div class="form-group">
          <label>Total a cobrar</label>
          <div class="input-group">
            <span class="input-group-addon">Bs</span>
            <input type="text" class="form-control" id="cobroTotal" readonly>
          </div>
        </div>
        <div class="form-group">
          <label>Metodo de pago</label>
          <select class="form-control" id="cobroMetodoPago">
            <option value="Efectivo">Efectivo</option>
            <option value="QR">QR</option>
            <option value="Tarjeta Credito">Tarjeta Credito</option>
            <option value="Tarjeta Debito">Tarjeta Debito</option>
          </select>
        </div>
        <div class="form-group" id="grupoMontoRecibido">
          <label>Monto recibido</label>
          <div class="input-group">
            <span class="input-group-addon">Bs</span>
            <input type="number" class="form-control" id="cobroMontoRecibido" min="0" step="0.01">
          </div>
        </div>
        <div class="form-group">
          <label>Cambio</label>
          <div class="input-group">
            <span class="input-group-addon">Bs</span>
            <input type="text" class="form-control" id="cobroCambio" readonly>
          </div>
        </div>
        <div class="form-group" id="grupoCodigoTransaccion" style="display:none">
          <label>Codigo / referencia de transaccion</label>
          <input type="text" class="form-control" id="cobroCodigoTransaccion">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnConfirmarCobro">Confirmar cobro</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){
  $('[title]').tooltip({container:'body'});
});

function filtrarPagoVentasCards(){
  var termino = ($("#buscarPagoVentasCards").val() || "").toString().toLowerCase();

  $(".pago-venta-grid").each(function(){
    var $grid = $(this);
    var visibles = 0;
    $grid.find(".pago-venta-empty.busqueda-vacia").remove();

    $grid.find(".pago-venta-card").each(function(){
      var $card = $(this);
      var coincide = !termino || (($card.data("search") || $card.text()).toString().toLowerCase().indexOf(termino) !== -1);
      $card.toggle(coincide);
      if(coincide){
        visibles++;
      }
    });

    if(visibles === 0 && $grid.find(".pago-venta-card").length > 0){
      $grid.append('<div class="pago-venta-empty busqueda-vacia"><i class="fa fa-search"></i> No hay cobros que coincidan con la busqueda.</div>');
    }
  });
}

$(document).on("input", "#buscarPagoVentasCards", filtrarPagoVentasCards);
$(document).on("shown.bs.tab", 'a[data-toggle="tab"]', filtrarPagoVentasCards);

$(document).on("click", ".pago-venta-card", function(e){
  if($(e.target).closest("button, a, .btn").length){
    return;
  }

  var $card = $(this);
  var estadoClase = $card.data("estado-clase") || "info";

  $("#modalDetallePagoVenta")
    .removeClass("estado-warning estado-info estado-success")
    .addClass("estado-" + estadoClase);
  $("#pagoVentaModalEstado").text($card.data("estado") || "Detalle");
  $("#pagoVentaModalTitulo").text("Venta " + ($card.data("codigo") || ""));
  $("#pagoVentaModalTotal").text($card.data("total") || "Bs 0.00");
  $("#pagoVentaModalCliente").text($card.data("cliente") || "-");
  $("#pagoVentaModalVendedor").text($card.data("vendedor") || "-");
  $("#pagoVentaModalCajero").text($card.data("cajero") || "-");
  $("#pagoVentaModalFecha").text($card.data("fecha") || "-");
  $("#pagoVentaModalProceso").text($card.data("proceso") || "-");
  $("#pagoVentaModalCodigo").text($card.data("codigo") || "-");
  $("#pagoVentaModalAcciones").html($card.find(".pago-venta-actions-template").html());
  $("#modalDetallePagoVenta").modal("show");
});

$(document).on("keydown", ".pago-venta-card", function(e){
  if(e.key === "Enter" || e.key === " "){
    e.preventDefault();
    $(this).trigger("click");
  }
});

$(document).on("click", ".btnImprimirBoletaCaja", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-caja.php?idVenta=" + $(this).attr("idVenta") + "&codigo=" + $(this).attr("codigoVenta"), "_blank");
});

$(document).on("click", ".btnImprimirBoletaDespacho", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-despacho.php?idVenta=" + $(this).attr("idVenta") + "&codigo=" + $(this).attr("codigoVenta"), "_blank");
});

$(document).on("click", ".btnImprimirFacturaVenta", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/factura.php?idVenta=" + $(this).attr("idVenta") + "&codigo=" + $(this).attr("codigoVenta"), "_blank");
});

$(document).on("click", ".btnImprimirConformidadVenta", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/conformidad.php?idVenta=" + $(this).attr("idVenta"), "_blank");
});

$(document).on("click", ".btnAprobarPagoVenta", function(e){
  e.preventDefault();
  e.stopPropagation();
  var total = Number($(this).attr("totalVenta"));

  $("#modalDetallePagoVenta").modal("hide");
  $("#cobroIdVenta").val($(this).attr("idVenta"));
  $("#cobroTotal").val(total.toFixed(2));
  $("#cobroMontoRecibido").val(total.toFixed(2));
  $("#cobroCambio").val("0.00");
  $("#cobroCodigoTransaccion").val("");
  $("#cobroMetodoPago").val("Efectivo").trigger("change");
  $("#modalCobrarVenta").modal("show");
});

$("#cobroMetodoPago").on("change", function(){
  var metodo = $(this).val();
  var total = Number($("#cobroTotal").val()) || 0;

  if(metodo === "Efectivo"){
    $("#grupoMontoRecibido").show();
    $("#grupoCodigoTransaccion").hide();
    $("#cobroMontoRecibido").val(total.toFixed(2));
  }else{
    $("#grupoMontoRecibido").hide();
    $("#grupoCodigoTransaccion").show();
    $("#cobroMontoRecibido").val(total.toFixed(2));
  }

  calcularCambioCobro();
});

$("#cobroMontoRecibido").on("input change", calcularCambioCobro);

function calcularCambioCobro(){
  var total = Number($("#cobroTotal").val()) || 0;
  var recibido = Number($("#cobroMontoRecibido").val()) || 0;
  var cambio = Math.max(0, recibido - total);
  $("#cobroCambio").val(cambio.toFixed(2));
}

$("#btnConfirmarCobro").on("click", function(){
  var idVenta = $("#cobroIdVenta").val();
  var metodo = $("#cobroMetodoPago").val();
  var total = Number($("#cobroTotal").val()) || 0;
  var recibido = Number($("#cobroMontoRecibido").val()) || 0;
  var codigoTransaccion = $("#cobroCodigoTransaccion").val();

  if(metodo === "Efectivo" && recibido < total){
    swal({
      title: "Monto insuficiente",
      text: "El monto recibido no cubre el total de la venta.",
      type: "error",
      confirmButtonText: "Cerrar"
    });
    return;
  }

  swal({
    title: "Confirmar cobro",
    text: "Se imprimira la boleta para almacen.",
    type: "warning",
    showCancelButton: true,
    confirmButtonText: "Si, cobrar",
    cancelButtonText: "Cancelar"
  }).then(function(result){
    if(result.value){
      window.location = "index.php?ruta=pagos-ventas&aprobarPagoVenta=" + idVenta +
        "&metodoPago=" + encodeURIComponent(metodo) +
        "&montoRecibido=" + encodeURIComponent(recibido) +
        "&codigoTransaccion=" + encodeURIComponent(codigoTransaccion);
    }
  });
});
</script>

<?php
$aprobarPago = new ControladorVentas();
$aprobarPago->ctrAprobarPagoVenta();
?>
