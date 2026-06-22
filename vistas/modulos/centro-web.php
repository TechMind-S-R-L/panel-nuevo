<?php
if(!ControladorWebPublicaciones::ctrPuedeAdministrar()){
  echo '<script>window.location="inicio";</script>';
  return;
}

ModeloWebPublicaciones::mdlAsegurarTablas();
ModeloWebConsultas::mdlAsegurarTablas();
ControladorWebPublicaciones::ctrProcesarAcciones();
ControladorWebConsultas::ctrProcesarRespuestasRapidas();

$publicacionesWeb = ModeloWebPublicaciones::mdlMostrarPublicaciones();
$publicidadesModalWeb = ModeloWebPublicaciones::mdlMostrarPublicidadModal();
$mostrarStockWeb = ModeloWebPublicaciones::mdlObtenerConfiguracion("catalogo_mostrar_stock", "1") === "1";
$mostrarPrecioWeb = ModeloWebPublicaciones::mdlObtenerConfiguracion("catalogo_mostrar_precio", "0") === "1";
$whatsappWeb = ModeloWebPublicaciones::mdlObtenerConfiguracion("web_whatsapp", "59168693338");
$telefonoWeb = ModeloWebPublicaciones::mdlObtenerConfiguracion("web_telefono", "68693338");
$prefijoWhatsappWeb = ModeloWebPublicaciones::mdlObtenerConfiguracion("web_whatsapp_prefijo", "591");
$prefijoTelefonoWeb = ModeloWebPublicaciones::mdlObtenerConfiguracion("web_telefono_prefijo", "591");
$correoWeb = ModeloWebPublicaciones::mdlObtenerConfiguracion("web_correo", "techmind.srl.bo@gmail.com");
$respuestasRapidasWeb = ModeloWebConsultas::mdlRespuestasRapidas(false);

function tmCentroWebEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmCentroWebEstado($item){
  return ModeloWebPublicaciones::mdlEstadoVigencia($item);
}

function tmCentroWebEstadoInfo($estado){
  $mapa = array(
    "vigente" => array("Vigente", "success", "fa-check-circle"),
    "programada" => array("Programada", "info", "fa-clock-o"),
    "vencida" => array("Vencida", "danger", "fa-calendar-times-o"),
    "pausada" => array("Pausada", "default", "fa-pause-circle")
  );
  return $mapa[$estado] ?? array(ucfirst($estado), "default", "fa-circle-o");
}

function tmCentroWebData($datos){
  return base64_encode(json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function tmCentroWebNumeroLocal($numero, $prefijo){
  $numero = preg_replace('/\D+/', '', (string)$numero);
  $prefijo = preg_replace('/\D+/', '', (string)$prefijo);
  return $prefijo !== "" && strpos($numero, $prefijo) === 0 ? substr($numero, strlen($prefijo)) : $numero;
}

$paisesContactoWeb = array(
  "591"=>"🇧🇴 BO (+591)", "54"=>"🇦🇷 AR (+54)", "55"=>"🇧🇷 BR (+55)",
  "56"=>"🇨🇱 CL (+56)", "57"=>"🇨🇴 CO (+57)", "593"=>"🇪🇨 EC (+593)",
  "51"=>"🇵🇪 PE (+51)", "595"=>"🇵🇾 PY (+595)", "598"=>"🇺🇾 UY (+598)",
  "58"=>"🇻🇪 VE (+58)", "52"=>"🇲🇽 MX (+52)", "1"=>"🇺🇸 US (+1)",
  "34"=>"🇪🇸 ES (+34)", "506"=>"🇨🇷 CR (+506)", "507"=>"🇵🇦 PA (+507)",
  "502"=>"🇬🇹 GT (+502)", "503"=>"🇸🇻 SV (+503)", "504"=>"🇭🇳 HN (+504)",
  "505"=>"🇳🇮 NI (+505)", "53"=>"🇨🇺 CU (+53)", "1809"=>"🇩🇴 DO (+1 809)"
);
$numeroWhatsappWeb = tmCentroWebNumeroLocal($whatsappWeb, $prefijoWhatsappWeb);
$numeroTelefonoWeb = tmCentroWebNumeroLocal($telefonoWeb, $prefijoTelefonoWeb);

$publicidadModalActiva = ModeloWebPublicaciones::mdlPublicidadModalActiva();
$publicacionesVigentes = 0;
$publicacionesProgramadas = 0;
$publicacionesVencidas = 0;
foreach($publicacionesWeb as $publicacionWeb){
  $estadoPublicacion = tmCentroWebEstado($publicacionWeb);
  if($estadoPublicacion === "vigente"){ $publicacionesVigentes++; }
  if($estadoPublicacion === "programada"){ $publicacionesProgramadas++; }
  if($estadoPublicacion === "vencida"){ $publicacionesVencidas++; }
}
$campanasVigentes = count(array_filter($publicidadesModalWeb, function($campana){
  return tmCentroWebEstado($campana) === "vigente";
}));
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
<div class="content-wrapper tm-web-page">
<style>
.tm-web-page{background:transparent!important}.tm-web-page .content{padding-top:10px}.tm-web-shell{font-family:"Segoe UI",Arial,sans-serif;color:#17314c}
.tm-web-hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;padding:22px 24px;border-radius:22px;color:#fff;background:linear-gradient(135deg,#102d43,#176a9c 62%,#28acd8);box-shadow:0 20px 45px rgba(18,75,116,.2)}
.tm-web-hero:after{content:"";position:absolute;right:-70px;top:-100px;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,.12)}.tm-web-hero>div{position:relative;z-index:1}
.tm-web-kicker{display:inline-flex;gap:7px;align-items:center;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;color:#bfeaff}.tm-web-hero h1{margin:5px 0;font-size:29px;font-weight:950}.tm-web-hero p{margin:0;max-width:760px;color:rgba(255,255,255,.87);font-weight:700}
.tm-web-hero-actions{display:flex;gap:8px;flex-wrap:wrap}.tm-web-hero .btn{border:0;border-radius:12px;padding:10px 14px;font-weight:900}.tm-web-hero .btn-primary{background:#fff;color:#176a9c}.tm-web-hero .btn-info{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25)}
.tm-web-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px;margin:14px 0}.tm-web-kpi{display:flex;align-items:center;gap:11px;padding:14px;border:1px solid rgba(173,205,230,.65);border-radius:17px;background:rgba(255,255,255,.78);box-shadow:0 11px 26px rgba(26,78,119,.07)}
.tm-web-kpi i{width:42px;height:42px;display:flex;align-items:center;justify-content:center;border-radius:13px;color:#fff;background:linear-gradient(135deg,#176b9b,#29b0dc);font-size:18px}.tm-web-kpi span{display:block;color:#71859a;font-size:9px;font-weight:950;text-transform:uppercase}.tm-web-kpi strong{display:block;margin-top:2px;color:#172d45;font-size:20px;font-weight:950}
.tm-web-nav{display:flex;gap:7px;flex-wrap:wrap;padding:9px;margin-bottom:13px;border:1px solid rgba(173,205,230,.65);border-radius:17px;background:rgba(255,255,255,.75)}.tm-web-nav>li{float:none;margin:0}.tm-web-nav>li>a{border:0!important;border-radius:11px!important;padding:9px 13px;color:#526b84;font-size:12px;font-weight:900}.tm-web-nav>li.active>a,.tm-web-nav>li>a:hover{color:#fff!important;background:linear-gradient(135deg,#176b9b,#29a9d6)!important}
.tm-web-panel{padding:15px;border:1px solid rgba(173,205,230,.68);border-radius:18px;background:rgba(255,255,255,.8);box-shadow:0 14px 32px rgba(26,78,119,.07)}.tm-web-section-head{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:13px}.tm-web-section-head h2{margin:0 0 3px;font-size:19px;font-weight:950;color:#172d45}.tm-web-section-head p{margin:0;color:#6b8197;font-size:12px;font-weight:700}.tm-web-section-head .btn{border-radius:10px;font-weight:900}
.tm-web-ad-feature{display:grid;grid-template-columns:minmax(270px,420px) 1fr;gap:14px;margin-bottom:14px}.tm-web-ad-preview{min-height:240px;border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#e8f3fb,#f8fbfe);display:flex;align-items:center;justify-content:center}.tm-web-ad-preview img{width:100%;height:100%;object-fit:cover}.tm-web-ad-preview i{font-size:46px;color:#67a5ca}
.tm-web-ad-info{padding:16px;border:1px solid #dceaf5;border-radius:16px;background:#f9fcff}.tm-web-ad-info h3{margin:8px 0 6px;color:#172d45;font-weight:950}.tm-web-ad-info p{color:#647b92}.tm-web-status{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:950;text-transform:uppercase}.tm-web-status.success{color:#087550;background:#e7f8f0}.tm-web-status.info{color:#126b9b;background:#e8f5fc}.tm-web-status.danger{color:#b9413a;background:#ffebe9}.tm-web-status.default{color:#64748b;background:#eef2f6}
.tm-web-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:12px 0}.tm-web-meta div{padding:9px;border:1px solid #e0ebf4;border-radius:11px;background:#fff}.tm-web-meta span{display:block;color:#8292a4;font-size:9px;font-weight:900;text-transform:uppercase}.tm-web-meta strong{display:block;margin-top:3px;color:#243b53;font-size:12px}.tm-web-actions{display:flex;gap:7px;flex-wrap:wrap}.tm-web-actions .btn{border-radius:9px;font-weight:850}
.tm-web-table-wrap{overflow:auto;border:1px solid #dce8f2;border-radius:14px}.tm-web-table{margin:0}.tm-web-table th{white-space:nowrap;color:#62798f;font-size:10px;text-transform:uppercase}.tm-web-table td{vertical-align:middle!important;color:#2b4056;font-size:12px}.tm-web-thumb{width:58px;height:45px;border-radius:9px;object-fit:cover;background:#edf4fa}
.tm-web-publications{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:12px}.tm-web-pub{overflow:hidden;border:1px solid #dce9f4;border-radius:16px;background:#fff;box-shadow:0 10px 24px rgba(26,78,119,.06)}.tm-web-pub-image{height:145px;background:linear-gradient(135deg,#e8f3fb,#f8fbfe);display:flex;align-items:center;justify-content:center}.tm-web-pub-image img{width:100%;height:100%;object-fit:cover}.tm-web-pub-image i{font-size:38px;color:#5fa5cf}.tm-web-pub-body{padding:14px}.tm-web-pub-top{display:flex;justify-content:space-between;gap:8px}.tm-web-type{font-size:10px;font-weight:950;text-transform:uppercase;color:#176b9b}.tm-web-pub h3{margin:8px 0 5px;font-size:16px;font-weight:950;color:#172d45}.tm-web-pub p{min-height:38px;color:#667d93;font-size:12px}
.tm-web-config-list{display:grid;gap:11px}.tm-web-config{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;padding:18px;border:1px solid #d8e8f4;border-radius:17px;background:linear-gradient(135deg,#f6fbff,#fff)}.tm-web-config-icon{width:48px;height:48px;display:flex;align-items:center;justify-content:center;border-radius:15px;color:#fff;background:linear-gradient(135deg,#176b9b,#2fb2dd);font-size:21px;float:left;margin-right:13px}.tm-web-config h3{margin:2px 0 5px;font-weight:950;color:#172d45}.tm-web-config p{margin:0;color:#697f94;font-size:12px}.tm-switch{display:flex;align-items:center;gap:10px;padding:10px 13px;border-radius:13px;background:#fff;border:1px solid #dce9f3;font-weight:900;white-space:nowrap}.tm-switch input{width:20px;height:20px;margin:0}.tm-web-save{margin-top:12px;text-align:right}.tm-web-save .btn{border-radius:11px;padding:9px 16px;font-weight:900}
.tm-web-modal .modal-content{border:0;border-radius:19px;overflow:hidden}.tm-web-modal .modal-header{padding:16px 20px;color:#fff;background:linear-gradient(135deg,#12384f,#249dce)}.tm-web-modal .modal-header h4{margin:0;font-weight:950}.tm-web-modal .modal-body{padding:20px;background:#f7fbfe}.tm-web-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.tm-web-form-grid .full{grid-column:1/-1}.tm-web-form-grid label{color:#526b82;font-size:10px;font-weight:950;text-transform:uppercase}.tm-web-form-grid .form-control{border-radius:10px;border-color:#d8e6f1;box-shadow:none}.tm-web-checks{padding:11px;border-radius:11px;background:#eaf5fb;display:flex;gap:16px;flex-wrap:wrap}
.tm-phone-control{display:grid;grid-template-columns:minmax(135px,.55fr) minmax(150px,1fr);gap:7px}.tm-phone-control select{display:none}.tm-phone-control input{height:39px}.tm-country-picker{position:relative}.tm-country-trigger{width:100%;height:39px;display:flex;align-items:center;gap:7px;padding:0 10px;border:1px solid #d8e6f1;border-radius:10px;color:#29455f;background:#f7fbfe;font-weight:800;text-align:left}.tm-country-trigger img,.tm-country-option img{width:22px;height:16px;border-radius:2px;object-fit:cover;box-shadow:0 0 0 1px rgba(0,0,0,.08)}.tm-country-trigger i{margin-left:auto;color:#72879a}.tm-country-menu{position:absolute;z-index:30;top:43px;left:0;width:210px;max-height:235px;overflow:auto;display:none;padding:6px;border:1px solid #d5e4ef;border-radius:12px;background:#fff;box-shadow:0 16px 35px rgba(25,70,105,.18)}.tm-country-picker.open .tm-country-menu{display:block}.tm-country-option{width:100%;display:flex;align-items:center;gap:8px;padding:7px 8px;border:0;border-radius:8px;color:#365069;background:transparent;text-align:left;font-size:11px;font-weight:800}.tm-country-option:hover,.tm-country-option.active{color:#1677a3;background:#eaf7fc}.tm-phone-preview{display:inline-flex;gap:5px;align-items:center;margin-top:5px;color:#6d8397;font-size:10px}.tm-phone-preview strong{color:#2385b2}
.tm-icon-picker{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px;padding:9px;border:1px solid #d8e6f1;border-radius:13px;background:#fff}.tm-icon-option{display:flex;align-items:center;gap:7px;min-height:43px;padding:7px 8px;border:1px solid #e0eaf2;border-radius:10px;color:#536b81;background:#f8fbfd;text-align:left;font-size:10px;font-weight:850;transition:.18s ease}.tm-icon-option i{display:flex;align-items:center;justify-content:center;width:27px;height:27px;border-radius:8px;color:#2389b7;background:#e8f6fc;font-size:16px}.tm-icon-option:hover,.tm-icon-option.active{border-color:#2799c8;color:#176b96;background:#eef9fd;box-shadow:0 5px 13px rgba(34,132,175,.12)}.tm-icon-option.active i{color:#fff;background:linear-gradient(135deg,#1877a6,#2eb0dc)}
.tm-web-empty{grid-column:1/-1;padding:45px;text-align:center;color:#72869a;border:1px dashed #c8ddec;border-radius:15px;background:#f9fcfe}
body.tm-dark-mode .tm-web-panel,body.tm-dark-mode .tm-web-kpi,body.tm-dark-mode .tm-web-nav,body.tm-dark-mode .tm-web-pub{background:rgba(15,29,50,.78);border-color:rgba(105,158,204,.25)}body.tm-dark-mode .tm-web-section-head h2,body.tm-dark-mode .tm-web-kpi strong,body.tm-dark-mode .tm-web-pub h3{color:#fff}
@media(max-width:900px){.tm-web-hero,.tm-web-ad-feature,.tm-web-config{grid-template-columns:1fr}.tm-web-kpis{grid-template-columns:repeat(2,1fr)}.tm-web-form-grid{grid-template-columns:1fr}.tm-web-form-grid .full{grid-column:auto}.tm-icon-picker{grid-template-columns:repeat(3,1fr)}}@media(max-width:540px){.tm-web-kpis{grid-template-columns:1fr}.tm-web-center{padding:10px}.tm-icon-picker{grid-template-columns:repeat(2,1fr)}}
</style>

<section class="content-header">
  <h1>Centro Web</h1>
  <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li><li class="active">Centro Web</li></ol>
</section>

<section class="content tm-web-center">
  <div class="tm-web-shell">
    <div class="tm-web-hero">
      <div>
        <span class="tm-web-kicker"><i class="fa fa-globe"></i> Administracion de la pagina web</span>
        <h1>Centro Web</h1>
        <p>Programa campañas, comunica novedades y controla cómo se presenta el catálogo público desde un solo lugar.</p>
      </div>
      <div class="tm-web-hero-actions">
        <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaPublicidadWeb"><i class="fa fa-image"></i> Nueva publicidad</button>
        <button class="btn btn-info" data-toggle="modal" data-target="#modalNuevaPublicacionWeb"><i class="fa fa-bullhorn"></i> Nueva publicacion</button>
      </div>
    </div>

    <div class="tm-web-kpis">
      <div class="tm-web-kpi"><i class="fa fa-image"></i><div><span>Publicidad vigente</span><strong><?php echo $campanasVigentes; ?></strong></div></div>
      <div class="tm-web-kpi"><i class="fa fa-bell"></i><div><span>Publicaciones vigentes</span><strong><?php echo $publicacionesVigentes; ?></strong></div></div>
      <div class="tm-web-kpi"><i class="fa fa-clock-o"></i><div><span>Programadas</span><strong><?php echo $publicacionesProgramadas; ?></strong></div></div>
      <div class="tm-web-kpi"><i class="fa fa-cubes"></i><div><span>Stock en catalogo</span><strong><?php echo $mostrarStockWeb ? "Visible" : "Oculto"; ?></strong></div></div>
    </div>

    <ul class="nav nav-tabs tm-web-nav">
      <li class="active"><a href="#webPublicidad" data-toggle="tab"><i class="fa fa-picture-o"></i> Publicidad de inicio</a></li>
      <li><a href="#webPublicaciones" data-toggle="tab"><i class="fa fa-bell-o"></i> Novedades y ofertas</a></li>
      <li><a href="#webCatalogo" data-toggle="tab"><i class="fa fa-shopping-bag"></i> Configuracion del catalogo</a></li>
      <li><a href="#webContacto" data-toggle="tab"><i class="fa fa-address-book-o"></i> Datos de contacto</a></li>
      <li><a href="#webConsultas" data-toggle="tab"><i class="fa fa-comments-o"></i> Consultas y mensajes</a></li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane active" id="webPublicidad">
        <div class="tm-web-panel">
          <div class="tm-web-section-head">
            <div><h2>Publicidad de bienvenida</h2><p>Solo se muestra cuando está activa y dentro de su periodo de vigencia.</p></div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaPublicidadWeb"><i class="fa fa-plus"></i> Crear campaña</button>
          </div>

          <div class="tm-web-ad-feature">
            <div class="tm-web-ad-preview">
              <?php if($publicidadModalActiva): ?><img src="<?php echo tmCentroWebEsc($publicidadModalActiva["imagen"]); ?>" alt="Publicidad vigente"><?php else: ?><i class="fa fa-picture-o"></i><?php endif; ?>
            </div>
            <div class="tm-web-ad-info">
              <?php if($publicidadModalActiva): $estadoActivoInfo=tmCentroWebEstadoInfo("vigente"); ?>
                <span class="tm-web-status <?php echo $estadoActivoInfo[1]; ?>"><i class="fa <?php echo $estadoActivoInfo[2]; ?>"></i><?php echo $estadoActivoInfo[0]; ?></span>
                <h3><?php echo tmCentroWebEsc($publicidadModalActiva["titulo"] ?: "Publicidad visual"); ?></h3>
                <p><?php echo nl2br(tmCentroWebEsc($publicidadModalActiva["texto"] ?: "Imagen promocional visible en el inicio.")); ?></p>
                <div class="tm-web-meta">
                  <div><span>Inicio</span><strong><?php echo date("d/m/Y H:i",strtotime($publicidadModalActiva["fecha_inicio"])); ?></strong></div>
                  <div><span>Finalizacion</span><strong><?php echo $publicidadModalActiva["fecha_fin"] ? date("d/m/Y H:i",strtotime($publicidadModalActiva["fecha_fin"])) : "Sin vencimiento"; ?></strong></div>
                </div>
                <?php $dataPublicidadActiva = tmCentroWebData(array(
                  "id"=>(int)$publicidadModalActiva["id"], "titulo"=>$publicidadModalActiva["titulo"], "texto"=>$publicidadModalActiva["texto"],
                  "imagen"=>$publicidadModalActiva["imagen"], "enlace"=>$publicidadModalActiva["enlace"], "texto_boton"=>$publicidadModalActiva["texto_boton"],
                  "estado"=>(int)$publicidadModalActiva["estado"], "fecha_inicio"=>date("Y-m-d\TH:i",strtotime($publicidadModalActiva["fecha_inicio"])),
                  "fecha_fin"=>$publicidadModalActiva["fecha_fin"]?date("Y-m-d\TH:i",strtotime($publicidadModalActiva["fecha_fin"])):"", "vencida"=>false
                )); ?>
                <div class="tm-web-actions">
                  <button type="button" class="btn btn-primary btnEditarPublicidadWeb" data-contenido="<?php echo tmCentroWebEsc($dataPublicidadActiva); ?>"><i class="fa fa-pencil"></i> Editar</button>
                  <a class="btn btn-warning" href="index.php?ruta=centro-web&idPublicidadModalWeb=<?php echo (int)$publicidadModalActiva["id"]; ?>&estadoPublicidadModalWeb=0"><i class="fa fa-pause"></i> Pausar</a>
                </div>
              <?php else: ?>
                <span class="tm-web-status default"><i class="fa fa-eye-slash"></i> Sin publicidad visible</span>
                <h3>No hay una campaña vigente</h3>
                <p>Las campañas vencidas se desactivan automáticamente. Puedes crear una nueva o activar una campaña programada.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="tm-web-table-wrap">
            <table class="table table-hover tm-web-table">
              <thead><tr><th>Vista</th><th>Campaña</th><th>Vigencia</th><th>Estado real</th><th>Acciones</th></tr></thead>
              <tbody>
              <?php if(!$publicidadesModalWeb): ?><tr><td colspan="5" class="text-center">Todavía no existen campañas.</td></tr><?php endif; ?>
              <?php foreach($publicidadesModalWeb as $campana): $estadoCampana=tmCentroWebEstado($campana); $estadoCampanaInfo=tmCentroWebEstadoInfo($estadoCampana);
                $dataCampana = tmCentroWebData(array(
                  "id"=>(int)$campana["id"], "titulo"=>$campana["titulo"], "texto"=>$campana["texto"], "imagen"=>$campana["imagen"],
                  "enlace"=>$campana["enlace"], "texto_boton"=>$campana["texto_boton"], "estado"=>(int)$campana["estado"],
                  "fecha_inicio"=>date("Y-m-d\TH:i",strtotime($campana["fecha_inicio"])),
                  "fecha_fin"=>$campana["fecha_fin"]?date("Y-m-d\TH:i",strtotime($campana["fecha_fin"])):"", "vencida"=>$estadoCampana==="vencida"
                ));
              ?>
                <tr>
                  <td><img class="tm-web-thumb" src="<?php echo tmCentroWebEsc($campana["imagen"]); ?>" alt=""></td>
                  <td><strong><?php echo tmCentroWebEsc($campana["titulo"] ?: "Publicidad visual"); ?></strong><br><small><?php echo tmCentroWebEsc($campana["texto_boton"] ?: "Sin boton"); ?></small></td>
                  <td><?php echo date("d/m/Y H:i",strtotime($campana["fecha_inicio"])); ?><br><small><?php echo $campana["fecha_fin"] ? "Hasta ".date("d/m/Y H:i",strtotime($campana["fecha_fin"])) : "Sin vencimiento"; ?></small></td>
                  <td><span class="tm-web-status <?php echo $estadoCampanaInfo[1]; ?>"><i class="fa <?php echo $estadoCampanaInfo[2]; ?>"></i><?php echo $estadoCampanaInfo[0]; ?></span></td>
                  <td>
                    <div class="tm-web-actions">
                      <button type="button" class="btn btn-xs btn-primary btnEditarPublicidadWeb" data-contenido="<?php echo tmCentroWebEsc($dataCampana); ?>" title="Editar campaña"><i class="fa fa-pencil"></i></button>
                      <?php if($estadoCampana !== "vencida"): ?><a class="btn btn-xs btn-<?php echo (int)$campana["estado"]===1?"warning":"success"; ?>" href="index.php?ruta=centro-web&idPublicidadModalWeb=<?php echo (int)$campana["id"]; ?>&estadoPublicidadModalWeb=<?php echo (int)$campana["estado"]===1?0:1; ?>"><i class="fa fa-<?php echo (int)$campana["estado"]===1?"pause":"play"; ?>"></i></a><?php endif; ?>
                      <button type="button" class="btn btn-xs btn-danger btnEliminarContenidoWeb" data-url="index.php?ruta=centro-web&eliminarPublicidadModalWeb=<?php echo (int)$campana["id"]; ?>" data-titulo="Eliminar campaña"><i class="fa fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="tab-pane" id="webPublicaciones">
        <div class="tm-web-panel">
          <div class="tm-web-section-head">
            <div><h2>Novedades, ofertas y avisos</h2><p>Contenido enviado a la campana de clientes registrados según audiencia y vigencia.</p></div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaPublicacionWeb"><i class="fa fa-plus"></i> Nueva publicacion</button>
          </div>
          <div class="tm-web-publications">
            <?php if(!$publicacionesWeb): ?><div class="tm-web-empty"><i class="fa fa-bell-o fa-3x"></i><h3>Sin publicaciones</h3><p>Crea la primera comunicación para tus clientes.</p></div><?php endif; ?>
            <?php foreach($publicacionesWeb as $pub): $estadoPub=tmCentroWebEstado($pub); $estadoPubInfo=tmCentroWebEstadoInfo($estadoPub);
              $dataPublicacion = tmCentroWebData(array(
                "id"=>(int)$pub["id"], "titulo"=>$pub["titulo"], "resumen"=>$pub["resumen"], "tipo"=>$pub["tipo"], "imagen"=>$pub["imagen"],
                "enlace"=>$pub["enlace"], "texto_boton"=>$pub["texto_boton"], "audiencia"=>$pub["audiencia"],
                "destacada"=>(int)$pub["destacada"], "estado"=>(int)$pub["estado"],
                "fecha_inicio"=>date("Y-m-d\TH:i",strtotime($pub["fecha_inicio"])),
                "fecha_fin"=>$pub["fecha_fin"]?date("Y-m-d\TH:i",strtotime($pub["fecha_fin"])):"", "vencida"=>$estadoPub==="vencida"
              ));
            ?>
              <article class="tm-web-pub">
                <div class="tm-web-pub-image"><?php if($pub["imagen"]): ?><img src="<?php echo tmCentroWebEsc($pub["imagen"]); ?>" alt=""><?php else: ?><i class="fa <?php echo $pub["tipo"]==="oferta"?"fa-tag":($pub["tipo"]==="aviso"?"fa-info-circle":"fa-bullhorn"); ?>"></i><?php endif; ?></div>
                <div class="tm-web-pub-body">
                  <div class="tm-web-pub-top"><span class="tm-web-type"><?php echo tmCentroWebEsc($pub["tipo"]); ?></span><span class="tm-web-status <?php echo $estadoPubInfo[1]; ?>"><?php echo $estadoPubInfo[0]; ?></span></div>
                  <h3><?php echo tmCentroWebEsc($pub["titulo"]); ?></h3>
                  <p><?php echo nl2br(tmCentroWebEsc($pub["resumen"])); ?></p>
                  <div class="tm-web-meta"><div><span>Audiencia</span><strong><?php echo tmCentroWebEsc(ucfirst(str_replace("_"," ",$pub["audiencia"]))); ?></strong></div><div><span>Finaliza</span><strong><?php echo $pub["fecha_fin"] ? date("d/m/Y H:i",strtotime($pub["fecha_fin"])) : "Sin vencimiento"; ?></strong></div></div>
                  <div class="tm-web-actions">
                    <button type="button" class="btn btn-primary btnEditarPublicacionWeb" data-contenido="<?php echo tmCentroWebEsc($dataPublicacion); ?>"><i class="fa fa-pencil"></i> Editar</button>
                    <?php if($estadoPub !== "vencida"): ?><a class="btn btn-<?php echo (int)$pub["estado"]===1?"warning":"success"; ?>" href="index.php?ruta=centro-web&idPublicacionWeb=<?php echo (int)$pub["id"]; ?>&estadoPublicacionWeb=<?php echo (int)$pub["estado"]===1?0:1; ?>"><i class="fa fa-<?php echo (int)$pub["estado"]===1?"pause":"play"; ?>"></i> <?php echo (int)$pub["estado"]===1?"Pausar":"Activar"; ?></a><?php endif; ?>
                    <button type="button" class="btn btn-danger btnEliminarContenidoWeb" data-url="index.php?ruta=centro-web&eliminarPublicacionWeb=<?php echo (int)$pub["id"]; ?>" data-titulo="Eliminar publicacion"><i class="fa fa-trash"></i> Eliminar</button>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="tab-pane" id="webCatalogo">
        <div class="tm-web-panel">
          <div class="tm-web-section-head"><div><h2>Presentacion del catálogo</h2><p>Decide qué información comercial pueden ver los visitantes.</p></div></div>
          <form method="post">
            <input type="hidden" name="guardarConfiguracionCatalogoWeb" value="1">
            <div class="tm-web-config-list">
            <div class="tm-web-config">
              <div>
                <span class="tm-web-config-icon"><i class="fa fa-cubes"></i></span>
                <h3>Mostrar cantidad de stock</h3>
                <p>Al desactivarlo, las tarjetas mostrarán “Consultar disponibilidad”. El stock seguirá utilizándose internamente para solicitudes y control de inventario.</p>
              </div>
              <label class="tm-switch"><input type="checkbox" name="catalogoMostrarStockWeb" value="1" <?php echo $mostrarStockWeb?"checked":""; ?>> <span><?php echo $mostrarStockWeb?"Visible":"Oculto"; ?></span></label>
            </div>
            <div class="tm-web-config">
              <div>
                <span class="tm-web-config-icon"><i class="fa fa-money"></i></span>
                <h3>Mostrar precio de venta</h3>
                <p>Muestra el precio registrado en las tarjetas y el detalle. Los productos sin monto indicarán “Consultar precio”.</p>
              </div>
              <label class="tm-switch"><input type="checkbox" name="catalogoMostrarPrecioWeb" value="1" <?php echo $mostrarPrecioWeb?"checked":""; ?>> <span><?php echo $mostrarPrecioWeb?"Visible":"Oculto"; ?></span></label>
            </div>
            </div>
            <div class="tm-web-save"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar configuracion</button></div>
          </form>
        </div>
      </div>

      <div class="tab-pane" id="webContacto">
        <div class="tm-web-panel">
          <div class="tm-web-section-head">
            <div><h2>Contacto de la página web</h2><p>Estos datos alimentan los botones de WhatsApp, llamadas y correo del inicio y servicios.</p></div>
          </div>
          <form method="post">
            <input type="hidden" name="guardarContactoWeb" value="1">
            <div class="tm-web-form-grid">
              <div>
                <label>Número de WhatsApp</label>
                <div class="tm-phone-control">
                  <select class="form-control tm-country-prefix" name="webWhatsappPrefijo" aria-label="País para WhatsApp">
                    <?php foreach($paisesContactoWeb as $prefijoPais=>$nombrePais): ?><option value="<?php echo tmCentroWebEsc($prefijoPais); ?>" <?php echo (string)$prefijoWhatsappWeb===(string)$prefijoPais?"selected":""; ?>><?php echo tmCentroWebEsc($nombrePais); ?></option><?php endforeach; ?>
                  </select>
                  <div class="input-group"><span class="input-group-addon"><i class="fa fa-whatsapp"></i></span><input class="form-control tm-local-phone" name="webWhatsapp" value="<?php echo tmCentroWebEsc($numeroWhatsappWeb); ?>" inputmode="numeric" maxlength="12" placeholder="Número local" required></div>
                </div>
                <small class="tm-phone-preview">Número completo: <strong data-phone-preview><?php echo "+".tmCentroWebEsc($prefijoWhatsappWeb.$numeroWhatsappWeb); ?></strong></small>
              </div>
              <div>
                <label>Número para llamadas</label>
                <div class="tm-phone-control">
                  <select class="form-control tm-country-prefix" name="webTelefonoPrefijo" aria-label="País para llamadas">
                    <?php foreach($paisesContactoWeb as $prefijoPais=>$nombrePais): ?><option value="<?php echo tmCentroWebEsc($prefijoPais); ?>" <?php echo (string)$prefijoTelefonoWeb===(string)$prefijoPais?"selected":""; ?>><?php echo tmCentroWebEsc($nombrePais); ?></option><?php endforeach; ?>
                  </select>
                  <div class="input-group"><span class="input-group-addon"><i class="fa fa-phone"></i></span><input class="form-control tm-local-phone" name="webTelefono" value="<?php echo tmCentroWebEsc($numeroTelefonoWeb); ?>" inputmode="numeric" maxlength="12" placeholder="Número local" required></div>
                </div>
                <small class="tm-phone-preview">Número completo: <strong data-phone-preview><?php echo "+".tmCentroWebEsc($prefijoTelefonoWeb.$numeroTelefonoWeb); ?></strong></small>
              </div>
              <div class="full">
                <label>Correo electrónico público</label>
                <div class="input-group"><span class="input-group-addon"><i class="fa fa-envelope"></i></span><input class="form-control" type="email" name="webCorreo" value="<?php echo tmCentroWebEsc($correoWeb); ?>" maxlength="180" required></div>
                <small>Se abrirá al presionar el botón Email de la página web.</small>
              </div>
            </div>
            <div class="tm-web-save"><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar datos de contacto</button></div>
          </form>
        </div>
      </div>

      <div class="tab-pane" id="webConsultas">
        <div class="tm-web-panel">
          <div class="tm-web-section-head">
            <div><h2>Mensajes rápidos de Consultas</h2><p>Son las opciones estilo chatbot que ayudan al cliente a iniciar una conversación con Ventas.</p></div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalNuevaRespuestaRapidaWeb"><i class="fa fa-plus"></i> Nuevo mensaje</button>
          </div>
          <div class="tm-web-publications">
            <?php foreach($respuestasRapidasWeb as $respuestaRapida):
              $dataRespuestaRapida=tmCentroWebData(array("id"=>(int)$respuestaRapida["id"],"titulo"=>$respuestaRapida["titulo"],"mensaje"=>$respuestaRapida["mensaje"],"icono"=>$respuestaRapida["icono"],"orden"=>(int)$respuestaRapida["orden"],"estado"=>(int)$respuestaRapida["estado"]));
            ?>
              <article class="tm-web-pub">
                <div class="tm-web-pub-body">
                  <div class="tm-web-pub-top"><span class="tm-web-type"><i class="fa fa-commenting-o"></i> Opción rápida</span><span class="tm-web-status <?php echo (int)$respuestaRapida["estado"]===1?"success":"default"; ?>"><?php echo (int)$respuestaRapida["estado"]===1?"Visible":"Oculta"; ?></span></div>
                  <h3><?php echo tmCentroWebEsc($respuestaRapida["titulo"]); ?></h3>
                  <p><?php echo tmCentroWebEsc($respuestaRapida["mensaje"]); ?></p>
                  <div class="tm-web-meta"><div><span>Icono</span><strong><?php echo tmCentroWebEsc($respuestaRapida["icono"]); ?></strong></div><div><span>Orden</span><strong><?php echo (int)$respuestaRapida["orden"]; ?></strong></div></div>
                  <div class="tm-web-actions">
                    <button class="btn btn-primary btnEditarRespuestaRapidaWeb" type="button" data-contenido="<?php echo tmCentroWebEsc($dataRespuestaRapida); ?>"><i class="fa fa-pencil"></i> Editar</button>
                    <a class="btn btn-<?php echo (int)$respuestaRapida["estado"]===1?"warning":"success"; ?>" href="index.php?ruta=centro-web&idRespuestaRapidaWeb=<?php echo (int)$respuestaRapida["id"]; ?>&estadoRespuestaRapidaWeb=<?php echo (int)$respuestaRapida["estado"]===1?0:1; ?>"><i class="fa fa-<?php echo (int)$respuestaRapida["estado"]===1?"eye-slash":"eye"; ?>"></i> <?php echo (int)$respuestaRapida["estado"]===1?"Ocultar":"Mostrar"; ?></a>
                    <button class="btn btn-danger btnEliminarContenidoWeb" type="button" data-url="index.php?ruta=centro-web&eliminarRespuestaRapidaWeb=<?php echo (int)$respuestaRapida["id"]; ?>" data-titulo="Eliminar mensaje rápido"><i class="fa fa-trash"></i></button>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</div>

<div id="modalNuevaPublicacionWeb" class="modal fade tm-web-modal" role="dialog">
  <div class="modal-dialog modal-lg"><div class="modal-content"><form method="post" enctype="multipart/form-data">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4><i class="fa fa-bullhorn"></i> Crear publicacion web</h4></div>
    <div class="modal-body"><input type="hidden" name="guardarPublicacionWeb" value="1"><div class="tm-web-form-grid">
      <div class="full"><label>Titulo</label><input class="form-control" name="tituloPublicacionWeb" maxlength="180" required></div>
      <div class="full"><label>Mensaje para el cliente</label><textarea class="form-control" name="resumenPublicacionWeb" rows="4" required></textarea></div>
      <div><label>Tipo</label><select class="form-control" name="tipoPublicacionWeb"><option value="novedad">Novedad</option><option value="oferta">Oferta</option><option value="aviso">Aviso</option></select></div>
      <div><label>Audiencia</label><select class="form-control" name="audienciaPublicacionWeb"><option value="todos">Todos los clientes</option><option value="con_compras">Clientes con compras</option><option value="con_servicios">Clientes con servicios</option><option value="con_proyectos">Clientes con proyectos</option></select></div>
      <div><label>Publicar desde</label><input class="form-control" type="datetime-local" name="fechaInicioPublicacionWeb" value="<?php echo date("Y-m-d\TH:i"); ?>"></div>
      <div><label>Finaliza</label><input class="form-control" type="datetime-local" name="fechaFinPublicacionWeb"></div>
      <div class="full"><label>Imagen destacada</label><input class="form-control" type="file" name="imagenPublicacionWeb" accept=".jpg,.jpeg,.png,.webp"></div>
      <div><label>Destino del botón</label><select class="form-control" name="enlacePublicacionWeb">
        <option value="">Sin enlace</option><option value="index.php">Ir a Inicio</option><option value="tienda.php?modulo=catalogo">Ir al Catálogo</option><option value="tienda.php?modulo=servicios">Ir a Servicios</option><option value="tienda.php?modulo=cuenta">Ir a Mi cuenta</option><option value="tienda.php?modulo=cuenta#consultas">Ir a Consultas</option><option value="index.php#contactanos">Ir a Contacto</option>
      </select></div>
      <div><label>Texto del boton</label><input class="form-control" name="textoBotonPublicacionWeb" placeholder="Ver productos"></div>
      <div class="full tm-web-checks"><label><input type="checkbox" name="destacadaPublicacionWeb" value="1"> Destacada</label><label><input type="checkbox" name="estadoPublicacionWeb" value="1" checked> Activar según programación</label></div>
    </div></div>
    <div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa fa-send"></i> Publicar</button></div>
  </form></div></div>
</div>

<div id="modalNuevaPublicidadWeb" class="modal fade tm-web-modal" role="dialog">
  <div class="modal-dialog modal-lg"><div class="modal-content"><form method="post" enctype="multipart/form-data">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4><i class="fa fa-image"></i> Nueva publicidad de inicio</h4></div>
    <div class="modal-body"><input type="hidden" name="guardarPublicidadModalWeb" value="1"><div class="tm-web-form-grid">
      <div class="full"><label>Imagen publicitaria</label><input class="form-control" type="file" name="imagenPublicidadModalWeb" accept=".jpg,.jpeg,.png,.webp" required><small>Recomendado: 1200 × 700 px.</small></div>
      <div class="full"><label>Titulo</label><input class="form-control" name="tituloPublicidadModalWeb" maxlength="180"></div>
      <div class="full"><label>Texto</label><textarea class="form-control" name="textoPublicidadModalWeb" rows="3"></textarea></div>
      <div><label>Destino del botón</label><select class="form-control" name="enlacePublicidadModalWeb">
        <option value="">Sin enlace</option><option value="index.php">Ir a Inicio</option><option value="tienda.php?modulo=catalogo">Ir al Catálogo</option><option value="tienda.php?modulo=servicios">Ir a Servicios</option><option value="tienda.php?modulo=cuenta">Ir a Mi cuenta</option><option value="tienda.php?modulo=cuenta#consultas">Ir a Consultas</option><option value="index.php#contactanos">Ir a Contacto</option>
      </select></div>
      <div><label>Texto del boton</label><input class="form-control" name="textoBotonPublicidadModalWeb" placeholder="Ver promocion"></div>
      <div><label>Mostrar desde</label><input class="form-control" type="datetime-local" name="fechaInicioPublicidadModalWeb" value="<?php echo date("Y-m-d\TH:i"); ?>"></div>
      <div><label>Finaliza</label><input class="form-control" type="datetime-local" name="fechaFinPublicidadModalWeb"></div>
      <div class="full tm-web-checks"><label><input type="checkbox" name="estadoPublicidadModalWeb" value="1" checked> Activar según programación</label><span>Al activarla, las demás campañas quedarán pausadas.</span></div>
    </div></div>
    <div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar campaña</button></div>
  </form></div></div>
</div>

<div id="modalEditarPublicidadWeb" class="modal fade tm-web-modal" role="dialog">
  <div class="modal-dialog modal-lg"><div class="modal-content"><form method="post" enctype="multipart/form-data">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4><i class="fa fa-pencil"></i> Editar publicidad de bienvenida</h4></div>
    <div class="modal-body">
      <input type="hidden" name="editarPublicidadModalWeb" value="1">
      <input type="hidden" name="idEditarPublicidadModalWeb" id="idEditarPublicidadModalWeb">
      <input type="hidden" name="imagenActualEditarPublicidadModalWeb" id="imagenActualEditarPublicidadModalWeb">
      <div class="tm-web-form-grid">
        <div class="full"><label>Reemplazar imagen (opcional)</label><input class="form-control" type="file" name="imagenEditarPublicidadModalWeb" accept=".jpg,.jpeg,.png,.webp"><small>Si no eliges otra imagen, se mantendrá la actual.</small></div>
        <div class="full"><label>Título</label><input class="form-control" name="tituloEditarPublicidadModalWeb" id="tituloEditarPublicidadModalWeb" maxlength="180"></div>
        <div class="full"><label>Texto</label><textarea class="form-control" name="textoEditarPublicidadModalWeb" id="textoEditarPublicidadModalWeb" rows="3"></textarea></div>
        <div><label>Destino del botón</label><select class="form-control" name="enlaceEditarPublicidadModalWeb" id="enlaceEditarPublicidadModalWeb">
          <option value="">Sin enlace</option><option value="index.php">Ir a Inicio</option><option value="tienda.php?modulo=catalogo">Ir al Catálogo</option><option value="tienda.php?modulo=servicios">Ir a Servicios</option><option value="tienda.php?modulo=cuenta">Ir a Mi cuenta</option><option value="tienda.php?modulo=cuenta#consultas">Ir a Consultas</option><option value="index.php#contactanos">Ir a Contacto</option>
        </select></div>
        <div><label>Texto del botón</label><input class="form-control" name="textoBotonEditarPublicidadModalWeb" id="textoBotonEditarPublicidadModalWeb"></div>
        <div><label>Mostrar desde</label><input class="form-control" type="datetime-local" name="fechaInicioEditarPublicidadModalWeb" id="fechaInicioEditarPublicidadModalWeb"></div>
        <div><label>Finaliza</label><input class="form-control" type="datetime-local" name="fechaFinEditarPublicidadModalWeb" id="fechaFinEditarPublicidadModalWeb"></div>
        <div class="full tm-web-checks"><label><input type="checkbox" name="estadoEditarPublicidadModalWeb" id="estadoEditarPublicidadModalWeb" value="1"> Activar según programación</label><span>Para reutilizar una campaña antigua, renueva su fecha final y déjala activa.</span></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar cambios</button></div>
  </form></div></div>
</div>

<div id="modalEditarPublicacionWeb" class="modal fade tm-web-modal" role="dialog">
  <div class="modal-dialog modal-lg"><div class="modal-content"><form method="post" enctype="multipart/form-data">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4><i class="fa fa-pencil"></i> Editar novedad, oferta o aviso</h4></div>
    <div class="modal-body">
      <input type="hidden" name="editarPublicacionWeb" value="1">
      <input type="hidden" name="idEditarPublicacionWeb" id="idEditarPublicacionWeb">
      <input type="hidden" name="imagenActualEditarPublicacionWeb" id="imagenActualEditarPublicacionWeb">
      <div class="tm-web-form-grid">
        <div class="full"><label>Título</label><input class="form-control" name="tituloEditarPublicacionWeb" id="tituloEditarPublicacionWeb" maxlength="180" required></div>
        <div class="full"><label>Mensaje para el cliente</label><textarea class="form-control" name="resumenEditarPublicacionWeb" id="resumenEditarPublicacionWeb" rows="4" required></textarea></div>
        <div><label>Tipo</label><select class="form-control" name="tipoEditarPublicacionWeb" id="tipoEditarPublicacionWeb"><option value="novedad">Novedad</option><option value="oferta">Oferta</option><option value="aviso">Aviso</option></select></div>
        <div><label>Audiencia</label><select class="form-control" name="audienciaEditarPublicacionWeb" id="audienciaEditarPublicacionWeb"><option value="todos">Todos los clientes</option><option value="con_compras">Clientes con compras</option><option value="con_servicios">Clientes con servicios</option><option value="con_proyectos">Clientes con proyectos</option></select></div>
        <div><label>Publicar desde</label><input class="form-control" type="datetime-local" name="fechaInicioEditarPublicacionWeb" id="fechaInicioEditarPublicacionWeb"></div>
        <div><label>Finaliza</label><input class="form-control" type="datetime-local" name="fechaFinEditarPublicacionWeb" id="fechaFinEditarPublicacionWeb"></div>
        <div class="full"><label>Reemplazar imagen (opcional)</label><input class="form-control" type="file" name="imagenEditarPublicacionWeb" accept=".jpg,.jpeg,.png,.webp"></div>
        <div><label>Destino del botón</label><select class="form-control" name="enlaceEditarPublicacionWeb" id="enlaceEditarPublicacionWeb">
          <option value="">Sin enlace</option><option value="index.php">Ir a Inicio</option><option value="tienda.php?modulo=catalogo">Ir al Catálogo</option><option value="tienda.php?modulo=servicios">Ir a Servicios</option><option value="tienda.php?modulo=cuenta">Ir a Mi cuenta</option><option value="tienda.php?modulo=cuenta#consultas">Ir a Consultas</option><option value="index.php#contactanos">Ir a Contacto</option>
        </select></div>
        <div><label>Texto del botón</label><input class="form-control" name="textoBotonEditarPublicacionWeb" id="textoBotonEditarPublicacionWeb"></div>
        <div class="full tm-web-checks"><label><input type="checkbox" name="destacadaEditarPublicacionWeb" id="destacadaEditarPublicacionWeb" value="1"> Destacada</label><label><input type="checkbox" name="estadoEditarPublicacionWeb" id="estadoEditarPublicacionWeb" value="1"> Activar según programación</label></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar cambios</button></div>
  </form></div></div>
</div>

<div id="modalNuevaRespuestaRapidaWeb" class="modal fade tm-web-modal" role="dialog">
  <div class="modal-dialog"><div class="modal-content"><form method="post">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4><i class="fa fa-comments"></i> Nuevo mensaje rápido</h4></div>
    <div class="modal-body"><input type="hidden" name="guardarRespuestaRapidaWeb" value="1"><div class="tm-web-form-grid">
      <div class="full"><label>Título de la opción</label><input class="form-control" name="tituloRespuestaRapidaWeb" maxlength="100" placeholder="Ej. Consultar producto" required></div>
      <div class="full"><label>Mensaje que se colocará en el chat</label><textarea class="form-control" name="mensajeRespuestaRapidaWeb" maxlength="500" rows="4" required></textarea></div>
      <div class="full"><label>Seleccione un icono</label><input type="hidden" name="iconoRespuestaRapidaWeb" id="iconoRespuestaRapidaWeb" value="ti-message-circle"><div class="tm-icon-picker" data-icon-target="#iconoRespuestaRapidaWeb"></div></div>
      <div><label>Orden</label><input class="form-control" type="number" name="ordenRespuestaRapidaWeb" value="10"></div>
      <div class="full tm-web-checks"><label><input type="checkbox" name="estadoRespuestaRapidaWeb" value="1" checked> Mostrar al cliente</label></div>
    </div></div>
    <div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar mensaje</button></div>
  </form></div></div>
</div>

<div id="modalEditarRespuestaRapidaWeb" class="modal fade tm-web-modal" role="dialog">
  <div class="modal-dialog"><div class="modal-content"><form method="post">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4><i class="fa fa-pencil"></i> Editar mensaje rápido</h4></div>
    <div class="modal-body"><input type="hidden" name="editarRespuestaRapidaWeb" value="1"><input type="hidden" name="idEditarRespuestaRapidaWeb" id="idEditarRespuestaRapidaWeb"><div class="tm-web-form-grid">
      <div class="full"><label>Título de la opción</label><input class="form-control" name="tituloEditarRespuestaRapidaWeb" id="tituloEditarRespuestaRapidaWeb" maxlength="100" required></div>
      <div class="full"><label>Mensaje del chat</label><textarea class="form-control" name="mensajeEditarRespuestaRapidaWeb" id="mensajeEditarRespuestaRapidaWeb" maxlength="500" rows="4" required></textarea></div>
      <div class="full"><label>Seleccione un icono</label><input type="hidden" name="iconoEditarRespuestaRapidaWeb" id="iconoEditarRespuestaRapidaWeb"><div class="tm-icon-picker" data-icon-target="#iconoEditarRespuestaRapidaWeb"></div></div>
      <div><label>Orden</label><input class="form-control" type="number" name="ordenEditarRespuestaRapidaWeb" id="ordenEditarRespuestaRapidaWeb"></div>
      <div class="full tm-web-checks"><label><input type="checkbox" name="estadoEditarRespuestaRapidaWeb" id="estadoEditarRespuestaRapidaWeb" value="1"> Mostrar al cliente</label></div>
    </div></div>
    <div class="modal-footer"><button class="btn btn-default" type="button" data-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Guardar cambios</button></div>
  </form></div></div>
</div>

<script>
function tmDecodificarContenidoWeb(valor){
  var binario=atob(valor||""),bytes=new Uint8Array(binario.length);
  for(var i=0;i<binario.length;i++){bytes[i]=binario.charCodeAt(i);}
  return JSON.parse(new TextDecoder("utf-8").decode(bytes));
}
var tmIconosConsultaWeb=[
  ["ti-message-circle","Mensaje"],["ti-package","Producto"],["ti-shopping-cart","Compra"],["ti-tool","Servicio"],
  ["ti-headset","Asesor"],["ti-file-invoice","Cotización"],["ti-truck-delivery","Entrega"],["ti-cash","Pago"],
  ["ti-device-laptop","Computadora"],["ti-camera","Cámaras"],["ti-code","Software"],["ti-network","Redes"],
  ["ti-shield-check","Seguridad"],["ti-help-circle","Ayuda"],["ti-info-circle","Información"],["ti-alert-circle","Problema"],
  ["ti-clock","Seguimiento"],["ti-calendar","Agendar"],["ti-map-pin","Ubicación"],["ti-phone","Llamada"]
];
function tmRenderIconPickers(){
  $(".tm-icon-picker").each(function(){
    var picker=$(this),target=$(picker.data("icon-target")),actual=target.val()||"ti-message-circle";
    picker.empty();
    tmIconosConsultaWeb.forEach(function(item){
      $("<button>",{type:"button",class:"tm-icon-option"+(item[0]===actual?" active":""),title:item[1]})
        .attr("data-icono",item[0]).append($("<i>",{class:"ti "+item[0]}),$("<span>",{text:item[1]})).appendTo(picker);
    });
  });
}
$(document).on("click",".tm-icon-option",function(){
  var picker=$(this).closest(".tm-icon-picker"),target=$(picker.data("icon-target"));
  target.val($(this).data("icono"));
  picker.find(".tm-icon-option").removeClass("active");
  $(this).addClass("active");
});
function tmSeleccionarEnlaceWeb(selector,valor){
  var campo=$(selector),enlace=valor||"";
  campo.find("option[data-personalizado='1']").remove();
  if(enlace!=="" && campo.find("option").filter(function(){return this.value===enlace;}).length===0){
    campo.append($("<option>",{value:enlace,text:"Enlace personalizado actual"}).attr("data-personalizado","1"));
  }
  campo.val(enlace);
}
$(document).on("click",".btnEditarPublicidadWeb",function(){
  var data=tmDecodificarContenidoWeb($(this).attr("data-contenido"));
  $("#idEditarPublicidadModalWeb").val(data.id||"");
  $("#imagenActualEditarPublicidadModalWeb").val(data.imagen||"");
  $("#tituloEditarPublicidadModalWeb").val(data.titulo||"");
  $("#textoEditarPublicidadModalWeb").val(data.texto||"");
  tmSeleccionarEnlaceWeb("#enlaceEditarPublicidadModalWeb",data.enlace);
  $("#textoBotonEditarPublicidadModalWeb").val(data.texto_boton||"");
  $("#fechaInicioEditarPublicidadModalWeb").val(data.fecha_inicio||"");
  $("#fechaFinEditarPublicidadModalWeb").val(data.fecha_fin||"");
  $("#estadoEditarPublicidadModalWeb").prop("checked",Number(data.estado)===1||data.vencida===true);
  $("#modalEditarPublicidadWeb").modal("show");
});
$(document).on("click",".btnEditarPublicacionWeb",function(){
  var data=tmDecodificarContenidoWeb($(this).attr("data-contenido"));
  $("#idEditarPublicacionWeb").val(data.id||"");
  $("#imagenActualEditarPublicacionWeb").val(data.imagen||"");
  $("#tituloEditarPublicacionWeb").val(data.titulo||"");
  $("#resumenEditarPublicacionWeb").val(data.resumen||"");
  $("#tipoEditarPublicacionWeb").val(data.tipo||"novedad");
  $("#audienciaEditarPublicacionWeb").val(data.audiencia||"todos");
  $("#fechaInicioEditarPublicacionWeb").val(data.fecha_inicio||"");
  $("#fechaFinEditarPublicacionWeb").val(data.fecha_fin||"");
  tmSeleccionarEnlaceWeb("#enlaceEditarPublicacionWeb",data.enlace);
  $("#textoBotonEditarPublicacionWeb").val(data.texto_boton||"");
  $("#destacadaEditarPublicacionWeb").prop("checked",Number(data.destacada)===1);
  $("#estadoEditarPublicacionWeb").prop("checked",Number(data.estado)===1||data.vencida===true);
  $("#modalEditarPublicacionWeb").modal("show");
});
$(document).on("click",".btnEditarRespuestaRapidaWeb",function(){
  var data=tmDecodificarContenidoWeb($(this).attr("data-contenido"));
  $("#idEditarRespuestaRapidaWeb").val(data.id||"");
  $("#tituloEditarRespuestaRapidaWeb").val(data.titulo||"");
  $("#mensajeEditarRespuestaRapidaWeb").val(data.mensaje||"");
  $("#iconoEditarRespuestaRapidaWeb").val(data.icono||"ti-message-circle");
  $("#ordenEditarRespuestaRapidaWeb").val(data.orden||0);
  $("#estadoEditarRespuestaRapidaWeb").prop("checked",Number(data.estado)===1);
  tmRenderIconPickers();
  $("#modalEditarRespuestaRapidaWeb").modal("show");
});
$(document).on("click",".btnEliminarContenidoWeb",function(){
  var url=$(this).data("url"),titulo=$(this).data("titulo")||"Eliminar contenido";
  swal({type:"warning",title:titulo,text:"Esta accion no se puede deshacer.",showCancelButton:true,confirmButtonColor:"#d33",confirmButtonText:"Si, eliminar",cancelButtonText:"Cancelar"}).then(function(r){if(r.value){window.location=url;}});
});
$(".tm-switch input").on("change",function(){$(this).siblings("span").text(this.checked?"Visible":"Oculto");});
function tmActualizarVistaTelefono(control){
  var grupo=$(control).closest(".tm-phone-control"),prefijo=grupo.find(".tm-country-prefix").val()||"",numero=(grupo.find(".tm-local-phone").val()||"").replace(/\D/g,"");
  grupo.find(".tm-local-phone").val(numero);
  grupo.next(".tm-phone-preview").find("[data-phone-preview]").text("+"+prefijo+numero);
}
$(document).on("change input",".tm-country-prefix,.tm-local-phone",function(){tmActualizarVistaTelefono(this);});
function tmCrearSelectoresPais(){
  $(".tm-country-prefix").each(function(){
    var select=$(this);
    if(select.next(".tm-country-picker").length){return;}
    var picker=$("<div>",{class:"tm-country-picker"}),trigger=$("<button>",{type:"button",class:"tm-country-trigger"}),menu=$("<div>",{class:"tm-country-menu"});
    select.find("option").each(function(){
      var option=$(this),texto=option.text().trim(),codigo=(texto.match(/\b([A-Z]{2})\b/)||[])[1]||"BO";
      var etiqueta=texto.replace(/^[^A-Z]*([A-Z]{2})\s*/,"$1 ");
      var item=$("<button>",{type:"button",class:"tm-country-option"+(option.is(":selected")?" active":""),text:etiqueta})
        .attr({"data-value":option.val(),"data-code":codigo});
      item.prepend($("<img>",{src:"https://flagcdn.com/24x18/"+codigo.toLowerCase()+".png",alt:codigo}));
      menu.append(item);
    });
    function pintar(value){
      var active=menu.find('.tm-country-option[data-value="'+value+'"]'),codigo=active.data("code")||"BO";
      trigger.empty().append($("<img>",{src:"https://flagcdn.com/24x18/"+String(codigo).toLowerCase()+".png",alt:codigo}),$("<span>",{text:active.text()}),$("<i>",{class:"fa fa-angle-down"}));
      menu.find(".tm-country-option").removeClass("active");active.addClass("active");
    }
    pintar(select.val());
    trigger.on("click",function(e){e.stopPropagation();$(".tm-country-picker").not(picker).removeClass("open");picker.toggleClass("open");});
    menu.on("click",".tm-country-option",function(){
      select.val($(this).data("value")).trigger("change");pintar(select.val());picker.removeClass("open");
    });
    picker.append(trigger,menu);select.after(picker);
  });
}
$(document).on("click",function(){$(".tm-country-picker").removeClass("open");});
tmCrearSelectoresPais();
$("#modalNuevaRespuestaRapidaWeb").on("show.bs.modal",function(){$("#iconoRespuestaRapidaWeb").val("ti-message-circle");tmRenderIconPickers();});
tmRenderIconPickers();
</script>
