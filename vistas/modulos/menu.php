<?php

$perfilMenu = $_SESSION["perfil"];
$rolMenu = $_SESSION["rol"];
$vistaRolMenu = $_SESSION["vistaRolMenu"] ?? "";
$esAdminRealMenu = ($_SESSION["perfil"] == "Administrador");

if($esAdminRealMenu && $vistaRolMenu != "" && $vistaRolMenu != "administrador"){
  $rolMenu = $vistaRolMenu;
  $perfilMenu = ($vistaRolMenu == "vendedor") ? "Vendedor" : "Especial";
}

$idUsuarioMenu = (int)($_SESSION["id"] ?? 0);
$menuBadges = array();

function tmMenuCount($sql, $params = array()){
  try{
    $stmt = Conexion::conectar()->prepare($sql);
    foreach($params as $key => $value){
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
  }catch(Exception $e){
    return 0;
  }
}

function tmMenuBadge($count){
  $count = (int)$count;
  if($count <= 0){
    return "";
  }
  $label = ($count > 99) ? "99+" : $count;
  return '<small class="label pull-right tm-menu-badge">'.$label.'</small>';
}

$menuBadges["ventas"] = ($rolMenu == "vendedor" && $perfilMenu != "Administrador")
  ? tmMenuCount("SELECT COUNT(*) FROM ventas WHERE id_vendedor = :id AND (estado_pago = 'pendiente' OR (estado_pago = 'aprobado' AND estado_despacho = 'pendiente'))", array(":id" => $idUsuarioMenu))
  : tmMenuCount("SELECT COUNT(*) FROM ventas WHERE estado_pago = 'pendiente' OR (estado_pago = 'aprobado' AND estado_despacho = 'pendiente')");

$menuBadges["pagos-ventas"] = tmMenuCount("SELECT COUNT(*) FROM ventas WHERE estado_pago = 'pendiente'");
$menuBadges["despacho"] = tmMenuCount("SELECT COUNT(*) FROM ventas WHERE estado_pago = 'aprobado' AND estado_despacho = 'pendiente'");
$menuBadges["solicitudes-web"] = tmMenuCount("SELECT COUNT(*) FROM cotizaciones WHERE origen = 'web' AND estado_web IN ('pendiente','en_revision')");
$menuBadges["consultas-web"] = tmMenuCount("SELECT COUNT(*) FROM web_consulta_mensajes WHERE emisor='cliente' AND leido_interno=0");
$menuBadges["pagos-servicios"] = tmMenuCount("SELECT COUNT(*) FROM servicios_ventas WHERE estado_pago IN ('pendiente','pendiente_adelanto','pendiente_final') OR (tipo_servicio = 'Soporte tecnico en taller' AND estado_pago = 'pendiente_retiro' AND estado_servicio = 'listo_cobro')");
$menuBadges["administrar-servicios"] = ($rolMenu == "vendedor" && $perfilMenu != "Administrador")
  ? tmMenuCount("SELECT COUNT(*) FROM servicios_ventas
                 WHERE id_vendedor = :id
                   AND estado_servicio NOT IN ('completado','cancelado')
                   AND (estado_pago LIKE 'pendiente%' OR estado_servicio LIKE 'pendiente%' OR estado_servicio = 'listo_cobro')", array(":id" => $idUsuarioMenu))
  : tmMenuCount("SELECT COUNT(*) FROM servicios_ventas
                 WHERE estado_servicio NOT IN ('completado','cancelado')
                   AND (estado_pago LIKE 'pendiente%' OR estado_servicio LIKE 'pendiente%' OR estado_servicio = 'listo_cobro')");
$menuBadges["ordenes-servicio"] = ($rolMenu == "tecnico" && !$esAdminRealMenu)
  ? tmMenuCount("SELECT COUNT(*) FROM servicios_ventas
                 WHERE id_tecnico = :id
                   AND tipo_servicio <> 'Desarrollo de software'
                   AND tipo_servicio NOT LIKE '%software%'
                   AND estado_servicio IN ('asignado','en_almacen','atendiendo','retiro_solicitado','en_proceso','diagnosticado','autorizado','rep_solicitado','rep_entregado','reparado')", array(":id" => $idUsuarioMenu))
  : tmMenuCount("SELECT COUNT(*) FROM servicios_ventas
                 WHERE tipo_servicio <> 'Desarrollo de software'
                   AND tipo_servicio NOT LIKE '%software%'
                   AND estado_servicio IN ('asignado','en_almacen','atendiendo','retiro_solicitado','en_proceso','diagnosticado','autorizado','rep_solicitado','rep_entregado','reparado')");
$menuBadges["proyectos"] = ($rolMenu == "desarrollador" && $perfilMenu != "Administrador")
  ? tmMenuCount("SELECT COUNT(*)
                 FROM proyectos_software p
                 INNER JOIN servicios_ventas s ON s.id = p.id_servicio
                 WHERE p.id_desarrollador = :id
                   AND p.estado NOT IN ('completado','cancelado')", array(":id" => $idUsuarioMenu))
  : tmMenuCount("SELECT COUNT(*)
                 FROM proyectos_software p
                 INNER JOIN servicios_ventas s ON s.id = p.id_servicio
                 WHERE p.estado NOT IN ('completado','cancelado')");
$menuBadges["solicitudes-de-compra"] = ($rolMenu == "cajero")
  ? tmMenuCount("SELECT COUNT(*) FROM compra WHERE estado IN ('pendiente','en_compra','rendicion_pendiente')")
  : tmMenuCount("SELECT COUNT(*) FROM compra WHERE estado IN ('pendiente','aprobado','en_compra','desembolsado','rendicion_pendiente','compra_rendida','entregado_almacen')");
$menuBadges["solicitudes-aprobadas"] = ($esAdminRealMenu && $rolMenu == "mensajero")
  ? tmMenuCount("SELECT COUNT(*) FROM compra WHERE estado IN ('aprobado','en_compra','desembolsado','rendicion_pendiente','compra_rendida')")
  : tmMenuCount("SELECT COUNT(*) FROM compra WHERE estado IN ('aprobado','en_compra','desembolsado','rendicion_pendiente','compra_rendida') AND (id_mensajero IS NULL OR id_mensajero = 0 OR id_mensajero = :id)", array(":id" => $idUsuarioMenu));
$menuBadges["ordenes-ingreso-material"] = tmMenuCount("SELECT COUNT(*) FROM compra WHERE estado IN ('compra_rendida','entregado_almacen')");
$menuBadges["recepcion-equipos-taller"] = tmMenuCount("SELECT COUNT(*) FROM servicio_taller_equipos WHERE estado_equipo IN ('ingresado','recibido_almacen','retiro_solicitado','pendiente_reingreso','devuelto_almacen') OR (estado_equipo = 'retirado_tecnico' AND (id_almacenero_retiro IS NULL OR id_almacenero_retiro = 0))");
$menuBadges["repuestos-taller-almacen"] = tmMenuCount("SELECT COUNT(*) FROM servicio_taller_repuestos WHERE estado = 'solicitado'");
$menuBadges["productos-cajero"] = tmMenuCount("SELECT COUNT(*) FROM productos WHERE stock > 0 AND (requiere_precio = 1 OR precio_venta <= 0)");
$menuBadges["crear-compra-almacen"] = 0;

$menuParentBadges = array(
  "ventas" => $menuBadges["ventas"],
  "cotizaciones" => $menuBadges["solicitudes-web"],
  "cobros" => $menuBadges["pagos-ventas"] + $menuBadges["pagos-servicios"],
  "servicios" => 0,
  "almacen" => 0,
  "compras" => $menuBadges["solicitudes-de-compra"],
  "precios" => $menuBadges["productos-cajero"],
  "mensajero" => $menuBadges["solicitudes-aprobadas"]
);

if($perfilMenu == "Administrador"){
  $menuParentBadges["servicios"] = $menuBadges["administrar-servicios"] + $menuBadges["ordenes-servicio"] + $menuBadges["proyectos"];
  $menuParentBadges["almacen"] = $menuBadges["solicitudes-de-compra"] + $menuBadges["ordenes-ingreso-material"] + $menuBadges["recepcion-equipos-taller"] + $menuBadges["repuestos-taller-almacen"] + $menuBadges["despacho"];
}else{
  if($rolMenu == "vendedor"){
    $menuParentBadges["servicios"] = $menuBadges["administrar-servicios"];
  }
  if($rolMenu == "tecnico"){
    $menuParentBadges["servicios"] = $menuBadges["ordenes-servicio"];
  }
  if($rolMenu == "desarrollador"){
    $menuParentBadges["servicios"] = $menuBadges["proyectos"];
  }
  if($rolMenu == "almacen"){
    $menuParentBadges["almacen"] = $menuBadges["solicitudes-de-compra"] + $menuBadges["ordenes-ingreso-material"] + $menuBadges["recepcion-equipos-taller"] + $menuBadges["repuestos-taller-almacen"] + $menuBadges["despacho"];
  }
  if($rolMenu == "cajero"){
    $menuParentBadges["almacen"] = 0;
  }
}

?>

<style>
  .main-sidebar .sidebar{
    padding:12px 10px 18px;
  }
  .main-sidebar .user-panel{
    margin:2px 0 12px;
    padding:12px;
    background:linear-gradient(135deg,rgba(37,99,235,.22),rgba(14,165,233,.12));
    border:1px solid rgba(148,163,184,.22);
    border-radius:16px;
  }
  .main-sidebar .user-panel:before,
  .main-sidebar .user-panel:after{
    content:"";
    display:table;
  }
  .main-sidebar .user-panel:after{
    clear:both;
  }
  .main-sidebar .user-panel>.image>img{
    width:42px;
    height:42px;
    border:2px solid rgba(255,255,255,.38);
    object-fit:cover;
  }
  .main-sidebar .user-panel>.info{
    left:60px;
    padding-top:6px;
    max-width:150px;
  }
  .main-sidebar .user-panel>.info>p{
    font-weight:800;
    color:#fff;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    margin-bottom:2px;
  }
  .main-sidebar .user-panel>.info>a{
    color:#b8d7ff;
    font-size:11px;
    line-height:1.2;
    white-space:normal;
  }
  .vista-menu-alert{
    margin:0 0 12px;
    padding:10px 11px;
    background:rgba(255,255,255,.08);
    color:#e2e8f0;
    border:1px solid rgba(148,163,184,.22);
    border-radius:12px;
    font-size:12px;
    line-height:1.3;
    white-space:normal;
    overflow-wrap:anywhere;
    word-break:break-word;
  }
  .vista-menu-alert a{
    display:inline-block;
    max-width:100%;
    white-space:normal;
    overflow-wrap:anywhere;
    color:#93c5fd;
    text-decoration:none;
    font-weight:800;
  }
  body.sidebar-collapse .vista-menu-alert{
    display:none !important;
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right),
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      width:230px !important;
      max-width:230px !important;
      left:50px !important;
      white-space:normal !important;
      overflow-wrap:anywhere;
      word-break:break-word;
      z-index:10000;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span{
      min-height:44px;
      height:auto !important;
      line-height:1.25;
      padding:11px 12px 10px 16px !important;
      background:#111827 !important;
      border-radius:0 12px 0 0;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      top:auto !important;
      margin-left:0 !important;
      padding:6px 0;
      background:#172033 !important;
      border-radius:0 0 12px 0;
      box-shadow:10px 14px 32px rgba(0,0,0,.25);
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a{
      white-space:normal !important;
      overflow-wrap:anywhere;
      word-break:break-word;
      line-height:1.25;
      padding:8px 10px 8px 18px;
      min-height:34px;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > .pull-right-container{
      left:230px !important;
      top:-24px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:not(.treeview) > a > span{
      border-radius:0 12px 12px 0;
    }
  }
  .sidebar-menu{
    display:block;
  }
  .sidebar-menu > li.header{
    margin:8px 6px 8px;
    padding:8px 8px 6px;
    color:#7dd3fc !important;
    background:transparent !important;
    font-size:10px;
    font-weight:900;
    letter-spacing:.12em;
    text-transform:uppercase;
  }
  .skin-blue .sidebar-menu > li > a,
  .skin-blue .sidebar-menu .treeview-menu > li > a{
    color:#cbd5e1;
  }
  .sidebar-menu > li{
    margin:3px 0;
  }
  .sidebar-menu > li > a{
    min-height:42px;
    display:flex;
    align-items:center;
    gap:9px;
    padding:11px 12px;
    border-left:0 !important;
    border-radius:12px;
    font-weight:750;
    letter-spacing:.01em;
    transition:background .18s ease,color .18s ease,transform .18s ease;
  }
  .sidebar-menu > li > a > i:first-child{
    width:26px;
    height:26px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    flex:0 0 26px;
    color:#93c5fd;
    background:rgba(37,99,235,.15);
    border-radius:9px;
    font-size:14px;
  }
  .sidebar-menu > li > a > span{
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .skin-blue .sidebar-menu > li:hover > a,
  .skin-blue .sidebar-menu > li.active > a,
  .skin-blue .sidebar-menu > li.menu-open > a{
    color:#fff;
    background:linear-gradient(135deg,rgba(37,99,235,.72),rgba(14,165,233,.34));
  }
  .skin-blue .sidebar-menu > li:hover > a > i:first-child,
  .skin-blue .sidebar-menu > li.active > a > i:first-child,
  .skin-blue .sidebar-menu > li.menu-open > a > i:first-child{
    color:#fff;
    background:rgba(255,255,255,.18);
  }
  .skin-blue .sidebar-menu > li > .treeview-menu{
    margin:3px 0 8px 15px;
    padding:5px 0 5px 8px;
    background:transparent;
    border-left:1px solid rgba(148,163,184,.22);
  }
  .sidebar-menu .treeview-menu > li > a{
    min-height:34px;
    padding:8px 10px 8px 12px;
    margin:2px 0;
    border-radius:10px;
    font-size:12px;
    font-weight:650;
    white-space:normal;
    overflow-wrap:anywhere;
  }
  .sidebar-menu .treeview-menu > li > a > i{
    width:18px;
    color:#7dd3fc;
  }
  .skin-blue .sidebar-menu .treeview-menu > li > a:hover,
  .skin-blue .sidebar-menu .treeview-menu > li.active > a{
    color:#fff;
    background:rgba(37,99,235,.22);
  }
  .tm-menu-badge{
    background:#f97316;
    color:#fff;
    font-weight:900;
    border-radius:999px;
    padding:3px 7px;
    margin-top:0;
    min-width:21px;
    text-align:center;
    box-shadow:0 6px 16px rgba(249,115,22,.28);
  }
  .treeview-menu .tm-menu-badge{
    margin-right:8px;
    margin-top:2px;
  }
  .sidebar-menu > li > a > .tm-menu-badge{
    position:relative;
    top:0;
  }
  body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > .tm-menu-badge{
    display:none;
  }
  body.sidebar-mini.sidebar-collapse .main-sidebar .sidebar{
    padding-left:6px;
    padding-right:6px;
  }
  body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel{
    padding:8px 4px;
    background:transparent;
    border-color:transparent;
  }
  body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image>img{
    width:36px;
    height:36px;
  }
  body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a{
    justify-content:center;
    padding:10px 8px;
    border-radius:12px;
  }
  body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > i:first-child{
    margin:0;
  }
  /* Rediseño alineado al index publico: glass claro + azul TechMind */
  .main-sidebar .sidebar{
    padding:14px 10px 20px;
  }
  .main-sidebar .user-panel{
    background:rgba(255,255,255,.36);
    border:1px solid rgba(223,232,243,.78);
    box-shadow:0 14px 28px rgba(23,75,134,.08);
  }
  .main-sidebar .user-panel>.image>img{
    border-color:rgba(93,135,255,.34);
  }
  .main-sidebar .user-panel>.info>p{
    color:#172033;
  }
  .main-sidebar .user-panel>.info>a{
    color:#61718b;
    font-weight:700;
  }
  .main-sidebar .user-panel>.info>a .text-success{
    color:#16a34a !important;
  }
  .vista-menu-alert{
    background:rgba(236,242,255,.46);
    color:#174b86;
    border-color:rgba(93,135,255,.24);
    box-shadow:0 12px 24px rgba(23,75,134,.08);
  }
  .vista-menu-alert a{
    color:#174b86;
  }
  .sidebar-menu > li.header{
    color:#174b86 !important;
  }
  .skin-blue .sidebar-menu > li > a,
  .skin-blue .sidebar-menu .treeview-menu > li > a{
    color:#172033;
  }
  .sidebar-menu > li > a{
    background:rgba(255,255,255,.22);
    border:1px solid transparent;
  }
  .sidebar-menu > li > a > i:first-child{
    color:#174b86;
    background:rgba(236,242,255,.56);
    border:1px solid rgba(93,135,255,.18);
  }
  .skin-blue .sidebar-menu > li:hover > a,
  .skin-blue .sidebar-menu > li.active > a,
  .skin-blue .sidebar-menu > li.menu-open > a{
    color:#174b86;
    background:rgba(236,242,255,.62);
    border-color:rgba(93,135,255,.24);
    box-shadow:0 12px 26px rgba(23,75,134,.10);
  }
  .skin-blue .sidebar-menu > li:hover > a > i:first-child,
  .skin-blue .sidebar-menu > li.active > a > i:first-child,
  .skin-blue .sidebar-menu > li.menu-open > a > i:first-child{
    color:#fff;
    background:linear-gradient(135deg,#174b86,#5d87ff);
  }
  .skin-blue .sidebar-menu > li > .treeview-menu{
    border-left:1px solid rgba(93,135,255,.20);
  }
  .sidebar-menu .treeview-menu > li > a{
    color:#61718b;
    background:transparent;
  }
  .sidebar-menu .treeview-menu > li > a > i{
    color:#174b86;
  }
  .skin-blue .sidebar-menu .treeview-menu > li > a:hover,
  .skin-blue .sidebar-menu .treeview-menu > li.active > a{
    color:#174b86;
    background:rgba(236,242,255,.48);
  }
  .tm-menu-badge{
    background:#174b86;
    box-shadow:0 8px 18px rgba(23,75,134,.18);
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span{
      background:rgba(255,255,255,.96) !important;
      color:#174b86;
      border:1px solid rgba(223,232,243,.86);
      box-shadow:0 16px 34px rgba(23,75,134,.12);
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      background:rgba(255,255,255,.96) !important;
      border:1px solid rgba(223,232,243,.86);
      box-shadow:0 18px 38px rgba(23,75,134,.13);
    }
  }
  /* Menu funcional, legible y compatible con claro/oscuro */
  .main-sidebar .sidebar{
    padding:12px 12px 22px;
  }
  .sidebar-menu > li{
    margin:4px 0;
  }
  .sidebar-menu > li > a{
    display:grid;
    grid-template-columns:30px minmax(0,1fr) auto auto;
    align-items:center;
    column-gap:10px;
    min-height:46px;
    padding:10px 11px;
    border-radius:12px;
    white-space:normal;
  }
  .sidebar-menu > li > a > i:first-child{
    width:30px;
    height:30px;
    margin:0;
  }
  .sidebar-menu > li > a > span:not(.pull-right-container){
    display:block;
    white-space:normal;
    overflow:visible;
    text-overflow:clip;
    line-height:1.2;
  }
  .sidebar-menu > li > a > .pull-right-container{
    position:static;
    width:auto;
    margin:0;
  }
  .sidebar-menu > li > a > .pull-right-container .fa{
    margin:0;
  }
  .sidebar-menu .treeview-menu > li > a{
    display:grid;
    grid-template-columns:20px minmax(0,1fr) auto;
    align-items:center;
    gap:8px;
    white-space:normal !important;
    overflow:visible;
    line-height:1.25;
    padding:9px 10px;
  }
  .sidebar-menu .treeview-menu > li > a .tm-menu-badge{
    grid-column:3;
  }
  .tm-menu-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    float:none !important;
    justify-self:end;
    min-width:24px;
    height:22px;
    padding:0 7px;
    margin:0 !important;
    line-height:1;
    font-size:11px;
  }
  body.tm-dark-mode .main-sidebar .user-panel,
  body.tm-dark-mode .sidebar-menu > li > a{
    background:rgba(16,26,46,.46);
    border-color:rgba(43,59,94,.76);
  }
  body.tm-dark-mode .main-sidebar .user-panel>.info>p,
  body.tm-dark-mode .skin-blue .sidebar-menu > li > a,
  body.tm-dark-mode .skin-blue .sidebar-menu .treeview-menu > li > a{
    color:#e5edf7;
  }
  body.tm-dark-mode .main-sidebar .user-panel>.info>a,
  body.tm-dark-mode .sidebar-menu .treeview-menu > li > a{
    color:#9fb0c7;
  }
  body.tm-dark-mode .skin-blue .sidebar-menu > li:hover > a,
  body.tm-dark-mode .skin-blue .sidebar-menu > li.active > a,
  body.tm-dark-mode .skin-blue .sidebar-menu > li.menu-open > a{
    color:#fff;
    background:rgba(93,135,255,.18);
    border-color:rgba(93,135,255,.34);
  }
  body.tm-dark-mode .skin-blue .sidebar-menu .treeview-menu > li > a:hover,
  body.tm-dark-mode .skin-blue .sidebar-menu .treeview-menu > li.active > a{
    color:#fff;
    background:rgba(93,135,255,.14);
  }
  body.tm-dark-mode .vista-menu-alert{
    background:rgba(93,135,255,.14);
    color:#dbeafe;
    border-color:rgba(93,135,255,.28);
  }
  body.tm-dark-mode .vista-menu-alert a{
    color:#8fb3ff;
  }
  body.tm-dark-mode .tm-menu-badge{
    background:#5d87ff;
    color:#07111f;
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a{
      display:flex;
      align-items:center;
      justify-content:center;
      width:42px;
      min-height:42px;
      padding:8px;
      margin:0 auto;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > i:first-child{
      width:30px;
      height:30px;
    }
  }

  /* TechMind menu v2: limpio, visible y alineado al index publico */
  .main-sidebar{
    padding-top:64px !important;
    width:240px;
    background:rgba(255,255,255,.26) !important;
    border-right:1px solid rgba(223,232,243,.58);
    box-shadow:14px 0 38px rgba(23,75,134,.08);
    backdrop-filter:blur(16px);
  }
  .main-sidebar .sidebar{
    padding:12px 10px 24px;
  }
  .tm-sidebar-brand{
    display:flex;
    align-items:center;
    gap:10px;
    min-height:58px;
    padding:10px 11px;
    margin:0 0 10px;
    color:#172033 !important;
    text-decoration:none !important;
    border:1px solid rgba(223,232,243,.70);
    border-radius:16px;
    background:rgba(255,255,255,.32);
    box-shadow:0 14px 28px rgba(23,75,134,.08);
  }
  .tm-brand-mark{
    width:40px;
    height:40px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    flex:0 0 40px;
    border-radius:13px;
    background:rgba(236,242,255,.50);
    overflow:hidden;
  }
  .tm-brand-mark img{
    width:38px;
    height:38px;
    object-fit:contain;
    display:block;
  }
  .tm-brand-text{
    min-width:0;
    line-height:1.05;
  }
  .tm-brand-text strong{
    display:block;
    font-size:16px;
    font-weight:900;
    color:#174b86;
    letter-spacing:.1px;
  }
  .tm-brand-text small{
    display:block;
    margin-top:4px;
    font-size:10px;
    font-weight:800;
    color:#61718b;
    text-transform:uppercase;
    letter-spacing:.05em;
    white-space:nowrap;
  }
  .main-sidebar .user-panel{
    display:grid;
    grid-template-columns:42px minmax(0,1fr);
    align-items:center;
    gap:10px;
    min-height:64px;
    padding:10px;
    margin:0 0 12px;
    border-radius:16px;
    background:rgba(255,255,255,.24);
    border:1px solid rgba(223,232,243,.62);
    box-shadow:none;
  }
  .main-sidebar .user-panel>.image,
  .main-sidebar .user-panel>.info{
    float:none !important;
    position:static !important;
    left:auto !important;
    padding:0 !important;
    max-width:none !important;
  }
  .main-sidebar .user-panel>.image>img{
    width:42px;
    height:42px;
    border:2px solid rgba(93,135,255,.28);
    object-fit:cover;
  }
  .main-sidebar .user-panel>.info>p{
    margin:0 0 4px;
    color:#172033;
    font-size:12px;
    font-weight:900;
    line-height:1.15;
  }
  .main-sidebar .user-panel>.info>a{
    display:block;
    color:#61718b;
    font-size:10px;
    font-weight:800;
    line-height:1.22;
  }
  .sidebar-menu > li.header{
    margin:12px 8px 8px;
    padding:0;
    color:#174b86 !important;
    font-size:10px;
    font-weight:900;
    letter-spacing:.14em;
  }
  .sidebar-menu > li{
    margin:5px 0;
  }
  .sidebar-menu > li > a{
    display:grid;
    grid-template-columns:32px minmax(0,1fr) auto auto;
    align-items:center;
    gap:9px;
    min-height:46px;
    padding:8px 10px;
    color:#172033 !important;
    border:1px solid transparent !important;
    border-radius:14px;
    background:rgba(255,255,255,.16);
    font-size:12px;
    font-weight:850;
    line-height:1.18;
  }
  .sidebar-menu > li > a > i:first-child{
    width:32px;
    height:32px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    color:#174b86;
    background:rgba(236,242,255,.62);
    border:1px solid rgba(93,135,255,.18);
    border-radius:11px;
    font-size:14px;
  }
  .sidebar-menu > li > a > span:not(.pull-right-container){
    white-space:normal;
    overflow:visible;
    text-overflow:clip;
  }
  .sidebar-menu > li > a > .pull-right-container{
    position:static;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    margin:0;
  }
  .skin-blue .sidebar-menu > li:hover > a,
  .skin-blue .sidebar-menu > li.active > a,
  .skin-blue .sidebar-menu > li.menu-open > a{
    color:#174b86 !important;
    background:rgba(236,242,255,.60) !important;
    border-color:rgba(93,135,255,.24) !important;
    box-shadow:0 12px 26px rgba(23,75,134,.10);
  }
  .skin-blue .sidebar-menu > li:hover > a > i:first-child,
  .skin-blue .sidebar-menu > li.active > a > i:first-child,
  .skin-blue .sidebar-menu > li.menu-open > a > i:first-child{
    color:#fff;
    background:linear-gradient(135deg,#174b86,#5d87ff);
  }
  .skin-blue .sidebar-menu > li > .treeview-menu{
    margin:6px 0 8px 16px;
    padding:5px 0 5px 10px;
    background:transparent !important;
    border-left:1px solid rgba(93,135,255,.20);
  }
  .sidebar-menu .treeview-menu > li > a{
    display:grid;
    grid-template-columns:20px minmax(0,1fr) auto;
    align-items:center;
    gap:8px;
    min-height:34px;
    padding:8px 9px;
    color:#61718b !important;
    border-radius:10px;
    font-size:12px;
    font-weight:750;
    white-space:normal !important;
  }
  .sidebar-menu .treeview-menu > li > a > i{
    color:#174b86;
  }
  .skin-blue .sidebar-menu .treeview-menu > li > a:hover,
  .skin-blue .sidebar-menu .treeview-menu > li.active > a{
    color:#174b86 !important;
    background:rgba(236,242,255,.48) !important;
  }
  .tm-menu-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    justify-self:end;
    min-width:22px;
    height:22px;
    padding:0 7px;
    float:none !important;
    margin:0 !important;
    color:#fff;
    background:#174b86;
    border-radius:999px;
    font-size:11px;
    font-weight:900;
    line-height:1;
    box-shadow:0 8px 18px rgba(23,75,134,.18);
  }
  @media(min-width:768px){
    body.sidebar-mini.sidebar-collapse .main-sidebar{
      width:58px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand{
      justify-content:center;
      padding:8px;
      min-height:50px;
      border-color:transparent;
      background:transparent;
      box-shadow:none;
    }
    body.sidebar-mini.sidebar-collapse .tm-brand-text,
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.info,
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li.header,
    body.sidebar-mini.sidebar-collapse .tm-menu-badge{
      display:none !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-brand-mark{
      width:38px;
      height:38px;
      flex-basis:38px;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel{
      display:flex;
      justify-content:center;
      min-height:48px;
      padding:6px;
      margin-bottom:8px;
      border-color:transparent;
      background:transparent;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image>img{
      width:36px;
      height:36px;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a{
      display:flex;
      justify-content:center;
      width:42px;
      min-height:42px;
      margin:0 auto;
      padding:5px;
      border-radius:14px;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > i:first-child{
      width:32px;
      height:32px;
    }
  }
  body.tm-dark-mode .main-sidebar{
    background:rgba(11,18,32,.72) !important;
    border-right-color:#22314e;
  }
  body.tm-dark-mode .tm-sidebar-brand,
  body.tm-dark-mode .main-sidebar .user-panel,
  body.tm-dark-mode .sidebar-menu > li > a{
    background:rgba(16,26,46,.54);
    border-color:rgba(43,59,94,.78) !important;
  }
  body.tm-dark-mode .tm-brand-text strong,
  body.tm-dark-mode .main-sidebar .user-panel>.info>p,
  body.tm-dark-mode .sidebar-menu > li > a{
    color:#e5edf7 !important;
  }
  body.tm-dark-mode .tm-brand-text small,
  body.tm-dark-mode .main-sidebar .user-panel>.info>a,
  body.tm-dark-mode .sidebar-menu .treeview-menu > li > a{
    color:#9fb0c7 !important;
  }
  body.tm-dark-mode .skin-blue .sidebar-menu > li:hover > a,
  body.tm-dark-mode .skin-blue .sidebar-menu > li.active > a,
  body.tm-dark-mode .skin-blue .sidebar-menu > li.menu-open > a{
    color:#fff !important;
    background:rgba(93,135,255,.18) !important;
    border-color:rgba(93,135,255,.34) !important;
  }

  /* Ajuste final: menu mas alto, sin zona borrosa y usuario legible */
  .main-sidebar{
    padding-top:50px !important;
    background:rgba(255,255,255,.18) !important;
    backdrop-filter:none !important;
    -webkit-backdrop-filter:none !important;
  }
  .main-sidebar .sidebar{
    padding:8px 9px 22px !important;
  }
  .tm-sidebar-brand{
    min-height:48px;
    margin-bottom:8px;
    padding:7px 9px;
    border-radius:13px;
    background:rgba(255,255,255,.22);
    box-shadow:none;
  }
  .tm-brand-mark{
    width:34px;
    height:34px;
    flex-basis:34px;
    border-radius:10px;
  }
  .tm-brand-mark img{
    width:32px;
    height:32px;
  }
  .tm-brand-text strong{
    font-size:15px;
  }
  .tm-brand-text small{
    font-size:9px;
  }
  .main-sidebar .user-panel{
    display:flex !important;
    align-items:center !important;
    gap:10px !important;
    min-height:56px !important;
    padding:8px 9px !important;
    margin:0 0 10px !important;
    border-radius:13px !important;
    background:rgba(255,255,255,.20) !important;
    border:1px solid rgba(223,232,243,.52) !important;
    box-shadow:none !important;
  }
  .main-sidebar .user-panel>.image,
  .main-sidebar .user-panel>.info{
    float:none !important;
    position:static !important;
    left:auto !important;
    top:auto !important;
    padding:0 !important;
  }
  .main-sidebar .user-panel>.image{
    width:38px !important;
    flex:0 0 38px !important;
  }
  .main-sidebar .user-panel>.image>img{
    width:38px !important;
    height:38px !important;
    display:block;
  }
  .main-sidebar .user-panel>.info{
    display:block !important;
    flex:1 1 auto !important;
    min-width:0 !important;
    max-width:none !important;
  }
  .main-sidebar .user-panel>.info>p{
    display:block !important;
    margin:0 0 3px !important;
    font-size:12px !important;
    line-height:1.1 !important;
    color:#172033 !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
  }
  .main-sidebar .user-panel>.info>a{
    display:block !important;
    font-size:10px !important;
    line-height:1.15 !important;
    color:#174b86 !important;
    white-space:normal !important;
    overflow:visible !important;
  }
  .sidebar-menu > li.header{
    margin:10px 8px 7px !important;
  }
  .sidebar-menu > li{
    margin:4px 0 !important;
  }
  .sidebar-menu > li > a{
    min-height:42px !important;
    padding:7px 9px !important;
    border-radius:11px !important;
    background:rgba(255,255,255,.12) !important;
    box-shadow:none !important;
  }
  .sidebar-menu > li > a > i:first-child{
    width:29px !important;
    height:29px !important;
    border-radius:9px !important;
    background:rgba(236,242,255,.42) !important;
  }
  .skin-blue .sidebar-menu > li:hover > a,
  .skin-blue .sidebar-menu > li.active > a,
  .skin-blue .sidebar-menu > li.menu-open > a{
    background:rgba(236,242,255,.44) !important;
    box-shadow:none !important;
  }
  .skin-blue .sidebar-menu > li > .treeview-menu{
    margin:4px 0 7px 14px !important;
    padding:3px 0 3px 8px !important;
  }
  .sidebar-menu .treeview-menu > li > a{
    min-height:31px !important;
    padding:7px 8px !important;
    border-radius:9px !important;
  }
  body.sidebar-mini.sidebar-collapse .main-sidebar{
    padding-top:50px !important;
  }
  body.sidebar-mini.sidebar-collapse .tm-sidebar-brand{
    min-height:42px;
    margin-bottom:6px;
  }
  body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel{
    min-height:42px !important;
    padding:4px !important;
  }
  body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image{
    width:34px !important;
    flex-basis:34px !important;
  }
  body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image>img{
    width:34px !important;
    height:34px !important;
  }
  body.tm-dark-mode .main-sidebar{
    background:rgba(11,18,32,.62) !important;
  }
  body.tm-dark-mode .tm-sidebar-brand,
  body.tm-dark-mode .main-sidebar .user-panel,
  body.tm-dark-mode .sidebar-menu > li > a{
    background:rgba(16,26,46,.34) !important;
  }

  /* Correccion final: logo lateral estable y flyout compacto sin espacios */
  .main-sidebar{
    padding-top:50px !important;
  }
  .main-sidebar .sidebar{
    padding-top:7px !important;
  }
  .tm-sidebar-brand{
    margin-top:0 !important;
    margin-bottom:10px !important;
    min-height:50px !important;
    overflow:visible !important;
  }
  .tm-brand-text{
    min-width:0 !important;
  }
  .tm-brand-text strong,
  .tm-brand-text small{
    display:block !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .main-sidebar{
      padding-top:50px !important;
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .sidebar{
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand{
      position:relative !important;
      width:42px !important;
      min-height:42px !important;
      padding:4px !important;
      margin:0 0 8px !important;
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand .tm-brand-mark{
      width:34px !important;
      height:34px !important;
      flex-basis:34px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand .tm-brand-text{
      display:none !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand:hover .tm-brand-text{
      display:block !important;
      position:absolute !important;
      left:42px !important;
      top:0 !important;
      width:230px !important;
      min-height:42px !important;
      padding:8px 12px !important;
      border:1px solid rgba(223,232,243,.86) !important;
      border-left:0 !important;
      border-radius:0 12px 12px 0 !important;
      background:rgba(255,255,255,.98) !important;
      box-shadow:0 16px 34px rgba(23,75,134,.14) !important;
      z-index:10020 !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand:hover .tm-brand-text strong{
      color:#172033 !important;
      font-size:13px !important;
      line-height:1.1 !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand:hover .tm-brand-text small{
      color:#174b86 !important;
      font-size:9px !important;
      line-height:1.1 !important;
      margin-top:3px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right),
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      left:50px !important;
      width:236px !important;
      max-width:236px !important;
      z-index:10010 !important;
      box-sizing:border-box !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      word-break:normal !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right){
      top:0 !important;
      min-height:42px !important;
      height:auto !important;
      margin:0 !important;
      padding:10px 12px !important;
      border-radius:0 12px 0 0 !important;
      background:rgba(255,255,255,.98) !important;
      color:#172033 !important;
      border:1px solid rgba(223,232,243,.86) !important;
      border-left:0 !important;
      box-shadow:0 16px 34px rgba(23,75,134,.14) !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      top:42px !important;
      margin:0 !important;
      padding:4px 0 6px !important;
      border-radius:0 0 12px 0 !important;
      background:rgba(255,255,255,.98) !important;
      border:1px solid rgba(223,232,243,.86) !important;
      border-top:0 !important;
      border-left:0 !important;
      box-shadow:0 20px 34px rgba(23,75,134,.13) !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a{
      min-height:32px !important;
      padding:8px 12px !important;
      color:#334155 !important;
      border-radius:0 !important;
      background:transparent !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a:hover{
      color:#174b86 !important;
      background:rgba(236,242,255,.74) !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:not(.treeview):hover > a > span:not(.pull-right){
      border-radius:0 12px 12px 0 !important;
    }
  }

  /* TechMind menu v3: marca superior, navegacion limpia y colapsado estable */
  .main-sidebar,
  .skin-blue .main-sidebar,
  .skin-blue .left-side{
    width:252px !important;
    padding-top:0 !important;
    background:
      linear-gradient(180deg,rgba(255,255,255,.74),rgba(244,248,255,.56)),
      radial-gradient(circle at 10% 4%,rgba(93,135,255,.18),transparent 30%) !important;
    border-right:1px solid rgba(174,194,224,.58) !important;
    box-shadow:16px 0 46px rgba(23,75,134,.10) !important;
    backdrop-filter:blur(12px) !important;
    -webkit-backdrop-filter:blur(12px) !important;
    overflow:visible !important;
  }
  .main-sidebar .sidebar{
    height:100vh !important;
    padding:10px 12px 24px !important;
    overflow-y:auto !important;
    overflow-x:visible !important;
  }
  .main-sidebar .sidebar::-webkit-scrollbar{
    width:6px;
  }
  .main-sidebar .sidebar::-webkit-scrollbar-thumb{
    background:rgba(93,135,255,.28);
    border-radius:999px;
  }
  .tm-sidebar-brand{
    position:relative !important;
    display:grid !important;
    grid-template-columns:46px minmax(0,1fr) !important;
    align-items:center !important;
    gap:11px !important;
    min-height:66px !important;
    margin:0 0 12px !important;
    padding:10px 12px !important;
    border:1px solid rgba(93,135,255,.22) !important;
    border-radius:18px !important;
    background:linear-gradient(135deg,rgba(23,75,134,.98),rgba(93,135,255,.92)) !important;
    color:#fff !important;
    box-shadow:0 18px 34px rgba(23,75,134,.22) !important;
    overflow:visible !important;
  }
  .tm-sidebar-brand:after{
    content:"";
    position:absolute;
    right:12px;
    bottom:10px;
    width:38px;
    height:38px;
    border-radius:50%;
    background:rgba(255,255,255,.14);
    pointer-events:none;
  }
  .tm-brand-mark{
    width:46px !important;
    height:46px !important;
    flex-basis:46px !important;
    border-radius:15px !important;
    background:rgba(255,255,255,.92) !important;
    border:1px solid rgba(255,255,255,.74) !important;
    box-shadow:0 10px 24px rgba(5,31,64,.16) !important;
    overflow:hidden !important;
  }
  .tm-brand-mark img{
    width:42px !important;
    height:42px !important;
    object-fit:contain !important;
  }
  .tm-brand-text{
    position:relative;
    z-index:1;
    line-height:1.05 !important;
  }
  .tm-brand-text strong{
    color:#fff !important;
    font-size:18px !important;
    font-weight:900 !important;
    letter-spacing:.2px !important;
  }
  .tm-brand-text small{
    color:rgba(255,255,255,.82) !important;
    font-size:9px !important;
    font-weight:900 !important;
    letter-spacing:.12em !important;
    text-transform:uppercase !important;
  }
  .main-sidebar .user-panel{
    display:grid !important;
    grid-template-columns:40px minmax(0,1fr) !important;
    align-items:center !important;
    gap:10px !important;
    min-height:58px !important;
    margin:0 0 12px !important;
    padding:9px 10px !important;
    border:1px solid rgba(223,232,243,.80) !important;
    border-radius:16px !important;
    background:rgba(255,255,255,.62) !important;
    box-shadow:0 12px 28px rgba(23,75,134,.07) !important;
  }
  .main-sidebar .user-panel>.image,
  .main-sidebar .user-panel>.info{
    float:none !important;
    position:static !important;
    left:auto !important;
    top:auto !important;
    width:auto !important;
    max-width:none !important;
    padding:0 !important;
  }
  .main-sidebar .user-panel>.image>img{
    width:40px !important;
    height:40px !important;
    border:2px solid rgba(93,135,255,.30) !important;
    object-fit:cover !important;
  }
  .main-sidebar .user-panel>.info>p{
    margin:0 0 4px !important;
    color:#172033 !important;
    font-size:12px !important;
    font-weight:900 !important;
    line-height:1.1 !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
  }
  .main-sidebar .user-panel>.info>a{
    display:block !important;
    color:#174b86 !important;
    font-size:10px !important;
    font-weight:800 !important;
    line-height:1.15 !important;
  }
  .sidebar-menu{
    padding:0 !important;
  }
  .sidebar-menu > li.header{
    margin:14px 8px 8px !important;
    padding:0 !important;
    color:#5d87ff !important;
    background:transparent !important;
    font-size:10px !important;
    font-weight:900 !important;
    letter-spacing:.16em !important;
    text-transform:uppercase !important;
  }
  .sidebar-menu > li{
    margin:6px 0 !important;
  }
  .sidebar-menu > li > a{
    display:grid !important;
    grid-template-columns:34px minmax(0,1fr) auto auto !important;
    align-items:center !important;
    gap:10px !important;
    min-height:46px !important;
    padding:7px 9px !important;
    border:1px solid rgba(223,232,243,.58) !important;
    border-radius:15px !important;
    background:rgba(255,255,255,.44) !important;
    color:#172033 !important;
    font-size:12px !important;
    font-weight:850 !important;
    line-height:1.14 !important;
    box-shadow:none !important;
  }
  .sidebar-menu > li > a > i:first-child{
    width:34px !important;
    height:34px !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    color:#174b86 !important;
    background:#ecf2ff !important;
    border:1px solid rgba(93,135,255,.18) !important;
    border-radius:12px !important;
    font-size:14px !important;
  }
  .sidebar-menu > li > a > span:not(.pull-right-container){
    min-width:0 !important;
    white-space:normal !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
  }
  .sidebar-menu > li > a > .pull-right-container{
    position:static !important;
    display:inline-flex !important;
    align-items:center !important;
    margin:0 !important;
  }
  .skin-blue .sidebar-menu > li:hover > a,
  .skin-blue .sidebar-menu > li.active > a,
  .skin-blue .sidebar-menu > li.menu-open > a{
    color:#174b86 !important;
    background:#ffffff !important;
    border-color:rgba(93,135,255,.34) !important;
    box-shadow:0 14px 28px rgba(23,75,134,.12) !important;
    transform:translateX(2px);
  }
  .skin-blue .sidebar-menu > li:hover > a > i:first-child,
  .skin-blue .sidebar-menu > li.active > a > i:first-child,
  .skin-blue .sidebar-menu > li.menu-open > a > i:first-child{
    color:#fff !important;
    background:linear-gradient(135deg,#174b86,#5d87ff) !important;
    border-color:transparent !important;
  }
  .tm-menu-badge{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    justify-self:end !important;
    min-width:22px !important;
    height:22px !important;
    padding:0 7px !important;
    float:none !important;
    margin:0 !important;
    color:#fff !important;
    background:#174b86 !important;
    border-radius:999px !important;
    font-size:10px !important;
    font-weight:900 !important;
    box-shadow:0 8px 18px rgba(23,75,134,.18) !important;
  }
  .skin-blue .sidebar-menu > li > .treeview-menu{
    margin:6px 0 10px 18px !important;
    padding:6px 0 6px 12px !important;
    background:transparent !important;
    border-left:2px solid rgba(93,135,255,.18) !important;
  }
  .sidebar-menu .treeview-menu > li{
    margin:3px 0 !important;
  }
  .sidebar-menu .treeview-menu > li > a{
    display:grid !important;
    grid-template-columns:20px minmax(0,1fr) auto !important;
    align-items:center !important;
    gap:8px !important;
    min-height:34px !important;
    padding:7px 9px !important;
    border-radius:11px !important;
    color:#52647d !important;
    background:transparent !important;
    font-size:12px !important;
    font-weight:750 !important;
    line-height:1.2 !important;
    white-space:normal !important;
  }
  .sidebar-menu .treeview-menu > li > a > i{
    color:#5d87ff !important;
  }
  .skin-blue .sidebar-menu .treeview-menu > li > a:hover,
  .skin-blue .sidebar-menu .treeview-menu > li.active > a{
    color:#174b86 !important;
    background:rgba(236,242,255,.78) !important;
  }
  body.tm-dark-mode .main-sidebar,
  body.tm-dark-mode .skin-blue .main-sidebar,
  body.tm-dark-mode .skin-blue .left-side{
    background:linear-gradient(180deg,rgba(11,18,32,.86),rgba(16,26,46,.72)) !important;
    border-right-color:rgba(43,59,94,.78) !important;
  }
  body.tm-dark-mode .main-sidebar .user-panel,
  body.tm-dark-mode .sidebar-menu > li > a{
    background:rgba(16,26,46,.58) !important;
    border-color:rgba(43,59,94,.74) !important;
  }
  body.tm-dark-mode .main-sidebar .user-panel>.info>p,
  body.tm-dark-mode .sidebar-menu > li > a{
    color:#e5edf7 !important;
  }
  body.tm-dark-mode .main-sidebar .user-panel>.info>a,
  body.tm-dark-mode .sidebar-menu .treeview-menu > li > a{
    color:#aebdda !important;
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .main-sidebar,
    body.sidebar-mini.sidebar-collapse .skin-blue .main-sidebar,
    body.sidebar-mini.sidebar-collapse .skin-blue .left-side{
      width:66px !important;
      padding-top:0 !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .sidebar{
      padding:10px 8px 20px !important;
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand{
      display:flex !important;
      justify-content:center !important;
      width:50px !important;
      min-height:54px !important;
      padding:5px !important;
      margin:0 auto 10px !important;
      border-radius:17px !important;
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-brand-mark{
      width:42px !important;
      height:42px !important;
      flex-basis:42px !important;
      border-radius:14px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-brand-mark img{
      width:38px !important;
      height:38px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand .tm-brand-text{
      display:none !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand:hover .tm-brand-text{
      display:block !important;
      position:absolute !important;
      left:58px !important;
      top:5px !important;
      width:232px !important;
      min-height:48px !important;
      padding:10px 13px !important;
      border:1px solid rgba(223,232,243,.88) !important;
      border-left:0 !important;
      border-radius:0 14px 14px 0 !important;
      background:#fff !important;
      box-shadow:0 18px 34px rgba(23,75,134,.16) !important;
      z-index:50000 !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand:hover .tm-brand-text strong{
      color:#174b86 !important;
      font-size:14px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand:hover .tm-brand-text small{
      color:#61718b !important;
      font-size:9px !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel{
      display:flex !important;
      justify-content:center !important;
      width:50px !important;
      min-height:48px !important;
      padding:5px !important;
      margin:0 auto 10px !important;
      border-color:transparent !important;
      background:transparent !important;
      box-shadow:none !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.info,
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li.header,
    body.sidebar-mini.sidebar-collapse .tm-menu-badge{
      display:none !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image>img{
      width:38px !important;
      height:38px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a{
      display:flex !important;
      justify-content:center !important;
      width:50px !important;
      min-height:48px !important;
      margin:0 auto !important;
      padding:7px !important;
      border-radius:16px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > i:first-child{
      width:34px !important;
      height:34px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right),
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      left:66px !important;
      width:244px !important;
      max-width:244px !important;
      z-index:50000 !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right){
      top:0 !important;
      min-height:48px !important;
      padding:13px 14px !important;
      border-radius:0 14px 0 0 !important;
      background:#fff !important;
      color:#174b86 !important;
      border:1px solid rgba(223,232,243,.90) !important;
      border-left:0 !important;
      box-shadow:0 18px 34px rgba(23,75,134,.16) !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      top:48px !important;
      margin:0 !important;
      padding:5px 0 7px !important;
      border-radius:0 0 14px 0 !important;
      background:#fff !important;
      border:1px solid rgba(223,232,243,.90) !important;
      border-top:0 !important;
      border-left:0 !important;
      box-shadow:0 22px 34px rgba(23,75,134,.14) !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a{
      color:#334155 !important;
      min-height:34px !important;
      padding:8px 13px !important;
      border-radius:0 !important;
      background:transparent !important;
    }
  }

  /* Ajuste visual final: usuario legible y menu con mas aire */
  .main-sidebar,
  .skin-blue .main-sidebar,
  .skin-blue .left-side{
    width:276px !important;
  }
  .main-sidebar .sidebar{
    padding:10px 14px 24px !important;
  }
  .tm-sidebar-brand{
    min-height:68px !important;
    padding:10px 13px !important;
    grid-template-columns:48px minmax(0,1fr) !important;
  }
  .tm-brand-mark{
    width:48px !important;
    height:48px !important;
    flex-basis:48px !important;
  }
  .tm-brand-mark img{
    width:43px !important;
    height:43px !important;
  }
  .tm-brand-text strong{
    font-size:18px !important;
  }
  .tm-brand-text small{
    white-space:normal !important;
    overflow:visible !important;
    text-overflow:clip !important;
    line-height:1.15 !important;
  }
  .main-sidebar .user-panel{
    display:grid !important;
    grid-template-columns:46px minmax(0,1fr) !important;
    align-items:center !important;
    text-align:left !important;
    min-height:74px !important;
    padding:11px 12px !important;
    gap:11px !important;
    overflow:visible !important;
  }
  .main-sidebar .user-panel>.image{
    width:46px !important;
    min-width:46px !important;
    justify-self:start !important;
  }
  .main-sidebar .user-panel>.image>img{
    width:46px !important;
    height:46px !important;
    display:block !important;
  }
  .main-sidebar .user-panel>.info{
    display:flex !important;
    flex-direction:column !important;
    align-items:flex-start !important;
    justify-content:center !important;
    min-width:0 !important;
    width:100% !important;
    overflow:visible !important;
    text-align:left !important;
  }
  .main-sidebar .user-panel>.info>p{
    width:100% !important;
    margin:0 0 5px !important;
    color:#10213d !important;
    font-size:13px !important;
    font-weight:900 !important;
    line-height:1.18 !important;
    white-space:normal !important;
    overflow:visible !important;
    text-overflow:clip !important;
    word-break:normal !important;
  }
  .main-sidebar .user-panel>.info>a{
    display:flex !important;
    align-items:center !important;
    gap:5px !important;
    width:100% !important;
    color:#174b86 !important;
    font-size:10.5px !important;
    font-weight:850 !important;
    line-height:1.2 !important;
    white-space:normal !important;
    overflow:visible !important;
    text-overflow:clip !important;
  }
  .main-sidebar .user-panel>.info>a .text-success{
    flex:0 0 auto !important;
  }
  .sidebar-menu > li > a{
    grid-template-columns:36px minmax(0,1fr) auto auto !important;
    min-height:48px !important;
    padding:8px 10px !important;
    font-size:12.2px !important;
  }
  .sidebar-menu > li > a > i:first-child{
    width:36px !important;
    height:36px !important;
  }
  .sidebar-menu > li > a > span:not(.pull-right-container){
    white-space:normal !important;
    overflow:visible !important;
    text-overflow:clip !important;
    line-height:1.18 !important;
  }
  .sidebar-menu .treeview-menu > li > a{
    font-size:11.8px !important;
    line-height:1.18 !important;
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .main-sidebar,
    body.sidebar-mini.sidebar-collapse .skin-blue .main-sidebar,
    body.sidebar-mini.sidebar-collapse .skin-blue .left-side{
      width:72px !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .sidebar{
      padding:10px 9px 20px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand{
      width:54px !important;
      min-height:56px !important;
      padding:5px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-brand-mark{
      width:44px !important;
      height:44px !important;
      flex-basis:44px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand .tm-brand-text,
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.info,
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li.header,
    body.sidebar-mini.sidebar-collapse .tm-menu-badge{
      display:none !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel{
      display:flex !important;
      width:54px !important;
      min-height:52px !important;
      justify-content:center !important;
      padding:5px !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image,
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image>img{
      width:42px !important;
      height:42px !important;
      min-width:42px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a{
      width:54px !important;
      min-height:50px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right),
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      left:72px !important;
      width:250px !important;
      max-width:250px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      top:50px !important;
    }
  }

  /* Menu compacto sin scroll: todos los modulos visibles */
  .main-sidebar,
  .skin-blue .main-sidebar,
  .skin-blue .left-side{
    width:258px !important;
  }
  .main-sidebar .sidebar{
    height:auto !important;
    min-height:100vh !important;
    padding:8px 10px 10px !important;
    overflow:visible !important;
  }
  .main-sidebar .sidebar::-webkit-scrollbar{
    display:none !important;
  }
  .tm-sidebar-brand{
    min-height:52px !important;
    margin:0 0 7px !important;
    padding:7px 9px !important;
    grid-template-columns:38px minmax(0,1fr) !important;
    gap:9px !important;
    border-radius:15px !important;
  }
  .tm-brand-mark{
    width:38px !important;
    height:38px !important;
    flex-basis:38px !important;
    border-radius:12px !important;
  }
  .tm-brand-mark img{
    width:35px !important;
    height:35px !important;
  }
  .tm-brand-text strong{
    font-size:16px !important;
    line-height:1 !important;
  }
  .tm-brand-text small{
    margin-top:3px !important;
    font-size:8.5px !important;
    line-height:1.1 !important;
    letter-spacing:.09em !important;
  }
  .main-sidebar .user-panel{
    display:grid !important;
    grid-template-columns:34px minmax(0,1fr) !important;
    min-height:50px !important;
    margin:0 0 8px !important;
    padding:7px 8px !important;
    gap:8px !important;
    border-radius:14px !important;
    background:
      linear-gradient(135deg,rgba(255,255,255,.84),rgba(236,242,255,.62)) !important;
    border:1px solid rgba(93,135,255,.18) !important;
    box-shadow:0 10px 22px rgba(23,75,134,.08) !important;
  }
  .main-sidebar .user-panel>.image{
    width:34px !important;
    min-width:34px !important;
  }
  .main-sidebar .user-panel>.image>img{
    width:34px !important;
    height:34px !important;
    border-radius:12px !important;
  }
  .main-sidebar .user-panel>.info{
    min-width:0 !important;
    overflow:hidden !important;
  }
  .main-sidebar .user-panel>.info>p{
    margin:0 0 2px !important;
    font-size:11.5px !important;
    line-height:1.05 !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
  }
  .main-sidebar .user-panel>.info>a{
    display:block !important;
    font-size:9.5px !important;
    line-height:1.12 !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
  }
  .sidebar-menu > li.header{
    margin:8px 7px 5px !important;
    font-size:9px !important;
    line-height:1 !important;
  }
  .sidebar-menu > li{
    margin:3px 0 !important;
  }
  .sidebar-menu > li > a{
    grid-template-columns:30px minmax(0,1fr) auto auto !important;
    min-height:38px !important;
    padding:4px 7px !important;
    gap:7px !important;
    border-radius:12px !important;
    font-size:11px !important;
    line-height:1.08 !important;
  }
  .sidebar-menu > li > a > i:first-child{
    width:30px !important;
    height:30px !important;
    border-radius:10px !important;
    font-size:13px !important;
  }
  .tm-menu-badge{
    min-width:19px !important;
    height:19px !important;
    padding:0 5px !important;
    font-size:9px !important;
  }
  .skin-blue .sidebar-menu > li > .treeview-menu{
    margin:4px 0 5px 13px !important;
    padding:3px 0 3px 8px !important;
  }
  .sidebar-menu .treeview-menu > li{
    margin:1px 0 !important;
  }
  .sidebar-menu .treeview-menu > li > a{
    grid-template-columns:16px minmax(0,1fr) auto !important;
    min-height:27px !important;
    padding:5px 7px !important;
    gap:6px !important;
    border-radius:9px !important;
    font-size:10.5px !important;
    line-height:1.08 !important;
  }
  body.tm-dark-mode .tm-admin-particles{
    opacity:1 !important;
  }
  body.tm-dark-mode .main-sidebar .user-panel{
    background:linear-gradient(135deg,rgba(255,255,255,.12),rgba(93,135,255,.15)) !important;
    border-color:rgba(255,255,255,.20) !important;
  }
  body.tm-dark-mode .main-sidebar .user-panel>.info>p{
    color:#fff !important;
  }
  body.tm-dark-mode .main-sidebar .user-panel>.info>a{
    color:#dbeafe !important;
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .main-sidebar,
    body.sidebar-mini.sidebar-collapse .skin-blue .main-sidebar,
    body.sidebar-mini.sidebar-collapse .skin-blue .left-side{
      width:60px !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .sidebar{
      padding:8px 6px 10px !important;
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-sidebar-brand{
      width:46px !important;
      min-height:46px !important;
      margin:0 auto 6px !important;
      padding:4px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-brand-mark{
      width:36px !important;
      height:36px !important;
      flex-basis:36px !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel{
      width:46px !important;
      min-height:42px !important;
      margin:0 auto 6px !important;
      padding:4px !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image,
    body.sidebar-mini.sidebar-collapse .main-sidebar .user-panel>.image>img{
      width:34px !important;
      height:34px !important;
      min-width:34px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a{
      width:46px !important;
      min-height:40px !important;
      padding:5px !important;
      border-radius:13px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > i:first-child{
      width:30px !important;
      height:30px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right),
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      left:60px !important;
      width:240px !important;
      max-width:240px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right){
      min-height:40px !important;
      padding:10px 12px !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      top:40px !important;
    }
  }

  /* Tarjeta de usuario del menu: reemplaza el user-panel antiguo */
  .tm-menu-user-card{
    display:grid !important;
    grid-template-columns:40px minmax(0,1fr) !important;
    align-items:center !important;
    gap:9px !important;
    min-height:58px !important;
    margin:0 0 8px !important;
    padding:8px 9px !important;
    border-radius:15px !important;
    background:linear-gradient(135deg,rgba(255,255,255,.90),rgba(236,242,255,.70)) !important;
    border:1px solid rgba(93,135,255,.22) !important;
    box-shadow:0 10px 24px rgba(23,75,134,.09) !important;
    overflow:hidden !important;
  }
  .tm-menu-user-avatar{
    position:relative;
    width:40px;
    height:40px;
    min-width:40px;
  }
  .tm-menu-user-avatar img{
    width:40px !important;
    height:40px !important;
    display:block !important;
    object-fit:cover !important;
    border-radius:13px !important;
    border:2px solid rgba(93,135,255,.30) !important;
    background:#fff !important;
  }
  .tm-menu-user-status{
    position:absolute;
    right:-1px;
    bottom:-1px;
    width:10px;
    height:10px;
    border-radius:50%;
    background:#16a34a;
    border:2px solid #fff;
  }
  .tm-menu-user-meta{
    min-width:0;
    display:flex;
    flex-direction:column;
    justify-content:center;
    gap:3px;
  }
  .tm-menu-user-name{
    display:block;
    color:#10213d;
    font-size:12.5px;
    font-weight:900;
    line-height:1.12;
    white-space:normal;
    overflow:visible;
    word-break:normal;
  }
  .tm-menu-user-role{
    display:flex;
    align-items:center;
    gap:5px;
    color:#174b86;
    font-size:9.5px;
    font-weight:850;
    line-height:1.1;
    white-space:normal;
  }
  .tm-menu-user-role i{
    color:#16a34a;
    font-size:8px;
    flex:0 0 auto;
  }
  body.tm-dark-mode .tm-menu-user-card{
    background:linear-gradient(135deg,rgba(255,255,255,.13),rgba(93,135,255,.17)) !important;
    border-color:rgba(255,255,255,.22) !important;
  }
  body.tm-dark-mode .tm-menu-user-name{
    color:#fff !important;
  }
  body.tm-dark-mode .tm-menu-user-role{
    color:#dbeafe !important;
  }
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .tm-menu-user-card{
      display:flex !important;
      justify-content:center !important;
      width:46px !important;
      min-height:44px !important;
      padding:4px !important;
      margin:0 auto 6px !important;
      background:transparent !important;
      border-color:transparent !important;
      box-shadow:none !important;
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-menu-user-avatar,
    body.sidebar-mini.sidebar-collapse .tm-menu-user-avatar img{
      width:34px !important;
      height:34px !important;
      min-width:34px !important;
      border-radius:12px !important;
    }
    body.sidebar-mini.sidebar-collapse .tm-menu-user-status,
    body.sidebar-mini.sidebar-collapse .tm-menu-user-meta{
      display:none !important;
    }
  }

  /* Transparencia suave final del menu lateral */
  body.tm-admin-page:not(.tm-dark-mode) .main-sidebar,
  body.tm-admin-page:not(.tm-dark-mode).skin-blue .main-sidebar,
  body.tm-admin-page:not(.tm-dark-mode).skin-blue .left-side{
    background:rgba(255,255,255,.32) !important;
    border-right-color:rgba(203,213,225,.40) !important;
    backdrop-filter:blur(7px) !important;
    -webkit-backdrop-filter:blur(7px) !important;
  }
  body.tm-admin-page:not(.tm-dark-mode) .tm-sidebar-brand{
    background:linear-gradient(135deg,rgba(23,75,134,.70),rgba(93,135,255,.56)) !important;
  }
  body.tm-admin-page:not(.tm-dark-mode) .tm-menu-user-card,
  body.tm-admin-page:not(.tm-dark-mode) .sidebar-menu > li > a{
    background:rgba(255,255,255,.36) !important;
    border-color:rgba(203,213,225,.40) !important;
  }
  body.tm-admin-page:not(.tm-dark-mode) .skin-blue .sidebar-menu > li:hover > a,
  body.tm-admin-page:not(.tm-dark-mode) .skin-blue .sidebar-menu > li.active > a,
  body.tm-admin-page:not(.tm-dark-mode) .skin-blue .sidebar-menu > li.menu-open > a{
    background:rgba(255,255,255,.66) !important;
  }
  body.tm-dark-mode .main-sidebar,
  body.tm-dark-mode.skin-blue .main-sidebar,
  body.tm-dark-mode.skin-blue .left-side{
    background:rgba(9,20,39,.36) !important;
    border-right-color:rgba(148,163,184,.14) !important;
    backdrop-filter:blur(8px) !important;
    -webkit-backdrop-filter:blur(8px) !important;
  }
  body.tm-dark-mode .tm-sidebar-brand{
    background:linear-gradient(135deg,rgba(23,75,134,.58),rgba(93,135,255,.40)) !important;
  }
  body.tm-dark-mode .tm-menu-user-card,
  body.tm-dark-mode .sidebar-menu > li > a{
    background:rgba(15,27,48,.32) !important;
    border-color:rgba(148,163,184,.12) !important;
  }

  /* Menu plegado: panel flotante clicable y sin espacios muertos */
  @media (min-width:768px){
    body.sidebar-mini.sidebar-collapse .main-sidebar{
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .main-sidebar .sidebar{
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu,
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li{
      overflow:visible !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li{
      position:relative !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a{
      position:relative !important;
      z-index:3 !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > span:not(.pull-right-container),
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li > a > .pull-right-container{
      display:none !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right-container){
      display:flex !important;
      position:absolute !important;
      left:52px !important;
      top:0 !important;
      width:250px !important;
      min-height:40px !important;
      align-items:center !important;
      padding:9px 13px !important;
      border-radius:13px 13px 0 0 !important;
      background:rgba(255,255,255,.96) !important;
      color:#174b86 !important;
      border:1px solid rgba(203,213,225,.72) !important;
      border-bottom:0 !important;
      box-shadow:0 14px 30px rgba(23,75,134,.13) !important;
      font-size:12px !important;
      font-weight:900 !important;
      line-height:1.15 !important;
      white-space:normal !important;
      z-index:10060 !important;
      pointer-events:auto !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu{
      display:block !important;
      position:absolute !important;
      left:52px !important;
      top:39px !important;
      width:250px !important;
      max-width:250px !important;
      min-width:250px !important;
      margin:0 !important;
      padding:7px !important;
      border-radius:0 0 13px 13px !important;
      background:rgba(255,255,255,.96) !important;
      border:1px solid rgba(203,213,225,.72) !important;
      border-top:0 !important;
      box-shadow:0 18px 38px rgba(23,75,134,.15) !important;
      z-index:10050 !important;
      pointer-events:auto !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li.treeview:hover:after,
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li.treeview:focus-within:after{
      content:"" !important;
      position:absolute !important;
      left:42px !important;
      top:0 !important;
      width:18px !important;
      height:280px !important;
      background:transparent !important;
      z-index:10045 !important;
      pointer-events:auto !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:focus-within > a > span:not(.pull-right-container){
      display:flex !important;
      position:absolute !important;
      left:52px !important;
      top:0 !important;
      width:250px !important;
      min-height:40px !important;
      align-items:center !important;
      padding:9px 13px !important;
      border-radius:13px 13px 0 0 !important;
      background:rgba(255,255,255,.96) !important;
      color:#174b86 !important;
      border:1px solid rgba(203,213,225,.72) !important;
      border-bottom:0 !important;
      box-shadow:0 14px 30px rgba(23,75,134,.13) !important;
      font-size:12px !important;
      font-weight:900 !important;
      line-height:1.15 !important;
      white-space:normal !important;
      z-index:10060 !important;
      pointer-events:auto !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:focus-within > .treeview-menu{
      display:block !important;
      position:absolute !important;
      left:52px !important;
      top:39px !important;
      width:250px !important;
      max-width:250px !important;
      min-width:250px !important;
      margin:0 !important;
      padding:7px !important;
      border-radius:0 0 13px 13px !important;
      background:rgba(255,255,255,.96) !important;
      border:1px solid rgba(203,213,225,.72) !important;
      border-top:0 !important;
      box-shadow:0 18px 38px rgba(23,75,134,.15) !important;
      z-index:10050 !important;
      pointer-events:auto !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:focus-within > .treeview-menu > li > a{
      display:grid !important;
      grid-template-columns:18px minmax(0,1fr) auto !important;
      align-items:center !important;
      min-height:32px !important;
      padding:7px 9px !important;
      margin:2px 0 !important;
      border-radius:9px !important;
      color:#334155 !important;
      font-size:11px !important;
      line-height:1.15 !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      pointer-events:auto !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a{
      display:grid !important;
      grid-template-columns:18px minmax(0,1fr) auto !important;
      align-items:center !important;
      min-height:32px !important;
      padding:7px 9px !important;
      margin:2px 0 !important;
      border-radius:9px !important;
      color:#334155 !important;
      font-size:11px !important;
      line-height:1.15 !important;
      white-space:normal !important;
      overflow-wrap:anywhere !important;
      pointer-events:auto !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a:hover{
      background:rgba(236,242,255,.86) !important;
      color:#174b86 !important;
    }
    body.sidebar-mini.sidebar-collapse .sidebar-menu > li:not(.treeview):hover > a > span:not(.pull-right-container){
      border-radius:13px !important;
      border-bottom:1px solid rgba(203,213,225,.72) !important;
    }
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > a > span:not(.pull-right-container),
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu,
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:focus-within > a > span:not(.pull-right-container),
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:focus-within > .treeview-menu{
      background:rgba(15,27,48,.98) !important;
      border-color:rgba(148,163,184,.24) !important;
      color:#eaf2ff !important;
      box-shadow:0 18px 40px rgba(0,0,0,.36) !important;
    }
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a,
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:focus-within > .treeview-menu > li > a{
      color:#dbeafe !important;
    }
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:hover > .treeview-menu > li > a:hover,
    body.tm-dark-mode.sidebar-mini.sidebar-collapse .sidebar-menu > li:focus-within > .treeview-menu > li > a:hover{
      background:rgba(93,135,255,.18) !important;
      color:#fff !important;
    }
  }
</style>

<aside class="main-sidebar">
  <section class="sidebar">
    <a href="inicio" class="tm-sidebar-brand">
      <span class="tm-brand-mark">
        <img src="vistas/img/plantilla/logo.ico" alt="TechMind">
      </span>
      <span class="tm-brand-text">
        <strong>TechMind</strong>
        <small>Panel administrativo</small>
      </span>
    </a>

    <div class="tm-menu-user-card">
      <div class="tm-menu-user-avatar">
        <?php
        if ($_SESSION["foto"] != "") {
          echo '<img src="' . $_SESSION["foto"] . '" alt="Usuario">';
        } else {
          echo '<img src="vistas/img/usuarios/default/anonymous.png" alt="Usuario">';
        }
        ?>
        <span class="tm-menu-user-status"></span>
      </div>
      <div class="tm-menu-user-meta">
        <span class="tm-menu-user-name"><?php echo $_SESSION["nombre"]; ?></span>
        <span class="tm-menu-user-role"><i class="fa fa-circle"></i><?php echo $_SESSION["perfil"]; ?> / <?php echo ucfirst($_SESSION["rol"]); ?></span>
      </div>
    </div>

    <?php if($_SESSION["perfil"] == "Administrador" && $vistaRolMenu != "" && $vistaRolMenu != "administrador"): ?>
      <div class="vista-menu-alert">
        <strong>Vista menu:</strong> <?php echo ucfirst($vistaRolMenu); ?><br>
        <a href="index.php?ruta=inicio&resetVistaRolMenu=1">Volver a administrador</a>
      </div>
    <?php endif; ?>

    <ul class="sidebar-menu tree" data-widget="tree">
      <li class="header">Navegacion</li>

      <?php if ($perfilMenu == "Administrador") : ?>
        <li><a href="inicio"><i class="fa fa-home"></i> <span>Inicio</span></a></li>
        <li class="treeview">
          <a href="#"><i class="fa fa-cogs"></i> <span>Administracion</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <li><a href="usuarios"><i class="fa fa-user"></i> Usuarios</a></li>
            <li><a href="datos-boletas"><i class="fa fa-file-text-o"></i> Datos de boletas</a></li>
            <li><a href="reportes"><i class="fa fa-bar-chart-o"></i> Reportes</a></li>
            <li><a href="logs-sistema"><i class="fa fa-history"></i> Logs del sistema</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor" || $rolMenu == "desarrollador") : ?>
        <li><a href="centro-web"><i class="fa fa-globe"></i> <span>Centro Web</span></a></li>
      <?php endif; ?>
      <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor") : ?>
        <li><a href="consultas-web"><i class="fa fa-comments"></i> <span>Consultas Web</span><?php echo tmMenuBadge($menuBadges["consultas-web"]); ?></a></li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor" || $rolMenu == "cajero") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-money"></i> <span>Gestion de Ventas</span><?php echo tmMenuBadge($menuParentBadges["ventas"]); ?><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <li><a href="ventas"><i class="fa fa-files-o"></i> Administrar Ventas <?php echo tmMenuBadge($menuBadges["ventas"]); ?></a></li>
            <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor") : ?>
              <li><a href="crear-venta"><i class="glyphicon glyphicon-copy"></i> Crear Venta</a></li>
            <?php endif; ?>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-briefcase"></i> <span>Cotizaciones</span><?php echo tmMenuBadge($menuParentBadges["cotizaciones"]); ?><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <li><a href="solicitudes-web"><i class="fa fa-globe"></i> Solicitudes Web <?php echo tmMenuBadge($menuBadges["solicitudes-web"]); ?></a></li>
            <li><a href="cotizacion"><i class="fa fa-files-o"></i> Administrar Cotizaciones</a></li>
            <li><a href="crear-cotizacion"><i class="glyphicon glyphicon-copy"></i> Crear Cotizacion</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor" || $rolMenu == "cajero") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-users"></i> <span>Gestion de Clientes</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <li><a href="clientes"><i class="fa fa-address-book"></i> Administrar Clientes</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "cajero") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-credit-card"></i> <span>Gestion de Cobros</span><?php echo tmMenuBadge($menuParentBadges["cobros"]); ?><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <li><a href="caja"><i class="fa fa-calculator"></i> Control de Caja</a></li>
            <li><a href="pagos-ventas"><i class="fa fa-money"></i> Cobro de Ventas <?php echo tmMenuBadge($menuBadges["pagos-ventas"]); ?></a></li>
            <li><a href="pagos-servicios"><i class="fa fa-wrench"></i> Cobro de Servicios <?php echo tmMenuBadge($menuBadges["pagos-servicios"]); ?></a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor" || $rolMenu == "tecnico" || $rolMenu == "desarrollador") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-wrench"></i> <span>Gestion de Servicios</span><?php echo tmMenuBadge($menuParentBadges["servicios"]); ?><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <?php if ($perfilMenu == "Administrador" || $rolMenu == "vendedor") : ?>
              <li><a href="servicios"><i class="fa fa-plus"></i> Venta de Servicios</a></li>
              <li><a href="administrar-servicios"><i class="fa fa-list"></i> Administrar Servicios <?php echo tmMenuBadge($menuBadges["administrar-servicios"]); ?></a></li>
            <?php endif; ?>
            <?php if ($perfilMenu == "Administrador" || $rolMenu == "tecnico") : ?>
              <li><a href="ordenes-servicio"><i class="fa fa-clipboard"></i> Ordenes de Servicio <?php echo tmMenuBadge($menuBadges["ordenes-servicio"]); ?></a></li>
            <?php endif; ?>
            <?php if ($perfilMenu == "Administrador" || $rolMenu == "desarrollador" || $rolMenu == "vendedor") : ?>
              <li><a href="proyectos"><i class="fa fa-code"></i> Proyectos de Software <?php echo tmMenuBadge($menuBadges["proyectos"]); ?></a></li>
            <?php endif; ?>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "almacen" || $rolMenu == "cajero") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-cubes"></i> <span>Gestion de Almacen</span><?php echo tmMenuBadge($menuParentBadges["almacen"]); ?><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <?php if ($perfilMenu == "Administrador" || $rolMenu == "almacen") : ?>
              <li><a href="categorias"><i class="fa fa-cubes"></i> Categorias</a></li>
              <li><a href="productos-almacen"><i class="fa fa-dropbox"></i> Productos</a></li>
              <?php if ($esAdminRealMenu) : ?>
                <li><a href="ingreso-directo-admin"><i class="fa fa-magic"></i> Ingreso Directo Admin</a></li>
              <?php endif; ?>
              <li><a href="crear-compra-almacen"><i class="fa fa-plus"></i> Crear Solicitud de Compra</a></li>
              <li><a href="solicitudes-de-compra"><i class="fa fa-list"></i> Historial Solicitudes <?php echo tmMenuBadge($menuBadges["solicitudes-de-compra"]); ?></a></li>
              <li><a href="ordenes-ingreso-material"><i class="fa fa-sign-in"></i> Ordenes de Ingreso <?php echo tmMenuBadge($menuBadges["ordenes-ingreso-material"]); ?></a></li>
              <li><a href="recepcion-equipos-taller"><i class="fa fa-laptop"></i> Recepcion Equipos Taller <?php echo tmMenuBadge($menuBadges["recepcion-equipos-taller"]); ?></a></li>
              <li><a href="repuestos-taller-almacen"><i class="fa fa-cubes"></i> Repuestos Taller <?php echo tmMenuBadge($menuBadges["repuestos-taller-almacen"]); ?></a></li>
              <li><a href="despacho"><i class="fa fa-truck"></i> Despacho <?php echo tmMenuBadge($menuBadges["despacho"]); ?></a></li>
            <?php endif; ?>
            <li><a href="proveedor"><i class="fa fa-industry"></i> Proveedores</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "cajero") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-shopping-cart"></i> <span>Gestion de Compras</span><?php echo tmMenuBadge($menuParentBadges["compras"]); ?><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <li><a href="solicitudes-de-compra"><i class="fa fa-list-alt"></i> Solicitudes y Desembolsos <?php echo tmMenuBadge($menuBadges["solicitudes-de-compra"]); ?></a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "cajero") : ?>
        <li class="treeview">
          <a href="#"><i class="fa fa-tag"></i> <span>Gestion de Precios</span><?php echo tmMenuBadge($menuParentBadges["precios"]); ?><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
          <ul class="treeview-menu">
            <li><a href="productos-cajero"><i class="fa fa-cube"></i> Precios de Productos <?php echo tmMenuBadge($menuBadges["productos-cajero"]); ?></a></li>
            <li><a href="precios-servicios"><i class="fa fa-wrench"></i> Precios de Servicios</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($perfilMenu == "Administrador" || $rolMenu == "mensajero") : ?>
        <li><a href="solicitudes-aprobadas"><i class="fa fa-motorcycle"></i> <span>Compras Mensajero</span><?php echo tmMenuBadge($menuBadges["solicitudes-aprobadas"]); ?></a></li>
      <?php endif; ?>
    </ul>
  </section>
</aside>
