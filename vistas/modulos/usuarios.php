<?php

if($_SESSION["perfil"] == "Especial" || $_SESSION["perfil"] == "Vendedor"){

  echo '<script>window.location = "inicio";</script>';
  return;

}

$usuarios = ControladorUsuarios::ctrMostrarUsuarios(null, null);

function tmUsuariosEsc($valor){
  return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function tmUsuarioFoto($foto){
  return $foto != "" ? $foto : "vistas/img/usuarios/default/anonymous.png";
}

function tmUsuarioIniciales($nombre){
  $partes = preg_split('/\s+/', trim((string)$nombre));
  $iniciales = "";

  foreach($partes as $parte){
    if($parte !== ""){
      $iniciales .= strtoupper(substr($parte, 0, 1));
    }

    if(strlen($iniciales) >= 2){
      break;
    }
  }

  return $iniciales ?: "US";
}

function tmUsuarioRolTexto($rol){
  $mapa = array(
    "vendedor" => "Vendedor",
    "cajero" => "Cajero",
    "almacen" => "Almacen",
    "mensajero" => "Mensajero",
    "tecnico" => "Tecnico",
    "desarrollador" => "Desarrollador"
  );

  return $mapa[$rol] ?? ucfirst((string)$rol);
}

function tmUsuarioPerfilTexto($perfil){
  return $perfil ?: "Sin perfil";
}

$rolesDisponibles = array(
  "vendedor" => "Vendedor",
  "cajero" => "Cajero",
  "almacen" => "Almacen",
  "mensajero" => "Mensajero",
  "tecnico" => "Tecnico",
  "desarrollador" => "Desarrollador"
);

$totalUsuarios = is_array($usuarios) ? count($usuarios) : 0;
$usuariosActivos = 0;
$usuariosInactivos = 0;
$rolesResumen = array();

if(is_array($usuarios)){
  foreach($usuarios as $usuarioResumen){
    $estadoResumen = (int)($usuarioResumen["estado"] ?? 0);
    $rolResumen = $usuarioResumen["rol"] ?? "sin_rol";

    if($estadoResumen === 1){
      $usuariosActivos++;
    }else{
      $usuariosInactivos++;
    }

    if(!isset($rolesResumen[$rolResumen])){
      $rolesResumen[$rolResumen] = 0;
    }

    $rolesResumen[$rolResumen]++;
  }
}

?>

<style>
  .tm-users-shell{
    color:#14243a;
    font-family:"Segoe UI",Roboto,Arial,sans-serif;
  }

  .tm-users-hero{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:18px;
    padding:18px 20px;
    margin-bottom:16px;
    border:1px solid rgba(25,116,190,.18);
    border-radius:18px;
    background:linear-gradient(135deg,rgba(12,54,86,.95),rgba(18,151,211,.9));
    color:#fff;
    box-shadow:0 18px 42px rgba(21,87,142,.18);
    overflow:hidden;
    position:relative;
  }

  .tm-users-hero:after{
    content:"";
    position:absolute;
    right:-52px;
    bottom:-70px;
    width:230px;
    height:230px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
  }

  .tm-users-hero-title{
    position:relative;
    z-index:1;
  }

  .tm-users-hero-kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
    padding:5px 10px;
    border-radius:999px;
    background:rgba(255,255,255,.16);
    border:1px solid rgba(255,255,255,.22);
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.4px;
  }

  .tm-users-hero h1{
    margin:0 0 5px;
    font-size:25px;
    line-height:1.15;
    font-weight:900;
  }

  .tm-users-hero p{
    margin:0;
    max-width:720px;
    color:rgba(255,255,255,.9);
    font-size:13px;
    font-weight:700;
  }

  .tm-users-hero-action{
    position:relative;
    z-index:1;
    display:flex;
    gap:8px;
    align-items:center;
  }

  .tm-users-primary-btn{
    border:0;
    border-radius:12px;
    background:#fff;
    color:#126ca5;
    min-height:40px;
    padding:10px 15px;
    font-size:13px;
    font-weight:900;
    box-shadow:0 10px 22px rgba(6,34,60,.18);
  }

  .tm-users-primary-btn:hover,
  .tm-users-primary-btn:focus{
    color:#0f5e91;
    background:#f4fbff;
  }

  .tm-users-stats{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    margin-bottom:14px;
  }

  .tm-users-stat{
    min-height:94px;
    padding:13px 14px;
    border:1px solid rgba(39,114,187,.14);
    border-radius:16px;
    background:rgba(255,255,255,.76);
    box-shadow:0 12px 30px rgba(20,80,135,.08);
    backdrop-filter:blur(8px);
    display:grid;
    grid-template-columns:42px minmax(0,1fr);
    gap:11px;
    align-items:center;
  }

  .tm-users-stat i{
    width:42px;
    height:42px;
    border-radius:13px;
    background:linear-gradient(135deg,#1d75d1,#0bb4dc);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
  }

  .tm-users-stat span{
    display:block;
    color:#6a7d93;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-users-stat b{
    display:block;
    color:#13243d;
    font-size:25px;
    line-height:1.1;
    font-weight:900;
  }

  .tm-users-layout{
    display:grid;
    grid-template-columns:minmax(0,1fr) 280px;
    gap:14px;
    align-items:start;
  }

  .tm-users-panel,
  .tm-users-side{
    border:1px solid rgba(39,114,187,.14);
    border-radius:18px;
    background:rgba(255,255,255,.78);
    box-shadow:0 14px 32px rgba(22,74,122,.08);
    backdrop-filter:blur(8px);
  }

  .tm-users-panel{
    padding:14px;
  }

  .tm-users-side{
    padding:13px;
  }

  .tm-users-toolbar{
    display:grid;
    grid-template-columns:minmax(220px,460px) auto;
    gap:12px;
    align-items:center;
    margin-bottom:14px;
  }

  .tm-users-search{
    position:relative;
  }

  .tm-users-search i{
    position:absolute;
    left:13px;
    top:50%;
    transform:translateY(-50%);
    color:#168fd0;
  }

  .tm-users-search input{
    width:100%;
    height:42px;
    border:1px solid #d9e7f5;
    border-radius:13px;
    background:rgba(255,255,255,.9);
    padding:0 14px 0 38px;
    box-shadow:none;
    font-weight:700;
  }

  .tm-users-count{
    justify-self:end;
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:8px 10px;
    border-radius:999px;
    background:#eef7ff;
    color:#18689f;
    border:1px solid #d8eafd;
    font-size:12px;
    font-weight:900;
  }

  .tm-users-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
    gap:13px;
  }

  .usuario-card{
    min-height:286px;
    border:1px solid #dce9f7;
    border-radius:16px;
    background:rgba(255,255,255,.92);
    box-shadow:0 12px 26px rgba(22,78,132,.09);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    cursor:pointer;
    transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;
  }

  .usuario-card:hover{
    transform:translateY(-2px);
    border-color:#99c9f0;
    box-shadow:0 18px 34px rgba(22,78,132,.15);
  }

  .tm-user-card-head{
    display:grid;
    grid-template-columns:54px minmax(0,1fr);
    gap:10px;
    align-items:center;
    padding:12px;
    background:linear-gradient(135deg,#eef8ff,#fff);
    border-bottom:1px solid #e5eef8;
  }

  .tm-user-avatar{
    width:54px;
    height:54px;
    border-radius:16px;
    overflow:hidden;
    background:#e8f3ff;
    border:3px solid #fff;
    box-shadow:0 8px 18px rgba(16,87,145,.16);
    position:relative;
  }

  .tm-user-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
  }

  .tm-user-avatar span{
    position:absolute;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    color:#0d5d9a;
    font-weight:900;
    font-size:16px;
  }

  .tm-user-title{
    min-width:0;
  }

  .tm-user-title h3{
    margin:0 0 4px;
    color:#13243d;
    font-size:15px;
    line-height:1.2;
    font-weight:900;
    overflow-wrap:anywhere;
  }

  .tm-user-title p{
    margin:0;
    color:#60758c;
    font-size:12px;
    font-weight:800;
    overflow-wrap:anywhere;
  }

  .tm-user-chips{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
    padding:10px 12px 0;
  }

  .tm-user-chip{
    display:inline-flex;
    align-items:center;
    gap:5px;
    max-width:100%;
    border:1px solid #d8eafd;
    border-radius:999px;
    background:#eef7ff;
    color:#185a91;
    padding:5px 8px;
    font-size:10px;
    line-height:1.1;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-user-chip.is-active{
    background:#dcfce7;
    border-color:#bbf7d0;
    color:#166534;
  }

  .tm-user-chip.is-inactive{
    background:#fee2e2;
    border-color:#fecaca;
    color:#991b1b;
  }

  .tm-user-info{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    padding:10px 12px;
  }

  .tm-user-info-box{
    min-width:0;
    min-height:48px;
    border:1px solid #e1ecf8;
    border-radius:12px;
    background:#f8fbff;
    padding:8px;
    color:#273e58;
    font-size:11px;
    font-weight:800;
    overflow-wrap:anywhere;
  }

  .tm-user-info-box b{
    display:block;
    margin-bottom:3px;
    color:#6d839a;
    font-size:9px;
    line-height:1;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-user-actions,
  #detalleUsuarioAcciones{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:7px;
  }

  .tm-user-actions{
    margin-top:auto;
    padding:10px 12px 12px;
    border-top:1px solid #edf3fb;
  }

  .usuario-card-actions-template{
    display:none;
  }

  .usuario-action{
    width:100%;
    min-height:34px;
    border:0;
    border-radius:10px;
    padding:8px 8px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    color:#fff;
    font-size:11px;
    font-weight:900;
    line-height:1.1;
    text-align:center;
    white-space:normal;
  }

  .usuario-action:hover,
  .usuario-action:focus{
    color:#fff;
    text-decoration:none;
    filter:brightness(.96);
  }

  .usuario-action-view{
    background:#15a6d7;
  }

  .usuario-action-edit{
    background:#f59e0b;
  }

  .usuario-action-delete{
    background:#ef4444;
  }

  .usuario-action-state.active{
    background:#10b981;
  }

  .usuario-action-state.inactive{
    background:#ef4444;
  }

  .tm-users-side h3{
    margin:0 0 10px;
    font-size:15px;
    font-weight:900;
    color:#14243a;
  }

  .tm-role-list{
    display:grid;
    gap:8px;
  }

  .tm-role-row{
    display:grid;
    grid-template-columns:34px minmax(0,1fr) auto;
    gap:9px;
    align-items:center;
    padding:9px;
    border:1px solid #e1ecf8;
    border-radius:13px;
    background:rgba(255,255,255,.72);
  }

  .tm-role-row i{
    width:34px;
    height:34px;
    border-radius:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#eef7ff;
    color:#1972b4;
  }

  .tm-role-row b{
    display:block;
    font-size:12px;
    color:#162a44;
  }

  .tm-role-row span{
    color:#6d8196;
    font-size:11px;
    font-weight:800;
  }

  .tm-role-count{
    min-width:28px;
    height:28px;
    border-radius:999px;
    background:#125f9d;
    color:#fff;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:12px;
    font-weight:900;
  }

  .tm-users-empty{
    padding:22px;
    border:1px dashed #bcd9f3;
    border-radius:14px;
    text-align:center;
    color:#668097;
    font-weight:800;
  }

  .usuario-detail-modal .modal-dialog{
    width:820px;
    max-width:calc(100% - 24px);
  }

  .usuario-detail-modal .modal-content,
  .usuarios-modal .modal-content{
    border:0;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 28px 70px rgba(12,36,68,.28);
  }

  .tm-detail-head{
    display:grid;
    grid-template-columns:78px minmax(0,1fr) 36px;
    gap:14px;
    align-items:center;
    padding:16px;
    color:#fff;
    background:linear-gradient(135deg,#0f3d67,#16a2da);
  }

  .tm-detail-head img{
    width:78px;
    height:78px;
    border-radius:20px;
    object-fit:cover;
    border:3px solid rgba(255,255,255,.8);
    background:#fff;
  }

  .tm-detail-head h3{
    margin:0 0 4px;
    font-size:21px;
    font-weight:900;
    overflow-wrap:anywhere;
  }

  .tm-detail-head p{
    margin:0;
    color:rgba(255,255,255,.9);
    font-size:12px;
    font-weight:800;
  }

  .tm-modal-close{
    border:0;
    width:36px;
    height:36px;
    border-radius:50%;
    background:rgba(255,255,255,.2);
    color:#fff;
    font-size:18px;
    line-height:1;
  }

  .tm-detail-body{
    padding:14px;
    background:#f6fbff;
  }

  .tm-detail-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:10px;
    margin-bottom:12px;
  }

  .tm-detail-box{
    min-height:64px;
    border:1px solid #e1ecf8;
    border-radius:13px;
    background:#fff;
    padding:10px;
    color:#223951;
    font-size:13px;
    font-weight:800;
    overflow-wrap:anywhere;
  }

  .tm-detail-box b{
    display:block;
    margin-bottom:5px;
    color:#6c8198;
    font-size:10px;
    font-weight:900;
    text-transform:uppercase;
  }

  #detalleUsuarioAcciones{
    padding:12px;
    border:1px solid #e1ecf8;
    border-radius:13px;
    background:#fff;
  }

  .usuarios-modal .modal-dialog{
    width:850px;
    max-width:calc(100% - 24px);
  }

  .tm-form-head{
    display:grid;
    grid-template-columns:46px minmax(0,1fr) 36px;
    gap:12px;
    align-items:center;
    padding:16px 18px;
    color:#fff;
    background:linear-gradient(135deg,#112f4b,#159bd2);
  }

  .tm-form-head-icon{
    width:46px;
    height:46px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(255,255,255,.16);
    border:1px solid rgba(255,255,255,.22);
    font-size:18px;
  }

  .tm-form-head h4{
    margin:0 0 4px;
    font-size:18px;
    font-weight:900;
  }

  .tm-form-head p{
    margin:0;
    color:rgba(255,255,255,.88);
    font-size:12px;
    font-weight:700;
  }

  .usuarios-modal .modal-body{
    padding:16px;
    background:#f4f8fc;
  }

  .tm-user-form-grid{
    display:grid;
    grid-template-columns:1.2fr .8fr;
    gap:14px;
  }

  .tm-user-form-card{
    border:1px solid #dce8f4;
    border-radius:15px;
    background:#fff;
    padding:14px;
  }

  .tm-user-form-card h5{
    margin:0 0 12px;
    color:#14243a;
    font-size:14px;
    font-weight:900;
  }

  .tm-user-field{
    margin-bottom:11px;
  }

  .tm-user-field label{
    display:block;
    margin-bottom:6px;
    color:#32465f;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
  }

  .tm-user-field .input-group-addon{
    min-width:42px;
    background:#f5f9fd;
    color:#1972b4;
    border-color:#d8e5f1;
  }

  .tm-user-field .form-control{
    min-height:40px;
    border-color:#d8e5f1;
    box-shadow:none;
    font-weight:700;
  }

  .tm-user-generated{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:10px 12px;
    border:1px solid #bde1f7;
    border-radius:13px;
    background:#eef9ff;
    color:#145c8d;
    font-weight:900;
    overflow-wrap:anywhere;
  }

  .tm-user-note{
    margin-top:8px;
    padding:9px 10px;
    border-left:4px solid #18a3dc;
    border-radius:10px;
    background:#eff9ff;
    color:#3f5c75;
    font-size:12px;
    font-weight:700;
  }

  .tm-photo-box{
    text-align:center;
  }

  .tm-photo-box img{
    width:118px;
    height:118px;
    object-fit:cover;
    border-radius:22px;
    border:4px solid #fff;
    box-shadow:0 12px 28px rgba(15,80,135,.15);
    margin-bottom:12px;
    background:#eef6ff;
  }

  .tm-photo-box input[type=file]{
    display:block;
    width:100%;
  }

  .tm-photo-box .help-block{
    margin:8px 0 0;
    font-size:11px;
    color:#70859a;
    font-weight:800;
  }

  .usuarios-modal .modal-footer{
    padding:12px 16px;
    background:#fff;
    border-top:1px solid #e3edf7;
  }

  .usuarios-modal .btn{
    border-radius:10px;
    font-weight:900;
  }

  body.tm-dark-mode .tm-users-shell,
  body.tm-dark-mode .tm-users-side h3,
  body.tm-dark-mode .tm-user-title h3,
  body.tm-dark-mode .tm-users-stat b,
  body.tm-dark-mode .tm-role-row b{
    color:#f4f8ff;
  }

  body.tm-dark-mode .tm-users-panel,
  body.tm-dark-mode .tm-users-side,
  body.tm-dark-mode .tm-users-stat,
  body.tm-dark-mode .usuario-card,
  body.tm-dark-mode .tm-role-row{
    background:rgba(11,24,43,.68);
    border-color:rgba(151,190,255,.22);
    box-shadow:0 14px 32px rgba(0,0,0,.18);
  }

  body.tm-dark-mode .tm-user-card-head{
    background:linear-gradient(135deg,rgba(18,44,75,.9),rgba(12,25,45,.9));
    border-color:rgba(151,190,255,.18);
  }

  body.tm-dark-mode .tm-user-title p,
  body.tm-dark-mode .tm-users-stat span,
  body.tm-dark-mode .tm-role-row span{
    color:#b9c8dd;
  }

  body.tm-dark-mode .tm-user-info-box,
  body.tm-dark-mode .tm-detail-box,
  body.tm-dark-mode #detalleUsuarioAcciones,
  body.tm-dark-mode .tm-user-form-card{
    background:rgba(15,31,53,.88);
    border-color:rgba(151,190,255,.2);
    color:#eef5ff;
  }

  body.tm-dark-mode .tm-detail-body,
  body.tm-dark-mode .usuarios-modal .modal-body{
    background:#0d1b2f;
  }

  body.tm-dark-mode .tm-users-search input,
  body.tm-dark-mode .tm-user-field .form-control{
    background:#0c1a2d;
    border-color:#28425f;
    color:#eef5ff;
  }

  @media (max-width:991px){
    .tm-users-layout,
    .tm-users-hero,
    .tm-user-form-grid{
      grid-template-columns:1fr;
    }

    .tm-users-stats{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .tm-users-hero-action{
      justify-content:flex-start;
    }
  }

  @media (max-width:640px){
    .tm-users-stats,
    .tm-users-toolbar,
    .tm-detail-grid,
    .tm-user-info{
      grid-template-columns:1fr;
    }

    .tm-users-count{
      justify-self:start;
    }
  }
</style>

<div class="content-wrapper">

  <section class="content-header">
    <h1>Administrar usuarios</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
      <li class="active">Usuarios</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-users-shell">

      <div class="tm-users-hero">
        <div class="tm-users-hero-title">
          <span class="tm-users-hero-kicker"><i class="fa fa-shield"></i> Control de acceso</span>
          <h1>Usuarios, roles y permisos del sistema</h1>
          <p>Administra cuentas internas, perfiles de acceso, roles operativos, estado de usuario y foto de identificacion desde una vista clara.</p>
        </div>
        <div class="tm-users-hero-action">
          <button type="button" class="tm-users-primary-btn" data-toggle="modal" data-target="#modalAgregarUsuario">
            <i class="fa fa-user-plus"></i> Nuevo usuario
          </button>
        </div>
      </div>

      <div class="tm-users-stats">
        <div class="tm-users-stat">
          <i class="fa fa-users"></i>
          <div>
            <span>Total usuarios</span>
            <b><?php echo $totalUsuarios; ?></b>
          </div>
        </div>
        <div class="tm-users-stat">
          <i class="fa fa-check-circle"></i>
          <div>
            <span>Activos</span>
            <b><?php echo $usuariosActivos; ?></b>
          </div>
        </div>
        <div class="tm-users-stat">
          <i class="fa fa-ban"></i>
          <div>
            <span>Inactivos</span>
            <b><?php echo $usuariosInactivos; ?></b>
          </div>
        </div>
        <div class="tm-users-stat">
          <i class="fa fa-id-badge"></i>
          <div>
            <span>Roles usados</span>
            <b><?php echo count($rolesResumen); ?></b>
          </div>
        </div>
      </div>

      <div class="tm-users-layout">
        <div class="tm-users-panel">
          <div class="tm-users-toolbar">
            <div class="tm-users-search">
              <i class="fa fa-search"></i>
              <input type="text" id="buscarUsuarioCard" placeholder="Buscar por nombre, usuario, correo, perfil o rol">
            </div>
            <span class="tm-users-count"><i class="fa fa-address-card"></i> <?php echo $totalUsuarios; ?> usuario(s)</span>
          </div>

          <?php if($totalUsuarios > 0): ?>
            <div class="tm-users-grid">
              <?php foreach($usuarios as $value): ?>
                <?php
                  $idUsuario = $value["id"] ?? "";
                  $nombreUsuario = $value["nombre"] ?? "Sin nombre";
                  $loginUsuario = $value["ultimo_login"] ?? "-";
                  $usuarioSistema = $value["usuario"] ?? "-";
                  $correoUsuario = $value["email"] ?? "-";
                  $perfilUsuario = tmUsuarioPerfilTexto($value["perfil"] ?? "");
                  $rolUsuario = tmUsuarioRolTexto($value["rol"] ?? "");
                  $fotoUsuario = tmUsuarioFoto($value["foto"] ?? "");
                  $estadoActivo = (int)($value["estado"] ?? 0) === 1;
                  $estadoTexto = $estadoActivo ? "Activo" : "Inactivo";
                  $iniciales = tmUsuarioIniciales($nombreUsuario);
                  $search = strtolower($nombreUsuario." ".$usuarioSistema." ".$correoUsuario." ".$perfilUsuario." ".$rolUsuario." ".$estadoTexto);
                  $acciones = '
                    <button type="button" class="usuario-action usuario-action-view btnVerUsuarioDetalle" title="Ver detalle del usuario"><i class="fa fa-eye"></i><span>Ver</span></button>
                    <button type="button" class="usuario-action usuario-action-state '.($estadoActivo ? 'active' : 'inactive').' btnActivar" title="'.($estadoActivo ? 'Desactivar usuario' : 'Activar usuario').'" idUsuario="'.tmUsuariosEsc($idUsuario).'" estadoUsuario="'.($estadoActivo ? '0' : '1').'"><i class="fa '.($estadoActivo ? 'fa-check-circle' : 'fa-ban').'"></i><span>'.($estadoActivo ? 'Activo' : 'Inactivo').'</span></button>
                    <button type="button" class="usuario-action usuario-action-edit btnEditarUsuario" title="Editar usuario" idUsuario="'.tmUsuariosEsc($idUsuario).'" data-toggle="modal" data-target="#modalEditarUsuario"><i class="fa fa-pencil"></i><span>Editar</span></button>
                    <button type="button" class="usuario-action usuario-action-delete btnEliminarUsuario" title="Eliminar usuario" idUsuario="'.tmUsuariosEsc($idUsuario).'" fotoUsuario="'.tmUsuariosEsc($value["foto"] ?? "").'" usuario="'.tmUsuariosEsc($usuarioSistema).'"><i class="fa fa-trash"></i><span>Eliminar</span></button>';
                ?>
                <article class="usuario-card"
                  data-search="<?php echo tmUsuariosEsc($search); ?>"
                  data-nombre="<?php echo tmUsuariosEsc($nombreUsuario); ?>"
                  data-usuario="<?php echo tmUsuariosEsc($usuarioSistema); ?>"
                  data-email="<?php echo tmUsuariosEsc($correoUsuario); ?>"
                  data-perfil="<?php echo tmUsuariosEsc($perfilUsuario); ?>"
                  data-rol="<?php echo tmUsuariosEsc($rolUsuario); ?>"
                  data-estado="<?php echo tmUsuariosEsc($estadoTexto); ?>"
                  data-login="<?php echo tmUsuariosEsc($loginUsuario); ?>"
                  data-foto="<?php echo tmUsuariosEsc($fotoUsuario); ?>">
                  <div class="tm-user-card-head">
                    <div class="tm-user-avatar">
                      <img src="<?php echo tmUsuariosEsc($fotoUsuario); ?>" alt="<?php echo tmUsuariosEsc($nombreUsuario); ?>">
                      <span><?php echo tmUsuariosEsc($iniciales); ?></span>
                    </div>
                    <div class="tm-user-title">
                      <h3><?php echo tmUsuariosEsc($nombreUsuario); ?></h3>
                      <p><i class="fa fa-user-circle"></i> <?php echo tmUsuariosEsc($usuarioSistema); ?></p>
                    </div>
                  </div>

                  <div class="tm-user-chips">
                    <span class="tm-user-chip <?php echo $estadoActivo ? 'is-active' : 'is-inactive'; ?>">
                      <i class="fa <?php echo $estadoActivo ? 'fa-check' : 'fa-ban'; ?>"></i> <?php echo tmUsuariosEsc($estadoTexto); ?>
                    </span>
                    <span class="tm-user-chip"><i class="fa fa-briefcase"></i> <?php echo tmUsuariosEsc($rolUsuario); ?></span>
                  </div>

                  <div class="tm-user-info">
                    <div class="tm-user-info-box">
                      <b>Perfil</b>
                      <?php echo tmUsuariosEsc($perfilUsuario); ?>
                    </div>
                    <div class="tm-user-info-box">
                      <b>Ultimo login</b>
                      <?php echo tmUsuariosEsc($loginUsuario); ?>
                    </div>
                    <div class="tm-user-info-box" style="grid-column:1 / -1">
                      <b>Correo</b>
                      <?php echo tmUsuariosEsc($correoUsuario); ?>
                    </div>
                  </div>

                  <div class="tm-user-actions">
                    <?php echo $acciones; ?>
                  </div>
                  <div class="usuario-card-actions-template">
                    <?php echo $acciones; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="tm-users-empty">
              <i class="fa fa-info-circle"></i> Todavia no hay usuarios registrados.
            </div>
          <?php endif; ?>
        </div>

        <aside class="tm-users-side">
          <h3><i class="fa fa-sitemap"></i> Roles habilitados</h3>
          <div class="tm-role-list">
            <?php foreach($rolesDisponibles as $valorRol => $textoRol): ?>
              <div class="tm-role-row">
                <i class="fa fa-id-card-o"></i>
                <div>
                  <b><?php echo tmUsuariosEsc($textoRol); ?></b>
                  <span><?php echo $valorRol === "almacen" ? "Almacen / inventario" : "Rol operativo"; ?></span>
                </div>
                <span class="tm-role-count"><?php echo $rolesResumen[$valorRol] ?? 0; ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </aside>
      </div>

    </div>
  </section>
</div>

<div id="modalDetalleUsuario" class="modal fade usuario-detail-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="tm-detail-head">
        <img id="detalleUsuarioFoto" src="vistas/img/usuarios/default/anonymous.png" alt="Usuario">
        <div>
          <h3 id="detalleUsuarioNombre">Usuario</h3>
          <p id="detalleUsuarioLogin">Ultimo login: -</p>
        </div>
        <button type="button" class="tm-modal-close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
      </div>
      <div class="tm-detail-body">
        <div class="tm-detail-grid">
          <div class="tm-detail-box"><b>Usuario</b><span id="detalleUsuarioUsuario">-</span></div>
          <div class="tm-detail-box"><b>Correo</b><span id="detalleUsuarioEmail">-</span></div>
          <div class="tm-detail-box"><b>Estado</b><span id="detalleUsuarioEstado">-</span></div>
          <div class="tm-detail-box"><b>Perfil</b><span id="detalleUsuarioPerfil">-</span></div>
          <div class="tm-detail-box"><b>Rol</b><span id="detalleUsuarioRol">-</span></div>
          <div class="tm-detail-box"><b>Acceso</b><span>Cuenta interna del sistema</span></div>
        </div>
        <div id="detalleUsuarioAcciones"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalAgregarUsuario" class="modal fade usuarios-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data" id="formAgregarUsuario">

        <div class="tm-form-head">
          <div class="tm-form-head-icon"><i class="fa fa-user-plus"></i></div>
          <div>
            <h4>Agregar usuario</h4>
            <p>El sistema genera el usuario automaticamente y envia una contrasena temporal al correo registrado.</p>
          </div>
          <button type="button" class="tm-modal-close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
        </div>

        <div class="modal-body">
          <div class="tm-user-form-grid">
            <div class="tm-user-form-card">
              <h5><i class="fa fa-address-card"></i> Datos principales</h5>

              <div class="tm-user-field">
                <label>Nombre completo</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-user"></i></span>
                  <input type="text" class="form-control" name="nuevoNombre" id="nuevoNombreUsuario" placeholder="Ej. Brandon Nava" required>
                </div>
              </div>

              <div class="tm-user-field">
                <label>Documento / CI</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-id-card"></i></span>
                  <input type="text" class="form-control" name="nuevoDocumentoUsuario" id="nuevoDocumentoUsuario" placeholder="Ej. 78552656">
                </div>
              </div>

              <div class="tm-user-field">
                <label>Correo electronico</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                  <input type="email" class="form-control" name="nuevoEmail" placeholder="correo@empresa.com" required>
                </div>
              </div>

              <input type="hidden" name="nuevoUsuario" id="nuevoUsuario">
              <input type="hidden" name="passwordGeneradaAutomaticamente" value="1">

              <div class="tm-user-generated">
                <span><i class="fa fa-key"></i> Usuario generado</span>
                <strong id="vistaUsuarioGenerado">Complete nombre y documento</strong>
              </div>

              <div class="tm-user-note">
                Cuando el admin o el area correspondiente entregue el acceso, el usuario recibira sus credenciales temporales por correo y en el primer ingreso debera cambiarlas.
              </div>
            </div>

            <div class="tm-user-form-card">
              <h5><i class="fa fa-lock"></i> Acceso y foto</h5>

              <div class="tm-user-field">
                <label>Perfil</label>
                <select class="form-control" name="nuevoPerfil" id="nuevoPerfil" required>
                  <option value="">Seleccionar perfil</option>
                  <option value="Administrador">Administrador</option>
                  <option value="Especial">Especial</option>
                  <option value="Vendedor">Vendedor</option>
                </select>
              </div>

              <div class="tm-user-field">
                <label>Rol operativo</label>
                <select class="form-control" name="nuevoRol" id="nuevoRol" required>
                  <option value="">Seleccionar rol</option>
                  <?php foreach($rolesDisponibles as $valorRol => $textoRol): ?>
                    <option value="<?php echo tmUsuariosEsc($valorRol); ?>"><?php echo tmUsuariosEsc($textoRol); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="tm-photo-box">
                <img src="vistas/img/usuarios/default/anonymous.png" class="previsualizar" alt="Vista previa">
                <input type="file" class="nuevaFoto" name="nuevaFoto">
                <p class="help-block">Foto opcional en JPG o PNG. Maximo 2MB.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar usuario</button>
        </div>

        <?php
          $crearUsuario = new ControladorUsuarios();
          $crearUsuario -> ctrCrearUsuario();
        ?>

      </form>
    </div>
  </div>
</div>

<div id="modalEditarUsuario" class="modal fade usuarios-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form role="form" method="post" enctype="multipart/form-data">

        <div class="tm-form-head">
          <div class="tm-form-head-icon"><i class="fa fa-pencil"></i></div>
          <div>
            <h4>Editar usuario</h4>
            <p>Actualiza datos de contacto, perfil, rol, estado visual y fotografia del usuario.</p>
          </div>
          <button type="button" class="tm-modal-close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
        </div>

        <div class="modal-body">
          <div class="tm-user-form-grid">
            <div class="tm-user-form-card">
              <h5><i class="fa fa-address-card"></i> Datos del usuario</h5>

              <div class="tm-user-field">
                <label>Nombre completo</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-user"></i></span>
                  <input type="text" class="form-control" id="editarNombre" name="editarNombre" required>
                </div>
              </div>

              <div class="tm-user-field">
                <label>Usuario</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-key"></i></span>
                  <input type="text" class="form-control" id="editarUsuario" name="editarUsuario" readonly>
                  <input type="hidden" id="idUsuario" name="idUsuario">
                </div>
              </div>

              <div class="tm-user-field">
                <label>Correo electronico</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                  <input type="email" class="form-control" id="editarEmail" name="editarEmail" placeholder="Correo electronico">
                </div>
              </div>

              <div class="tm-user-field">
                <label>Nueva contrasena</label>
                <div class="input-group">
                  <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                  <input type="password" class="form-control" name="editarPassword" placeholder="Dejar vacio para mantener la actual">
                  <input type="hidden" id="passwordActual" name="passwordActual">
                </div>
              </div>
            </div>

            <div class="tm-user-form-card">
              <h5><i class="fa fa-sitemap"></i> Perfil y rol</h5>

              <div class="tm-user-field">
                <label>Perfil</label>
                <select class="form-control" name="editarPerfil" id="editarPerfil" required>
                  <option value="">Seleccionar perfil</option>
                  <option value="Administrador">Administrador</option>
                  <option value="Especial">Especial</option>
                  <option value="Vendedor">Vendedor</option>
                </select>
              </div>

              <div class="tm-user-field">
                <label>Rol operativo</label>
                <select class="form-control" name="editarRol" id="editarRol" required>
                  <option value="">Seleccionar rol</option>
                  <?php foreach($rolesDisponibles as $valorRol => $textoRol): ?>
                    <option value="<?php echo tmUsuariosEsc($valorRol); ?>"><?php echo tmUsuariosEsc($textoRol); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="tm-photo-box">
                <img src="vistas/img/usuarios/default/anonymous.png" class="previsualizarEditar" alt="Vista previa">
                <input type="file" class="nuevaFoto" name="editarFoto">
                <input type="hidden" name="fotoActual" id="fotoActual">
                <p class="help-block">Si no subes una imagen nueva, se mantiene la foto actual.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
        </div>

        <?php
          $editarUsuario = new ControladorUsuarios();
          $editarUsuario -> ctrEditarUsuario();
        ?>

      </form>
    </div>
  </div>
</div>

<?php
  $borrarUsuario = new ControladorUsuarios();
  $borrarUsuario -> ctrBorrarUsuario();
?>
