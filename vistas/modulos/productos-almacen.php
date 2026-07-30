<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

function tmAlmacenEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$stockCeroAlmacen = 0;
$stockBajoAlmacen = 0;

try{
  $dbStockAlmacen = Conexion::conectar();
  $stockCeroAlmacen = (int)$dbStockAlmacen->query("SELECT COUNT(*) FROM productos WHERE stock <= 0")->fetchColumn();
  $stockBajoAlmacen = (int)$dbStockAlmacen->query("SELECT COUNT(*) FROM productos WHERE stock > 0 AND stock <= 3")->fetchColumn();
}catch(Exception $e){
  $stockCeroAlmacen = 0;
  $stockBajoAlmacen = 0;
}

$item = null;
$valor = null;
$categorias = ControladorCategorias::ctrMostrarCategorias($item, $valor);
$categorias = is_array($categorias) ? $categorias : array();
$marcasProducto = ControladorProductos::ctrMostrarMarcasActivas();
$marcasProducto = is_array($marcasProducto) ? $marcasProducto : array();

?>

<div class="content-wrapper tm-products-warehouse-page">
  <style>
    .tm-products-warehouse-page .content{padding-top:10px;}
    .tm-warehouse-hero{
      border-radius:22px;
      padding:18px;
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
    .tm-warehouse-hero:after{
      content:"";
      position:absolute;
      right:-85px;
      top:-110px;
      width:260px;
      height:260px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-warehouse-hero-copy{
      display:flex;
      align-items:center;
      gap:14px;
      position:relative;
      z-index:1;
      min-width:0;
    }
    .tm-warehouse-hero-icon{
      width:56px;
      height:56px;
      border-radius:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.24);
      font-size:24px;
      flex:0 0 auto;
    }
    .tm-warehouse-hero h2{
      margin:0;
      font-size:25px;
      font-weight:950;
    }
    .tm-warehouse-hero p{
      margin:5px 0 0;
      max-width:760px;
      color:rgba(255,255,255,.88);
      font-weight:750;
    }
    .tm-warehouse-metrics{
      display:grid;
      grid-template-columns:repeat(3, minmax(104px, 1fr));
      gap:10px;
      min-width:360px;
      position:relative;
      z-index:1;
    }
    .tm-warehouse-metric{
      border:1px solid rgba(255,255,255,.26);
      border-radius:16px;
      padding:11px 12px;
      text-align:center;
      background:rgba(255,255,255,.12);
    }
    .tm-warehouse-metric strong{
      display:block;
      font-size:27px;
      line-height:1;
      font-weight:950;
    }
    .tm-warehouse-metric span{
      display:block;
      margin-top:4px;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      color:rgba(255,255,255,.82);
    }
    .tm-warehouse-alert{
      border:0;
      border-radius:16px;
      padding:13px 15px;
      margin-bottom:14px;
      background:rgba(243,156,18,.14);
      color:#8a5a00;
      font-weight:850;
      box-shadow:0 12px 26px rgba(15,23,42,.06);
    }
    .tm-warehouse-toolbar{
      border:1px solid rgba(184,205,232,.70);
      border-radius:18px;
      background:rgba(255,255,255,.72);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      padding:13px;
      display:grid;
      grid-template-columns:minmax(260px, 1fr) auto auto;
      gap:11px;
      align-items:center;
      margin-bottom:14px;
    }
    .tm-warehouse-search{
      position:relative;
      min-width:0;
    }
    .tm-warehouse-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#3c8dbc;
    }
    .tm-warehouse-search input,
    .tm-warehouse-toolbar select{
      width:100%;
      border:1px solid rgba(184,205,232,.85);
      border-radius:13px;
      padding:11px 13px;
      background:rgba(255,255,255,.88);
      color:#1f2d3d;
      font-weight:850;
      outline:0;
      height:43px;
    }
    .tm-warehouse-search input{padding-left:38px;}
    .tm-warehouse-toolbar .btn{
      border-radius:999px;
      font-weight:900;
      padding:10px 15px;
      box-shadow:0 10px 20px rgba(23,107,155,.16);
    }
    .tm-warehouse-filter-row{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-bottom:14px;
    }
    .tm-stock-filter{
      border:1px solid rgba(184,205,232,.78);
      border-radius:999px;
      background:rgba(255,255,255,.70);
      color:#1f4f75;
      padding:8px 12px;
      font-weight:950;
      box-shadow:0 10px 20px rgba(15,23,42,.05);
    }
    .tm-stock-filter.is-active{
      background:#176b9b;
      border-color:#176b9b;
      color:#fff;
    }
    .tm-products-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(286px, 1fr));
      gap:13px;
      align-items:stretch;
    }
    .tm-product-card{
      border:1px solid rgba(184,205,232,.74);
      border-radius:18px;
      background:rgba(255,255,255,.84);
      box-shadow:0 14px 30px rgba(15,23,42,.08);
      min-height:318px;
      display:flex;
      flex-direction:column;
      overflow:hidden;
      position:relative;
      transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .tm-product-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 18px 34px rgba(15,23,42,.12);
    }
    .tm-product-visual{
      position:relative;
      background:linear-gradient(135deg, rgba(232,243,252,.95), rgba(255,255,255,.78));
      border-bottom:1px solid rgba(184,205,232,.58);
    }
    .tm-product-image{
      height:104px;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:9px 12px;
    }
    .tm-product-image img{
      max-width:100%;
      max-height:86px;
      object-fit:contain;
      display:block;
    }
    .tm-product-stock-wrap{
      position:absolute;
      right:9px;
      top:9px;
      display:flex;
      flex-direction:column;
      align-items:flex-end;
      gap:4px;
    }
    .tm-product-stock-label{
      color:#60758d;
      font-size:9px;
      font-weight:950;
      text-transform:uppercase;
      background:rgba(255,255,255,.86);
      border:1px solid rgba(184,205,232,.72);
      border-radius:999px;
      padding:3px 7px;
    }
    .tm-product-body{
      padding:11px 12px 10px;
      display:flex;
      flex-direction:column;
      gap:7px;
      flex:1;
    }
    .tm-product-top{
      display:flex;
      flex-direction:column;
      gap:6px;
      align-items:stretch;
    }
    .tm-product-code{
      display:inline-flex;
      width:max-content;
      max-width:100%;
      border-radius:999px;
      padding:5px 8px;
      background:#e8f3fc;
      color:#176b9b;
      font-size:10px;
      font-weight:950;
      overflow-wrap:anywhere;
      line-height:1.1;
    }
    .tm-product-stock{
      display:inline-flex;
      justify-content:center;
      align-items:center;
      min-width:42px;
      min-height:28px;
      border-radius:999px;
      padding:5px 9px;
      color:#fff;
      font-size:13px;
      font-weight:950;
      text-align:center;
      box-shadow:0 10px 20px rgba(15,23,42,.13);
    }
    .tm-product-stock.stock-ok{background:#00a65a;}
    .tm-product-stock.stock-low{background:#f39c12;}
    .tm-product-stock.stock-zero{background:#dd4b39;}
    .tm-product-title{
      margin:0;
      color:#1f2d3d;
      font-size:14px;
      line-height:1.28;
      font-weight:950;
      overflow-wrap:anywhere;
      display:-webkit-box;
      -webkit-line-clamp:2;
      -webkit-box-orient:vertical;
      overflow:hidden;
    }
    .tm-product-category{
      display:flex;
      align-items:flex-start;
      gap:7px;
      color:#526a84;
      font-size:11px;
      font-weight:850;
      line-height:1.3;
      overflow-wrap:anywhere;
      min-height:28px;
    }
    .tm-product-category i{
      color:#3c8dbc;
      margin-top:2px;
      flex:0 0 auto;
    }
    .tm-product-meta{
      display:flex;
      flex-direction:column;
      gap:5px;
      margin-top:auto;
    }
    .tm-product-info{
      border:1px solid rgba(184,205,232,.62);
      border-radius:10px;
      background:rgba(248,251,255,.82);
      padding:7px 9px;
      min-width:0;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
    }
    .tm-product-info span{
      display:block;
      color:#6a7a8f;
      font-size:9px;
      font-weight:950;
      text-transform:uppercase;
      flex:0 0 auto;
    }
    .tm-product-info strong{
      display:block;
      color:#24364d;
      font-size:11px;
      font-weight:950;
      overflow-wrap:anywhere;
      text-align:right;
      min-width:0;
    }
    .tm-product-actions{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      align-items:center;
      justify-content:flex-start;
      border-top:1px dashed rgba(184,205,232,.76);
      padding:10px 12px 12px;
      min-height:52px;
      background:rgba(248,251,255,.48);
    }
    .tm-product-actions .btn-group{display:none !important;}
    .tm-action-row{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      width:100%;
      align-items:center;
    }
    .tm-product-actions .btn,
    .tm-product-actions button,
    .tm-action-btn{
      border:0;
      border-radius:10px !important;
      min-height:32px;
      font-weight:900;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:5px;
      box-shadow:0 8px 16px rgba(15,23,42,.08);
      padding:7px 9px;
      line-height:1.1;
      white-space:normal;
      color:#fff;
      flex:1 1 104px;
      max-width:100%;
    }
    .tm-action-detail{background:#176b9b;}
    .tm-action-codes{background:#00a7d0;}
    .tm-action-print{background:#605ca8;}
    .tm-action-edit{background:#f39c12;}
    .tm-action-delete{background:#dd4b39;}
    .tm-action-btn:hover,
    .tm-action-btn:focus{
      filter:brightness(.96);
      color:#fff;
      outline:0;
    }
    .tm-warehouse-empty{
      grid-column:1/-1;
      min-height:220px;
      border:1px dashed rgba(60,141,188,.35);
      border-radius:18px;
      color:#6d7f93;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:8px;
      text-align:center;
      background:rgba(255,255,255,.58);
    }
    .tm-warehouse-empty i{font-size:36px;color:#3c8dbc;}
    .tm-warehouse-empty strong{color:#1f2d3d;font-weight:950;}
    .tm-warehouse-pager{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
      margin-top:14px;
      border:1px solid rgba(184,205,232,.70);
      border-radius:16px;
      background:rgba(255,255,255,.70);
      padding:11px 12px;
      color:#60758d;
      font-weight:850;
    }
    .tm-warehouse-pages{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      justify-content:flex-end;
    }
    .tm-warehouse-pages button{
      border:1px solid rgba(184,205,232,.85);
      border-radius:10px;
      min-width:34px;
      height:32px;
      background:#fff;
      color:#176b9b;
      font-weight:950;
    }
    .tm-warehouse-pages button.is-active{
      background:#176b9b;
      color:#fff;
      border-color:#176b9b;
    }
    .tm-product-modal .modal-dialog{
      width:min(980px, calc(100vw - 34px));
      margin:22px auto;
    }
    .tm-product-modal .modal-content{
      border:0;
      border-radius:24px;
      overflow:hidden;
      box-shadow:0 30px 76px rgba(15,23,42,.30);
    }
    .tm-product-modal .modal-header{
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#3c8dbc);
      padding:18px 22px;
      position:relative;
      overflow:hidden;
    }
    .tm-product-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-45px;
      top:-72px;
      width:176px;
      height:176px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-product-modal .modal-title{
      position:relative;
      z-index:1;
      display:flex;
      align-items:center;
      gap:13px;
      font-size:21px;
      line-height:1.2;
      font-weight:950;
    }
    .tm-product-modal-icon{
      width:46px;
      height:46px;
      border-radius:16px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.20);
      flex:0 0 auto;
    }
    .tm-product-modal .modal-title small{
      display:block;
      margin-top:4px;
      color:rgba(255,255,255,.84);
      font-size:12px;
      font-weight:800;
    }
    .tm-product-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.9;
      text-shadow:none;
    }
    .tm-product-modal-body{
      padding:18px;
      background:linear-gradient(180deg,#f5f8fc,#eef5fb);
    }
    .tm-product-detail-layout{
      display:grid;
      grid-template-columns:280px minmax(0, 1fr);
      gap:16px;
      align-items:stretch;
    }
    .tm-product-detail-image{
      border:1px solid rgba(184,205,232,.74);
      border-radius:20px;
      background:linear-gradient(135deg,#fff,#eef7ff);
      padding:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:275px;
      box-shadow:0 16px 34px rgba(15,23,42,.07);
    }
    .tm-product-detail-image img{
      max-width:100%;
      max-height:245px;
      object-fit:contain;
    }
    .tm-product-detail-grid,
    .tm-product-form-grid{
      display:grid;
      grid-template-columns:repeat(2, minmax(0, 1fr));
      gap:10px;
    }
    .tm-product-detail-box,
    .tm-product-field{
      border:1px solid rgba(184,205,232,.74);
      border-radius:16px;
      background:rgba(255,255,255,.92);
      padding:12px;
      min-width:0;
      box-shadow:0 10px 22px rgba(15,23,42,.045);
    }
    .tm-product-detail-box span,
    .tm-product-field label{
      display:block;
      color:#4d6178;
      font-size:10.5px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:6px;
    }
    .tm-product-detail-box strong{
      display:block;
      color:#203047;
      font-size:14px;
      font-weight:950;
      overflow-wrap:anywhere;
      line-height:1.28;
    }
    .tm-product-detail-box.is-wide{grid-column:1/-1;}
    .tm-product-field .input-group-addon{
      border-color:#d6e1ee;
      background:#eef5fc;
      color:#176b9b;
      font-weight:900;
    }
    .tm-product-field .form-control{
      border-color:#d6e1ee;
      border-radius:8px;
      font-weight:850;
      height:39px;
    }
    .tm-product-field.is-wide{grid-column:1/-1;}
    .tm-product-image-field{
      display:flex;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
    }
    .tm-product-image-field img{
      width:82px;
      height:82px;
      object-fit:contain;
      border-radius:14px;
      background:#fff;
    }
    .tm-product-modal .modal-footer{
      border:0;
      padding:15px 18px;
      background:#eef5fb;
      display:flex;
      gap:9px;
      flex-wrap:wrap;
      justify-content:flex-end;
      align-items:center;
    }
    .tm-product-modal .modal-footer .btn{
      border-radius:12px;
      font-weight:900;
      padding:9px 14px;
      min-height:38px;
    }
    .tm-product-modal .modal-footer .pull-left{margin-right:auto;}
    .tm-product-modal .modal-footer .tm-action-row{
      width:auto;
      flex:1 1 auto;
      justify-content:flex-end;
    }
    .tm-product-modal .modal-footer .tm-action-btn{
      flex:0 1 132px;
    }
    .sweet-alert,
    .sweet-overlay,
    .swal2-container,
    .swal-overlay{
      z-index:20000 !important;
    }
    .tm-delete-modal .modal-dialog{width:min(520px, calc(100vw - 28px));}
    .tm-delete-modal .modal-content{
      border:0;
      border-radius:22px;
      overflow:hidden;
      box-shadow:0 30px 76px rgba(15,23,42,.32);
    }
    .tm-delete-head{
      padding:20px;
      display:flex;
      gap:13px;
      align-items:center;
      color:#fff;
      background:linear-gradient(135deg,#7f1d1d,#dd4b39);
    }
    .tm-delete-head i{
      width:48px;
      height:48px;
      border-radius:16px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.22);
      font-size:22px;
      flex:0 0 auto;
    }
    .tm-delete-head h4{margin:0 0 4px;font-size:20px;font-weight:950;}
    .tm-delete-head p{margin:0;color:rgba(255,255,255,.84);font-weight:800;}
    .tm-delete-body{
      padding:18px 20px;
      background:#f8fbff;
      color:#203047;
    }
    .tm-delete-body strong{
      display:block;
      margin-top:10px;
      border:1px solid rgba(184,205,232,.78);
      border-radius:14px;
      padding:11px 12px;
      background:#fff;
      color:#173b5d;
      overflow-wrap:anywhere;
    }
    .tm-delete-footer{
      display:flex;
      justify-content:flex-end;
      gap:9px;
      padding:0 20px 18px;
      background:#f8fbff;
    }
    .tm-delete-footer .btn{
      border-radius:12px;
      font-weight:950;
      min-width:128px;
      min-height:38px;
    }
    .tm-codes-list{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));
      gap:9px;
      max-height:420px;
      overflow:auto;
      padding-right:4px;
    }
    .tm-code-unit{
      border:1px solid rgba(184,205,232,.74);
      border-radius:14px;
      background:#fff;
      padding:10px;
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:center;
    }
    .tm-code-unit strong{
      color:#203047;
      overflow-wrap:anywhere;
    }
    .tm-code-unit small{
      display:block;
      color:#6a7a8f;
      font-weight:800;
      margin-top:3px;
    }
    body.tm-dark-mode .tm-warehouse-toolbar,
    body.tm-dark-mode .tm-product-card,
    body.tm-dark-mode .tm-warehouse-pager,
    body.dark-mode .tm-warehouse-toolbar,
    body.dark-mode .tm-product-card,
    body.dark-mode .tm-warehouse-pager{
      background:rgba(15,23,42,.64);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-product-title,
    body.tm-dark-mode .tm-product-info strong,
    body.tm-dark-mode .tm-warehouse-empty strong,
    body.dark-mode .tm-product-title,
    body.dark-mode .tm-product-info strong,
    body.dark-mode .tm-warehouse-empty strong{
      color:#f8fbff;
    }
    body.tm-dark-mode .tm-product-info,
    body.tm-dark-mode .tm-product-detail-box,
    body.dark-mode .tm-product-info,
    body.dark-mode .tm-product-detail-box{
      background:rgba(255,255,255,.06);
      border-color:rgba(255,255,255,.13);
    }
    body.tm-dark-mode .tm-product-detail-box strong,
    body.dark-mode .tm-product-detail-box strong{color:#f8fbff;}
    @media (max-width: 991px){
      .tm-warehouse-hero{flex-direction:column;align-items:stretch;}
      .tm-warehouse-metrics{min-width:0;}
      .tm-warehouse-toolbar{grid-template-columns:1fr;}
      .tm-product-detail-layout{grid-template-columns:1fr;}
    }
    @media (max-width: 767px){
      .tm-warehouse-hero-copy{align-items:flex-start;}
      .tm-warehouse-metrics{grid-template-columns:1fr;}
      .tm-product-form-grid,
      .tm-product-detail-grid{grid-template-columns:1fr;}
      .tm-product-modal .modal-dialog{width:calc(100vw - 18px);margin:9px auto;}
    }
  </style>

  <section class="content-header">
    <h1>Administrar productos almacen</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Productos almacen</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-warehouse-hero">
      <div class="tm-warehouse-hero-copy">
        <div class="tm-warehouse-hero-icon"><i class="fa fa-dropbox"></i></div>
        <div>
          <h2>Inventario de almacen</h2>
          <p>Controla productos, stock, imagenes y codigos unicos sin perder espacio en tablas largas.</p>
        </div>
      </div>
      <div class="tm-warehouse-metrics">
        <div class="tm-warehouse-metric">
          <strong id="tmTotalProductosAlmacen">0</strong>
          <span>Productos</span>
        </div>
        <div class="tm-warehouse-metric">
          <strong><?php echo (int)$stockCeroAlmacen; ?></strong>
          <span>Sin stock</span>
        </div>
        <div class="tm-warehouse-metric">
          <strong><?php echo (int)$stockBajoAlmacen; ?></strong>
          <span>Stock bajo</span>
        </div>
      </div>
    </div>

    <?php if($stockCeroAlmacen > 0 || $stockBajoAlmacen > 0): ?>
      <div class="tm-warehouse-alert">
        <i class="fa fa-warning"></i>
        <strong>Notificacion de stock:</strong>
        <?php echo (int)$stockCeroAlmacen; ?> producto(s) sin stock y
        <?php echo (int)$stockBajoAlmacen; ?> producto(s) con stock bajo.
      </div>
    <?php endif; ?>

    <div class="tm-warehouse-toolbar">
      <div class="tm-warehouse-search">
        <i class="fa fa-search"></i>
        <input type="text" id="buscarProductoAlmacen" placeholder="Buscar producto, codigo, categoria o codigo general">
      </div>
      <select id="cantidadProductosAlmacen">
        <option value="16">Mostrar 16</option>
        <option value="32">Mostrar 32</option>
        <option value="50">Mostrar 50</option>
        <option value="100">Mostrar 100</option>
      </select>
      <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarProducto">
        <i class="fa fa-plus"></i> Agregar producto
      </button>
    </div>

    <div class="tm-warehouse-filter-row">
      <button type="button" class="tm-stock-filter is-active" data-filtro="todos">Todos</button>
      <button type="button" class="tm-stock-filter" data-filtro="stock">Con stock</button>
      <button type="button" class="tm-stock-filter" data-filtro="bajo">Stock bajo</button>
      <button type="button" class="tm-stock-filter" data-filtro="cero">Sin stock</button>
    </div>

    <div id="gridProductosAlmacen" class="tm-products-grid">
      <div class="tm-warehouse-empty">
        <i class="fa fa-spinner fa-spin"></i>
        <strong>Cargando productos...</strong>
        <span>Un momento por favor.</span>
      </div>
    </div>

    <div class="tm-warehouse-pager">
      <span id="resumenProductosAlmacen">Mostrando 0 productos</span>
      <div class="tm-warehouse-pages" id="paginasProductosAlmacen"></div>
    </div>

    <input type="hidden" value="<?php echo tmAlmacenEsc($_SESSION['perfil']); ?>" id="perfilOculto">
    <input type="hidden" value="<?php echo tmAlmacenEsc($_SESSION['rol']); ?>" id="rolOculto">
  </section>
</div>

<div id="modalAgregarProducto" class="modal fade tm-product-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">
            <span class="tm-product-modal-icon"><i class="fa fa-plus"></i></span>
            <span>Agregar producto<small>Almacen crea el producto sin asignar precio de venta.</small></span>
          </h4>
        </div>

        <div class="tm-product-modal-body">
          <div class="tm-product-form-grid">
            <div class="tm-product-field is-wide">
              <label>Categoria</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-th"></i></span>
                <select class="form-control" id="nuevaCategoria" name="nuevaCategoria" required>
                  <option value="">Seleccionar categoria</option>
                  <?php foreach($categorias as $value): ?>
                    <?php if(empty($value["id_padre"])): ?>
                      <option value="" disabled>-- <?php echo tmAlmacenEsc($value["categoria"]); ?> --</option>
                    <?php else: ?>
                      <option value="<?php echo (int)$value["id"]; ?>"><?php echo tmAlmacenEsc($value["ruta_categoria"] ?? $value["categoria"]); ?></option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="tm-product-field">
              <label>Marca</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-tag"></i></span>
                <select class="form-control" id="nuevaMarca" name="nuevaMarca">
                  <option value="0">Sin marca / generico</option>
                  <?php foreach($marcasProducto as $marcaProducto): ?>
                    <option value="<?php echo (int)$marcaProducto["id_marca"]; ?>"><?php echo tmAlmacenEsc($marcaProducto["nombre"]); ?></option>
                  <?php endforeach; ?>
                </select>
                <span class="input-group-btn">
                  <button type="button" class="btn btn-info btnNuevaMarcaProducto" data-selector="#nuevaMarca" title="Registrar una marca nueva">
                    <i class="fa fa-plus"></i> Nueva
                  </button>
                </span>
              </div>
            </div>

            <div class="tm-product-field">
              <label>Codigo</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-code"></i></span><input type="text" class="form-control" id="nuevoCodigo" name="nuevoCodigo" placeholder="Ingresar codigo" required></div>
            </div>

            <div class="tm-product-field">
              <label>Codigo general</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-barcode"></i></span><input type="text" class="form-control" name="nuevoCodigoGenerico" placeholder="Codigo general" required></div>
            </div>

            <div class="tm-product-field is-wide">
              <label>Descripcion</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-product-hunt"></i></span><input type="text" class="form-control" name="nuevaDescripcion" placeholder="Ingresar descripcion" required></div>
            </div>

            <div class="tm-product-field is-wide">
              <label>Características principales</label>
              <textarea class="form-control" name="nuevoDetalle" rows="6" maxlength="5000" placeholder="Escriba una característica por línea. Ejemplo:&#10;Resolución: 1920 x 1080.&#10;Conectividad: HDMI y USB.&#10;Garantía: 12 meses."></textarea>
              <span class="help-block">Esta información se mostrará en el detalle de la tarjeta del catálogo web. Se recomienda escribir una característica por línea.</span>
            </div>

            <div class="tm-product-field">
              <label>Codigo unico inicial</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-barcode"></i></span><input type="text" class="form-control" name="nuevoCodigoUnico" placeholder="Codigo unico" required></div>
            </div>

            <div class="tm-product-field">
              <label>Stock inicial</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-cubes"></i></span><input type="number" class="form-control" name="nuevoStock" value="0" min="0" readonly required></div>
            </div>

            <div class="tm-product-field">
              <label>Precio de compra</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control" id="nuevoPrecioCompra" name="nuevoPrecioCompra" step="any" min="0" value="0" readonly></div>
            </div>

            <div class="tm-product-field">
              <label>Precio de venta</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control" id="nuevoPrecioVenta" name="nuevoPrecioVenta" step="any" min="0" value="0" readonly></div>
            </div>

            <div class="tm-product-field is-wide">
              <label>Imagen</label>
              <div class="tm-product-image-field">
                <input type="file" class="nuevaImagen" name="nuevaImagen">
                <img src="vistas/img/productos/default/anonymous.png" class="img-thumbnail previsualizar" alt="Previsualizacion">
                <span class="help-block">Peso maximo 2MB. Formato JPG o PNG.</span>
              </div>
            </div>

            <input type="hidden" class="nuevoPorcentaje" value="0">
            <input type="hidden" name="retornoProducto" value="productos-almacen">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar producto</button>
        </div>
      </form>
      <?php
        $crearProducto = new ControladorProductos();
        $crearProducto -> ctrCrearProducto();
      ?>
    </div>
  </div>
</div>

<div id="modalEditarProducto" class="modal fade tm-product-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">
            <span class="tm-product-modal-icon"><i class="fa fa-pencil"></i></span>
            <span>Editar producto<small>Actualiza datos de almacen. Los precios los define caja/admin.</small></span>
          </h4>
        </div>

        <div class="tm-product-modal-body">
          <div class="tm-product-form-grid">
            <div class="tm-product-field is-wide">
              <label>Categoria</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-th"></i></span>
                <select class="form-control" name="editarCategoria" readonly required>
                  <option id="editarCategoria"></option>
                </select>
              </div>
            </div>

            <div class="tm-product-field">
              <label>Marca</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-tag"></i></span>
                <select class="form-control" id="editarMarca" name="editarMarca">
                  <option value="0">Sin marca / generico</option>
                  <?php foreach($marcasProducto as $marcaProducto): ?>
                    <option value="<?php echo (int)$marcaProducto["id_marca"]; ?>"><?php echo tmAlmacenEsc($marcaProducto["nombre"]); ?></option>
                  <?php endforeach; ?>
                </select>
                <span class="input-group-btn">
                  <button type="button" class="btn btn-info btnNuevaMarcaProducto" data-selector="#editarMarca" title="Registrar una marca nueva">
                    <i class="fa fa-plus"></i> Nueva
                  </button>
                </span>
              </div>
            </div>

            <div class="tm-product-field">
              <label>Codigo</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-code"></i></span><input type="text" class="form-control" id="editarCodigo" name="editarCodigo" required></div>
            </div>
            <input type="hidden" id="editarIdProducto" name="editarIdProducto">

            <div class="tm-product-field">
              <label>Codigo general</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-barcode"></i></span><input type="text" class="form-control" id="editarCodigoGenerico" name="editarCodigoGenerico" required></div>
            </div>

            <div class="tm-product-field is-wide">
              <label>Descripcion</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-product-hunt"></i></span><input type="text" class="form-control" id="editarDescripcion" name="editarDescripcion" required></div>
            </div>

            <div class="tm-product-field is-wide">
              <label>Características principales</label>
              <textarea class="form-control" id="editarDetalle" name="editarDetalle" rows="6" maxlength="5000" placeholder="Escriba una característica por línea"></textarea>
              <span class="help-block">Este contenido aparece en la sección “Características principales” del catálogo público.</span>
            </div>

            <div class="tm-product-field">
              <label>Codigo unico</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-barcode"></i></span><input type="text" class="form-control" id="editarCodigoUnico" name="editarCodigoUnico" required></div>
            </div>

            <div class="tm-product-field">
              <label>Stock</label>
              <div class="input-group"><span class="input-group-addon"><i class="fa fa-cubes"></i></span><input type="number" class="form-control" id="editarStock" name="editarStock" min="0" required></div>
            </div>

            <div class="tm-product-field">
              <label>Precio de compra</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control" id="editarPrecioCompra" name="editarPrecioCompra" step="any" min="0" value="0" readonly></div>
            </div>

            <div class="tm-product-field">
              <label>Precio de venta</label>
              <div class="input-group"><span class="input-group-addon">Bs</span><input type="number" class="form-control" id="editarPrecioVenta" name="editarPrecioVenta" step="any" min="0" value="0" readonly></div>
            </div>

            <div class="tm-product-field is-wide">
              <label>Imagen</label>
              <div class="tm-product-image-field">
                <input type="file" class="nuevaImagen" name="editarImagen">
                <img src="vistas/img/productos/default/anonymous.png" class="img-thumbnail previsualizar" alt="Previsualizacion">
                <input type="hidden" name="imagenActual" id="imagenActual">
                <span class="help-block">Peso maximo 2MB. Formato JPG o PNG.</span>
              </div>
            </div>

            <input type="hidden" class="nuevoPorcentaje" value="0">
            <input type="hidden" name="retornoProducto" value="productos-almacen">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
        </div>
      </form>
      <?php
        $editarProducto = new ControladorProductos();
        $editarProducto -> ctrEditarProducto();
      ?>
    </div>
  </div>
</div>

<div id="modalNuevaMarcaProducto" class="modal fade tm-product-modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog" style="max-width:520px">
    <div class="modal-content">
      <form id="formNuevaMarcaProducto">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">
            <span class="tm-product-modal-icon"><i class="fa fa-tag"></i></span>
            <span>Agregar nueva marca<small>La marca quedara disponible para todos los productos.</small></span>
          </h4>
        </div>
        <div class="tm-product-modal-body">
          <div class="tm-product-field" style="margin-bottom:14px">
            <label>Nombre de la marca</label>
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-copyright"></i></span>
              <input type="text" class="form-control" id="nombreMarcaProducto" maxlength="100" placeholder="Ej.: PHILIPS, ASUS, ACER" required>
            </div>
          </div>
          <div class="tm-product-field">
            <label>Descripcion opcional</label>
            <textarea class="form-control" id="descripcionMarcaProducto" rows="3" maxlength="500" placeholder="Informacion adicional sobre la marca"></textarea>
          </div>
          <input type="hidden" id="selectorMarcaProducto" value="#nuevaMarca">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="guardarNuevaMarcaProducto">
            <i class="fa fa-save"></i> Guardar marca
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  #modalNuevaMarcaProducto{z-index:1070;}
  .modal-backdrop.tm-marca-backdrop{z-index:1060;}
</style>

<div id="modalDetalleProductoAlmacen" class="modal fade tm-product-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">
          <span class="tm-product-modal-icon"><i class="fa fa-dropbox"></i></span>
          <span id="detalleProductoTitulo">Detalle de producto<small id="detalleProductoCodigo">Producto de almacen</small></span>
        </h4>
      </div>
      <div class="tm-product-modal-body">
        <div class="tm-product-detail-layout">
          <div class="tm-product-detail-image"><img id="detalleProductoImagen" src="vistas/img/productos/default/anonymous.png" alt="Producto" onerror="this.onerror=null;this.src='vistas/img/productos/default/anonymous.png';"></div>
          <div class="tm-product-detail-grid">
            <div class="tm-product-detail-box"><span>Descripcion</span><strong id="detalleProductoDescripcion">-</strong></div>
            <div class="tm-product-detail-box"><span>Categoria</span><strong id="detalleProductoCategoria">-</strong></div>
            <div class="tm-product-detail-box"><span>Codigo general</span><strong id="detalleProductoCodigoGeneral">-</strong></div>
            <div class="tm-product-detail-box"><span>Stock</span><strong id="detalleProductoStock">0</strong></div>
            <div class="tm-product-detail-box"><span>Precio compra</span><strong id="detalleProductoCompra">Bs 0.00</strong></div>
            <div class="tm-product-detail-box"><span>Precio venta</span><strong id="detalleProductoVenta">Bs 0.00</strong></div>
            <div class="tm-product-detail-box is-wide"><span>Agregado</span><strong id="detalleProductoFecha">-</strong></div>
          </div>
        </div>
      </div>
      <div class="modal-footer" id="detalleProductoAcciones"></div>
    </div>
  </div>
</div>

<div id="modalCodigosUnicosProducto" class="modal fade tm-product-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">
          <span class="tm-product-modal-icon"><i class="fa fa-barcode"></i></span>
          <span>Codigos unicos<small id="tituloCodigosUnicos"></small></span>
        </h4>
      </div>
      <div class="tm-product-modal-body">
        <div class="tm-warehouse-alert">
          <i class="fa fa-info-circle"></i> Codigos reales por unidad. Para entregar, almacen valida uno disponible.
        </div>
        <div class="tm-codes-list" id="listaCodigosUnicosProducto"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<div id="modalEliminarProductoAlmacen" class="modal fade tm-delete-modal" role="dialog" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="tm-delete-head">
        <i class="fa fa-trash"></i>
        <div>
          <h4>Eliminar producto</h4>
          <p>Confirma esta accion solo si estas seguro.</p>
        </div>
      </div>
      <div class="tm-delete-body">
        <p>Este producto se quitara del sistema. Si tiene movimientos asociados, revisa antes de continuar.</p>
        <strong id="eliminarProductoCodigo">Producto seleccionado</strong>
        <input type="hidden" id="eliminarProductoId">
        <input type="hidden" id="eliminarProductoImagen">
      </div>
      <div class="tm-delete-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">
          <i class="fa fa-times"></i> Cancelar
        </button>
        <button type="button" class="btn btn-danger" id="confirmarEliminarProductoAlmacen">
          <i class="fa fa-check"></i> Si, eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  var productosAlmacen = [];
  var productosFiltrados = [];
  var paginaActual = 1;
  var porPagina = 16;
  var filtroStock = "todos";
  var productosCargados = false;
  var buscandoDuranteCarga = false;
  var temporizadorBusqueda = null;
  var endpointProductos = "ajax/datatable-productos.ajax.php?perfilOculto=<?php echo rawurlencode($_SESSION['perfil']); ?>&rolOculto=<?php echo rawurlencode($_SESSION['rol']); ?>";
  var defaultImage = "vistas/img/productos/default/anonymous.png";

  function limpiarHtml(html){
    return $("<div>").html(html || "").text().replace(/\s+/g, " ").trim();
  }

  function extraerSrc(html){
    var src = $("<div>").html(html || "").find("img").attr("src") || "";
    return normalizarImagen(src);
  }

  function normalizarImagen(src){
    src = (src || "").trim();
    if(!src || src === "null" || src === "undefined"){
      return defaultImage;
    }
    return src;
  }

  function extraerAtributo(html, atributo){
    var wrapper = $("<div>").html(html || "");
    var valor = wrapper.find("[" + atributo + "]").first().attr(atributo);
    return valor || "";
  }

  function stockNumero(stockHtml){
    var texto = limpiarHtml(stockHtml);
    var match = texto.match(/-?\d+/);
    return match ? parseInt(match[0], 10) : 0;
  }

  function normalizarProducto(row, index){
    var stock = stockNumero(row[6]);
    return {
      index: index,
      numero: row[0] || (index + 1),
      codigo: limpiarHtml(row[1]),
      codigoGeneral: limpiarHtml(row[2]),
      codigosHtml: row[3] || "",
      descripcion: limpiarHtml(row[4]),
      categoria: limpiarHtml(row[5]),
      stock: stock,
      imagen: extraerSrc(row[7]),
      precioCompra: limpiarHtml(row[8]) || "0",
      precioVenta: limpiarHtml(row[9]) || "0",
      fecha: limpiarHtml(row[10]) || "-",
      accionesHtml: row[11] || "",
      idProducto: extraerAtributo((row[3] || "") + (row[11] || ""), "idProducto"),
      codigoEliminar: extraerAtributo(row[11] || "", "codigo"),
      imagenEliminar: extraerAtributo(row[11] || "", "imagen")
    };
  }

  function escapar(texto){
    return $("<div>").text(texto == null ? "" : texto).html();
  }

  function stockClase(stock){
    if(stock <= 0){ return "stock-zero"; }
    if(stock <= 3){ return "stock-low"; }
    return "stock-ok";
  }

  function stockTexto(stock){
    if(stock <= 0){ return "Sin stock"; }
    if(stock <= 3){ return "Bajo"; }
    return "Stock";
  }

  function accionesProducto(producto, incluirDetalle){
    var acciones = '<div class="tm-action-row">';
    if(incluirDetalle !== false){
      acciones += '<button type="button" class="tm-action-btn tm-action-detail btnDetalleProductoAlmacen" data-index="'+producto.index+'" data-toggle="modal" data-target="#modalDetalleProductoAlmacen" title="Ver detalle del producto"><i class="fa fa-eye"></i> Detalle</button>';
    }
    if(producto.codigosHtml && producto.codigosHtml.indexOf("btnVerCodigosUnicos") !== -1){
      acciones += '<button type="button" class="tm-action-btn tm-action-codes btnVerCodigosUnicos" idProducto="'+escapar(producto.idProducto)+'" codigo="'+escapar(producto.codigo || "")+'" data-toggle="modal" data-target="#modalCodigosUnicosProducto" title="Ver codigos unicos"><i class="fa fa-list"></i> Codigos</button>';
    }
    if(producto.idProducto){
      acciones += '<button type="button" class="tm-action-btn tm-action-print btnImprimirCodigosProducto" idProducto="'+escapar(producto.idProducto)+'" codigo="'+escapar(producto.codigo || "")+'" title="Generar o imprimir etiquetas"><i class="fa fa-print"></i> Imprimir códigos</button>';
    }
    if(producto.accionesHtml && producto.accionesHtml.indexOf("btnEditarProducto") !== -1){
      acciones += '<button type="button" class="tm-action-btn tm-action-edit btnEditarProducto" idProducto="'+escapar(producto.idProducto)+'" data-toggle="modal" data-target="#modalEditarProducto" title="Editar producto"><i class="fa fa-pencil"></i> Editar</button>';
    }
    if(producto.accionesHtml && producto.accionesHtml.indexOf("btnEliminarProducto") !== -1){
      acciones += '<button type="button" class="tm-action-btn tm-action-delete btnEliminarProducto" idProducto="'+escapar(producto.idProducto)+'" codigo="'+escapar(producto.codigoEliminar || producto.codigo || "")+'" imagen="'+escapar(producto.imagenEliminar || producto.imagen || "")+'" title="Eliminar producto"><i class="fa fa-trash"></i> Eliminar</button>';
    }
    acciones += '</div>';
    return acciones;
  }

  function tarjetaProducto(producto){
    var estado = stockTexto(producto.stock);
    return '<article class="tm-product-card" data-index="'+producto.index+'">'+
      '<div class="tm-product-visual">'+
        '<div class="tm-product-image"><img src="'+escapar(normalizarImagen(producto.imagen))+'" alt="'+escapar(producto.descripcion)+'" onerror="this.onerror=null;this.src=\''+escapar(defaultImage)+'\';"></div>'+
        '<div class="tm-product-stock-wrap">'+
          '<span class="tm-product-stock-label">Stock</span>'+
          '<span class="tm-product-stock '+stockClase(producto.stock)+'">'+producto.stock+'</span>'+
        '</div>'+
      '</div>'+
      '<div class="tm-product-body">'+
        '<div class="tm-product-top">'+
          '<span class="tm-product-code">'+escapar(producto.codigo || "Sin codigo")+'</span>'+
          '<h3 class="tm-product-title">'+escapar(producto.descripcion || "Producto sin descripcion")+'</h3>'+
        '</div>'+
        '<div class="tm-product-category"><i class="fa fa-sitemap"></i> '+escapar(producto.categoria || "Sin categoria")+'</div>'+
        '<div class="tm-product-meta">'+
          '<div class="tm-product-info"><span>Codigo general</span><strong>'+escapar(producto.codigoGeneral || "-")+'</strong></div>'+
          '<div class="tm-product-info"><span>Estado</span><strong>'+estado+'</strong></div>'+
          '<div class="tm-product-info"><span>Compra</span><strong>Bs '+escapar(producto.precioCompra)+'</strong></div>'+
        '</div>'+
      '</div>'+
      '<div class="tm-product-actions">'+accionesProducto(producto, true)+'</div>'+
    '</article>';
  }

  function filtrarProductos(){
    if(!productosCargados){
      buscandoDuranteCarga = true;
      $("#resumenProductosAlmacen").text("Cargando inventario; la busqueda se aplicara automaticamente...");
      return;
    }
    var query = ($("#buscarProductoAlmacen").val() || "").toLowerCase().trim();
    productosFiltrados = productosAlmacen.filter(function(producto){
      var texto = [producto.codigo, producto.codigoGeneral, producto.descripcion, producto.categoria].join(" ").toLowerCase();
      var coincideTexto = !query || texto.indexOf(query) !== -1;
      var coincideStock = filtroStock === "todos" ||
        (filtroStock === "stock" && producto.stock > 0) ||
        (filtroStock === "bajo" && producto.stock > 0 && producto.stock <= 3) ||
        (filtroStock === "cero" && producto.stock <= 0);
      return coincideTexto && coincideStock;
    });
    paginaActual = 1;
    renderProductos();
  }

  function botonesPagina(totalPaginas){
    if(totalPaginas <= 1){ return ""; }
    var html = '<button type="button" data-pagina="prev">Anterior</button>';
    var paginas = [];
    for(var i = 1; i <= totalPaginas; i++){
      if(i <= 5 || i === totalPaginas || Math.abs(i - paginaActual) <= 1){
        paginas.push(i);
      }else if(paginas[paginas.length - 1] !== "..."){
        paginas.push("...");
      }
    }
    paginas.forEach(function(pagina){
      if(pagina === "..."){
        html += '<button type="button" disabled>...</button>';
      }else{
        html += '<button type="button" class="'+(pagina === paginaActual ? "is-active" : "")+'" data-pagina="'+pagina+'">'+pagina+'</button>';
      }
    });
    html += '<button type="button" data-pagina="next">Siguiente</button>';
    return html;
  }

  function renderProductos(){
    var total = productosFiltrados.length;
    var totalPaginas = Math.max(1, Math.ceil(total / porPagina));
    if(paginaActual > totalPaginas){ paginaActual = totalPaginas; }
    var inicio = (paginaActual - 1) * porPagina;
    var visibles = productosFiltrados.slice(inicio, inicio + porPagina);

    $("#tmTotalProductosAlmacen").text(productosAlmacen.length);
    $("#resumenProductosAlmacen").text(total ? "Mostrando " + (inicio + 1) + " - " + (inicio + visibles.length) + " de " + total + " producto(s)" : "No hay productos para mostrar");
    $("#paginasProductosAlmacen").html(botonesPagina(totalPaginas));

    if(!visibles.length){
      $("#gridProductosAlmacen").html('<div class="tm-warehouse-empty"><i class="fa fa-search"></i><strong>No hay productos visibles.</strong><span>Cambia el filtro o busca otro producto.</span></div>');
      return;
    }

    $("#gridProductosAlmacen").html(visibles.map(tarjetaProducto).join(""));
  }

  function cargarProductos(){
    productosCargados = false;
    $("#buscarProductoAlmacen").attr("aria-busy", "true");
    $.ajax({
      url: endpointProductos,
      method: "GET",
      dataType: "json",
      success: function(respuesta){
        var data = respuesta && respuesta.data ? respuesta.data : [];
        productosAlmacen = data.map(normalizarProducto);
        productosCargados = true;
        $("#buscarProductoAlmacen").attr("aria-busy", "false");
        filtrarProductos();
        buscandoDuranteCarga = false;
      },
      error: function(){
        productosCargados = true;
        $("#buscarProductoAlmacen").attr("aria-busy", "false");
        $("#gridProductosAlmacen").html('<div class="tm-warehouse-empty"><i class="fa fa-warning"></i><strong>No se pudo cargar el inventario.</strong><span>Revise la conexion o el endpoint de productos.</span></div>');
      }
    });
  }

  function productoPorIndex(index){
    return productosAlmacen.filter(function(item){ return String(item.index) === String(index); })[0] || null;
  }

  $(document).on("click", ".tm-product-card", function(event){
    if($(event.target).closest("button, a, .btn, .btn-group").length){
      return;
    }
    $(this).find(".btnDetalleProductoAlmacen").trigger("click");
  });

  $(document).on("click", ".btnDetalleProductoAlmacen", function(event){
    event.stopPropagation();
    var producto = productoPorIndex($(this).data("index"));
    if(!producto){ return; }
    $("#detalleProductoTitulo").contents().filter(function(){ return this.nodeType === 3; }).remove();
    $("#detalleProductoTitulo").prepend(document.createTextNode(producto.descripcion || "Detalle de producto"));
    $("#detalleProductoCodigo").text(producto.codigo || "Producto de almacen");
    $("#detalleProductoImagen").attr("src", normalizarImagen(producto.imagen));
    $("#detalleProductoDescripcion").text(producto.descripcion || "-");
    $("#detalleProductoCategoria").text(producto.categoria || "-");
    $("#detalleProductoCodigoGeneral").text(producto.codigoGeneral || "-");
    $("#detalleProductoStock").text(producto.stock + " unidad(es)");
    $("#detalleProductoCompra").text("Bs " + producto.precioCompra);
    $("#detalleProductoVenta").text("Bs " + producto.precioVenta);
    $("#detalleProductoFecha").text(producto.fecha || "-");
    $("#detalleProductoAcciones").html('<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>' + accionesProducto(producto, false));
  });

  $("#buscarProductoAlmacen").on("input", function(){
    clearTimeout(temporizadorBusqueda);
    temporizadorBusqueda = setTimeout(filtrarProductos, 180);
  });
  $("#cantidadProductosAlmacen").on("change", function(){
    porPagina = parseInt($(this).val(), 10) || 16;
    paginaActual = 1;
    renderProductos();
  });
  $(".tm-stock-filter").on("click", function(){
    $(".tm-stock-filter").removeClass("is-active");
    $(this).addClass("is-active");
    filtroStock = $(this).data("filtro") || "todos";
    filtrarProductos();
  });
  $("#paginasProductosAlmacen").on("click", "button[data-pagina]", function(){
    var destino = $(this).data("pagina");
    var totalPaginas = Math.max(1, Math.ceil(productosFiltrados.length / porPagina));
    if(destino === "prev"){ paginaActual = Math.max(1, paginaActual - 1); }
    else if(destino === "next"){ paginaActual = Math.min(totalPaginas, paginaActual + 1); }
    else{ paginaActual = parseInt(destino, 10) || 1; }
    renderProductos();
  });

  $("#confirmarEliminarProductoAlmacen").on("click", function(){
    var idProducto = $("#eliminarProductoId").val();
    var imagen = $("#eliminarProductoImagen").val();
    var codigo = $("#eliminarProductoCodigo").text();
    if(!idProducto){ return; }
    window.location = "index.php?ruta=productos&idProducto=" + encodeURIComponent(idProducto) + "&imagen=" + encodeURIComponent(imagen || "") + "&codigo=" + encodeURIComponent(codigo || "") + "&retorno=productos-almacen";
  });

  $(document).on("click", ".btnImprimirCodigosProducto", function(event){
    event.preventDefault();
    event.stopPropagation();
    var boton = $(this);
    var idProducto = boton.attr("idProducto");
    var codigo = boton.attr("codigo") || "Producto";
    if(!idProducto){ return; }

    boton.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Preparando');
    $.ajax({
      url:"ajax/productos-etiquetas.ajax.php",
      method:"POST",
      dataType:"json",
      data:{idProducto:idProducto}
    }).done(function(res){
      if(!res || res.status !== "ok"){
        swal({type:"error",title:"No se pudieron preparar las etiquetas",text:(res && res.message) ? res.message : "Intente nuevamente.",confirmButtonText:"Cerrar"});
        return;
      }
      var texto = res.generados > 0
        ? (res.modo === "general"
            ? "El producto no tenía códigos ni stock. Se creó una etiqueta general sin modificar el inventario."
            : "Se generaron "+res.generados+" códigos para las "+res.cantidad+" unidades existentes.")
        : "Se imprimirán los "+res.cantidad+" códigos ya registrados.";
      swal({
        type:"success",
        title:"Etiquetas listas",
        text:texto,
        showCancelButton:true,
        confirmButtonText:"Abrir PDF",
        cancelButtonText:"Cerrar"
      }).then(function(result){
        if(result.value){
          window.open("extensiones/tcpdf/pdf/etiquetas-producto.php?id="+encodeURIComponent(idProducto),"_blank");
        }
      });
    }).fail(function(xhr){
      var mensaje = "No tiene permiso o ocurrió un problema al preparar los códigos.";
      if(xhr.responseJSON && xhr.responseJSON.message){ mensaje = xhr.responseJSON.message; }
      swal({type:"error",title:"No se pudo imprimir",text:mensaje,confirmButtonText:"Cerrar"});
    }).always(function(){
      boton.prop("disabled", false).html('<i class="fa fa-print"></i> Imprimir códigos');
    });
  });

  cargarProductos();
})();
</script>

<?php
  $eliminarProducto = new ControladorProductos();
  $eliminarProducto -> ctrEliminarProducto();
?>
