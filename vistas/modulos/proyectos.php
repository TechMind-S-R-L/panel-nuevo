<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "desarrollador" && $_SESSION["rol"] != "vendedor"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

ControladorProyectos::ctrEliminarDocumentoProyecto();

$proyectos = ControladorProyectos::ctrMostrarProyectosUsuario();
$proyectos = is_array($proyectos) ? $proyectos : array();
$tmProyectoPuedeVerMontos = ($_SESSION["perfil"] ?? "") == "Administrador" || ($_SESSION["rol"] ?? "") == "vendedor";

function tmProyectoTexto($valor, $fallback = "-"){
  $valor = trim((string)$valor);
  return htmlspecialchars($valor !== "" ? $valor : $fallback, ENT_QUOTES, "UTF-8");
}

function tmProyectoFecha($valor){
  if(empty($valor) || $valor == "0000-00-00" || $valor == "0000-00-00 00:00:00"){
    return "-";
  }
  $timestamp = strtotime($valor);
  return $timestamp ? date("d/m/Y", $timestamp) : $valor;
}

function tmProyectoEstado($estado){
  global $tmProyectoPuedeVerMontos;
  $mapa = array(
    "pendiente_adelanto" => array("Pendiente adelanto", "warning"),
    "en_desarrollo" => array("En desarrollo", "primary"),
    "revision_interna" => array("Revision interna", "info"),
    "revision_cliente" => array("Revision cliente", "info"),
    "pendiente_pago_final" => array("Pendiente pago final", "warning"),
    "pagado_final" => array("Pagado final", "success"),
    "completado" => array("Completado", "success"),
    "cancelado" => array("Cancelado", "danger")
  );
  if(!$tmProyectoPuedeVerMontos){
    $mapa["pendiente_adelanto"] = array("Pendiente de inicio", "warning");
    $mapa["pendiente_pago_final"] = array("Revision final", "warning");
    $mapa["pagado_final"] = array("Listo para entrega", "success");
  }
  return $mapa[$estado] ?? array(ucwords(str_replace("_", " ", (string)$estado)), "default");
}

function tmProyectoGrupo($estado){
  if($estado == "pendiente_adelanto"){
    return "pendientes";
  }
  if($estado == "completado"){
    return "completados";
  }
  if($estado == "cancelado"){
    return "cancelados";
  }
  return "proceso";
}

function tmProyectoEsEvidencia($documento){
  $tipo = strtolower((string)($documento["tipo_documento"] ?? ""));
  $archivo = strtolower((string)($documento["archivo"] ?? ""));
  return strpos($tipo, "evidencia") !== false
    || strpos($tipo, "captura") !== false
    || strpos($tipo, "video") !== false
    || preg_match('/\.(png|jpg|jpeg|webp|gif|mp4|mov|avi|webm)$/', $archivo);
}

function tmProyectoAcciones($proyecto){
  $idProyecto = (int)$proyecto["id"];
  $idServicio = (int)$proyecto["id_servicio"];
  $estado = $proyecto["estado"] ?? "";
  $acciones = '<button class="btn btn-default btnImprimirContratoSoftware" title="Imprimir contrato" idServicio="'.$idServicio.'"><i class="fa fa-file-text-o"></i> Contrato</button>';

  if(($_SESSION["perfil"] == "Administrador" || $_SESSION["rol"] == "desarrollador") && !in_array($estado, array("pendiente_adelanto", "pagado_final", "completado", "cancelado"))){
    $acciones .= ' <button class="btn btn-success btnAvanceProyecto" title="Registrar avance" idProyecto="'.$idProyecto.'" porcentaje="'.(int)($proyecto["porcentaje_avance"] ?? 0).'"><i class="fa fa-line-chart"></i> Avance</button>';
    $acciones .= ' <button class="btn btn-primary btnDocumentoProyecto" title="Subir propuesta, documento tecnico o entregable" idProyecto="'.$idProyecto.'" tipoDocumento="Documento tecnico PDF"><i class="fa fa-upload"></i> Documento</button>';
    $acciones .= ' <button class="btn btn-info btnDocumentoProyecto" title="Subir captura o video de avance" idProyecto="'.$idProyecto.'" tipoDocumento="Evidencia de avance"><i class="fa fa-camera"></i> Evidencia</button>';
  }

  if(($_SESSION["perfil"] == "Administrador" || $_SESSION["rol"] == "vendedor") && $estado == "pagado_final"){
    $acciones .= ' <a class="btn btn-warning btnEntregarProyectoSoftware" title="Entregar proyecto" href="index.php?ruta=proyectos&entregarProyectoSoftware='.$idProyecto.'"><i class="fa fa-check-square-o"></i> Entregar</a>';
  }

  if($estado == "completado"){
    $acciones .= ' <button class="btn btn-info btnImprimirActaSoftware" title="Imprimir acta de entrega" idProyecto="'.$idProyecto.'"><i class="fa fa-print"></i> Acta</button>';
  }

  return $acciones;
}

$proyectosVista = array();
$contadores = array("pendientes" => 0, "proceso" => 0, "completados" => 0, "cancelados" => 0);
$totalCartera = 0;
$totalDocumentos = 0;
$totalEvidencias = 0;
$totalAvances = 0;
$avanceAcumulado = 0;
$proyectosEnRiesgo = 0;

foreach($proyectos as $proyecto){
  $grupo = tmProyectoGrupo($proyecto["estado"] ?? "");
  $contadores[$grupo]++;
  if($tmProyectoPuedeVerMontos){
    $totalCartera += (float)($proyecto["precio_total"] ?? 0);
  }
  $documentos = ModeloProyectos::mdlMostrarDocumentos((int)$proyecto["id"]);
  $avances = ModeloProyectos::mdlMostrarAvances((int)$proyecto["id"]);
  $documentos = is_array($documentos) ? $documentos : array();
  $avances = is_array($avances) ? $avances : array();
  $evidencias = array_filter($documentos, "tmProyectoEsEvidencia");
  $totalDocumentos += count($documentos);
  $totalEvidencias += count($evidencias);
  $totalAvances += count($avances);
  $avanceAcumulado += max(0, min(100, (int)($proyecto["porcentaje_avance"] ?? 0)));
  $fechaEntrega = $proyecto["fecha_entrega_estimada"] ?? "";
  if(!empty($fechaEntrega) && $fechaEntrega != "0000-00-00" && !in_array(($proyecto["estado"] ?? ""), array("completado", "cancelado"))){
    $diasEntrega = floor((strtotime($fechaEntrega) - strtotime(date("Y-m-d"))) / 86400);
    if($diasEntrega <= 7){
      $proyectosEnRiesgo++;
    }
  }
  $proyectosVista[] = array(
    "proyecto" => $proyecto,
    "documentos" => $documentos,
    "avances" => $avances,
    "evidencias" => $evidencias,
    "grupo" => $grupo
  );
}
$avancePromedio = count($proyectosVista) > 0 ? round($avanceAcumulado / count($proyectosVista)) : 0;
?>

<div class="content-wrapper proyectos-page">
  <style>
    .proyectos-page{background:#eef3f7 !important;}
    .proyectos-hero{
      background:#163140;
      color:#fff;
      padding:18px 20px;
      border-radius:8px;
      margin-bottom:16px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      flex-wrap:wrap;
      box-shadow:0 14px 34px rgba(22,49,64,.12);
    }
    .proyectos-hero h2{margin:0 0 6px;font-size:24px;font-weight:900;}
    .proyectos-hero p{margin:0;color:#c8d7df;}
    .proyecto-hero-stats{display:flex;gap:10px;flex-wrap:wrap;}
    .proyecto-hero-stat{
      background:rgba(255,255,255,.1);
      border:1px solid rgba(255,255,255,.18);
      border-radius:8px;
      padding:10px 14px;
      min-width:135px;
      text-align:center;
    }
    .proyecto-hero-stat span{display:block;font-size:11px;font-weight:800;text-transform:uppercase;color:#dceaf1;}
    .proyecto-hero-stat strong{display:block;font-size:24px;line-height:1;color:#fff;}
    .proyectos-flow{
      background:#00c0ef;
      color:#fff;
      border-radius:8px;
      padding:13px 16px;
      margin-bottom:16px;
      font-weight:800;
      box-shadow:0 10px 24px rgba(0,192,239,.2);
    }
    .proyectos-flow span{display:block;font-size:16px;margin-bottom:4px;}
    .proyectos-panel{
      background:rgba(255,255,255,.92);
      border:1px solid #dbe5ec;
      border-radius:10px;
      box-shadow:0 12px 30px rgba(22,49,64,.08);
      overflow:hidden;
    }
    .proyectos-toolbar{
      padding:14px 16px;
      border-bottom:1px solid #e5edf2;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
    }
    .proyectos-toolbar h3{margin:0;font-size:18px;font-weight:900;color:#1f2d3d;}
    .proyecto-search{
      position:relative;
      flex:1 1 320px;
      max-width:520px;
    }
    .proyecto-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#6f8190;
    }
    .proyecto-search input{
      height:42px;
      padding-left:38px;
      border:1px solid #d7e1e8;
      border-radius:7px;
      box-shadow:none;
    }
    .proyecto-tabs{
      padding:0 16px;
      border-bottom:1px solid #e5edf2;
      background:#fbfdff;
    }
    .proyecto-tabs.nav-tabs>li>a{
      border:0;
      border-radius:0;
      color:#60717f;
      font-weight:800;
      padding:14px 10px;
    }
    .proyecto-tabs.nav-tabs>li.active>a,
    .proyecto-tabs.nav-tabs>li.active>a:focus,
    .proyecto-tabs.nav-tabs>li.active>a:hover{
      border:0;
      border-bottom:3px solid #3c8dbc;
      color:#163140;
      background:transparent;
    }
    .proyecto-tab-count{
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
    .proyecto-card-grid{
      padding:12px;
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(255px, 1fr));
      gap:10px;
    }
    .proyecto-card{
      position:relative;
      border:1px solid #dbe5ec;
      border-radius:10px;
      background:#fff;
      padding:11px;
      min-height:230px;
      display:flex;
      flex-direction:column;
      gap:9px;
      cursor:pointer;
      box-shadow:0 10px 26px rgba(22,49,64,.07);
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      overflow:hidden;
    }
    .proyecto-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#3c8dbc;
    }
    .proyecto-card.estado-pendientes:before{background:#f39c12;}
    .proyecto-card.estado-proceso:before{background:#3c8dbc;}
    .proyecto-card.estado-completados:before{background:#00a65a;}
    .proyecto-card.estado-cancelados:before{background:#dd4b39;}
    .proyecto-card:hover{
      border-color:#3c8dbc;
      box-shadow:0 16px 34px rgba(22,49,64,.14);
      transform:translateY(-2px);
    }
    .proyecto-card-head{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:9px;
      padding-left:4px;
    }
    .proyecto-card-code{
      display:inline-flex;
      align-items:center;
      gap:6px;
      color:#176b9b;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .proyecto-card h4{
      margin:4px 0 0;
      color:#1f2d3d;
      font-size:14px;
      font-weight:900;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .proyecto-card-type{
      color:#60717f;
      font-size:11px;
      font-weight:800;
      margin-top:2px;
    }
    .proyecto-card-total{
      min-width:78px;
      text-align:right;
      color:#163140;
      font-size:15px;
      font-weight:900;
      line-height:1.1;
    }
    .proyecto-card-total span{
      display:block;
      color:#7b8b96;
      font-size:10px;
      text-transform:uppercase;
      font-weight:800;
    }
    .proyecto-progress{
      display:grid;
      grid-template-columns:minmax(0,1fr) auto;
      gap:8px;
      align-items:center;
    }
    .proyecto-progress .progress{
      margin:0;
      height:8px;
      border-radius:999px;
      background:#e8eef3;
      box-shadow:none;
    }
    .proyecto-progress strong{color:#163140;font-weight:900;}
    .proyecto-card-body{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:6px;
    }
    .proyecto-card-item{
      border:1px solid #edf2f6;
      background:#f8fbfd;
      border-radius:8px;
      padding:7px;
      min-height:50px;
    }
    .proyecto-card-item.full{grid-column:1 / -1;}
    .proyecto-card-item span{
      display:block;
      color:#7b8b96;
      font-size:10px;
      text-transform:uppercase;
      font-weight:900;
      margin-bottom:4px;
    }
    .proyecto-card-item strong,
    .proyecto-card-item p{
      margin:0;
      color:#263845;
      font-size:11px;
      font-weight:800;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .proyecto-card-footer{
      margin-top:auto;
      padding-top:8px;
      border-top:1px solid #edf2f6;
      display:grid;
      grid-template-columns:1fr;
      gap:7px;
      align-items:start;
    }
    .proyecto-card-footer .label{
      justify-self:start;
      max-width:100%;
      white-space:normal;
      text-align:left;
      line-height:1.25;
    }
    .proyecto-actions{
      width:100%;
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(34px, 1fr));
      gap:5px;
    }
    .proyecto-actions .btn{
      border-radius:7px;
      font-size:11px;
      font-weight:800;
      padding:6px 7px;
      width:100%;
      min-width:0;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }
    .proyecto-empty{
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
    .proyecto-modal .modal-dialog{max-width:920px;width:92%;}
    .proyecto-modal .modal-content{
      border:0;
      border-radius:16px;
      overflow:hidden;
      background:#f4f8fb;
      box-shadow:0 28px 80px rgba(10,30,45,.34);
    }
    .proyecto-modal .modal-header{
      position:relative;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 62%,#36aee2 100%);
      color:#fff;
      border:0;
      padding:17px 22px;
      overflow:hidden;
    }
    .proyecto-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-38px;
      top:-56px;
      width:180px;
      height:180px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .proyecto-modal .modal-header .close{
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
    .proyecto-modal .modal-body{padding:14px;}
    .proyecto-modal-title{
      position:relative;
      z-index:2;
      display:flex;
      align-items:center;
      gap:12px;
      max-width:88%;
    }
    .proyecto-modal-icon{
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
    .proyecto-modal-kicker{
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
    .proyecto-modal-title h4{margin:0;font-size:19px;font-weight:900;line-height:1.25;}
    .proyecto-modal-title p{margin:3px 0 0;color:#eaf7ff;font-weight:700;}
    .proyecto-modal-progress-card{
      display:grid;
      grid-template-columns:1fr 96px;
      gap:10px;
      align-items:center;
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:12px;
      padding:11px;
      margin-bottom:10px;
      box-shadow:0 10px 24px rgba(22,49,64,.06);
    }
    .proyecto-modal-progress-card span,
    .proyecto-modal-block-title,
    .proyecto-summary-card span,
    .proyecto-detail-box span{
      display:block;
      color:#7b8b96;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .proyecto-modal-progress-card h5{
      margin:0 0 6px;
      color:#263845;
      font-size:13px;
      font-weight:900;
    }
    .proyecto-modal-progress-card .progress{
      height:9px;
      margin:0;
      border-radius:999px;
      background:#e8f0f6;
      box-shadow:none;
      overflow:hidden;
    }
    .proyecto-modal-progress-card .progress-bar{
      background:linear-gradient(90deg,#176b9b,#36aee2);
      box-shadow:none;
    }
    .proyecto-modal-progress-number{
      width:76px;
      height:76px;
      border-radius:18px;
      background:linear-gradient(135deg,#176b9b,#36aee2);
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:22px;
      font-weight:900;
      justify-self:end;
      box-shadow:0 14px 26px rgba(23,107,155,.26);
    }
    .proyecto-modal-summary{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:8px;
      margin-bottom:10px;
    }
    .proyecto-summary-card{
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:10px;
      min-height:66px;
      box-shadow:0 8px 18px rgba(22,49,64,.05);
    }
    .proyecto-summary-card strong{
      display:block;
      color:#176b9b;
      font-size:18px;
      font-weight:900;
      line-height:1.15;
      overflow-wrap:anywhere;
    }
    .proyecto-summary-card p{
      color:#263845;
      font-weight:800;
      margin:0;
      overflow-wrap:anywhere;
    }
    .proyecto-modal-pill{
      display:inline-flex;
      align-items:center;
      gap:7px;
      min-height:28px;
      padding:5px 9px;
      border-radius:999px;
      font-size:11px;
      font-weight:900;
      color:#fff !important;
      background:#607d8b;
    }
    .proyecto-detail-box p .proyecto-modal-pill,
    .proyecto-modal-pill i{
      color:#fff !important;
    }
    .proyecto-modal-pill.warning{background:#f39c12;color:#fff !important;}
    .proyecto-modal-pill.success{background:#00a65a;color:#fff !important;}
    .proyecto-modal-pill.info{background:#00acd6;color:#fff !important;}
    .proyecto-modal-pill.danger{background:#dd4b39;color:#fff !important;}
    .proyecto-modal-section{
      background:#fff;
      border:1px solid #dce8f1;
      border-radius:10px;
      padding:11px;
      margin-top:10px;
      box-shadow:0 8px 18px rgba(22,49,64,.04);
    }
    .proyecto-detail-grid{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:8px;
    }
    .proyecto-detail-box{
      border:1px solid #e2ebf2;
      border-radius:9px;
      background:#f8fbfd;
      padding:10px;
      min-height:62px;
    }
    .proyecto-detail-box.full{grid-column:1 / -1;}
    .proyecto-detail-box strong,
    .proyecto-detail-box p{
      color:#263845;
      font-size:13px;
      font-weight:800;
      margin:0;
      overflow-wrap:anywhere;
      white-space:pre-wrap;
    }
    .proyecto-modal-text-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
      margin-top:10px;
    }
    .proyecto-modal-text-card{
      border:1px solid #e2ebf2;
      border-radius:10px;
      background:#fbfdff;
      padding:10px;
      min-height:92px;
    }
    .proyecto-modal-text-card.full{grid-column:1 / -1;}
    .proyecto-modal-text-card span{
      display:block;
      color:#7b8b96;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .proyecto-modal-text-card p{
      margin:0;
      color:#263845;
      font-weight:800;
      line-height:1.45;
      white-space:pre-wrap;
      overflow-wrap:anywhere;
    }
    .proyecto-doc-list{
      margin:0;
      padding:0;
      list-style:none;
      display:grid;
      gap:6px;
    }
    .proyecto-doc-list li{
      border:1px solid #e2ebf2;
      border-radius:8px;
      padding:7px 8px;
      background:#fff;
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:center;
      flex-wrap:wrap;
    }
    .proyecto-workspace-grid{
      display:grid;
      grid-template-columns:1.15fr .85fr;
      gap:10px;
      margin-top:10px;
    }
    .proyecto-workspace-card{
      border:1px solid #dce8f1;
      border-radius:12px;
      background:#fff;
      padding:11px;
      box-shadow:0 8px 18px rgba(22,49,64,.045);
      min-width:0;
    }
    .proyecto-workspace-card.full{grid-column:1 / -1;}
    .proyecto-workspace-card.highlight{
      background:linear-gradient(135deg,#f9fcff 0%,#eef8ff 100%);
      border-color:#cfe4f2;
    }
    .proyecto-workspace-head{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      margin-bottom:9px;
    }
    .proyecto-workspace-head h5{
      margin:0;
      color:#163140;
      font-size:14px;
      font-weight:900;
      display:flex;
      align-items:center;
      gap:7px;
    }
    .proyecto-workspace-head span{
      color:#6b7d8c;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .proyecto-dev-checklist{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:7px;
    }
    .proyecto-dev-check{
      border:1px solid #e3edf4;
      border-radius:10px;
      background:#f8fbfd;
      padding:9px;
      color:#263845;
      font-size:12px;
      font-weight:850;
      min-height:58px;
      display:flex;
      gap:8px;
      align-items:flex-start;
    }
    .proyecto-dev-check i{
      width:22px;
      height:22px;
      border-radius:50%;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      background:#b8c7d3;
      flex:0 0 22px;
      margin-top:1px;
    }
    .proyecto-dev-check.done i{background:#00a65a;}
    .proyecto-dev-check.warn i{background:#f39c12;}
    .proyecto-evidence-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(135px,1fr));
      gap:8px;
    }
    .proyecto-evidence-item{
      border:1px solid #e2ebf2;
      border-radius:10px;
      background:#f8fbfd;
      overflow:hidden;
      min-height:126px;
      display:flex;
      flex-direction:column;
    }
    .proyecto-evidence-preview{
      height:82px;
      background:#eaf2f8;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#176b9b;
      font-size:25px;
      overflow:hidden;
    }
    .proyecto-evidence-preview img,
    .proyecto-evidence-preview video{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
    }
    .proyecto-evidence-body{
      padding:7px;
      min-width:0;
    }
    .proyecto-evidence-body strong{
      display:block;
      color:#263845;
      font-size:11px;
      line-height:1.25;
      font-weight:900;
      overflow-wrap:anywhere;
    }
    .proyecto-evidence-body a{
      font-size:11px;
      font-weight:900;
    }
    .proyecto-timeline{
      margin:0;
      padding:0;
      list-style:none;
      display:grid;
      gap:8px;
      max-height:260px;
      overflow-y:auto;
      padding-right:4px;
    }
    .proyecto-timeline li{
      border:1px solid #e2ebf2;
      border-radius:10px;
      background:#fbfdff;
      padding:9px;
      display:grid;
      grid-template-columns:52px minmax(0,1fr);
      gap:9px;
      align-items:start;
    }
    .proyecto-timeline-percent{
      width:48px;
      height:48px;
      border-radius:14px;
      background:linear-gradient(135deg,#176b9b,#36aee2);
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:14px;
      font-weight:900;
    }
    .proyecto-timeline-body strong{
      display:block;
      color:#163140;
      font-size:12px;
      font-weight:900;
      margin-bottom:3px;
    }
    .proyecto-timeline-body p{
      margin:0;
      color:#425466;
      font-size:12px;
      line-height:1.35;
      white-space:pre-wrap;
      overflow-wrap:anywhere;
    }
    .proyecto-doc-group{
      display:grid;
      gap:8px;
    }
    .proyecto-doc-group-title{
      margin:3px 0 0;
      color:#176b9b;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .proyecto-doc-card{
      border:1px solid #e2ebf2;
      border-radius:9px;
      background:#fbfdff;
      padding:8px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:8px;
      margin-top:6px;
    }
    .proyecto-doc-card strong{
      display:block;
      color:#263845;
      font-size:12px;
      font-weight:900;
      overflow-wrap:anywhere;
    }
    .proyecto-doc-card span{
      color:#7b8b96;
      font-size:11px;
      font-weight:800;
    }
    .proyecto-doc-card .btn{
      flex:0 0 auto;
      border-radius:7px;
      font-weight:900;
      padding:5px 8px;
    }
    .proyecto-dev-note{
      border:1px solid #cde9f6;
      background:#eefaff;
      color:#176b9b;
      border-radius:10px;
      padding:10px;
      font-size:12px;
      font-weight:850;
      line-height:1.45;
      margin-top:8px;
    }
    .proyecto-modal-actions{
      margin-top:10px;
      padding:10px;
      border:1px solid #dce8f1;
      border-radius:10px;
      background:#fff;
      display:flex;
      justify-content:flex-end;
      gap:6px;
      flex-wrap:wrap;
    }
    .proyecto-modal-actions:before{
      content:"Acciones disponibles";
      margin-right:auto;
      color:#60717f;
      font-size:12px;
      font-weight:900;
      text-transform:uppercase;
      align-self:center;
    }
    .proyecto-modal-actions .btn{
      border-radius:8px;
      font-weight:900;
      padding:6px 10px;
    }
    .proyecto-form-modal .modal-header{
      background:#3c8dbc;
      color:white;
      border:0;
    }
    .proyecto-form-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;
    }
    .proyecto-form-grid .full{grid-column:1 / -1;}
    .proyecto-file-help{
      margin-top:6px;
      color:#6f8190;
      font-size:12px;
      font-weight:700;
    }
    @media(max-width:991px){
      .proyecto-modal-summary{grid-template-columns:1fr 1fr;}
      .proyecto-detail-grid{grid-template-columns:1fr 1fr;}
      .proyecto-workspace-grid{grid-template-columns:1fr;}
    }
    @media(max-width:767px){
      .proyecto-card-grid{grid-template-columns:1fr;padding:12px;}
      .proyecto-modal-progress-card{grid-template-columns:1fr;}
      .proyecto-modal-progress-number{justify-self:start;width:84px;height:84px;}
      .proyecto-modal-summary,
      .proyecto-modal-text-grid{grid-template-columns:1fr;}
      .proyecto-detail-grid{grid-template-columns:1fr;}
      .proyecto-dev-checklist{grid-template-columns:1fr;}
      .proyecto-form-grid{grid-template-columns:1fr;}
      .proyecto-tabs.nav-tabs>li{float:none;}
      .proyecto-hero-stat{width:100%;}
    }
  </style>

  <section class="content-header">
    <h1>Proyectos de Software</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Proyectos</li>
    </ol>
  </section>

  <section class="content">
    <div class="proyectos-hero">
      <div>
        <h2>Centro de desarrollo</h2>
        <p>Controle alcance, documentos tecnicos, evidencias, avances, revision, cobro final y entrega.</p>
      </div>
      <div class="proyecto-hero-stats">
        <div class="proyecto-hero-stat"><span>Total</span><strong><?php echo count($proyectosVista); ?></strong></div>
        <div class="proyecto-hero-stat"><span>Avance prom.</span><strong><?php echo $avancePromedio; ?>%</strong></div>
        <div class="proyecto-hero-stat"><span>Docs</span><strong><?php echo $totalDocumentos; ?></strong></div>
        <div class="proyecto-hero-stat"><span>Evidencias</span><strong><?php echo $totalEvidencias; ?></strong></div>
        <div class="proyecto-hero-stat"><span>Riesgo</span><strong><?php echo $proyectosEnRiesgo; ?></strong></div>
      </div>
    </div>

    <div class="proyectos-flow">
      <span>Flujo del desarrollador</span>
      Alcance aprobado -> propuesta/documento tecnico -> avances con evidencia -> revision interna -> revision cliente -> cobro final -> acta de entrega.
    </div>

    <div class="proyectos-panel">
      <div class="proyectos-toolbar">
        <h3><i class="fa fa-code"></i> Seguimiento de proyectos</h3>
        <div class="proyecto-search">
          <i class="fa fa-search"></i>
          <input type="text" class="form-control" id="buscarProyectoCards" placeholder="Buscar por codigo, proyecto, cliente o desarrollador">
        </div>
      </div>

      <ul class="nav nav-tabs proyecto-tabs">
        <li class="active"><a href="#tabProyectosPendientes" data-toggle="tab">Pendientes <span class="proyecto-tab-count"><?php echo $contadores["pendientes"]; ?></span></a></li>
        <li><a href="#tabProyectosProceso" data-toggle="tab">En proceso <span class="proyecto-tab-count"><?php echo $contadores["proceso"]; ?></span></a></li>
        <li><a href="#tabProyectosCompletados" data-toggle="tab">Completados <span class="proyecto-tab-count"><?php echo $contadores["completados"]; ?></span></a></li>
        <?php if($contadores["cancelados"] > 0): ?>
          <li><a href="#tabProyectosCancelados" data-toggle="tab">Cancelados <span class="proyecto-tab-count"><?php echo $contadores["cancelados"]; ?></span></a></li>
        <?php endif; ?>
      </ul>

      <div class="tab-content">
        <?php
          $tabsProyecto = array(
            "pendientes" => array("id" => "tabProyectosPendientes", "empty" => "No hay proyectos pendientes de adelanto."),
            "proceso" => array("id" => "tabProyectosProceso", "empty" => "No hay proyectos en proceso."),
            "completados" => array("id" => "tabProyectosCompletados", "empty" => "No hay proyectos completados."),
            "cancelados" => array("id" => "tabProyectosCancelados", "empty" => "No hay proyectos cancelados.")
          );
        ?>
        <?php foreach($tabsProyecto as $grupoProyecto => $tabProyecto): ?>
          <?php if($grupoProyecto == "cancelados" && $contadores["cancelados"] == 0) continue; ?>
          <div class="tab-pane <?php echo $grupoProyecto == "pendientes" ? "active" : ""; ?>" id="<?php echo $tabProyecto["id"]; ?>">
            <div class="proyecto-card-grid">
              <?php if($contadores[$grupoProyecto] == 0): ?>
                <div class="proyecto-empty"><?php echo $tabProyecto["empty"]; ?></div>
              <?php endif; ?>

              <?php foreach($proyectosVista as $itemProyecto): ?>
                <?php
                  if($itemProyecto["grupo"] != $grupoProyecto) continue;

                  $proyecto = $itemProyecto["proyecto"];
                  $documentos = $itemProyecto["documentos"];
                  $avancesProyecto = $itemProyecto["avances"];
                  $estadoInfo = tmProyectoEstado($proyecto["estado"] ?? "");
                  $avance = max(0, min(100, (int)($proyecto["porcentaje_avance"] ?? 0)));
                  $cliente = $proyecto["cliente"] ?? "-";
                  $desarrollador = $proyecto["desarrollador"] ?? "Sin asignar";
                  $searchProyecto = strtolower(trim(($proyecto["codigo"] ?? "")." ".($proyecto["nombre_proyecto"] ?? "")." ".($proyecto["tipo_software"] ?? "")." ".$cliente." ".$desarrollador." ".($proyecto["estado"] ?? "")));
                  $proyectoDatosModal = $proyecto;
                  if(!$tmProyectoPuedeVerMontos){
                    foreach(array("precio_total", "porcentaje_adelanto", "monto_adelanto", "saldo_pendiente", "pago_adelanto", "pago_final") as $campoMontoProyecto){
                      unset($proyectoDatosModal[$campoMontoProyecto]);
                    }
                  }
                  $proyectoJson = htmlspecialchars(json_encode($proyectoDatosModal, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8");
                  $docsJson = htmlspecialchars(json_encode($documentos, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8");
                  $avancesJson = htmlspecialchars(json_encode($avancesProyecto, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8");
                  $evidenciasProyecto = array_filter($documentos, function($doc){
                    $tipoDoc = strtolower((string)($doc["tipo_documento"] ?? ""));
                    $archivoDoc = strtolower((string)($doc["archivo"] ?? ""));
                    return strpos($tipoDoc, "evidencia") !== false || strpos($tipoDoc, "captura") !== false || strpos($tipoDoc, "video") !== false || preg_match('/\.(png|jpg|jpeg|webp|gif|mp4|mov|avi|webm)$/', $archivoDoc);
                  });
                ?>
                <article class="proyecto-card proyectoCardDetalle estado-<?php echo $grupoProyecto; ?>" data-search="<?php echo htmlspecialchars($searchProyecto, ENT_QUOTES, "UTF-8"); ?>" data-proyecto="<?php echo $proyectoJson; ?>" data-documentos="<?php echo $docsJson; ?>" data-avances="<?php echo $avancesJson; ?>">
                  <div class="proyecto-card-head">
                    <div>
                      <span class="proyecto-card-code"><i class="fa fa-hashtag"></i> <?php echo tmProyectoTexto($proyecto["codigo"] ?? ""); ?></span>
                      <h4><?php echo tmProyectoTexto($proyecto["nombre_proyecto"] ?? ""); ?></h4>
                      <div class="proyecto-card-type"><?php echo tmProyectoTexto($proyecto["tipo_software"] ?? ""); ?></div>
                    </div>
                    <?php if($tmProyectoPuedeVerMontos): ?>
                      <div class="proyecto-card-total">
                        <span>Total</span>
                        Bs <?php echo number_format((float)($proyecto["precio_total"] ?? 0), 2); ?>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="proyecto-progress">
                    <div class="progress"><div class="progress-bar progress-bar-primary" style="width:<?php echo $avance; ?>%"></div></div>
                    <strong><?php echo $avance; ?>%</strong>
                  </div>

                  <div class="proyecto-card-body">
                    <div class="proyecto-card-item">
                      <span>Cliente</span>
                      <p><?php echo tmProyectoTexto($cliente); ?></p>
                    </div>
                    <div class="proyecto-card-item">
                      <span>Desarrollador</span>
                      <p><?php echo tmProyectoTexto($desarrollador); ?></p>
                    </div>
                    <?php if($tmProyectoPuedeVerMontos): ?>
                      <div class="proyecto-card-item">
                        <span>Adelanto</span>
                        <strong>Bs <?php echo number_format((float)($proyecto["monto_adelanto"] ?? 0), 2); ?></strong>
                      </div>
                    <?php endif; ?>
                    <div class="proyecto-card-item">
                      <span>Entrega</span>
                      <p><?php echo tmProyectoTexto(tmProyectoFecha($proyecto["fecha_entrega_estimada"] ?? "")); ?></p>
                    </div>
                    <div class="proyecto-card-item">
                      <span>Documentos</span>
                      <strong><?php echo count($documentos); ?> archivo(s)</strong>
                    </div>
                    <div class="proyecto-card-item">
                      <span>Evidencias</span>
                      <strong><?php echo count($evidenciasProyecto); ?> captura/video</strong>
                    </div>
                    <div class="proyecto-card-item full">
                      <span>Bitacora</span>
                      <p><?php echo count($avancesProyecto); ?> avance(s) registrados</p>
                    </div>
                  </div>

                  <div class="proyecto-card-footer">
                    <span class="label label-<?php echo $estadoInfo[1]; ?>"><?php echo tmProyectoTexto($estadoInfo[0]); ?></span>
                    <div class="proyecto-actions">
                      <?php echo tmProyectoAcciones($proyecto); ?>
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

<div id="modalAvanceProyecto" class="modal fade proyecto-form-modal" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-line-chart"></i> Registrar avance del proyecto</h4></div>
      <div class="modal-body">
        <input type="hidden" name="guardarAvanceProyecto" value="1">
        <input type="hidden" id="idProyectoAvance" name="idProyectoAvance">
        <div class="proyecto-form-grid">
          <div class="form-group">
            <label>Porcentaje de avance</label>
            <input type="number" class="form-control" id="porcentajeAvanceProyecto" name="porcentajeAvanceProyecto" min="0" max="100" required>
          </div>
          <div class="form-group">
            <label>Estado</label>
            <select class="form-control" name="estadoAvanceProyecto">
              <option value="en_desarrollo">En desarrollo</option>
              <option value="revision_interna">Revision interna</option>
              <option value="revision_cliente">Revision con cliente</option>
              <option value="pendiente_pago_final"><?php echo $tmProyectoPuedeVerMontos ? "Listo, pedir pago final" : "Listo para revision final"; ?></option>
            </select>
          </div>
          <div class="form-group full">
            <label>Detalle tecnico realizado</label>
            <textarea class="form-control" name="descripcionAvanceProyecto" rows="3" placeholder="Modulos terminados, cambios realizados, pruebas ejecutadas o entregables preparados."></textarea>
          </div>
          <div class="form-group">
            <label>Bloqueos o riesgos</label>
            <textarea class="form-control" name="bloqueosAvanceProyecto" rows="3" placeholder="Falta de informacion, aprobacion pendiente, dependencia tecnica o riesgo de fecha."></textarea>
          </div>
          <div class="form-group">
            <label>Siguiente paso</label>
            <textarea class="form-control" name="proximoPasoAvanceProyecto" rows="3" placeholder="Que se hara despues de este avance."></textarea>
          </div>
          <div class="form-group full">
            <label>Enlace demo / repositorio / entorno de prueba</label>
            <input type="text" class="form-control" name="urlDemoAvanceProyecto" placeholder="https://...">
          </div>
          <div class="form-group full">
            <label><input type="checkbox" name="visibleClienteAvanceProyecto" value="1"> Publicar este avance en Mi cuenta del cliente</label>
            <div class="proyecto-file-help">Desmarque esta opcion si contiene notas internas, bloqueos tecnicos o enlaces privados.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Guardar avance</button></div>
      <?php ControladorProyectos::ctrGuardarAvanceProyecto(); ?>
    </form>
  </div></div>
</div>

<div id="modalDocumentoProyecto" class="modal fade proyecto-form-modal" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <form method="post" enctype="multipart/form-data">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title"><i class="fa fa-upload"></i> Subir recurso del proyecto</h4></div>
      <div class="modal-body">
        <input type="hidden" name="guardarDocumentoProyecto" value="1">
        <input type="hidden" id="idProyectoDocumento" name="idProyectoDocumento">
        <div class="proyecto-form-grid">
          <div class="form-group">
            <label>Tipo de recurso</label>
            <select class="form-control" id="tipoDocumentoProyecto" name="tipoDocumentoProyecto">
              <option>Propuesta comercial</option>
              <option>Documento tecnico PDF</option>
              <option>Requerimientos del cliente</option>
              <option>Arquitectura / base de datos</option>
              <option>Evidencia de avance</option>
              <option>Captura de avance</option>
              <option>Video de avance</option>
              <option>Manual de usuario</option>
              <option>Entregable</option>
              <option>Acta / conformidad</option>
              <option>Otro</option>
            </select>
          </div>
          <div class="form-group">
            <label>Titulo</label>
            <input type="text" class="form-control" name="tituloDocumentoProyecto" placeholder="Ej. Propuesta v1, Login terminado, Demo cliente" required>
          </div>
          <div class="form-group full">
            <label>Archivo</label>
            <input type="file" class="form-control" name="archivoProyecto" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp,.gif,.mp4,.mov,.avi,.webm,.zip,.rar">
            <div class="proyecto-file-help">Puede subir PDF, Word, Excel, imagenes, videos o archivos comprimidos del proyecto.</div>
          </div>
          <div class="form-group full">
            <label>Observacion</label>
            <textarea class="form-control" name="observacionDocumentoProyecto" rows="3" placeholder="Explique para que sirve este archivo o que avance demuestra."></textarea>
          </div>
          <div class="form-group full">
            <label><input type="checkbox" name="visibleClienteDocumentoProyecto" value="1"> Mostrar este recurso al cliente</label>
            <div class="proyecto-file-help">Active solo para entregables, evidencias o documentos que el cliente pueda descargar.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar recurso</button></div>
      <?php ControladorProyectos::ctrGuardarDocumentoProyecto(); ?>
    </form>
  </div></div>
</div>

<div id="modalVerProyectoSoftware" class="modal fade proyecto-modal" role="dialog">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">&times;</button>
      <div class="proyecto-modal-title">
        <div class="proyecto-modal-icon"><i class="fa fa-code"></i></div>
        <div>
          <span class="proyecto-modal-kicker" id="proyectoModalCodigo">Proyecto</span>
          <h4 id="proyectoModalTitulo">Detalle del proyecto</h4>
          <p id="proyectoModalSubtitulo">Tipo de software</p>
        </div>
      </div>
    </div>
    <div class="modal-body">
      <div class="proyecto-modal-progress-card">
        <div>
          <span>Avance del proyecto</span>
          <h5 id="proyectoModalEstadoTexto">Estado del proyecto</h5>
          <div class="progress"><div class="progress-bar" id="proyectoModalBarraAvance" style="width:0%"></div></div>
        </div>
        <div class="proyecto-modal-progress-number" id="proyectoModalAvance">0%</div>
      </div>

      <div class="proyecto-modal-summary" id="proyectoModalResumenMontos" style="<?php echo $tmProyectoPuedeVerMontos ? "" : "display:none"; ?>">
        <div class="proyecto-summary-card"><span>Total</span><strong id="proyectoModalTotal">-</strong></div>
        <div class="proyecto-summary-card"><span>Adelanto</span><strong id="proyectoModalAdelanto">-</strong></div>
        <div class="proyecto-summary-card"><span>Saldo pendiente</span><strong id="proyectoModalSaldo">-</strong></div>
      </div>

      <div class="proyecto-modal-section">
        <span class="proyecto-modal-block-title">Responsables y fechas</span>
        <div class="proyecto-detail-grid">
          <div class="proyecto-detail-box"><span>Cliente</span><p id="proyectoModalCliente">-</p></div>
          <div class="proyecto-detail-box"><span>Vendedor</span><p id="proyectoModalVendedor">-</p></div>
          <div class="proyecto-detail-box"><span>Desarrollador</span><p id="proyectoModalDesarrollador">-</p></div>
          <div class="proyecto-detail-box"><span>Entrega estimada</span><p id="proyectoModalEntrega">-</p></div>
          <div class="proyecto-detail-box"><span>Estado</span><p><span class="proyecto-modal-pill info" id="proyectoModalEstado">-</span></p></div>
        </div>
      </div>

      <div class="proyecto-modal-text-grid">
        <div class="proyecto-modal-text-card"><span>Alcance</span><p id="proyectoModalAlcance">-</p></div>
        <div class="proyecto-modal-text-card"><span>Entregables</span><p id="proyectoModalEntregables">-</p></div>
        <div class="proyecto-modal-text-card"><span>Exclusiones</span><p id="proyectoModalExclusiones">-</p></div>
        <div class="proyecto-modal-text-card"><span>Observaciones</span><p id="proyectoModalObservaciones">-</p></div>
      </div>

      <div class="proyecto-workspace-grid">
        <div class="proyecto-workspace-card highlight">
          <div class="proyecto-workspace-head">
            <h5><i class="fa fa-check-square-o"></i> Checklist del desarrollador</h5>
            <span>Control tecnico</span>
          </div>
          <div class="proyecto-dev-checklist" id="proyectoModalChecklist"></div>
          <div class="proyecto-dev-note">
            Use este control para saber si el proyecto ya tiene alcance claro, documentos, evidencias, avances y si esta listo para revision o entrega.
          </div>
        </div>

        <div class="proyecto-workspace-card">
          <div class="proyecto-workspace-head">
            <h5><i class="fa fa-history"></i> Bitacora de avances</h5>
            <span id="proyectoModalAvancesCount">0 registros</span>
          </div>
          <div id="proyectoModalAvances"></div>
        </div>

        <div class="proyecto-workspace-card">
          <div class="proyecto-workspace-head">
            <h5><i class="fa fa-folder-open"></i> Documentos</h5>
            <span>Propuesta y tecnica</span>
          </div>
          <div id="proyectoModalDocumentos">-</div>
        </div>

        <div class="proyecto-workspace-card">
          <div class="proyecto-workspace-head">
            <h5><i class="fa fa-camera"></i> Evidencias visuales</h5>
            <span>Capturas / videos</span>
          </div>
          <div id="proyectoModalEvidencias">-</div>
        </div>
      </div>

      <div class="proyecto-modal-actions" id="proyectoModalAcciones"></div>
    </div>
  </div></div>
</div>

<script>
var tmProyectoPuedeVerMontos = <?php echo $tmProyectoPuedeVerMontos ? "true" : "false"; ?>;
var tmProyectoPuedeGestionarDocs = <?php echo (($_SESSION["perfil"] ?? "") == "Administrador" || ($_SESSION["rol"] ?? "") == "desarrollador") ? "true" : "false"; ?>;
$(function(){ $('[title]').tooltip({container:'body'}); });

function escapeHtmlProyecto(text){
  return String(text || "").replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]; });
}

function valorProyecto(valor){
  return valor && String(valor).trim() !== "" ? String(valor) : "-";
}

function dineroProyecto(valor){
  var numero = Number(valor || 0);
  return "Bs " + numero.toLocaleString("es-BO", {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function fechaProyecto(valor){
  return valor && String(valor).trim() !== "" && valor !== "0000-00-00" ? String(valor) : "-";
}

function textoEstadoProyecto(estado){
  var mapa = {
    pendiente_adelanto: "Pendiente adelanto",
    en_desarrollo: "En desarrollo",
    revision_interna: "Revision interna",
    revision_cliente: "Revision cliente",
    pendiente_pago_final: "Pendiente pago final",
    pagado_final: "Pagado final",
    completado: "Completado",
    cancelado: "Cancelado"
  };
  if(!tmProyectoPuedeVerMontos){
    mapa.pendiente_adelanto = "Pendiente de inicio";
    mapa.pendiente_pago_final = "Revision final";
    mapa.pagado_final = "Listo para entrega";
  }
  return mapa[estado] || valorProyecto(estado).replace(/_/g, " ");
}

function claseEstadoProyectoModal(estado){
  estado = String(estado || "").toLowerCase();
  if(estado.indexOf("pendiente") !== -1){
    return "warning";
  }
  if(estado.indexOf("completado") !== -1 || estado.indexOf("pagado") !== -1){
    return "success";
  }
  if(estado.indexOf("cancelado") !== -1){
    return "danger";
  }
  return "info";
}

function esEvidenciaProyecto(doc){
  var tipo = String((doc && doc.tipo_documento) || "").toLowerCase();
  var archivo = String((doc && doc.archivo) || "").toLowerCase();
  return tipo.indexOf("evidencia") !== -1 ||
    tipo.indexOf("captura") !== -1 ||
    tipo.indexOf("video") !== -1 ||
    /\.(png|jpg|jpeg|webp|gif|mp4|mov|avi|webm)$/.test(archivo);
}

function renderDocumentosProyecto(docs){
  if(!docs || !docs.length){
    return '<p class="text-muted" style="margin:0">Sin documentos registrados.</p>';
  }

  var grupos = {};
  docs.filter(function(d){ return !esEvidenciaProyecto(d); }).forEach(function(d){
    var tipo = valorProyecto(d.tipo_documento);
    if(!grupos[tipo]){
      grupos[tipo] = [];
    }
    grupos[tipo].push(d);
  });

  if(!Object.keys(grupos).length){
    return '<p class="text-muted" style="margin:0">Sin documentos tecnicos registrados.</p>';
  }

  var html = '<div class="proyecto-doc-group">';
  Object.keys(grupos).forEach(function(tipo){
    html += '<div><div class="proyecto-doc-group-title">'+escapeHtmlProyecto(tipo)+'</div>';
    grupos[tipo].forEach(function(d){
      html += '<div class="proyecto-doc-card"><div><strong>'+escapeHtmlProyecto(d.titulo || "Documento")+'</strong><span>'+escapeHtmlProyecto(d.usuario || "Sin usuario")+'</span></div>';
      html += '<div class="proyecto-doc-actions">';
      html += d.archivo ? '<a class="btn btn-default btn-sm" target="_blank" href="'+escapeHtmlProyecto(d.archivo)+'"><i class="fa fa-folder-open"></i> Abrir</a>' : '<span class="text-muted">Sin archivo</span>';
      if(tmProyectoPuedeGestionarDocs && d.id){
        html += ' <button type="button" class="btn btn-danger btn-sm btnEliminarDocumentoProyecto" data-id="'+escapeHtmlProyecto(d.id)+'"><i class="fa fa-trash"></i> Eliminar</button>';
      }
      html += '</div>';
      html += '</div>';
    });
    html += '</div>';
  });
  html += '</div>';
  return html;
}

function renderEvidenciasProyecto(docs){
  var evidencias = (docs || []).filter(esEvidenciaProyecto);
  if(!evidencias.length){
    return '<p class="text-muted" style="margin:0">Sin capturas o videos registrados.</p>';
  }

  var html = '<div class="proyecto-evidence-grid">';
  evidencias.forEach(function(d){
    var archivo = String(d.archivo || "");
    var archivoLower = archivo.toLowerCase();
    var preview = '<i class="fa fa-file-image-o"></i>';
    if(/\.(png|jpg|jpeg|webp|gif)$/.test(archivoLower)){
      preview = '<img src="'+escapeHtmlProyecto(archivo)+'" alt="'+escapeHtmlProyecto(d.titulo || "Evidencia")+'">';
    }else if(/\.(mp4|mov|avi|webm)$/.test(archivoLower)){
      preview = '<video src="'+escapeHtmlProyecto(archivo)+'" muted controls></video>';
    }
    html += '<div class="proyecto-evidence-item">';
    html += '<div class="proyecto-evidence-preview">'+preview+'</div>';
    html += '<div class="proyecto-evidence-body"><strong>'+escapeHtmlProyecto(d.titulo || d.tipo_documento || "Evidencia")+'</strong>';
    html += archivo ? '<a target="_blank" href="'+escapeHtmlProyecto(archivo)+'"><i class="fa fa-external-link"></i> Abrir evidencia</a>' : '<span class="text-muted">Sin archivo</span>';
    if(tmProyectoPuedeGestionarDocs && d.id){
      html += '<button type="button" class="btn btn-danger btn-xs btnEliminarDocumentoProyecto" data-id="'+escapeHtmlProyecto(d.id)+'"><i class="fa fa-trash"></i> Eliminar evidencia</button>';
    }
    html += '</div></div>';
  });
  html += '</div>';
  return html;
}

function renderAvancesProyecto(avances){
  if(!avances || !avances.length){
    $("#proyectoModalAvancesCount").text("0 registros");
    return '<p class="text-muted" style="margin:0">Sin bitacora de avances.</p>';
  }
  $("#proyectoModalAvancesCount").text(avances.length + " registro(s)");
  var html = '<ul class="proyecto-timeline">';
  avances.forEach(function(a){
    html += '<li><div class="proyecto-timeline-percent">'+escapeHtmlProyecto(a.porcentaje || 0)+'%</div>';
    html += '<div class="proyecto-timeline-body"><strong>'+escapeHtmlProyecto(textoEstadoProyecto(a.estado))+' - '+escapeHtmlProyecto(a.usuario || "Sin usuario")+'</strong>';
    html += '<p>'+escapeHtmlProyecto(a.descripcion || "Sin detalle registrado.")+'</p></div></li>';
  });
  html += '</ul>';
  return html;
}

function renderChecklistProyecto(p, docs, avances){
  var avance = Number(p.porcentaje_avance || 0);
  var tieneDocs = (docs || []).some(function(d){ return !esEvidenciaProyecto(d); });
  var tieneEvidencia = (docs || []).some(esEvidenciaProyecto);
  var tieneAvances = (avances || []).length > 0;
  var estado = String(p.estado || "");
  var items = [
    {txt:"Alcance y entregables definidos", ok:String(p.alcance || "").trim() !== "" && String(p.entregables || "").trim() !== ""},
    {txt:"Propuesta o documento tecnico cargado", ok:tieneDocs},
    {txt:"Evidencias visuales del avance", ok:tieneEvidencia},
    {txt:"Bitacora tecnica registrada", ok:tieneAvances},
    {txt: tmProyectoPuedeVerMontos ? "Proyecto en revision o listo para cobro" : "Proyecto en revision o listo para entrega", ok:avance >= 80 || estado.indexOf("revision") !== -1 || estado === "pendiente_pago_final" || estado === "pagado_final" || estado === "completado"},
    {txt:"Entrega y acta final cerrada", ok:estado === "completado"}
  ];
  return items.map(function(item){
    return '<div class="proyecto-dev-check '+(item.ok ? 'done' : 'warn')+'"><i class="fa '+(item.ok ? 'fa-check' : 'fa-clock-o')+'"></i><span>'+escapeHtmlProyecto(item.txt)+'</span></div>';
  }).join("");
}

function pintarDetalleProyecto(p, docs, avances){
  var avance = Math.max(0, Math.min(100, Number(p.porcentaje_avance || 0)));
  var estadoTexto = textoEstadoProyecto(p.estado);
  var estadoClase = claseEstadoProyectoModal(p.estado);

  $("#proyectoModalCodigo").html('<i class="fa fa-hashtag"></i> ' + valorProyecto(p.codigo));
  $("#proyectoModalTitulo").text(valorProyecto(p.nombre_proyecto));
  $("#proyectoModalSubtitulo").text(valorProyecto(p.tipo_software));
  $("#proyectoModalCliente").text(valorProyecto(p.cliente));
  $("#proyectoModalVendedor").text(valorProyecto(p.vendedor));
  $("#proyectoModalDesarrollador").text(valorProyecto(p.desarrollador || "Sin asignar"));
  $("#proyectoModalAvance").text(avance + "%");
  $("#proyectoModalBarraAvance").css("width", avance + "%");
  $("#proyectoModalEstadoTexto").text(estadoTexto);
  $("#proyectoModalEstado")
    .removeClass("warning success info danger")
    .addClass(estadoClase)
    .html('<i class="fa fa-circle"></i> ' + estadoTexto);
  $("#proyectoModalEntrega").text(fechaProyecto(p.fecha_entrega_estimada));
  $("#proyectoModalTotal").text(dineroProyecto(p.precio_total));
  $("#proyectoModalAdelanto").text(dineroProyecto(p.monto_adelanto));
  $("#proyectoModalSaldo").text(dineroProyecto(p.saldo_pendiente));
  $("#proyectoModalAlcance").text(valorProyecto(p.alcance));
  $("#proyectoModalEntregables").text(valorProyecto(p.entregables));
  $("#proyectoModalExclusiones").text(valorProyecto(p.exclusiones));
  $("#proyectoModalObservaciones").text(valorProyecto(p.observaciones));
  $("#proyectoModalChecklist").html(renderChecklistProyecto(p, docs, avances));
  $("#proyectoModalDocumentos").html(renderDocumentosProyecto(docs));
  $("#proyectoModalEvidencias").html(renderEvidenciasProyecto(docs));
  $("#proyectoModalAvances").html(renderAvancesProyecto(avances));
}

$(document).on("click", ".proyectoCardDetalle", function(e){
  if($(e.target).closest("button, a, .btn").length){
    return;
  }

  var p = {};
  var docs = [];
  var avances = [];
  try{ p = JSON.parse($(this).attr("data-proyecto") || "{}"); }catch(error){}
  try{ docs = JSON.parse($(this).attr("data-documentos") || "[]"); }catch(error){}
  try{ avances = JSON.parse($(this).attr("data-avances") || "[]"); }catch(error){}

  pintarDetalleProyecto(p, docs, avances);
  $("#proyectoModalAcciones").html($(this).find(".proyecto-actions").html());
  $("#modalVerProyectoSoftware").modal("show");
});

$(document).on("input", "#buscarProyectoCards", function(){
  var busqueda = ($(this).val() || "").toLowerCase().trim();
  $(".proyectoCardDetalle").each(function(){
    var coincide = ($(this).attr("data-search") || "").indexOf(busqueda) !== -1;
    $(this).toggle(coincide);
  });
});

$(document).on("click", ".btnAvanceProyecto", function(e){
  e.preventDefault();
  e.stopPropagation();
  $("#idProyectoAvance").val($(this).attr("idProyecto"));
  $("#porcentajeAvanceProyecto").val($(this).attr("porcentaje"));
  $("#modalVerProyectoSoftware").modal("hide");
  $("#modalAvanceProyecto").modal("show");
});

$(document).on("click", ".btnDocumentoProyecto", function(e){
  e.preventDefault();
  e.stopPropagation();
  $("#idProyectoDocumento").val($(this).attr("idProyecto"));
  $("#tipoDocumentoProyecto").val($(this).attr("tipoDocumento") || "Documento tecnico PDF");
  $("#modalVerProyectoSoftware").modal("hide");
  $("#modalDocumentoProyecto").modal("show");
});

$(document).on("click", ".btnEliminarDocumentoProyecto", function(e){
  e.preventDefault();
  e.stopPropagation();
  var idDocumento = $(this).data("id");
  if(!idDocumento){
    return;
  }
  swal({
    type: "warning",
    title: "Eliminar documento?",
    text: "Se quitara del proyecto. Si el archivo existe tambien se borrara del servidor.",
    showCancelButton: true,
    confirmButtonText: "Si, eliminar",
    cancelButtonText: "Cancelar"
  }).then(function(result){
    if(result.value){
      window.location = "index.php?ruta=proyectos&eliminarDocumentoProyecto=" + encodeURIComponent(idDocumento);
    }
  });
});

$(document).on("click", ".btnImprimirContratoSoftware", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/contrato-software.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});

$(document).on("click", ".btnImprimirActaSoftware", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/acta-entrega-software.php?idProyecto=" + $(this).attr("idProyecto"), "_blank");
});
</script>

<?php ControladorProyectos::ctrEntregarProyecto(); ?>
