<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

function tmProductoPrecioEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmProductoPrecioCategoriasMapa(){
  static $mapa = null;
  if($mapa !== null){
    return $mapa;
  }
  $mapa = array();
  $categorias = ControladorCategorias::ctrMostrarCategorias(null, null);
  if(is_array($categorias)){
    foreach($categorias as $categoria){
      $mapa[(int)$categoria["id"]] = $categoria["ruta_categoria"] ?? $categoria["categoria"] ?? "Sin categoria";
    }
  }
  return $mapa;
}

function tmProductoPrecioCategoria($idCategoria, $mapaCategorias = null){
  if(empty($idCategoria)){
    return "Sin categoria";
  }

  $mapaCategorias = is_array($mapaCategorias) ? $mapaCategorias : tmProductoPrecioCategoriasMapa();
  return $mapaCategorias[(int)$idCategoria] ?? "Sin categoria";
}

function tmProductoPrecioImagen($ruta){
  $ruta = trim((string)$ruta);
  if($ruta === ""){
    return "vistas/img/productos/default/anonymous.png";
  }
  return $ruta;
}

function tmProductoPrecioStock($stock){
  $stock = (int)$stock;
  if($stock <= 10){
    return '<span class="tm-price-stock stock-danger">'.$stock.'</span>';
  }
  if($stock <= 15){
    return '<span class="tm-price-stock stock-warning">'.$stock.'</span>';
  }
  return '<span class="tm-price-stock stock-success">'.$stock.'</span>';
}

function tmProductoPrecioEstado($producto){
  $compra = strtolower(trim((string)($producto["estado_compra"] ?? "")));
  if($compra === "aprobado"){
    return '<span class="tm-price-state state-ready">Ingreso aprobado</span>';
  }
  return '<span class="tm-price-state state-pending">Pendiente de precio</span>';
}

function tmProductoPrecioDatos($productos){
  $mapaCategorias = tmProductoPrecioCategoriasMapa();
  $datos = array();
  foreach($productos as $producto){
    $categoria = tmProductoPrecioCategoria($producto["id_categoria"] ?? null, $mapaCategorias);
    $codigo = (string)($producto["codigo"] ?? "");
    $codigoGeneral = (string)($producto["codigo_producto_generico"] ?? "");
    $codigoUnico = (string)($producto["codigo_barras_unico"] ?? "");
    $descripcion = (string)($producto["descripcion"] ?? "Producto sin nombre");
    $datos[] = array(
      "id" => (int)($producto["id"] ?? 0),
      "codigo" => $codigo,
      "codigoGeneral" => $codigoGeneral,
      "codigoUnico" => $codigoUnico,
      "descripcion" => $descripcion,
      "categoria" => $categoria,
      "imagen" => tmProductoPrecioImagen($producto["imagen"] ?? ""),
      "stock" => (int)($producto["stock"] ?? 0),
      "precioCompra" => (float)($producto["precio_compra"] ?? 0),
      "precioVenta" => (float)($producto["precio_venta"] ?? 0),
      "ultimoCostoFacturado" => (float)($producto["ultimo_costo_facturado"] ?? 0),
      "ultimaFacturaCompra" => (string)($producto["ultima_factura_compra"] ?? ""),
      "fecha" => (string)($producto["fecha"] ?? ""),
      "estadoCompra" => (string)($producto["estado_compra"] ?? ""),
      "search" => strtolower($codigo." ".$codigoGeneral." ".$codigoUnico." ".$descripcion." ".$categoria)
    );
  }
  return $datos;
}

function tmProductoPrecioRender($productos){
  if(empty($productos)){
    echo '<div class="tm-price-empty">
            <i class="fa fa-tags"></i>
            <strong>No hay productos pendientes de precio.</strong>
            <span>Cuando almacen ingrese productos sin precio apareceran aqui.</span>
          </div>';
    return;
  }

  $mapaCategorias = tmProductoPrecioCategoriasMapa();
  foreach($productos as $producto){
    $id = (int)($producto["id"] ?? 0);
    $codigo = $producto["codigo"] ?? "";
    $codigoGeneral = $producto["codigo_producto_generico"] ?? "";
    $codigoUnico = $producto["codigo_barras_unico"] ?? "";
    $descripcion = $producto["descripcion"] ?? "Producto sin nombre";
    $categoria = tmProductoPrecioCategoria($producto["id_categoria"] ?? null, $mapaCategorias);
    $imagen = tmProductoPrecioImagen($producto["imagen"] ?? "");
    $stock = (int)($producto["stock"] ?? 0);
    $precioCompra = (float)($producto["precio_compra"] ?? 0);
    $precioVenta = (float)($producto["precio_venta"] ?? 0);
    $fecha = $producto["fecha"] ?? "";
    $textoBusqueda = strtolower($codigo." ".$codigoGeneral." ".$codigoUnico." ".$descripcion." ".$categoria);

    echo '<article class="tm-price-card" tabindex="0"
        data-search="'.tmProductoPrecioEsc($textoBusqueda).'"
        data-id="'.$id.'"
        data-codigo="'.tmProductoPrecioEsc($codigo).'"
        data-codigo-general="'.tmProductoPrecioEsc($codigoGeneral).'"
        data-codigo-unico="'.tmProductoPrecioEsc($codigoUnico).'"
        data-descripcion="'.tmProductoPrecioEsc($descripcion).'"
        data-categoria="'.tmProductoPrecioEsc($categoria).'"
        data-stock="'.$stock.'"
        data-imagen="'.tmProductoPrecioEsc($imagen).'"
        data-precio-compra="'.tmProductoPrecioEsc($precioCompra).'"
        data-precio-venta="'.tmProductoPrecioEsc($precioVenta).'"
        data-fecha="'.tmProductoPrecioEsc($fecha).'">
        <div class="tm-price-img">
          <img src="'.tmProductoPrecioEsc($imagen).'" alt="'.tmProductoPrecioEsc($descripcion).'" onerror="this.src=\'vistas/img/productos/default/anonymous.png\'">
          '.tmProductoPrecioEstado($producto).'
        </div>
        <div class="tm-price-content">
          <div class="tm-price-top">
            <span><i class="fa fa-barcode"></i> '.tmProductoPrecioEsc($codigo ?: "Sin codigo").'</span>
            '.tmProductoPrecioStock($stock).'
          </div>
          <h3>'.tmProductoPrecioEsc($descripcion).'</h3>
          <p>'.tmProductoPrecioEsc($categoria).'</p>
          <div class="tm-price-meta">
            <div><span>Compra</span><b>Bs '.number_format($precioCompra, 2).'</b></div>
            <div><span>Venta</span><b>Bs '.number_format($precioVenta, 2).'</b></div>
          </div>
          <button type="button" class="btn btn-primary btnAbrirPrecioProducto" title="Poner precio">
            <i class="fa fa-tag"></i> Poner precio
          </button>
        </div>
      </article>';
  }
}

$productosPendientesPrecio = ControladorProductos::ctrMostrarPendientesPrecio();
$productosPendientesPrecio = is_array($productosPendientesPrecio) ? $productosPendientesPrecio : array();
$productosPendientesPrecioJson = tmProductoPrecioDatos($productosPendientesPrecio);
$totalProductosPendientesPrecio = count($productosPendientesPrecio);
$stockTotalPendientePrecio = array_sum(array_map(function($producto){
  return (int)($producto["stock"] ?? 0);
}, $productosPendientesPrecio));
$costoTotalPendientePrecio = array_sum(array_map(function($producto){
  return ((float)($producto["precio_compra"] ?? 0)) * ((int)($producto["stock"] ?? 0));
}, $productosPendientesPrecio));
?>

<div class="content-wrapper tm-price-wrapper">
  <style>
    .tm-price-panel{
      border:1px solid rgba(184,205,232,.70);
      border-radius:16px;
      background:rgba(255,255,255,.72);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      overflow:hidden;
    }
    .tm-price-toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:14px;
      padding:16px;
      border-bottom:1px solid rgba(184,205,232,.62);
      background:rgba(255,255,255,.62);
    }
    .tm-price-toolbar h2{
      margin:0;
      color:#1f2d3d;
      font-size:18px;
      font-weight:950;
    }
    .tm-price-toolbar p{
      margin:3px 0 0;
      color:#68798e;
      font-size:12px;
      font-weight:750;
    }
    .tm-price-search{
      position:relative;
      width:min(390px, 100%);
      flex:0 0 min(390px, 100%);
    }
    .tm-price-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#3c8dbc;
    }
    .tm-price-search input{
      width:100%;
      border:1px solid rgba(184,205,232,.85);
      border-radius:12px;
      padding:11px 14px 11px 36px;
      color:#1f2d3d;
      font-weight:800;
      outline:0;
      background:rgba(255,255,255,.86);
    }
    .tm-price-body{padding:16px;}
    .tm-price-viewbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      margin-bottom:12px;
      color:#52657a;
      font-size:12px;
      font-weight:850;
      flex-wrap:wrap;
    }
    .tm-price-viewbar select{
      width:auto;
      display:inline-block;
      border-radius:10px;
      border:1px solid rgba(184,205,232,.85);
      font-weight:850;
      height:34px;
      padding:4px 10px;
      background:rgba(255,255,255,.88);
    }
    .tm-price-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));
      gap:12px;
    }
    .tm-price-pagination{
      display:flex;
      justify-content:center;
      align-items:center;
      gap:6px;
      flex-wrap:wrap;
      margin-top:14px;
    }
    .tm-price-page-btn{
      border:1px solid rgba(184,205,232,.86);
      background:rgba(255,255,255,.86);
      color:#17456b;
      border-radius:10px;
      padding:7px 10px;
      min-width:34px;
      font-size:12px;
      font-weight:900;
    }
    .tm-price-page-btn.active,
    .tm-price-page-btn:hover{
      background:#3c8dbc;
      color:#fff;
      border-color:#3c8dbc;
    }
    .tm-price-card{
      border:1px solid rgba(184,205,232,.74);
      border-radius:14px;
      background:rgba(255,255,255,.86);
      box-shadow:0 14px 30px rgba(15,23,42,.07);
      overflow:hidden;
      cursor:pointer;
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      display:flex;
      flex-direction:column;
      min-height:314px;
    }
    .tm-price-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 20px 38px rgba(15,23,42,.13);
    }
    .tm-price-img{
      position:relative;
      height:112px;
      background:linear-gradient(135deg, rgba(60,141,188,.12), rgba(0,192,239,.08));
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
    }
    .tm-price-img img{
      width:100%;
      height:100%;
      object-fit:contain;
      padding:10px;
    }
    .tm-price-state{
      position:absolute;
      left:9px;
      top:9px;
      border-radius:999px;
      padding:5px 8px;
      color:#fff;
      font-size:9.5px;
      font-weight:950;
      text-transform:uppercase;
      box-shadow:0 8px 18px rgba(15,23,42,.13);
    }
    .tm-price-state.state-ready{background:#00a65a;}
    .tm-price-state.state-pending{background:#f39c12;}
    .tm-price-content{
      padding:12px;
      display:flex;
      flex-direction:column;
      gap:9px;
      flex:1;
    }
    .tm-price-top{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:8px;
    }
    .tm-price-top span{
      color:#176b9b;
      font-size:10.5px;
      font-weight:950;
      text-transform:uppercase;
      overflow-wrap:anywhere;
    }
    .tm-price-stock{
      display:inline-flex;
      min-width:36px;
      height:26px;
      align-items:center;
      justify-content:center;
      border-radius:8px;
      color:#fff;
      font-size:12px;
      font-weight:950;
    }
    .tm-price-stock.stock-danger{background:#dd4b39;}
    .tm-price-stock.stock-warning{background:#f39c12;}
    .tm-price-stock.stock-success{background:#00a65a;}
    .tm-price-card h3{
      margin:0;
      color:#1f2d3d;
      font-size:14px;
      line-height:1.25;
      font-weight:950;
      overflow-wrap:anywhere;
    }
    .tm-price-card p{
      margin:0;
      color:#66788d;
      font-size:11.5px;
      line-height:1.25;
      font-weight:800;
      min-height:29px;
      overflow-wrap:anywhere;
    }
    .tm-price-meta{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:7px;
      margin-top:auto;
    }
    .tm-price-meta div{
      border:1px solid rgba(184,205,232,.62);
      border-radius:10px;
      background:rgba(248,251,255,.82);
      padding:8px;
    }
    .tm-price-meta span{
      display:block;
      color:#718299;
      font-size:9.5px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-price-meta b{
      display:block;
      margin-top:2px;
      color:#203047;
      font-size:12px;
      font-weight:950;
    }
    .tm-price-card .btn{
      border-radius:9px;
      font-weight:900;
      padding:8px 10px;
    }
    .tm-price-empty{
      min-height:220px;
      border:1px dashed rgba(60,141,188,.36);
      border-radius:15px;
      color:#6d7f93;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:8px;
      text-align:center;
      background:rgba(255,255,255,.58);
    }
    .tm-price-empty i{font-size:34px;color:#3c8dbc;}
    .tm-price-modal .modal-dialog{width:min(760px, calc(100vw - 28px));}
    .tm-price-modal .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 28px 70px rgba(15,23,42,.28);
    }
    .tm-price-modal .modal-header{
      position:relative;
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#3c8dbc);
      padding:18px 22px;
    }
    .tm-price-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-52px;
      top:-72px;
      width:165px;
      height:165px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-price-modal .modal-title{
      position:relative;
      z-index:1;
      font-size:22px;
      font-weight:950;
      line-height:1.15;
    }
    .tm-price-modal .modal-title small{
      display:block;
      margin-top:5px;
      color:rgba(255,255,255,.86);
      font-size:12px;
      font-weight:800;
    }
    .tm-price-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.9;
      text-shadow:none;
    }
    .tm-price-modal-body{
      padding:16px;
      background:#f5f8fc;
    }
    .tm-price-detail{
      display:grid;
      grid-template-columns:190px 1fr;
      gap:14px;
      margin-bottom:14px;
    }
    .tm-price-preview{
      border:1px solid rgba(184,205,232,.72);
      border-radius:14px;
      background:#fff;
      min-height:180px;
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
    }
    .tm-price-preview img{
      width:100%;
      height:180px;
      object-fit:contain;
      padding:12px;
    }
    .tm-price-detail-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:9px;
    }
    .tm-price-box{
      border:1px solid rgba(184,205,232,.72);
      border-radius:12px;
      background:#fff;
      padding:10px 12px;
      min-width:0;
    }
    .tm-price-box span{
      display:block;
      color:#728299;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-price-box strong{
      display:block;
      margin-top:4px;
      color:#203047;
      font-size:13px;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .tm-price-form-card{
      border:1px solid rgba(184,205,232,.76);
      border-radius:15px;
      background:#fff;
      padding:14px;
    }
    .tm-price-form-card h4{
      margin:0 0 12px;
      color:#203047;
      font-size:15px;
      font-weight:950;
    }
    .tm-price-form-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
    }
    .tm-price-form-card label{
      color:#4d6178;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-price-form-card .input-group-addon{
      border-color:#d6e1ee;
      background:#eef5fc;
      color:#176b9b;
      font-weight:900;
    }
    .tm-price-form-card .form-control{
      border-color:#d6e1ee;
      border-radius:0 8px 8px 0;
      font-weight:850;
    }
    .tm-price-percent{
      display:grid;
      grid-template-columns:1fr 120px;
      gap:12px;
      align-items:end;
      margin-top:12px;
    }
    .tm-price-percent label{
      display:flex;
      align-items:center;
      gap:8px;
      min-height:34px;
      margin:0;
      color:#4d6178;
      text-transform:none;
      font-size:12px;
    }
    .tm-price-modal .modal-footer{
      border:0;
      padding:14px 16px;
      background:#f5f8fc;
    }
    .tm-price-modal .modal-footer .btn{
      border-radius:9px;
      font-weight:900;
      padding:9px 14px;
    }
    body.tm-dark-mode .tm-price-panel,
    body.dark-mode .tm-price-panel,
    body.tm-dark-mode .tm-price-card,
    body.dark-mode .tm-price-card{
      background:rgba(15,23,42,.72);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-price-toolbar,
    body.dark-mode .tm-price-toolbar{background:rgba(15,23,42,.52);}
    body.tm-dark-mode .tm-price-toolbar h2,
    body.tm-dark-mode .tm-price-card h3,
    body.tm-dark-mode .tm-price-meta b,
    body.dark-mode .tm-price-toolbar h2,
    body.dark-mode .tm-price-card h3,
    body.dark-mode .tm-price-meta b{color:#f8fbff;}
    body.tm-dark-mode .tm-price-meta div,
    body.dark-mode .tm-price-meta div{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.13);}
    @media (max-width: 767px){
      .tm-price-toolbar{flex-direction:column;align-items:stretch;}
      .tm-price-search{width:100%;flex:auto;}
      .tm-price-grid{grid-template-columns:1fr;}
      .tm-price-detail{grid-template-columns:1fr;}
      .tm-price-detail-grid,
      .tm-price-form-grid,
      .tm-price-percent{grid-template-columns:1fr;}
    }
    .tm-price-wrapper .content{padding-top:10px;}
    .tm-price-hero{
      position:relative;
      margin-bottom:14px;
      padding:18px 20px;
      border:1px solid rgba(184,205,232,.62);
      border-radius:18px;
      color:#fff;
      background:linear-gradient(135deg, rgba(16,43,59,.94), rgba(23,107,155,.86));
      box-shadow:0 18px 38px rgba(15,23,42,.12);
      overflow:hidden;
    }
    .tm-price-hero:after{
      content:"";
      position:absolute;
      right:-64px;
      top:-82px;
      width:220px;
      height:220px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .tm-price-hero h2{
      position:relative;
      z-index:1;
      margin:0 0 5px;
      font-size:24px;
      font-weight:950;
    }
    .tm-price-hero p{
      position:relative;
      z-index:1;
      margin:0;
      max-width:820px;
      color:rgba(255,255,255,.86);
      font-size:13px;
      font-weight:750;
      line-height:1.35;
    }
    .tm-price-kpis{
      display:grid;
      grid-template-columns:repeat(3, minmax(0, 1fr));
      gap:10px;
      margin-bottom:12px;
    }
    .tm-price-kpi{
      display:flex;
      align-items:center;
      gap:10px;
      min-width:0;
      border:1px solid rgba(184,205,232,.66);
      border-radius:15px;
      background:rgba(255,255,255,.70);
      padding:12px;
      box-shadow:0 12px 26px rgba(15,23,42,.06);
    }
    .tm-price-kpi i{
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
    .tm-price-kpi span{
      display:block;
      color:#6b7d91;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-price-kpi strong{
      display:block;
      margin-top:2px;
      color:#162235;
      font-size:18px;
      font-weight:950;
      line-height:1.15;
      overflow-wrap:anywhere;
    }
    .tm-price-panel{
      border-radius:17px;
      background:rgba(255,255,255,.62);
    }
    .tm-price-toolbar{
      padding:12px;
      background:rgba(255,255,255,.45);
    }
    .tm-price-toolbar h2{font-size:16px;}
    .tm-price-search input{
      height:36px;
      padding-top:0;
      padding-bottom:0;
    }
    .tm-price-body{padding:12px;}
    .tm-price-grid{
      grid-template-columns:repeat(auto-fill, minmax(218px, 1fr));
      gap:10px;
    }
    .tm-price-card{
      min-height:286px;
      border-radius:14px;
      background:rgba(255,255,255,.82);
    }
    .tm-price-img{height:96px;}
    .tm-price-img img{padding:8px;}
    .tm-price-state{
      left:8px;
      top:8px;
      padding:4px 7px;
      font-size:8.8px;
    }
    .tm-price-content{
      padding:10px;
      gap:7px;
    }
    .tm-price-top span{font-size:10px;}
    .tm-price-stock{
      min-width:32px;
      height:23px;
      border-radius:8px;
      font-size:11px;
    }
    .tm-price-card h3{font-size:13px;}
    .tm-price-card p{
      font-size:10.8px;
      min-height:27px;
    }
    .tm-price-meta{gap:6px;}
    .tm-price-meta div{
      border-radius:9px;
      padding:6px 7px;
    }
    .tm-price-meta span{font-size:8.8px;}
    .tm-price-meta b{font-size:11px;}
    .tm-price-card .btn{
      padding:7px 9px;
      font-size:12px;
    }
    .tm-price-empty{grid-column:1 / -1;}
    .tm-price-modal .modal-dialog{width:min(820px, calc(100vw - 36px));}
    .tm-price-modal .modal-header{
      padding:14px 18px;
      background:linear-gradient(135deg,#176b9b,#36aee2);
    }
    .tm-price-modal .modal-title{
      display:flex;
      align-items:center;
      gap:10px;
      font-size:20px;
    }
    .tm-price-modal-icon{
      width:40px;
      height:40px;
      border-radius:13px;
      background:rgba(255,255,255,.18);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:18px;
      flex:0 0 auto;
    }
    .tm-price-modal .modal-title small{
      margin-top:3px;
      font-size:11px;
    }
    .tm-price-modal-body{padding:13px;}
    .tm-price-detail{
      grid-template-columns:170px 1fr;
      gap:12px;
      margin-bottom:12px;
    }
    .tm-price-preview{min-height:160px;}
    .tm-price-preview img{height:160px;padding:10px;}
    .tm-price-detail-grid{gap:8px;}
    .tm-price-box{
      border-radius:10px;
      padding:8px 10px;
    }
    .tm-price-box span{font-size:9px;}
    .tm-price-box strong{font-size:12px;}
    .tm-price-form-card{
      border-radius:13px;
      padding:12px;
    }
    .tm-price-form-card h4{
      margin-bottom:10px;
      font-size:14px;
    }
    .tm-price-form-grid{gap:10px;}
    .tm-price-percent{margin-top:10px;}
    .tm-price-modal .modal-footer{
      padding:10px 13px;
      background:#fff;
      border-top:1px solid #e4eef5;
    }
    .tm-price-modal .modal-footer .btn{
      padding:7px 12px;
      font-size:12px;
    }
    body.tm-dark-mode .tm-price-kpi,
    body.dark-mode .tm-price-kpi{
      background:rgba(15,23,42,.58);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-price-kpi strong,
    body.dark-mode .tm-price-kpi strong{color:#f8fbff;}
    body.tm-dark-mode .tm-price-modal .modal-body,
    body.dark-mode .tm-price-modal .modal-body,
    body.tm-dark-mode .tm-price-modal-body,
    body.dark-mode .tm-price-modal-body{background:#0d1729;}
    body.tm-dark-mode .tm-price-modal .modal-footer,
    body.dark-mode .tm-price-modal .modal-footer{background:rgba(15,27,48,.92);border-color:rgba(147,197,253,.18);}
    @media (max-width: 991px){
      .tm-price-kpis{grid-template-columns:repeat(2, minmax(0, 1fr));}
    }
    @media (max-width: 767px){
      .tm-price-kpis{grid-template-columns:1fr;}
      .tm-price-detail{grid-template-columns:1fr;}
    }
  </style>

  <section class="content-header">
    <h1>Productos sin precio</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Productos sin precio</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-price-hero">
      <h2><i class="fa fa-tags"></i> Gestión de precios pendientes</h2>
      <p>Revise los productos ingresados por almacén y asigne precio de compra y venta para habilitarlos en ventas.</p>
    </div>

    <div class="tm-price-kpis">
      <div class="tm-price-kpi">
        <i class="fa fa-cubes"></i>
        <div><span>Productos pendientes</span><strong><?php echo $totalProductosPendientesPrecio; ?> producto(s)</strong></div>
      </div>
      <div class="tm-price-kpi">
        <i class="fa fa-archive"></i>
        <div><span>Unidades en stock</span><strong><?php echo number_format($stockTotalPendientePrecio, 0); ?> unidad(es)</strong></div>
      </div>
      <div class="tm-price-kpi">
        <i class="fa fa-money"></i>
        <div><span>Costo registrado</span><strong>Bs <?php echo number_format($costoTotalPendientePrecio, 2); ?></strong></div>
      </div>
    </div>

    <div class="tm-price-panel">
      <div class="tm-price-toolbar">
        <div>
          <h2><i class="fa fa-tags"></i> Productos pendientes de precio</h2>
          <p>Asigna precio de compra y venta antes de habilitar estos productos para ventas.</p>
        </div>
        <div class="tm-price-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarProductoPrecio" placeholder="Buscar por codigo, producto o categoria">
        </div>
      </div>
      <div class="tm-price-body">
        <div class="tm-price-viewbar">
          <div id="tmPrecioResumenVista">Preparando productos...</div>
          <label>Mostrar
            <select id="tmPrecioPorPagina">
              <option value="12">12</option>
              <option value="24" selected>24</option>
              <option value="36">36</option>
              <option value="50">50</option>
            </select>
          </label>
        </div>
        <div class="tm-price-grid" id="gridProductosSinPrecio">
          <div class="tm-price-empty">
            <i class="fa fa-spinner fa-spin"></i>
            <strong>Cargando productos pendientes.</strong>
            <span>Optimizando la vista para que no se congele.</span>
          </div>
        </div>
        <div class="tm-price-pagination" id="tmPrecioPaginacion"></div>
      </div>
    </div>
  </section>
</div>

<div id="modalEditarProducto" class="modal fade tm-price-modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
          <h4 class="modal-title">
            <span class="tm-price-modal-icon"><i class="fa fa-tag"></i></span>
            <span>
              Poner precio
              <small id="modalPrecioSubtitulo">Producto pendiente de precio</small>
            </span>
          </h4>
        </div>

        <div class="tm-price-modal-body">
          <div class="tm-price-detail">
            <div class="tm-price-preview">
              <img id="modalPrecioImagen" src="vistas/img/productos/default/anonymous.png" alt="Producto">
            </div>
            <div class="tm-price-detail-grid">
              <div class="tm-price-box">
                <span>Codigo</span>
                <strong id="modalPrecioCodigo">-</strong>
              </div>
              <div class="tm-price-box">
                <span>Stock</span>
                <strong id="modalPrecioStock">-</strong>
              </div>
              <div class="tm-price-box">
                <span>Categoria</span>
                <strong id="modalPrecioCategoria">-</strong>
              </div>
              <div class="tm-price-box">
                <span>Agregado</span>
                <strong id="modalPrecioFecha">-</strong>
              </div>
              <div class="tm-price-box">
                <span>Codigo general</span>
                <strong id="modalPrecioCodigoGeneral">-</strong>
              </div>
              <div class="tm-price-box">
                <span>Codigo unico</span>
                <strong id="modalPrecioCodigoUnico">-</strong>
              </div>
              <div class="tm-price-box">
                <span>Ultimo costo con factura</span>
                <strong id="modalPrecioCostoReferencia">Sin referencia</strong>
              </div>
            </div>
            <a id="modalPrecioFacturaReferencia" class="btn btn-default btn-block" target="_blank" style="display:none;margin-top:10px">
              <i class="fa fa-file-image-o"></i> Ver factura de referencia
            </a>
          </div>

          <div class="tm-price-form-card">
            <h4><i class="fa fa-calculator"></i> Datos del precio</h4>
            <div class="tm-price-form-grid">
              <div class="form-group">
                <label for="nuevoPrecioCompra">Precio de compra</label>
                <div class="input-group">
                  <span class="input-group-addon">Bs</span>
                  <input type="number" class="form-control input-lg" id="nuevoPrecioCompra" placeholder="Precio de compra" name="nuevoPrecioCompra" step="any" min="0" required>
                </div>
              </div>
              <div class="form-group">
                <label for="nuevoPrecioVenta">Precio de venta</label>
                <div class="input-group">
                  <span class="input-group-addon">Bs</span>
                  <input type="number" class="form-control input-lg" id="nuevoPrecioVenta" name="nuevoPrecioVenta" placeholder="Precio de venta" step="any" min="0" required>
                </div>
              </div>
            </div>

            <div class="tm-price-percent">
              <label>
                <input type="checkbox" class="minimal porcentaje" checked>
                Utilizar porcentaje para calcular precio de venta
              </label>
              <div class="input-group">
                <input type="number" class="form-control input-lg nuevoPorcentaje" step="any" min="0" value="40" required>
                <span class="input-group-addon"><i class="fa fa-percent"></i></span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <input type="hidden" id="idProducto" name="idProducto">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar precio</button>
        </div>
      </form>

      <?php
        $editarProducto = new ControladorProductos();
        $editarProducto -> ctrEditarProductoCajero();
      ?>
    </div>
  </div>
</div>

<script>
var tmProductosPrecioData = <?php echo json_encode($productosPendientesPrecioJson, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

(function(){
  var productos = Array.isArray(tmProductosPrecioData) ? tmProductosPrecioData : [];
  var filtrados = productos.slice();
  var paginaActual = 1;
  var porPagina = 24;
  var timerBusqueda = null;

  function tmPriceEscape(value){
    return $("<div>").text(value || "").html();
  }

  function tmPriceFormat(value){
    var number = Number(value || 0);
    if(!isFinite(number)){
      number = 0;
    }
    return number.toFixed(2);
  }

  function recalcularPrecioVenta(){
    if(!$(".porcentaje").prop("checked")){
      $("#nuevoPrecioVenta").prop("readonly", false);
      return;
    }

    var compra = Number($("#nuevoPrecioCompra").val() || 0);
    var porcentaje = Number($(".nuevoPorcentaje").val() || 0);
    var venta = porcentaje >= 100 ? compra : (compra / (1 - porcentaje / 100));
    $("#nuevoPrecioVenta").val(tmPriceFormat(venta));
    $("#nuevoPrecioVenta").prop("readonly", true);
  }

  function stockHtml(stock){
    stock = Number(stock || 0);
    var clase = stock <= 10 ? "stock-danger" : (stock <= 15 ? "stock-warning" : "stock-success");
    return '<span class="tm-price-stock ' + clase + '">' + stock + '</span>';
  }

  function estadoHtml(producto){
    var estado = String(producto.estadoCompra || "").toLowerCase().trim();
    if(estado === "aprobado"){
      return '<span class="tm-price-state state-ready">Ingreso aprobado</span>';
    }
    return '<span class="tm-price-state state-pending">Pendiente de precio</span>';
  }

  function renderCard(producto, index){
    return '<article class="tm-price-card" tabindex="0" data-index="' + index + '">' +
        '<div class="tm-price-img">' +
          '<img loading="lazy" src="' + tmPriceEscape(producto.imagen || "vistas/img/productos/default/anonymous.png") + '" alt="' + tmPriceEscape(producto.descripcion) + '" onerror="this.src=\'vistas/img/productos/default/anonymous.png\'">' +
          estadoHtml(producto) +
        '</div>' +
        '<div class="tm-price-content">' +
          '<div class="tm-price-top">' +
            '<span><i class="fa fa-barcode"></i> ' + tmPriceEscape(producto.codigo || "Sin codigo") + '</span>' +
            stockHtml(producto.stock) +
          '</div>' +
          '<h3>' + tmPriceEscape(producto.descripcion || "Producto sin nombre") + '</h3>' +
          '<p>' + tmPriceEscape(producto.categoria || "Sin categoria") + '</p>' +
          '<div class="tm-price-meta">' +
            '<div><span>Compra</span><b>Bs ' + tmPriceFormat(producto.precioCompra) + '</b></div>' +
            '<div><span>Venta</span><b>Bs ' + tmPriceFormat(producto.precioVenta) + '</b></div>' +
          '</div>' +
          '<button type="button" class="btn btn-primary btnAbrirPrecioProducto" title="Poner precio">' +
            '<i class="fa fa-tag"></i> Poner precio' +
          '</button>' +
        '</div>' +
      '</article>';
  }

  function renderPaginacion(totalPaginas){
    if(totalPaginas <= 1){
      $("#tmPrecioPaginacion").empty();
      return;
    }

    var botones = [];
    botones.push('<button type="button" class="tm-price-page-btn" data-page="' + Math.max(1, paginaActual - 1) + '">Anterior</button>');
    var inicio = Math.max(1, paginaActual - 2);
    var fin = Math.min(totalPaginas, paginaActual + 2);
    if(inicio > 1){
      botones.push('<button type="button" class="tm-price-page-btn" data-page="1">1</button>');
      if(inicio > 2){ botones.push('<span class="tm-price-page-dots">...</span>'); }
    }
    for(var i = inicio; i <= fin; i++){
      botones.push('<button type="button" class="tm-price-page-btn ' + (i === paginaActual ? "active" : "") + '" data-page="' + i + '">' + i + '</button>');
    }
    if(fin < totalPaginas){
      if(fin < totalPaginas - 1){ botones.push('<span class="tm-price-page-dots">...</span>'); }
      botones.push('<button type="button" class="tm-price-page-btn" data-page="' + totalPaginas + '">' + totalPaginas + '</button>');
    }
    botones.push('<button type="button" class="tm-price-page-btn" data-page="' + Math.min(totalPaginas, paginaActual + 1) + '">Siguiente</button>');
    $("#tmPrecioPaginacion").html(botones.join(""));
  }

  function renderProductos(){
    var total = filtrados.length;
    var totalPaginas = Math.max(1, Math.ceil(total / porPagina));
    paginaActual = Math.max(1, Math.min(paginaActual, totalPaginas));
    var inicio = (paginaActual - 1) * porPagina;
    var pagina = filtrados.slice(inicio, inicio + porPagina);

    if(productos.length === 0){
      $("#gridProductosSinPrecio").html('<div class="tm-price-empty"><i class="fa fa-tags"></i><strong>No hay productos pendientes de precio.</strong><span>Cuando almacen ingrese productos sin precio apareceran aqui.</span></div>');
      $("#tmPrecioResumenVista").text("Sin productos pendientes.");
      $("#tmPrecioPaginacion").empty();
      return;
    }

    if(total === 0){
      $("#gridProductosSinPrecio").html('<div class="tm-price-empty"><i class="fa fa-search"></i><strong>No hay coincidencias.</strong><span>Prueba con otro codigo, producto o categoria.</span></div>');
      $("#tmPrecioResumenVista").text("0 resultados encontrados.");
      $("#tmPrecioPaginacion").empty();
      return;
    }

    $("#gridProductosSinPrecio").html(pagina.map(function(producto, idx){
      return renderCard(producto, inicio + idx);
    }).join(""));
    $("#tmPrecioResumenVista").text("Mostrando " + (inicio + 1) + " - " + Math.min(inicio + porPagina, total) + " de " + total + " producto(s).");
    renderPaginacion(totalPaginas);
  }

  function abrirModalPrecio(producto){
    if(!producto){ return; }
    $("#idProducto").val(producto.id);
    $("#nuevoPrecioCompra").val(producto.precioCompra || "");
    $("#nuevoPrecioVenta").val(producto.precioVenta || "");
    $("#modalPrecioSubtitulo").text(producto.descripcion || "Producto pendiente de precio");
    $("#modalPrecioImagen").attr("src", producto.imagen || "vistas/img/productos/default/anonymous.png");
    $("#modalPrecioCodigo").text(producto.codigo || "Sin codigo");
    $("#modalPrecioStock").text(producto.stock || "0");
    $("#modalPrecioCategoria").text(producto.categoria || "Sin categoria");
    $("#modalPrecioFecha").text(producto.fecha || "-");
    $("#modalPrecioCodigoGeneral").text(producto.codigoGeneral || "-");
    $("#modalPrecioCodigoUnico").text(producto.codigoUnico || "-");
    var costoReferencia = Number(producto.ultimoCostoFacturado || 0);
    $("#modalPrecioCostoReferencia").text(costoReferencia > 0 ? "Bs " + tmPriceFormat(costoReferencia) : "Sin referencia");
    $("#modalPrecioFacturaReferencia")
      .attr("href", producto.ultimaFacturaCompra || "#")
      .toggle(!!producto.ultimaFacturaCompra);
    if(Number(producto.precioCompra || 0) <= 0 && costoReferencia > 0){
      $("#nuevoPrecioCompra").val(tmPriceFormat(costoReferencia));
    }
    recalcularPrecioVenta();
    $("#modalEditarProducto").modal("show");
  }

  $(document).on("click keypress", ".tm-price-card", function(event){
    if(event.type === "keypress" && event.which !== 13 && event.which !== 32){
      return;
    }
    abrirModalPrecio(filtrados[Number($(this).attr("data-index"))]);
  });

  $(document).on("click", ".btnAbrirPrecioProducto", function(event){
    event.stopPropagation();
    abrirModalPrecio(filtrados[Number($(this).closest(".tm-price-card").attr("data-index"))]);
  });

  $(document).on("input change", "#nuevoPrecioCompra, .nuevoPorcentaje", recalcularPrecioVenta);

  $(document).on("change", ".porcentaje", function(){
    if($(this).prop("checked")){
      recalcularPrecioVenta();
    }else{
      $("#nuevoPrecioVenta").prop("readonly", false);
    }
  });

  $("#buscarProductoPrecio").on("input", function(){
    clearTimeout(timerBusqueda);
    var query = ($(this).val() || "").toLowerCase().trim();
    timerBusqueda = setTimeout(function(){
      filtrados = query ? productos.filter(function(producto){
        return String(producto.search || "").indexOf(query) !== -1;
      }) : productos.slice();
      paginaActual = 1;
      renderProductos();
    }, 120);
  });

  $("#tmPrecioPorPagina").on("change", function(){
    porPagina = Number($(this).val()) || 24;
    paginaActual = 1;
    renderProductos();
  });

  $(document).on("click", ".tm-price-page-btn", function(){
    paginaActual = Number($(this).attr("data-page")) || 1;
    renderProductos();
  });

  renderProductos();
})();
</script>

<?php
  $eliminarProducto = new ControladorProductos();
  $eliminarProducto -> ctrEliminarProducto();
?>
