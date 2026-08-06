<?php

session_start();
date_default_timezone_set("America/La_Paz");
?>

<!DOCTYPE html>
<html>
<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <title>TechMind</title>

  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="icon" href="/favicon.ico?v=31" sizes="any">
  <link rel="icon" type="image/png" href="/favicon.png?v=31">
  <link rel="shortcut icon" href="/favicon.ico?v=31">
  <link rel="apple-touch-icon" href="/favicon.png?v=31">

   <!--=====================================
  PLUGINS DE CSS
  ======================================-->

  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="vistas/bower_components/bootstrap/dist/css/bootstrap.css">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="vistas/bower_components/font-awesome/css/font-awesome.min.css">

  <!-- Ionicons -->
  <link rel="stylesheet" href="vistas/bower_components/Ionicons/css/ionicons.min.css">

  <!-- Theme style -->
  <link rel="stylesheet" href="vistas/dist/css/AdminLTE.css">
  
  <!-- AdminLTE Skins -->
  <link rel="stylesheet" href="vistas/dist/css/skins/_all-skins.css">

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:400,500,600,700,800|Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

   <!-- DataTables -->
  <link rel="stylesheet" href="vistas/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="vistas/bower_components/datatables.net-bs/css/responsive.bootstrap.min.css">

  <!-- iCheck for checkboxes and radio inputs -->
  <link rel="stylesheet" href="vistas/plugins/iCheck/all.css">

   <!-- Daterange picker -->
  <link rel="stylesheet" href="vistas/bower_components/bootstrap-daterangepicker/daterangepicker.css">

  <!-- Morris chart -->
  <link rel="stylesheet" href="vistas/bower_components/morris.js/morris.css">

  <style>
    :root{
      --tm-bg:#f5f8fc;
      --tm-panel:#ffffff;
      --tm-sidebar:#ffffff;
      --tm-sidebar-2:#f8fbff;
      --tm-sidebar-soft:#ecf2ff;
      --tm-primary:#174b86;
      --tm-primary-2:#5d87ff;
      --tm-text:#172033;
      --tm-muted:#61718b;
      --tm-line:#dfe8f3;
      --tm-shadow:0 18px 42px rgba(23,75,134,.10);
    }
    html,body{
      font-family:Inter,"Source Sans Pro",Arial,sans-serif;
      background:var(--tm-bg);
    }
    body.tm-admin-page{
      background:#fff !important;
      min-height:100vh;
      overflow-x:hidden;
    }
    body.tm-admin-page #tmAdminParticles{
      position:fixed !important;
      left:0 !important;
      top:0 !important;
      right:0 !important;
      bottom:0 !important;
      width:100vw !important;
      height:100vh !important;
      margin:0 !important;
      padding:0 !important;
      pointer-events:none !important;
      display:block !important;
    }
    .wrapper{
      background:transparent !important;
      position:relative;
      z-index:1;
      min-height:100vh;
    }
    .main-header{
      margin-bottom:0 !important;
      border-bottom:0 !important;
      box-shadow:0 10px 24px rgba(15,23,42,.08) !important;
      position:relative;
      z-index:1030;
    }
    .skin-blue .main-header .navbar{
      background:rgba(255,255,255,.20) !important;
      border-bottom:1px solid rgba(223,232,243,.68) !important;
      box-shadow:none !important;
      backdrop-filter:none !important;
      -webkit-backdrop-filter:none !important;
    }
    .skin-blue .main-header .logo{
      background:rgba(255,255,255,.42) !important;
      color:var(--tm-text) !important;
      border-right:1px solid var(--tm-line);
      font-weight:800;
      letter-spacing:.2px;
    }
    .main-header .logo{
      display:flex !important;
      align-items:center;
      justify-content:center;
      padding:0 10px !important;
      overflow:hidden;
    }
    .main-header .logo .logo-lg,
    .main-header .logo .logo-mini{
      align-items:center;
      justify-content:center;
      width:100%;
      height:50px;
    }
    .main-header .logo .logo-lg{
      display:flex;
      gap:8px;
    }
    .main-header .logo .logo-mini{
      display:none;
    }
    .main-header .logo img{
      width:34px;
      height:34px;
      object-fit:contain;
      display:inline-block;
    }
    .main-header .logo .logo-lg b{
      color:#174b86;
      font-weight:900;
    }
    body.sidebar-mini.sidebar-collapse .main-header .logo .logo-mini{
      display:flex !important;
    }
    body.sidebar-mini.sidebar-collapse .main-header .logo .logo-lg{
      display:none !important;
    }
    .skin-blue .main-header .logo:hover{
      background:rgba(236,242,255,.46) !important;
    }
    .skin-blue .main-header .navbar .sidebar-toggle{
      color:var(--tm-text) !important;
      border-radius:10px;
      margin:8px 0 0 12px;
      height:34px;
      width:38px;
      padding:8px 10px;
    }
    .skin-blue .main-header .navbar .sidebar-toggle:hover{
      background:rgba(236,242,255,.52) !important;
      color:var(--tm-primary) !important;
    }
    .main-sidebar{
      background:rgba(255,255,255,.22) !important;
      border-right:1px solid rgba(223,232,243,.75);
      box-shadow:10px 0 28px rgba(23,75,134,.08);
      backdrop-filter:none !important;
      -webkit-backdrop-filter:none !important;
    }
    .skin-blue .main-sidebar,
    .skin-blue .left-side{
      background:rgba(255,255,255,.22) !important;
    }
    .content-wrapper{
      position:relative;
      border-top:0 !important;
      background:rgba(245,248,252,.68);
      min-height:calc(100vh - 50px);
      margin-top:0 !important;
      padding-top:0 !important;
    }
    .content-wrapper:before{
      content:"";
      position:absolute;
      top:0;
      left:0;
      right:0;
      height:0;
      background:transparent;
      z-index:0;
      pointer-events:none;
    }
    .content-wrapper>.content-header,
    .content-wrapper>.content{
      position:relative;
      z-index:1;
    }
    .content-wrapper>.content-header{
      padding:22px 24px 8px;
    }
    .content-wrapper>.content-header>h1{
      font-size:25px;
      font-weight:800;
      color:var(--tm-text);
      letter-spacing:.1px;
    }
    .content-wrapper>.content-header>.breadcrumb{
      background:rgba(255,255,255,.66);
      border:1px solid rgba(219,228,238,.8);
      border-radius:999px;
      padding:7px 12px;
      top:22px;
      right:24px;
    }
    .content{
      padding:16px 24px 24px;
    }
    .box,
    .nav-tabs-custom{
      border:1px solid rgba(219,228,238,.92);
      border-radius:14px;
      box-shadow:var(--tm-shadow);
      overflow:hidden;
      background:rgba(255,255,255,.54);
    }
    .box{
      border-top:0;
      background:rgba(255,255,255,.54);
    }
    .box-header{
      padding:15px 18px;
      border-bottom:1px solid #e7edf5;
    }
    .box.box-primary{
      border-top:0;
    }
    .table>thead>tr>th,
    .table>tbody>tr>th{
      background:#f8fafc;
      color:#334155;
      font-size:12px;
      text-transform:uppercase;
      letter-spacing:.03em;
      border-bottom:1px solid #e7edf5;
    }
    .table>tbody>tr>td{
      vertical-align:middle;
      color:#243044;
    }
    .btn{
      border-radius:7px;
      font-weight:700;
      border:0;
      box-shadow:none;
    }
    .btn-primary{
      background:linear-gradient(135deg,var(--tm-primary),var(--tm-primary-2));
    }
    .label{
      border-radius:999px;
      padding:5px 9px;
      font-weight:800;
    }
    @media(max-width:767px){
      .content-wrapper>.content-header{
        padding:16px 14px 4px;
      }
      .content-wrapper>.content-header>.breadcrumb{
        position:static;
        display:inline-block;
        margin-top:10px;
      }
      .content{
        padding:12px 14px 18px;
      }
    }
    .tm-theme-toggle{
      display:inline-flex !important;
      align-items:center;
      gap:8px;
      margin:8px 12px 0 0;
      padding:8px 12px !important;
      min-height:34px;
      color:var(--tm-primary) !important;
      background:rgba(236,242,255,.58) !important;
      border:1px solid rgba(93,135,255,.22);
      border-radius:999px;
      font-weight:800;
    }
    .tm-theme-toggle:hover{
      background:rgba(236,242,255,.86) !important;
    }
    .tm-theme-toggle span{
      font-size:12px;
    }
    .table-responsive{
      border:0;
    }
    .table{
      background:rgba(255,255,255,.50);
      border-radius:12px;
      overflow:hidden;
    }
    .table-bordered{
      border:1px solid rgba(223,232,243,.9);
    }
    .table-bordered>thead>tr>th,
    .table-bordered>tbody>tr>td,
    .table-bordered>tbody>tr>th{
      border-color:#edf2f7;
    }
    .table-striped>tbody>tr:nth-of-type(odd){
      background-color:rgba(245,248,252,.72);
    }
    .table-hover>tbody>tr:hover{
      background-color:rgba(236,242,255,.72);
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input,
    .form-control,
    .form-select{
      border-radius:8px;
      border:1px solid #dfe8f3;
      box-shadow:none;
    }
    body.tm-dark-mode{
      --tm-bg:#0b1220;
      --tm-panel:#101a2e;
      --tm-sidebar:#0f172a;
      --tm-sidebar-2:#111c33;
      --tm-sidebar-soft:#17233b;
      --tm-primary:#5d87ff;
      --tm-primary-2:#8fb3ff;
      --tm-text:#e5edf7;
      --tm-muted:#9fb0c7;
      --tm-line:#22314e;
      --tm-shadow:0 18px 42px rgba(0,0,0,.28);
      background:#0b1220;
      color:#cbd5e1;
    }
    body.tm-dark-mode .wrapper,
    body.tm-dark-mode .content-wrapper{
      background:
        radial-gradient(circle at 14% 0%, rgba(93,135,255,.14), transparent 34%),
        radial-gradient(circle at 96% 14%, rgba(23,75,134,.18), transparent 28%),
        #0b1220 !important;
    }
    body.tm-dark-mode .skin-blue .main-header .navbar,
    body.tm-dark-mode .main-header .navbar{
      background:rgba(15,23,42,.72) !important;
      border-bottom-color:rgba(34,49,78,.88) !important;
    }
    body.tm-dark-mode .skin-blue .main-header .logo{
      background:rgba(15,23,42,.82) !important;
      color:#e5edf7 !important;
      border-right-color:#22314e;
    }
    body.tm-dark-mode .main-sidebar,
    body.tm-dark-mode .skin-blue .main-sidebar,
    body.tm-dark-mode .skin-blue .left-side{
      background:rgba(15,23,42,.80) !important;
      border-right-color:#22314e;
    }
    body.tm-dark-mode .content-wrapper>.content-header>h1,
    body.tm-dark-mode .box-title,
    body.tm-dark-mode .table>tbody>tr>td,
    body.tm-dark-mode .table>thead>tr>th{
      color:#e5edf7;
    }
    body.tm-dark-mode .box,
    body.tm-dark-mode .nav-tabs-custom,
    body.tm-dark-mode .table{
      background:rgba(16,26,46,.82);
      border-color:#22314e;
      color:#cbd5e1;
    }
    body.tm-dark-mode .box-header,
    body.tm-dark-mode .table>thead>tr>th,
    body.tm-dark-mode .table>tbody>tr>th{
      background:rgba(23,35,59,.92);
      border-color:#22314e;
      color:#dbeafe;
    }
    body.tm-dark-mode .table-bordered>thead>tr>th,
    body.tm-dark-mode .table-bordered>tbody>tr>td,
    body.tm-dark-mode .table-bordered>tbody>tr>th,
    body.tm-dark-mode .tm-dashboard-table td{
      border-color:#22314e !important;
    }
    body.tm-dark-mode .table-striped>tbody>tr:nth-of-type(odd){
      background-color:rgba(23,35,59,.54);
    }
    body.tm-dark-mode .table-hover>tbody>tr:hover{
      background-color:rgba(93,135,255,.16);
    }
    body.tm-dark-mode .form-control,
    body.tm-dark-mode .form-select,
    body.tm-dark-mode .dataTables_wrapper .dataTables_filter input,
    body.tm-dark-mode .dataTables_wrapper .dataTables_length select{
      background:#101a2e;
      color:#e5edf7;
      border-color:#2b3b5e;
    }
    body.tm-dark-mode .breadcrumb,
    body.tm-dark-mode .content-wrapper>.content-header>.breadcrumb{
      background:rgba(16,26,46,.82);
      border-color:#22314e;
    }
    body.tm-dark-mode,
    body.tm-dark-mode p,
    body.tm-dark-mode span,
    body.tm-dark-mode label,
    body.tm-dark-mode small,
    body.tm-dark-mode .help-block,
    body.tm-dark-mode .text-muted,
    body.tm-dark-mode .dataTables_info,
    body.tm-dark-mode .dataTables_length,
    body.tm-dark-mode .dataTables_filter,
    body.tm-dark-mode .pagination>li>a,
    body.tm-dark-mode .pagination>li>span{
      color:#dbe7f6 !important;
    }
    body.tm-dark-mode h1,
    body.tm-dark-mode h2,
    body.tm-dark-mode h3,
    body.tm-dark-mode h4,
    body.tm-dark-mode h5,
    body.tm-dark-mode h6,
    body.tm-dark-mode b,
    body.tm-dark-mode strong,
    body.tm-dark-mode .box-title,
    body.tm-dark-mode .modal-title,
    body.tm-dark-mode .breadcrumb>li,
    body.tm-dark-mode .breadcrumb>li>a{
      color:#ffffff !important;
    }
    body.tm-dark-mode a{
      color:#9cc2ff;
    }
    body.tm-dark-mode .modal-content,
    body.tm-dark-mode .dropdown-menu,
    body.tm-dark-mode .popover{
      background:#101a2e;
      color:#e5edf7;
      border-color:#22314e;
    }
    body.tm-dark-mode .modal-header,
    body.tm-dark-mode .modal-footer{
      border-color:#22314e;
    }
    body.tm-dark-mode .input-group-addon,
    body.tm-dark-mode .panel,
    body.tm-dark-mode .well{
      background:#17233b;
      color:#e5edf7;
      border-color:#2b3b5e;
    }
    body.tm-dark-mode .pagination>.disabled>a,
    body.tm-dark-mode .pagination>.disabled>span{
      background:#101a2e;
      border-color:#22314e;
      color:#6f819f !important;
    }
    body.tm-dark-mode .pagination>li>a,
    body.tm-dark-mode .pagination>li>span{
      background:#17233b;
      border-color:#2b3b5e;
    }
    body.tm-dark-mode .pagination>.active>a,
    body.tm-dark-mode .pagination>.active>span{
      background:#5d87ff;
      border-color:#5d87ff;
      color:#fff !important;
    }
    .tm-admin-particles{
      position:fixed;
      inset:0;
      z-index:0;
      pointer-events:none;
      opacity:1;
      display:block;
      width:100vw;
      height:100vh;
      mix-blend-mode:multiply;
      filter:drop-shadow(0 0 5px rgba(37,99,235,.28));
    }
    .main-header,
    .content-wrapper,
    .main-footer,
    .content-wrapper>.content-header,
    .content-wrapper>.content{
      position:relative;
      z-index:1;
    }
    .main-sidebar,
    .skin-blue .main-sidebar,
    .skin-blue .left-side{
      position:absolute;
      top:0;
      left:0;
      z-index:810;
    }
    body.tm-dark-mode .tm-admin-particles{
      opacity:1;
      mix-blend-mode:screen;
      filter:drop-shadow(0 0 7px rgba(255,255,255,.55));
    }
    .table td:last-child,
    .table th:last-child{
      white-space:normal;
    }
    .btn-group{
      display:inline-flex;
      flex-wrap:wrap;
      gap:5px;
      vertical-align:middle;
    }
    .btn-group>.btn,
    .btn-group-vertical>.btn{
      float:none;
      border-radius:7px !important;
      margin-left:0 !important;
    }
    td .btn,
    td a.btn,
    td button.btn{
      margin:2px;
      white-space:nowrap;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:5px;
    }
    td .btn i{
      line-height:1;
    }
    .table .acciones,
    .table td:last-child{
      min-width:110px;
    }
    @media(max-width:991px){
      .table td:last-child{
        min-width:140px;
      }
      td .btn,
      td a.btn,
      td button.btn{
        min-height:32px;
      }
    }
    .wrapper{
      background:transparent !important;
    }
    .content-wrapper{
      background:
        radial-gradient(circle at 14% 0%, rgba(93,135,255,.04), transparent 34%),
        radial-gradient(circle at 96% 14%, rgba(23,75,134,.025), transparent 28%),
        rgba(245,248,252,.12) !important;
    }
    body.tm-admin-page>.wrapper>.content-wrapper{
      margin-top:0 !important;
      top:0 !important;
      transform:none !important;
    }
    body.tm-dark-mode .content-wrapper{
      background:
        radial-gradient(circle at 14% 0%, rgba(93,135,255,.13), transparent 34%),
        radial-gradient(circle at 96% 14%, rgba(23,75,134,.18), transparent 28%),
        rgba(11,18,32,.78) !important;
    }
    /* Ajuste final: sin logo superior, navbar completa y sidebar con marca propia */
    body.tm-admin-page .main-header .logo,
    body.tm-admin-page .main-header .tm-logo-superior{
      display:none !important;
      width:0 !important;
      min-width:0 !important;
      height:0 !important;
      padding:0 !important;
      border:0 !important;
      overflow:hidden !important;
    }
    body.tm-admin-page .main-header .navbar,
    body.tm-admin-page.sidebar-mini.sidebar-collapse .main-header .navbar{
      margin-left:0 !important;
    }
    body.tm-admin-page .main-header .navbar .sidebar-toggle{
      margin-left:14px !important;
    }
    /* Capas Bootstrap/AdminLTE: evita que modales y menus queden bloqueados */
    body.tm-admin-page .wrapper{
      z-index:auto !important;
      isolation:auto !important;
    }
    body.tm-admin-page .content-wrapper,
    body.tm-admin-page .content-wrapper>.content-header,
    body.tm-admin-page .content-wrapper>.content{
      z-index:auto !important;
    }
    body.tm-admin-page .main-header{
      z-index:1200 !important;
    }
    body.tm-admin-page .main-sidebar{
      z-index:1100 !important;
    }
    body.tm-admin-page .modal{
      z-index:30050 !important;
      pointer-events:auto !important;
    }
    body.tm-admin-page .modal-backdrop{
      z-index:30040 !important;
    }
    body.tm-admin-page .modal-dialog,
    body.tm-admin-page .modal-content{
      pointer-events:auto !important;
    }
    body.tm-admin-page .swal2-container{
      z-index:40080 !important;
    }
    body.tm-admin-page .swal2-popup{
      z-index:40090 !important;
    }
    body.tm-admin-page.swal2-shown .modal,
    body.tm-admin-page.swal2-shown .modal-backdrop{
      pointer-events:none !important;
    }
    body.tm-admin-page.swal2-shown .swal2-container,
    body.tm-admin-page.swal2-shown .swal2-popup{
      pointer-events:auto !important;
    }
    /* Layout final del sidebar nuevo: marca arriba y boton de menu visible */
    @media (min-width:768px){
      body.tm-admin-page .main-sidebar{
        z-index:1300 !important;
      }
      body.tm-admin-page .main-header{
        z-index:1200 !important;
      }
      body.tm-admin-page .main-header .navbar .sidebar-toggle{
        margin-left:272px !important;
        transition:margin-left .2s ease, background .2s ease;
      }
      body.tm-admin-page.sidebar-collapse .main-header .navbar .sidebar-toggle{
        margin-left:72px !important;
      }
      body.tm-admin-page .content-wrapper,
      body.tm-admin-page .main-footer{
        margin-left:258px !important;
        transition:margin-left .2s ease;
      }
      body.tm-admin-page.sidebar-collapse .content-wrapper,
      body.tm-admin-page.sidebar-collapse .main-footer{
        margin-left:60px !important;
      }
    }

    /* Tablas TechMind: compactas, centradas y con acciones ordenadas */
    .box-body.table-responsive,
    .table-responsive,
    .dataTables_wrapper{
      width:100% !important;
      border:0 !important;
      overflow-x:auto !important;
      overflow-y:visible !important;
      border-radius:14px;
    }
    .table-responsive::-webkit-scrollbar,
    .dataTables_wrapper::-webkit-scrollbar{
      height:7px;
    }
    .table-responsive::-webkit-scrollbar-thumb,
    .dataTables_wrapper::-webkit-scrollbar-thumb{
      background:rgba(93,135,255,.28);
      border-radius:999px;
    }
    table.table,
    table.dataTable{
      width:100% !important;
      max-width:100% !important;
      margin:0 !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      table-layout:auto !important;
      background:rgba(255,255,255,.76) !important;
      border:1px solid rgba(223,232,243,.92) !important;
      border-radius:14px !important;
      overflow:hidden !important;
      font-size:12px !important;
    }
    table.table thead tr th,
    table.dataTable thead tr th{
      padding:10px 8px !important;
      vertical-align:middle !important;
      text-align:center !important;
      white-space:normal !important;
      color:#17324d !important;
      background:linear-gradient(180deg,#f8fbff,#edf4ff) !important;
      border-color:#dfe8f3 !important;
      font-size:11px !important;
      font-weight:900 !important;
      line-height:1.15 !important;
      letter-spacing:.02em !important;
      text-transform:uppercase !important;
    }
    table.table tbody tr td,
    table.dataTable tbody tr td{
      padding:8px 8px !important;
      vertical-align:middle !important;
      text-align:center !important;
      color:#243044 !important;
      border-color:#edf2f7 !important;
      line-height:1.22 !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
    }
    table.table tbody tr td:nth-child(2),
    table.dataTable tbody tr td:nth-child(2),
    table.table tbody tr td:nth-child(3),
    table.dataTable tbody tr td:nth-child(3),
    table.table tbody tr td:nth-child(4),
    table.dataTable tbody tr td:nth-child(4){
      text-align:left !important;
    }
    table.table tbody tr:nth-child(odd),
    table.dataTable tbody tr:nth-child(odd){
      background:rgba(248,251,255,.78) !important;
    }
    table.table tbody tr:nth-child(even),
    table.dataTable tbody tr:nth-child(even){
      background:rgba(255,255,255,.70) !important;
    }
    table.table tbody tr:hover,
    table.dataTable tbody tr:hover{
      background:rgba(236,242,255,.92) !important;
    }
    table.table th:first-child,
    table.table td:first-child,
    table.dataTable th:first-child,
    table.dataTable td:first-child{
      width:42px !important;
      min-width:42px !important;
      text-align:center !important;
    }
    table.table th:last-child,
    table.table td:last-child,
    table.dataTable th:last-child,
    table.dataTable td:last-child,
    .table .acciones,
    .cliente-col-acciones{
      width:auto !important;
      min-width:112px !important;
      max-width:190px !important;
      text-align:center !important;
      white-space:normal !important;
    }
    table.table td:last-child .btn-group,
    table.dataTable td:last-child .btn-group,
    .cliente-col-acciones .btn-group,
    .table .acciones .btn-group{
      display:inline-flex !important;
      flex-wrap:wrap !important;
      align-items:center !important;
      justify-content:center !important;
      gap:5px !important;
      width:100% !important;
      margin:0 auto !important;
      float:none !important;
    }
    table.table td .btn,
    table.dataTable td .btn,
    table.table td a.btn,
    table.dataTable td a.btn,
    table.table td button.btn,
    table.dataTable td button.btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      gap:5px !important;
      min-width:31px !important;
      min-height:31px !important;
      max-width:128px !important;
      margin:2px !important;
      padding:6px 8px !important;
      border-radius:9px !important;
      font-size:11px !important;
      font-weight:800 !important;
      line-height:1.08 !important;
      white-space:normal !important;
      text-align:center !important;
      box-shadow:0 8px 16px rgba(23,75,134,.10) !important;
    }
    table.table td .btn i,
    table.dataTable td .btn i{
      line-height:1 !important;
      margin:0 !important;
      flex:0 0 auto !important;
    }
    .dataTables_wrapper .row{
      margin-left:0 !important;
      margin-right:0 !important;
    }
    .dataTables_wrapper .row:first-child{
      display:flex !important;
      flex-wrap:wrap !important;
      align-items:center !important;
      gap:10px !important;
      margin:0 0 10px !important;
    }
    .dataTables_wrapper .row:first-child>[class*="col-"]{
      flex:1 1 240px !important;
      width:auto !important;
      max-width:none !important;
      padding-left:0 !important;
      padding-right:0 !important;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter{
      float:none !important;
      display:flex !important;
      align-items:center !important;
      gap:8px !important;
      min-height:36px !important;
      margin:0 !important;
    }
    .dataTables_wrapper .dataTables_filter{
      justify-content:flex-end !important;
      text-align:right !important;
    }
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label{
      display:flex !important;
      align-items:center !important;
      gap:7px !important;
      margin:0 !important;
      font-weight:700 !important;
      color:#334155 !important;
    }
    .dataTables_wrapper .dataTables_length select{
      width:72px !important;
      min-width:72px !important;
    }
    .dataTables_wrapper .dataTables_filter input{
      width:clamp(150px,22vw,270px) !important;
      min-height:34px !important;
    }
    .dataTables_wrapper .row:last-child{
      display:flex !important;
      flex-wrap:wrap !important;
      align-items:center !important;
      gap:8px !important;
      margin:10px 0 0 !important;
    }
    .dataTables_wrapper .row:last-child>[class*="col-"]{
      flex:1 1 250px !important;
      width:auto !important;
      max-width:none !important;
      padding-left:0 !important;
      padding-right:0 !important;
    }
    .dataTables_info{
      float:none !important;
      padding-top:0 !important;
      color:#61718b !important;
      font-weight:700 !important;
      font-size:12px !important;
    }
    .dataTables_paginate{
      float:none !important;
      display:flex !important;
      justify-content:flex-end !important;
      padding-top:0 !important;
      overflow-x:auto !important;
    }
    .pagination{
      display:flex !important;
      flex-wrap:wrap !important;
      gap:5px !important;
      margin:0 !important;
    }
    .pagination>li>a,
    .pagination>li>span{
      border-radius:8px !important;
      border:1px solid rgba(223,232,243,.95) !important;
      color:#174b86 !important;
      font-weight:800 !important;
      min-width:32px !important;
      text-align:center !important;
    }
    .pagination>.active>a,
    .pagination>.active>span{
      color:#fff !important;
      background:linear-gradient(135deg,#174b86,#5d87ff) !important;
      border-color:transparent !important;
    }
    @media(max-width:991px){
      .dataTables_wrapper .dataTables_filter{
        justify-content:flex-start !important;
        text-align:left !important;
      }
      table.table,
      table.dataTable{
        min-width:760px !important;
      }
    }
    body.tm-dark-mode .content-wrapper{
      background:
        radial-gradient(circle at 16% 8%, rgba(255,255,255,.08), transparent 24%),
        radial-gradient(circle at 92% 12%, rgba(93,135,255,.20), transparent 30%),
        rgba(6,12,24,.44) !important;
    }
    body.tm-dark-mode .box,
    body.tm-dark-mode .nav-tabs-custom{
      background:rgba(10,18,35,.62) !important;
      border-color:rgba(255,255,255,.12) !important;
      backdrop-filter:blur(7px);
      -webkit-backdrop-filter:blur(7px);
    }
    body.tm-dark-mode table.table,
    body.tm-dark-mode table.dataTable{
      background:rgba(8,15,30,.64) !important;
      border-color:rgba(255,255,255,.13) !important;
      color:#e5edf7 !important;
    }
    body.tm-dark-mode table.table thead tr th,
    body.tm-dark-mode table.dataTable thead tr th{
      color:#ffffff !important;
      background:linear-gradient(180deg,rgba(30,46,78,.92),rgba(18,30,54,.92)) !important;
      border-color:rgba(255,255,255,.12) !important;
    }
    body.tm-dark-mode table.table tbody tr td,
    body.tm-dark-mode table.dataTable tbody tr td{
      color:#e5edf7 !important;
      border-color:rgba(255,255,255,.10) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(odd),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(odd){
      background:rgba(15,25,45,.72) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(even),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(even){
      background:rgba(10,18,35,.58) !important;
    }
    body.tm-dark-mode table.table tbody tr:hover,
    body.tm-dark-mode table.dataTable tbody tr:hover{
      background:rgba(93,135,255,.20) !important;
    }
    body.tm-dark-mode .dataTables_wrapper .dataTables_length label,
    body.tm-dark-mode .dataTables_wrapper .dataTables_filter label,
    body.tm-dark-mode .dataTables_info{
      color:#dbeafe !important;
    }
    body.tm-dark-mode .pagination>li>a,
    body.tm-dark-mode .pagination>li>span{
      background:rgba(12,22,42,.72) !important;
      border-color:rgba(255,255,255,.13) !important;
      color:#dbeafe !important;
    }
    body.tm-dark-mode .tm-admin-particles{
      opacity:1 !important;
      filter:drop-shadow(0 0 8px rgba(255,255,255,.55));
    }

    /* Correccion final real: modo oscuro visible + tablas sobrias */
    body.tm-dark-mode,
    body.tm-dark-mode.tm-admin-page{
      background:#071121 !important;
    }
    body.tm-dark-mode .wrapper{
      background:transparent !important;
    }
    body.tm-dark-mode .content-wrapper{
      background:
        radial-gradient(circle at 12% 8%, rgba(96,165,250,.16), transparent 28%),
        radial-gradient(circle at 90% 15%, rgba(255,255,255,.08), transparent 24%),
        rgba(7,17,33,.36) !important;
    }
    body.tm-dark-mode .content-wrapper>.content-header,
    body.tm-dark-mode .content-wrapper>.content{
      background:transparent !important;
    }
    body.tm-dark-mode .tm-admin-particles{
      z-index:0 !important;
      opacity:1 !important;
      mix-blend-mode:screen;
      filter:drop-shadow(0 0 7px rgba(255,255,255,.55));
    }
    body.tm-admin-page:not(.tm-dark-mode) .tm-admin-particles{
      z-index:0 !important;
      opacity:1 !important;
      mix-blend-mode:multiply !important;
      filter:drop-shadow(0 0 5px rgba(37,99,235,.30));
    }
    body.tm-admin-page:not(.tm-dark-mode) .box,
    body.tm-admin-page:not(.tm-dark-mode) .nav-tabs-custom,
    body.tm-admin-page:not(.tm-dark-mode) .tm-dashboard-card,
    body.tm-admin-page:not(.tm-dark-mode) .tm-dashboard-panel,
    body.tm-admin-page:not(.tm-dark-mode) .tm-welcome{
      background:rgba(255,255,255,.72) !important;
      backdrop-filter:blur(4px);
      -webkit-backdrop-filter:blur(4px);
    }
    body.tm-dark-mode .box,
    body.tm-dark-mode .nav-tabs-custom,
    body.tm-dark-mode .tm-dashboard-card,
    body.tm-dark-mode .tm-dashboard-panel,
    body.tm-dark-mode .tm-welcome{
      background:rgba(12,24,46,.72) !important;
      border-color:rgba(148,163,184,.22) !important;
      box-shadow:0 18px 42px rgba(0,0,0,.26) !important;
    }
    .table-responsive,
    .box-body.table-responsive{
      padding:0 !important;
      border:0 !important;
      overflow-x:auto !important;
      overflow-y:visible !important;
      border-radius:12px !important;
    }
    .dataTables_wrapper{
      width:100% !important;
      overflow-x:auto !important;
      overflow-y:visible !important;
      padding-bottom:2px !important;
    }
    .dataTables_wrapper .row{
      display:block !important;
      margin-left:0 !important;
      margin-right:0 !important;
    }
    .dataTables_wrapper .row>[class*="col-"]{
      padding-left:0 !important;
      padding-right:0 !important;
    }
    .dataTables_wrapper .dataTables_length{
      float:left !important;
      margin:0 0 10px !important;
    }
    .dataTables_wrapper .dataTables_filter{
      float:right !important;
      margin:0 0 10px !important;
      text-align:right !important;
    }
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label{
      display:inline-flex !important;
      align-items:center !important;
      gap:7px !important;
      margin:0 !important;
      color:#475569 !important;
      font-size:12px !important;
      font-weight:700 !important;
    }
    .dataTables_wrapper .dataTables_length select{
      width:68px !important;
      min-width:68px !important;
      height:32px !important;
      padding:4px 8px !important;
    }
    .dataTables_wrapper .dataTables_filter input{
      width:220px !important;
      max-width:100% !important;
      height:32px !important;
      padding:5px 10px !important;
    }
    table.table,
    table.dataTable{
      width:100% !important;
      min-width:0 !important;
      max-width:none !important;
      margin:0 !important;
      table-layout:auto !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      border:1px solid #dfe8f3 !important;
      border-radius:12px !important;
      overflow:hidden !important;
      background:#ffffff !important;
      font-size:12px !important;
    }
    table.table thead th,
    table.dataTable thead th{
      padding:9px 8px !important;
      color:#1e3a5f !important;
      background:#f3f7fc !important;
      border-color:#dfe8f3 !important;
      font-size:11px !important;
      font-weight:900 !important;
      line-height:1.18 !important;
      text-align:left !important;
      vertical-align:middle !important;
      white-space:nowrap !important;
      text-transform:none !important;
      letter-spacing:0 !important;
    }
    table.table tbody td,
    table.dataTable tbody td{
      padding:8px 8px !important;
      color:#223047 !important;
      border-color:#edf2f7 !important;
      background:transparent !important;
      font-size:12px !important;
      line-height:1.25 !important;
      vertical-align:middle !important;
      text-align:left !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
    }
    table.table tbody tr:nth-child(odd),
    table.dataTable tbody tr:nth-child(odd){
      background:#ffffff !important;
    }
    table.table tbody tr:nth-child(even),
    table.dataTable tbody tr:nth-child(even){
      background:#f8fbff !important;
    }
    table.table tbody tr:hover,
    table.dataTable tbody tr:hover{
      background:#eef5ff !important;
    }
    table.table th:first-child,
    table.table td:first-child,
    table.dataTable th:first-child,
    table.dataTable td:first-child{
      width:42px !important;
      min-width:42px !important;
      text-align:center !important;
      white-space:nowrap !important;
    }
    table.table th:last-child,
    table.table td:last-child,
    table.dataTable th:last-child,
    table.dataTable td:last-child,
    .table .acciones,
    .cliente-col-acciones{
      min-width:132px !important;
      width:132px !important;
      max-width:170px !important;
      text-align:center !important;
      white-space:normal !important;
      overflow:visible !important;
    }
    table.table td:last-child .btn-group,
    table.dataTable td:last-child .btn-group,
    .table .acciones .btn-group,
    .cliente-col-acciones .btn-group{
      display:inline-flex !important;
      flex-wrap:wrap !important;
      justify-content:center !important;
      align-items:center !important;
      gap:4px !important;
      width:auto !important;
      max-width:150px !important;
      margin:0 auto !important;
      float:none !important;
    }
    table.table td .btn,
    table.dataTable td .btn,
    table.table td a.btn,
    table.dataTable td a.btn,
    table.table td button.btn,
    table.dataTable td button.btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      min-width:30px !important;
      min-height:30px !important;
      margin:1px !important;
      padding:6px 8px !important;
      border-radius:8px !important;
      font-size:11px !important;
      font-weight:800 !important;
      line-height:1 !important;
      white-space:nowrap !important;
      box-shadow:none !important;
    }
    table.table td .btn i,
    table.dataTable td .btn i{
      margin:0 !important;
      line-height:1 !important;
    }
    .dataTables_info{
      float:left !important;
      padding-top:12px !important;
      color:#64748b !important;
      font-size:12px !important;
      font-weight:700 !important;
    }
    .dataTables_paginate{
      float:right !important;
      padding-top:8px !important;
      text-align:right !important;
      overflow-x:auto !important;
      max-width:100% !important;
    }
    .pagination{
      margin:0 !important;
      display:inline-flex !important;
      flex-wrap:wrap !important;
      gap:4px !important;
      justify-content:flex-end !important;
    }
    .pagination>li>a,
    .pagination>li>span{
      min-width:30px !important;
      border-radius:8px !important;
      color:#174b86 !important;
      border-color:#dfe8f3 !important;
      font-weight:800 !important;
      text-align:center !important;
    }
    .pagination>.active>a,
    .pagination>.active>span{
      color:#fff !important;
      background:#174b86 !important;
      border-color:#174b86 !important;
    }
    body.tm-dark-mode .dataTables_wrapper .dataTables_length label,
    body.tm-dark-mode .dataTables_wrapper .dataTables_filter label,
    body.tm-dark-mode .dataTables_info{
      color:#cbd5e1 !important;
    }
    body.tm-dark-mode table.table,
    body.tm-dark-mode table.dataTable{
      background:rgba(15,27,48,.76) !important;
      border-color:rgba(148,163,184,.22) !important;
      color:#e5edf7 !important;
    }
    body.tm-dark-mode table.table thead th,
    body.tm-dark-mode table.dataTable thead th{
      color:#dbeafe !important;
      background:rgba(30,48,82,.90) !important;
      border-color:rgba(148,163,184,.22) !important;
    }
    body.tm-dark-mode table.table tbody td,
    body.tm-dark-mode table.dataTable tbody td{
      color:#e5edf7 !important;
      border-color:rgba(148,163,184,.14) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(odd),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(odd){
      background:rgba(15,27,48,.70) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(even),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(even){
      background:rgba(20,36,62,.70) !important;
    }
    body.tm-dark-mode table.table tbody tr:hover,
    body.tm-dark-mode table.dataTable tbody tr:hover{
      background:rgba(37,99,235,.25) !important;
    }
    body.tm-dark-mode .dataTables_wrapper .dataTables_filter input,
    body.tm-dark-mode .dataTables_wrapper .dataTables_length select{
      background:rgba(15,27,48,.92) !important;
      color:#e5edf7 !important;
      border-color:rgba(148,163,184,.26) !important;
    }
    body.tm-dark-mode .pagination>li>a,
    body.tm-dark-mode .pagination>li>span{
      color:#dbeafe !important;
      background:rgba(15,27,48,.82) !important;
      border-color:rgba(148,163,184,.24) !important;
    }
    body.tm-dark-mode .pagination>.active>a,
    body.tm-dark-mode .pagination>.active>span{
      color:#fff !important;
      background:#3b82f6 !important;
      border-color:#3b82f6 !important;
    }
    @media(max-width:991px){
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_filter,
      .dataTables_info,
      .dataTables_paginate{
        float:none !important;
        text-align:left !important;
        width:100% !important;
      }
      .dataTables_wrapper .dataTables_filter{
        margin-top:8px !important;
      }
      .dataTables_wrapper .dataTables_filter input{
        width:100% !important;
      }
      table.table,
      table.dataTable{
        min-width:0 !important;
      }
    }

    /* Transparencia final suave para que las particulas respiren */
    body.tm-admin-page:not(.tm-dark-mode) .main-header .navbar{
      background:rgba(255,255,255,.40) !important;
      backdrop-filter:blur(5px);
      -webkit-backdrop-filter:blur(5px);
    }
    body.tm-admin-page:not(.tm-dark-mode) .content-wrapper{
      background:rgba(245,248,252,.02) !important;
    }
    body.tm-admin-page:not(.tm-dark-mode) .box,
    body.tm-admin-page:not(.tm-dark-mode) .nav-tabs-custom,
    body.tm-admin-page:not(.tm-dark-mode) .tm-dashboard-card,
    body.tm-admin-page:not(.tm-dark-mode) .tm-dashboard-panel,
    body.tm-admin-page:not(.tm-dark-mode) .tm-welcome,
    body.tm-admin-page:not(.tm-dark-mode) .breadcrumb{
      background:rgba(255,255,255,.42) !important;
      border-color:rgba(203,213,225,.48) !important;
      backdrop-filter:blur(5px);
      -webkit-backdrop-filter:blur(5px);
    }
    body.tm-admin-page:not(.tm-dark-mode) table.table,
    body.tm-admin-page:not(.tm-dark-mode) table.dataTable{
      background:rgba(255,255,255,.48) !important;
    }
    body.tm-admin-page:not(.tm-dark-mode) table.table tbody tr:nth-child(odd),
    body.tm-admin-page:not(.tm-dark-mode) table.dataTable tbody tr:nth-child(odd){
      background:rgba(255,255,255,.40) !important;
    }
    body.tm-admin-page:not(.tm-dark-mode) table.table tbody tr:nth-child(even),
    body.tm-admin-page:not(.tm-dark-mode) table.dataTable tbody tr:nth-child(even){
      background:rgba(248,251,255,.34) !important;
    }
    body.tm-dark-mode .main-header .navbar{
      background:rgba(8,17,34,.38) !important;
      backdrop-filter:blur(6px);
      -webkit-backdrop-filter:blur(6px);
    }
    body.tm-dark-mode .content-wrapper{
      background:rgba(7,17,33,.08) !important;
    }
    body.tm-dark-mode .box,
    body.tm-dark-mode .nav-tabs-custom,
    body.tm-dark-mode .tm-dashboard-card,
    body.tm-dark-mode .tm-dashboard-panel,
    body.tm-dark-mode .tm-welcome,
    body.tm-dark-mode .breadcrumb{
      background:rgba(12,24,46,.36) !important;
      border-color:rgba(148,163,184,.16) !important;
      backdrop-filter:blur(6px);
      -webkit-backdrop-filter:blur(6px);
    }
    body.tm-dark-mode table.table,
    body.tm-dark-mode table.dataTable{
      background:rgba(15,27,48,.36) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(odd),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(odd){
      background:rgba(15,27,48,.32) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(even),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(even){
      background:rgba(20,36,62,.28) !important;
    }

    /* Rediseño final global de tablas: mas compactas y acciones estables */
    .box-body.table-responsive,
    .table-responsive{
      width:100% !important;
      padding:0 !important;
      border:0 !important;
      overflow-x:auto !important;
      overflow-y:visible !important;
      border-radius:14px !important;
    }
    .dataTables_wrapper{
      width:100% !important;
      overflow:visible !important;
    }
    .dataTables_wrapper .row{
      margin-left:0 !important;
      margin-right:0 !important;
    }
    .dataTables_wrapper .row>[class*="col-"]{
      padding-left:0 !important;
      padding-right:0 !important;
    }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter{
      margin-bottom:9px !important;
    }
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label{
      display:inline-flex !important;
      align-items:center !important;
      gap:7px !important;
      margin:0 !important;
      font-size:11.5px !important;
      font-weight:800 !important;
      color:#42526b !important;
    }
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input{
      height:31px !important;
      border-radius:9px !important;
      border:1px solid rgba(203,213,225,.78) !important;
      box-shadow:none !important;
    }
    .dataTables_wrapper .dataTables_length select{
      width:62px !important;
      min-width:62px !important;
      padding:3px 7px !important;
    }
    .dataTables_wrapper .dataTables_filter input{
      width:clamp(150px,18vw,230px) !important;
      padding:4px 9px !important;
    }
    table.table,
    table.dataTable{
      width:100% !important;
      min-width:1080px !important;
      max-width:none !important;
      margin:0 !important;
      table-layout:auto !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      border:1px solid rgba(203,213,225,.68) !important;
      border-radius:14px !important;
      overflow:hidden !important;
      background:rgba(255,255,255,.46) !important;
      font-size:12.5px !important;
    }
    table.table thead th,
    table.dataTable thead th{
      padding:9px 8px !important;
      min-height:34px !important;
      color:#174b86 !important;
      background:rgba(236,242,255,.66) !important;
      border-color:rgba(203,213,225,.64) !important;
      font-size:11.8px !important;
      font-weight:900 !important;
      line-height:1.22 !important;
      text-align:center !important;
      vertical-align:middle !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      text-transform:none !important;
      letter-spacing:0 !important;
      min-width:76px !important;
    }
    table.table tbody td,
    table.dataTable tbody td{
      padding:8px 8px !important;
      color:#223047 !important;
      border-color:rgba(226,232,240,.66) !important;
      background:transparent !important;
      font-size:12.5px !important;
      line-height:1.28 !important;
      vertical-align:middle !important;
      text-align:left !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
      min-width:76px !important;
    }
    table.table tbody tr:nth-child(odd),
    table.dataTable tbody tr:nth-child(odd){
      background:rgba(255,255,255,.42) !important;
    }
    table.table tbody tr:nth-child(even),
    table.dataTable tbody tr:nth-child(even){
      background:rgba(248,251,255,.32) !important;
    }
    table.table tbody tr:hover,
    table.dataTable tbody tr:hover{
      background:rgba(236,242,255,.68) !important;
    }
    table.table th:first-child,
    table.table td:first-child,
    table.dataTable th:first-child,
    table.dataTable td:first-child{
      width:40px !important;
      min-width:40px !important;
      max-width:40px !important;
      text-align:center !important;
      white-space:nowrap !important;
    }
    table.table th:last-child,
    table.table td:last-child,
    table.dataTable th:last-child,
    table.dataTable td:last-child,
    .table .acciones,
    .cliente-col-acciones{
      width:144px !important;
      min-width:144px !important;
      max-width:168px !important;
      text-align:center !important;
      white-space:normal !important;
      overflow:visible !important;
    }
    table.table td:last-child,
    table.dataTable td:last-child,
    .table .acciones,
    .cliente-col-acciones{
      padding-left:4px !important;
      padding-right:4px !important;
    }
    table.table td:last-child .btn-group,
    table.dataTable td:last-child .btn-group,
    .table .acciones .btn-group,
    .cliente-col-acciones .btn-group{
      display:inline-grid !important;
      grid-template-columns:repeat(3, minmax(30px, auto)) !important;
      justify-content:center !important;
      align-items:center !important;
      gap:4px !important;
      max-width:144px !important;
      margin:0 auto !important;
      float:none !important;
    }
    table.table td .btn,
    table.dataTable td .btn,
    table.table td a.btn,
    table.dataTable td a.btn,
    table.table td button.btn,
    table.dataTable td button.btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      min-width:30px !important;
      min-height:30px !important;
      margin:1px !important;
      padding:5px 7px !important;
      border-radius:8px !important;
      font-size:11.5px !important;
      font-weight:850 !important;
      line-height:1.05 !important;
      white-space:normal !important;
      text-align:center !important;
      box-shadow:none !important;
    }
    table.table td .btn i,
    table.dataTable td .btn i{
      margin:0 !important;
      line-height:1 !important;
    }
    table.table td .label,
    table.dataTable td .label,
    table.table td .badge,
    table.dataTable td .badge{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      max-width:100% !important;
      white-space:normal !important;
      line-height:1.1 !important;
      border-radius:999px !important;
      padding:5px 7px !important;
    }
    table.table img,
    table.dataTable img{
      max-width:44px !important;
      max-height:44px !important;
      object-fit:contain !important;
    }
    .dataTables_info{
      padding-top:10px !important;
      font-size:12px !important;
      font-weight:800 !important;
      color:#61718b !important;
    }
    .dataTables_paginate{
      padding-top:7px !important;
      max-width:100% !important;
      overflow-x:auto !important;
    }
    .pagination{
      margin:0 !important;
      display:inline-flex !important;
      flex-wrap:wrap !important;
      gap:4px !important;
    }
    .pagination>li>a,
    .pagination>li>span{
      min-width:28px !important;
      min-height:28px !important;
      padding:5px 8px !important;
      border-radius:8px !important;
      font-size:11px !important;
      font-weight:850 !important;
      color:#174b86 !important;
      border-color:rgba(203,213,225,.76) !important;
      background:rgba(255,255,255,.58) !important;
    }
    .pagination>.active>a,
    .pagination>.active>span{
      color:#fff !important;
      background:#174b86 !important;
      border-color:#174b86 !important;
    }
    body.tm-dark-mode .dataTables_wrapper .dataTables_length label,
    body.tm-dark-mode .dataTables_wrapper .dataTables_filter label,
    body.tm-dark-mode .dataTables_info{
      color:#dbeafe !important;
    }
    body.tm-dark-mode .dataTables_wrapper .dataTables_length select,
    body.tm-dark-mode .dataTables_wrapper .dataTables_filter input{
      background:rgba(15,27,48,.56) !important;
      border-color:rgba(148,163,184,.20) !important;
      color:#e5edf7 !important;
    }
    body.tm-dark-mode table.table,
    body.tm-dark-mode table.dataTable{
      background:rgba(15,27,48,.34) !important;
      border-color:rgba(148,163,184,.16) !important;
      color:#e5edf7 !important;
    }
    body.tm-dark-mode table.table thead th,
    body.tm-dark-mode table.dataTable thead th{
      color:#eaf2ff !important;
      background:rgba(30,48,82,.52) !important;
      border-color:rgba(148,163,184,.16) !important;
    }
    body.tm-dark-mode table.table tbody td,
    body.tm-dark-mode table.dataTable tbody td{
      color:#e5edf7 !important;
      border-color:rgba(148,163,184,.12) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(odd),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(odd){
      background:rgba(15,27,48,.30) !important;
    }
    body.tm-dark-mode table.table tbody tr:nth-child(even),
    body.tm-dark-mode table.dataTable tbody tr:nth-child(even){
      background:rgba(20,36,62,.24) !important;
    }
    body.tm-dark-mode table.table tbody tr:hover,
    body.tm-dark-mode table.dataTable tbody tr:hover{
      background:rgba(93,135,255,.20) !important;
    }
    body.tm-dark-mode .pagination>li>a,
    body.tm-dark-mode .pagination>li>span{
      color:#dbeafe !important;
      background:rgba(15,27,48,.48) !important;
      border-color:rgba(148,163,184,.18) !important;
    }
    body.tm-dark-mode .pagination>.active>a,
    body.tm-dark-mode .pagination>.active>span{
      color:#fff !important;
      background:#3b82f6 !important;
      border-color:#3b82f6 !important;
    }
    @media(max-width:991px){
      table.table,
      table.dataTable{
        min-width:920px !important;
      }
      .dataTables_wrapper .dataTables_filter{
        text-align:left !important;
      }
      .dataTables_wrapper .dataTables_filter input{
        width:100% !important;
      }
    }
    /* Correccion definitiva: tablas sin columnas pisadas y acciones ordenadas */
    body.tm-admin-page .tm-dashboard-card,
    body.tm-admin-page .tm-dashboard-panel,
    body.tm-admin-page .box,
    body.tm-admin-page .box-body,
    body.tm-admin-page .tab-pane,
    body.tm-admin-page .nav-tabs-custom .tab-content{
      overflow:visible !important;
    }
    body.tm-admin-page .table-responsive,
    body.tm-admin-page .dataTables_wrapper,
    body.tm-admin-page .box-body:has(table.table),
    body.tm-admin-page .box-body:has(table.dataTable){
      max-width:100% !important;
      overflow-x:auto !important;
      overflow-y:visible !important;
    }
    body.tm-admin-page .box-body::-webkit-scrollbar,
    body.tm-admin-page .table-responsive::-webkit-scrollbar,
    body.tm-admin-page .dataTables_wrapper::-webkit-scrollbar{
      height:8px;
    }
    body.tm-admin-page .box-body::-webkit-scrollbar-thumb,
    body.tm-admin-page .table-responsive::-webkit-scrollbar-thumb,
    body.tm-admin-page .dataTables_wrapper::-webkit-scrollbar-thumb{
      background:rgba(23,75,134,.28);
      border-radius:999px;
    }
    body.tm-admin-page table.table,
    body.tm-admin-page table.dataTable{
      width:100% !important;
      min-width:100% !important;
      max-width:none !important;
      table-layout:auto !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      border:1px solid rgba(203,213,225,.78) !important;
      border-radius:14px !important;
      overflow:hidden !important;
      font-size:13px !important;
    }
    body.tm-admin-page table.table thead th,
    body.tm-admin-page table.dataTable thead th{
      padding:10px 12px !important;
      font-size:12px !important;
      line-height:1.25 !important;
      font-weight:900 !important;
      color:#174b86 !important;
      background:rgba(236,242,255,.72) !important;
      border-right:1px solid rgba(203,213,225,.82) !important;
      border-bottom:1px solid rgba(203,213,225,.82) !important;
      text-align:center !important;
      vertical-align:middle !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
    }
    body.tm-admin-page table.table tbody td,
    body.tm-admin-page table.dataTable tbody td{
      padding:9px 12px !important;
      font-size:13px !important;
      line-height:1.32 !important;
      color:#223047 !important;
      border-right:1px solid rgba(226,232,240,.82) !important;
      border-bottom:1px solid rgba(226,232,240,.82) !important;
      text-align:left !important;
      vertical-align:middle !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
    }
    body.tm-admin-page table.table thead th:last-child,
    body.tm-admin-page table.dataTable thead th:last-child,
    body.tm-admin-page table.table tbody td:last-child,
    body.tm-admin-page table.dataTable tbody td:last-child{
      border-right:0 !important;
    }
    body.tm-admin-page table.table th:first-child,
    body.tm-admin-page table.table td:first-child,
    body.tm-admin-page table.dataTable th:first-child,
    body.tm-admin-page table.dataTable td:first-child,
    body.tm-admin-page table.table th:last-child,
    body.tm-admin-page table.table td:last-child,
    body.tm-admin-page table.dataTable th:last-child,
    body.tm-admin-page table.dataTable td:last-child{
      width:auto !important;
      min-width:96px !important;
      max-width:none !important;
      white-space:normal !important;
    }
    body.tm-admin-page table.table td:has(.btn),
    body.tm-admin-page table.dataTable td:has(.btn),
    body.tm-admin-page .table .acciones,
    body.tm-admin-page .cliente-col-acciones{
      width:180px !important;
      min-width:180px !important;
      max-width:240px !important;
      padding-left:7px !important;
      padding-right:7px !important;
      text-align:center !important;
      white-space:normal !important;
      overflow:visible !important;
    }
    body.tm-admin-page table.table td:has(.btn) .btn-group,
    body.tm-admin-page table.dataTable td:has(.btn) .btn-group,
    body.tm-admin-page .table .acciones .btn-group,
    body.tm-admin-page .cliente-col-acciones .btn-group{
      display:inline-flex !important;
      flex-wrap:wrap !important;
      justify-content:center !important;
      align-items:center !important;
      gap:5px !important;
      max-width:220px !important;
      margin:0 auto !important;
      float:none !important;
    }
    body.tm-admin-page table.table td .btn,
    body.tm-admin-page table.dataTable td .btn,
    body.tm-admin-page table.table td a.btn,
    body.tm-admin-page table.dataTable td a.btn,
    body.tm-admin-page table.table td button.btn,
    body.tm-admin-page table.dataTable td button.btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      min-width:32px !important;
      min-height:31px !important;
      margin:1px !important;
      padding:6px 8px !important;
      border-radius:8px !important;
      font-size:12px !important;
      font-weight:850 !important;
      line-height:1.05 !important;
      white-space:nowrap !important;
      text-align:center !important;
    }
    body.tm-admin-page table.table td .label,
    body.tm-admin-page table.dataTable td .label,
    body.tm-admin-page table.table td .badge,
    body.tm-admin-page table.dataTable td .badge{
      white-space:nowrap !important;
      max-width:none !important;
    }
    body.tm-admin-page table.table img,
    body.tm-admin-page table.dataTable img{
      max-width:52px !important;
      max-height:52px !important;
      object-fit:contain !important;
    }
    body.tm-dark-mode table.table thead th,
    body.tm-dark-mode table.dataTable thead th{
      color:#eaf2ff !important;
      background:rgba(30,48,82,.56) !important;
      border-right-color:rgba(148,163,184,.24) !important;
      border-bottom-color:rgba(148,163,184,.24) !important;
    }
    body.tm-dark-mode table.table tbody td,
    body.tm-dark-mode table.dataTable tbody td{
      color:#e5edf7 !important;
      border-right-color:rgba(148,163,184,.18) !important;
      border-bottom-color:rgba(148,163,184,.18) !important;
    }

    /* Tablas TechMind 2026: columnas clasificadas, acciones siempre visibles */
    body.tm-admin-page .tm-dashboard-card,
    body.tm-admin-page .tm-dashboard-panel,
    body.tm-admin-page .box,
    body.tm-admin-page .box-body,
    body.tm-admin-page .tab-pane,
    body.tm-admin-page .nav-tabs-custom .tab-content{
      overflow:visible !important;
    }
    body.tm-admin-page .box-body:has(table.table),
    body.tm-admin-page .box-body:has(table.dataTable){
      overflow:visible !important;
    }
    body.tm-admin-page .tm-table-scroll,
    body.tm-admin-page .table-responsive,
    body.tm-admin-page .dataTables_wrapper{
      max-width:100% !important;
      overflow-x:auto !important;
      overflow-y:visible !important;
      border-radius:14px !important;
    }
    body.tm-admin-page .tm-table-scroll::-webkit-scrollbar,
    body.tm-admin-page .table-responsive::-webkit-scrollbar,
    body.tm-admin-page .dataTables_wrapper::-webkit-scrollbar{
      height:8px;
    }
    body.tm-admin-page .tm-table-scroll::-webkit-scrollbar-thumb,
    body.tm-admin-page .table-responsive::-webkit-scrollbar-thumb,
    body.tm-admin-page .dataTables_wrapper::-webkit-scrollbar-thumb{
      background:rgba(23,75,134,.32);
      border-radius:999px;
    }
    body.tm-admin-page table.table,
    body.tm-admin-page table.dataTable,
    body.tm-admin-page table.tm-table-fit{
      width:100% !important;
      min-width:0 !important;
      table-layout:fixed !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      border:1px solid rgba(203,213,225,.78) !important;
      border-radius:14px !important;
      overflow:hidden !important;
      background:rgba(255,255,255,.50) !important;
      font-size:12.5px !important;
    }
    body.tm-admin-page table.table thead th,
    body.tm-admin-page table.dataTable thead th,
    body.tm-admin-page table.tm-table-fit thead th{
      padding:8px 7px !important;
      font-size:11.5px !important;
      line-height:1.18 !important;
      color:#174b86 !important;
      background:rgba(236,242,255,.74) !important;
      border-right:1px solid rgba(203,213,225,.82) !important;
      border-bottom:1px solid rgba(203,213,225,.82) !important;
      text-align:center !important;
      vertical-align:middle !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
    }
    body.tm-admin-page table.table tbody td,
    body.tm-admin-page table.dataTable tbody td,
    body.tm-admin-page table.tm-table-fit tbody td{
      padding:7px 7px !important;
      font-size:12.5px !important;
      line-height:1.22 !important;
      color:#223047 !important;
      border-right:1px solid rgba(226,232,240,.82) !important;
      border-bottom:1px solid rgba(226,232,240,.82) !important;
      text-align:left !important;
      vertical-align:middle !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
    }
    body.tm-admin-page table.table thead th:last-child,
    body.tm-admin-page table.dataTable thead th:last-child,
    body.tm-admin-page table.table tbody td:last-child,
    body.tm-admin-page table.dataTable tbody td:last-child{
      border-right:0 !important;
    }
    body.tm-admin-page .tm-col-index{
      width:42px !important;
      min-width:42px !important;
      max-width:42px !important;
      text-align:center !important;
    }
    body.tm-admin-page .tm-col-image{
      width:66px !important;
      min-width:66px !important;
      max-width:66px !important;
      text-align:center !important;
    }
    body.tm-admin-page .tm-col-code{
      width:92px !important;
      min-width:92px !important;
      max-width:108px !important;
      text-align:center !important;
    }
    body.tm-admin-page .tm-col-contact{
      width:125px !important;
      min-width:125px !important;
      max-width:145px !important;
    }
    body.tm-admin-page .tm-col-status{
      width:108px !important;
      min-width:108px !important;
      max-width:120px !important;
      text-align:center !important;
    }
    body.tm-admin-page .tm-col-money{
      width:88px !important;
      min-width:88px !important;
      max-width:100px !important;
      text-align:center !important;
    }
    body.tm-admin-page .tm-col-date{
      width:120px !important;
      min-width:120px !important;
      max-width:135px !important;
      text-align:center !important;
    }
    body.tm-admin-page .tm-col-desc{
      width:230px !important;
      min-width:230px !important;
      max-width:270px !important;
    }
    body.tm-admin-page .tm-col-actions,
    body.tm-admin-page .table .acciones,
    body.tm-admin-page .cliente-col-acciones{
      width:190px !important;
      min-width:190px !important;
      max-width:220px !important;
      text-align:center !important;
      white-space:normal !important;
      overflow:visible !important;
    }
    body.tm-admin-page .tm-col-actions .btn-group,
    body.tm-admin-page .table .acciones .btn-group,
    body.tm-admin-page .cliente-col-acciones .btn-group{
      display:flex !important;
      flex-wrap:wrap !important;
      align-items:center !important;
      justify-content:center !important;
      gap:4px !important;
      max-width:185px !important;
      margin:0 auto !important;
      float:none !important;
    }
    body.tm-admin-page .tm-col-actions .btn,
    body.tm-admin-page .table .acciones .btn,
    body.tm-admin-page .cliente-col-acciones .btn,
    body.tm-admin-page table.table td .btn,
    body.tm-admin-page table.dataTable td .btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      min-width:30px !important;
      min-height:29px !important;
      margin:1px !important;
      padding:5px 7px !important;
      border-radius:8px !important;
      font-size:11.5px !important;
      font-weight:850 !important;
      line-height:1.05 !important;
      white-space:nowrap !important;
      text-align:center !important;
    }
    body.tm-admin-page table.table .label,
    body.tm-admin-page table.dataTable .label,
    body.tm-admin-page table.table .badge,
    body.tm-admin-page table.dataTable .badge{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      white-space:normal !important;
      max-width:105px !important;
      line-height:1.08 !important;
      padding:5px 7px !important;
      border-radius:999px !important;
    }
    body.tm-admin-page table.table img,
    body.tm-admin-page table.dataTable img{
      max-width:48px !important;
      max-height:48px !important;
      object-fit:contain !important;
    }
    body.tm-dark-mode table.table,
    body.tm-dark-mode table.dataTable,
    body.tm-dark-mode table.tm-table-fit{
      background:rgba(15,27,48,.34) !important;
      border-color:rgba(148,163,184,.18) !important;
    }
    body.tm-dark-mode table.table thead th,
    body.tm-dark-mode table.dataTable thead th{
      color:#eaf2ff !important;
      background:rgba(30,48,82,.58) !important;
      border-right-color:rgba(148,163,184,.24) !important;
      border-bottom-color:rgba(148,163,184,.24) !important;
    }
    body.tm-dark-mode table.table tbody td,
    body.tm-dark-mode table.dataTable tbody td{
      color:#e5edf7 !important;
      border-right-color:rgba(148,163,184,.18) !important;
      border-bottom-color:rgba(148,163,184,.18) !important;
    }

    /* Automatizacion de ancho por celda: gana sobre reglas antiguas */
    body.tm-admin-page table.tm-table-fit{
      width:100% !important;
      max-width:100% !important;
      table-layout:fixed !important;
    }
    body.tm-admin-page table.tm-table-fit th,
    body.tm-admin-page table.tm-table-fit td{
      box-sizing:border-box !important;
      overflow:visible !important;
      text-overflow:clip !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-index{
      width:30px !important;
      min-width:30px !important;
      max-width:30px !important;
      text-align:center !important;
      white-space:nowrap !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-xs{
      width:46px !important;
      min-width:46px !important;
      max-width:46px !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-sm{
      width:82px !important;
      min-width:82px !important;
      max-width:82px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-md{
      width:118px !important;
      min-width:118px !important;
      max-width:118px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-lg{
      width:170px !important;
      min-width:170px !important;
      max-width:170px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-xl{
      width:240px !important;
      min-width:240px !important;
      max-width:240px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-image{
      width:70px !important;
      min-width:70px !important;
      max-width:70px !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-code{
      width:96px !important;
      min-width:96px !important;
      max-width:96px !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-contact{
      width:130px !important;
      min-width:130px !important;
      max-width:130px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-status{
      width:112px !important;
      min-width:112px !important;
      max-width:112px !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-money{
      width:92px !important;
      min-width:92px !important;
      max-width:92px !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-date{
      width:124px !important;
      min-width:124px !important;
      max-width:124px !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-desc{
      width:230px !important;
      min-width:210px !important;
      max-width:300px !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      text-overflow:clip !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions{
      width:190px !important;
      min-width:190px !important;
      max-width:190px !important;
      text-align:center !important;
      overflow:visible !important;
      text-overflow:clip !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn-group{
      display:flex !important;
      flex-wrap:wrap !important;
      justify-content:center !important;
      gap:4px !important;
      max-width:184px !important;
      margin:0 auto !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn{
      min-width:30px !important;
      min-height:28px !important;
      padding:5px 7px !important;
      font-size:11px !important;
    }
    body.tm-admin-page .tm-table-scroll,
    body.tm-admin-page .dataTables_wrapper,
    body.tm-admin-page .table-responsive{
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit{
      margin-left:auto !important;
      margin-right:auto !important;
    }
    body.tm-admin-page table.tm-table-fit th,
    body.tm-admin-page table.tm-table-fit td{
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-desc{
      text-align:center !important;
    }

    /* Tablas sin scroll: encajar completo dentro del ancho disponible */
    body.tm-admin-page .tm-table-no-scroll,
    body.tm-admin-page .tm-table-scroll,
    body.tm-admin-page .table-responsive,
    body.tm-admin-page .dataTables_wrapper,
    body.tm-admin-page .box-body:has(table),
    body.tm-admin-page .tab-pane:has(table){
      overflow-x:visible !important;
      overflow-y:visible !important;
      max-width:100% !important;
    }
    body.tm-admin-page table.table,
    body.tm-admin-page table.dataTable,
    body.tm-admin-page table.tm-table-fit{
      width:100% !important;
      min-width:0 !important;
      max-width:100% !important;
      table-layout:fixed !important;
      margin-left:auto !important;
      margin-right:auto !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      font-size:11.5px !important;
    }
    body.tm-admin-page table.table th,
    body.tm-admin-page table.dataTable th,
    body.tm-admin-page table.tm-table-fit th,
    body.tm-admin-page table.table td,
    body.tm-admin-page table.dataTable td,
    body.tm-admin-page table.tm-table-fit td{
      box-sizing:border-box !important;
      padding:6px 5px !important;
      line-height:1.18 !important;
      white-space:normal !important;
      overflow:visible !important;
      text-overflow:clip !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-align:center !important;
      vertical-align:middle !important;
    }
    body.tm-admin-page table.table th,
    body.tm-admin-page table.dataTable th,
    body.tm-admin-page table.tm-table-fit th{
      font-size:10.5px !important;
      font-weight:900 !important;
    }
    body.tm-admin-page table.table td,
    body.tm-admin-page table.dataTable td,
    body.tm-admin-page table.tm-table-fit td{
      font-size:11.2px !important;
      font-weight:650 !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-index{
      width:4% !important;
      min-width:0 !important;
      max-width:none !important;
      font-size:10.5px !important;
      padding-left:2px !important;
      padding-right:2px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-xs{
      width:5% !important;
      min-width:0 !important;
      max-width:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-image{
      width:6% !important;
      min-width:0 !important;
      max-width:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-code{
      width:8% !important;
      min-width:0 !important;
      max-width:none !important;
      font-size:10.8px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-sm{
      width:8% !important;
      min-width:0 !important;
      max-width:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-money{
      width:7% !important;
      min-width:0 !important;
      max-width:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-status{
      width:9% !important;
      min-width:0 !important;
      max-width:none !important;
      font-size:10.5px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-date{
      width:10% !important;
      min-width:0 !important;
      max-width:none !important;
      font-size:10.2px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-contact,
    body.tm-admin-page table.tm-table-fit .tm-col-md{
      width:11% !important;
      min-width:0 !important;
      max-width:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-lg{
      width:14% !important;
      min-width:0 !important;
      max-width:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-desc,
    body.tm-admin-page table.tm-table-fit .tm-col-xl{
      width:18% !important;
      min-width:0 !important;
      max-width:none !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions{
      width:13% !important;
      min-width:0 !important;
      max-width:none !important;
      overflow:visible !important;
      padding-left:3px !important;
      padding-right:3px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn-group,
    body.tm-admin-page table.tm-table-fit .tm-col-actions{
      white-space:normal !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn-group{
      display:flex !important;
      flex-wrap:wrap !important;
      justify-content:center !important;
      align-items:center !important;
      gap:3px !important;
      width:100% !important;
      max-width:100% !important;
      margin:0 auto !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn,
    body.tm-admin-page table.tm-table-fit td .btn{
      min-width:26px !important;
      min-height:25px !important;
      padding:4px 6px !important;
      margin:1px !important;
      font-size:10.5px !important;
      line-height:1 !important;
      white-space:normal !important;
    }
    body.tm-admin-page table.tm-table-fit .label,
    body.tm-admin-page table.tm-table-fit .badge{
      max-width:100% !important;
      white-space:normal !important;
      word-break:break-word !important;
      font-size:10px !important;
      line-height:1.05 !important;
      padding:4px 5px !important;
    }
    body.tm-admin-page table.tm-table-fit img{
      max-width:38px !important;
      max-height:38px !important;
      object-fit:contain !important;
    }
    @media(max-width:1400px){
      body.tm-admin-page table.table,
      body.tm-admin-page table.dataTable,
      body.tm-admin-page table.tm-table-fit{
        font-size:10.5px !important;
      }
      body.tm-admin-page table.table th,
      body.tm-admin-page table.dataTable th,
      body.tm-admin-page table.tm-table-fit th,
      body.tm-admin-page table.table td,
      body.tm-admin-page table.dataTable td,
      body.tm-admin-page table.tm-table-fit td{
        padding:5px 4px !important;
        font-size:10.2px !important;
        line-height:1.12 !important;
      }
      body.tm-admin-page table.tm-table-fit .tm-col-actions .btn,
      body.tm-admin-page table.tm-table-fit td .btn{
        min-width:24px !important;
        min-height:24px !important;
        padding:3px 5px !important;
        font-size:9.8px !important;
      }
    }

    /* Correccion final sin scroll: nada se sale de su celda */
    body.tm-admin-page .tm-table-no-scroll,
    body.tm-admin-page .tm-table-scroll,
    body.tm-admin-page .table-responsive,
    body.tm-admin-page .dataTables_wrapper{
      overflow-x:visible !important;
      overflow-y:visible !important;
      width:100% !important;
      max-width:100% !important;
    }
    body.tm-admin-page table.tm-table-fit,
    body.tm-admin-page table.table,
    body.tm-admin-page table.dataTable{
      width:100% !important;
      min-width:0 !important;
      max-width:100% !important;
      table-layout:fixed !important;
      margin:0 auto !important;
    }
    body.tm-admin-page table.tm-table-fit th,
    body.tm-admin-page table.tm-table-fit td{
      min-width:0 !important;
      max-width:none !important;
      overflow:visible !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-overflow:clip !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-index{
      width:3.5% !important;
      padding-left:1px !important;
      padding-right:1px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-code,
    body.tm-admin-page table.tm-table-fit .tm-col-money,
    body.tm-admin-page table.tm-table-fit .tm-col-xs,
    body.tm-admin-page table.tm-table-fit .tm-col-sm{
      width:7% !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-date,
    body.tm-admin-page table.tm-table-fit .tm-col-status{
      width:9% !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-contact,
    body.tm-admin-page table.tm-table-fit .tm-col-md{
      width:10% !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-desc,
    body.tm-admin-page table.tm-table-fit .tm-col-lg,
    body.tm-admin-page table.tm-table-fit .tm-col-xl{
      width:16% !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions{
      width:12% !important;
      padding-left:2px !important;
      padding-right:2px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-status .label,
    body.tm-admin-page table.tm-table-fit .tm-col-status .badge,
    body.tm-admin-page table.tm-table-fit .tm-col-status span[class*="label"],
    body.tm-admin-page table.tm-table-fit .tm-col-status span[class*="badge"],
    body.tm-admin-page table.tm-table-fit .tm-col-status .btn,
    body.tm-admin-page table.tm-table-fit td .label,
    body.tm-admin-page table.tm-table-fit td .badge{
      display:block !important;
      width:100% !important;
      min-width:0 !important;
      max-width:100% !important;
      height:auto !important;
      margin:0 auto !important;
      padding:4px 4px !important;
      white-space:normal !important;
      overflow:visible !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-overflow:clip !important;
      line-height:1.08 !important;
      text-align:center !important;
      box-sizing:border-box !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-status .label,
    body.tm-admin-page table.tm-table-fit .tm-col-status .badge,
    body.tm-admin-page table.tm-table-fit .tm-col-status span[class*="label"],
    body.tm-admin-page table.tm-table-fit .tm-col-status span[class*="badge"]{
      font-size:9px !important;
      border-radius:7px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn-group{
      display:flex !important;
      flex-direction:column !important;
      align-items:stretch !important;
      justify-content:center !important;
      gap:3px !important;
      width:100% !important;
      max-width:100% !important;
      margin:0 !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn,
    body.tm-admin-page table.tm-table-fit .tm-col-actions a.btn,
    body.tm-admin-page table.tm-table-fit .tm-col-actions button.btn{
      display:flex !important;
      width:100% !important;
      max-width:100% !important;
      min-width:0 !important;
      min-height:23px !important;
      margin:1px 0 !important;
      padding:4px 4px !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      line-height:1.05 !important;
      font-size:9.8px !important;
      text-align:center !important;
      box-sizing:border-box !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn i{
      flex:0 0 auto !important;
    }

    /* NORMALIZACION FINAL DE TABLAS TECHMIND
       Esta capa gana sobre estilos antiguos y evita scroll horizontal. */
    body.tm-admin-page .box,
    body.tm-admin-page .box-body,
    body.tm-admin-page .tab-pane,
    body.tm-admin-page .nav-tabs-custom,
    body.tm-admin-page .nav-tabs-custom .tab-content,
    body.tm-admin-page .tm-dashboard-card,
    body.tm-admin-page .tm-dashboard-panel{
      overflow:visible !important;
    }
    body.tm-admin-page .tm-table-no-scroll,
    body.tm-admin-page .tm-table-scroll,
    body.tm-admin-page .table-responsive,
    body.tm-admin-page .dataTables_wrapper{
      width:100% !important;
      min-width:0 !important;
      max-width:100% !important;
      overflow-x:visible !important;
      overflow-y:visible !important;
      text-align:center !important;
      border-radius:12px !important;
    }
    body.tm-admin-page .dataTables_wrapper .row{
      width:100% !important;
      margin-left:0 !important;
      margin-right:0 !important;
      display:flex !important;
      flex-wrap:wrap !important;
      align-items:center !important;
      justify-content:space-between !important;
      gap:8px !important;
    }
    body.tm-admin-page .dataTables_wrapper .row>[class*="col-"]{
      width:auto !important;
      max-width:100% !important;
      float:none !important;
      padding-left:0 !important;
      padding-right:0 !important;
      flex:0 1 auto !important;
    }
    body.tm-admin-page .dataTables_wrapper .dataTables_length,
    body.tm-admin-page .dataTables_wrapper .dataTables_filter{
      margin:4px 0 10px !important;
      text-align:left !important;
      white-space:normal !important;
    }
    body.tm-admin-page .dataTables_wrapper .dataTables_filter{
      margin-left:auto !important;
      text-align:right !important;
    }
    body.tm-admin-page table.tm-table-fit,
    body.tm-admin-page table.table.tm-table-fit,
    body.tm-admin-page table.dataTable.tm-table-fit{
      width:100% !important;
      min-width:0 !important;
      max-width:100% !important;
      table-layout:fixed !important;
      margin:0 auto !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      background:rgba(255,255,255,.48) !important;
      border:1px solid rgba(191,209,232,.82) !important;
      border-radius:12px !important;
      overflow:hidden !important;
    }
    body.tm-admin-page table.tm-table-fit th,
    body.tm-admin-page table.tm-table-fit td{
      box-sizing:border-box !important;
      min-width:0 !important;
      max-width:none !important;
      height:auto !important;
      padding:5px 4px !important;
      white-space:normal !important;
      overflow:visible !important;
      text-overflow:clip !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-align:center !important;
      vertical-align:middle !important;
      border-right:1px solid rgba(205,218,236,.82) !important;
      border-bottom:1px solid rgba(218,228,240,.82) !important;
    }
    body.tm-admin-page table.tm-table-fit th{
      color:#174b86 !important;
      background:rgba(232,241,255,.72) !important;
      font-size:10.8px !important;
      line-height:1.12 !important;
      font-weight:900 !important;
      letter-spacing:0 !important;
    }
    body.tm-admin-page table.tm-table-fit td{
      color:#223047 !important;
      font-size:11.2px !important;
      line-height:1.14 !important;
      font-weight:650 !important;
    }
    body.tm-admin-page table.tm-table-fit th:last-child,
    body.tm-admin-page table.tm-table-fit td:last-child{
      border-right:0 !important;
    }
    body.tm-admin-page table.tm-table-fit th.tm-col-index,
    body.tm-admin-page table.tm-table-fit td.tm-col-index{
      width:30px !important;
      min-width:30px !important;
      max-width:30px !important;
      padding-left:1px !important;
      padding-right:1px !important;
      white-space:nowrap !important;
      overflow:hidden !important;
      font-size:10px !important;
      background-image:none !important;
    }
    body.tm-admin-page table.tm-table-fit th.tm-col-index.sorting,
    body.tm-admin-page table.tm-table-fit th.tm-col-index.sorting_asc,
    body.tm-admin-page table.tm-table-fit th.tm-col-index.sorting_desc{
      background-image:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-image{
      width:5.2% !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-code,
    body.tm-admin-page table.tm-table-fit .tm-col-xs,
    body.tm-admin-page table.tm-table-fit .tm-col-sm{
      width:7% !important;
      font-size:10.4px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-money{
      width:7.4% !important;
      font-size:10.6px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-status{
      width:9.4% !important;
      font-size:9.8px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-date{
      width:9.8% !important;
      font-size:9.8px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-contact,
    body.tm-admin-page table.tm-table-fit .tm-col-md{
      width:10.5% !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-desc,
    body.tm-admin-page table.tm-table-fit .tm-col-lg,
    body.tm-admin-page table.tm-table-fit .tm-col-xl{
      width:auto !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions,
    body.tm-admin-page table.tm-table-fit td:has(.btn),
    body.tm-admin-page table.tm-table-fit .acciones,
    body.tm-admin-page table.tm-table-fit .cliente-col-acciones{
      width:11.8% !important;
      min-width:0 !important;
      max-width:none !important;
      padding-left:3px !important;
      padding-right:3px !important;
      overflow:visible !important;
      white-space:normal !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-status .label,
    body.tm-admin-page table.tm-table-fit .tm-col-status .badge,
    body.tm-admin-page table.tm-table-fit td .label,
    body.tm-admin-page table.tm-table-fit td .badge{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      width:100% !important;
      max-width:100% !important;
      min-width:0 !important;
      height:auto !important;
      margin:1px auto !important;
      padding:3px 4px !important;
      white-space:normal !important;
      overflow:visible !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-overflow:clip !important;
      line-height:1.05 !important;
      font-size:8.8px !important;
      border-radius:7px !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn-group,
    body.tm-admin-page table.tm-table-fit td:has(.btn) .btn-group,
    body.tm-admin-page table.tm-table-fit .acciones .btn-group,
    body.tm-admin-page table.tm-table-fit .cliente-col-acciones .btn-group{
      display:flex !important;
      flex-wrap:wrap !important;
      align-items:center !important;
      justify-content:center !important;
      gap:3px !important;
      width:100% !important;
      max-width:100% !important;
      margin:0 auto !important;
      float:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn,
    body.tm-admin-page table.tm-table-fit td:has(.btn) .btn,
    body.tm-admin-page table.tm-table-fit .acciones .btn,
    body.tm-admin-page table.tm-table-fit .cliente-col-acciones .btn,
    body.tm-admin-page table.tm-table-fit td a.btn,
    body.tm-admin-page table.tm-table-fit td button.btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      width:auto !important;
      min-width:24px !important;
      max-width:100% !important;
      min-height:23px !important;
      margin:1px !important;
      padding:3px 5px !important;
      white-space:normal !important;
      overflow:visible !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-overflow:clip !important;
      line-height:1.02 !important;
      font-size:9.4px !important;
      font-weight:850 !important;
      text-align:center !important;
      border-radius:7px !important;
      box-sizing:border-box !important;
    }
    body.tm-admin-page table.tm-table-fit td .btn i,
    body.tm-admin-page table.tm-table-fit td .fa{
      flex:0 0 auto !important;
      margin-right:2px !important;
    }
    body.tm-admin-page table.tm-table-fit img{
      display:block !important;
      width:auto !important;
      max-width:34px !important;
      max-height:34px !important;
      object-fit:contain !important;
      margin:0 auto !important;
    }
    body.tm-admin-page .dataTables_wrapper .dataTables_info,
    body.tm-admin-page .dataTables_wrapper .dataTables_paginate{
      float:none !important;
      width:auto !important;
      margin-top:10px !important;
      white-space:normal !important;
      text-align:left !important;
      font-size:11px !important;
    }
    body.tm-admin-page .dataTables_wrapper .dataTables_paginate{
      margin-left:auto !important;
      text-align:right !important;
    }
    body.tm-admin-page .dataTables_wrapper .dataTables_paginate .paginate_button,
    body.tm-admin-page .dataTables_wrapper .dataTables_paginate .paginate_button a{
      padding:4px 8px !important;
      margin:1px !important;
      border-radius:8px !important;
      font-size:10.5px !important;
      line-height:1.1 !important;
    }
    body.tm-dark-mode table.tm-table-fit{
      background:rgba(14,27,49,.44) !important;
      border-color:rgba(147,197,253,.22) !important;
    }
    body.tm-dark-mode table.tm-table-fit th{
      color:#f8fbff !important;
      background:rgba(37,70,119,.60) !important;
      border-right-color:rgba(147,197,253,.22) !important;
      border-bottom-color:rgba(147,197,253,.24) !important;
    }
    body.tm-dark-mode table.tm-table-fit td{
      color:#edf5ff !important;
      border-right-color:rgba(147,197,253,.16) !important;
      border-bottom-color:rgba(147,197,253,.16) !important;
    }
    @media(max-width:1500px){
      body.tm-admin-page table.tm-table-fit th{
        font-size:10px !important;
        padding:4px 3px !important;
      }
      body.tm-admin-page table.tm-table-fit td{
        font-size:10.2px !important;
        padding:4px 3px !important;
        line-height:1.08 !important;
      }
      body.tm-admin-page table.tm-table-fit th.tm-col-index,
      body.tm-admin-page table.tm-table-fit td.tm-col-index{
        width:26px !important;
        min-width:26px !important;
        max-width:26px !important;
        font-size:9px !important;
      }
      body.tm-admin-page table.tm-table-fit .tm-col-actions,
      body.tm-admin-page table.tm-table-fit td:has(.btn){
        width:12.8% !important;
      }
      body.tm-admin-page table.tm-table-fit .tm-col-actions .btn,
      body.tm-admin-page table.tm-table-fit td:has(.btn) .btn,
      body.tm-admin-page table.tm-table-fit td a.btn,
      body.tm-admin-page table.tm-table-fit td button.btn{
        min-width:22px !important;
        min-height:22px !important;
        padding:3px 4px !important;
        font-size:8.8px !important;
      }
    }

    /* Correccion real de encabezados/estados: sin flechas sobre texto y sin invasion de celdas */
    body.tm-admin-page table.tm-table-fit th.sorting,
    body.tm-admin-page table.tm-table-fit th.sorting_asc,
    body.tm-admin-page table.tm-table-fit th.sorting_desc,
    body.tm-admin-page table.tm-table-fit th.sorting_disabled,
    body.tm-admin-page table.table th.sorting,
    body.tm-admin-page table.table th.sorting_asc,
    body.tm-admin-page table.table th.sorting_desc,
    body.tm-admin-page table.table th.sorting_disabled,
    body.tm-admin-page table.dataTable th.sorting,
    body.tm-admin-page table.dataTable th.sorting_asc,
    body.tm-admin-page table.dataTable th.sorting_desc,
    body.tm-admin-page table.dataTable th.sorting_disabled{
      background-image:none !important;
      padding-right:6px !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-align:center !important;
    }
    body.tm-admin-page table.tm-table-fit th.sorting:before,
    body.tm-admin-page table.tm-table-fit th.sorting:after,
    body.tm-admin-page table.tm-table-fit th.sorting_asc:before,
    body.tm-admin-page table.tm-table-fit th.sorting_asc:after,
    body.tm-admin-page table.tm-table-fit th.sorting_desc:before,
    body.tm-admin-page table.tm-table-fit th.sorting_desc:after,
    body.tm-admin-page table.dataTable th.sorting:before,
    body.tm-admin-page table.dataTable th.sorting:after,
    body.tm-admin-page table.dataTable th.sorting_asc:before,
    body.tm-admin-page table.dataTable th.sorting_asc:after,
    body.tm-admin-page table.dataTable th.sorting_desc:before,
    body.tm-admin-page table.dataTable th.sorting_desc:after{
      display:none !important;
      content:"" !important;
    }
    body.tm-admin-page table.tm-table-fit th.tm-col-status,
    body.tm-admin-page table.tm-table-fit td.tm-col-status{
      width:140px !important;
      min-width:140px !important;
      max-width:140px !important;
      overflow:hidden !important;
      padding-left:5px !important;
      padding-right:5px !important;
    }
    body.tm-admin-page table.tm-table-fit td.tm-col-status .label,
    body.tm-admin-page table.tm-table-fit td.tm-col-status .badge,
    body.tm-admin-page table.tm-table-fit td.tm-col-status span[class*="label"],
    body.tm-admin-page table.tm-table-fit td.tm-col-status span[class*="badge"]{
      display:block !important;
      width:100% !important;
      min-width:0 !important;
      max-width:100% !important;
      height:auto !important;
      margin:0 auto !important;
      padding:5px 4px !important;
      box-sizing:border-box !important;
      white-space:normal !important;
      overflow:hidden !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-overflow:clip !important;
      line-height:1.12 !important;
      font-size:9.6px !important;
      text-align:center !important;
      border-radius:8px !important;
    }
    body.tm-admin-page table.tm-table-fit th.tm-col-money,
    body.tm-admin-page table.tm-table-fit td.tm-col-money{
      width:126px !important;
      min-width:126px !important;
      max-width:126px !important;
      overflow:hidden !important;
    }
    body.tm-admin-page table.tm-table-fit th.tm-col-actions,
    body.tm-admin-page table.tm-table-fit td.tm-col-actions,
    body.tm-admin-page table.tm-table-fit td:has(.btn){
      width:164px !important;
      min-width:164px !important;
      max-width:164px !important;
      overflow:visible !important;
    }

    /* REESTRUCTURACION GLOBAL FINAL DE TABLAS
       Las tablas ya no se aplastan: si hay muchas columnas usan scroll horizontal controlado. */
    body.tm-admin-page .tm-table-scroll,
    body.tm-admin-page .table-responsive,
    body.tm-admin-page .dataTables_wrapper{
      width:100% !important;
      max-width:100% !important;
      overflow-x:auto !important;
      overflow-y:visible !important;
      text-align:center !important;
      border-radius:14px !important;
      padding-bottom:6px !important;
    }
    body.tm-admin-page table.tm-table-fit,
    body.tm-admin-page table.table.tm-table-fit,
    body.tm-admin-page table.dataTable.tm-table-fit{
      width:max-content !important;
      min-width:100% !important;
      max-width:none !important;
      table-layout:auto !important;
      margin:0 auto !important;
      border-collapse:separate !important;
      border-spacing:0 !important;
      border:1px solid rgba(184,205,232,.78) !important;
      border-radius:14px !important;
      overflow:hidden !important;
      background:rgba(255,255,255,.58) !important;
      font-family:'Segoe UI',Roboto,Arial,sans-serif !important;
      box-shadow:0 14px 32px rgba(15,23,42,.05) !important;
    }
    body.tm-admin-page table.tm-table-fit th,
    body.tm-admin-page table.tm-table-fit td{
      box-sizing:border-box !important;
      height:auto !important;
      padding:9px 10px !important;
      white-space:normal !important;
      overflow:visible !important;
      text-overflow:clip !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
      text-align:center !important;
      vertical-align:middle !important;
      border-right:1px solid rgba(205,218,236,.82) !important;
      border-bottom:1px solid rgba(218,228,240,.82) !important;
      font-family:'Segoe UI',Roboto,Arial,sans-serif !important;
    }
    body.tm-admin-page table.tm-table-fit th{
      color:#174b86 !important;
      background:rgba(232,241,255,.86) !important;
      background-image:none !important;
      font-size:11.8px !important;
      line-height:1.18 !important;
      font-weight:900 !important;
      letter-spacing:0 !important;
      text-transform:uppercase !important;
      overflow-wrap:break-word !important;
    }
    body.tm-admin-page table.tm-table-fit td{
      color:#1f2d42 !important;
      font-size:12.8px !important;
      line-height:1.24 !important;
      font-weight:650 !important;
    }
    body.tm-admin-page table.tm-table-fit th.sorting,
    body.tm-admin-page table.tm-table-fit th.sorting_asc,
    body.tm-admin-page table.tm-table-fit th.sorting_desc,
    body.tm-admin-page table.tm-table-fit th.sorting_disabled{
      background-image:none !important;
      padding-right:10px !important;
    }
    body.tm-admin-page table.tm-table-fit th.sorting:before,
    body.tm-admin-page table.tm-table-fit th.sorting:after,
    body.tm-admin-page table.tm-table-fit th.sorting_asc:before,
    body.tm-admin-page table.tm-table-fit th.sorting_asc:after,
    body.tm-admin-page table.tm-table-fit th.sorting_desc:before,
    body.tm-admin-page table.tm-table-fit th.sorting_desc:after{
      display:none !important;
      content:"" !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-index{
      width:44px !important;
      min-width:44px !important;
      max-width:44px !important;
      padding-left:4px !important;
      padding-right:4px !important;
      white-space:nowrap !important;
      overflow:hidden !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-image{min-width:76px !important;}
    body.tm-admin-page table.tm-table-fit .tm-col-code{min-width:118px !important;}
    body.tm-admin-page table.tm-table-fit .tm-col-person,
    body.tm-admin-page table.tm-table-fit .tm-col-contact,
    body.tm-admin-page table.tm-table-fit .tm-col-md{min-width:150px !important;}
    body.tm-admin-page table.tm-table-fit .tm-col-date{min-width:132px !important;}
    body.tm-admin-page table.tm-table-fit .tm-col-money{min-width:122px !important;}
    body.tm-admin-page table.tm-table-fit .tm-col-status{
      min-width:155px !important;
      max-width:190px !important;
      overflow:hidden !important;
      padding-left:7px !important;
      padding-right:7px !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-desc,
    body.tm-admin-page table.tm-table-fit .tm-col-lg,
    body.tm-admin-page table.tm-table-fit .tm-col-xl{min-width:230px !important;}
    body.tm-admin-page table.tm-table-fit .tm-col-actions,
    body.tm-admin-page table.tm-table-fit .acciones,
    body.tm-admin-page table.tm-table-fit .cliente-col-acciones{
      min-width:188px !important;
      max-width:230px !important;
      overflow:visible !important;
      white-space:normal !important;
    }
    body.tm-admin-page table.tm-table-fit td:has(.btn):not(.tm-col-status){
      min-width:188px !important;
      max-width:230px !important;
      overflow:visible !important;
      white-space:normal !important;
    }
    body.tm-admin-page table.tm-table-fit td.tm-col-status .label,
    body.tm-admin-page table.tm-table-fit td.tm-col-status .badge,
    body.tm-admin-page table.tm-table-fit td.tm-col-status .btn,
    body.tm-admin-page table.tm-table-fit td.tm-col-status span[class*="label"],
    body.tm-admin-page table.tm-table-fit td.tm-col-status span[class*="badge"]{
      display:block !important;
      width:100% !important;
      max-width:100% !important;
      min-width:0 !important;
      height:auto !important;
      margin:0 auto !important;
      padding:6px 7px !important;
      white-space:normal !important;
      overflow:hidden !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-overflow:clip !important;
      line-height:1.14 !important;
      font-size:10.4px !important;
      font-weight:850 !important;
      border-radius:9px !important;
      text-align:center !important;
      box-sizing:border-box !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn-group,
    body.tm-admin-page table.tm-table-fit .acciones .btn-group,
    body.tm-admin-page table.tm-table-fit .cliente-col-acciones .btn-group{
      display:flex !important;
      flex-wrap:wrap !important;
      align-items:center !important;
      justify-content:center !important;
      gap:5px !important;
      width:100% !important;
      max-width:100% !important;
      margin:0 auto !important;
      float:none !important;
    }
    body.tm-admin-page table.tm-table-fit .tm-col-actions .btn,
    body.tm-admin-page table.tm-table-fit .acciones .btn,
    body.tm-admin-page table.tm-table-fit .cliente-col-acciones .btn,
    body.tm-admin-page table.tm-table-fit td:not(.tm-col-status) a.btn,
    body.tm-admin-page table.tm-table-fit td:not(.tm-col-status) button.btn{
      display:inline-flex !important;
      align-items:center !important;
      justify-content:center !important;
      width:auto !important;
      min-width:34px !important;
      max-width:100% !important;
      min-height:32px !important;
      margin:1px !important;
      padding:6px 9px !important;
      white-space:normal !important;
      overflow:visible !important;
      overflow-wrap:anywhere !important;
      word-break:break-word !important;
      text-overflow:clip !important;
      line-height:1.08 !important;
      font-size:11.4px !important;
      font-weight:850 !important;
      text-align:center !important;
      border-radius:9px !important;
      box-sizing:border-box !important;
      box-shadow:0 5px 12px rgba(15,23,42,.08) !important;
    }
    body.tm-dark-mode table.tm-table-fit{
      background:rgba(14,27,49,.50) !important;
      border-color:rgba(147,197,253,.25) !important;
    }
    body.tm-dark-mode table.tm-table-fit th{
      color:#f8fbff !important;
      background:rgba(37,70,119,.68) !important;
      border-right-color:rgba(147,197,253,.25) !important;
      border-bottom-color:rgba(147,197,253,.28) !important;
    }
    body.tm-dark-mode table.tm-table-fit td{
      color:#edf5ff !important;
      border-right-color:rgba(147,197,253,.18) !important;
      border-bottom-color:rgba(147,197,253,.18) !important;
    }
  </style>
  <script>
    (function(){
      try{
        localStorage.removeItem("tmTheme");
      }catch(e){}
    })();
  </script>

  <!--=====================================
  PLUGINS DE JAVASCRIPT
  ======================================-->

  <!-- jQuery 3 -->
  <script src="vistas/bower_components/jquery/dist/jquery.min.js"></script>
  
  <!-- Bootstrap 3.3.7 -->
  <script src="vistas/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

  <!-- FastClick -->
  <script src="vistas/bower_components/fastclick/lib/fastclick.js"></script>
  
  <!-- AdminLTE App -->
  <script src="vistas/dist/js/adminlte.min.js"></script>
  

  <!-- DataTables -->
  <script src="vistas/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
  <script src="vistas/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
  <script src="vistas/bower_components/datatables.net-bs/js/dataTables.responsive.min.js"></script>
  <script src="vistas/bower_components/datatables.net-bs/js/responsive.bootstrap.min.js"></script>

  <!-- SweetAlert 2 -->
  <script src="vistas/plugins/sweetalert2/sweetalert2.all.js"></script>
   <!-- By default SweetAlert2 doesn't support IE. To enable IE 11 support, include Promise polyfill:-->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/core-js/2.4.1/core.js"></script>

  <!-- iCheck 1.0.1 -->
  <script src="vistas/plugins/iCheck/icheck.min.js"></script>

  <!-- InputMask -->
  <script src="vistas/plugins/input-mask/jquery.inputmask.js"></script>
  <script src="vistas/plugins/input-mask/jquery.inputmask.date.extensions.js"></script>
  <script src="vistas/plugins/input-mask/jquery.inputmask.extensions.js"></script>

  <!-- jQuery Number -->
  <script src="vistas/plugins/jqueryNumber/jquerynumber.min.js"></script>

  <!-- daterangepicker http://www.daterangepicker.com/-->
  <script src="vistas/bower_components/moment/min/moment.min.js"></script>
  <script src="vistas/bower_components/bootstrap-daterangepicker/daterangepicker.js"></script>

  <!-- Morris.js charts http://morrisjs.github.io/morris.js/-->
  <script src="vistas/bower_components/raphael/raphael.min.js"></script>
  <script src="vistas/bower_components/morris.js/morris.min.js"></script>

  <!-- ChartJS http://www.chartjs.org/-->
  <script src="vistas/bower_components/chart.js/Chart.js"></script>



</head>

<!--=====================================
CUERPO DOCUMENTO
======================================-->

<body class="hold-transition skin-blue sidebar-mini <?php echo (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok") ? "tm-admin-page" : "login-page"; ?>">
 
  <?php

  if(isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok"){

   echo '<script>document.body.classList.remove("login-page");document.body.classList.add("tm-admin-page");</script>';

   if((($_GET["ruta"] ?? "") != "salir") && !ControladorUsuarios::ctrValidarSesionActual()){
    return;
   }

   if(($_SESSION["debe_cambiar_password"] ?? 0) == 1 && (($_GET["ruta"] ?? "") != "cambiar-password") && (($_GET["ruta"] ?? "") != "salir")){
    echo '<script>window.location = "cambiar-password";</script>';
    return;
   }

   $rutaActualCaja = $_GET["ruta"] ?? "inicio";
   if(ControladorCaja::ctrRequiereApertura() &&
      !in_array($rutaActualCaja, array("caja", "salir", "cambiar-password")) &&
      !ControladorCaja::ctrAperturaActiva()){
    echo '<script>window.location = "caja";</script>';
    return;
   }

   if($_SESSION["perfil"] == "Administrador"){

    $rolesVistaMenu = array("administrador", "vendedor", "cajero", "almacen", "mensajero", "tecnico", "desarrollador");

    if(isset($_GET["vistaRolMenu"]) && in_array($_GET["vistaRolMenu"], $rolesVistaMenu)){
      $_SESSION["vistaRolMenu"] = $_GET["vistaRolMenu"];
    }

    if(isset($_GET["resetVistaRolMenu"])){
      unset($_SESSION["vistaRolMenu"]);
    }

   }

   echo '<div class="wrapper">';

    /*=============================================
    CABEZOTE
    =============================================*/

    include "modulos/cabezote.php";

    /*=============================================
    MENU
    =============================================*/

    include "modulos/menu.php";

    /*=============================================
    CONTENIDO
    =============================================*/

    if(isset($_GET["ruta"])){

      if($_GET["ruta"] == "inicio" ||
         $_GET["ruta"] == "usuarios" ||
         $_GET["ruta"] == "categorias" ||
         $_GET["ruta"] == "productos" ||
         $_GET["ruta"] == "productos-almacen" ||
         $_GET["ruta"] == "ingreso-directo-admin" ||
         $_GET["ruta"] == "datos-boletas" ||
         $_GET["ruta"] == "ordenes-ingreso-material" ||
         $_GET["ruta"] == "recepcion-equipos-taller" ||
         $_GET["ruta"] == "repuestos-taller-almacen" ||
         $_GET["ruta"] == "productos-cajero" ||
         $_GET["ruta"] == "productos-precios" ||
         $_GET["ruta"] == "compras-almacen" ||
         $_GET["ruta"] == "compras-cajero" ||
         $_GET["ruta"] == "proveedor" ||
         $_GET["ruta"] == "clientes" ||
         $_GET["ruta"] == "crear-compra" ||
         $_GET["ruta"] == "crear-compra-almacen" ||
         $_GET["ruta"] == "compras" ||
         $_GET["ruta"] == "solicitudes-de-compra" ||
         $_GET["ruta"] == "solicitudes-aprobadas" ||
         $_GET["ruta"] == "pagos-ventas" ||
         $_GET["ruta"] == "pagos-servicios" ||
         $_GET["ruta"] == "caja" ||
         $_GET["ruta"] == "despacho" ||
         $_GET["ruta"] == "logs-sistema" ||
         $_GET["ruta"] == "ventas" ||
         $_GET["ruta"] == "crear-venta" ||
         $_GET["ruta"] == "cambiar-password" ||
         $_GET["ruta"] == "servicios" ||
         $_GET["ruta"] == "proyectos" ||
         $_GET["ruta"] == "administrar-servicios" ||
         $_GET["ruta"] == "precios-servicios" ||
         $_GET["ruta"] == "ordenes-servicio" ||
         $_GET["ruta"] == "cotizacion" ||
         $_GET["ruta"] == "solicitudes-web" ||
         $_GET["ruta"] == "crear-cotizacion" ||
         $_GET["ruta"] == "procesar-solicitud-web" ||
         $_GET["ruta"] == "editar-venta" ||
         $_GET["ruta"] == "reportes" ||
         $_GET["ruta"] == "centro-web" ||
         $_GET["ruta"] == "consultas-web" ||
         $_GET["ruta"] == "salir"){

        include "modulos/".$_GET["ruta"].".php";

      }else{

        include "modulos/404.php";

      }

    }else{

      include "modulos/inicio.php";

    }

    /*=============================================
    FOOTER
    =============================================*/

   include "modulos/footer.php";

  echo '</div>';

   echo '<script>
    if(window.jQuery){
      jQuery(document).on("show.bs.modal", ".modal", function(){
        if(this.parentNode !== document.body){
          document.body.appendChild(this);
        }
        var modalIndex = jQuery(".modal:visible").length;
        var zIndex = 30050 + (modalIndex * 30);
        this.style.setProperty("z-index", zIndex, "important");
        setTimeout(function(){
          jQuery(".modal-backdrop").not(".tm-modal-stacked").last().each(function(){
            this.style.setProperty("z-index", zIndex - 10, "important");
            jQuery(this).addClass("tm-modal-stacked");
          });
        }, 0);
      });
      jQuery(document).on("hidden.bs.modal", ".modal", function(){
        if(jQuery(".modal:visible").length){
          jQuery(document.body).addClass("modal-open");
        }else{
          jQuery(".modal-backdrop").remove();
        }
      });
    }
    (function(){
      var canvas = document.getElementById("tmAdminParticles");
      if(!canvas){
        canvas = document.createElement("canvas");
        canvas.id = "tmAdminParticles";
        canvas.className = "tm-admin-particles";
        canvas.setAttribute("aria-hidden", "true");
        document.body.insertBefore(canvas, document.body.firstChild);
      }
      canvas.style.position = "fixed";
      canvas.style.left = "0";
      canvas.style.top = "0";
      canvas.style.right = "0";
      canvas.style.bottom = "0";
      canvas.style.width = "100vw";
      canvas.style.height = "100vh";
      canvas.style.margin = "0";
      canvas.style.padding = "0";
      canvas.style.pointerEvents = "none";
      canvas.style.zIndex = "0";
      canvas.style.display = "block";
      var ctx = canvas.getContext("2d");
      var particles = [];
      var mouse = {x:null,y:null};
      var maxParticles = 170;

      function applyCanvasMode(){
        var dark = document.body.classList.contains("tm-dark-mode");
        canvas.style.mixBlendMode = dark ? "screen" : "multiply";
        canvas.style.filter = dark ? "drop-shadow(0 0 7px rgba(255,255,255,.55))" : "drop-shadow(0 0 5px rgba(37,99,235,.30))";
      }

      function color(){
        return document.body.classList.contains("tm-dark-mode") ? "rgba(219,234,254,1)" : "rgba(37,99,235,.72)";
      }
      function lineColor(){
        return document.body.classList.contains("tm-dark-mode") ? "rgba(147,197,253,.58)" : "rgba(37,99,235,.26)";
      }
      function resize(){
        applyCanvasMode();
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        var dark = document.body.classList.contains("tm-dark-mode");
        var density = dark ? 5600 : 7200;
        var minParticles = dark ? 120 : 95;
        var target = Math.min(maxParticles, Math.max(minParticles, Math.floor((canvas.width * canvas.height) / density)));
        particles = [];
        for(var i=0;i<target;i++){
          particles.push({
            x:Math.random()*canvas.width,
            y:Math.random()*canvas.height,
            vx:(Math.random()-.5)*(dark ? 1.02 : .72),
            vy:(Math.random()-.5)*(dark ? 1.02 : .72),
            r:Math.random()*(dark ? 4.4 : 3.5)+(dark ? 2.6 : 2.0)
          });
        }
      }
      function draw(){
        ctx.clearRect(0,0,canvas.width,canvas.height);
        var dot = color();
        var line = lineColor();
        for(var i=0;i<particles.length;i++){
          var p = particles[i];
          p.x += p.vx;
          p.y += p.vy;
          if(p.x < 0 || p.x > canvas.width){ p.vx *= -1; }
          if(p.y < 0 || p.y > canvas.height){ p.vy *= -1; }

          if(mouse.x !== null){
            var dx = p.x - mouse.x;
            var dy = p.y - mouse.y;
            var dist = Math.sqrt(dx*dx + dy*dy);
            if(dist < 105 && dist > 0){
              p.x += (dx / dist) * 1.2;
              p.y += (dy / dist) * 1.2;
            }
          }

          ctx.beginPath();
          ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
          ctx.fillStyle = dot;
          ctx.fill();

          for(var j=i+1;j<particles.length;j++){
            var q = particles[j];
            var lx = p.x - q.x;
            var ly = p.y - q.y;
            var ld = Math.sqrt(lx*lx + ly*ly);
            var limit = document.body.classList.contains("tm-dark-mode") ? 235 : 175;
            if(ld < limit){
              ctx.beginPath();
              ctx.moveTo(p.x,p.y);
              ctx.lineTo(q.x,q.y);
              ctx.strokeStyle = line;
              ctx.lineWidth = (document.body.classList.contains("tm-dark-mode") ? 1.65 : 1.15) - (ld / limit);
              ctx.stroke();
            }
          }
        }
        requestAnimationFrame(draw);
      }
      window.addEventListener("resize", resize);
      window.addEventListener("mousemove", function(e){ mouse.x = e.clientX; mouse.y = e.clientY; });
      window.addEventListener("mouseleave", function(){ mouse.x = null; mouse.y = null; });
      if(window.MutationObserver){
        var lastDarkState = document.body.classList.contains("tm-dark-mode");
        new MutationObserver(function(){
          var currentDarkState = document.body.classList.contains("tm-dark-mode");
          if(currentDarkState !== lastDarkState){
            lastDarkState = currentDarkState;
            applyCanvasMode();
            resize();
          }
        }).observe(document.body, { attributes:true, attributeFilter:["class"] });
      }
      applyCanvasMode();
      resize();
      draw();
    })();
   </script>';

   echo '<script src="vistas/js/tiempo-real.js?v='.filemtime("vistas/js/tiempo-real.js").'"></script>';

  }else{

    include "modulos/login.php";

  }

  ?>


<script src="vistas/js/plantilla.js?v=<?php echo filemtime("vistas/js/plantilla.js"); ?>"></script>
<script src="vistas/js/usuarios.js?v=<?php echo filemtime("vistas/js/usuarios.js"); ?>"></script>
<script src="vistas/js/categorias.js"></script>
<script src="vistas/js/proveedor.js"></script>
<script src="vistas/js/productos.js?v=<?php echo filemtime("vistas/js/productos.js"); ?>"></script>
<script src="vistas/js/clientes.js?v=<?php echo filemtime("vistas/js/clientes.js"); ?>"></script>

<script src="vistas/js/compras.js"></script>
<script src="vistas/js/ventas.js?v=<?php echo filemtime("vistas/js/ventas.js"); ?>"></script>
<script src="vistas/js/cotizacion.js?v=<?php echo filemtime("vistas/js/cotizacion.js"); ?>"></script>
<script src="vistas/js/reportes.js"></script>

</body>
</html>
