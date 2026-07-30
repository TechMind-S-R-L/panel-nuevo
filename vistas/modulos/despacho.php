<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$ventasCobradasDespacho = ControladorVentas::ctrMostrarVentasCobradas();
$ventasCobradasDespacho = is_array($ventasCobradasDespacho) ? $ventasCobradasDespacho : array();
$ventasDespacho = array_values(array_filter($ventasCobradasDespacho, function($venta){
  return ($venta["estado_despacho"] ?? "") == "pendiente";
}));
$ventasCompletadasDespacho = array_values(array_filter($ventasCobradasDespacho, function($venta){
  return ($venta["estado_despacho"] ?? "") == "entregado";
}));

function tmDespachoEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmDespachoProductos($venta){
  $productos = json_decode($venta["productos"] ?? "[]", true);
  return is_array($productos) ? $productos : array();
}

function tmDespachoResumenProductos($productos){
  $cantidad = 0;
  foreach($productos as $producto){
    $cantidad += (int)($producto["cantidad"] ?? 0);
  }
  return $cantidad;
}

function tmDespachoProductosHtml($productos){
  if(empty($productos)){
    return '<div class="tm-dispatch-product empty">Sin productos registrados</div>';
  }

  ob_start();
  foreach($productos as $producto):
    $descripcion = $producto["descripcion"] ?? "Producto";
    $cantidad = (int)($producto["cantidad"] ?? 0);
    $precio = isset($producto["precio"]) ? (float)$producto["precio"] : (isset($producto["total"]) && $cantidad > 0 ? ((float)$producto["total"] / $cantidad) : 0);
    $subtotal = isset($producto["total"]) ? (float)$producto["total"] : ($precio * $cantidad);
  ?>
    <div class="tm-dispatch-product">
      <div>
        <strong><?php echo tmDespachoEsc($descripcion); ?></strong>
        <span>Cantidad: <?php echo $cantidad; ?> unidad(es)</span>
      </div>
      <div class="tm-dispatch-product-total">
        <b>Bs <?php echo number_format($subtotal, 2); ?></b>
      </div>
    </div>
  <?php
  endforeach;
  return ob_get_clean();
}

function tmDespachoVentaCard($venta, $modo = "pendiente"){
  $cliente = ControladorClientes::ctrMostrarClientes("id", $venta["id_cliente"]);
  $vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_vendedor"]);
  $cajero = !empty($venta["id_cajero"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_cajero"]) : null;
  $productos = tmDespachoProductos($venta);
  $productosHtml = tmDespachoProductosHtml($productos);
  $productosJson = tmDespachoEsc(json_encode($productos));
  $cantidadProductos = tmDespachoResumenProductos($productos);
  $clienteNombre = $cliente["nombre"] ?? "Sin cliente";
  $vendedorNombre = $vendedor["nombre"] ?? "Sin vendedor";
  $cajeroNombre = $cajero["nombre"] ?? "Sin cajero";
  $busqueda = strtolower(($venta["codigo"] ?? "")." ".$clienteNombre." ".$vendedorNombre." ".$cajeroNombre);
  $esCompletado = $modo == "completado" || (($venta["estado_despacho"] ?? "") == "entregado");
  $estadoTexto = $esCompletado ? "Entregado" : "Pendiente entrega";
  $estadoClase = $esCompletado ? "is-completed" : "is-pending";
  $icono = $esCompletado ? "fa-check-circle" : "fa-truck";
  ?>
  <article class="tm-dispatch-card <?php echo $estadoClase; ?>"
    data-search="<?php echo tmDespachoEsc($busqueda); ?>"
    data-id-venta="<?php echo (int)$venta["id"]; ?>"
    data-codigo="<?php echo tmDespachoEsc($venta["codigo"] ?? ""); ?>"
    data-cliente="<?php echo tmDespachoEsc($clienteNombre); ?>"
    data-vendedor="<?php echo tmDespachoEsc($vendedorNombre); ?>"
    data-cajero="<?php echo tmDespachoEsc($cajeroNombre); ?>"
    data-total="Bs <?php echo number_format((float)($venta["total"] ?? 0), 2); ?>"
    data-cantidad="<?php echo (int)$cantidadProductos; ?>"
    data-fecha="<?php echo tmDespachoEsc($venta["fecha"] ?? ""); ?>"
    data-estado="<?php echo tmDespachoEsc($estadoTexto); ?>"
    data-productos="<?php echo $productosJson; ?>">
    <div class="tm-dispatch-card-head">
      <div class="tm-dispatch-icon"><i class="fa <?php echo $icono; ?>"></i></div>
      <div class="tm-dispatch-title">
        <span class="tm-dispatch-code"><i class="fa fa-file-text-o"></i> Venta <?php echo tmDespachoEsc($venta["codigo"] ?? ""); ?></span>
        <h3><?php echo tmDespachoEsc($clienteNombre); ?></h3>
      </div>
      <span class="tm-dispatch-status"><?php echo tmDespachoEsc($estadoTexto); ?></span>
    </div>

    <div class="tm-dispatch-mini">
      <div><i class="fa fa-money"></i><span>Total cobrado</span><strong>Bs <?php echo number_format((float)($venta["total"] ?? 0), 2); ?></strong></div>
      <div><i class="fa fa-cubes"></i><span>Unid.</span><strong><?php echo (int)$cantidadProductos; ?></strong></div>
      <div><i class="fa fa-check-circle"></i><span>Pago</span><strong>Cobrado</strong></div>
    </div>

    <div class="tm-dispatch-people">
      <div><span>Vendedor</span><strong><?php echo tmDespachoEsc($vendedorNombre); ?></strong></div>
      <div><span>Cajero</span><strong><?php echo tmDespachoEsc($cajeroNombre); ?></strong></div>
    </div>

    <div class="tm-dispatch-products">
      <?php echo $productosHtml; ?>
    </div>

    <div class="tm-dispatch-actions">
      <?php if(!$esCompletado): ?>
        <button type="button" class="tm-dispatch-btn primary btnAbrirEntregaVenta" title="Registrar codigos y entregar" idVenta="<?php echo (int)$venta["id"]; ?>" codigoVenta="<?php echo tmDespachoEsc($venta["codigo"] ?? ""); ?>" productos="<?php echo $productosJson; ?>">
          <i class="fa fa-truck"></i><span>Entregar</span>
        </button>
      <?php else: ?>
        <button type="button" class="tm-dispatch-btn success btnImprimirFacturaDespacho" title="Reimprimir factura o nota de venta" idVenta="<?php echo (int)$venta["id"]; ?>" codigoVenta="<?php echo tmDespachoEsc($venta["codigo"] ?? ""); ?>">
          <i class="fa fa-file-text-o"></i><span>Factura</span>
        </button>
        <button type="button" class="tm-dispatch-btn light btnImprimirConformidadDespacho" title="Reimprimir conformidad de entrega" idVenta="<?php echo (int)$venta["id"]; ?>">
          <i class="fa fa-check-square-o"></i><span>Conformidad</span>
        </button>
      <?php endif; ?>
    </div>
    <div class="tm-dispatch-products-template" style="display:none"><?php echo $productosHtml; ?></div>
  </article>
  <?php
}
?>

<style>
.tm-dispatch-page .content{padding-top:12px}
.tm-dispatch-hero{background:linear-gradient(135deg,#12384a,#1d86c8);color:#fff;border-radius:18px;padding:22px 24px;margin-bottom:16px;box-shadow:0 16px 35px rgba(18,56,74,.16);display:flex;align-items:center;justify-content:space-between;gap:18px}
.tm-dispatch-hero h1{margin:0;font-size:25px;font-weight:900}
.tm-dispatch-hero p{margin:6px 0 0;opacity:.93;max-width:780px}
.tm-dispatch-hero-icon{width:58px;height:58px;border-radius:18px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:27px}
.tm-dispatch-stats{display:grid;grid-template-columns:repeat(3,minmax(150px,1fr));gap:12px;margin-bottom:16px}
.tm-dispatch-stat{background:rgba(255,255,255,.78);border:1px solid rgba(45,111,181,.14);border-radius:16px;padding:14px 16px;box-shadow:0 12px 25px rgba(30,80,120,.08)}
.tm-dispatch-stat span{display:block;color:#668099;font-size:12px;font-weight:900;text-transform:uppercase}
.tm-dispatch-stat strong{display:block;color:#12384a;font-size:27px;line-height:1;margin-top:6px}
.tm-dispatch-panel{background:rgba(255,255,255,.74);border:1px solid rgba(45,111,181,.16);border-radius:18px;box-shadow:0 14px 35px rgba(30,80,120,.09);overflow:hidden}
.tm-dispatch-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid rgba(45,111,181,.12)}
.tm-dispatch-toolbar h3{margin:0;font-size:18px;font-weight:900;color:#16324a}
.tm-dispatch-search{max-width:390px;width:100%;position:relative}
.tm-dispatch-search i{position:absolute;left:13px;top:12px;color:#5c7da0}
.tm-dispatch-search input{width:100%;height:40px;border:1px solid rgba(45,111,181,.18);border-radius:12px;padding:0 14px 0 36px;background:rgba(255,255,255,.86);outline:0}
.tm-dispatch-note{background:rgba(22,169,224,.09);border:1px solid rgba(22,169,224,.18);color:#245066;border-radius:14px;padding:12px 14px;margin:16px 18px 0;font-weight:800}
.tm-dispatch-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;padding:18px}
.tm-dispatch-card{background:rgba(255,255,255,.9);border:1px solid rgba(45,111,181,.16);border-radius:16px;padding:14px;box-shadow:0 12px 25px rgba(30,80,120,.08);cursor:pointer;transition:.18s ease;position:relative;overflow:hidden}
.tm-dispatch-card:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(30,80,120,.14);border-color:rgba(22,169,224,.42)}
.tm-dispatch-card:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:#f39c12}
.tm-dispatch-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}
.tm-dispatch-code{font-size:12px;font-weight:900;color:#114d85;background:#edf7ff;border:1px solid rgba(45,111,181,.14);border-radius:999px;padding:6px 9px;line-height:1.2}
.tm-dispatch-status{display:inline-flex;border-radius:999px;background:#f39c12;color:#fff;font-size:11px;font-weight:900;line-height:1.15;padding:6px 9px;text-align:center}
.tm-dispatch-card h3{margin:0 0 10px;color:#142b3f;font-size:17px;font-weight:900;line-height:1.25;min-height:42px}
.tm-dispatch-mini{display:grid;grid-template-columns:1fr 74px 86px;gap:8px;margin-bottom:10px}
.tm-dispatch-mini div{background:#f6f9fc;border:1px solid rgba(45,111,181,.11);border-radius:12px;padding:9px}
.tm-dispatch-mini span{display:block;font-size:10px;text-transform:uppercase;color:#6b8299;font-weight:900;margin-bottom:4px}
.tm-dispatch-mini strong{display:block;font-size:12px;color:#1d3348;line-height:1.25}
.tm-dispatch-people{display:flex;flex-direction:column;gap:5px;margin-bottom:10px;color:#4b647a;font-weight:700;font-size:12px}
.tm-dispatch-products{display:flex;flex-direction:column;gap:7px;max-height:150px;overflow:auto;padding-right:2px}
.tm-dispatch-product{display:flex;justify-content:space-between;gap:10px;background:#fff;border:1px solid rgba(45,111,181,.1);border-radius:12px;padding:9px}
.tm-dispatch-product.empty{color:#8a5b00;background:#fff7e6}
.tm-dispatch-product strong{display:block;color:#1d3348;font-size:12px;line-height:1.25}
.tm-dispatch-product span{display:block;color:#657c93;font-size:11px;margin-top:3px}
.tm-dispatch-product-total{text-align:right;min-width:76px}
.tm-dispatch-product-total b{display:block;color:#114d85;font-size:12px}
.tm-dispatch-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}
.tm-dispatch-btn{border:0;border-radius:10px;padding:8px 10px;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:900;line-height:1;text-decoration:none!important;white-space:normal;min-height:34px}
.tm-dispatch-btn.primary{background:#248fce;color:#fff}
.tm-dispatch-empty{grid-column:1/-1;text-align:center;border:1px dashed rgba(45,111,181,.24);border-radius:16px;padding:34px;background:rgba(255,255,255,.58);color:#5f7690}
.tm-dispatch-empty i{font-size:38px;color:#00a65a}.tm-dispatch-empty h4{font-weight:900;color:#17344c}
.tm-dispatch-modal .modal-dialog{width:min(900px,calc(100vw - 34px))}
.tm-dispatch-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 24px 60px rgba(11,42,68,.28)}
.tm-dispatch-modal .modal-header{border:0;background:linear-gradient(135deg,#12384a,#178bd0);color:#fff;padding:18px 22px}
.tm-dispatch-modal .modal-header h4{margin:0;font-size:19px;font-weight:900}
.tm-dispatch-modal .close{color:#fff;opacity:.85}
.tm-dispatch-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.tm-dispatch-detail{border:1px solid rgba(45,111,181,.14);border-radius:14px;padding:12px;background:#f8fbfd}
.tm-dispatch-detail span{display:block;color:#6b8299;font-size:11px;text-transform:uppercase;font-weight:900;margin-bottom:4px}
.tm-dispatch-detail strong{display:block;color:#153047;font-size:14px;margin:0;font-weight:900;line-height:1.35}
.tm-dispatch-detail.full{grid-column:1/-1}
.tm-dispatch-detail-items{display:flex;flex-direction:column;gap:8px;max-height:270px;overflow:auto}
.tm-dispatch-modal-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.tm-dispatch-delivery-note{background:rgba(22,169,224,.09);border:1px solid rgba(22,169,224,.18);color:#245066;border-radius:14px;padding:12px 14px;margin-bottom:14px;font-weight:800}
.tm-delivery-products{display:flex;flex-direction:column;gap:12px}
.tm-delivery-product{border:1px solid rgba(45,111,181,.16);background:#f8fbfd;border-radius:14px;padding:12px}
.tm-delivery-product h5{margin:0 0 10px;font-weight:900;color:#17344c}
.tm-delivery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px}
.tm-delivery-grid label{font-size:11px;color:#627b94;font-weight:900;text-transform:uppercase}
.tm-delivery-grid input{border-radius:10px;border:1px solid rgba(45,111,181,.18);box-shadow:none}
body.dark-mode .tm-dispatch-panel,body.tm-dark .tm-dispatch-panel,body.dark-mode .tm-dispatch-card,body.tm-dark .tm-dispatch-card,body.dark-mode .tm-dispatch-stat,body.tm-dark .tm-dispatch-stat{background:rgba(15,27,48,.78);border-color:rgba(255,255,255,.12);color:#eaf3ff}
body.dark-mode .tm-dispatch-toolbar h3,body.dark-mode .tm-dispatch-stat strong,body.dark-mode .tm-dispatch-card h3,body.tm-dark .tm-dispatch-toolbar h3,body.tm-dark .tm-dispatch-stat strong,body.tm-dark .tm-dispatch-card h3{color:#fff}
body.dark-mode .tm-dispatch-mini div,body.dark-mode .tm-dispatch-product,body.tm-dark .tm-dispatch-mini div,body.tm-dark .tm-dispatch-product{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-dispatch-mini strong,body.dark-mode .tm-dispatch-product strong,body.tm-dark .tm-dispatch-mini strong,body.tm-dark .tm-dispatch-product strong{color:#edf6ff}
.tm-dispatch-grid{grid-template-columns:repeat(auto-fill,minmax(322px,1fr));gap:16px}
.tm-dispatch-card{padding:0;border-radius:18px;background:rgba(255,255,255,.82);backdrop-filter:blur(8px);display:flex;flex-direction:column;min-height:342px}
.tm-dispatch-card:before{display:none}
.tm-dispatch-card:after{content:"";position:absolute;right:-34px;bottom:-40px;width:126px;height:126px;border-radius:50%;background:rgba(243,156,18,.13);pointer-events:none}
.tm-dispatch-card-head{display:grid;grid-template-columns:46px minmax(0,1fr) auto;gap:10px;align-items:start;margin:0;padding:14px;background:linear-gradient(135deg,rgba(18,56,74,.08),rgba(243,156,18,.08));border-bottom:1px solid rgba(45,111,181,.12)}
.tm-dispatch-icon{width:44px;height:44px;border-radius:15px;background:linear-gradient(135deg,#155a9c,#19aee8);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 10px 22px rgba(24,113,177,.2)}
.tm-dispatch-title{min-width:0}
.tm-dispatch-title h3{margin:7px 0 0!important;min-height:0!important;font-size:16px!important;line-height:1.2!important;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-dispatch-code{display:inline-flex;align-items:center;gap:5px;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tm-dispatch-status{min-width:92px;max-width:118px;justify-content:center;box-shadow:0 8px 18px rgba(243,156,18,.18)}
.tm-dispatch-mini{grid-template-columns:1.2fr .78fr .78fr;gap:8px;margin:12px 14px 10px}
.tm-dispatch-mini div{display:grid;grid-template-columns:24px minmax(0,1fr);column-gap:7px;align-items:start;padding:10px;border-radius:14px}
.tm-dispatch-mini div>i{grid-row:1 / span 2;color:#248fce;margin-top:1px}
.tm-dispatch-mini span,.tm-dispatch-mini strong{min-width:0}
.tm-dispatch-mini strong{font-size:13px}
.tm-dispatch-people{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:0 14px 10px}
.tm-dispatch-people div{background:#f6f9fc;border:1px solid rgba(45,111,181,.1);border-radius:13px;padding:9px}
.tm-dispatch-people span{display:block;font-size:10px;text-transform:uppercase;color:#6b8299;font-weight:900;margin-bottom:3px}
.tm-dispatch-people strong{display:block;color:#1d3348;font-size:12px;line-height:1.25}
.tm-dispatch-products{margin:0 14px;max-height:128px}
.tm-dispatch-product{border-radius:13px;background:#fff;border-color:rgba(45,111,181,.12)}
.tm-dispatch-product strong{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-dispatch-actions{margin-top:auto;padding:12px 14px 14px;border-top:1px solid rgba(45,111,181,.1)}
.tm-dispatch-btn{min-height:38px;border-radius:12px;padding:10px 12px}
.tm-dispatch-actions .tm-dispatch-btn{width:100%}
.tm-dispatch-modal .modal-dialog{width:min(840px,calc(100vw - 34px))}
.tm-dispatch-modal .modal-content{border-radius:20px;background:rgba(255,255,255,.98)}
.tm-dispatch-modal .modal-header{padding:0;background:linear-gradient(135deg,#12384a,#1d86c8);overflow:hidden}
.tm-dispatch-modal-head{display:grid;grid-template-columns:52px minmax(0,1fr) auto;gap:12px;align-items:center;padding:18px 22px;position:relative}
.tm-dispatch-modal-head:after{content:"";position:absolute;right:-44px;top:-50px;width:138px;height:138px;border-radius:50%;background:rgba(255,255,255,.14)}
.tm-dispatch-modal-icon{width:48px;height:48px;border-radius:16px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:21px}
.tm-dispatch-modal-head span{display:block;text-transform:uppercase;font-size:11px;font-weight:900;opacity:.84;letter-spacing:.03em}
.tm-dispatch-modal-head h4{font-size:21px!important;line-height:1.15;margin:0!important;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tm-dispatch-modal-head strong{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;min-width:94px;border-radius:999px;background:rgba(255,255,255,.22);padding:8px 10px;font-size:12px;text-align:center}
.tm-dispatch-summary{display:grid;grid-template-columns:1.2fr .85fr .65fr;gap:12px;margin-bottom:12px}
.tm-dispatch-summary div{border:1px solid rgba(45,111,181,.13);background:linear-gradient(135deg,#f8fbfd,#eef7ff);border-radius:16px;padding:13px}
.tm-dispatch-summary span,.tm-dispatch-detail span{display:block;color:#6b8299;font-size:10px;text-transform:uppercase;font-weight:900;margin-bottom:5px}
.tm-dispatch-summary strong{display:block;color:#153047;font-size:15px;line-height:1.25}
.tm-dispatch-detail-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.tm-dispatch-detail{display:grid;grid-template-columns:28px minmax(0,1fr);column-gap:8px;border-radius:15px;padding:12px}
.tm-dispatch-detail>i{grid-row:1 / span 2;width:28px;height:28px;border-radius:9px;background:#eaf5ff;color:#176ca9;display:flex;align-items:center;justify-content:center}
.tm-dispatch-detail.full{grid-column:1/-1;display:block}
.tm-dispatch-detail-items{max-height:240px}
.tm-dispatch-modal .modal-footer{border-top:1px solid rgba(45,111,181,.12);background:#f7fbfe}
.tm-dispatch-modal-actions .tm-dispatch-btn{min-width:170px}
.tm-dispatch-delivery-note{display:grid;grid-template-columns:150px minmax(0,1fr);gap:12px;align-items:center;border-radius:16px;background:linear-gradient(135deg,#eef8ff,#f8fbfd)}
.tm-dispatch-delivery-note div{background:#fff;border:1px solid rgba(45,111,181,.12);border-radius:13px;padding:10px}
.tm-dispatch-delivery-note span{display:block;font-size:10px;text-transform:uppercase;color:#6b8299;font-weight:900}
.tm-dispatch-delivery-note strong{display:block;color:#12384a;font-size:18px;font-weight:900}
.tm-dispatch-delivery-note p{margin:0;line-height:1.4}
.tm-delivery-product{border-radius:16px;background:#f8fbfd}
.tm-delivery-product h5{display:flex;align-items:center;gap:8px;line-height:1.3}
.tm-delivery-product h5:before{content:"\\f02a";font-family:FontAwesome;width:30px;height:30px;border-radius:10px;background:#eaf5ff;color:#176ca9;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
.tm-delivery-grid{grid-template-columns:repeat(auto-fill,minmax(180px,1fr))}
body.dark-mode .tm-dispatch-card-head,body.tm-dark .tm-dispatch-card-head{background:linear-gradient(135deg,rgba(255,255,255,.08),rgba(243,156,18,.08));border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-dispatch-people div,body.dark-mode .tm-dispatch-detail,body.dark-mode .tm-dispatch-summary div,body.dark-mode .tm-dispatch-delivery-note div,body.tm-dark .tm-dispatch-people div,body.tm-dark .tm-dispatch-detail,body.tm-dark .tm-dispatch-summary div,body.tm-dark .tm-dispatch-delivery-note div{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-dispatch-people strong,body.dark-mode .tm-dispatch-summary strong,body.dark-mode .tm-dispatch-detail strong,body.dark-mode .tm-dispatch-delivery-note strong,body.tm-dark .tm-dispatch-people strong,body.tm-dark .tm-dispatch-summary strong,body.tm-dark .tm-dispatch-detail strong,body.tm-dark .tm-dispatch-delivery-note strong{color:#fff}
.tm-dispatch-tabs{padding:14px 18px 0;border-bottom:1px solid rgba(45,111,181,.12)}
.tm-dispatch-tabs>li>a{border:0!important;border-radius:14px 14px 0 0;color:#49677f;font-weight:900;padding:10px 16px}
.tm-dispatch-tabs>li.active>a,.tm-dispatch-tabs>li.active>a:hover,.tm-dispatch-tabs>li.active>a:focus{background:rgba(36,143,206,.12)!important;color:#114d85!important;border:0!important}
.tm-dispatch-tabs span{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;margin-left:6px;border-radius:999px;background:#248fce;color:#fff;font-size:11px}
.tm-dispatch-tab-content{padding-top:2px}
.tm-dispatch-grid{grid-template-columns:repeat(auto-fill,minmax(282px,1fr))!important;gap:12px!important}
.tm-dispatch-card{min-height:300px!important;border-radius:16px!important}
.tm-dispatch-card.is-completed:after{background:rgba(0,166,90,.14)}
.tm-dispatch-card.is-completed .tm-dispatch-status{background:#00a65a;box-shadow:0 8px 18px rgba(0,166,90,.18)}
.tm-dispatch-card-head{grid-template-columns:39px minmax(0,1fr) minmax(76px,92px)!important;gap:8px!important;padding:11px 12px!important}
.tm-dispatch-icon{width:38px!important;height:38px!important;border-radius:13px!important;font-size:16px!important}
.tm-dispatch-title h3{font-size:14px!important;line-height:1.18!important;margin-top:5px!important}
.tm-dispatch-code{font-size:10px!important;padding:5px 7px!important}
.tm-dispatch-status{min-width:0!important;max-width:none!important;width:100%;font-size:10px!important;line-height:1.1!important;padding:6px 7px!important;white-space:normal;overflow-wrap:anywhere}
.tm-dispatch-mini{grid-template-columns:minmax(94px,1fr) 58px 78px!important;gap:6px!important;margin:10px 12px 8px!important}
.tm-dispatch-mini div{grid-template-columns:18px minmax(0,1fr)!important;column-gap:4px!important;padding:7px 6px!important;border-radius:12px!important;overflow:hidden;align-items:center!important}
.tm-dispatch-mini span{font-size:8px!important;line-height:1!important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px!important}
.tm-dispatch-mini strong{font-size:10.5px!important;line-height:1.1!important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;overflow-wrap:normal!important;word-break:normal!important}
.tm-dispatch-mini div>i{font-size:12px;width:18px;text-align:center}
.tm-dispatch-mini div:nth-child(2),.tm-dispatch-mini div:nth-child(3){grid-template-columns:16px minmax(0,1fr)!important;padding-left:5px!important;padding-right:5px!important}
.tm-dispatch-mini div:nth-child(2) strong,.tm-dispatch-mini div:nth-child(3) strong{font-size:10px!important}
.tm-dispatch-people{gap:6px!important;margin:0 12px 8px!important}
.tm-dispatch-people div{padding:7px!important;border-radius:11px!important;min-width:0}
.tm-dispatch-people strong{font-size:11px!important;overflow-wrap:anywhere}
.tm-dispatch-products{margin:0 12px!important;max-height:94px!important}
.tm-dispatch-product{padding:7px!important;border-radius:11px!important}
.tm-dispatch-product strong{font-size:11px!important}
.tm-dispatch-product span,.tm-dispatch-product-total b{font-size:10px!important}
.tm-dispatch-product-total{min-width:58px!important}
.tm-dispatch-actions{padding:9px 12px 11px!important;gap:6px!important}
.tm-dispatch-actions .tm-dispatch-btn{width:auto!important;flex:1 1 118px;min-height:32px!important;font-size:11px!important;padding:8px 9px!important;border-radius:10px!important}
.tm-dispatch-btn.success{background:#00a65a;color:#fff}
.tm-dispatch-btn.light{background:#eef5fb;color:#17476b;border:1px solid rgba(45,111,181,.16)}
.tm-dispatch-modal .modal-header{position:relative;overflow:hidden}
.tm-dispatch-modal .modal-header .close{position:absolute;right:13px;top:10px;z-index:30;color:#fff;opacity:.96;text-shadow:none;pointer-events:auto;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}
.tm-dispatch-modal-head{padding:15px 58px 15px 18px!important;grid-template-columns:46px minmax(0,1fr) auto!important}
.tm-dispatch-modal-head:after{pointer-events:none}
.tm-dispatch-modal-icon{width:42px!important;height:42px!important;border-radius:14px!important}
.tm-dispatch-modal-head h4{font-size:18px!important}
.tm-dispatch-modal-head strong{font-size:11px!important;min-width:86px!important;padding:7px 9px!important;white-space:normal}
.tm-dispatch-modal-actions .tm-dispatch-btn{min-width:135px!important}
@media(max-width:900px){.tm-dispatch-hero,.tm-dispatch-toolbar{flex-direction:column;align-items:flex-start}.tm-dispatch-stats{grid-template-columns:1fr 1fr}.tm-dispatch-detail-grid{grid-template-columns:1fr}}
@media(max-width:900px){.tm-dispatch-summary,.tm-dispatch-detail-grid{grid-template-columns:1fr}.tm-dispatch-delivery-note{grid-template-columns:1fr}}
@media(max-width:540px){.tm-dispatch-stats{grid-template-columns:1fr}.tm-dispatch-grid{grid-template-columns:1fr}.tm-dispatch-mini,.tm-dispatch-people{grid-template-columns:1fr}.tm-dispatch-modal-head{grid-template-columns:44px minmax(0,1fr)}.tm-dispatch-modal-head strong{grid-column:1/-1;justify-self:start}}
</style>

<div class="content-wrapper tm-dispatch-page">
  <section class="content-header">
    <h1>Despacho de productos</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Despacho</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-dispatch-hero">
      <div>
        <h1>Entrega al cliente con control de codigos</h1>
        <p>Solo se muestran ventas cobradas. Al entregar, registre el codigo unico de cada unidad para cerrar el despacho y emitir la factura mas conformidad.</p>
      </div>
      <div class="tm-dispatch-hero-icon"><i class="fa fa-truck"></i></div>
    </div>

    <div class="tm-dispatch-stats">
      <div class="tm-dispatch-stat"><span>Ventas por entregar</span><strong><?php echo count($ventasDespacho); ?></strong></div>
      <div class="tm-dispatch-stat"><span>Unidades pendientes</span><strong><?php $u = 0; foreach($ventasDespacho as $v){ $u += tmDespachoResumenProductos(tmDespachoProductos($v)); } echo (int)$u; ?></strong></div>
      <div class="tm-dispatch-stat"><span>Completados</span><strong><?php echo count($ventasCompletadasDespacho); ?></strong></div>
    </div>

    <div class="tm-dispatch-panel">
      <div class="tm-dispatch-toolbar">
        <h3><i class="fa fa-archive"></i> Ventas listas para despacho</h3>
        <div class="tm-dispatch-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarDespacho" placeholder="Buscar por boleta, cliente, vendedor o cajero">
        </div>
      </div>
      <div class="tm-dispatch-note">Haga clic en una tarjeta para revisar el detalle. Use Entregar para registrar los codigos fisicos que salen de almacen.</div>

      <ul class="nav nav-tabs tm-dispatch-tabs">
        <li class="active"><a href="#tabDespachoPendientes" data-toggle="tab">Pendientes <span><?php echo count($ventasDespacho); ?></span></a></li>
        <li><a href="#tabDespachoCompletados" data-toggle="tab">Completados <span><?php echo count($ventasCompletadasDespacho); ?></span></a></li>
      </ul>

      <div class="tab-content tm-dispatch-tab-content">
        <div class="tab-pane active" id="tabDespachoPendientes">
          <div class="tm-dispatch-grid">
            <?php
            if(empty($ventasDespacho)){
              echo '<div class="tm-dispatch-empty"><i class="fa fa-check-circle-o"></i><h4>No hay despachos pendientes</h4><p>Todas las ventas cobradas ya fueron entregadas o aun esperan cobro en caja.</p></div>';
            }else{
              foreach($ventasDespacho as $venta){
                tmDespachoVentaCard($venta, "pendiente");
              }
            }
            ?>
          </div>
        </div>
        <div class="tab-pane" id="tabDespachoCompletados">
          <div class="tm-dispatch-grid">
            <?php
            if(empty($ventasCompletadasDespacho)){
              echo '<div class="tm-dispatch-empty"><i class="fa fa-archive"></i><h4>No hay despachos completados</h4><p>Cuando almacen entregue al cliente, la venta aparecera en esta pestana.</p></div>';
            }else{
              foreach($ventasCompletadasDespacho as $venta){
                tmDespachoVentaCard($venta, "completado");
              }
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleDespacho" class="modal fade tm-dispatch-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="tm-dispatch-modal-head">
          <div class="tm-dispatch-modal-icon"><i class="fa fa-truck"></i></div>
          <div>
            <span id="detalleDespachoTipo">Despacho pendiente</span>
            <h4>Venta <b id="detalleDespachoCodigo"></b></h4>
          </div>
          <strong id="detalleDespachoEstado">Cobrado</strong>
        </div>
      </div>
      <div class="modal-body">
        <div class="tm-dispatch-summary">
          <div>
            <span>Cliente</span>
            <strong id="detalleDespachoCliente"></strong>
          </div>
          <div>
            <span>Total cobrado</span>
            <strong id="detalleDespachoTotal"></strong>
          </div>
          <div>
            <span>Unidades a entregar</span>
            <strong id="detalleDespachoCantidad"></strong>
          </div>
        </div>

        <div class="tm-dispatch-detail-grid">
          <div class="tm-dispatch-detail"><i class="fa fa-user"></i><span>Vendedor</span><strong id="detalleDespachoVendedor"></strong></div>
          <div class="tm-dispatch-detail"><i class="fa fa-money"></i><span>Cajero</span><strong id="detalleDespachoCajero"></strong></div>
          <div class="tm-dispatch-detail"><i class="fa fa-calendar"></i><span>Fecha venta</span><strong id="detalleDespachoFecha"></strong></div>
          <div class="tm-dispatch-detail full"><span>Productos cobrados para entregar</span><div class="tm-dispatch-detail-items" id="detalleDespachoProductos"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="tm-dispatch-modal-actions" id="detalleDespachoAcciones"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalEntregarVenta" class="modal fade tm-dispatch-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="formEntregarVenta">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <div class="tm-dispatch-modal-head">
            <div class="tm-dispatch-modal-icon"><i class="fa fa-barcode"></i></div>
            <div>
              <span>Control de inventario</span>
              <h4>Registrar codigos de entrega</h4>
            </div>
            <strong>Almacen</strong>
          </div>
        </div>

        <div class="modal-body">
          <input type="hidden" name="entregarVenta" id="entregarVenta">
          <input type="hidden" name="codigosDespacho" id="codigosDespacho">

          <div class="tm-dispatch-delivery-note">
            <div>
              <span>Venta</span>
              <strong id="codigoVentaEntrega"></strong>
            </div>
            <p>Registre exactamente un codigo unico por cada unidad vendida. La entrega no cerrara si falta un codigo o si no pertenece al producto.</p>
          </div>

          <div class="tm-delivery-products" id="contenedorCodigosEntrega"></div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-check"></i> Confirmar entrega
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var tmDespachoActual = { idVenta: "", codigoVenta: "", productos: [] };

function tmDispatchEscape(text){
  return $("<div>").text(text || "").html();
}

function tmNormalizarCodigoDespacho(codigo){
  codigo = String(codigo || "").trim();
  codigo = codigo.replace(/\s+/g, "");
  codigo = codigo.replace(/['`´’‘‛＇_–—−‐]+/g, "-");
  codigo = codigo.replace(/-+/g, "-").toUpperCase();

  var conSeparador = codigo.match(/^(TMU[A-Z0-9]+)-([0-9]{5})$/);
  if(conSeparador){
    return conSeparador[1] + "-" + conSeparador[2];
  }

  var pegado = codigo.match(/^(TMU[A-Z0-9]+)([0-9]{5})$/);
  if(pegado){
    return pegado[1] + "-" + pegado[2];
  }

  return codigo;
}

function tmAbrirModalEntrega(idVenta, codigoVenta, productos){
  $("#entregarVenta").val(idVenta);
  $("#codigoVentaEntrega").text(codigoVenta);
  $("#codigosDespacho").val("");
  $("#contenedorCodigosEntrega").html("");

  if(!Array.isArray(productos)){
    productos = [];
  }

  productos.forEach(function(producto){
    var idProducto = parseInt(producto.id, 10);
    var cantidad = parseInt(producto.cantidad, 10) || 0;
    var descripcion = producto.descripcion || "Producto";
    var campos = "";

    for(var i = 1; i <= cantidad; i++){
      campos += '<div class="form-group">'+
        '<label>Codigo unidad '+i+'</label>'+
        '<input type="text" class="form-control codigoEntrega" data-id-producto="'+idProducto+'" placeholder="Escanea o escribe el codigo" required>'+
      '</div>';
    }

    $("#contenedorCodigosEntrega").append(
      '<div class="tm-delivery-product">'+
        '<h5>'+tmDispatchEscape(descripcion)+' | Cantidad: '+cantidad+'</h5>'+
        '<div class="tm-delivery-grid">'+campos+'</div>'+
      '</div>'
    );
  });

  if(productos.length === 0){
    $("#contenedorCodigosEntrega").html('<div class="alert alert-danger">No se pudieron cargar productos para esta venta.</div>');
  }

  $("#modalDetalleDespacho").modal("hide");
  setTimeout(function(){
    $("#modalEntregarVenta").modal("show");
  }, 140);
}

$(function(){
  $('[title]').tooltip({container:'body'});

  $("#buscarDespacho").on("input", function(){
    var term = ($(this).val() || "").toLowerCase().trim();
    $(".tm-dispatch-card").each(function(){
      var text = ($(this).attr("data-search") || "").toLowerCase();
      $(this).toggle(text.indexOf(term) !== -1);
    });
  });
});

$(document).on("click", ".tm-dispatch-card", function(event){
  if($(event.target).closest("button,a,.tm-dispatch-actions").length){
    return;
  }

  var card = $(this);
  var productos = [];
  try{
    productos = JSON.parse(card.attr("data-productos") || "[]");
  }catch(e){
    productos = [];
  }

  tmDespachoActual = {
    idVenta: card.data("idVenta"),
    codigoVenta: card.data("codigo"),
    productos: productos
  };

  $("#detalleDespachoCodigo").text(card.data("codigo") || "-");
  $("#detalleDespachoCliente").text(card.data("cliente") || "-");
  $("#detalleDespachoTotal").text(card.data("total") || "-");
  $("#detalleDespachoVendedor").text(card.data("vendedor") || "-");
  $("#detalleDespachoCajero").text(card.data("cajero") || "-");
  $("#detalleDespachoCantidad").text(card.data("cantidad") || "0");
  $("#detalleDespachoFecha").text(card.data("fecha") || "-");
  var estadoDespacho = card.data("estado") || "Pendiente entrega";
  $("#detalleDespachoTipo").text(estadoDespacho === "Entregado" ? "Despacho completado" : "Despacho pendiente");
  $("#detalleDespachoEstado").text(estadoDespacho);
  $("#detalleDespachoProductos").html(card.find(".tm-dispatch-products-template").html());
  $("#detalleDespachoAcciones").html(card.find(".tm-dispatch-actions").html());
  $("#detalleDespachoAcciones [title]").tooltip({container:"body"});
  $("#modalDetalleDespacho").modal("show");
});

$(document).on("click", ".btnAbrirEntregaVenta", function(event){
  event.preventDefault();
  event.stopPropagation();
  var productos = [];
  try{
    productos = JSON.parse($(this).attr("productos") || "[]");
  }catch(e){
    productos = [];
  }
  tmAbrirModalEntrega($(this).attr("idVenta"), $(this).attr("codigoVenta"), productos);
});

$("#btnModalEntregarVenta").on("click", function(){
  tmAbrirModalEntrega(tmDespachoActual.idVenta, tmDespachoActual.codigoVenta, tmDespachoActual.productos);
});

$(document).on("click", ".btnImprimirFacturaDespacho", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/factura.php?idVenta=" + $(this).attr("idVenta") + "&codigo=" + encodeURIComponent($(this).attr("codigoVenta") || ""), "_blank");
});

$(document).on("click", ".btnImprimirConformidadDespacho", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/conformidad.php?idVenta=" + $(this).attr("idVenta"), "_blank");
});

$("#formEntregarVenta").on("submit", function(e){
  var codigos = {};
  var repetidos = {};
  var codigoRepetido = "";

  $(".codigoEntrega").each(function(){
    var idProducto = $(this).attr("data-id-producto");
    var codigo = tmNormalizarCodigoDespacho($(this).val() || "");
    $(this).val(codigo);
    var llave = codigo.toLowerCase();

    if(!codigos[idProducto]){
      codigos[idProducto] = [];
    }

    if(codigo !== ""){
      if(repetidos[llave]){
        codigoRepetido = codigo;
      }
      repetidos[llave] = true;
    }

    codigos[idProducto].push(codigo);
  });

  if(codigoRepetido !== ""){
    e.preventDefault();
    swal({
      type: "error",
      title: "Codigo repetido",
      text: "El codigo "+codigoRepetido+" esta repetido en esta entrega.",
      confirmButtonText: "Cerrar"
    });
    return;
  }

  $("#codigosDespacho").val(JSON.stringify(codigos));
});

$(document).on("blur change", ".codigoEntrega", function(){
  $(this).val(tmNormalizarCodigoDespacho($(this).val() || ""));
});
</script>

<?php
$entregarVenta = new ControladorVentas();
$entregarVenta->ctrEntregarVenta();
?>
