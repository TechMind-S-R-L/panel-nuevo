<?php

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

function tmCategoriaEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$item = null;
$valor = null;
$categorias = ControladorCategorias::ctrMostrarCategorias($item, $valor);
$categorias = is_array($categorias) ? $categorias : array();

$categoriasMadre = array();
$subcategoriasPorMadre = array();
$categoriasSinMadre = array();
$totalMadres = 0;
$totalSubcategorias = 0;

foreach($categorias as $categoriaBase){
  if(empty($categoriaBase["id_padre"])){
    $categoriasMadre[] = $categoriaBase;
    $totalMadres++;
  }else{
    $totalSubcategorias++;
    $idPadre = (int)$categoriaBase["id_padre"];
    if(!isset($subcategoriasPorMadre[$idPadre])){
      $subcategoriasPorMadre[$idPadre] = array();
    }
    $subcategoriasPorMadre[$idPadre][] = $categoriaBase;
  }
}

foreach($categorias as $categoriaBase){
  if(!empty($categoriaBase["id_padre"])){
    $idPadre = (int)$categoriaBase["id_padre"];
    $existeMadre = false;
    foreach($categoriasMadre as $madreValidacion){
      if((int)$madreValidacion["id"] === $idPadre){
        $existeMadre = true;
        break;
      }
    }
    if(!$existeMadre){
      $categoriasSinMadre[] = $categoriaBase;
    }
  }
}

?>

<div class="content-wrapper tm-categories-page">
  <style>
    .tm-categories-page .content{padding-top:10px;}
    .tm-category-hero{
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
    .tm-category-hero:after{
      content:"";
      position:absolute;
      right:-80px;
      top:-105px;
      width:250px;
      height:250px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-category-hero-copy{
      display:flex;
      align-items:center;
      gap:14px;
      position:relative;
      z-index:1;
      min-width:0;
    }
    .tm-category-hero-icon{
      width:54px;
      height:54px;
      border-radius:18px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.24);
      font-size:23px;
      flex:0 0 auto;
    }
    .tm-category-hero h2{
      margin:0;
      font-size:25px;
      font-weight:950;
    }
    .tm-category-hero p{
      margin:5px 0 0;
      max-width:760px;
      color:rgba(255,255,255,.88);
      font-weight:750;
    }
    .tm-category-metrics{
      display:grid;
      grid-template-columns:repeat(3, minmax(105px, 1fr));
      gap:10px;
      position:relative;
      z-index:1;
      min-width:360px;
    }
    .tm-category-metric{
      border:1px solid rgba(255,255,255,.26);
      border-radius:16px;
      padding:11px 12px;
      text-align:center;
      background:rgba(255,255,255,.12);
    }
    .tm-category-metric strong{
      display:block;
      font-size:27px;
      font-weight:950;
      line-height:1;
    }
    .tm-category-metric span{
      display:block;
      margin-top:4px;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      color:rgba(255,255,255,.82);
    }
    .tm-category-toolbar{
      border:1px solid rgba(184,205,232,.70);
      border-radius:18px;
      background:rgba(255,255,255,.72);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      padding:13px;
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      margin-bottom:14px;
    }
    .tm-category-search{
      position:relative;
      flex:1 1 360px;
      max-width:560px;
    }
    .tm-category-search i{
      position:absolute;
      left:13px;
      top:50%;
      transform:translateY(-50%);
      color:#3c8dbc;
    }
    .tm-category-search input{
      width:100%;
      border:1px solid rgba(184,205,232,.85);
      border-radius:13px;
      padding:12px 14px 12px 38px;
      background:rgba(255,255,255,.88);
      color:#1f2d3d;
      font-weight:850;
      outline:0;
    }
    .tm-category-toolbar .btn{
      border-radius:999px;
      font-weight:900;
      padding:10px 15px;
      box-shadow:0 10px 20px rgba(23,107,155,.16);
    }
    .tm-category-layout{
      display:grid;
      grid-template-columns:280px minmax(0, 1fr);
      gap:16px;
      align-items:start;
    }
    .tm-category-index{
      position:sticky;
      top:84px;
      border:1px solid rgba(184,205,232,.74);
      border-radius:20px;
      background:rgba(255,255,255,.78);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      padding:14px;
    }
    .tm-category-index h3{
      margin:0 0 11px;
      color:#173b5d;
      font-size:16px;
      font-weight:950;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .tm-category-index-list{
      display:flex;
      flex-direction:column;
      gap:8px;
      max-height:calc(100vh - 260px);
      overflow:auto;
      padding-right:2px;
    }
    .tm-category-index-item{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:9px;
      border:1px solid rgba(184,205,232,.62);
      border-radius:14px;
      background:rgba(248,251,255,.82);
      color:#203047;
      padding:9px 10px;
      font-weight:900;
      line-height:1.2;
      text-decoration:none !important;
    }
    .tm-category-index-item:hover{
      border-color:#3c8dbc;
      color:#176b9b;
      background:#eef8ff;
    }
    .tm-category-index-item span{
      min-width:26px;
      min-height:24px;
      border-radius:999px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:#176b9b;
      color:#fff;
      font-size:11px;
      font-weight:950;
      flex:0 0 auto;
    }
    .tm-category-tree{
      display:flex;
      flex-direction:column;
      gap:14px;
      min-width:0;
    }
    .tm-category-group{
      border:1px solid rgba(184,205,232,.76);
      border-radius:22px;
      background:rgba(255,255,255,.80);
      box-shadow:0 18px 42px rgba(15,23,42,.08);
      overflow:hidden;
    }
    .tm-category-group-head{
      display:grid;
      grid-template-columns:auto minmax(0, 1fr) auto;
      gap:12px;
      align-items:center;
      padding:15px 16px;
      background:linear-gradient(135deg, rgba(232,243,252,.95), rgba(255,255,255,.78));
      border-bottom:1px solid rgba(184,205,232,.60);
    }
    .tm-category-group-icon{
      width:48px;
      height:48px;
      border-radius:16px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      background:linear-gradient(135deg,#1f6cad,#20b7df);
      box-shadow:0 12px 24px rgba(31,108,173,.20);
      font-size:20px;
    }
    .tm-category-group-title small{
      display:block;
      color:#6a7a8f;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .tm-category-group-title h3{
      margin:0;
      color:#1f2d3d;
      font-size:19px;
      font-weight:950;
      line-height:1.2;
      text-transform:uppercase;
      overflow-wrap:anywhere;
    }
    .tm-category-group-title p{
      margin:5px 0 0;
      color:#60758d;
      font-size:12px;
      font-weight:850;
      overflow-wrap:anywhere;
    }
    .tm-category-group-tools{
      display:flex;
      align-items:center;
      justify-content:flex-end;
      gap:8px;
      flex-wrap:wrap;
    }
    .tm-category-count{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      padding:7px 10px;
      background:#176b9b;
      color:#fff;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
      white-space:nowrap;
    }
    .tm-category-action-btn{
      width:38px;
      height:36px;
      border:0;
      border-radius:12px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      box-shadow:0 8px 16px rgba(15,23,42,.09);
    }
    .tm-category-action-edit{background:#f39c12;}
    .tm-category-action-delete{background:#dd4b39;}
    .tm-subcategory-list{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(245px, 1fr));
      gap:10px;
      padding:14px;
    }
    .tm-subcategory-card{
      border:1px solid rgba(184,205,232,.68);
      border-radius:16px;
      background:rgba(248,251,255,.88);
      padding:12px;
      display:grid;
      grid-template-columns:auto minmax(0, 1fr) auto;
      gap:10px;
      align-items:center;
      min-width:0;
    }
    .tm-subcategory-icon{
      width:38px;
      height:38px;
      border-radius:13px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(135deg,#008da5,#00c0ef);
      color:#fff;
      flex:0 0 auto;
    }
    .tm-subcategory-copy small{
      display:block;
      color:#6a7a8f;
      font-size:9.5px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:3px;
    }
    .tm-subcategory-copy strong{
      display:block;
      color:#24364d;
      font-size:13px;
      font-weight:950;
      line-height:1.22;
      text-transform:uppercase;
      overflow-wrap:anywhere;
    }
    .tm-subcategory-copy span{
      display:block;
      color:#718299;
      font-size:11px;
      font-weight:800;
      margin-top:3px;
      overflow-wrap:anywhere;
    }
    .tm-subcategory-actions{
      display:flex;
      gap:6px;
      align-items:center;
      justify-content:flex-end;
      flex-wrap:wrap;
    }
    .tm-category-no-children{
      margin:14px;
      border:1px dashed rgba(60,141,188,.38);
      border-radius:16px;
      padding:18px;
      text-align:center;
      background:rgba(255,255,255,.62);
      color:#6d7f93;
      font-weight:850;
    }
    .tm-category-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(230px, 1fr));
      gap:13px;
      align-items:stretch;
    }
    .tm-category-card{
      border:1px solid rgba(184,205,232,.74);
      border-radius:18px;
      background:rgba(255,255,255,.76);
      box-shadow:0 16px 34px rgba(15,23,42,.08);
      padding:13px;
      min-height:202px;
      display:flex;
      flex-direction:column;
      gap:10px;
      position:relative;
      overflow:hidden;
      transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .tm-category-card:hover{
      transform:translateY(-2px);
      border-color:#3c8dbc;
      box-shadow:0 22px 42px rgba(15,23,42,.13);
    }
    .tm-category-card:after{
      content:"";
      position:absolute;
      right:-42px;
      bottom:-48px;
      width:128px;
      height:128px;
      border-radius:50%;
      background:rgba(60,141,188,.10);
      pointer-events:none;
    }
    .tm-category-card.is-child:after{background:rgba(0,192,239,.10);}
    .tm-category-top{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:10px;
      position:relative;
      z-index:1;
    }
    .tm-category-icon{
      width:42px;
      height:42px;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:18px;
      background:linear-gradient(135deg,#1f6cad,#20b7df);
      box-shadow:0 12px 24px rgba(31,108,173,.20);
      flex:0 0 auto;
    }
    .tm-category-card.is-child .tm-category-icon{
      background:linear-gradient(135deg,#008da5,#00c0ef);
    }
    .tm-category-badge{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      border-radius:999px;
      padding:5px 9px;
      background:#e8f3fc;
      color:#176b9b;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      text-align:center;
    }
    .tm-category-title{
      position:relative;
      z-index:1;
      flex:1;
    }
    .tm-category-title small{
      display:block;
      color:#6a7a8f;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:4px;
    }
    .tm-category-title h3{
      margin:0;
      color:#1f2d3d;
      font-size:16px;
      line-height:1.22;
      font-weight:950;
      text-transform:uppercase;
      overflow-wrap:anywhere;
    }
    .tm-category-meta{
      position:relative;
      z-index:1;
      display:grid;
      grid-template-columns:1fr;
      gap:7px;
      flex:1;
    }
    .tm-category-info{
      border:1px solid rgba(184,205,232,.62);
      border-radius:13px;
      background:rgba(248,251,255,.82);
      padding:9px;
      min-height:54px;
    }
    .tm-category-info span{
      display:block;
      color:#6a7a8f;
      font-size:10px;
      font-weight:950;
      text-transform:uppercase;
    }
    .tm-category-info strong{
      display:block;
      margin-top:3px;
      color:#24364d;
      font-size:13px;
      font-weight:950;
      overflow-wrap:anywhere;
    }
    .tm-category-actions{
      position:relative;
      z-index:1;
      display:flex;
      flex-wrap:wrap;
      gap:7px;
      justify-content:flex-end;
      border-top:1px dashed rgba(184,205,232,.76);
      padding-top:10px;
    }
    .tm-category-actions .btn{
      width:38px;
      height:34px;
      border-radius:11px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:0;
      box-shadow:0 8px 16px rgba(15,23,42,.09);
    }
    .tm-category-empty{
      grid-column:1/-1;
      min-height:210px;
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
    .tm-category-empty i{font-size:34px;color:#3c8dbc;}
    .tm-category-empty strong{color:#1f2d3d;font-weight:950;}
    .tm-category-modal .modal-dialog{
      width:min(660px, calc(100vw - 34px));
      margin:26px auto;
    }
    .tm-category-modal .modal-content{
      border:0;
      border-radius:22px;
      overflow:hidden;
      box-shadow:0 30px 76px rgba(15,23,42,.30);
    }
    .tm-category-modal .modal-header{
      border:0;
      color:#fff;
      background:linear-gradient(135deg,#12384f,#3c8dbc);
      padding:17px 20px;
      position:relative;
      overflow:hidden;
    }
    .tm-category-modal .modal-header:after{
      content:"";
      position:absolute;
      right:-45px;
      top:-72px;
      width:170px;
      height:170px;
      border-radius:50%;
      background:rgba(255,255,255,.13);
    }
    .tm-category-modal .modal-title{
      position:relative;
      z-index:1;
      display:flex;
      align-items:center;
      gap:12px;
      font-size:20px;
      font-weight:950;
    }
    .tm-category-modal-icon{
      width:42px;
      height:42px;
      border-radius:15px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.16);
      border:1px solid rgba(255,255,255,.20);
      flex:0 0 auto;
    }
    .tm-category-modal .modal-title small{
      display:block;
      margin-top:4px;
      color:rgba(255,255,255,.84);
      font-size:12px;
      font-weight:800;
    }
    .tm-category-modal .close{
      position:relative;
      z-index:2;
      color:#fff;
      opacity:.9;
      text-shadow:none;
    }
    .tm-category-modal-body{
      padding:16px;
      background:#f5f8fc;
    }
    .tm-category-field{
      border:1px solid rgba(184,205,232,.74);
      border-radius:15px;
      background:#fff;
      padding:12px;
      margin-bottom:11px;
    }
    .tm-category-field label{
      display:block;
      color:#4d6178;
      font-size:11px;
      font-weight:950;
      text-transform:uppercase;
      margin-bottom:7px;
    }
    .tm-category-field .input-group-addon{
      border-color:#d6e1ee;
      background:#eef5fc;
      color:#176b9b;
      font-weight:900;
    }
    .tm-category-field .form-control{
      border-color:#d6e1ee;
      border-radius:8px;
      font-weight:850;
      height:40px;
    }
    .tm-category-modal .modal-footer{
      border:0;
      padding:14px 16px;
      background:#f5f8fc;
    }
    .tm-category-modal .modal-footer .btn{
      border-radius:999px;
      font-weight:900;
      padding:9px 15px;
    }
    body.tm-dark-mode .tm-category-toolbar,
    body.tm-dark-mode .tm-category-card,
    body.tm-dark-mode .tm-category-index,
    body.tm-dark-mode .tm-category-group,
    body.dark-mode .tm-category-toolbar,
    body.dark-mode .tm-category-card,
    body.dark-mode .tm-category-index,
    body.dark-mode .tm-category-group{
      background:rgba(15,23,42,.64);
      border-color:rgba(99,135,184,.45);
    }
    body.tm-dark-mode .tm-category-title h3,
    body.tm-dark-mode .tm-category-info strong,
    body.tm-dark-mode .tm-category-index h3,
    body.tm-dark-mode .tm-category-group-title h3,
    body.tm-dark-mode .tm-subcategory-copy strong,
    body.dark-mode .tm-category-title h3,
    body.dark-mode .tm-category-info strong,
    body.dark-mode .tm-category-index h3,
    body.dark-mode .tm-category-group-title h3,
    body.dark-mode .tm-subcategory-copy strong{
      color:#f8fbff;
    }
    body.tm-dark-mode .tm-category-info,
    body.tm-dark-mode .tm-subcategory-card,
    body.tm-dark-mode .tm-category-index-item,
    body.tm-dark-mode .tm-category-no-children,
    body.dark-mode .tm-category-info,
    body.dark-mode .tm-subcategory-card,
    body.dark-mode .tm-category-index-item,
    body.dark-mode .tm-category-no-children{
      background:rgba(255,255,255,.06);
      border-color:rgba(255,255,255,.13);
    }
    @media (max-width: 991px){
      .tm-category-hero{flex-direction:column;align-items:stretch;}
      .tm-category-metrics{min-width:0;}
      .tm-category-layout{grid-template-columns:1fr;}
      .tm-category-index{position:relative;top:auto;}
      .tm-category-index-list{max-height:none;}
    }
    @media (max-width: 767px){
      .tm-category-hero-copy{align-items:flex-start;}
      .tm-category-metrics{grid-template-columns:1fr;}
      .tm-category-toolbar{flex-direction:column;align-items:stretch;}
      .tm-category-search{max-width:none;}
      .tm-category-modal .modal-dialog{width:calc(100vw - 18px);margin:9px auto;}
    }
  </style>

  <section class="content-header">
    <h1>Administrar categorias</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Administrar categorias</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-category-hero">
      <div class="tm-category-hero-copy">
        <div class="tm-category-hero-icon"><i class="fa fa-sitemap"></i></div>
        <div>
          <h2>Catalogo organizado por categorias</h2>
          <p>Administra categorias principales y subcategorias para mantener productos, tienda y almacen con una estructura limpia.</p>
        </div>
      </div>
      <div class="tm-category-metrics">
        <div class="tm-category-metric">
          <strong><?php echo count($categorias); ?></strong>
          <span>Total</span>
        </div>
        <div class="tm-category-metric">
          <strong><?php echo (int)$totalMadres; ?></strong>
          <span>Principales</span>
        </div>
        <div class="tm-category-metric">
          <strong><?php echo (int)$totalSubcategorias; ?></strong>
          <span>Subcategorias</span>
        </div>
      </div>
    </div>

    <div class="tm-category-toolbar">
      <div class="tm-category-search">
        <i class="fa fa-search"></i>
        <input type="text" id="buscarCategoriaTarjeta" placeholder="Buscar categoria, madre o subcategoria">
      </div>
      <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarCategoria">
        <i class="fa fa-plus"></i> Nueva categoria
      </button>
    </div>

    <div class="tm-category-layout" id="gridCategorias">
      <aside class="tm-category-index">
        <h3><i class="fa fa-folder-open"></i> Categorias madre</h3>
        <div class="tm-category-index-list">
          <?php if(empty($categoriasMadre)): ?>
            <div class="tm-category-index-item">Sin categorias <span>0</span></div>
          <?php endif; ?>
          <?php foreach($categoriasMadre as $madreIndice): ?>
            <?php $hijasIndice = $subcategoriasPorMadre[(int)$madreIndice["id"]] ?? array(); ?>
            <a class="tm-category-index-item" href="#categoria-<?php echo (int)$madreIndice["id"]; ?>">
              <?php echo tmCategoriaEsc($madreIndice["categoria"]); ?>
              <span><?php echo count($hijasIndice); ?></span>
            </a>
          <?php endforeach; ?>
          <?php if(!empty($categoriasSinMadre)): ?>
            <a class="tm-category-index-item" href="#categorias-sin-madre">
              Sin madre registrada
              <span><?php echo count($categoriasSinMadre); ?></span>
            </a>
          <?php endif; ?>
        </div>
      </aside>

      <main class="tm-category-tree">
        <?php if(empty($categorias)): ?>
          <div class="tm-category-empty">
            <i class="fa fa-folder-open"></i>
            <strong>No hay categorias registradas.</strong>
            <span>Crea la primera categoria principal para ordenar productos.</span>
          </div>
        <?php endif; ?>

        <?php foreach($categoriasMadre as $madre): ?>
          <?php
            $hijas = $subcategoriasPorMadre[(int)$madre["id"]] ?? array();
            $busquedaGrupo = strtolower(($madre["categoria"] ?? "")." ".($madre["ruta_categoria"] ?? ""));
            foreach($hijas as $hijaBusqueda){
              $busquedaGrupo .= " ".strtolower(($hijaBusqueda["categoria"] ?? "")." ".($hijaBusqueda["ruta_categoria"] ?? ""));
            }
          ?>
          <section class="tm-category-group tm-category-search-item" id="categoria-<?php echo (int)$madre["id"]; ?>" data-parent-search="<?php echo tmCategoriaEsc(strtolower(($madre["categoria"] ?? "")." ".($madre["ruta_categoria"] ?? ""))); ?>" data-search="<?php echo tmCategoriaEsc($busquedaGrupo); ?>">
            <header class="tm-category-group-head">
              <div class="tm-category-group-icon"><i class="fa fa-folder"></i></div>
              <div class="tm-category-group-title">
                <small>Categoria principal</small>
                <h3><?php echo tmCategoriaEsc($madre["categoria"]); ?></h3>
                <p><?php echo tmCategoriaEsc($madre["ruta_categoria"] ?? $madre["categoria"]); ?></p>
              </div>
              <div class="tm-category-group-tools">
                <span class="tm-category-count"><?php echo count($hijas); ?> subcategoria(s)</span>
                <button class="tm-category-action-btn tm-category-action-edit btnEditarCategoria" idCategoria="<?php echo (int)$madre["id"]; ?>" data-toggle="modal" data-target="#modalEditarCategoria" title="Editar categoria madre">
                  <i class="fa fa-pencil"></i>
                </button>
                <?php if($_SESSION["perfil"] == "Administrador"): ?>
                  <button class="tm-category-action-btn tm-category-action-delete btnEliminarCategoria" idCategoria="<?php echo (int)$madre["id"]; ?>" title="Eliminar categoria madre">
                    <i class="fa fa-trash"></i>
                  </button>
                <?php endif; ?>
              </div>
            </header>

            <?php if(empty($hijas)): ?>
              <div class="tm-category-no-children">
                <i class="fa fa-info-circle"></i> Esta categoria principal aun no tiene subcategorias.
              </div>
            <?php else: ?>
              <div class="tm-subcategory-list">
                <?php foreach($hijas as $hija): ?>
                  <?php $busquedaHija = strtolower(($hija["categoria"] ?? "")." ".($hija["ruta_categoria"] ?? "")." ".($madre["categoria"] ?? "")); ?>
                  <article class="tm-subcategory-card tm-category-search-item" data-search="<?php echo tmCategoriaEsc($busquedaHija); ?>">
                    <div class="tm-subcategory-icon"><i class="fa fa-tag"></i></div>
                    <div class="tm-subcategory-copy">
                      <small>Subcategoria de <?php echo tmCategoriaEsc($madre["categoria"]); ?></small>
                      <strong><?php echo tmCategoriaEsc($hija["categoria"]); ?></strong>
                      <span><?php echo tmCategoriaEsc($hija["ruta_categoria"] ?? ($madre["categoria"]." > ".$hija["categoria"])); ?></span>
                    </div>
                    <div class="tm-subcategory-actions">
                      <button class="tm-category-action-btn tm-category-action-edit btnEditarCategoria" idCategoria="<?php echo (int)$hija["id"]; ?>" data-toggle="modal" data-target="#modalEditarCategoria" title="Editar subcategoria">
                        <i class="fa fa-pencil"></i>
                      </button>
                      <?php if($_SESSION["perfil"] == "Administrador"): ?>
                        <button class="tm-category-action-btn tm-category-action-delete btnEliminarCategoria" idCategoria="<?php echo (int)$hija["id"]; ?>" title="Eliminar subcategoria">
                          <i class="fa fa-trash"></i>
                        </button>
                      <?php endif; ?>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>

        <?php if(!empty($categoriasSinMadre)): ?>
          <section class="tm-category-group tm-category-search-item" id="categorias-sin-madre" data-parent-search="sin madre" data-search="sin madre categorias sin madre">
            <header class="tm-category-group-head">
              <div class="tm-category-group-icon"><i class="fa fa-warning"></i></div>
              <div class="tm-category-group-title">
                <small>Revision necesaria</small>
                <h3>Subcategorias sin madre registrada</h3>
                <p>Estas categorias tienen un id_padre que ya no existe o no esta cargado.</p>
              </div>
              <div class="tm-category-group-tools">
                <span class="tm-category-count"><?php echo count($categoriasSinMadre); ?> item(s)</span>
              </div>
            </header>
            <div class="tm-subcategory-list">
              <?php foreach($categoriasSinMadre as $hija): ?>
                <article class="tm-subcategory-card tm-category-search-item" data-search="<?php echo tmCategoriaEsc(strtolower($hija["categoria"] ?? "")); ?>">
                  <div class="tm-subcategory-icon"><i class="fa fa-tag"></i></div>
                  <div class="tm-subcategory-copy">
                    <small>Sin madre valida</small>
                    <strong><?php echo tmCategoriaEsc($hija["categoria"]); ?></strong>
                    <span><?php echo tmCategoriaEsc($hija["ruta_categoria"] ?? $hija["categoria"]); ?></span>
                  </div>
                  <div class="tm-subcategory-actions">
                    <button class="tm-category-action-btn tm-category-action-edit btnEditarCategoria" idCategoria="<?php echo (int)$hija["id"]; ?>" data-toggle="modal" data-target="#modalEditarCategoria" title="Editar categoria">
                      <i class="fa fa-pencil"></i>
                    </button>
                    <?php if($_SESSION["perfil"] == "Administrador"): ?>
                      <button class="tm-category-action-btn tm-category-action-delete btnEliminarCategoria" idCategoria="<?php echo (int)$hija["id"]; ?>" title="Eliminar categoria">
                        <i class="fa fa-trash"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <div id="sinCategoriasResultado" class="tm-category-empty" style="display:none;">
          <i class="fa fa-search"></i>
          <strong>No hay coincidencias.</strong>
          <span>Prueba con otro nombre, categoria madre o subcategoria.</span>
        </div>
      </main>
    </div>
  </section>
</div>

<div id="modalAgregarCategoria" class="modal fade tm-category-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">
            <span class="tm-category-modal-icon"><i class="fa fa-plus"></i></span>
            <span>Agregar categoria<small>Crea una categoria principal o una subcategoria.</small></span>
          </h4>
        </div>

        <div class="tm-category-modal-body">
          <div class="tm-category-field">
            <label>Nombre de la categoria</label>
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-th"></i></span>
              <input type="text" class="form-control" name="nuevaCategoria" placeholder="Ej: Camaras 4MP" required>
            </div>
          </div>

          <div class="tm-category-field">
            <label>Ubicacion dentro del catalogo</label>
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-sitemap"></i></span>
              <select class="form-control" name="nuevaCategoriaPadre">
                <option value="">Sin madre (categoria principal)</option>
                <?php foreach($categoriasMadre as $madre): ?>
                  <option value="<?php echo (int)$madre["id"]; ?>"><?php echo tmCategoriaEsc($madre["categoria"]); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar categoria</button>
        </div>

        <?php
          $crearCategoria = new ControladorCategorias();
          $crearCategoria -> ctrCrearCategoria();
        ?>
      </form>
    </div>
  </div>
</div>

<div id="modalEditarCategoria" class="modal fade tm-category-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">
            <span class="tm-category-modal-icon"><i class="fa fa-pencil"></i></span>
            <span>Editar categoria<small>Actualiza el nombre o su categoria madre.</small></span>
          </h4>
        </div>

        <div class="tm-category-modal-body">
          <div class="tm-category-field">
            <label>Nombre de la categoria</label>
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-th"></i></span>
              <input type="text" class="form-control" name="editarCategoria" id="editarCategoria" required>
              <input type="hidden" name="idCategoria" id="idCategoria" required>
            </div>
          </div>

          <div class="tm-category-field">
            <label>Ubicacion dentro del catalogo</label>
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-sitemap"></i></span>
              <select class="form-control" name="editarCategoriaPadre" id="editarCategoriaPadre">
                <option value="">Sin madre (categoria principal)</option>
                <?php foreach($categoriasMadre as $madre): ?>
                  <option value="<?php echo (int)$madre["id"]; ?>"><?php echo tmCategoriaEsc($madre["categoria"]); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
        </div>

        <?php
          $editarCategoria = new ControladorCategorias();
          $editarCategoria -> ctrEditarCategoria();
        ?>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  $("#buscarCategoriaTarjeta").on("input", function(){
    var query = ($(this).val() || "").toLowerCase().trim();
    var visibles = 0;

    $(".tm-category-group").each(function(){
      var grupo = $(this);
      var textoMadre = String(grupo.data("parent-search") || "").toLowerCase();
      var coincideMadre = !query || textoMadre.indexOf(query) !== -1;
      var hijosVisibles = 0;

      grupo.find(".tm-subcategory-card").each(function(){
        var item = $(this);
        var textoItem = String(item.data("search") || item.text()).toLowerCase();
        var coincideItem = !query || coincideMadre || textoItem.indexOf(query) !== -1;
        item.toggle(coincideItem);
        if(coincideItem){
          hijosVisibles++;
        }
      });

      var textoGrupo = String(grupo.data("search") || "").toLowerCase();
      var coincideGrupo = !query || coincideMadre || hijosVisibles > 0 || textoGrupo.indexOf(query) !== -1;
      grupo.toggle(coincideGrupo);
      if(coincideGrupo){
        visibles++;
      }
    });

    $("#sinCategoriasResultado").toggle(visibles === 0 && $(".tm-category-group").length > 0);
  });
})();
</script>

<?php
  $borrarCategoria = new ControladorCategorias();
  $borrarCategoria -> ctrBorrarCategoria();
?>
