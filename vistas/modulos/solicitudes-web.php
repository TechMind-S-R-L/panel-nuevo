<?php

$solicitudesWeb = ControladorCotizacion::ctrMostrarSolicitudesWeb(null);
$solicitudesWeb = is_array($solicitudesWeb) ? $solicitudesWeb : array();

$solicitudesPendientesWeb = array_values(array_filter($solicitudesWeb, function($solicitud){
  return ($solicitud["estado_web"] ?? "pendiente") != "cotizada";
}));

$solicitudesCotizadasWeb = array_values(array_filter($solicitudesWeb, function($solicitud){
  return ($solicitud["estado_web"] ?? "pendiente") == "cotizada";
}));

if(!function_exists("tmWebTexto")){
  function tmWebTexto($valor){
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
  }
}

if(!function_exists("tmWebEstadoTexto")){
  function tmWebEstadoTexto($estado){
    $estado = $estado ?: "pendiente";
    $mapa = array(
      "pendiente" => "Pendiente",
      "en_revision" => "En revision",
      "cotizada" => "Cotizada"
    );
    return $mapa[$estado] ?? ucwords(str_replace("_", " ", $estado));
  }
}

if(!function_exists("tmWebEstadoClase")){
  function tmWebEstadoClase($estado){
    return ($estado == "cotizada") ? "web-request-cotizada" : "web-request-pendiente";
  }
}

if(!function_exists("tmWebProductos")){
  function tmWebProductos($productos, $limite = null){
    $productos = is_array($productos) ? $productos : array();
    if(count($productos) == 0){
      echo '<div class="web-products-empty">Sin productos registrados.</div>';
      return;
    }

    $mostrar = ($limite === null) ? $productos : array_slice($productos, 0, $limite);
    echo '<div class="web-products-list">';
    foreach($mostrar as $producto){
      $cantidad = max(1, (int)($producto["cantidad"] ?? 1));
      $descripcion = tmWebTexto($producto["descripcion"] ?? "Producto");
      $precio = (float)($producto["precio"] ?? 0);
      $total = (float)($producto["total"] ?? ($precio * $cantidad));
      echo '<div class="web-product-item">
        <div class="web-product-main">
          <strong>'.$descripcion.'</strong>
          <span><i class="fa fa-cube"></i> '.$cantidad.' unidad(es)</span>
        </div>
        <b>'.($precio > 0 ? "Bs ".number_format($total, 2) : "Sin precio").'</b>
      </div>';
    }

    if($limite !== null && count($productos) > $limite){
      echo '<div class="web-product-more">+'.(count($productos) - $limite).' producto(s) mas</div>';
    }
    echo '</div>';
  }
}

if(!function_exists("tmWebAcciones")){
  function tmWebAcciones($solicitud){
    $estado = $solicitud["estado_web"] ?? "pendiente";
    $id = (int)($solicitud["id"] ?? 0);
    $codigo = tmWebTexto($solicitud["codigo"] ?? "");
    $codigoUrl = urlencode((string)($solicitud["codigo"] ?? ""));

    echo '<div class="web-request-actions">
      <a class="btn btn-warning" href="index.php?ruta=procesar-solicitud-web&idSolicitudWeb='.$id.'" title="Procesar solicitud">
        <i class="fa fa-pencil"></i> Procesar
      </a>';

    if($estado == "cotizada"){
      echo '<a class="btn btn-info" target="_blank" href="extensiones/tcpdf/pdf/cotizacion.php?idCotizacion='.$id.'&codigoCotizacion='.$codigoUrl.'" title="Imprimir cotizacion">
        <i class="fa fa-print"></i> Imprimir
      </a>';
    }

    echo '</div>';
  }
}

if(!function_exists("tmWebRenderCards")){
  function tmWebRenderCards($solicitudes, $tipo){
    if(count($solicitudes) == 0){
      echo '<div class="web-request-empty">
        <i class="fa fa-inbox"></i>
        <strong>No hay solicitudes '.($tipo == "cotizadas" ? "cotizadas" : "pendientes").'</strong>
        <span>Cuando llegue una solicitud desde la web aparecera aqui.</span>
      </div>';
      return;
    }

    echo '<div class="web-request-grid">';
    foreach($solicitudes as $solicitud){
      $productos = json_decode($solicitud["productos"] ?? "[]", true);
      $productos = is_array($productos) ? $productos : array();
      $estado = $solicitud["estado_web"] ?? "pendiente";
      $estadoTexto = tmWebEstadoTexto($estado);
      $estadoClase = tmWebEstadoClase($estado);
      $idModal = "modalSolicitudWeb".(int)$solicitud["id"];
      $codigo = tmWebTexto($solicitud["codigo"] ?? "");
      $cliente = tmWebTexto($solicitud["cliente"] ?? "Sin cliente");
      $contacto = trim(($solicitud["telefono"] ?? "")." ".($solicitud["email"] ?? ""));
      $fecha = tmWebTexto($solicitud["fecha"] ?? "");
      $total = number_format((float)($solicitud["total"] ?? 0), 2);
      $vendedor = tmWebTexto($solicitud["vendedor"] ?? "Sin vendedor asignado");
      $validoHasta = tmWebTexto($solicitud["valido_hasta"] ?? "-");

      echo '<div class="web-request-card '.$estadoClase.'" data-toggle="modal" data-target="#'.$idModal.'">
        <div class="web-request-top">
          <span>'.$estadoTexto.'</span>
          <strong>#'.$codigo.'</strong>
        </div>
        <h4>'.$cliente.'</h4>
        <div class="web-request-meta">
          <span><i class="fa fa-phone"></i> '.tmWebTexto($contacto ?: "Sin contacto").'</span>
          <span><i class="fa fa-calendar"></i> '.$fecha.'</span>
        </div>
        <div class="web-request-preview">
          <div class="web-request-count">
            <i class="fa fa-cubes"></i>
            <div>
              <strong>'.count($productos).'</strong>
              <span>producto(s) solicitados</span>
            </div>
          </div>
        </div>
        <div class="web-request-footer">
          <b>Bs '.$total.'</b>
          <small>Clic para ver detalle</small>
        </div>
      </div>

      <div class="modal fade web-request-modal" id="'.$idModal.'" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header '.$estadoClase.'">
              <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
              <div class="web-modal-title">
                <div class="web-modal-icon"><i class="fa fa-globe"></i></div>
                <div>
                  <span class="web-modal-kicker">'.$estadoTexto.'</span>
                  <h4 class="modal-title">Solicitud web #'.$codigo.'</h4>
                  <p>Registro recibido desde TechMind Store</p>
                </div>
              </div>
            </div>
            <div class="modal-body">
              <div class="web-modal-status-row">
                <span class="web-modal-status '.$estadoClase.'"><i class="fa fa-circle"></i> '.$estadoTexto.'</span>
                <span class="web-modal-code"><i class="fa fa-hashtag"></i> '.$codigo.'</span>
              </div>

              <div class="web-modal-summary">
                <div class="web-summary-card cliente"><span>Cliente</span><strong>'.$cliente.'</strong><p>'.tmWebTexto($contacto ?: "Sin contacto").'</p></div>
                <div class="web-summary-card"><span>Vendedor</span><strong>'.$vendedor.'</strong><p>Responsable comercial</p></div>
                <div class="web-summary-card total"><span>Total cotizado</span><strong>Bs '.$total.'</strong><p>'.count($productos).' producto(s)</p></div>
                <div class="web-summary-card"><span>Fecha solicitud</span><strong>'.$fecha.'</strong></div>
                <div class="web-summary-card"><span>Valido hasta</span><strong>'.$validoHasta.'</strong></div>
              </div>

              <div class="web-modal-section">
                <div class="web-section-title">
                  <div><i class="fa fa-cubes"></i></div>
                  <div>
                    <strong>Productos solicitados</strong>
                    <span>Detalle completo de la solicitud enviada por el cliente.</span>
                  </div>
                </div>';
                tmWebProductos($productos, null);
              echo '</div>
              <div class="web-modal-section actions">
                <div class="web-section-title">
                  <div><i class="fa fa-bolt"></i></div>
                  <div>
                    <strong>Acciones disponibles</strong>
                    <span>Procese la solicitud o reimprima la cotizacion si ya fue publicada.</span>
                  </div>
                </div>';
                tmWebAcciones($solicitud);
              echo '</div>';
            echo '</div>
          </div>
        </div>
      </div>';
    }
    echo '</div>';
  }
}

?>

<div class="content-wrapper solicitudes-web-page">
<style>
  .solicitudes-web-page .web-hero{
    background:linear-gradient(135deg,#163140,#227eac);
    color:#fff;
    padding:20px;
    border-radius:12px;
    margin-bottom:16px;
    box-shadow:0 16px 36px rgba(22,49,64,.18);
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
  }
  .solicitudes-web-page .web-hero h2{margin:0 0 6px;font-weight:800;font-size:24px;}
  .solicitudes-web-page .web-hero p{margin:0;color:#d9ecf7;}
  .web-hero-stats{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }
  .web-hero-stat{
    min-width:110px;
    padding:10px 12px;
    border-radius:10px;
    background:rgba(255,255,255,.14);
    border:1px solid rgba(255,255,255,.18);
    text-align:center;
  }
  .web-hero-stat b{display:block;font-size:24px;line-height:1;}
  .web-hero-stat span{font-size:12px;color:#e8f6ff;font-weight:700;}
  .web-tabs-box{
    background:rgba(255,255,255,.84);
    border:1px solid rgba(180,205,224,.7);
    border-radius:12px;
    box-shadow:0 14px 34px rgba(22,49,64,.10);
    overflow:hidden;
  }
  .web-tabs-box .nav-tabs{
    border:0;
    background:rgba(245,249,252,.78);
    padding:8px 10px 0;
  }
  .web-tabs-box .nav-tabs>li>a{
    border:0 !important;
    border-radius:10px 10px 0 0;
    color:#506476;
    font-weight:800;
    padding:12px 18px;
  }
  .web-tabs-box .nav-tabs>li.active>a,
  .web-tabs-box .nav-tabs>li.active>a:hover{
    background:#fff;
    color:#163140;
    box-shadow:0 -2px 0 #2b9fd4 inset;
  }
  .web-tabs-box .tab-content{
    background:#fff;
    padding:16px;
  }
  .web-request-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(225px,1fr));
    gap:10px;
  }
  .web-request-card{
    position:relative;
    border:1px solid #dbe7ef;
    border-radius:12px;
    background:#fff;
    min-height:198px;
    padding:11px 12px;
    box-shadow:0 10px 24px rgba(22,49,64,.08);
    cursor:pointer;
    overflow:hidden;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .web-request-card:before{
    content:"";
    position:absolute;
    left:0;
    top:0;
    bottom:0;
    width:7px;
  }
  .web-request-card:hover{
    transform:translateY(-3px);
    border-color:#8fc5e1;
    box-shadow:0 16px 34px rgba(22,49,64,.14);
  }
  .web-request-pendiente:before{background:#f39c12;}
  .web-request-cotizada:before{background:#00a65a;}
  .web-request-top{
    display:flex;
    justify-content:space-between;
    gap:8px;
    align-items:center;
    margin-bottom:8px;
  }
  .web-request-top span{
    display:inline-flex;
    padding:4px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    color:#fff;
  }
  .web-request-pendiente .web-request-top span{background:#f39c12;}
  .web-request-cotizada .web-request-top span{background:#00a65a;}
  .web-request-top strong{
    color:#2a7298;
    font-size:13px;
  }
  .web-request-card h4{
    margin:0 0 8px;
    color:#102b3b;
    font-size:15px;
    font-weight:800;
    line-height:1.25;
    overflow-wrap:anywhere;
  }
  .web-request-meta{
    display:grid;
    gap:4px;
    color:#607484;
    font-size:11px;
    font-weight:700;
    margin-bottom:9px;
  }
  .web-request-meta span{
    overflow-wrap:anywhere;
  }
  .web-request-preview{
    min-height:60px;
    background:#f7fbfd;
    border:1px solid #e3edf4;
    border-radius:10px;
    padding:8px;
    display:flex;
    align-items:center;
  }
  .web-request-count{
    width:100%;
    display:flex;
    align-items:center;
    gap:9px;
  }
  .web-request-count i{
    width:34px;
    height:34px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#eaf5fb;
    color:#176b9b;
    font-size:15px;
  }
  .web-request-count strong{
    display:block;
    color:#163140;
    font-size:20px;
    line-height:1;
  }
  .web-request-count span{
    display:block;
    color:#6f8190;
    font-size:11px;
    font-weight:800;
    margin-top:3px;
  }
  .web-products-list{
    display:grid;
    gap:7px;
  }
  .web-product-item{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:10px;
    padding:8px;
    border-radius:8px;
    background:#fff;
    border:1px solid #e3edf4;
  }
  .web-product-item strong{
    display:block;
    color:#203442;
    font-size:12px;
    line-height:1.3;
    overflow-wrap:anywhere;
  }
  .web-product-item span{
    display:block;
    color:#789;
    font-size:11px;
    font-weight:700;
    margin-top:3px;
  }
  .web-product-item b{
    color:#176b9b;
    white-space:nowrap;
    font-size:12px;
  }
  .web-product-more,
  .web-products-empty{
    color:#7a8a96;
    font-size:12px;
    font-weight:800;
    text-align:center;
    padding:6px;
  }
  .web-request-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    margin-top:9px;
  }
  .web-request-footer b{
    font-size:17px;
    color:#163140;
  }
  .web-request-footer small{
    color:#7a8a96;
    font-weight:800;
    font-size:11px;
  }
  .web-request-empty{
    min-height:220px;
    border:1px dashed #b8cfdf;
    border-radius:12px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:8px;
    color:#6b7e8c;
    background:#f8fbfd;
    text-align:center;
  }
  .web-request-empty i{
    font-size:34px;
    color:#8fb1c8;
  }
  .web-request-modal .modal-dialog{max-width:920px;width:92%;}
  .web-request-modal .modal-content{
    border:0;
    border-radius:16px;
    overflow:hidden;
    background:#f4f8fb;
    box-shadow:0 28px 80px rgba(10,30,45,.34);
  }
  .web-request-modal .modal-header{
    position:relative;
    color:#fff;
    border:0;
    padding:18px 22px;
    overflow:hidden;
  }
  .web-request-modal .modal-header:after{
    content:"";
    position:absolute;
    right:-42px;
    top:-62px;
    width:180px;
    height:180px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
  }
  .web-request-modal .modal-header.web-request-pendiente{background:linear-gradient(135deg,#7b5310,#f39c12);}
  .web-request-modal .modal-header.web-request-cotizada{background:linear-gradient(135deg,#0c5b38,#00a65a);}
  .web-request-modal .close{
    position:relative;
    z-index:3;
    color:#fff;
    opacity:.9;
    text-shadow:none;
    width:32px;
    height:32px;
    border-radius:50%;
    background:rgba(255,255,255,.18);
    font-size:24px;
    line-height:30px;
  }
  .web-modal-title{
    position:relative;
    z-index:2;
    display:flex;
    align-items:center;
    gap:12px;
    max-width:88%;
  }
  .web-modal-icon{
    width:48px;
    height:48px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.18);
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
    font-size:21px;
    flex:0 0 48px;
  }
  .web-modal-kicker{
    display:inline-flex;
    align-items:center;
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
  .web-modal-title h4{
    margin:0;
    font-size:20px;
    font-weight:900;
    line-height:1.18;
  }
  .web-modal-title p{
    margin:3px 0 0;
    color:#eaf7ff;
    font-weight:700;
  }
  .web-request-modal .modal-body{
    background:#f4f8fb;
    padding:14px;
  }
  .web-modal-status-row{
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
  .web-modal-status,
  .web-modal-code{
    display:inline-flex;
    align-items:center;
    gap:7px;
    min-height:28px;
    padding:5px 9px;
    border-radius:999px;
    font-size:11px;
    font-weight:900;
    color:#fff;
    background:#607d8b;
  }
  .web-modal-status.web-request-pendiente{background:#f39c12;}
  .web-modal-status.web-request-cotizada{background:#00a65a;}
  .web-modal-code{background:#176b9b;}
  .web-modal-summary{
    display:grid;
    grid-template-columns:1.25fr 1fr 1fr;
    gap:8px;
    margin-bottom:10px;
  }
  .web-summary-card{
    background:#fff;
    border:1px solid #dbe7ef;
    border-radius:10px;
    padding:10px 11px;
    min-height:70px;
    box-shadow:0 8px 18px rgba(22,49,64,.05);
  }
  .web-summary-card.cliente{grid-row:span 2;}
  .web-summary-card.total strong{
    color:#176b9b;
    font-size:19px;
  }
  .web-summary-card span,
  .web-section-title span{
    display:block;
    color:#6f8190;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    margin-bottom:4px;
  }
  .web-summary-card strong{
    display:block;
    color:#1f2d3d;
    font-size:14px;
    font-weight:900;
    line-height:1.25;
    overflow-wrap:anywhere;
  }
  .web-summary-card p{
    margin:4px 0 0;
    color:#60717f;
    font-weight:800;
    overflow-wrap:anywhere;
  }
  .web-modal-section{
    background:#fff;
    border:1px solid #dce8f1;
    border-radius:10px;
    padding:11px;
    margin-top:10px;
    box-shadow:0 8px 18px rgba(22,49,64,.04);
  }
  .web-modal-section.actions{padding-bottom:10px;}
  .web-section-title{
    display:flex;
    align-items:center;
    gap:8px;
    margin-bottom:10px;
  }
  .web-section-title>div:first-child{
    width:32px;
    height:32px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    background:linear-gradient(135deg,#176b9b,#36aee2);
    flex:0 0 32px;
  }
  .web-section-title strong{
    display:block;
    color:#163140;
    font-size:14px;
    font-weight:900;
    margin-bottom:2px;
  }
  .web-section-title span{
    margin:0;
    text-transform:none;
    font-size:11px;
    font-weight:800;
  }
  .web-request-modal .web-products-list{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:8px;
  }
  .web-request-modal .web-product-item{
    border-radius:9px;
    background:#f8fbfd;
    border:1px solid #e2ebf2;
    padding:9px 10px;
    align-items:flex-start;
  }
  .web-product-main strong{
    color:#263845;
    font-size:13px;
    font-weight:900;
    line-height:1.3;
  }
  .web-product-main span{
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-top:4px;
    color:#60717f;
    font-size:12px;
    font-weight:800;
  }
  .web-request-modal .web-product-item b{
    padding:5px 8px;
    border-radius:999px;
    background:#e8f4fb;
    color:#176b9b;
  }
  .web-request-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }
  .web-request-actions .btn{
    border-radius:8px;
    font-weight:900;
    padding:7px 12px;
  }
  @media(max-width:991px){
    .web-modal-summary{
      grid-template-columns:1fr 1fr;
    }
    .web-summary-card.cliente{grid-row:auto;}
    .web-request-modal .web-products-list{grid-template-columns:1fr;}
    .web-request-grid{
      grid-template-columns:1fr;
    }
  }
  @media(max-width:767px){
    .web-modal-summary{grid-template-columns:1fr;}
    .web-modal-title{max-width:100%;}
  }
</style>

  <section class="content-header">
    <h1>Solicitudes web</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Solicitudes web</li>
    </ol>
  </section>

  <section class="content">
    <div class="web-hero">
      <div>
        <h2>Solicitudes de compra/cotizacion desde TechMind Store</h2>
        <p>Revise cada solicitud, procese precios y publique la cotizacion para que el cliente pueda verla desde la web.</p>
      </div>
      <div class="web-hero-stats">
        <div class="web-hero-stat">
          <b><?php echo count($solicitudesPendientesWeb); ?></b>
          <span>Pendientes</span>
        </div>
        <div class="web-hero-stat">
          <b><?php echo count($solicitudesCotizadasWeb); ?></b>
          <span>Cotizadas</span>
        </div>
      </div>
    </div>

    <div class="web-tabs-box">
      <ul class="nav nav-tabs">
        <li class="active">
          <a href="#tabSolicitudesWebPendientes" data-toggle="tab">
            Pendientes <span class="badge bg-yellow"><?php echo count($solicitudesPendientesWeb); ?></span>
          </a>
        </li>
        <li>
          <a href="#tabSolicitudesWebCotizadas" data-toggle="tab">
            Cotizadas <span class="badge bg-green"><?php echo count($solicitudesCotizadasWeb); ?></span>
          </a>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane active" id="tabSolicitudesWebPendientes">
          <?php tmWebRenderCards($solicitudesPendientesWeb, "pendientes"); ?>
        </div>
        <div class="tab-pane" id="tabSolicitudesWebCotizadas">
          <?php tmWebRenderCards($solicitudesCotizadasWeb, "cotizadas"); ?>
        </div>
      </div>
    </div>
  </section>
</div>
