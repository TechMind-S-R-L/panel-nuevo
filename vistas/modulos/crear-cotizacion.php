<?php

$cotizaciones = ControladorCotizacion::ctrMostrarCotizacion(null, null);
$codigoCotizacion = 10001;
if($cotizaciones && count($cotizaciones) > 0){
  $ultimaCotizacion = end($cotizaciones);
  $codigoCotizacion = 10000 + ((int)$ultimaCotizacion["id"] + 1);
}

$clientesCotizacion = ControladorClientes::ctrMostrarClientes(null, null);
$validoHastaDefecto = date("Y-m-d", strtotime("+15 days"));
$condicionesDefecto = "Forma de pago: efectivo, transferencia o segun acuerdo con el cliente.\nForma de entrega: en instalaciones del cliente o punto acordado.\nPrecios: incluyen impuestos de ley.\nGarantia: segun condiciones del fabricante y servicio contratado.";

?>

<div class="content-wrapper crear-cotizacion-page">
<style>
  .crear-cotizacion-page{background:#eef3f7 !important;}
  .cotizacion-hero{
    background:#163140;
    color:#fff;
    padding:18px 20px;
    border-radius:4px;
    margin-bottom:16px;
    display:flex;
    justify-content:space-between;
    gap:15px;
    align-items:center;
    flex-wrap:wrap;
  }
  .cotizacion-hero h2{margin:0 0 6px;font-size:24px;font-weight:700;}
  .cotizacion-hero p{margin:0;color:#c8d7df;}
  .cotizacion-code{
    background:#fff;
    color:#163140;
    border-radius:4px;
    padding:12px 16px;
    min-width:180px;
    text-align:center;
  }
  .cotizacion-code span{display:block;color:#6f7f8a;font-size:12px;text-transform:uppercase;font-weight:700;}
  .cotizacion-code strong{font-size:26px;line-height:1;}
  .cotizacion-panel{
    background:#fff;
    border:1px solid #dbe5ec;
    border-radius:4px;
    box-shadow:0 1px 2px rgba(0,0,0,.06);
    margin-bottom:16px;
  }
  .cotizacion-step{overflow:hidden;}
  .cotizacion-step.cotizacion-step-hidden{display:none;}
  .cotizacion-wizard-progress{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:10px;
    margin:0 0 16px;
  }
  .cotizacion-wizard-dot{
    border:1px solid #dbe5ec;
    background:rgba(255,255,255,.88);
    border-radius:7px;
    padding:11px 12px;
    display:flex;
    align-items:center;
    gap:10px;
    text-align:left;
    color:#506574;
    font-weight:800;
    box-shadow:0 8px 22px rgba(22,49,64,.06);
    cursor:pointer;
    transition:all .18s ease;
  }
  .cotizacion-wizard-dot span{
    width:30px;
    height:30px;
    border-radius:50%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    flex:0 0 30px;
    background:#eaf3fa;
    color:#2b79a5;
    font-weight:900;
  }
  .cotizacion-wizard-dot small{
    display:block;
    font-size:11px;
    color:#8a9aa6;
    margin-top:2px;
  }
  .cotizacion-wizard-dot.active{
    background:#163140;
    color:#fff;
    border-color:#163140;
    box-shadow:0 14px 30px rgba(22,49,64,.18);
  }
  .cotizacion-wizard-dot.active span{
    background:#3c8dbc;
    color:#fff;
  }
  .cotizacion-wizard-dot.active small{
    color:#c8d7df;
  }
  .cotizacion-wizard-dot.done{
    border-color:#9dd4af;
    background:#f2fff6;
  }
  .cotizacion-step-header{
    padding:14px 16px;
    border-bottom:1px solid #e5edf2;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    background:#fbfdff;
  }
  .cotizacion-step-title{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .cotizacion-step-number{
    width:32px;
    height:32px;
    border-radius:50%;
    background:#3c8dbc;
    color:#fff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    flex:0 0 32px;
  }
  .cotizacion-step-header h3{margin:0;font-size:18px;font-weight:700;color:#1f2d3d;}
  .cotizacion-step-body{padding:16px;}
  .cotizacion-step-body.table-responsive{padding:10px;}
  .cotizacion-fields-grid{
    display:grid;
    grid-template-columns:1fr 1.25fr 220px;
    gap:14px;
    align-items:end;
  }
  .cotizacion-panel-header{
    padding:14px 16px;
    border-bottom:1px solid #e5edf2;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .cotizacion-panel-header h3{margin:0;font-size:18px;font-weight:700;color:#1f2d3d;}
  .cotizacion-panel-body{padding:16px;}
  .cotizacion-label{display:block;font-weight:700;color:#34495e;margin-bottom:7px;}
  .cotizacion-input,.cotizacion-select{
    height:42px;
    border-radius:4px;
    border-color:#d7e1e8;
    box-shadow:none;
  }
  .cotizacion-inline-action{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:8px;
    align-items:center;
  }
  .cotizacion-inline-action .form-control{min-width:0;}
  .cotizacion-inline-action .btn{white-space:nowrap;}
  .cotizacion-cart{
    min-height:118px;
    border:1px dashed #c6d4df;
    background:#f8fbfd;
    border-radius:4px;
    padding:10px 0;
  }
  .cotizacion-cart:empty:before{
    content:"Seleccione productos desde las tarjetas para agregarlos a esta cotizacion.";
    display:block;
    padding:20px;
    text-align:center;
    color:#7d8c96;
    font-weight:600;
  }
  .nuevoProducto .row{
    margin-left:0 !important;
    margin-right:0 !important;
    padding:8px 12px !important;
    border-bottom:1px solid #e8eef3;
  }
  .nuevoProducto .row:last-child{border-bottom:0;}
  .nuevaCantidadProducto{font-weight:700;color:#1f2d3d;}
  .cotizacion-summary{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  .cotizacion-summary-box{
    border:1px solid #dbe5ec;
    border-radius:4px;
    padding:12px;
    background:#fbfdff;
  }
  .cotizacion-summary-box label{color:#60717f;font-weight:700;margin-bottom:7px;}
  .cotizacion-total input{font-size:24px;font-weight:700;color:#163140;}
  .cotizacion-note{
    border-left:4px solid #3c8dbc;
    background:#f2f8fc;
    color:#35556b;
    padding:12px;
    border-radius:4px;
    margin-top:14px;
  }
  .cotizacion-condiciones{
    min-height:150px;
    resize:vertical;
    border-color:#d7e1e8;
  }
  .cotizacion-product-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:14px;
  }
  .cotizacion-product-search{
    position:relative;
    flex:1 1 320px;
  }
  .cotizacion-product-search i{
    position:absolute;
    left:13px;
    top:50%;
    transform:translateY(-50%);
    color:#6f8190;
  }
  .cotizacion-product-search input{
    height:42px;
    padding-left:38px;
    border-color:#d7e1e8;
    box-shadow:none;
  }
  .cotizacion-product-size{
    display:flex;
    align-items:center;
    gap:8px;
    color:#60717f;
    font-weight:700;
  }
  .cotizacion-product-size select{
    width:96px;
    height:42px;
  }
  .cotizacion-product-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(145px, 1fr));
    gap:10px;
    max-height:650px;
    overflow-y:auto;
    padding-right:6px;
  }
  .cotizacion-product-grid::-webkit-scrollbar{width:8px;}
  .cotizacion-product-grid::-webkit-scrollbar-track{
    background:#eef3f7;
    border-radius:8px;
  }
  .cotizacion-product-grid::-webkit-scrollbar-thumb{
    background:#9bb7ca;
    border-radius:8px;
  }
  .cotizacion-product-card{
    border:1px solid #dbe5ec;
    border-radius:7px;
    background:#fff;
    padding:9px;
    min-height:215px;
    display:flex;
    flex-direction:column;
    box-shadow:0 8px 20px rgba(22,49,64,.06);
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .cotizacion-product-card:hover{
    border-color:#3c8dbc;
    box-shadow:0 12px 26px rgba(22,49,64,.12);
    transform:translateY(-2px);
  }
  .cotizacion-product-img{
    height:78px;
    background:#f7fafc;
    border:1px solid #edf2f6;
    border-radius:6px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
    margin-bottom:10px;
  }
  .cotizacion-product-img img,
  .cotizacion-product-img .img-producto-cotizacion{
    width:100%;
    max-width:105px;
    height:68px;
    object-fit:contain;
    margin:0 auto;
  }
  .cotizacion-product-code{
    display:block;
    color:#3c8dbc;
    font-size:12px;
    font-weight:800;
    letter-spacing:.02em;
    text-transform:uppercase;
    margin-bottom:5px;
    overflow-wrap:anywhere;
  }
  .cotizacion-product-card h4{
    margin:0 0 7px;
    color:#1f2d3d;
    font-size:12px;
    font-weight:800;
    line-height:1.35;
    min-height:48px;
    overflow-wrap:anywhere;
  }
  .cotizacion-product-price{
    min-height:25px;
    margin-bottom:6px;
  }
  .cotizacion-card-price{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:24px;
    padding:4px 8px;
    border-radius:6px;
    background:#eaf5fb;
    color:#176b9b;
    font-weight:800;
    font-size:12px;
  }
  .cotizacion-product-footer{
    margin-top:auto;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
  }
  .cotizacion-product-stock .btn,
  .cotizacion-product-action .btn{
    white-space:nowrap;
    padding:5px 8px;
    font-size:12px;
  }
  .cotizacion-product-footer-bar{
    margin-top:14px;
    padding-top:12px;
    border-top:1px solid #e5edf2;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }
  .productosCotizacionInfo{
    color:#60717f;
    font-weight:700;
    font-size:12px;
  }
  .productosCotizacionPaginacion{
    display:flex;
    align-items:center;
    gap:6px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }
  .productosCotizacionPaginas{
    display:flex;
    align-items:center;
    gap:4px;
    flex-wrap:wrap;
  }
  .productosCotizacionPagina,
  .productosCotizacionPuntos{
    min-width:34px;
    height:34px;
    border:1px solid #d7e1e8;
    border-radius:4px;
    background:#fff;
    color:#34495e;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }
  .productosCotizacionPagina.active{
    background:#3c8dbc;
    color:#fff;
    border-color:#3c8dbc;
  }
  .cotizacion-products-empty{
    grid-column:1 / -1;
    min-height:140px;
    border:1px dashed #c6d4df;
    background:#f8fbfd;
    border-radius:6px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#7d8c96;
    font-weight:700;
    text-align:center;
    padding:18px;
  }
  .productos-cotizacion-panel .dataTables_filter input,
  .productos-cotizacion-panel .dataTables_length select{
    border:1px solid #d7e1e8;
    border-radius:4px;
    height:34px;
  }
  .productos-cotizacion-panel .dataTables_wrapper{width:100%;}
  .productos-cotizacion-panel .dataTables_length{float:left;min-height:38px;}
  .productos-cotizacion-panel .dataTables_filter{float:right;min-height:38px;text-align:right;}
  .productos-cotizacion-panel .dataTables_info{float:left;padding-top:12px;white-space:nowrap;}
  .productos-cotizacion-panel .dataTables_paginate{float:right;padding-top:8px;text-align:right;}
  .productos-cotizacion-panel .pagination{margin:0;display:inline-flex;align-items:center;gap:4px;flex-wrap:nowrap;}
  .productos-cotizacion-panel .pagination>li{display:inline-block;}
  .productos-cotizacion-panel .pagination>li>a,
  .productos-cotizacion-panel .pagination>li>span{
    min-width:34px;
    height:34px;
    padding:7px 10px;
    line-height:18px;
    text-align:center;
    border-radius:4px;
    margin-left:0;
  }
  .productos-cotizacion-panel table.tablaCotizacion{
    width:100% !important;
    table-layout:auto;
    margin-bottom:8px !important;
  }
  .productos-cotizacion-panel table.tablaCotizacion th:nth-child(1),
  .productos-cotizacion-panel table.tablaCotizacion td:nth-child(1){width:38px !important;text-align:center;}
  .productos-cotizacion-panel table.tablaCotizacion th:nth-child(2),
  .productos-cotizacion-panel table.tablaCotizacion td:nth-child(2){width:56px !important;text-align:center;}
  .productos-cotizacion-panel table.tablaCotizacion th:nth-child(3),
  .productos-cotizacion-panel table.tablaCotizacion td:nth-child(3){width:96px !important;word-break:break-word;}
  .productos-cotizacion-panel table.tablaCotizacion th:nth-child(4),
  .productos-cotizacion-panel table.tablaCotizacion td:nth-child(4){min-width:170px;overflow-wrap:anywhere;}
  .productos-cotizacion-panel table.tablaCotizacion th:nth-child(5),
  .productos-cotizacion-panel table.tablaCotizacion td:nth-child(5){width:68px !important;text-align:center;}
  .productos-cotizacion-panel table.tablaCotizacion th:nth-child(6),
  .productos-cotizacion-panel table.tablaCotizacion td:nth-child(6){width:92px !important;text-align:center;}
  .productos-cotizacion-panel table.tablaCotizacion td{vertical-align:middle !important;padding:7px 6px !important;line-height:1.25;}
  .productos-cotizacion-panel table.tablaCotizacion .img-producto-cotizacion{
    width:34px;
    height:34px;
    object-fit:contain;
    display:block;
    margin:0 auto;
  }
  .productos-cotizacion-panel .cotizacion-panel-body{padding:10px;overflow-x:hidden;}
  .productos-cotizacion-panel .dataTables_wrapper .row{margin-left:0;margin-right:0;}
  .productos-cotizacion-panel .dataTables_wrapper .col-sm-12,
  .productos-cotizacion-panel .dataTables_wrapper .col-sm-6{padding-left:0;padding-right:0;}
  .productos-cotizacion-panel table thead th{background:#f5f8fa;color:#263845;}
  .cotizacion-wizard-actions{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-top:4px;
  }
  .cotizacion-wizard-actions .btn{
    min-width:150px;
    font-weight:800;
  }
  .cotizacion-wizard-help{
    color:#6f8190;
    font-weight:700;
  }
  #modalAgregarCliente .modal-dialog{max-width:720px;}
  #modalAgregarCliente .modal-header{background:#2f89b8;color:#fff;}
  #modalAgregarCliente .cliente-modal-note{
    background:#f7fbff;
    border:1px solid #d8e8f3;
    color:#35556b;
    padding:10px 12px;
    border-radius:4px;
    margin-bottom:15px;
  }
  #modalAgregarCliente label{font-weight:600;color:#34495e;}
  #modalAgregarCliente .input-group-addon{min-width:42px;background:#f4f6f8;}
  @media(max-width:991px){
    .cotizacion-summary{grid-template-columns:1fr;}
    .cotizacion-fields-grid{grid-template-columns:1fr;}
    .cotizacion-wizard-progress{grid-template-columns:1fr;}
    .cotizacion-wizard-actions{flex-direction:column;align-items:stretch;}
    .cotizacion-wizard-actions .btn{width:100%;}
    .productos-cotizacion-panel .dataTables_length,
    .productos-cotizacion-panel .dataTables_filter,
    .productos-cotizacion-panel .dataTables_info,
    .productos-cotizacion-panel .dataTables_paginate{float:none;text-align:left;width:100%;}
    .productos-cotizacion-panel .dataTables_filter{margin-top:8px;}
    .productos-cotizacion-panel .dataTables_paginate{overflow-x:auto;padding-bottom:4px;}
  }
</style>

  <section class="content-header">
    <h1>Crear cotizacion</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Crear cotizacion</li>
    </ol>
  </section>

  <section class="content">
    <div class="cotizacion-hero">
      <div>
        <h2>Nueva cotizacion comercial</h2>
        <p>Seleccione cliente, agregue productos, ajuste condiciones y defina la fecha de validez.</p>
      </div>
      <div class="cotizacion-code">
        <span>Codigo de cotizacion</span>
        <strong><?php echo $codigoCotizacion; ?></strong>
      </div>
    </div>

    <form role="form" method="post" class="formularioCotizacion">
      <input type="hidden" id="listaProductosCotizacion" name="listaProductosCotizacion">
      <input type="hidden" name="idUser" value="<?php echo $_SESSION["id"]; ?>">
      <input type="hidden" id="nuevaCotizacion" name="nuevaCotizacion" value="<?php echo $codigoCotizacion; ?>">

      <div class="cotizacion-wizard-progress">
        <button type="button" class="cotizacion-wizard-dot active" data-cotizacion-step-target="1">
          <span>1</span>
          <div>Datos <small>Cliente y validez</small></div>
        </button>
        <button type="button" class="cotizacion-wizard-dot" data-cotizacion-step-target="2">
          <span>2</span>
          <div>Productos <small>Buscar y agregar</small></div>
        </button>
        <button type="button" class="cotizacion-wizard-dot" data-cotizacion-step-target="3">
          <span>3</span>
          <div>Seleccionados <small>Cantidades</small></div>
        </button>
        <button type="button" class="cotizacion-wizard-dot" data-cotizacion-step-target="4">
          <span>4</span>
          <div>Condiciones <small>Texto de boleta</small></div>
        </button>
        <button type="button" class="cotizacion-wizard-dot" data-cotizacion-step-target="5">
          <span>5</span>
          <div>Resumen <small>Guardar</small></div>
        </button>
      </div>

      <div class="cotizacion-panel cotizacion-step" data-cotizacion-step="1">
        <div class="cotizacion-step-header">
          <div class="cotizacion-step-title">
            <span class="cotizacion-step-number">1</span>
            <h3><i class="fa fa-user"></i> Datos de la cotizacion</h3>
          </div>
        </div>
        <div class="cotizacion-step-body">
          <div class="cotizacion-fields-grid">
            <div class="form-group">
              <label class="cotizacion-label">Vendedor</label>
              <input type="text" class="form-control cotizacion-input" id="nuevoUser" value="<?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?>" readonly>
            </div>

            <div class="form-group">
              <label class="cotizacion-label">Cliente</label>
              <div class="cotizacion-inline-action">
                <select class="form-control cotizacion-select" id="seleccionarCliente" name="seleccionarCliente" required>
                  <option value="">Seleccionar cliente</option>
                  <?php foreach ($clientesCotizacion as $clienteCotizacion): ?>
                    <option value="<?php echo $clienteCotizacion["id"]; ?>"><?php echo htmlspecialchars($clienteCotizacion["nombre"], ENT_QUOTES, "UTF-8"); ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modalAgregarCliente">
                  <i class="fa fa-plus"></i> Cliente
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="cotizacion-label">Valido hasta</label>
              <input type="date" class="form-control cotizacion-input" name="validoHastaCotizacion" value="<?php echo $validoHastaDefecto; ?>" required>
            </div>
          </div>
        </div>
      </div>

      <div class="cotizacion-panel cotizacion-step productos-cotizacion-panel cotizacion-step-hidden" data-cotizacion-step="2">
        <div class="cotizacion-step-header">
          <div class="cotizacion-step-title">
            <span class="cotizacion-step-number">2</span>
            <h3><i class="fa fa-cubes"></i> Productos disponibles</h3>
          </div>
          <span class="label label-info">Primero se muestran productos con stock</span>
        </div>
        <div class="cotizacion-step-body">
          <div class="cotizacion-product-toolbar">
            <div class="cotizacion-product-search">
              <i class="fa fa-search"></i>
              <input type="text" class="form-control" id="buscarProductoCotizacionCards" placeholder="Buscar por codigo o descripcion">
            </div>
            <div class="cotizacion-product-size">
              <span>Mostrar</span>
              <select class="form-control" id="cantidadProductoCotizacionCards">
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

          <div class="productosCardsCotizacion cotizacion-product-grid">
            <div class="cotizacion-products-empty">Cargando productos disponibles...</div>
          </div>

          <div class="cotizacion-product-footer-bar">
            <span class="productosCotizacionInfo">Cargando productos...</span>
            <div class="productosCotizacionPaginacion">
              <button type="button" class="btn btn-default btn-sm" data-page="prev">Anterior</button>
              <span class="productosCotizacionPaginas"></span>
              <button type="button" class="btn btn-default btn-sm" data-page="next">Siguiente</button>
            </div>
          </div>
        </div>
      </div>

      <div class="cotizacion-panel cotizacion-step cotizacion-step-hidden" data-cotizacion-step="3">
        <div class="cotizacion-step-header">
          <div class="cotizacion-step-title">
            <span class="cotizacion-step-number">3</span>
            <h3><i class="fa fa-shopping-basket"></i> Productos seleccionados</h3>
          </div>
          <button type="button" class="btn btn-default btn-sm hidden-lg btnAgregarProductoss">
            <i class="fa fa-plus"></i> Agregar producto
          </button>
        </div>
        <div class="cotizacion-step-body">
          <div class="nuevoProducto cotizacion-cart"></div>
        </div>
      </div>

      <div class="cotizacion-panel cotizacion-step cotizacion-step-hidden" data-cotizacion-step="4">
        <div class="cotizacion-step-header">
          <div class="cotizacion-step-title">
            <span class="cotizacion-step-number">4</span>
            <h3><i class="fa fa-file-text-o"></i> Condiciones</h3>
          </div>
        </div>
        <div class="cotizacion-step-body">
          <textarea class="form-control cotizacion-condiciones" name="condicionesCotizacion" required><?php echo htmlspecialchars($condicionesDefecto, ENT_QUOTES, "UTF-8"); ?></textarea>
          <div class="cotizacion-note">
            Antes de guardar puede aumentar, quitar o modificar las condiciones que saldran en la boleta de cotizacion.
          </div>
        </div>
      </div>

      <div class="cotizacion-panel cotizacion-step cotizacion-step-hidden" data-cotizacion-step="5">
        <div class="cotizacion-step-header">
          <div class="cotizacion-step-title">
            <span class="cotizacion-step-number">5</span>
            <h3><i class="fa fa-calculator"></i> Resumen</h3>
          </div>
        </div>
        <div class="cotizacion-step-body">
          <div class="cotizacion-summary">
            <div class="cotizacion-summary-box">
              <label>Descuento</label>
              <div class="input-group">
                <input type="number" class="form-control input-lg" min="0" id="nuevoImpuestoCotizacion" name="nuevoImpuestoCotizacion" placeholder="0">
                <input type="hidden" name="nuevoPrecioImpuesto" id="nuevoPrecioImpuesto">
                <input type="hidden" name="nuevoPrecioNeto" id="nuevoPrecioNeto" required>
                <span class="input-group-addon"><i class="fa fa-percent"></i></span>
              </div>
            </div>

            <div class="cotizacion-summary-box cotizacion-total">
              <label>Total</label>
              <div class="input-group">
                <span class="input-group-addon"><b>Bs</b></span>
                <input type="text" class="form-control input-lg" id="nuevoTotalCotizacion" name="nuevoTotalCotizacion" total="" placeholder="0.00" readonly required>
                <input type="hidden" name="totalCotizacion" id="totalCotizacion">
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:16px">
            <i class="fa fa-save"></i> Guardar cotizacion
          </button>
        </div>
      </div>

      <div class="cotizacion-wizard-actions">
        <button type="button" class="btn btn-default btnCotizacionPasoAnterior">
          <i class="fa fa-arrow-left"></i> Anterior
        </button>
        <span class="cotizacion-wizard-help">Complete el paso actual para continuar.</span>
        <button type="button" class="btn btn-primary btnCotizacionPasoSiguiente">
          Siguiente <i class="fa fa-arrow-right"></i>
        </button>
      </div>

      <?php
        $guardarCotizacion = new ControladorCotizacion();
        $guardarCotizacion -> ctrCrearCotizacion();
      ?>
    </form>
  </section>
</div>

<div id="modalAgregarCliente" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <input type="hidden" name="origenCliente" value="cotizacion">

        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Agregar cliente</h4>
        </div>

        <div class="modal-body">
          <div class="box-body">
            <div class="cliente-modal-note">
              Registre los datos principales del cliente para poder seleccionarlo en esta cotizacion sin salir del formulario.
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
