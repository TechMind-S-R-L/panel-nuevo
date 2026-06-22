<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero"){
  echo '<script>window.location = "ventas";</script>';
  return;
}

$item = "id";
$valor = $_GET["idVenta"] ?? 0;
$venta = ControladorVentas::ctrMostrarVentas($item, $valor);

if(!$venta){
  echo '<script>window.location = "ventas";</script>';
  return;
}

$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $venta["id_vendedor"]);
$cliente = ControladorClientes::ctrMostrarClientes("id", $venta["id_cliente"]);
$clientesVenta = ControladorClientes::ctrMostrarClientes(null, null);
$listaProducto = json_decode($venta["productos"] ?? "[]", true);
$listaProducto = is_array($listaProducto) ? $listaProducto : array();
$netoVenta = (float)($venta["neto"] ?? 0);
$descuentoVenta = (float)($venta["descuento"] ?? 0);
$porcentajeImpuesto = $netoVenta > 0 ? ($descuentoVenta * 100 / $netoVenta) : 0;
$metodoActual = $venta["metodo_pago"] ?? "Pendiente de pago";

?>
<div class="content-wrapper editar-venta-page">
  <style>
    .editar-venta-page{
      background:transparent !important;
    }
    .editar-venta-hero{
      border:1px solid rgba(184,205,232,.72);
      border-radius:20px;
      background:linear-gradient(135deg,rgba(16,43,59,.96),rgba(23,107,155,.90));
      color:#fff;
      padding:18px 20px;
      margin-bottom:16px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      box-shadow:0 18px 40px rgba(15,23,42,.12);
      overflow:hidden;
      position:relative;
    }
    .editar-venta-hero:after{
      content:"";
      position:absolute;
      right:-48px;
      top:-70px;
      width:180px;
      height:180px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .editar-venta-hero h2{
      margin:0 0 5px;
      font-size:24px;
      font-weight:950;
    }
    .editar-venta-hero p{
      margin:0;
      color:rgba(255,255,255,.78);
      font-weight:700;
    }
    .editar-venta-code{
      position:relative;
      z-index:1;
      min-width:170px;
      border:1px solid rgba(255,255,255,.28);
      border-radius:16px;
      background:rgba(255,255,255,.14);
      padding:12px 16px;
      text-align:center;
      backdrop-filter:blur(8px);
    }
    .editar-venta-code span{
      display:block;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
      color:rgba(255,255,255,.74);
    }
    .editar-venta-code strong{
      display:block;
      margin-top:3px;
      font-size:28px;
      line-height:1;
      font-weight:950;
    }
    .editar-venta-layout{
      display:grid;
      grid-template-columns:minmax(340px, .92fr) minmax(460px, 1.45fr);
      gap:16px;
      align-items:start;
    }
    .editar-card{
      border:1px solid rgba(184,205,232,.72);
      border-radius:18px;
      background:rgba(255,255,255,.72);
      box-shadow:0 18px 40px rgba(15,23,42,.08);
      overflow:hidden;
      margin-bottom:16px;
    }
    .editar-card-header{
      padding:14px 16px;
      border-bottom:1px solid rgba(184,205,232,.55);
      background:rgba(248,252,255,.78);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
    }
    .editar-card-header h3{
      margin:0;
      color:#17263a;
      font-size:17px;
      font-weight:950;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .editar-card-header h3 i{
      color:#1f8ec2;
    }
    .editar-card-body{
      padding:15px;
    }
    .editar-fields{
      display:grid;
      grid-template-columns:1fr;
      gap:12px;
    }
    .editar-field label{
      display:block;
      color:#42556b;
      font-size:12px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:6px;
    }
    .editar-field .form-control{
      min-height:42px;
      border:1px solid #d7e3ee;
      border-radius:12px;
      box-shadow:none;
      font-weight:800;
    }
    .editar-inline-client{
      display:grid;
      grid-template-columns:minmax(0,1fr) auto;
      gap:8px;
      align-items:center;
    }
    .editar-inline-client .btn{
      height:42px;
      border-radius:12px;
      font-weight:900;
      white-space:nowrap;
    }
    .editar-note{
      margin-top:12px;
      border-left:4px solid #f39c12;
      border-radius:12px;
      background:rgba(255,248,235,.92);
      color:#6f4a00;
      padding:11px 12px;
      font-weight:800;
      line-height:1.35;
    }
    .editar-cart{
      min-height:120px;
      border:1px dashed #c6d4df;
      background:rgba(248,251,253,.74);
      border-radius:14px;
      padding:8px;
    }
    .editar-cart:empty:before{
      content:"Agregue productos desde las tarjetas del catalogo.";
      display:block;
      padding:22px;
      text-align:center;
      color:#6f8190;
      font-weight:800;
    }
    .editar-cart.nuevoProducto .row{
      display:grid;
      grid-template-columns:minmax(0,1fr) minmax(0,1fr);
      gap:8px;
      margin:0 0 8px !important;
      padding:10px !important;
      border:1px solid #dfe8ef;
      border-radius:13px;
      background:#fff;
      box-shadow:0 8px 18px rgba(15,23,42,.04);
    }
    .editar-cart.nuevoProducto .row>[class*="col-"]{
      float:none !important;
      width:100% !important;
      max-width:100% !important;
      padding:0 !important;
      min-width:0;
    }
    .editar-cart.nuevoProducto .row>[class*="col-"]:first-child{
      grid-column:1 / -1;
    }
    .editar-cart.nuevoProducto .ingresoCantidad,
    .editar-cart.nuevoProducto .ingresoPrecio{
      position:relative;
      padding-top:18px !important;
    }
    .editar-cart.nuevoProducto .ingresoCantidad:before,
    .editar-cart.nuevoProducto .ingresoPrecio:before{
      position:absolute;
      top:0;
      left:0;
      color:#60717f;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      letter-spacing:.02em;
    }
    .editar-cart.nuevoProducto .ingresoCantidad:before{
      content:"Cantidad";
    }
    .editar-cart.nuevoProducto .ingresoPrecio:before{
      content:"Precio";
    }
    .editar-cart.nuevoProducto .row:last-child{
      margin-bottom:0 !important;
    }
    .nuevoProducto .input-group-addon{
      border-color:#d7e3ee;
      background:#f4f8fb;
      border-radius:10px 0 0 10px;
    }
    .nuevoProducto .form-control{
      border-color:#d7e3ee;
      box-shadow:none;
      min-height:38px;
      font-weight:800;
    }
    .editar-cart .nuevaDescripcionProducto{
      height:auto;
      min-height:40px;
      white-space:normal;
      overflow:visible;
      text-overflow:clip;
      padding-top:9px;
      padding-bottom:9px;
      line-height:1.25;
    }
    .editar-cart .nuevaCantidadProducto,
    .editar-cart .nuevoPrecioProducto{
      min-width:0;
      text-align:center;
      padding-left:4px;
      padding-right:4px;
      font-size:14px;
    }
    .editar-cart .input-group{
      width:100%;
    }
    .nuevoProducto .quitarProducto{
      border-radius:8px;
      width:28px;
      height:28px;
      padding:0;
      display:inline-flex;
      align-items:center;
      justify-content:center;
    }
    .controlCantidad .btn{
      min-width:34px;
      min-height:38px;
    }
    .editar-summary{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
    }
    .editar-summary-box{
      border:1px solid #dfe8ef;
      border-radius:14px;
      background:#fff;
      padding:12px;
    }
    .editar-summary-box label{
      display:block;
      color:#60717f;
      font-size:12px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:7px;
    }
    .editar-summary-box .input-group-addon{
      border-radius:10px 0 0 10px;
      background:#f4f8fb;
    }
    .editar-summary-box .form-control{
      min-height:44px;
      border-color:#d7e3ee;
      box-shadow:none;
      font-weight:900;
    }
    .editar-total input{
      color:#0f5f8d;
      font-size:23px;
      font-weight:950;
    }
    .editar-submit{
      display:flex;
      gap:10px;
      justify-content:flex-end;
      flex-wrap:wrap;
      border-top:1px solid rgba(184,205,232,.55);
      padding:14px 15px;
      background:rgba(248,252,255,.72);
    }
    .editar-submit .btn{
      border-radius:12px;
      font-weight:950;
      padding:11px 16px;
    }
    .venta-product-toolbar{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }
    .venta-product-search{
      position:relative;
      flex:1 1 280px;
    }
    .venta-product-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#6f8190;
    }
    .venta-product-search input{
      height:42px;
      border-radius:12px;
      padding-left:38px;
      border-color:#d7e3ee;
      box-shadow:none;
    }
    .venta-product-size{
      display:flex;
      align-items:center;
      gap:8px;
      color:#60717f;
      font-weight:900;
    }
    .venta-product-size select{
      width:98px;
      height:42px;
      border-radius:12px;
    }
    .venta-product-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(150px, 1fr));
      gap:10px;
      max-height:620px;
      overflow-y:auto;
      padding:4px 6px 4px 2px;
    }
    .venta-product-card{
      border:1px solid #dbe5ec;
      border-radius:14px;
      background:rgba(255,255,255,.86);
      padding:10px;
      min-height:220px;
      display:flex;
      flex-direction:column;
      box-shadow:0 10px 22px rgba(22,49,64,.07);
      cursor:pointer;
      transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;
    }
    .venta-product-card:hover{
      border-color:#2f9ccc;
      box-shadow:0 14px 28px rgba(22,49,64,.13);
      transform:translateY(-2px);
    }
    .venta-product-img{
      height:76px;
      border:1px solid #edf2f6;
      border-radius:12px;
      background:#f7fafc;
      display:flex;
      align-items:center;
      justify-content:center;
      overflow:hidden;
      margin-bottom:9px;
    }
    .venta-product-img img,
    .venta-product-img .img-producto-venta{
      width:100%;
      max-width:105px;
      height:66px;
      object-fit:contain;
      display:block;
      margin:0 auto;
    }
    .venta-product-code{
      display:block;
      color:#176b9b;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:5px;
      overflow-wrap:anywhere;
    }
    .venta-product-card h4{
      margin:0 0 7px;
      color:#1f2d3d;
      font-size:12px;
      font-weight:950;
      line-height:1.32;
      min-height:47px;
      overflow-wrap:anywhere;
    }
    .venta-card-price{
      display:inline-flex;
      min-height:24px;
      padding:4px 8px;
      border-radius:999px;
      background:#eaf5fb;
      color:#176b9b;
      font-size:12px;
      font-weight:950;
    }
    .venta-product-footer{
      margin-top:auto;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:8px;
    }
    .venta-product-stock .btn,
    .venta-product-action .btn{
      border-radius:9px;
      padding:5px 8px;
      font-size:11px;
      font-weight:900;
      white-space:normal;
      line-height:1.15;
    }
    .venta-product-hint{
      margin-top:8px;
      color:#7a8b98;
      font-size:11px;
      font-weight:800;
    }
    .venta-product-footer-bar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin-top:12px;
      padding-top:12px;
      border-top:1px solid #e5edf2;
    }
    .productosVentaInfo{
      color:#60717f;
      font-weight:900;
    }
    .productosVentaPaginacion,
    .productosVentaPaginas{
      display:flex;
      align-items:center;
      gap:5px;
      flex-wrap:wrap;
    }
    .productosVentaPagina{
      min-width:32px;
      height:32px;
      border-radius:9px;
      border:1px solid #cddde8;
      background:#eef5fa;
      color:#2e5d78;
      font-weight:950;
    }
    .productosVentaPagina.active{
      background:#176b9b;
      border-color:#176b9b;
      color:#fff;
    }
    .productosVentaPuntos{
      color:#60717f;
      font-weight:950;
      padding:0 4px;
    }
    .venta-products-empty{
      grid-column:1 / -1;
      min-height:150px;
      display:flex;
      align-items:center;
      justify-content:center;
      text-align:center;
      border:1px dashed #c6d4df;
      border-radius:14px;
      color:#6f8190;
      background:#f8fbfd;
      font-weight:900;
    }
    .venta-product-modal .modal-dialog{
      max-width:860px;
      width:92%;
    }
    .venta-product-modal .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      background:#f4f8fb;
      box-shadow:0 28px 80px rgba(10,30,45,.34);
    }
    .venta-product-modal .modal-header{
      position:relative;
      background:linear-gradient(135deg,#102b3b 0%,#176b9b 58%,#36aee2 100%);
      color:#fff;
      border:0;
      padding:18px 22px;
    }
    .venta-product-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.92;
      text-shadow:none;
    }
    .venta-product-modal-title{
      display:flex;
      align-items:center;
      gap:12px;
    }
    .venta-product-modal-icon{
      width:48px;
      height:48px;
      border-radius:14px;
      background:rgba(255,255,255,.18);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:22px;
      flex:0 0 48px;
    }
    .venta-product-modal-code{
      display:inline-flex;
      padding:4px 10px;
      border-radius:999px;
      background:rgba(255,255,255,.16);
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
    }
    .venta-product-modal-title h4{
      margin:5px 0 0;
      font-size:21px;
      font-weight:950;
      overflow-wrap:anywhere;
    }
    .venta-product-modal-layout{
      display:grid;
      grid-template-columns:280px minmax(0,1fr);
    }
    .venta-product-modal-img{
      min-height:320px;
      border-right:1px solid #dbe5ec;
      background:linear-gradient(180deg,#fff,#eef6fb);
      display:flex;
      align-items:center;
      justify-content:center;
      padding:22px;
    }
    .venta-product-modal-img img,
    .venta-product-modal-img .img-producto-venta{
      width:100%;
      max-height:250px;
      object-fit:contain;
      filter:drop-shadow(0 12px 18px rgba(22,49,64,.12));
    }
    .venta-product-modal-info{
      padding:18px;
    }
    .venta-product-modal-grid{
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:10px;
      margin-bottom:12px;
    }
    .venta-product-modal-grid>div,
    .venta-product-modal-description-box,
    .venta-product-modal-actions{
      background:#fff;
      border:1px solid #dbe5ec;
      border-radius:13px;
      padding:12px;
      box-shadow:0 10px 20px rgba(22,49,64,.05);
    }
    .venta-product-modal-grid span,
    .venta-product-modal-description-box h5{
      display:block;
      margin:0 0 5px;
      color:#6f8190;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
    }
    .venta-product-modal-grid strong,
    .venta-product-modal-description{
      color:#1f2d3d;
      font-weight:850;
      overflow-wrap:anywhere;
    }
    .venta-product-modal-description-box{
      margin-bottom:12px;
    }
    .venta-product-modal-note{
      display:flex;
      gap:8px;
      align-items:flex-start;
      border-left:4px solid #f39c12;
      border-radius:12px;
      background:#fff8e8;
      color:#634c17;
      padding:10px 12px;
      margin-bottom:12px;
      font-weight:850;
    }
    .venta-product-modal-actions{
      display:flex;
      justify-content:flex-end;
      gap:8px;
      flex-wrap:wrap;
    }
    .venta-product-modal-actions .btn{
      border-radius:11px;
      font-weight:950;
      padding:10px 16px;
    }
    #modalAgregarCliente .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
    }
    #modalAgregarCliente .modal-header{
      background:linear-gradient(135deg,#102b3b,#176b9b);
      color:#fff;
      border:0;
    }
    #modalAgregarCliente label{
      font-weight:900;
      color:#34495e;
    }
    #modalAgregarCliente .form-control,
    #modalAgregarCliente .input-group-addon{
      border-color:#d7e3ee;
      box-shadow:none;
    }
    .cliente-modal-note{
      background:#f7fbff;
      border:1px solid #d8e8f3;
      color:#35556b;
      padding:10px 12px;
      border-radius:12px;
      margin-bottom:14px;
      font-weight:800;
    }
    body.tm-dark-mode .editar-card,
    body.dark-mode .editar-card,
    body.tm-dark-mode .venta-product-card,
    body.dark-mode .venta-product-card{
      background:rgba(15,23,42,.72);
      border-color:rgba(99,135,184,.45);
      color:#f8fbff;
    }
    body.tm-dark-mode .editar-card-header,
    body.dark-mode .editar-card-header{
      background:rgba(15,23,42,.46);
      border-color:rgba(99,135,184,.35);
    }
    body.tm-dark-mode .editar-card-header h3,
    body.tm-dark-mode .venta-product-card h4,
    body.dark-mode .editar-card-header h3,
    body.dark-mode .venta-product-card h4{
      color:#f8fbff;
    }
    @media(max-width:1100px){
      .editar-venta-layout{
        grid-template-columns:1fr;
      }
    }
    @media(max-width:767px){
      .editar-venta-hero,
      .venta-product-toolbar,
      .editar-submit{
        flex-direction:column;
        align-items:stretch;
      }
      .editar-venta-code{
        width:100%;
      }
      .editar-summary,
      .venta-product-modal-layout,
      .venta-product-modal-grid{
        grid-template-columns:1fr;
      }
      .venta-product-modal-img{
        min-height:220px;
        border-right:0;
        border-bottom:1px solid #dbe5ec;
      }
    }
  </style>

  <section class="content-header">
    <h1>Editar venta</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li><a href="ventas">Ventas</a></li>
      <li class="active">Editar venta</li>
    </ol>
  </section>

  <section class="content">
    <div class="editar-venta-hero">
      <div>
        <h2>Modificar venta registrada</h2>
        <p>Actualice cliente, productos, cantidades y total antes de guardar los cambios.</p>
      </div>
      <div class="editar-venta-code">
        <span>Codigo de venta</span>
        <strong><?php echo htmlspecialchars($venta["codigo"], ENT_QUOTES, "UTF-8"); ?></strong>
      </div>
    </div>

    <form role="form" method="post" class="formularioVenta">
      <input type="hidden" id="listaProductos" name="listaProductos">
      <input type="hidden" id="listaMetodoPago" name="listaMetodoPago" value="<?php echo htmlspecialchars($metodoActual, ENT_QUOTES, "UTF-8"); ?>">
      <input type="hidden" name="idVendedor" value="<?php echo (int)($vendedor["id"] ?? $venta["id_vendedor"]); ?>">
      <input type="hidden" id="nuevaVenta" name="editarVenta" value="<?php echo htmlspecialchars($venta["codigo"], ENT_QUOTES, "UTF-8"); ?>">

      <div class="editar-venta-layout">
        <div>
          <div class="editar-card">
            <div class="editar-card-header">
              <h3><i class="fa fa-pencil-square-o"></i> Datos de la venta</h3>
              <span class="label label-info">Edicion</span>
            </div>
            <div class="editar-card-body">
              <div class="editar-fields">
                <div class="editar-field">
                  <label>Vendedor original</label>
                  <input type="text" class="form-control" id="nuevoVendedor" value="<?php echo htmlspecialchars($vendedor["nombre"] ?? "Sin vendedor", ENT_QUOTES, "UTF-8"); ?>" readonly>
                </div>
                <div class="editar-field">
                  <label>Cliente</label>
                  <div class="editar-inline-client">
                    <select class="form-control" id="seleccionarCliente" name="seleccionarCliente" required>
                      <option value="<?php echo (int)($cliente["id"] ?? 0); ?>"><?php echo htmlspecialchars($cliente["nombre"] ?? "Sin cliente", ENT_QUOTES, "UTF-8"); ?></option>
                      <?php foreach ($clientesVenta as $clienteVenta): ?>
                        <?php if((int)$clienteVenta["id"] !== (int)($cliente["id"] ?? 0)): ?>
                          <option value="<?php echo (int)$clienteVenta["id"]; ?>"><?php echo htmlspecialchars($clienteVenta["nombre"], ENT_QUOTES, "UTF-8"); ?></option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modalAgregarCliente">
                      <i class="fa fa-plus"></i> Cliente
                    </button>
                  </div>
                </div>
              </div>
              <div class="editar-note">
                Al guardar se recalcula el detalle de la venta. Revise cantidades y stock antes de confirmar.
              </div>
            </div>
          </div>

          <div class="editar-card">
            <div class="editar-card-header">
              <h3><i class="fa fa-shopping-basket"></i> Productos seleccionados</h3>
              <button type="button" class="btn btn-default btn-sm hidden-lg btnAgregarProducto">
                <i class="fa fa-plus"></i> Agregar
              </button>
            </div>
            <div class="editar-card-body">
              <div class="nuevoProducto editar-cart">
                <?php foreach ($listaProducto as $productoVenta): ?>
                  <?php
                    $itemProducto = "id";
                    $valorProducto = $productoVenta["id"] ?? 0;
                    $ordenProducto = "id";
                    $productoBase = ControladorProductos::ctrMostrarProductos($itemProducto, $valorProducto, $ordenProducto);
                    $stockAntiguo = (int)($productoBase["stock"] ?? 0) + (int)($productoVenta["cantidad"] ?? 0);
                    $precioBase = (float)($productoBase["precio_venta"] ?? 0);
                  ?>
                  <div class="row">
                    <div class="col-sm-6 col-xs-12" style="padding-right:6px">
                      <div class="input-group">
                        <span class="input-group-addon">
                          <button type="button" class="btn btn-danger btn-xs quitarProducto" idProducto="<?php echo (int)($productoVenta["id"] ?? 0); ?>" title="Quitar producto">
                            <i class="fa fa-times"></i>
                          </button>
                        </span>
                        <input type="text" class="form-control nuevaDescripcionProducto" idProducto="<?php echo (int)($productoVenta["id"] ?? 0); ?>" name="agregarProducto" value="<?php echo htmlspecialchars($productoVenta["descripcion"] ?? "Producto", ENT_QUOTES, "UTF-8"); ?>" readonly required>
                      </div>
                    </div>
                    <div class="col-sm-3 col-xs-6 ingresoCantidad">
                      <div class="input-group input-group-sm controlCantidad">
                        <span class="input-group-btn"><button type="button" class="btn btn-default btnCantidadMenos"><i class="fa fa-minus"></i></button></span>
                        <input type="number" class="form-control text-center nuevaCantidadProducto" name="nuevaCantidadProducto" min="1" value="<?php echo (int)($productoVenta["cantidad"] ?? 1); ?>" stock="<?php echo $stockAntiguo; ?>" nuevoStock="<?php echo (int)($productoVenta["stock"] ?? 0); ?>" required>
                        <span class="input-group-btn"><button type="button" class="btn btn-default btnCantidadMas"><i class="fa fa-plus"></i></button></span>
                      </div>
                    </div>
                    <div class="col-sm-3 col-xs-6 ingresoPrecio" style="padding-left:6px">
                      <div class="input-group">
                        <span class="input-group-addon"><b>Bs</b></span>
                        <input type="text" class="form-control nuevoPrecioProducto" precioReal="<?php echo $precioBase; ?>" name="nuevoPrecioProducto" value="<?php echo htmlspecialchars($productoVenta["total"] ?? 0, ENT_QUOTES, "UTF-8"); ?>" readonly required>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="editar-card">
            <div class="editar-card-header">
              <h3><i class="fa fa-calculator"></i> Resumen y guardado</h3>
            </div>
            <div class="editar-card-body">
              <div class="editar-summary">
                <div class="editar-summary-box">
                  <label>Descuento</label>
                  <div class="input-group">
                    <input type="number" class="form-control input-lg" min="0" id="nuevoImpuestoVenta" name="nuevoImpuestoVenta" value="<?php echo htmlspecialchars(number_format($porcentajeImpuesto, 2, ".", ""), ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="nuevoPrecioImpuesto" id="nuevoPrecioImpuesto" value="<?php echo htmlspecialchars($venta["descuento"], ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="nuevoPrecioNeto" id="nuevoPrecioNeto" value="<?php echo htmlspecialchars($venta["neto"], ENT_QUOTES, "UTF-8"); ?>" required>
                    <span class="input-group-addon"><i class="fa fa-percent"></i></span>
                  </div>
                </div>
                <div class="editar-summary-box editar-total">
                  <label>Total</label>
                  <div class="input-group">
                    <span class="input-group-addon"><b>Bs</b></span>
                    <input type="text" class="form-control input-lg" id="nuevoTotalVenta" name="nuevoTotalVenta" total="<?php echo htmlspecialchars($venta["neto"], ENT_QUOTES, "UTF-8"); ?>" value="<?php echo htmlspecialchars($venta["total"], ENT_QUOTES, "UTF-8"); ?>" readonly required>
                    <input type="hidden" name="totalVenta" value="<?php echo htmlspecialchars($venta["total"], ENT_QUOTES, "UTF-8"); ?>" id="totalVenta">
                  </div>
                </div>
              </div>
            </div>
            <div class="editar-submit">
              <a href="ventas" class="btn btn-default"><i class="fa fa-arrow-left"></i> Volver</a>
              <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
            </div>
          </div>
        </div>

        <div class="editar-card">
          <div class="editar-card-header">
            <h3><i class="fa fa-cubes"></i> Productos disponibles</h3>
            <span class="label label-info">Clic para ver detalle</span>
          </div>
          <div class="editar-card-body">
            <div class="venta-product-toolbar">
              <div class="venta-product-search">
                <i class="fa fa-search"></i>
                <input type="text" class="form-control" id="buscarProductoVentaCards" placeholder="Buscar por codigo o descripcion">
              </div>
              <div class="venta-product-size">
                <span>Mostrar</span>
                <select class="form-control" id="cantidadProductoVentaCards">
                  <option value="auto" selected>Auto</option>
                  <option value="8">8</option>
                  <option value="12">12</option>
                  <option value="16">16</option>
                  <option value="24">24</option>
                  <option value="32">32</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
            </div>
            <div class="productosCardsVenta venta-product-grid">
              <div class="venta-products-empty">Cargando productos disponibles...</div>
            </div>
            <div class="venta-product-footer-bar">
              <span class="productosVentaInfo">Cargando productos...</span>
              <div class="productosVentaPaginacion">
                <button type="button" class="btn btn-default btn-sm" data-page="prev">Anterior</button>
                <span class="productosVentaPaginas"></span>
                <button type="button" class="btn btn-default btn-sm" data-page="next">Siguiente</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php
        $editarVenta = new ControladorVentas();
        $editarVenta -> ctrEditarVenta();
      ?>
    </form>
  </section>
</div>

<div class="modal fade venta-product-modal" id="modalDetalleProductoVenta" tabindex="-1" role="dialog" aria-labelledby="tituloDetalleProductoVenta">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        <div class="venta-product-modal-title">
          <div class="venta-product-modal-icon"><i class="fa fa-cube"></i></div>
          <div>
            <span class="venta-product-modal-code"></span>
            <h4 class="modal-title venta-product-modal-name" id="tituloDetalleProductoVenta">Detalle del producto</h4>
          </div>
        </div>
      </div>
      <div class="modal-body">
        <div class="venta-product-modal-layout">
          <div class="venta-product-modal-img"></div>
          <div class="venta-product-modal-info">
            <div class="venta-product-modal-grid">
              <div><span>Codigo</span><strong class="venta-product-modal-code-value">-</strong></div>
              <div><span>Stock</span><strong class="venta-product-modal-stock">-</strong></div>
              <div><span>Precio</span><strong class="venta-product-modal-price">-</strong></div>
            </div>
            <div class="venta-product-modal-description-box">
              <h5>Descripcion</h5>
              <p class="venta-product-modal-description">-</p>
            </div>
            <div class="venta-product-modal-note">
              <i class="fa fa-info-circle"></i>
              <span>Revise stock y precio antes de agregar el producto a la venta.</span>
            </div>
            <div class="venta-product-modal-actions"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modalAgregarCliente" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <input type="hidden" name="origenCliente" value="ventas">

        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Agregar cliente</h4>
        </div>

        <div class="modal-body">
          <div class="box-body">
            <div class="cliente-modal-note">
              Registre los datos principales del cliente para poder seleccionarlo en esta venta sin salir del formulario.
            </div>

            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Nombre completo</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-user"></i></span>
                    <input type="text" class="form-control" name="nuevoCliente" placeholder="Ej. Juan Perez" required>
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Cedula de identidad / NIT</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                    <input type="number" min="0" class="form-control" name="nuevoDocumentoId" placeholder="Ej. 1234567" required>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Telefono</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                    <input type="text" class="form-control" name="nuevoTelefono" placeholder="Ej. 70000000">
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>Email</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                    <input type="email" class="form-control" name="nuevoEmail" placeholder="correo@ejemplo.com">
                  </div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Direccion</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                <input type="text" class="form-control" name="nuevaDireccion" placeholder="Barrio, calle, numero o referencia">
              </div>
            </div>

            <div class="form-group">
              <label>Fecha de nacimiento</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                <input type="text" class="form-control" name="nuevaFechaNacimiento" placeholder="aaaa/mm/dd" data-inputmask="'alias': 'yyyy/mm/dd'" data-mask>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary">Guardar cliente</button>
        </div>
      </form>

      <?php
        $crearCliente = new ControladorClientes();
        $crearCliente -> ctrCrearCliente();
      ?>
    </div>
  </div>
</div>
