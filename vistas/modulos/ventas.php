<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "vendedor" && $_SESSION["rol"] != "cajero"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$perfilVentas = $_SESSION["perfil"];
$rolVentas = $_SESSION["rol"];
$vistaRolVentas = $_SESSION["vistaRolMenu"] ?? "";
$esAdminVentasReal = ($_SESSION["perfil"] == "Administrador");

if($_SESSION["perfil"] == "Administrador" && $vistaRolVentas != "" && $vistaRolVentas != "administrador"){
  $rolVentas = $vistaRolVentas;
  $perfilVentas = ($vistaRolVentas == "vendedor") ? "Vendedor" : "Especial";
}

if(isset($_GET["fechaInicial"])){
  $fechaInicial = $_GET["fechaInicial"];
  $fechaFinal = $_GET["fechaFinal"];
}else{
  $fechaInicial = null;
  $fechaFinal = null;
}

$ventas = ControladorVentas::ctrRangoFechasVentas($fechaInicial, $fechaFinal);
$ventas = is_array($ventas) ? $ventas : array();

$ventasPorCobrar = array_values(array_filter($ventas, function($venta){
  return ($venta["estado_pago"] ?? "pendiente") == "pendiente";
}));
$ventasPorEntregar = array_values(array_filter($ventas, function($venta){
  return ($venta["estado_pago"] ?? "") == "aprobado" && ($venta["estado_despacho"] ?? "") == "pendiente";
}));
$ventasCompletadas = array_values(array_filter($ventas, function($venta){
  return ($venta["estado_pago"] ?? "") == "aprobado" && ($venta["estado_despacho"] ?? "") == "entregado";
}));

function tmVentaEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmVentaMoney($valor){
  return "Bs ".number_format((float)($valor ?? 0), 2);
}

function tmVentaClase($venta){
  $estadoPago = $venta["estado_pago"] ?? "pendiente";
  $estadoDespacho = $venta["estado_despacho"] ?? "pendiente";

  if($estadoPago == "aprobado" && $estadoDespacho == "entregado"){
    return "completada";
  }
  if($estadoPago == "aprobado"){
    return "entrega";
  }
  return "cobro";
}

function tmVentaEstadoTexto($venta){
  $clase = tmVentaClase($venta);
  if($clase == "completada"){
    return "Completada";
  }
  if($clase == "entrega"){
    return "Por entregar";
  }
  return "Por cobrar";
}

function tmVentaProcesoTexto($venta){
  $clase = tmVentaClase($venta);
  if($clase == "completada"){
    return "Cobro y despacho completados";
  }
  if($clase == "entrega"){
    return "Caja cobro. Almacen debe entregar";
  }
  return "Cliente debe pasar por caja";
}

function tmVentaProductos($productosJson){
  $productos = json_decode($productosJson ?? "[]", true);
  return is_array($productos) ? $productos : array();
}

function tmVentaProductosHtml($productos){
  if(!is_array($productos) || count($productos) == 0){
    return '<div class="tm-sale-empty-line">Sin productos registrados.</div>';
  }

  $html = '<div class="tm-sale-products-list">';
  foreach($productos as $producto){
    $descripcion = tmVentaEsc($producto["descripcion"] ?? "Producto");
    $cantidad = (int)($producto["cantidad"] ?? 0);
    $precio = tmVentaMoney($producto["precio"] ?? 0);
    $total = tmVentaMoney($producto["total"] ?? 0);
    $html .= '<div class="tm-sale-product-row">
      <div>
        <strong>'.$descripcion.'</strong>
        <span>Cantidad: '.$cantidad.' | P. unitario: '.$precio.'</span>
      </div>
      <b>'.$total.'</b>
    </div>';
  }
  $html .= '</div>';
  return $html;
}

function tmVentaAccionesHtml($venta, $perfilVentas, $rolVentas, $esAdminVentasReal = false){
  $estadoPago = $venta["estado_pago"] ?? "pendiente";
  $estadoDespacho = $venta["estado_despacho"] ?? "pendiente";
  $codigo = tmVentaEsc($venta["codigo"] ?? "");
  $idVenta = (int)($venta["id"] ?? 0);

  $html = '<div class="tm-sale-actions">';
  $html .= '<button type="button" class="btn btn-default btnImprimirBoletaCaja" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'" title="Imprimir boleta para caja">
      <i class="fa fa-money"></i> Caja
    </button>';

  if($estadoPago == "aprobado"){
    $html .= '<button type="button" class="btn btn-info btnImprimirBoletaDespacho" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'" title="Imprimir boleta para almacen">
      <i class="fa fa-truck"></i> Despacho
    </button>';
  }

  if($estadoPago == "aprobado" && $estadoDespacho == "entregado"){
    $html .= '<button type="button" class="btn btn-success btnImprimirFactura" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'" title="Imprimir factura o nota de venta">
      <i class="fa fa-print"></i> Nota venta
    </button>
    <button type="button" class="btn btn-primary btnImprimirControlEntrega" idVenta="'.$idVenta.'" title="Imprimir control de entrega">
      <i class="fa fa-list-alt"></i> Control
    </button>';
  }

  if($esAdminVentasReal || $perfilVentas == "Administrador" || $rolVentas == "cajero"){
    $html .= '<a href="index.php?ruta=editar-venta&idVenta='.$idVenta.'" class="btn btn-warning btnEditarVenta" data-id-venta="'.$idVenta.'" data-codigo-venta="'.$codigo.'" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'" title="Editar venta" onclick="return window.tmAbrirEditarVenta ? window.tmAbrirEditarVenta(this,event) : true;">
      <i class="fa fa-pencil"></i> Editar
    </a>';
  }

  if($esAdminVentasReal || $perfilVentas == "Administrador"){
    $html .= '<button type="button" class="btn btn-danger btnEliminarVenta" idVenta="'.$idVenta.'" codigoVenta="'.$codigo.'" estadoVenta="'.tmVentaEsc(tmVentaEstadoTexto($venta)).'" title="Eliminar venta">
      <i class="fa fa-trash"></i> Eliminar
    </button>';
  }

  $html .= '</div>';
  return $html;
}

function tmVentaCliente($idCliente){
  $cliente = ControladorClientes::ctrMostrarClientes("id", $idCliente);
  return $cliente["nombre"] ?? "Sin cliente";
}

function tmVentaUsuario($idUsuario, $fallback = "Sin usuario"){
  if(empty($idUsuario)){
    return $fallback;
  }
  $usuario = ControladorUsuarios::ctrMostrarUsuarios("id", $idUsuario);
  return $usuario["nombre"] ?? $fallback;
}

function tmVentaRenderCards($ventas, $perfilVentas, $rolVentas, $esAdminVentasReal = false){
  if(count($ventas) == 0){
    echo '<div class="tm-sale-empty">
      <i class="fa fa-inbox"></i>
      <strong>No hay ventas en esta pestaña.</strong>
      <span>Cuando exista movimiento se mostrara aqui.</span>
    </div>';
    return;
  }

  foreach($ventas as $venta){
    $productos = tmVentaProductos($venta["productos"] ?? "[]");
    $cliente = tmVentaCliente($venta["id_cliente"] ?? null);
    $vendedor = tmVentaUsuario($venta["id_vendedor"] ?? null, "Sin vendedor");
    $cajero = tmVentaUsuario($venta["id_cajero"] ?? null, "Pendiente");
    $despachador = tmVentaUsuario($venta["id_despachador"] ?? null, "Pendiente");
    $clase = tmVentaClase($venta);
    $estado = tmVentaEstadoTexto($venta);
    $proceso = tmVentaProcesoTexto($venta);
    $codigo = $venta["codigo"] ?? "";
    $fecha = $venta["fecha"] ?? "";
    $fechaPago = $venta["fecha_pago"] ?? "-";
    $fechaDespacho = $venta["fecha_despacho"] ?? "-";
    $metodoPago = $venta["metodo_pago"] ?? "Pendiente";
    $total = tmVentaMoney($venta["total"] ?? 0);
    $descuento = tmVentaMoney($venta["descuento"] ?? 0);
    $textoBusqueda = strtolower($codigo." ".$cliente." ".$vendedor." ".$cajero." ".$estado." ".$proceso." ".$total);

    echo '<article class="tm-sale-card sale-'.$clase.'" tabindex="0"
      data-search="'.tmVentaEsc($textoBusqueda).'"
      data-codigo="'.tmVentaEsc($codigo).'"
      data-cliente="'.tmVentaEsc($cliente).'"
      data-vendedor="'.tmVentaEsc($vendedor).'"
      data-cajero="'.tmVentaEsc($cajero).'"
      data-despachador="'.tmVentaEsc($despachador).'"
      data-total="'.tmVentaEsc($total).'"
      data-descuento="'.tmVentaEsc($descuento).'"
      data-estado="'.tmVentaEsc($estado).'"
      data-proceso="'.tmVentaEsc($proceso).'"
      data-metodo="'.tmVentaEsc($metodoPago).'"
      data-fecha="'.tmVentaEsc($fecha).'"
      data-fecha-pago="'.tmVentaEsc($fechaPago).'"
      data-fecha-despacho="'.tmVentaEsc($fechaDespacho).'"
      data-productos="'.count($productos).'"
      data-clase="'.tmVentaEsc($clase).'">
      <div class="tm-sale-card-head">
        <span class="tm-sale-status">'.$estado.'</span>
        <b>#'.tmVentaEsc($codigo).'</b>
      </div>
      <h3>'.tmVentaEsc($cliente).'</h3>
      <p>'.tmVentaEsc($proceso).'</p>
      <div class="tm-sale-total">
        <strong>'.$total.'</strong>
        <span>Total venta</span>
      </div>
      <div class="tm-sale-mini">
        <div><span>Vendedor</span><b>'.tmVentaEsc($vendedor).'</b></div>
        <div><span>Productos</span><b>'.count($productos).'</b></div>
        <div><span>Fecha</span><b>'.tmVentaEsc($fecha ?: "-").'</b></div>
      </div>
      <div class="tm-sale-card-foot">
        <span><i class="fa fa-mouse-pointer"></i> Ver detalle y acciones</span>
        <i class="fa fa-chevron-right"></i>
      </div>
      '.($esAdminVentasReal ? '<div class="tm-sale-admin-actions">
        <button type="button" class="btn btn-danger btnEliminarVenta" idVenta="'.(int)$venta["id"].'" codigoVenta="'.tmVentaEsc($codigo).'" estadoVenta="'.tmVentaEsc($estado).'" title="Eliminar venta y revertir inventario">
          <i class="fa fa-trash"></i> Eliminar venta
        </button>
      </div>' : '').'
      <div class="tm-sale-products-template" style="display:none">'.tmVentaProductosHtml($productos).'</div>
      <div class="tm-sale-actions-template" style="display:none">'.tmVentaAccionesHtml($venta, $perfilVentas, $rolVentas, $esAdminVentasReal).'</div>
    </article>';
  }
}

$totalVentas = array_sum(array_map(function($venta){ return (float)($venta["total"] ?? 0); }, $ventas));
$totalPorCobrar = array_sum(array_map(function($venta){ return (float)($venta["total"] ?? 0); }, $ventasPorCobrar));
$totalPorEntregar = array_sum(array_map(function($venta){ return (float)($venta["total"] ?? 0); }, $ventasPorEntregar));
$totalCompletadas = array_sum(array_map(function($venta){ return (float)($venta["total"] ?? 0); }, $ventasCompletadas));

?>
<div class="content-wrapper tm-sales-wrapper">
  <style>
    .tm-sales-wrapper .content-header h1{
      font-weight:950;
      color:#17263a;
    }
    .tm-sales-shell{
      border:1px solid rgba(184,205,232,.70);
      border-radius:20px;
      background:rgba(255,255,255,.66);
      box-shadow:0 20px 46px rgba(15,23,42,.08);
      overflow:hidden;
    }
    .tm-sales-hero{
      position:relative;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:18px;
      padding:20px;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#1f78ad 58%,#20b7df);
      overflow:hidden;
    }
    .tm-sales-hero:after{
      content:"";
      position:absolute;
      right:-78px;
      top:-105px;
      width:250px;
      height:250px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-sales-hero h2{
      position:relative;
      z-index:1;
      margin:0;
      font-size:25px;
      font-weight:950;
    }
    .tm-sales-hero p{
      position:relative;
      z-index:1;
      margin:6px 0 0;
      color:rgba(255,255,255,.88);
      font-weight:750;
      max-width:760px;
    }
    .tm-sales-hero-actions{
      position:relative;
      z-index:1;
      display:flex;
      gap:9px;
      flex-wrap:wrap;
      justify-content:flex-end;
    }
    .tm-sales-hero-actions .btn{
      border-radius:10px;
      font-weight:900;
      padding:10px 13px;
      border:1px solid rgba(255,255,255,.32);
      box-shadow:0 12px 24px rgba(15,23,42,.16);
    }
    .tm-sales-kpis{
      display:grid;
      grid-template-columns:repeat(4, minmax(160px, 1fr));
      gap:12px;
      padding:14px;
      background:rgba(255,255,255,.54);
      border-bottom:1px solid rgba(184,205,232,.62);
    }
    .tm-sales-kpi{
      border:1px solid rgba(184,205,232,.66);
      border-radius:16px;
      background:rgba(255,255,255,.76);
      padding:13px;
      display:flex;
      align-items:center;
      gap:12px;
      min-width:0;
    }
    .tm-sales-kpi i{
      width:42px;
      height:42px;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:18px;
      background:linear-gradient(135deg,#1f6cad,#20b7df);
      box-shadow:0 12px 22px rgba(31,108,173,.18);
      flex:0 0 auto;
    }
    .tm-sales-kpi.kpi-warning i{background:linear-gradient(135deg,#c56a00,#f39c12);}
    .tm-sales-kpi.kpi-info i{background:linear-gradient(135deg,#008da5,#00c0ef);}
    .tm-sales-kpi.kpi-success i{background:linear-gradient(135deg,#008d4c,#00a65a);}
    .tm-sales-kpi span{
      display:block;
      color:#65778c;
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-sales-kpi strong{
      display:block;
      color:#203047;
      font-size:18px;
      font-weight:950;
      line-height:1.1;
      overflow-wrap:anywhere;
    }
    .tm-sales-body{padding:14px;}
    .tm-sales-toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:12px;
      flex-wrap:wrap;
    }
    .tm-sales-search{
      position:relative;
      width:min(520px, 100%);
      flex:1 1 320px;
    }
    .tm-sales-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#3c8dbc;
    }
    .tm-sales-search input{
      width:100%;
      height:43px;
      border:1px solid rgba(184,205,232,.86);
      border-radius:13px;
      padding:10px 14px 10px 38px;
      background:rgba(255,255,255,.86);
      color:#1f2d3d;
      font-weight:850;
      outline:0;
    }
    .tm-sales-counter{
      color:#60748b;
      font-weight:900;
      padding:8px 10px;
      border-radius:999px;
      background:rgba(232,243,252,.72);
    }
    .tm-sales-tabs{
      background:transparent;
      box-shadow:none;
      margin:0;
    }
    .tm-sales-tabs .nav-tabs{
      border:1px solid rgba(184,205,232,.64);
      border-radius:15px 15px 0 0;
      background:rgba(255,255,255,.60);
      padding:0 10px;
    }
    .tm-sales-tabs .nav-tabs>li>a{
      border:0;
      border-radius:0;
      color:#53677e;
      font-weight:900;
      padding:13px 14px;
    }
    .tm-sales-tabs .nav-tabs>li.active>a,
    .tm-sales-tabs .nav-tabs>li.active>a:hover,
    .tm-sales-tabs .nav-tabs>li.active>a:focus{
      border:0;
      border-bottom:3px solid #3c8dbc;
      color:#173b5d;
      background:transparent;
    }
    .tm-sales-tabs .tab-content{
      border:1px solid rgba(184,205,232,.64);
      border-top:0;
      border-radius:0 0 15px 15px;
      background:rgba(255,255,255,.48);
      padding:14px;
    }
    .tm-sales-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(218px, 1fr));
      gap:10px;
    }
    .tm-sale-card{
      position:relative;
      min-height:214px;
      border:1px solid rgba(184,205,232,.72);
      border-radius:14px;
      background:rgba(255,255,255,.84);
      padding:11px;
      display:flex;
      flex-direction:column;
      gap:8px;
      cursor:pointer;
      overflow:hidden;
      box-shadow:0 14px 30px rgba(15,23,42,.07);
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .tm-sale-card:before{
      content:"";
      position:absolute;
      inset:0 auto 0 0;
      width:5px;
      background:#f39c12;
    }
    .tm-sale-card.sale-entrega:before{background:#00a7d0;}
    .tm-sale-card.sale-completada:before{background:#00a65a;}
    .tm-sale-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 21px 42px rgba(15,23,42,.14);
    }
    .tm-sale-card-head{
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:flex-start;
      padding-left:3px;
    }
    .tm-sale-status{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-height:22px;
      padding:4px 8px;
      border-radius:999px;
      color:#fff;
      background:#f39c12;
      font-size:9px;
      font-weight:950;
      text-transform:uppercase;
      line-height:1.1;
    }
    .sale-entrega .tm-sale-status{background:#00a7d0;}
    .sale-completada .tm-sale-status{background:#00a65a;}
    .tm-sale-card-head b{
      color:#176b9b;
      font-weight:950;
      font-size:11px;
      white-space:nowrap;
    }
    .tm-sale-card h3{
      margin:0;
      color:#1f2d3d;
      font-size:14px;
      line-height:1.22;
      font-weight:950;
      overflow-wrap:anywhere;
    }
    .tm-sale-card p{
      margin:0;
      color:#60748b;
      font-size:11px;
      font-weight:800;
      line-height:1.3;
    }
    .tm-sale-total{
      border-radius:13px;
      padding:8px 10px;
      background:linear-gradient(135deg, rgba(60,141,188,.13), rgba(0,192,239,.08));
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      gap:8px;
    }
    .tm-sale-total strong{
      color:#0b4e78;
      font-size:18px;
      font-weight:950;
    }
    .tm-sale-total span{
      color:#65788d;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-sale-mini{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:6px;
      flex:1;
    }
    .tm-sale-mini div{
      border:1px solid rgba(184,205,232,.60);
      border-radius:10px;
      background:rgba(248,251,255,.80);
      padding:6px 7px;
      min-width:0;
    }
    .tm-sale-mini span{
      display:block;
      color:#718299;
      font-size:8.8px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-sale-mini b{
      display:block;
      margin-top:3px;
      color:#25364a;
      font-size:11px;
      line-height:1.2;
      overflow-wrap:anywhere;
    }
    .tm-sale-card-foot{
      display:flex;
      align-items:center;
      justify-content:space-between;
      color:#176b9b;
      font-size:10px;
      font-weight:950;
      border-top:1px dashed rgba(184,205,232,.78);
      padding-top:7px;
    }
    .tm-sale-admin-actions{
      padding:0 14px 14px;
      position:relative;
      z-index:2;
    }
    .tm-sale-admin-actions .btn{
      width:100%;
      border-radius:10px;
      padding:8px 12px;
      font-size:12px;
      font-weight:900;
      box-shadow:0 8px 16px rgba(221,75,57,.15);
    }
    .tm-sale-empty{
      grid-column:1 / -1;
      min-height:190px;
      border:1px dashed rgba(60,141,188,.38);
      border-radius:16px;
      background:rgba(255,255,255,.58);
      color:#6d7f93;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:8px;
      text-align:center;
      font-weight:850;
    }
    .tm-sale-empty i{
      color:#3c8dbc;
      font-size:34px;
    }
    .tm-sale-modal .modal-dialog{width:min(820px, calc(100vw - 36px));}
    .tm-sale-modal .modal-content{
      border:0;
      border-radius:20px;
      overflow:hidden;
      box-shadow:0 30px 76px rgba(15,23,42,.30);
    }
    .tm-sale-modal .modal-header{
      position:relative;
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#f39c12,#d98200);
      padding:14px 18px;
    }
    .tm-sale-modal .modal-header.sale-entrega{background:linear-gradient(135deg,#00a7d0,#087da3);}
    .tm-sale-modal .modal-header.sale-completada{background:linear-gradient(135deg,#00a65a,#087a46);}
    .tm-sale-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-48px;
      top:-72px;
      width:150px;
      height:150px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-sale-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.9;
      text-shadow:none;
      font-size:24px;
    }
    .tm-sale-modal-title{
      position:relative;
      z-index:1;
      display:flex;
      align-items:center;
      gap:10px;
    }
    .tm-sale-modal-icon{
      width:40px;
      height:40px;
      border-radius:13px;
      background:rgba(255,255,255,.18);
      border:1px solid rgba(255,255,255,.28);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:18px;
      flex:0 0 auto;
    }
    .tm-sale-modal-title span{
      display:block;
      margin-bottom:3px;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
      color:rgba(255,255,255,.90);
    }
    .tm-sale-modal-title h4{
      margin:0;
      font-size:20px;
      font-weight:950;
    }
    .tm-sale-modal .modal-body{
      background:#f5f8fc;
      padding:13px;
    }
    .tm-sale-summary{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:10px;
      margin-bottom:14px;
    }
    .tm-sale-summary div{
      border:1px solid rgba(184,205,232,.74);
      border-radius:13px;
      background:#fff;
      padding:10px 12px;
      min-width:0;
    }
    .tm-sale-summary span{
      display:block;
      color:#728299;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-sale-summary strong{
      display:block;
      margin-top:4px;
      color:#203047;
      font-size:13px;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .tm-sale-timeline{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:10px;
      margin-bottom:14px;
    }
    .tm-sale-step{
      border:1px solid rgba(184,205,232,.74);
      border-radius:13px;
      background:#fff;
      padding:11px;
      text-align:center;
      color:#8a9aa6;
      font-weight:900;
    }
    .tm-sale-step i{
      display:block;
      font-size:20px;
      margin-bottom:5px;
    }
    .tm-sale-step.active{
      color:#165d8b;
      border-color:#b9d9ef;
      background:linear-gradient(180deg,#f6fbff,#eaf6ff);
    }
    .tm-sale-section{
      border:1px solid rgba(184,205,232,.74);
      border-radius:15px;
      background:#fff;
      padding:12px;
      margin-bottom:12px;
    }
    .tm-sale-section h4{
      margin:0 0 10px;
      color:#203047;
      font-size:15px;
      font-weight:950;
    }
    .tm-sale-section h4 i{
      color:#3c8dbc;
      margin-right:6px;
    }
    .tm-sale-products-list{
      display:grid;
      gap:8px;
    }
    .tm-sale-product-row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      border:1px solid rgba(184,205,232,.60);
      border-radius:12px;
      padding:9px 10px;
      background:rgba(248,251,255,.86);
    }
    .tm-sale-product-row strong{
      color:#23344a;
      font-size:13px;
      line-height:1.25;
    }
    .tm-sale-product-row span{
      display:block;
      color:#718299;
      font-size:11px;
      font-weight:800;
      margin-top:2px;
    }
    .tm-sale-product-row b{
      color:#0b4e78;
      white-space:nowrap;
      border-radius:8px;
      background:#e8f3fc;
      padding:6px 8px;
    }
    .tm-sale-actions{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
    }
    .tm-sale-actions .btn{
      border-radius:9px;
      font-weight:900;
      padding:8px 11px;
      white-space:normal;
      line-height:1.12;
    }
    .tm-edit-sale-modal .modal-dialog{width:min(620px, calc(100vw - 36px));}
    .tm-edit-sale-modal .modal-content{
      border:0;
      border-radius:20px;
      overflow:hidden;
      box-shadow:0 30px 78px rgba(15,23,42,.30);
      background:#f6f9fc;
    }
    .tm-edit-sale-modal .modal-header{
      position:relative;
      border:0;
      padding:17px 20px;
      color:#fff;
      background:linear-gradient(135deg,#d98200,#f39c12);
      overflow:hidden;
    }
    .tm-edit-sale-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-48px;
      top:-76px;
      width:160px;
      height:160px;
      border-radius:50%;
      background:rgba(255,255,255,.16);
    }
    .tm-edit-sale-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.92;
      text-shadow:none;
    }
    .tm-edit-title{
      position:relative;
      z-index:1;
      display:flex;
      align-items:center;
      gap:12px;
    }
    .tm-edit-title i{
      width:44px;
      height:44px;
      border-radius:15px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.18);
      border:1px solid rgba(255,255,255,.28);
      font-size:18px;
      flex:0 0 auto;
    }
    .tm-edit-title span{
      display:block;
      color:rgba(255,255,255,.88);
      font-size:11px;
      font-weight:900;
      text-transform:uppercase;
      margin-bottom:3px;
    }
    .tm-edit-title h4{
      margin:0;
      font-size:21px;
      font-weight:950;
    }
    .tm-edit-sale-modal .modal-body{padding:15px;background:#f6f9fc;}
    .tm-edit-grid{
      display:grid;
      grid-template-columns:repeat(2, 1fr);
      gap:10px;
      margin-bottom:12px;
    }
    .tm-edit-grid div,
    .tm-edit-warning{
      border:1px solid rgba(184,205,232,.78);
      border-radius:14px;
      background:#fff;
      padding:11px 12px;
      min-width:0;
    }
    .tm-edit-grid span{
      display:block;
      color:#728299;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-edit-grid strong{
      display:block;
      margin-top:4px;
      color:#203047;
      font-size:14px;
      line-height:1.25;
      overflow-wrap:anywhere;
    }
    .tm-edit-warning{
      display:flex;
      gap:10px;
      align-items:flex-start;
      background:linear-gradient(135deg,#fff8eb,#fff);
      border-color:#f3d69e;
      color:#6f4a00;
      font-weight:800;
      margin-bottom:12px;
    }
    .tm-edit-warning i{
      color:#f39c12;
      font-size:18px;
      margin-top:2px;
    }
    .tm-edit-actions{
      display:flex;
      justify-content:flex-end;
      gap:9px;
      flex-wrap:wrap;
    }
    .tm-edit-actions .btn{
      border-radius:10px;
      font-weight:900;
      padding:10px 14px;
    }
    .tm-edit-inline-panel{
      display:none;
      border:1px solid rgba(243,156,18,.28);
      border-radius:16px;
      background:linear-gradient(135deg,rgba(255,248,235,.98),rgba(255,255,255,.96));
      box-shadow:0 16px 32px rgba(217,130,0,.10);
      padding:12px;
      margin-bottom:14px;
    }
    .tm-edit-inline-head{
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom:10px;
    }
    .tm-edit-inline-head i{
      width:38px;
      height:38px;
      border-radius:13px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      background:linear-gradient(135deg,#d98200,#f39c12);
      box-shadow:0 10px 20px rgba(217,130,0,.18);
      flex:0 0 auto;
    }
    .tm-edit-inline-head span{
      display:block;
      color:#8a5b00;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-edit-inline-head strong{
      display:block;
      color:#1d2c42;
      font-size:18px;
      font-weight:950;
      line-height:1.1;
    }
    .tm-edit-inline-panel .tm-edit-grid div{
      background:rgba(255,255,255,.82);
    }
    body.tm-dark-mode .tm-sales-shell,
    body.dark-mode .tm-sales-shell,
    body.tm-dark-mode .tm-sale-card,
    body.dark-mode .tm-sale-card{
      background:rgba(15,23,42,.72);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-sales-kpis,
    body.dark-mode .tm-sales-kpis,
    body.tm-dark-mode .tm-sales-tabs .nav-tabs,
    body.dark-mode .tm-sales-tabs .nav-tabs,
    body.tm-dark-mode .tm-sales-tabs .tab-content,
    body.dark-mode .tm-sales-tabs .tab-content{
      background:rgba(15,23,42,.42);
      border-color:rgba(99,135,184,.35);
    }
    body.tm-dark-mode .tm-sales-kpi,
    body.tm-dark-mode .tm-sale-mini div,
    body.dark-mode .tm-sales-kpi,
    body.dark-mode .tm-sale-mini div{
      background:rgba(255,255,255,.07);
      border-color:rgba(255,255,255,.12);
    }
    body.tm-dark-mode .tm-sale-card h3,
    body.tm-dark-mode .tm-sales-kpi strong,
    body.tm-dark-mode .tm-sale-mini b,
    body.dark-mode .tm-sale-card h3,
    body.dark-mode .tm-sales-kpi strong,
    body.dark-mode .tm-sale-mini b{color:#f8fbff;}
    @media(max-width:991px){
      .tm-sales-kpis{grid-template-columns:repeat(2, minmax(160px, 1fr));}
      .tm-sale-summary{grid-template-columns:repeat(2, 1fr);}
    }
    @media(max-width:767px){
      .tm-sales-hero,
      .tm-sales-toolbar{flex-direction:column;align-items:stretch;}
      .tm-sales-hero-actions{justify-content:flex-start;}
      .tm-sales-kpis{grid-template-columns:1fr;}
      .tm-sales-grid{grid-template-columns:1fr;}
      .tm-sale-summary,
      .tm-sale-timeline,
      .tm-edit-grid{grid-template-columns:1fr;}
    }
  </style>

  <section class="content-header">
    <h1>Administrar ventas</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Administrar ventas</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-sales-shell">
      <div class="tm-sales-hero">
        <div>
          <h2>Control de ventas</h2>
          <p>Seguimiento completo desde la venta pendiente de cobro hasta la entrega final al cliente.</p>
        </div>
        <div class="tm-sales-hero-actions">
          <?php if($esAdminVentasReal || $perfilVentas == "Administrador" || $rolVentas == "vendedor"): ?>
            <a href="crear-venta" class="btn btn-primary"><i class="fa fa-plus"></i> Nueva venta</a>
          <?php endif; ?>
          <button type="button" class="btn btn-default" id="daterange-btn">
            <span><i class="fa fa-calendar"></i> Rango de fecha</span>
            <i class="fa fa-caret-down"></i>
          </button>
        </div>
      </div>

      <div class="tm-sales-kpis">
        <div class="tm-sales-kpi">
          <i class="fa fa-line-chart"></i>
          <div><span>Total filtrado</span><strong><?php echo tmVentaMoney($totalVentas); ?></strong></div>
        </div>
        <div class="tm-sales-kpi kpi-warning">
          <i class="fa fa-clock-o"></i>
          <div><span>Por cobrar</span><strong><?php echo count($ventasPorCobrar); ?> / <?php echo tmVentaMoney($totalPorCobrar); ?></strong></div>
        </div>
        <div class="tm-sales-kpi kpi-info">
          <i class="fa fa-truck"></i>
          <div><span>Por entregar</span><strong><?php echo count($ventasPorEntregar); ?> / <?php echo tmVentaMoney($totalPorEntregar); ?></strong></div>
        </div>
        <div class="tm-sales-kpi kpi-success">
          <i class="fa fa-check"></i>
          <div><span>Completadas</span><strong><?php echo count($ventasCompletadas); ?> / <?php echo tmVentaMoney($totalCompletadas); ?></strong></div>
        </div>
      </div>

      <div class="tm-sales-body">
        <div class="tm-sales-toolbar">
          <div class="tm-sales-search">
            <i class="fa fa-search"></i>
            <input type="text" id="buscarVentasCards" placeholder="Buscar por codigo, cliente, vendedor, cajero, estado o total">
          </div>
          <div class="tm-sales-counter" id="contadorVentasCards"><?php echo count($ventas); ?> venta(s)</div>
        </div>

        <div class="nav-tabs-custom tm-sales-tabs">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#tabVentasPorCobrar" data-toggle="tab">Por cobrar <span class="badge bg-yellow"><?php echo count($ventasPorCobrar); ?></span></a></li>
            <li><a href="#tabVentasPorEntregar" data-toggle="tab">Por entregar <span class="badge bg-aqua"><?php echo count($ventasPorEntregar); ?></span></a></li>
            <li><a href="#tabVentasCompletadas" data-toggle="tab">Completadas <span class="badge bg-green"><?php echo count($ventasCompletadas); ?></span></a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tabVentasPorCobrar">
              <div class="tm-sales-grid"><?php tmVentaRenderCards($ventasPorCobrar, $perfilVentas, $rolVentas, $esAdminVentasReal); ?></div>
            </div>
            <div class="tab-pane" id="tabVentasPorEntregar">
              <div class="tm-sales-grid"><?php tmVentaRenderCards($ventasPorEntregar, $perfilVentas, $rolVentas, $esAdminVentasReal); ?></div>
            </div>
            <div class="tab-pane" id="tabVentasCompletadas">
              <div class="tm-sales-grid"><?php tmVentaRenderCards($ventasCompletadas, $perfilVentas, $rolVentas, $esAdminVentasReal); ?></div>
            </div>
          </div>
        </div>

        <?php
          $eliminarVenta = new ControladorVentas();
          $eliminarVenta -> ctrEliminarVenta();
        ?>
      </div>
    </div>
  </section>
</div>

<div id="modalEditarVentaProceso" class="modal fade tm-edit-sale-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
        <div class="tm-edit-title">
          <i class="fa fa-pencil"></i>
          <div>
            <span>Edicion de venta</span>
            <h4>Venta <span id="modalEditarVentaCodigo">#</span></h4>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div class="tm-edit-grid">
          <div><span>Cliente</span><strong id="modalEditarVentaCliente">-</strong></div>
          <div><span>Total actual</span><strong id="modalEditarVentaTotal">Bs 0.00</strong></div>
          <div><span>Estado</span><strong id="modalEditarVentaEstado">-</strong></div>
          <div><span>Proceso</span><strong id="modalEditarVentaProcesoTexto">-</strong></div>
        </div>
        <div class="tm-edit-warning">
          <i class="fa fa-exclamation-triangle"></i>
          <div>
            Revise bien antes de modificar. Si cambia productos, cantidades o datos de la venta, la boleta y el seguimiento se actualizan desde la pantalla de edicion.
          </div>
        </div>
        <div class="tm-edit-actions">
          <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancelar
          </button>
          <a href="#" class="btn btn-warning" id="btnAbrirEdicionVenta">
            <i class="fa fa-pencil"></i> Abrir edicion
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modalDetalleVentaProceso" class="modal fade tm-sale-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" id="modalVentaHeader">
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
        <div class="tm-sale-modal-title">
          <div class="tm-sale-modal-icon"><i class="fa fa-shopping-cart"></i></div>
          <div>
            <span id="modalVentaEstado">Estado</span>
            <h4>Venta <span id="modalVentaCodigo"></span></h4>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div class="tm-sale-summary">
          <div><span>Cliente</span><strong id="modalVentaCliente"></strong></div>
          <div><span>Vendedor</span><strong id="modalVentaVendedor"></strong></div>
          <div><span>Cajero</span><strong id="modalVentaCajero"></strong></div>
          <div><span>Despachador</span><strong id="modalVentaDespachador"></strong></div>
          <div><span>Metodo de pago</span><strong id="modalVentaMetodo"></strong></div>
          <div><span>Fecha venta</span><strong id="modalVentaFecha"></strong></div>
          <div><span>Fecha cobro</span><strong id="modalVentaFechaPago"></strong></div>
          <div><span>Fecha despacho</span><strong id="modalVentaFechaDespacho"></strong></div>
          <div><span>Descuento</span><strong id="modalVentaDescuento"></strong></div>
          <div><span>Total</span><strong id="modalVentaTotal"></strong></div>
          <div><span>Productos</span><strong id="modalVentaProductosCantidad"></strong></div>
          <div><span>Proceso</span><strong id="modalVentaProceso"></strong></div>
        </div>

        <div class="tm-sale-timeline">
          <div class="tm-sale-step active"><i class="fa fa-file-text-o"></i><span>Venta creada</span></div>
          <div class="tm-sale-step" id="modalVentaStepCobro"><i class="fa fa-money"></i><span>Cobro en caja</span></div>
          <div class="tm-sale-step" id="modalVentaStepEntrega"><i class="fa fa-truck"></i><span>Entrega almacen</span></div>
        </div>

        <div class="tm-sale-section">
          <h4><i class="fa fa-cubes"></i> Productos vendidos</h4>
          <div id="modalVentaProductos"></div>
        </div>

        <div class="tm-sale-section">
          <h4><i class="fa fa-bolt"></i> Acciones disponibles</h4>
          <div id="modalEditarVentaInline" class="tm-edit-inline-panel">
            <div class="tm-edit-inline-head">
              <i class="fa fa-pencil"></i>
              <div>
                <span>Edicion de venta</span>
                <strong>Venta <span id="modalEditarInlineCodigo">#</span></strong>
              </div>
            </div>
            <div class="tm-edit-grid">
              <div><span>Cliente</span><strong id="modalEditarInlineCliente">-</strong></div>
              <div><span>Total actual</span><strong id="modalEditarInlineTotal">Bs 0.00</strong></div>
              <div><span>Estado</span><strong id="modalEditarInlineEstado">-</strong></div>
              <div><span>Proceso</span><strong id="modalEditarInlineProcesoTexto">-</strong></div>
            </div>
            <div class="tm-edit-warning">
              <i class="fa fa-exclamation-triangle"></i>
              <div>Confirme la venta que desea modificar. Al abrir edicion podra cambiar datos y productos desde la pantalla completa.</div>
            </div>
            <div class="tm-edit-actions">
              <button type="button" class="btn btn-default" id="btnCancelarEdicionInline">
                <i class="fa fa-times"></i> Cancelar
              </button>
              <a href="#" class="btn btn-warning" id="btnAbrirEdicionVentaInline">
                <i class="fa fa-pencil"></i> Abrir edicion
              </a>
            </div>
          </div>
          <div id="modalVentaAcciones"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  function normalizarTexto(valor){
    return (valor || "").toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
  }

  function escapeHtml(valor){
    return $("<div>").text(valor || "").html();
  }

  function filtrarVentasCards(){
    var termino = normalizarTexto($("#buscarVentasCards").val());
    var visiblesTotal = 0;

    $(".tm-sales-grid").each(function(){
      var grid = $(this);
      var visiblesGrid = 0;
      grid.find(".busqueda-vacia").remove();

      grid.find(".tm-sale-card").each(function(){
        var card = $(this);
        var coincide = !termino || normalizarTexto(card.data("search") || card.text()).indexOf(termino) !== -1;
        card.toggle(coincide);
        if(coincide){
          visiblesGrid++;
          visiblesTotal++;
        }
      });

      if(visiblesGrid === 0 && grid.find(".tm-sale-card").length > 0){
        grid.append('<div class="tm-sale-empty busqueda-vacia"><i class="fa fa-search"></i><strong>No hay ventas que coincidan.</strong><span>Prueba con otro codigo, cliente o estado.</span></div>');
      }
    });

    $("#contadorVentasCards").text(visiblesTotal + " venta(s) encontradas");
  }

  $(document).on("click keypress", ".tm-sale-card", function(event){
    if(event.type === "keypress" && event.which !== 13 && event.which !== 32){
      return;
    }
    if($(event.target).closest("button, a, input").length){
      return;
    }

    var card = $(this);
    var clase = card.data("clase") || "cobro";

    $("#modalVentaHeader").removeClass("sale-cobro sale-entrega sale-completada").addClass("sale-" + clase);
    $("#modalVentaEstado").text(card.data("estado") || "");
    $("#modalVentaCodigo").text("#" + (card.data("codigo") || ""));
    $("#modalVentaCliente").text(card.data("cliente") || "-");
    $("#modalVentaVendedor").text(card.data("vendedor") || "-");
    $("#modalVentaCajero").text(card.data("cajero") || "-");
    $("#modalVentaDespachador").text(card.data("despachador") || "-");
    $("#modalVentaMetodo").text(card.data("metodo") || "-");
    $("#modalVentaFecha").text(card.data("fecha") || "-");
    $("#modalVentaFechaPago").text(card.data("fechaPago") || "-");
    $("#modalVentaFechaDespacho").text(card.data("fechaDespacho") || "-");
    $("#modalVentaDescuento").text(card.data("descuento") || "Bs 0.00");
    $("#modalVentaTotal").text(card.data("total") || "Bs 0.00");
    $("#modalVentaProductosCantidad").text((card.data("productos") || 0) + " producto(s)");
    $("#modalVentaProceso").text(card.data("proceso") || "-");
    $("#modalVentaProductos").html(card.find(".tm-sale-products-template").html());
    $("#modalVentaAcciones").html(card.find(".tm-sale-actions-template").html());
    $("#modalEditarVentaInline").hide();

    $("#modalVentaStepCobro").toggleClass("active", clase === "entrega" || clase === "completada");
    $("#modalVentaStepEntrega").toggleClass("active", clase === "completada");
    $("#modalDetalleVentaProceso").modal("show");
  });

  function abrirModalEditarVentaDesdeBoton(boton){
    var idVenta = boton.attr("data-id-venta") || boton.attr("idVenta") || boton.attr("idventa") || "";
    var codigoVenta = boton.attr("data-codigo-venta") || boton.attr("codigoVenta") || boton.attr("codigoventa") || ($("#modalVentaCodigo").text() || "").replace("#", "").trim();
    var urlEdicion = boton.attr("href") || ("index.php?ruta=editar-venta&idVenta=" + encodeURIComponent(idVenta));

    if(!idVenta){
      return;
    }

    $("#modalEditarVentaCodigo").text("#" + (codigoVenta || idVenta));
    $("#modalEditarVentaCliente").text($("#modalVentaCliente").text() || "-");
    $("#modalEditarVentaTotal").text($("#modalVentaTotal").text() || "Bs 0.00");
    $("#modalEditarVentaEstado").text($("#modalVentaEstado").text() || "-");
    $("#modalEditarVentaProcesoTexto").text($("#modalVentaProceso").text() || "-");
    $("#btnAbrirEdicionVenta").attr("href", urlEdicion);
    $("#modalEditarInlineCodigo").text("#" + (codigoVenta || idVenta));
    $("#modalEditarInlineCliente").text($("#modalVentaCliente").text() || "-");
    $("#modalEditarInlineTotal").text($("#modalVentaTotal").text() || "Bs 0.00");
    $("#modalEditarInlineEstado").text($("#modalVentaEstado").text() || "-");
    $("#modalEditarInlineProcesoTexto").text($("#modalVentaProceso").text() || "-");
    $("#btnAbrirEdicionVentaInline").attr("href", urlEdicion);

    var panel = $("#modalEditarVentaInline");
    if(panel.length){
      panel.stop(true, true).slideDown(160);
      panel[0].scrollIntoView({behavior:"smooth", block:"nearest"});
      return;
    }

    window.location = urlEdicion;
  }

  window.tmAbrirEditarVenta = function(elemento, event){
    if(event){
      event.preventDefault();
      event.stopPropagation();
    }
    abrirModalEditarVentaDesdeBoton($(elemento));
    return false;
  };

  $(document).off("click.ventasEditarModal", ".btnEditarVenta").on("click.ventasEditarModal", ".btnEditarVenta", function(event){
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();
    abrirModalEditarVentaDesdeBoton($(this));
    return false;
  });

  $(document).off("click.ventasCancelarEdicionInline", "#btnCancelarEdicionInline").on("click.ventasCancelarEdicionInline", "#btnCancelarEdicionInline", function(event){
    event.preventDefault();
    $("#modalEditarVentaInline").slideUp(140);
  });

  $(document).off("click.ventasAbrirEdicion", "#btnAbrirEdicionVenta, #btnAbrirEdicionVentaInline").on("click.ventasAbrirEdicion", "#btnAbrirEdicionVenta, #btnAbrirEdicionVentaInline", function(event){
    var urlEdicion = $(this).attr("href");

    if(!urlEdicion || urlEdicion === "#"){
      event.preventDefault();
      return false;
    }

    event.preventDefault();
    window.location = urlEdicion;
    return false;
  });

  $(document).on("input", "#buscarVentasCards", filtrarVentasCards);
  $(document).on("shown.bs.tab", 'a[data-toggle="tab"]', filtrarVentasCards);
  $(function(){
    $('[title]').tooltip({container:'body'});
  });
})();
</script>
