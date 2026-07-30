<?php

if(($_SESSION["perfil"] ?? "") != "Administrador"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

function tmAdminPrecioEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmAdminPrecioImagen($ruta){
  $ruta = trim((string)$ruta);
  return $ruta !== "" ? $ruta : "vistas/img/productos/default/anonymous.png";
}

function tmAdminPrecioMoney($valor){
  return "Bs ".number_format((float)($valor ?? 0), 2);
}

function tmAdminPrecioData($productos){
  $datos = array();

  foreach($productos as $producto){
    $compra = (float)($producto["precio_compra"] ?? 0);
    $venta = (float)($producto["precio_venta"] ?? 0);
    $ganancia = $venta - $compra;
    $margen = $compra > 0 ? (($ganancia / $compra) * 100) : 0;
    $categoria = $producto["ruta_categoria"] ?? "Sin categoria";
    $codigo = $producto["codigo"] ?? "";
    $codigoGeneral = $producto["codigo_producto_generico"] ?? "";
    $codigoUnico = $producto["codigo_barras_unico"] ?? "";
    $descripcion = $producto["descripcion"] ?? "Producto sin nombre";

    $datos[] = array(
      "id" => (int)($producto["id"] ?? 0),
      "codigo" => (string)$codigo,
      "codigoGeneral" => (string)$codigoGeneral,
      "codigoUnico" => (string)$codigoUnico,
      "descripcion" => (string)$descripcion,
      "categoria" => (string)$categoria,
      "imagen" => tmAdminPrecioImagen($producto["imagen"] ?? ""),
      "stock" => (int)($producto["stock"] ?? 0),
      "precioCompra" => $compra,
      "precioVenta" => $venta,
      "ganancia" => $ganancia,
      "margen" => $margen,
      "fecha" => (string)($producto["fecha"] ?? ""),
      "search" => strtolower($codigo." ".$codigoGeneral." ".$codigoUnico." ".$descripcion." ".$categoria)
    );
  }

  return $datos;
}

$productosAdminPrecios = ControladorProductos::ctrMostrarProductosAlmacen();
$productosAdminPrecios = is_array($productosAdminPrecios) ? $productosAdminPrecios : array();
$productosAdminPreciosData = tmAdminPrecioData($productosAdminPrecios);
$totalProductosAdminPrecios = count($productosAdminPreciosData);
$sinPrecioAdmin = count(array_filter($productosAdminPreciosData, function($producto){
  return (float)$producto["precioCompra"] <= 0 || (float)$producto["precioVenta"] <= 0;
}));
$stockAdmin = array_sum(array_map(function($producto){
  return (int)$producto["stock"];
}, $productosAdminPreciosData));
$valorCompraAdmin = array_sum(array_map(function($producto){
  return ((float)$producto["precioCompra"]) * ((int)$producto["stock"]);
}, $productosAdminPreciosData));
?>

<div class="content-wrapper tm-admin-prices-wrapper">
  <style>
    .tm-admin-prices-hero{
      border:1px solid rgba(184,205,232,.72);
      border-radius:18px;
      background:linear-gradient(135deg, rgba(18,56,79,.96), rgba(52,159,209,.92));
      color:#fff;
      padding:24px;
      margin-bottom:18px;
      box-shadow:0 22px 48px rgba(15,23,42,.14);
      overflow:hidden;
      position:relative;
    }
    .tm-admin-prices-hero:after{
      content:"";
      position:absolute;
      right:-70px;
      top:-90px;
      width:230px;
      height:230px;
      border-radius:50%;
      background:rgba(255,255,255,.12);
    }
    .tm-admin-prices-hero h2{
      margin:0;
      font-weight:950;
      letter-spacing:-.5px;
    }
    .tm-admin-prices-hero p{
      margin:8px 0 0;
      max-width:760px;
      color:rgba(255,255,255,.84);
      font-weight:750;
    }
    .tm-admin-prices-kpis{
      display:grid;
      grid-template-columns:repeat(4, minmax(0, 1fr));
      gap:12px;
      margin-bottom:18px;
    }
    .tm-admin-prices-kpi{
      border:1px solid rgba(184,205,232,.72);
      border-radius:16px;
      background:rgba(255,255,255,.72);
      box-shadow:0 16px 38px rgba(15,23,42,.07);
      padding:16px;
      min-height:96px;
    }
    .tm-admin-prices-kpi span{
      display:block;
      color:#5f7188;
      font-size:12px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-admin-prices-kpi strong{
      display:block;
      margin-top:8px;
      color:#172033;
      font-size:24px;
      font-weight:950;
    }
    .tm-admin-prices-panel{
      border:1px solid rgba(184,205,232,.72);
      border-radius:18px;
      background:rgba(255,255,255,.76);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      overflow:hidden;
    }
    .tm-admin-prices-toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:14px;
      padding:16px;
      border-bottom:1px solid rgba(184,205,232,.62);
      background:rgba(255,255,255,.66);
      flex-wrap:wrap;
    }
    .tm-admin-prices-search{
      position:relative;
      flex:1 1 420px;
    }
    .tm-admin-prices-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#3c8dbc;
    }
    .tm-admin-prices-search input,
    .tm-admin-prices-toolbar select{
      border:1px solid rgba(184,205,232,.88);
      border-radius:12px;
      height:42px;
      background:rgba(255,255,255,.9);
      color:#172033;
      font-weight:850;
      outline:0;
    }
    .tm-admin-prices-search input{
      width:100%;
      padding:10px 14px 10px 38px;
    }
    .tm-admin-prices-toolbar select{
      width:auto;
      padding:8px 12px;
    }
    .tm-admin-prices-body{padding:16px;}
    .tm-admin-prices-viewbar{
      display:flex;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom:12px;
      color:#52657a;
      font-size:12px;
      font-weight:900;
    }
    .tm-admin-prices-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));
      gap:14px;
    }
    .tm-admin-price-card{
      border:1px solid rgba(184,205,232,.76);
      border-radius:16px;
      background:rgba(255,255,255,.86);
      box-shadow:0 14px 34px rgba(15,23,42,.06);
      overflow:hidden;
      transition:.18s ease;
    }
    .tm-admin-price-card:hover{
      transform:translateY(-2px);
      box-shadow:0 18px 44px rgba(15,23,42,.11);
      border-color:rgba(60,141,188,.55);
    }
    .tm-admin-price-img{
      height:132px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(135deg, rgba(240,249,255,.85), rgba(255,255,255,.82));
      border-bottom:1px solid rgba(184,205,232,.55);
      position:relative;
    }
    .tm-admin-price-img img{
      max-width:100%;
      max-height:124px;
      object-fit:contain;
      padding:10px;
    }
    .tm-admin-price-stock{
      position:absolute;
      top:10px;
      right:10px;
      border-radius:999px;
      background:#eaf7ff;
      color:#176b9b;
      padding:5px 9px;
      font-size:11px;
      font-weight:950;
      border:1px solid rgba(60,141,188,.18);
    }
    .tm-admin-price-content{padding:13px;}
    .tm-admin-price-code{
      display:inline-flex;
      align-items:center;
      gap:5px;
      max-width:100%;
      border-radius:9px;
      background:#eef7ff;
      color:#176b9b;
      padding:4px 8px;
      font-size:11px;
      font-weight:950;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }
    .tm-admin-price-content h3{
      margin:9px 0 5px;
      min-height:38px;
      color:#172033;
      font-size:14px;
      line-height:1.32;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-admin-price-content p{
      margin:0 0 10px;
      min-height:30px;
      color:#60758d;
      font-size:11px;
      font-weight:850;
    }
    .tm-admin-price-meta{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:8px;
      margin-bottom:10px;
    }
    .tm-admin-price-meta div{
      border:1px solid #e4edf7;
      border-radius:11px;
      background:#f8fbff;
      padding:8px;
    }
    .tm-admin-price-meta span{
      display:block;
      color:#71839a;
      font-size:10px;
      font-weight:900;
      text-transform:uppercase;
    }
    .tm-admin-price-meta b{
      color:#172033;
      font-size:14px;
      font-weight:950;
    }
    .tm-admin-price-card .btn{
      width:100%;
      border-radius:10px;
      font-weight:950;
      background:#3c8dbc;
      border-color:#3c8dbc;
      box-shadow:0 10px 22px rgba(60,141,188,.24);
    }
    .tm-admin-prices-pagination{
      display:flex;
      justify-content:center;
      gap:6px;
      flex-wrap:wrap;
      margin-top:16px;
    }
    .tm-admin-prices-pagination button{
      border:1px solid rgba(184,205,232,.88);
      background:#fff;
      color:#176b9b;
      border-radius:9px;
      padding:7px 11px;
      font-weight:900;
    }
    .tm-admin-prices-pagination button.active{
      background:#3c8dbc;
      color:#fff;
      border-color:#3c8dbc;
    }
    .tm-admin-price-empty{
      grid-column:1 / -1;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      min-height:210px;
      border:1px dashed rgba(60,141,188,.35);
      border-radius:18px;
      color:#61748b;
      font-weight:850;
      text-align:center;
      background:rgba(255,255,255,.58);
    }
    .tm-admin-price-empty i{font-size:36px;color:#3c8dbc;margin-bottom:10px;}
    .tm-admin-price-modal .modal-dialog{width:min(760px, calc(100vw - 28px));}
    .tm-admin-price-modal .modal-content{
      border:0;
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 28px 70px rgba(15,23,42,.28);
    }
    .tm-admin-price-modal .modal-header{
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#3c8dbc);
      padding:18px 22px;
    }
    .tm-admin-price-modal .modal-title{font-weight:950;}
    .tm-admin-price-modal .close{color:#fff;opacity:.92;text-shadow:none;}
    .tm-admin-price-modal-body{padding:16px;background:#f5f8fc;}
    .tm-admin-price-detail{
      display:grid;
      grid-template-columns:180px 1fr;
      gap:14px;
      margin-bottom:14px;
    }
    .tm-admin-price-preview{
      border:1px solid rgba(184,205,232,.72);
      border-radius:14px;
      background:#fff;
      min-height:160px;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .tm-admin-price-preview img{
      max-width:100%;
      max-height:150px;
      object-fit:contain;
      padding:10px;
    }
    .tm-admin-price-detail-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:9px;
    }
    .tm-admin-price-box{
      border:1px solid rgba(184,205,232,.72);
      border-radius:12px;
      background:#fff;
      padding:9px 11px;
    }
    .tm-admin-price-box span{
      display:block;
      color:#6b7f95;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-admin-price-box strong{
      display:block;
      margin-top:4px;
      color:#172033;
      font-size:13px;
      font-weight:950;
      word-break:break-word;
    }
    .tm-admin-price-form-card{
      border:1px solid rgba(184,205,232,.72);
      border-radius:14px;
      background:#fff;
      padding:14px;
    }
    .tm-admin-price-form-card h4{
      margin:0 0 12px;
      color:#172033;
      font-weight:950;
    }
    .tm-admin-price-form-grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
    }
    .tm-admin-price-form-card label{
      color:#53677f;
      font-size:12px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-admin-price-form-card .input-group-addon{
      border-color:#d8e6f3;
      background:#eaf7ff;
      color:#176b9b;
      font-weight:950;
    }
    .tm-admin-price-form-card .form-control{
      border-color:#d8e6f3;
      box-shadow:none;
      font-weight:950;
    }
    .tm-admin-price-summary{
      margin-top:12px;
      border-radius:12px;
      background:#edf8ff;
      color:#176b9b;
      padding:10px 12px;
      font-weight:900;
    }
    .tm-admin-price-modal .modal-footer{
      border:0;
      padding:14px 16px;
      background:#f5f8fc;
    }
    .tm-admin-price-modal .modal-footer .btn{
      border-radius:9px;
      font-weight:950;
      padding:9px 14px;
    }
    @media (max-width: 991px){
      .tm-admin-prices-kpis{grid-template-columns:repeat(2, minmax(0, 1fr));}
    }
    @media (max-width: 767px){
      .tm-admin-prices-kpis,
      .tm-admin-price-form-grid,
      .tm-admin-price-detail,
      .tm-admin-price-detail-grid{grid-template-columns:1fr;}
    }
  </style>

  <section class="content-header">
    <h1>Editar precios de productos</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      <li>Gestion de Precios</li>
      <li class="active">Editar precios de productos</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-admin-prices-hero">
      <h2><i class="fa fa-tags"></i> Control administrativo de precios</h2>
      <p>Modulo exclusivo para administrador. Desde aqui puedes corregir precio de compra y precio de venta de cualquier producto del inventario, manteniendo historial de cambios.</p>
    </div>

    <div class="tm-admin-prices-kpis">
      <div class="tm-admin-prices-kpi"><span>Productos</span><strong><?php echo number_format($totalProductosAdminPrecios); ?></strong></div>
      <div class="tm-admin-prices-kpi"><span>Sin precio completo</span><strong><?php echo number_format($sinPrecioAdmin); ?></strong></div>
      <div class="tm-admin-prices-kpi"><span>Stock total</span><strong><?php echo number_format($stockAdmin); ?></strong></div>
      <div class="tm-admin-prices-kpi"><span>Valor compra stock</span><strong><?php echo tmAdminPrecioMoney($valorCompraAdmin); ?></strong></div>
    </div>

    <div class="tm-admin-prices-panel">
      <div class="tm-admin-prices-toolbar">
        <div class="tm-admin-prices-search">
          <i class="fa fa-search"></i>
          <input type="search" id="buscarProductoPrecioAdmin" placeholder="Buscar producto, codigo, categoria o codigo general">
        </div>
        <select id="filtroProductoPrecioAdmin">
          <option value="todos">Todos los productos</option>
          <option value="sin_precio">Sin precio completo</option>
          <option value="con_stock">Con stock</option>
          <option value="sin_stock">Sin stock</option>
        </select>
        <select id="porPaginaProductoPrecioAdmin">
          <option value="12">Mostrar 12</option>
          <option value="24" selected>Mostrar 24</option>
          <option value="48">Mostrar 48</option>
          <option value="96">Mostrar 96</option>
        </select>
      </div>
      <div class="tm-admin-prices-body">
        <div class="tm-admin-prices-viewbar">
          <span id="resumenProductoPrecioAdmin">Cargando productos.</span>
          <span>Solo Administrador puede editar esta pantalla.</span>
        </div>
        <div class="tm-admin-prices-grid" id="gridProductoPrecioAdmin"></div>
        <div class="tm-admin-prices-pagination" id="paginacionProductoPrecioAdmin"></div>
      </div>
    </div>
  </section>
</div>

<div id="modalEditarPrecioAdminProducto" class="modal fade tm-admin-price-modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
          <h4 class="modal-title"><i class="fa fa-tag"></i> Editar precios del producto</h4>
        </div>

        <div class="tm-admin-price-modal-body">
          <div class="tm-admin-price-detail">
            <div class="tm-admin-price-preview">
              <img id="adminPrecioImagen" src="vistas/img/productos/default/anonymous.png" alt="Producto">
            </div>
            <div class="tm-admin-price-detail-grid">
              <div class="tm-admin-price-box"><span>Producto</span><strong id="adminPrecioDescripcion">-</strong></div>
              <div class="tm-admin-price-box"><span>Codigo</span><strong id="adminPrecioCodigo">-</strong></div>
              <div class="tm-admin-price-box"><span>Codigo general</span><strong id="adminPrecioCodigoGeneral">-</strong></div>
              <div class="tm-admin-price-box"><span>Codigo unico</span><strong id="adminPrecioCodigoUnico">-</strong></div>
              <div class="tm-admin-price-box"><span>Categoria</span><strong id="adminPrecioCategoria">-</strong></div>
              <div class="tm-admin-price-box"><span>Stock</span><strong id="adminPrecioStock">-</strong></div>
            </div>
          </div>

          <div class="tm-admin-price-form-card">
            <h4><i class="fa fa-calculator"></i> Nuevos montos</h4>
            <div class="tm-admin-price-form-grid">
              <div class="form-group">
                <label for="nuevoPrecioCompra">Precio de compra</label>
                <div class="input-group">
                  <span class="input-group-addon">Bs</span>
                  <input type="number" class="form-control input-lg" id="nuevoPrecioCompra" name="nuevoPrecioCompra" step="0.01" min="0" required>
                </div>
              </div>
              <div class="form-group">
                <label for="nuevoPrecioVenta">Precio de venta</label>
                <div class="input-group">
                  <span class="input-group-addon">Bs</span>
                  <input type="number" class="form-control input-lg" id="nuevoPrecioVenta" name="nuevoPrecioVenta" step="0.01" min="0" required>
                </div>
              </div>
            </div>
            <div class="tm-admin-price-summary" id="adminPrecioResumenMargen">Ganancia: Bs 0.00 | Margen: 0%</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <input type="hidden" id="idProducto" name="idProducto">
          <input type="hidden" name="retornoPrecioProducto" value="productos-precios">
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar precios</button>
        </div>
      </form>

      <?php
        $editarProductoPrecioAdmin = new ControladorProductos();
        $editarProductoPrecioAdmin -> ctrEditarProductoCajero();
      ?>
    </div>
  </div>
</div>

<script>
var tmAdminProductosPrecios = <?php echo json_encode($productosAdminPreciosData, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

(function(){
  var productos = Array.isArray(tmAdminProductosPrecios) ? tmAdminProductosPrecios : [];
  var filtrados = productos.slice();
  var paginaActual = 1;
  var timerBusqueda = null;

  function esc(valor){
    return String(valor == null ? "" : valor).replace(/[&<>"']/g, function(caracter){
      return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[caracter];
    });
  }

  function money(valor){
    valor = Number(valor || 0);
    return valor.toLocaleString("es-BO", {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function renderCard(producto, index){
    return '' +
      '<article class="tm-admin-price-card" data-index="' + index + '">' +
        '<div class="tm-admin-price-img">' +
          '<img src="' + esc(producto.imagen || "vistas/img/productos/default/anonymous.png") + '" alt="' + esc(producto.descripcion) + '" onerror="this.src=\'vistas/img/productos/default/anonymous.png\'">' +
          '<span class="tm-admin-price-stock">Stock ' + esc(producto.stock || 0) + '</span>' +
        '</div>' +
        '<div class="tm-admin-price-content">' +
          '<span class="tm-admin-price-code"><i class="fa fa-barcode"></i> ' + esc(producto.codigo || "Sin codigo") + '</span>' +
          '<h3>' + esc(producto.descripcion || "Producto sin nombre") + '</h3>' +
          '<p><i class="fa fa-folder-open"></i> ' + esc(producto.categoria || "Sin categoria") + '</p>' +
          '<div class="tm-admin-price-meta">' +
            '<div><span>Compra</span><b>Bs ' + money(producto.precioCompra) + '</b></div>' +
            '<div><span>Venta</span><b>Bs ' + money(producto.precioVenta) + '</b></div>' +
          '</div>' +
          '<button type="button" class="btn btn-primary btnEditarPrecioAdminProducto"><i class="fa fa-pencil"></i> Editar precios</button>' +
        '</div>' +
      '</article>';
  }

  function aplicarFiltros(){
    var q = ($("#buscarProductoPrecioAdmin").val() || "").toLowerCase().trim();
    var filtro = $("#filtroProductoPrecioAdmin").val() || "todos";

    filtrados = productos.filter(function(producto){
      var coincide = !q || String(producto.search || "").indexOf(q) !== -1;
      if(!coincide){ return false; }
      if(filtro === "sin_precio"){
        return Number(producto.precioCompra || 0) <= 0 || Number(producto.precioVenta || 0) <= 0;
      }
      if(filtro === "con_stock"){
        return Number(producto.stock || 0) > 0;
      }
      if(filtro === "sin_stock"){
        return Number(producto.stock || 0) <= 0;
      }
      return true;
    });

    paginaActual = 1;
    render();
  }

  function renderPaginacion(totalPaginas){
    if(totalPaginas <= 1){
      $("#paginacionProductoPrecioAdmin").empty();
      return;
    }

    var html = '';
    for(var i = 1; i <= totalPaginas; i++){
      if(i === 1 || i === totalPaginas || Math.abs(i - paginaActual) <= 2){
        html += '<button type="button" class="' + (i === paginaActual ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
      }else if(i === paginaActual - 3 || i === paginaActual + 3){
        html += '<button type="button" disabled>...</button>';
      }
    }
    $("#paginacionProductoPrecioAdmin").html(html);
  }

  function render(){
    var porPagina = Number($("#porPaginaProductoPrecioAdmin").val() || 24);
    var total = filtrados.length;
    var totalPaginas = Math.max(1, Math.ceil(total / porPagina));
    paginaActual = Math.min(paginaActual, totalPaginas);
    var inicio = (paginaActual - 1) * porPagina;
    var pagina = filtrados.slice(inicio, inicio + porPagina);

    if(productos.length === 0){
      $("#gridProductoPrecioAdmin").html('<div class="tm-admin-price-empty"><i class="fa fa-cubes"></i><strong>No hay productos registrados.</strong><span>Cuando exista inventario aparecerá aquí.</span></div>');
      $("#resumenProductoPrecioAdmin").text("Sin productos.");
      $("#paginacionProductoPrecioAdmin").empty();
      return;
    }

    if(total === 0){
      $("#gridProductoPrecioAdmin").html('<div class="tm-admin-price-empty"><i class="fa fa-search"></i><strong>No hay coincidencias.</strong><span>Prueba con otro producto, código o categoría.</span></div>');
      $("#resumenProductoPrecioAdmin").text("0 resultados encontrados.");
      $("#paginacionProductoPrecioAdmin").empty();
      return;
    }

    $("#gridProductoPrecioAdmin").html(pagina.map(function(producto, idx){
      return renderCard(producto, inicio + idx);
    }).join(""));
    $("#resumenProductoPrecioAdmin").text("Mostrando " + (inicio + 1) + " - " + Math.min(inicio + porPagina, total) + " de " + total + " producto(s).");
    renderPaginacion(totalPaginas);
  }

  function actualizarResumen(){
    var compra = Number($("#nuevoPrecioCompra").val() || 0);
    var venta = Number($("#nuevoPrecioVenta").val() || 0);
    var ganancia = venta - compra;
    var margen = compra > 0 ? ((ganancia / compra) * 100) : 0;
    $("#adminPrecioResumenMargen").text("Ganancia: Bs " + money(ganancia) + " | Margen: " + money(margen) + "%");
  }

  function abrirModal(producto){
    if(!producto){ return; }
    $("#idProducto").val(producto.id);
    $("#nuevoPrecioCompra").val(Number(producto.precioCompra || 0).toFixed(2));
    $("#nuevoPrecioVenta").val(Number(producto.precioVenta || 0).toFixed(2));
    $("#adminPrecioImagen").attr("src", producto.imagen || "vistas/img/productos/default/anonymous.png");
    $("#adminPrecioDescripcion").text(producto.descripcion || "Producto sin nombre");
    $("#adminPrecioCodigo").text(producto.codigo || "Sin codigo");
    $("#adminPrecioCodigoGeneral").text(producto.codigoGeneral || "-");
    $("#adminPrecioCodigoUnico").text(producto.codigoUnico || "-");
    $("#adminPrecioCategoria").text(producto.categoria || "Sin categoria");
    $("#adminPrecioStock").text(producto.stock || "0");
    actualizarResumen();
    $("#modalEditarPrecioAdminProducto").modal("show");
  }

  $("#buscarProductoPrecioAdmin").on("input", function(){
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(aplicarFiltros, 180);
  });

  $("#filtroProductoPrecioAdmin, #porPaginaProductoPrecioAdmin").on("change", aplicarFiltros);

  $(document).on("click", ".btnEditarPrecioAdminProducto", function(){
    abrirModal(filtrados[Number($(this).closest(".tm-admin-price-card").attr("data-index"))]);
  });

  $(document).on("input", "#nuevoPrecioCompra, #nuevoPrecioVenta", actualizarResumen);

  $("#paginacionProductoPrecioAdmin").on("click", "button[data-page]", function(){
    paginaActual = Number($(this).attr("data-page") || 1);
    render();
    $("html, body").animate({scrollTop: $(".tm-admin-prices-panel").offset().top - 80}, 180);
  });

  render();
})();
</script>
