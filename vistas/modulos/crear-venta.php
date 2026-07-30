<?php

$vistaRolCrearVenta = $_SESSION["vistaRolMenu"] ?? "";
if(($_SESSION["rol"] == "cajero" && $_SESSION["perfil"] != "Administrador") || ($_SESSION["perfil"] == "Administrador" && $vistaRolCrearVenta == "cajero")){

  echo '<script>window.location = "ventas";</script>';
  return;
}

if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "vendedor"){

  echo '<script>window.location = "inicio";</script>';
  return;
}

$codigo = ControladorVentas::ctrSiguienteCodigoVenta();
$clientesVenta = ControladorClientes::ctrMostrarClientes(null, null);

?>

<div class="content-wrapper crear-venta-page">
<style>
  .crear-venta-page{
    background:#eef3f7 !important;
  }
  .venta-hero{
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
  .venta-hero h2{
    margin:0 0 6px;
    font-weight:700;
    font-size:24px;
  }
  .venta-hero p{
    margin:0;
    color:#c8d7df;
  }
  .venta-code{
    background:#fff;
    color:#163140;
    border-radius:4px;
    padding:12px 16px;
    min-width:170px;
    text-align:center;
  }
  .venta-code span{
    display:block;
    color:#6f7f8a;
    font-size:12px;
    text-transform:uppercase;
    font-weight:700;
  }
  .venta-code strong{
    font-size:26px;
    line-height:1;
  }
  .venta-panel{
    background:#fff;
    border:1px solid #dbe5ec;
    border-radius:4px;
    box-shadow:0 1px 2px rgba(0,0,0,.06);
    margin-bottom:16px;
  }
  .venta-step{
    overflow:hidden;
  }
  .venta-step.venta-step-hidden{
    display:none;
  }
  .venta-wizard-progress{
    display:grid;
    grid-template-columns:repeat(4, 1fr);
    gap:10px;
    margin:0 0 16px;
  }
  .venta-wizard-dot{
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
  .venta-wizard-dot span{
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
  .venta-wizard-dot small{
    display:block;
    font-size:11px;
    color:#8a9aa6;
    margin-top:2px;
  }
  .venta-wizard-dot.active{
    background:#163140;
    color:#fff;
    border-color:#163140;
    box-shadow:0 14px 30px rgba(22,49,64,.18);
  }
  .venta-wizard-dot.active span{
    background:#3c8dbc;
    color:#fff;
  }
  .venta-wizard-dot.active small{
    color:#c8d7df;
  }
  .venta-wizard-dot.done{
    border-color:#9dd4af;
    background:#f2fff6;
  }
  .venta-wizard-actions{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    margin-top:4px;
  }
  .venta-wizard-actions .btn{
    min-width:150px;
    font-weight:800;
  }
  .venta-wizard-help{
    color:#6f8190;
    font-weight:700;
  }
  .venta-step-header{
    padding:14px 16px;
    border-bottom:1px solid #e5edf2;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    background:#fbfdff;
  }
  .venta-step-title{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .venta-step-number{
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
  .venta-step-header h3{
    margin:0;
    font-size:18px;
    font-weight:700;
    color:#1f2d3d;
  }
  .venta-step-body{
    padding:16px;
  }
  .venta-step-body.table-responsive{
    padding:10px;
  }
  .venta-fields-grid{
    display:grid;
    grid-template-columns:1fr 1.3fr;
    gap:14px;
    align-items:end;
  }
  .venta-row-superior,
  .venta-row-inferior{
    display:flex;
    flex-wrap:wrap;
    align-items:flex-start;
  }
  .venta-row-superior>[class*="col-"],
  .venta-row-inferior>[class*="col-"]{
    display:flex;
    flex-direction:column;
  }
  .venta-row-superior .venta-panel,
  .venta-row-inferior .venta-panel{
    width:100%;
  }
  .venta-panel-header{
    padding:14px 16px;
    border-bottom:1px solid #e5edf2;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .venta-panel-header h3{
    margin:0;
    font-size:18px;
    font-weight:700;
    color:#1f2d3d;
  }
  .venta-panel-body{
    padding:16px;
  }
  .venta-label{
    display:block;
    font-weight:700;
    color:#34495e;
    margin-bottom:7px;
  }
  .venta-input,
  .venta-select{
    height:42px;
    border-radius:4px;
    border-color:#d7e1e8;
    box-shadow:none;
  }
  .venta-input:focus,
  .venta-select:focus{
    border-color:#3c8dbc;
    box-shadow:0 0 0 3px rgba(60,141,188,.12);
  }
  .venta-inline-action{
    display:grid;
    grid-template-columns:minmax(0, 1fr) auto;
    gap:8px;
    align-items:center;
  }
  .venta-inline-action .form-control{
    min-width:0;
  }
  .venta-inline-action .btn{
    white-space:nowrap;
  }
  .venta-cart{
    min-height:120px;
    border:1px dashed #c6d4df;
    background:#f8fbfd;
    border-radius:4px;
    padding:10px 0;
  }
  .venta-cart:empty:before{
    content:"Seleccione productos desde la tabla para agregarlos a esta venta.";
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
  .nuevoProducto .row:last-child{
    border-bottom:0;
  }
  .controlCantidad .btn{
    min-width:34px;
  }
  .nuevaCantidadProducto{
    font-weight:700;
    color:#1f2d3d;
  }
  .venta-summary{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
  }
  .venta-summary-box{
    border:1px solid #dbe5ec;
    border-radius:4px;
    padding:12px;
    background:#fbfdff;
  }
  .venta-summary-box label{
    color:#60717f;
    font-weight:700;
    margin-bottom:7px;
  }
  .venta-total input{
    font-size:24px;
    font-weight:700;
    color:#163140;
  }
  .venta-note{
    border-left:4px solid #f39c12;
    background:#fff8e8;
    color:#5d4a1f;
    padding:12px;
    border-radius:4px;
    margin-top:14px;
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
    flex:1 1 320px;
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
    padding-left:38px;
    border-color:#d7e1e8;
    box-shadow:none;
  }
  .venta-product-size{
    display:flex;
    align-items:center;
    gap:8px;
    color:#60717f;
    font-weight:700;
  }
  .venta-product-size select{
    width:96px;
    height:42px;
  }
  .venta-product-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(145px, 1fr));
    gap:10px;
    max-height:650px;
    overflow-y:auto;
    padding-right:6px;
  }
  .venta-product-grid::-webkit-scrollbar{
    width:8px;
  }
  .venta-product-grid::-webkit-scrollbar-track{
    background:#eef3f7;
    border-radius:8px;
  }
  .venta-product-grid::-webkit-scrollbar-thumb{
    background:#9bb7ca;
    border-radius:8px;
  }
  .venta-product-card{
    border:1px solid #dbe5ec;
    border-radius:7px;
    background:#fff;
    padding:9px;
    min-height:215px;
    display:flex;
    flex-direction:column;
    box-shadow:0 8px 20px rgba(22,49,64,.06);
    cursor:pointer;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .venta-product-card:hover{
    border-color:#3c8dbc;
    box-shadow:0 12px 26px rgba(22,49,64,.12);
    transform:translateY(-2px);
  }
  .venta-product-img{
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
  .venta-product-img img,
  .venta-product-img .img-producto-venta{
    width:100%;
    max-width:105px;
    height:68px;
    object-fit:contain;
    margin:0 auto;
  }
  .venta-product-code{
    display:block;
    color:#3c8dbc;
    font-size:12px;
    font-weight:800;
    letter-spacing:.02em;
    text-transform:uppercase;
    margin-bottom:5px;
    overflow-wrap:anywhere;
  }
  .venta-product-card h4{
    margin:0 0 7px;
    color:#1f2d3d;
    font-size:12px;
    font-weight:800;
    line-height:1.35;
    min-height:48px;
    overflow-wrap:anywhere;
  }
  .venta-product-price{
    min-height:25px;
    margin-bottom:6px;
  }
  .venta-card-price{
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
  .venta-product-footer{
    margin-top:auto;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
  }
  .venta-product-hint{
    color:#7a8b98;
    font-size:11px;
    font-weight:700;
    margin-top:8px;
  }
  .venta-product-stock .btn,
  .venta-product-action .btn{
    white-space:nowrap;
    padding:5px 8px;
    font-size:12px;
  }
  .venta-product-modal .modal-dialog{
    max-width:900px;
    width:92%;
  }
  .venta-product-modal .modal-content{
    border:0;
    border-radius:16px;
    overflow:hidden;
    background:#f4f8fb;
    box-shadow:0 28px 80px rgba(10,30,45,.34);
  }
  .venta-product-modal .modal-header{
    position:relative;
    background:linear-gradient(135deg,#102b3b 0%,#176b9b 58%,#36aee2 100%);
    color:#fff;
    border:0;
    padding:20px 24px;
    min-height:104px;
  }
  .venta-product-modal .modal-header:after{
    content:"";
    position:absolute;
    right:-42px;
    top:-58px;
    width:190px;
    height:190px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
  }
  .venta-product-modal .close{
    position:relative;
    z-index:2;
    color:#fff;
    opacity:.9;
    text-shadow:none;
    width:36px;
    height:36px;
    border-radius:50%;
    background:rgba(255,255,255,.16);
    font-size:28px;
    line-height:34px;
  }
  .venta-product-modal-title{
    position:relative;
    z-index:2;
    display:flex;
    align-items:center;
    gap:14px;
    max-width:86%;
  }
  .venta-product-modal-title h4{
    margin:4px 0 0;
    font-size:23px;
    font-weight:800;
    line-height:1.25;
    overflow-wrap:anywhere;
  }
  .venta-product-modal-code{
    display:inline-flex;
    align-items:center;
    min-height:24px;
    padding:4px 10px;
    border-radius:999px;
    background:rgba(255,255,255,.16);
    color:#eaf7ff;
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
  }
  .venta-product-modal-icon{
    width:54px;
    height:54px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.18);
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
    font-size:24px;
    flex:0 0 54px;
  }
  .venta-product-modal .modal-body{
    padding:0;
    background:#f4f8fb;
  }
  .venta-product-modal-layout{
    display:grid;
    grid-template-columns:310px minmax(0,1fr);
    gap:0;
    align-items:stretch;
  }
  .venta-product-modal-img{
    min-height:360px;
    border:0;
    border-right:1px solid #dbe5ec;
    border-radius:0;
    background:linear-gradient(180deg,#fff,#eef6fb);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    position:relative;
  }
  .venta-product-modal-img:before{
    content:"Imagen del producto";
    position:absolute;
    top:16px;
    left:18px;
    color:#6b8190;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.06em;
  }
  .venta-product-modal-img img,
  .venta-product-modal-img .img-producto-venta{
    width:100%;
    max-height:280px;
    object-fit:contain;
    filter:drop-shadow(0 14px 20px rgba(22,49,64,.12));
  }
  .venta-product-modal-info{
    padding:22px;
  }
  .venta-product-modal-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:12px;
    margin-bottom:16px;
  }
  .venta-product-modal-grid>div{
    background:#fff;
    border:1px solid #dbe5ec;
    border-radius:12px;
    padding:12px;
    min-height:76px;
    box-shadow:0 10px 24px rgba(22,49,64,.06);
  }
  .venta-product-modal-grid span{
    display:block;
    color:#6f8190;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    margin-bottom:4px;
  }
  .venta-product-modal-grid strong{
    color:#1f2d3d;
    font-size:15px;
    overflow-wrap:anywhere;
  }
  .venta-product-modal-description-box{
    background:#fff;
    border:1px solid #dbe5ec;
    border-radius:12px;
    padding:16px;
    margin-bottom:16px;
    box-shadow:0 10px 24px rgba(22,49,64,.06);
  }
  .venta-product-modal-description-box h5{
    margin:0 0 9px;
    color:#163140;
    font-weight:800;
    font-size:15px;
  }
  .venta-product-modal-description{
    margin:0;
    color:#465a68;
    line-height:1.55;
    overflow-wrap:anywhere;
  }
  .venta-product-modal-note{
    display:flex;
    gap:10px;
    align-items:flex-start;
    background:#fff8e8;
    color:#634c17;
    border:1px solid #f4dfae;
    border-left:4px solid #f39c12;
    border-radius:10px;
    padding:11px 12px;
    margin-bottom:16px;
    font-weight:700;
    line-height:1.35;
  }
  .venta-product-modal-note i{
    margin-top:2px;
    color:#d7890b;
  }
  .venta-product-modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
    background:#fff;
    border:1px solid #dbe5ec;
    border-radius:12px;
    padding:12px;
  }
  .venta-product-modal-actions .btn{
    min-height:42px;
    padding:10px 18px;
    font-weight:800;
    border-radius:8px;
    box-shadow:none;
  }
  .venta-product-modal-actions .btn-primary{
    background:#0f8ec0;
    border-color:#0f8ec0;
  }
  .venta-product-modal-actions .btn-primary:hover{
    background:#0b79a4;
    border-color:#0b79a4;
  }
  .venta-product-footer-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    border-top:1px solid #e5edf2;
    margin-top:14px;
    padding-top:12px;
  }
  .productosVentaInfo{
    color:#60717f;
    font-weight:700;
  }
  .productosVentaPaginacion{
    display:flex;
    align-items:center;
    gap:6px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }
  .productosVentaPaginas{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:5px;
    flex-wrap:wrap;
    max-height:74px;
    overflow-y:auto;
    padding:2px;
  }
  .productosVentaPagina{
    min-width:34px;
    height:32px;
    border-radius:5px;
    background:#eef5fa;
    color:#2e5d78;
    border:1px solid #cddde8;
    font-weight:800;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
  }
  .productosVentaPagina.active{
    background:#3c8dbc;
    border-color:#3c8dbc;
    color:#fff;
  }
  .productosVentaPuntos{
    min-width:26px;
    height:32px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    color:#60717f;
    font-weight:800;
  }
  .venta-products-empty{
    grid-column:1 / -1;
    min-height:150px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    border:1px dashed #c6d4df;
    border-radius:8px;
    color:#6f8190;
    background:#f8fbfd;
    font-weight:700;
  }
  .productos-panel .dataTables_filter input,
  .productos-panel .dataTables_length select{
    border:1px solid #d7e1e8;
    border-radius:4px;
    height:34px;
  }
  .productos-panel .dataTables_wrapper{
    width:100%;
  }
  .productos-panel .dataTables_length{
    float:left;
    min-height:38px;
  }
  .productos-panel .dataTables_filter{
    float:right;
    min-height:38px;
    text-align:right;
  }
  .productos-panel .dataTables_info{
    float:left;
    padding-top:12px;
    white-space:nowrap;
  }
  .productos-panel .dataTables_paginate{
    float:right;
    padding-top:8px;
    text-align:right;
  }
  .productos-panel .pagination{
    margin:0;
    display:inline-flex;
    align-items:center;
    gap:4px;
    flex-wrap:nowrap;
  }
  .productos-panel .pagination>li{
    display:inline-block;
  }
  .productos-panel .pagination>li>a,
  .productos-panel .pagination>li>span{
    min-width:34px;
    height:34px;
    padding:7px 10px;
    line-height:18px;
    text-align:center;
    border-radius:4px;
    margin-left:0;
  }
  .productos-panel .pagination>.previous>a,
  .productos-panel .pagination>.next>a{
    min-width:86px;
  }
  .productos-panel table.tablaVentas{
    width:100% !important;
    table-layout:auto;
    margin-bottom:8px !important;
  }
  .productos-panel table.tablaVentas th:nth-child(1),
  .productos-panel table.tablaVentas td:nth-child(1){
    width:38px !important;
    text-align:center;
  }
  .productos-panel table.tablaVentas th:nth-child(2),
  .productos-panel table.tablaVentas td:nth-child(2){
    width:56px !important;
    text-align:center;
  }
  .productos-panel table.tablaVentas th:nth-child(3),
  .productos-panel table.tablaVentas td:nth-child(3){
    width:96px !important;
    word-break:break-word;
  }
  .productos-panel table.tablaVentas th:nth-child(4),
  .productos-panel table.tablaVentas td:nth-child(4){
    min-width:170px;
    word-break:normal;
    overflow-wrap:anywhere;
  }
  .productos-panel table.tablaVentas th:nth-child(5),
  .productos-panel table.tablaVentas td:nth-child(5){
    width:68px !important;
    text-align:center;
  }
  .productos-panel table.tablaVentas th:nth-child(6),
  .productos-panel table.tablaVentas td:nth-child(6){
    width:92px !important;
    text-align:center;
  }
  .productos-panel table.tablaVentas td{
    vertical-align:middle !important;
    padding:7px 6px !important;
    line-height:1.25;
  }
  .productos-panel table.tablaVentas .img-producto-venta{
    width:34px;
    height:34px;
    object-fit:contain;
    display:block;
    margin:0 auto;
  }
  .productos-panel table.tablaVentas .btn{
    padding:6px 10px;
    line-height:1.2;
    white-space:nowrap;
  }
  .productos-panel .venta-panel-body{
    padding:10px;
    overflow-x:hidden;
  }
  .productos-panel .dataTables_wrapper .row{
    margin-left:0;
    margin-right:0;
  }
  .productos-panel .dataTables_wrapper .col-sm-12,
  .productos-panel .dataTables_wrapper .col-sm-6{
    padding-left:0;
    padding-right:0;
  }
  .productos-panel table thead th{
    background:#f5f8fa;
    color:#263845;
  }
  @media(max-width:767px){
    .productos-panel .dataTables_length,
    .productos-panel .dataTables_filter,
    .productos-panel .dataTables_info,
    .productos-panel .dataTables_paginate{
      float:none;
      text-align:left;
      width:100%;
    }
    .productos-panel .dataTables_filter{
      margin-top:8px;
    }
    .productos-panel .dataTables_paginate{
      overflow-x:auto;
      padding-bottom:4px;
    }
  }
  #modalAgregarCliente .modal-dialog{
    max-width:720px;
  }
  #modalAgregarCliente .modal-header{
    background:#2f89b8;
    color:#fff;
  }
  #modalAgregarCliente .cliente-modal-note{
    background:#f7fbff;
    border:1px solid #d8e8f3;
    color:#35556b;
    padding:10px 12px;
    border-radius:4px;
    margin-bottom:15px;
  }
  #modalAgregarCliente label{
    font-weight:600;
    color:#34495e;
  }
  #modalAgregarCliente .input-group-addon{
    min-width:42px;
    background:#f4f6f8;
  }
  @media(max-width:991px){
    .venta-row-superior,
    .venta-row-inferior{
      display:block;
    }
    .venta-product-modal-layout{
      grid-template-columns:1fr;
    }
    .venta-product-modal-title{
      max-width:100%;
    }
    .venta-product-modal-img{
      min-height:240px;
      border-right:0;
      border-bottom:1px solid #dbe5ec;
    }
    .venta-product-modal-grid{
      grid-template-columns:1fr;
    }
    .venta-fields-grid{
      grid-template-columns:1fr;
    }
    .venta-summary{
      grid-template-columns:1fr;
    }
    .venta-wizard-progress{
      grid-template-columns:1fr;
    }
    .venta-wizard-actions{
      flex-direction:column;
      align-items:stretch;
    }
    .venta-wizard-actions .btn{
      width:100%;
    }
  }
</style>

  <section class="content-header">
    <h1>Crear venta</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Crear venta</li>
    </ol>
  </section>

  <section class="content">
    <div class="venta-hero">
      <div>
        <h2>Nueva venta pendiente de cobro</h2>
        <p>Seleccione cliente, agregue productos y genere la boleta para que caja realice el cobro.</p>
      </div>
      <div class="venta-code">
        <span>Codigo de venta</span>
        <strong><?php echo $codigo; ?></strong>
      </div>
    </div>

    <form role="form" method="post" class="formularioVenta">
      <input type="hidden" id="listaProductos" name="listaProductos">
      <input type="hidden" id="listaMetodoPago" name="listaMetodoPago" value="Pendiente de pago">
      <input type="hidden" name="idVendedor" value="<?php echo $_SESSION["id"]; ?>">
      <input type="hidden" id="nuevaVenta" name="nuevaVenta" value="<?php echo $codigo; ?>">

      <div class="venta-wizard-progress">
        <button type="button" class="venta-wizard-dot active" data-step-target="1">
          <span>1</span>
          <div>Datos <small>Cliente y vendedor</small></div>
        </button>
        <button type="button" class="venta-wizard-dot" data-step-target="2">
          <span>2</span>
          <div>Productos <small>Buscar y agregar</small></div>
        </button>
        <button type="button" class="venta-wizard-dot" data-step-target="3">
          <span>3</span>
          <div>Seleccionados <small>Cantidades</small></div>
        </button>
        <button type="button" class="venta-wizard-dot" data-step-target="4">
          <span>4</span>
          <div>Resumen <small>Generar boleta</small></div>
        </button>
      </div>

      <div class="venta-panel venta-step" data-venta-step="1">
        <div class="venta-step-header">
          <div class="venta-step-title">
            <span class="venta-step-number">1</span>
            <h3><i class="fa fa-user"></i> Datos de la venta</h3>
          </div>
        </div>
        <div class="venta-step-body">
          <div class="venta-fields-grid">
            <div class="form-group">
              <label class="venta-label">Vendedor</label>
              <input type="text" class="form-control venta-input" id="nuevoVendedor" value="<?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?>" readonly>
            </div>

            <div class="form-group">
              <label class="venta-label">Cliente</label>
              <div class="venta-inline-action">
                <select class="form-control venta-select" id="seleccionarCliente" name="seleccionarCliente" required>
                  <option value="">Seleccionar cliente</option>
                  <?php foreach ($clientesVenta as $clienteVenta): ?>
                    <option value="<?php echo $clienteVenta["id"]; ?>"><?php echo htmlspecialchars($clienteVenta["nombre"], ENT_QUOTES, "UTF-8"); ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-default" data-toggle="modal" data-target="#modalAgregarCliente">
                  <i class="fa fa-plus"></i> Cliente
                </button>
              </div>
            </div>
          </div>

          <div class="venta-note">
            Esta venta queda pendiente de cobro. El cliente debe presentar la boleta en caja para pagar.
          </div>
        </div>
      </div>

      <div class="venta-panel venta-step productos-panel venta-step-hidden" data-venta-step="2">
        <div class="venta-step-header">
          <div class="venta-step-title">
            <span class="venta-step-number">2</span>
            <h3><i class="fa fa-cubes"></i> Productos disponibles</h3>
          </div>
          <span class="label label-info">Primero se muestran productos con stock</span>
        </div>
        <div class="venta-step-body">
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
                    <div>
                      <span>Codigo</span>
                      <strong class="venta-product-modal-code-value">-</strong>
                    </div>
                    <div>
                      <span>Stock</span>
                      <strong class="venta-product-modal-stock">-</strong>
                    </div>
                    <div>
                      <span>Precio</span>
                      <strong class="venta-product-modal-price">-</strong>
                    </div>
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

      <div class="venta-panel venta-step venta-step-hidden" data-venta-step="3">
        <div class="venta-step-header">
          <div class="venta-step-title">
            <span class="venta-step-number">3</span>
            <h3><i class="fa fa-shopping-basket"></i> Productos seleccionados</h3>
          </div>
          <button type="button" class="btn btn-default btn-sm hidden-lg btnAgregarProducto">
            <i class="fa fa-plus"></i> Agregar producto
          </button>
        </div>
        <div class="venta-step-body">
          <div class="nuevoProducto venta-cart"></div>
        </div>
      </div>

      <div class="venta-panel venta-step venta-step-hidden" data-venta-step="4">
        <div class="venta-step-header">
          <div class="venta-step-title">
            <span class="venta-step-number">4</span>
            <h3><i class="fa fa-calculator"></i> Resumen</h3>
          </div>
        </div>
        <div class="venta-step-body">
          <div class="venta-summary">
            <div class="venta-summary-box">
              <label>Descuento</label>
              <div class="input-group">
                <input type="number" class="form-control input-lg" min="0" id="nuevoImpuestoVenta" name="nuevoImpuestoVenta" placeholder="0">
                <input type="hidden" name="nuevoPrecioImpuesto" id="nuevoPrecioImpuesto">
                <input type="hidden" name="nuevoPrecioNeto" id="nuevoPrecioNeto" required>
                <span class="input-group-addon"><i class="fa fa-percent"></i></span>
              </div>
            </div>

            <div class="venta-summary-box venta-total">
              <label>Total</label>
              <div class="input-group">
                <span class="input-group-addon"><b>Bs</b></span>
                <input type="text" class="form-control input-lg" id="nuevoTotalVenta" name="nuevoTotalVenta" total="" placeholder="0.00" readonly required>
                <input type="hidden" name="totalVenta" id="totalVenta">
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:16px">
            <i class="fa fa-print"></i> Generar boleta de cobro
          </button>
        </div>
      </div>

      <div class="venta-wizard-actions">
        <button type="button" class="btn btn-default btnVentaPasoAnterior">
          <i class="fa fa-arrow-left"></i> Anterior
        </button>
        <span class="venta-wizard-help">Complete el paso actual para continuar.</span>
        <button type="button" class="btn btn-primary btnVentaPasoSiguiente">
          Siguiente <i class="fa fa-arrow-right"></i>
        </button>
      </div>

      <?php
        $guardarVenta = new ControladorVentas();
        $guardarVenta -> ctrCrearVenta();
      ?>
    </form>
  </section>
</div>

<div id="modalAgregarCliente" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" novalidate>
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
                    <input type="text" class="form-control" name="nuevoDocumentoId" placeholder="Ej. 1234567" required>
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
                    <input type="text" class="form-control" name="nuevoEmail" placeholder="correo@ejemplo.com">
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
                <input type="text" class="form-control" name="nuevaFechaNacimiento" placeholder="aaaa-mm-dd">
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
