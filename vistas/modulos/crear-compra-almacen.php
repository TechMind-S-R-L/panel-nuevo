<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen"){

  echo '<script>window.location = "inicio";</script>';
  return;

}

$codigoCompra = ControladorCompras::ctrSiguienteCodigoCompra();
$proveedoresCompra = ControladorProveedor::ctrMostrarProveedor(null, null);
$filtroStockCompra = $_GET["stock"] ?? "";

?>

<div class="content-wrapper compra-almacen-page">
<style>
  .compra-almacen-page{
    background:transparent !important;
  }
  .compra-hero{
    background:linear-gradient(135deg, rgba(20,49,66,.96), rgba(36,108,168,.92));
    color:#fff;
    border-radius:18px;
    padding:18px 20px;
    margin-bottom:16px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    box-shadow:0 18px 45px rgba(15,50,91,.16);
  }
  .compra-hero h2{
    margin:0 0 6px;
    font-size:25px;
    font-weight:800;
  }
  .compra-hero p{
    margin:0;
    color:rgba(255,255,255,.78);
    font-weight:600;
  }
  .compra-code{
    background:rgba(255,255,255,.96);
    color:#123145;
    border-radius:14px;
    padding:12px 18px;
    min-width:165px;
    text-align:center;
    box-shadow:0 12px 25px rgba(0,0,0,.12);
  }
  .compra-code span{
    display:block;
    font-size:11px;
    font-weight:800;
    letter-spacing:.4px;
    text-transform:uppercase;
    color:#607d94;
  }
  .compra-code strong{
    display:block;
    font-size:28px;
    line-height:1.05;
  }
  .compra-steps{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:10px;
    margin-bottom:16px;
  }
  .compra-step-chip{
    border:1px solid rgba(49,115,185,.16);
    background:rgba(255,255,255,.78);
    border-radius:14px;
    padding:10px 12px;
    display:flex;
    align-items:center;
    gap:10px;
    min-height:58px;
  }
  .compra-step-chip b{
    width:30px;
    height:30px;
    border-radius:10px;
    background:#245fbc;
    color:#fff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    flex:0 0 30px;
  }
  .compra-step-chip span{
    display:block;
    color:#1b2c41;
    font-weight:800;
  }
  .compra-step-chip small{
    display:block;
    color:#61758b;
    font-weight:600;
  }
  .compra-panel{
    background:rgba(255,255,255,.82);
    border:1px solid rgba(55,104,160,.16);
    border-radius:16px;
    box-shadow:0 18px 45px rgba(18,49,78,.10);
    overflow:hidden;
    margin-bottom:16px;
  }
  .compra-panel-header{
    padding:14px 16px;
    border-bottom:1px solid rgba(55,104,160,.12);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }
  .compra-panel-header h3{
    margin:0;
    font-size:17px;
    font-weight:800;
    color:#14263a;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .compra-panel-header p{
    margin:4px 0 0;
    color:#6b7d8f;
    font-weight:600;
  }
  .compra-panel-body{
    padding:16px;
  }
  .compra-field{
    margin-bottom:14px;
  }
  .compra-field label{
    display:block;
    color:#263a4f;
    font-weight:800;
    margin-bottom:7px;
  }
  .compra-control{
    height:42px;
    border:1px solid #d8e4ef;
    border-radius:12px;
    box-shadow:none;
    background:#fff;
  }
  .compra-provider-row{
    display:grid;
    grid-template-columns:minmax(0, 1fr) auto;
    gap:8px;
    align-items:center;
  }
  .compra-provider-row .btn{
    height:42px;
    border-radius:12px;
    font-weight:800;
    white-space:nowrap;
  }
  .compra-alert{
    border-radius:14px;
    padding:12px 14px;
    margin-bottom:14px;
    font-weight:700;
    border:1px solid transparent;
  }
  .compra-alert.danger{
    background:#fff1f1;
    color:#933535;
    border-color:#ffd1d1;
  }
  .compra-alert.warning{
    background:#fff8e6;
    color:#8a5d00;
    border-color:#ffe3a8;
  }
  .compra-products-toolbar{
    display:grid;
    grid-template-columns:minmax(240px, 1fr) auto auto;
    gap:10px;
    align-items:center;
    margin-bottom:12px;
  }
  .compra-search{
    position:relative;
  }
  .compra-search i{
    position:absolute;
    left:13px;
    top:50%;
    transform:translateY(-50%);
    color:#6f879c;
  }
  .compra-search input{
    padding-left:38px;
  }
  .compra-products-shell{
    max-height:610px;
    overflow-y:auto;
    padding-right:4px;
  }
  .productosCardsCompra{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(190px, 1fr));
    gap:12px;
  }
  .compra-product-card{
    background:rgba(255,255,255,.92);
    border:1px solid rgba(71,116,166,.18);
    border-radius:16px;
    padding:11px;
    min-height:230px;
    display:flex;
    flex-direction:column;
    gap:8px;
    cursor:pointer;
    transition:.18s ease;
  }
  .compra-product-card:hover{
    transform:translateY(-2px);
    box-shadow:0 14px 30px rgba(27,86,145,.14);
    border-color:rgba(36,95,188,.35);
  }
  .compra-product-img{
    height:82px;
    border-radius:13px;
    background:#f2f7fb;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
  }
  .compra-product-img img{
    width:100%;
    height:100%;
    object-fit:contain;
  }
  .compra-product-code{
    font-size:11px;
    color:#2560b7;
    font-weight:800;
    text-transform:uppercase;
    word-break:break-word;
  }
  .compra-product-card h4{
    margin:0;
    color:#172b40;
    font-weight:800;
    font-size:13px;
    line-height:1.25;
    min-height:48px;
  }
  .compra-product-footer{
    margin-top:auto;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
  }
  .compra-product-stock .btn,
  .compra-product-action .btn{
    border-radius:10px;
    font-weight:800;
    padding:6px 10px;
    font-size:12px;
  }
  .compra-product-hint{
    color:#71859a;
    font-size:11px;
    font-weight:700;
  }
  .productosCompraInfo{
    color:#63788d;
    font-weight:700;
  }
  .productosCompraPaginacion{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    flex-wrap:wrap;
    margin-top:12px;
  }
  .productosCompraPaginacion button,
  .productosCompraPagina{
    border:1px solid rgba(36,95,188,.18);
    background:#fff;
    color:#17406a;
    border-radius:10px;
    padding:7px 10px;
    font-weight:800;
  }
  .productosCompraPaginacion button:disabled{
    opacity:.45;
    cursor:not-allowed;
  }
  .productosCompraPagina.active{
    background:#245fbc;
    color:#fff;
  }
  .compra-cart{
    min-height:210px;
    max-height:330px;
    overflow-y:auto;
    border:1px dashed #b9cce0;
    background:rgba(247,251,255,.85);
    border-radius:14px;
    padding:10px 0;
  }
  .compra-cart:empty:before{
    content:"Agrega productos desde las tarjetas para preparar la solicitud.";
    display:block;
    padding:42px 18px;
    text-align:center;
    color:#71859a;
    font-weight:800;
  }
  .nuevoProducto .row{
    margin:0 !important;
    padding:9px 12px !important;
    border-bottom:1px solid #e4edf5;
  }
  .nuevoProducto .row:last-child{
    border-bottom:0;
  }
  .nuevoProducto .input-group-addon,
  .nuevoProducto .form-control{
    border-radius:10px;
  }
  .compra-summary-box{
    background:linear-gradient(135deg, rgba(255,255,255,.95), rgba(235,245,255,.9));
    border:1px solid rgba(55,104,160,.14);
    border-radius:14px;
    padding:14px;
  }
  .compra-summary-box label{
    display:block;
    color:#60758a;
    font-weight:800;
    margin-bottom:8px;
  }
  .compra-total-row{
    display:flex;
    gap:8px;
  }
  .compra-total-row .input-group-addon{
    border-radius:12px 0 0 12px;
    font-weight:800;
  }
  .compra-total-row input{
    height:48px;
    font-size:23px;
    font-weight:800;
    color:#143142;
  }
  .compra-submit{
    margin-top:12px;
    width:100%;
    height:46px;
    border-radius:12px;
    font-weight:800;
    background:#245fbc;
    border-color:#245fbc;
  }
  .compra-side-stack{
    position:sticky;
    top:78px;
  }
  .compra-side-stack .compra-panel{
    margin-bottom:12px;
  }
  .compra-compact-header{
    padding:12px 14px;
  }
  .compra-compact-body{
    padding:14px;
  }
  .compra-note{
    border-left:4px solid #f39c12;
    background:#fff8e8;
    color:#604714;
    padding:12px;
    border-radius:12px;
    font-weight:700;
    margin-top:12px;
  }
  .compra-modal-modern .modal-content{
    border:0;
    border-radius:18px;
    overflow:hidden;
  }
  .compra-modal-modern .modal-header{
    background:linear-gradient(135deg, #123145, #245fbc);
    color:#fff;
    border:0;
    padding:18px 22px;
  }
  .compra-modal-modern .modal-header h4{
    font-weight:800;
  }
  .compra-modal-modern .modal-body{
    background:#f6f9fc;
    padding:18px;
  }
  .compra-modal-modern .modal-footer{
    background:#fff;
    border:0;
    padding:14px 18px;
  }
  .compra-product-modal{
    display:grid;
    grid-template-columns:180px minmax(0, 1fr);
    gap:16px;
  }
  .compra-product-modal-img{
    min-height:170px;
    background:#f2f7fb;
    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
  }
  .compra-product-modal-img img{
    max-width:100%;
    max-height:170px;
    object-fit:contain;
  }
  .compra-product-modal h3{
    margin:0 0 8px;
    color:#14263a;
    font-weight:800;
  }
  .compra-modal-meta{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:10px;
    margin:12px 0;
  }
  .compra-modal-meta div{
    background:#fff;
    border:1px solid #e0e9f2;
    border-radius:12px;
    padding:10px;
  }
  .compra-modal-meta span{
    display:block;
    color:#71859a;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
  }
  .compra-modal-actions{
    margin-top:12px;
  }
  @media (max-width: 991px){
    .compra-steps,
    .compra-products-toolbar,
    .compra-product-modal{
      grid-template-columns:1fr;
    }
    .compra-hero{
      align-items:flex-start;
    }
    .compra-side-stack{
      position:static;
    }
    .compra-cart{
      max-height:none;
    }
  }
</style>

  <section class="content-header">
    <h1>Solicitud de compra</h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Crear solicitud</li>
    </ol>
  </section>

  <section class="content">

    <div class="compra-hero">
      <div>
        <h2>Nueva solicitud para almacen</h2>
        <p>Selecciona proveedor, agrega los productos faltantes y genera la solicitud para aprobacion.</p>
      </div>
      <div class="compra-code">
        <span>Codigo de solicitud</span>
        <strong><?php echo $codigoCompra; ?></strong>
      </div>
    </div>

    <div class="compra-steps">
      <div class="compra-step-chip"><b>1</b><div><span>Datos</span><small>Usuario y proveedor</small></div></div>
      <div class="compra-step-chip"><b>2</b><div><span>Productos</span><small>Busca y agrega</small></div></div>
      <div class="compra-step-chip"><b>3</b><div><span>Cantidades</span><small>Ajusta lo solicitado</small></div></div>
      <div class="compra-step-chip"><b>4</b><div><span>Enviar</span><small>Genera la solicitud</small></div></div>
    </div>

    <form role="form" method="post" class="formularioCompra">

      <div class="row">
        <div class="col-lg-7 col-md-7 col-sm-12">
          <div class="compra-panel">
            <div class="compra-panel-header">
              <div>
                <h3><i class="fa fa-cubes"></i> Productos disponibles</h3>
                <p>Busca, filtra y agrega productos a la solicitud.</p>
              </div>
            </div>
            <div class="compra-panel-body">
              <?php if($filtroStockCompra === "0"): ?>
                <div class="compra-alert danger">
                  <i class="fa fa-cart-plus"></i>
                  Productos sin stock: se muestran primero los productos en cero para generar compra.
                </div>
              <?php elseif($filtroStockCompra === "bajo"): ?>
                <div class="compra-alert warning">
                  <i class="fa fa-warning"></i>
                  Stock bajo: se muestran productos con pocas unidades para reposicion.
                </div>
              <?php endif; ?>

              <div class="compra-products-toolbar">
                <div class="compra-search">
                  <i class="fa fa-search"></i>
                  <input type="text" class="form-control compra-control" id="buscarProductoCompraCards" placeholder="Buscar por codigo o descripcion">
                </div>
                <select class="form-control compra-control" id="filtroStockCompraCards">
                  <option value="">Todos los productos</option>
                  <option value="sin-stock" <?php echo $filtroStockCompra === "0" ? "selected" : ""; ?>>Sin stock</option>
                  <option value="stock-bajo" <?php echo $filtroStockCompra === "bajo" ? "selected" : ""; ?>>Stock bajo</option>
                </select>
                <select class="form-control compra-control" id="cantidadProductoCompraCards">
                  <option value="12">12 productos</option>
                  <option value="24">24 productos</option>
                  <option value="32">32 productos</option>
                  <option value="50">50 productos</option>
                  <option value="100">100 productos</option>
                </select>
              </div>

              <div class="compra-products-shell">
                <div class="productosCardsCompra">
                  <div class="text-center text-muted" style="padding:35px 0;font-weight:800;">Cargando productos...</div>
                </div>
              </div>

              <div class="productosCompraPaginacion">
                <span class="productosCompraInfo">Cargando...</span>
                <div>
                  <button type="button" data-page="prev">Anterior</button>
                  <span class="productosCompraPaginas"></span>
                  <button type="button" data-page="next">Siguiente</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5 col-md-5 col-sm-12">
          <div class="compra-side-stack">
            <div class="compra-panel">
              <div class="compra-panel-header compra-compact-header">
                <div>
                  <h3><i class="fa fa-clipboard"></i> Datos de solicitud</h3>
                  <p>Proveedor y responsable.</p>
                </div>
              </div>
              <div class="compra-panel-body compra-compact-body">
                <div class="compra-field">
                  <label>Solicitante</label>
                  <input type="text" class="form-control compra-control" id="nuevoUsuario" value="<?php echo $_SESSION["nombre"]; ?>" readonly>
                  <input type="hidden" name="idUsuario" value="<?php echo $_SESSION["id"]; ?>">
                </div>

                <div class="compra-field">
                  <label>Codigo de compra</label>
                  <input type="text" class="form-control compra-control" id="nuevaCompra" name="nuevaCompra" value="<?php echo $codigoCompra; ?>" readonly>
                </div>

                <div class="compra-field">
                  <label>Proveedor</label>
                  <div class="compra-provider-row">
                    <select class="form-control compra-control" id="seleccionarProveedor" name="seleccionarProveedor" required>
                      <option value="">Seleccionar proveedor</option>
                      <?php foreach ($proveedoresCompra as $key => $value): ?>
                        <option value="<?php echo $value["id"]; ?>"><?php echo $value["nombre"]; ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modalAgregarProveedor">
                      <i class="fa fa-plus"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="compra-panel">
              <div class="compra-panel-header compra-compact-header">
                <div>
                  <h3><i class="fa fa-shopping-basket"></i> Productos seleccionados</h3>
                  <p>Todo lo agregado queda visible aqui.</p>
                </div>
              </div>
              <div class="compra-panel-body compra-compact-body">
                <div class="form-group row nuevoProducto compra-cart"></div>
                <button type="button" class="btn btn-default btnAgregarProductoCompra visible-xs visible-sm">
                  <i class="fa fa-plus"></i> Agregar producto manual
                </button>
              </div>
            </div>

            <div class="compra-panel">
              <div class="compra-panel-header compra-compact-header">
                <div>
                  <h3><i class="fa fa-file-text-o"></i> Resumen y envio</h3>
                  <p>Almacen solicita cantidades; caja definira el monto a desembolsar.</p>
                </div>
              </div>
              <div class="compra-panel-body compra-compact-body">
                <div class="compra-summary-box">
                  <label><i class="fa fa-info-circle"></i> Solicitud sin precio</label>
                  <p style="margin:0;color:#60758a;font-weight:700">El cajero o administrador analizara el mercado y registrara un desembolso con margen suficiente para la compra.</p>
                </div>
                <input type="hidden" id="nuevoTotalCompra" name="nuevoTotalCompra" value="0" total="0">
                <input type="hidden" name="totalCompra" id="totalCompra" value="0">
                <input type="hidden" id="listaProductos" name="listaProductos">
                <div class="compra-note">
                  <i class="fa fa-info-circle"></i>
                  Se guarda como solicitud; el stock se actualiza recien en ordenes de ingreso.
                </div>
                <button type="submit" class="btn btn-primary compra-submit">
                  <i class="fa fa-paper-plane"></i> Guardar solicitud
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php
        $guardarCompra = new ControladorCompras();
        $guardarCompra -> ctrCrearCompra();
      ?>

    </form>

  </section>

</div>

<div id="modalDetalleProductoCompra" class="modal fade compra-modal-modern" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-cube"></i> Detalle del producto</h4>
      </div>
      <div class="modal-body">
        <div class="compra-product-modal">
          <div class="compra-product-modal-img"></div>
          <div>
            <span class="compra-product-modal-code label label-primary"></span>
            <h3 class="compra-product-modal-name"></h3>
            <p class="compra-product-modal-description text-muted"></p>
            <div class="compra-modal-meta">
              <div><span>Codigo</span><strong class="compra-product-modal-code-value"></strong></div>
              <div><span>Stock actual</span><strong class="compra-product-modal-stock"></strong></div>
            </div>
            <div class="compra-modal-actions"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!--=====================================
MODAL AGREGAR PROVEEDOR
======================================-->

<div id="modalAgregarProveedor" class="modal fade compra-modal-modern" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-truck"></i> Nuevo proveedor</h4>
        </div>
        <div class="modal-body">
          <div class="compra-field">
            <label>Nombre del proveedor</label>
            <input type="text" class="form-control compra-control" name="nuevoProveedor" placeholder="Ej. Distribuidora tecnologia" required>
          </div>
          <div class="compra-field">
            <label>Contacto</label>
            <input type="text" class="form-control compra-control" name="nuevoContacto" placeholder="Persona de contacto" id="nuevoContacto" required>
          </div>
          <div class="compra-field">
            <label>Direccion</label>
            <input type="text" class="form-control compra-control" name="nuevaDireccion" placeholder="Direccion del proveedor" required>
          </div>
          <div class="compra-field">
            <label>Telefono</label>
            <input type="number" class="form-control compra-control" name="nuevoTelefono" placeholder="Telefono o celular" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar proveedor</button>
        </div>

        <?php
          $crearProveedor = new ControladorProveedor();
          $crearProveedor -> ctrCrearProveedor();
        ?>
      </form>
    </div>
  </div>
</div>
