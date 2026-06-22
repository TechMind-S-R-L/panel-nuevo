<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

function tmTarifaEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmTarifaMoney($valor){
  return "Bs ".number_format((float)($valor ?? 0), 2);
}

function tmTarifaEstado($estado){
  return ((int)$estado === 1)
    ? '<span class="tm-service-status status-active">Activo</span>'
    : '<span class="tm-service-status status-off">Inactivo</span>';
}

function tmTarifaTotalReferencial($precio){
  return (float)($precio["mano_obra_base"] ?? 0)
    + (float)($precio["precio_por_camara"] ?? 0)
    + (float)($precio["precio_por_metro"] ?? 0)
    + (float)($precio["precio_canalizacion_metro"] ?? 0)
    + (float)($precio["costo_visita"] ?? 0)
    + (float)($precio["costo_diagnostico"] ?? 0)
    + (float)($precio["recargo_altura"] ?? 0)
    + (float)($precio["recargo_urgencia"] ?? 0)
    + (float)($precio["costo_transporte"] ?? 0);
}

function tmTarifaPrincipales($precio){
  $campos = array(
    "mano_obra_base" => "Mano obra",
    "precio_por_camara" => "Camara",
    "precio_por_metro" => "Metro cable",
    "precio_canalizacion_metro" => "Canalizacion",
    "costo_visita" => "Visita",
    "costo_diagnostico" => "Diagnostico",
    "recargo_altura" => "Altura",
    "recargo_urgencia" => "Urgencia",
    "costo_transporte" => "Transporte"
  );
  $items = array();

  foreach($campos as $campo => $label){
    if((float)($precio[$campo] ?? 0) > 0){
      $items[] = '<span><b>'.$label.'</b> '.tmTarifaMoney($precio[$campo]).'</span>';
    }
  }

  if(empty($items)){
    $items[] = '<span><b>Sin costos</b> Bs 0.00</span>';
  }

  return implode("", array_slice($items, 0, 4));
}

$precios = ControladorServicios::ctrMostrarPrecios();
$precios = is_array($precios) ? $precios : array();
$serviciosSinTarifa = array("Soporte tecnico en taller", "Desarrollo de software", "Redes e infraestructura");
$preciosVista = array_values(array_filter($precios, function($precio) use ($serviciosSinTarifa) {
  return !in_array($precio["tipo_servicio"], $serviciosSinTarifa, true);
}));

$serviciosTarjetas = array(
  array(
    "nombre" => "Instalacion de camaras",
    "icono" => "fa-video-camera",
    "tono" => "blue",
    "texto" => "Tarifa por camara, cable, canalizacion y recargos.",
    "guia" => "Use esta tarifa para instalaciones nuevas. La venta calcula camaras, metros de cable, canalizacion, altura, urgencia y transporte.",
    "campos" => "instalacion,camara,metro,canalizacion,altura,urgencia,transporte"
  ),
  array(
    "nombre" => "Mantenimiento de camaras",
    "icono" => "fa-wrench",
    "tono" => "green",
    "texto" => "Mano de obra, visita y camaras revisadas.",
    "guia" => "Use esta tarifa cuando el trabajo sea revisar, limpiar o mantener camaras existentes.",
    "campos" => "mano,camara,visita,urgencia,transporte"
  ),
  array(
    "nombre" => "Reubicacion de camaras",
    "icono" => "fa-exchange",
    "tono" => "orange",
    "texto" => "Mover camaras con cableado y canalizacion si aplica.",
    "guia" => "Use esta tarifa para retirar y reinstalar camaras en otra ubicacion.",
    "campos" => "instalacion,mano,camara,metro,canalizacion,altura,urgencia,transporte"
  ),
  array(
    "nombre" => "Diagnostico tecnico",
    "icono" => "fa-search",
    "tono" => "purple",
    "texto" => "Visita, revision tecnica e informe.",
    "guia" => "Use esta tarifa cuando solo se cobrara visita y diagnostico documentado.",
    "campos" => "visita,diagnostico,urgencia,transporte"
  ),
  array(
    "nombre" => "Domotica",
    "icono" => "fa-home",
    "tono" => "cyan",
    "texto" => "Automatizacion por tipo de dispositivo o alcance.",
    "guia" => "Use esta tarifa para sensores, luces, chapas, puertas y automatizacion integral.",
    "campos" => "instalacion,mano,metro,canalizacion,visita,urgencia,transporte"
  ),
  array(
    "nombre" => "Instalacion de alarmas",
    "icono" => "fa-shield",
    "tono" => "red",
    "texto" => "Alarmas, cableado, canalizacion y visita.",
    "guia" => "Use esta tarifa para instalacion de sistemas de alarma en hogares o empresas.",
    "campos" => "mano,metro,canalizacion,visita,urgencia,transporte"
  )
);

$resumenServicios = array();
foreach($serviciosTarjetas as $servicio){
  $resumenServicios[$servicio["nombre"]] = array("total" => 0, "activos" => 0);
}
foreach($preciosVista as $precio){
  $tipo = $precio["tipo_servicio"];
  if(!isset($resumenServicios[$tipo])){
    $resumenServicios[$tipo] = array("total" => 0, "activos" => 0);
  }
  $resumenServicios[$tipo]["total"]++;
  if((int)$precio["estado"] === 1){
    $resumenServicios[$tipo]["activos"]++;
  }
}

$tarifasActivas = count(array_filter($preciosVista, function($precio){
  return (int)($precio["estado"] ?? 0) === 1;
}));
?>

<div class="content-wrapper tm-service-prices-wrapper">
  <style>
    .tm-service-prices-hero{
      border-radius:18px;
      padding:20px;
      margin-bottom:16px;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#196da0 55%,#20b7df);
      box-shadow:0 20px 46px rgba(15,23,42,.16);
      display:flex;
      justify-content:space-between;
      gap:16px;
      align-items:center;
      overflow:hidden;
      position:relative;
    }
    .tm-service-prices-hero:after{
      content:"";
      position:absolute;
      right:-85px;
      top:-105px;
      width:250px;
      height:250px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-service-prices-hero h2{
      margin:0;
      font-size:25px;
      font-weight:950;
      position:relative;
      z-index:1;
    }
    .tm-service-prices-hero p{
      margin:6px 0 0;
      max-width:760px;
      color:rgba(255,255,255,.88);
      font-weight:750;
      position:relative;
      z-index:1;
    }
    .tm-service-prices-hero .tm-hero-count{
      position:relative;
      z-index:1;
      min-width:150px;
      border:1px solid rgba(255,255,255,.26);
      border-radius:16px;
      padding:12px 14px;
      text-align:center;
      background:rgba(255,255,255,.12);
    }
    .tm-hero-count strong{
      display:block;
      font-size:30px;
      font-weight:950;
      line-height:1;
    }
    .tm-hero-count span{
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      color:rgba(255,255,255,.82);
    }
    .tm-service-catalog{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(235px, 1fr));
      gap:12px;
      margin-bottom:16px;
    }
    .tm-service-type-card{
      border:1px solid rgba(184,205,232,.72);
      border-radius:16px;
      background:rgba(255,255,255,.78);
      padding:14px;
      min-height:180px;
      cursor:pointer;
      box-shadow:0 16px 34px rgba(15,23,42,.07);
      display:flex;
      flex-direction:column;
      gap:10px;
      transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease;
      position:relative;
      overflow:hidden;
    }
    .tm-service-type-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 22px 42px rgba(15,23,42,.13);
    }
    .tm-service-type-card:after{
      content:"";
      position:absolute;
      right:-36px;
      bottom:-44px;
      width:116px;
      height:116px;
      border-radius:50%;
      background:rgba(60,141,188,.11);
    }
    .tm-service-type-icon{
      width:44px;
      height:44px;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:19px;
      background:linear-gradient(135deg,#1f6cad,#20b7df);
      box-shadow:0 12px 24px rgba(31,108,173,.20);
    }
    .tm-service-type-card[data-tone="green"] .tm-service-type-icon{background:linear-gradient(135deg,#008d4c,#00a65a);}
    .tm-service-type-card[data-tone="orange"] .tm-service-type-icon{background:linear-gradient(135deg,#c56a00,#f39c12);}
    .tm-service-type-card[data-tone="purple"] .tm-service-type-icon{background:linear-gradient(135deg,#605ca8,#8e7cc3);}
    .tm-service-type-card[data-tone="cyan"] .tm-service-type-icon{background:linear-gradient(135deg,#008da5,#00c0ef);}
    .tm-service-type-card[data-tone="red"] .tm-service-type-icon{background:linear-gradient(135deg,#b7372a,#dd4b39);}
    .tm-service-type-card h3{
      margin:0;
      color:#1f2d3d;
      font-size:16px;
      line-height:1.2;
      font-weight:950;
      position:relative;
      z-index:1;
    }
    .tm-service-type-card p{
      margin:0;
      color:#64758a;
      font-size:12px;
      line-height:1.35;
      font-weight:750;
      position:relative;
      z-index:1;
      flex:1;
    }
    .tm-service-type-foot{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:8px;
      position:relative;
      z-index:1;
    }
    .tm-service-type-foot span{
      color:#176b9b;
      font-weight:950;
      font-size:11px;
      text-transform:uppercase;
    }
    .tm-service-pill{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:30px;
      height:24px;
      padding:0 8px;
      border-radius:999px;
      background:#e8f3fc;
      color:#176b9b;
      font-size:11px;
      font-weight:950;
    }
    .tm-tariff-panel{
      border:1px solid rgba(184,205,232,.70);
      border-radius:18px;
      background:rgba(255,255,255,.72);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      overflow:hidden;
    }
    .tm-tariff-toolbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
      padding:15px 16px;
      border-bottom:1px solid rgba(184,205,232,.62);
      background:rgba(255,255,255,.62);
    }
    .tm-tariff-toolbar h3{
      margin:0;
      color:#1f2d3d;
      font-size:18px;
      font-weight:950;
    }
    .tm-tariff-toolbar p{
      margin:3px 0 0;
      color:#6a7a8f;
      font-size:12px;
      font-weight:750;
    }
    .tm-tariff-search{
      position:relative;
      width:min(390px, 100%);
      flex:0 0 min(390px, 100%);
    }
    .tm-tariff-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#3c8dbc;
    }
    .tm-tariff-search input{
      width:100%;
      border:1px solid rgba(184,205,232,.85);
      border-radius:12px;
      padding:11px 14px 11px 36px;
      background:rgba(255,255,255,.86);
      color:#1f2d3d;
      font-weight:800;
      outline:0;
    }
    .tm-tariff-body{padding:16px;}
    .tm-tariff-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(255px, 1fr));
      gap:12px;
    }
    .tm-tariff-card{
      border:1px solid rgba(184,205,232,.72);
      border-radius:15px;
      background:rgba(255,255,255,.84);
      padding:13px;
      min-height:218px;
      display:flex;
      flex-direction:column;
      gap:10px;
      cursor:pointer;
      position:relative;
      overflow:hidden;
      box-shadow:0 14px 30px rgba(15,23,42,.07);
      transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .tm-tariff-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 20px 38px rgba(15,23,42,.13);
    }
    .tm-tariff-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#9aa8b6;
    }
    .tm-tariff-card.is-active:before{background:#00a65a;}
    .tm-tariff-card.is-off:before{background:#9aa8b6;}
    .tm-tariff-head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:10px;
      padding-left:4px;
    }
    .tm-tariff-head h4{
      margin:0;
      color:#203047;
      font-size:15px;
      line-height:1.2;
      font-weight:950;
      overflow-wrap:anywhere;
    }
    .tm-tariff-head small{
      display:block;
      margin-top:4px;
      color:#66788d;
      font-weight:850;
    }
    .tm-service-status{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      min-height:24px;
      padding:5px 9px;
      color:#fff !important;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      line-height:1.1;
      white-space:normal;
      text-align:center;
    }
    .tm-service-status.status-active{background:#00a65a;}
    .tm-service-status.status-off{background:#7e8b9a;}
    .tm-tariff-total{
      border-radius:13px;
      padding:10px 12px;
      background:linear-gradient(135deg, rgba(60,141,188,.13), rgba(0,192,239,.08));
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      gap:8px;
    }
    .tm-tariff-total strong{
      color:#0b4e78;
      font-size:20px;
      font-weight:950;
    }
    .tm-tariff-total span{
      color:#65788d;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
      text-align:right;
    }
    .tm-tariff-values{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      flex:1;
    }
    .tm-tariff-values span{
      border:1px solid rgba(184,205,232,.62);
      border-radius:999px;
      background:rgba(248,251,255,.84);
      color:#4c5f76;
      padding:5px 8px;
      font-size:10.5px;
      font-weight:800;
    }
    .tm-tariff-values b{
      color:#203047;
      margin-right:4px;
    }
    .tm-tariff-foot{
      display:flex;
      align-items:center;
      justify-content:space-between;
      border-top:1px dashed rgba(184,205,232,.76);
      padding-top:9px;
      color:#176b9b;
      font-size:11px;
      font-weight:950;
    }
    .tm-tariff-empty{
      min-height:210px;
      border:1px dashed rgba(60,141,188,.35);
      border-radius:16px;
      color:#6d7f93;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:8px;
      text-align:center;
      background:rgba(255,255,255,.58);
    }
    .tm-tariff-empty i{font-size:34px;color:#3c8dbc;}
    .tm-tariff-modal .modal-dialog{width:min(920px, calc(100vw - 28px));}
    .tm-tariff-modal .modal-content{
      border:0;
      border-radius:20px;
      overflow:hidden;
      box-shadow:0 30px 76px rgba(15,23,42,.30);
    }
    .tm-tariff-modal .modal-header{
      position:relative;
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#3c8dbc);
      padding:18px 22px;
    }
    .tm-tariff-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-52px;
      top:-76px;
      width:176px;
      height:176px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-tariff-modal .modal-title{
      position:relative;
      z-index:1;
      font-size:22px;
      font-weight:950;
      line-height:1.15;
    }
    .tm-tariff-modal .modal-title small{
      display:block;
      margin-top:5px;
      color:rgba(255,255,255,.86);
      font-size:12px;
      font-weight:800;
    }
    .tm-tariff-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.9;
      text-shadow:none;
    }
    .tm-tariff-modal-body{
      padding:16px;
      background:#f5f8fc;
    }
    .tm-tariff-guide{
      border:1px solid rgba(184,205,232,.70);
      border-radius:14px;
      background:#fff;
      padding:12px;
      margin-bottom:12px;
      display:flex;
      gap:12px;
      align-items:flex-start;
    }
    .tm-tariff-guide i{
      width:36px;
      height:36px;
      border-radius:12px;
      background:#e8f3fc;
      color:#176b9b;
      display:flex;
      align-items:center;
      justify-content:center;
      flex:0 0 auto;
    }
    .tm-tariff-guide strong{
      display:block;
      color:#203047;
      font-weight:950;
    }
    .tm-tariff-guide span{
      display:block;
      color:#65788d;
      font-size:12px;
      font-weight:750;
      margin-top:3px;
    }
    .tm-tariff-form-grid{
      display:grid;
      grid-template-columns:repeat(4, 1fr);
      gap:10px;
    }
    .tm-tariff-form-box{
      border:1px solid rgba(184,205,232,.74);
      border-radius:13px;
      background:#fff;
      padding:10px;
      min-width:0;
    }
    .tm-tariff-form-box label{
      display:block;
      color:#4d6178;
      font-size:10.5px;
      font-weight:950;
      text-transform:uppercase;
      line-height:1.2;
      min-height:26px;
    }
    .tm-tariff-form-box .input-group-addon{
      border-color:#d6e1ee;
      background:#eef5fc;
      color:#176b9b;
      font-weight:900;
    }
    .tm-tariff-form-box .form-control{
      border-color:#d6e1ee;
      font-weight:850;
      height:36px;
    }
    .tm-tariff-form-box select.form-control{
      border-radius:8px;
    }
    .tm-tariff-active{
      display:flex;
      align-items:center;
      gap:9px;
      min-height:36px;
      margin-top:18px;
      color:#4d6178;
      font-weight:900;
    }
    .tm-tariff-modal .modal-footer{
      border:0;
      padding:14px 16px;
      background:#f5f8fc;
    }
    .tm-tariff-modal .modal-footer .btn{
      border-radius:9px;
      font-weight:900;
      padding:9px 14px;
    }
    body.tm-dark-mode .tm-service-type-card,
    body.tm-dark-mode .tm-tariff-panel,
    body.tm-dark-mode .tm-tariff-card,
    body.dark-mode .tm-service-type-card,
    body.dark-mode .tm-tariff-panel,
    body.dark-mode .tm-tariff-card{
      background:rgba(15,23,42,.72);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-tariff-toolbar,
    body.dark-mode .tm-tariff-toolbar{background:rgba(15,23,42,.52);}
    body.tm-dark-mode .tm-service-type-card h3,
    body.tm-dark-mode .tm-tariff-toolbar h3,
    body.tm-dark-mode .tm-tariff-head h4,
    body.tm-dark-mode .tm-tariff-values b,
    body.dark-mode .tm-service-type-card h3,
    body.dark-mode .tm-tariff-toolbar h3,
    body.dark-mode .tm-tariff-head h4,
    body.dark-mode .tm-tariff-values b{color:#f8fbff;}
    body.tm-dark-mode .tm-tariff-values span,
    body.dark-mode .tm-tariff-values span{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.13);}
    @media (max-width: 991px){
      .tm-tariff-form-grid{grid-template-columns:repeat(2, 1fr);}
    }
    @media (max-width: 767px){
      .tm-service-prices-hero,
      .tm-tariff-toolbar{flex-direction:column;align-items:stretch;}
      .tm-tariff-search{width:100%;flex:auto;}
      .tm-tariff-form-grid{grid-template-columns:1fr;}
    }

    .tm-service-prices-wrapper .content{padding-top:10px;}
    .tm-service-prices-wrapper .tm-service-prices-hero{
      padding:18px;
      border-radius:20px;
      align-items:stretch;
    }
    .tm-service-prices-wrapper .tm-hero-copy{
      display:flex;
      align-items:center;
      gap:14px;
      position:relative;
      z-index:1;
      min-width:0;
    }
    .tm-service-prices-wrapper .tm-hero-icon{
      width:54px;
      height:54px;
      border-radius:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.22);
      font-size:23px;
      flex:0 0 auto;
    }
    .tm-service-prices-wrapper .tm-hero-metrics{
      display:grid;
      grid-template-columns:repeat(2, minmax(118px, 1fr));
      gap:10px;
      position:relative;
      z-index:1;
      min-width:260px;
    }
    .tm-service-prices-wrapper .tm-hero-count{
      min-width:0;
      height:100%;
    }
    .tm-service-flow{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:10px;
      margin:0 0 14px;
    }
    .tm-service-flow-step{
      border:1px solid rgba(184,205,232,.70);
      border-radius:16px;
      background:rgba(255,255,255,.68);
      padding:12px;
      display:flex;
      align-items:center;
      gap:10px;
      box-shadow:0 14px 30px rgba(15,23,42,.06);
    }
    .tm-service-flow-step span{
      width:30px;
      height:30px;
      border-radius:11px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:#e8f3fc;
      color:#176b9b;
      font-weight:950;
      flex:0 0 auto;
    }
    .tm-service-flow-step strong{
      display:block;
      color:#17253a;
      font-size:13px;
      font-weight:950;
    }
    .tm-service-flow-step small{
      display:block;
      color:#6a7a8f;
      font-size:11px;
      font-weight:800;
      margin-top:2px;
    }
    .tm-service-flow-step.is-active{
      border-color:#3c8dbc;
      background:rgba(232,243,252,.78);
    }
    .tm-service-catalog{
      grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    }
    .tm-service-type-card{
      min-height:166px;
      padding:13px;
    }
    .tm-service-type-card.is-selected{
      border-color:#176b9b;
      box-shadow:0 18px 36px rgba(23,107,155,.18);
      background:rgba(235,247,255,.88);
    }
    .tm-service-type-card.is-selected:before{
      content:"Seleccionado";
      position:absolute;
      top:10px;
      right:10px;
      z-index:2;
      border-radius:999px;
      padding:4px 8px;
      background:#176b9b;
      color:#fff;
      font-size:9px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-service-type-foot{
      align-items:flex-end;
    }
    .tm-service-type-foot .btn{
      border-radius:999px;
      font-weight:900;
      padding:5px 10px;
      box-shadow:0 8px 16px rgba(23,107,155,.16);
    }
    .tm-service-type-foot .tm-service-type-counts{
      display:flex;
      flex-wrap:wrap;
      gap:5px;
      justify-content:flex-end;
    }
    .tm-tariff-panel{
      border-radius:20px;
    }
    .tm-tariff-toolbar{
      align-items:flex-start;
      flex-wrap:wrap;
      padding:14px;
    }
    .tm-tariff-toolbar-main{
      min-width:230px;
      flex:1 1 320px;
    }
    .tm-service-selected{
      flex:1 1 360px;
      border:1px solid rgba(184,205,232,.70);
      border-radius:15px;
      background:rgba(248,251,255,.82);
      padding:10px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      min-width:280px;
    }
    .tm-service-selected span{
      display:block;
      color:#6a7a8f;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-service-selected strong{
      display:block;
      color:#1f2d3d;
      font-size:14px;
      font-weight:950;
      line-height:1.2;
    }
    .tm-service-selected-actions{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      justify-content:flex-end;
    }
    .tm-service-selected-actions .btn{
      border-radius:999px;
      font-weight:900;
    }
    .tm-tariff-search{
      flex:0 1 310px;
      width:310px;
    }
    .tm-tariff-body{
      padding:14px;
    }
    .tm-tariff-grid{
      grid-template-columns:repeat(auto-fit, minmax(235px, 1fr));
      align-items:stretch;
    }
    .tm-tariff-card{
      min-height:192px;
      padding:12px;
    }
    .tm-tariff-card.oculto-servicio,
    .tm-tariff-card.oculto-busqueda{
      display:none !important;
    }
    .tm-tariff-modal .modal-dialog{
      width:min(840px, calc(100vw - 36px));
      margin:22px auto;
    }
    .tm-tariff-modal .modal-header{
      padding:15px 18px;
    }
    .tm-tariff-modal .modal-title{
      display:flex;
      align-items:center;
      gap:12px;
      font-size:19px;
    }
    .tm-tariff-modal-icon{
      width:42px;
      height:42px;
      border-radius:15px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.20);
      flex:0 0 auto;
    }
    .tm-tariff-modal-body{
      padding:14px;
    }
    .tm-tariff-form-grid{
      grid-template-columns:repeat(3, 1fr);
    }
    .tm-tariff-form-box{
      padding:9px;
    }
    body.tm-dark-mode .tm-service-flow-step,
    body.tm-dark-mode .tm-service-selected,
    body.dark-mode .tm-service-flow-step,
    body.dark-mode .tm-service-selected{
      background:rgba(15,23,42,.64);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-service-flow-step strong,
    body.tm-dark-mode .tm-service-selected strong,
    body.dark-mode .tm-service-flow-step strong,
    body.dark-mode .tm-service-selected strong{
      color:#f8fbff;
    }
    body.tm-dark-mode .tm-service-type-card.is-selected,
    body.dark-mode .tm-service-type-card.is-selected{
      background:rgba(20,71,105,.74);
      border-color:#63b3ed;
    }
    @media (max-width: 991px){
      .tm-service-prices-wrapper .tm-service-prices-hero{flex-direction:column;}
      .tm-service-prices-wrapper .tm-hero-metrics{min-width:0;}
      .tm-service-flow{grid-template-columns:1fr;}
      .tm-tariff-form-grid{grid-template-columns:repeat(2, 1fr);}
    }
    @media (max-width: 767px){
      .tm-service-prices-wrapper .tm-hero-copy{align-items:flex-start;}
      .tm-service-prices-wrapper .tm-hero-metrics{grid-template-columns:1fr;}
      .tm-service-selected{min-width:0;align-items:flex-start;flex-direction:column;}
      .tm-tariff-search{width:100%;flex:auto;}
      .tm-tariff-form-grid{grid-template-columns:1fr;}
      .tm-tariff-modal .modal-dialog{width:calc(100vw - 18px);margin:9px auto;}
    }
  </style>

  <section class="content-header">
    <h1>Precios de servicios</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Precios de servicios</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-service-prices-hero">
      <div class="tm-hero-copy">
        <div class="tm-hero-icon"><i class="fa fa-sliders"></i></div>
        <div>
        <h2>Tarifario inteligente de servicios</h2>
        <p>Configura precios por servicio y tipo de instalacion. La venta de servicios toma estas tarifas activas para calcular totales sin mezclar costos que no corresponden.</p>
      </div>
      </div>
      <div class="tm-hero-metrics">
        <div class="tm-hero-count">
          <strong><?php echo count($preciosVista); ?></strong>
          <span>Tarifas registradas</span>
        </div>
        <div class="tm-hero-count">
          <strong><?php echo (int)$tarifasActivas; ?></strong>
          <span>Tarifas activas</span>
        </div>
      </div>
    </div>

    <div class="tm-service-flow">
      <div class="tm-service-flow-step is-active">
        <span>1</span>
        <div><strong>Elige servicio</strong><small>Filtra el tarifario</small></div>
      </div>
      <div class="tm-service-flow-step">
        <span>2</span>
        <div><strong>Revisa tarifas</strong><small>Activas e inactivas</small></div>
      </div>
      <div class="tm-service-flow-step">
        <span>3</span>
        <div><strong>Crea o edita</strong><small>Guarda precios base</small></div>
      </div>
    </div>

    <div class="tm-service-catalog">
      <?php foreach($serviciosTarjetas as $servicio): ?>
        <?php
          $resumen = $resumenServicios[$servicio["nombre"]] ?? array("total" => 0, "activos" => 0);
        ?>
        <article class="tm-service-type-card tm-service-select-card" tabindex="0"
          data-servicio="<?php echo tmTarifaEsc($servicio["nombre"]); ?>"
          data-guia="<?php echo tmTarifaEsc($servicio["guia"]); ?>"
          data-campos="<?php echo tmTarifaEsc($servicio["campos"]); ?>"
          data-tone="<?php echo tmTarifaEsc($servicio["tono"]); ?>">
          <div class="tm-service-type-icon"><i class="fa <?php echo tmTarifaEsc($servicio["icono"]); ?>"></i></div>
          <h3><?php echo tmTarifaEsc($servicio["nombre"]); ?></h3>
          <p><?php echo tmTarifaEsc($servicio["texto"]); ?></p>
          <div class="tm-service-type-foot">
            <button type="button" class="btn btn-primary btn-xs btnAbrirTarifaServicio" data-servicio="<?php echo tmTarifaEsc($servicio["nombre"]); ?>">
              <i class="fa fa-plus"></i> Tarifa
            </button>
            <div class="tm-service-type-counts">
              <span class="tm-service-pill" title="Tarifas activas"><?php echo (int)$resumen["activos"]; ?> activas</span>
              <span class="tm-service-pill" title="Tarifas registradas"><?php echo (int)$resumen["total"]; ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="tm-tariff-panel">
      <div class="tm-tariff-toolbar">
        <div class="tm-tariff-toolbar-main">
          <h3><i class="fa fa-list-alt"></i> Tarifas registradas</h3>
          <p id="textoFiltroTarifas">Mostrando todos los servicios configurados.</p>
        </div>
        <div class="tm-service-selected">
          <div>
            <span>Servicio seleccionado</span>
            <strong id="servicioSeleccionadoTexto">Todos los servicios</strong>
          </div>
          <div class="tm-service-selected-actions">
            <button type="button" class="btn btn-default btn-xs" id="btnVerTodasTarifas">
              <i class="fa fa-th-large"></i> Ver todas
            </button>
            <button type="button" class="btn btn-primary btn-xs" id="btnCrearTarifaSeleccionada" disabled>
              <i class="fa fa-plus"></i> Crear tarifa
            </button>
          </div>
        </div>
        <div class="tm-tariff-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarTarifaServicio" placeholder="Buscar servicio, tipo o costo">
        </div>
      </div>
      <div class="tm-tariff-body">
        <div class="tm-tariff-grid" id="gridTarifasServicios">
          <?php if(empty($preciosVista)): ?>
            <div class="tm-tariff-empty">
              <i class="fa fa-tags"></i>
              <strong>No hay tarifas registradas.</strong>
              <span>Selecciona un servicio de arriba para crear la primera tarifa.</span>
            </div>
          <?php endif; ?>

          <?php foreach($preciosVista as $precio): ?>
            <?php
              $totalRef = tmTarifaTotalReferencial($precio);
              $estadoActivo = (int)$precio["estado"] === 1;
              $textoBusqueda = strtolower($precio["tipo_servicio"]." ".$precio["tipo_instalacion"]." ".$totalRef." ".$precio["estado"]);
            ?>
            <article class="tm-tariff-card <?php echo $estadoActivo ? "is-active" : "is-off"; ?>" tabindex="0"
              data-search="<?php echo tmTarifaEsc($textoBusqueda); ?>"
              data-key="<?php echo tmTarifaEsc($precio["tipo_servicio"]."||".$precio["tipo_instalacion"]); ?>"
              data-servicio="<?php echo tmTarifaEsc($precio["tipo_servicio"]); ?>"
              data-instalacion="<?php echo tmTarifaEsc($precio["tipo_instalacion"]); ?>">
              <div class="tm-tariff-head">
                <div>
                  <h4><?php echo tmTarifaEsc($precio["tipo_servicio"]); ?></h4>
                  <small><?php echo tmTarifaEsc($precio["tipo_instalacion"]); ?></small>
                </div>
                <?php echo tmTarifaEstado($precio["estado"]); ?>
              </div>
              <div class="tm-tariff-total">
                <strong><?php echo tmTarifaMoney($totalRef); ?></strong>
                <span>Suma referencial<br>de costos base</span>
              </div>
              <div class="tm-tariff-values">
                <?php echo tmTarifaPrincipales($precio); ?>
              </div>
              <div class="tm-tariff-foot">
                <span><i class="fa fa-mouse-pointer"></i> Ver y editar</span>
                <i class="fa fa-chevron-right"></i>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalTarifaServicio" class="modal fade tm-tariff-modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="formTarifaServicio">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
          <h4 class="modal-title">
            <span class="tm-tariff-modal-icon"><i class="fa fa-tags"></i></span>
            <span>
              <span id="tituloTarifaServicio">Configurar precios</span>
              <small id="subtituloTarifaServicio">Tarifa de servicio</small>
            </span>
          </h4>
        </div>
        <div class="tm-tariff-modal-body">
          <input type="hidden" name="guardarPrecioServicio" value="1">
          <input type="hidden" name="tipoServicioPrecio" id="tipoServicioPrecio">

          <div class="tm-tariff-guide">
            <i class="fa fa-lightbulb-o"></i>
            <div>
              <strong id="tarifaModoTexto">Nueva tarifa</strong>
              <span id="ayudaTipoServicio">Selecciona un servicio para configurar costos.</span>
            </div>
          </div>

          <div class="tm-tariff-form-grid">
            <div class="tm-tariff-form-box campo-instalacion">
              <label>Tipo / alcance</label>
              <select class="form-control" name="tipoInstalacionPrecio" id="tipoInstalacionPrecio">
                <option>Interior</option>
                <option>Exterior</option>
                <option>Mixta</option>
                <option>Altura</option>
                <option>Canalizada</option>
                <option>Instalacion</option>
                <option>A medida</option>
                <option>Sensor de movimiento</option>
                <option>Luces inteligentes</option>
                <option>Chapas electricas</option>
                <option>Apertura de puertas</option>
                <option>Automatizacion integral</option>
              </select>
            </div>
            <div class="tm-tariff-form-box campo-mano">
              <label>Mano de obra base</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="manoObraBase" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-camara">
              <label>Precio por camara</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="precioPorCamara" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-metro">
              <label>Precio por metro cable</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="precioPorMetro" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-canalizacion">
              <label>Metro canalizacion</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="precioCanalizacionMetro" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-visita">
              <label>Visita tecnica</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="costoVisita" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-diagnostico">
              <label>Diagnostico</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="costoDiagnostico" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-altura">
              <label>Recargo por altura</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="recargoAltura" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-urgencia">
              <label>Recargo urgencia</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="recargoUrgencia" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box campo-transporte">
              <label>Transporte</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control tarifa-input" name="costoTransporte" min="0" step="0.01" value="0"></div>
            </div>
            <div class="tm-tariff-form-box">
              <label>Estado</label>
              <label class="tm-tariff-active">
                <input type="checkbox" name="precioActivo" id="precioActivo" checked>
                Tarifa activa
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar tarifa</button>
        </div>
        <?php ControladorServicios::ctrGuardarPrecioServicio(); ?>
      </form>
    </div>
  </div>
</div>

<script>
var tarifasServicios = <?php echo json_encode($preciosVista, JSON_UNESCAPED_UNICODE); ?> || [];
var serviciosConfig = <?php echo json_encode($serviciosTarjetas, JSON_UNESCAPED_UNICODE); ?> || [];

(function(){
  var servicioSeleccionado = "";
  var opcionesPorServicio = {
    "Instalacion de camaras": ["Interior", "Exterior", "Mixta", "Altura", "Canalizada"],
    "Mantenimiento de camaras": ["Interior"],
    "Reubicacion de camaras": ["Interior", "Exterior", "Mixta", "Altura", "Canalizada"],
    "Diagnostico tecnico": ["Interior"],
    "Domotica": ["Sensor de movimiento", "Luces inteligentes", "Chapas electricas", "Apertura de puertas", "Automatizacion integral"],
    "Instalacion de alarmas": ["Instalacion"]
  };

  function configServicio(nombre){
    return serviciosConfig.find(function(item){ return item.nombre === nombre; }) || {
      nombre: nombre,
      guia: "Configure los costos que aplican a este servicio.",
      campos: "instalacion,mano,camara,metro,canalizacion,visita,diagnostico,altura,urgencia,transporte"
    };
  }

  function tarifaPorClave(servicio, instalacion){
    return tarifasServicios.find(function(item){
      return item.tipo_servicio === servicio && item.tipo_instalacion === instalacion;
    });
  }

  function limpiarTarifa(){
    $(".tarifa-input").val("0");
    $("#precioActivo").prop("checked", true);
  }

  function cargarOpciones(servicio, seleccion){
    var opciones = opcionesPorServicio[servicio] || ["Interior", "Exterior", "Mixta", "A medida"];
    var html = opciones.map(function(opcion){
      return '<option value="'+opcion+'">'+opcion+'</option>';
    }).join("");
    $("#tipoInstalacionPrecio").html(html);
    $("#tipoInstalacionPrecio").val(seleccion && opciones.indexOf(seleccion) !== -1 ? seleccion : opciones[0]);
  }

  function rellenarDesdeTarifa(tarifa){
    limpiarTarifa();
    if(!tarifa){
      return;
    }
    $('[name="manoObraBase"]').val(tarifa.mano_obra_base || 0);
    $('[name="precioPorCamara"]').val(tarifa.precio_por_camara || 0);
    $('[name="precioPorMetro"]').val(tarifa.precio_por_metro || 0);
    $('[name="precioCanalizacionMetro"]').val(tarifa.precio_canalizacion_metro || 0);
    $('[name="costoVisita"]').val(tarifa.costo_visita || 0);
    $('[name="costoDiagnostico"]').val(tarifa.costo_diagnostico || 0);
    $('[name="recargoAltura"]').val(tarifa.recargo_altura || 0);
    $('[name="recargoUrgencia"]').val(tarifa.recargo_urgencia || 0);
    $('[name="costoTransporte"]').val(tarifa.costo_transporte || 0);
    $("#precioActivo").prop("checked", Number(tarifa.estado) === 1);
  }

  function aplicarCampos(campos){
    $(".campo-instalacion, .campo-mano, .campo-camara, .campo-metro, .campo-canalizacion, .campo-visita, .campo-diagnostico, .campo-altura, .campo-urgencia, .campo-transporte").hide();
    (campos || "").split(",").forEach(function(campo){
      $(".campo-" + campo).show();
    });
  }

  function filtrarTarifas(){
    var query = ($("#buscarTarifaServicio").val() || "").toLowerCase().trim();
    var visibles = 0;
    $(".tm-tariff-card").each(function(){
      var card = $(this);
      var coincideServicio = !servicioSeleccionado || String(card.data("servicio") || "") === servicioSeleccionado;
      var coincideBusqueda = !query || String(card.data("search") || "").indexOf(query) !== -1;
      card.toggleClass("oculto-servicio", !coincideServicio);
      card.toggleClass("oculto-busqueda", !coincideBusqueda);
      if(coincideServicio && coincideBusqueda){
        visibles++;
      }
    });

    $("#sinTarifasResultado").remove();
    if(visibles === 0 && $(".tm-tariff-card").length > 0){
      $("#gridTarifasServicios").append('<div id="sinTarifasResultado" class="tm-tariff-empty"><i class="fa fa-search"></i><strong>No hay tarifas visibles.</strong><span>Crea una tarifa para este servicio o cambia el filtro.</span></div>');
    }
  }

  function seleccionarServicio(servicio){
    servicioSeleccionado = servicio || "";
    $(".tm-service-select-card").removeClass("is-selected");
    if(servicioSeleccionado){
      $(".tm-service-select-card").filter(function(){
        return String($(this).data("servicio") || "") === servicioSeleccionado;
      }).addClass("is-selected");
      $("#servicioSeleccionadoTexto").text(servicioSeleccionado);
      $("#textoFiltroTarifas").text("Mostrando tarifas de " + servicioSeleccionado + ".");
      $("#btnCrearTarifaSeleccionada").prop("disabled", false);
    }else{
      $("#servicioSeleccionadoTexto").text("Todos los servicios");
      $("#textoFiltroTarifas").text("Mostrando todos los servicios configurados.");
      $("#btnCrearTarifaSeleccionada").prop("disabled", true);
    }
    filtrarTarifas();
  }

  function abrirModalTarifa(servicio, instalacion){
    var cfg = configServicio(servicio);
    $("#tipoServicioPrecio").val(servicio);
    $("#tituloTarifaServicio").text(servicio);
    $("#subtituloTarifaServicio").text(instalacion ? "Editando tarifa: " + instalacion : "Nueva tarifa");
    $("#tarifaModoTexto").text(instalacion ? "Editar tarifa registrada" : "Crear o actualizar tarifa");
    $("#ayudaTipoServicio").text(cfg.guia || "Configure los costos que aplican a este servicio.");
    cargarOpciones(servicio, instalacion);
    aplicarCampos(cfg.campos);
    rellenarDesdeTarifa(tarifaPorClave(servicio, $("#tipoInstalacionPrecio").val()));
    $("#modalTarifaServicio").modal("show");
  }

  $(document).on("click keypress", ".btnAbrirTarifaServicio", function(event){
    if(event.type === "keypress" && event.which !== 13 && event.which !== 32){
      return;
    }
    event.stopPropagation();
    var servicio = $(this).data("servicio") || servicioSeleccionado;
    if(!servicio){
      return;
    }
    seleccionarServicio(servicio);
    abrirModalTarifa(servicio, null);
  });

  $(document).on("click keypress", ".tm-service-select-card", function(event){
    if($(event.target).closest(".btnAbrirTarifaServicio").length){
      return;
    }
    if(event.type === "keypress" && event.which !== 13 && event.which !== 32){
      return;
    }
    seleccionarServicio($(this).data("servicio"));
  });

  $(document).on("click keypress", ".tm-tariff-card", function(event){
    if(event.type === "keypress" && event.which !== 13 && event.which !== 32){
      return;
    }
    abrirModalTarifa($(this).data("servicio"), $(this).data("instalacion"));
  });

  $("#tipoInstalacionPrecio").on("change", function(){
    var servicio = $("#tipoServicioPrecio").val();
    rellenarDesdeTarifa(tarifaPorClave(servicio, $(this).val()));
    $("#subtituloTarifaServicio").text(tarifaPorClave(servicio, $(this).val()) ? "Editando tarifa: " + $(this).val() : "Nueva tarifa: " + $(this).val());
  });

  $("#buscarTarifaServicio").on("input", filtrarTarifas);

  $("#btnVerTodasTarifas").on("click", function(){
    seleccionarServicio("");
  });

  $("#btnCrearTarifaSeleccionada").on("click", function(){
    if(servicioSeleccionado){
      abrirModalTarifa(servicioSeleccionado, null);
    }
  });

  filtrarTarifas();
})();
</script>
