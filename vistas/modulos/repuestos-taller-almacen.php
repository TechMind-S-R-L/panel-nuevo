<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$repuestos = ControladorServicios::ctrMostrarRepuestosTaller();
$pendientesPorServicio = array();
$historialPorServicio = array();

foreach($repuestos as $repuesto){
  $idServicio = $repuesto["id_servicio"];
  if(($repuesto["estado"] ?? "") == "solicitado"){
    $pendientesPorServicio[$idServicio][] = $repuesto;
  }else{
    $historialPorServicio[$idServicio][] = $repuesto;
  }
}

function tmRepuestoEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmRepuestoEquipoTexto($item){
  $partes = array_filter(array($item["codigo_equipo"] ?? "", $item["tipo_equipo"] ?? "", $item["marca"] ?? "", $item["modelo"] ?? ""));
  return trim(implode(" ", $partes)) ?: "Equipo sin detalle";
}

function tmRepuestoEstadoTexto($estado){
  $mapa = array(
    "solicitado" => "Solicitado",
    "entregado" => "Entregado",
    "cancelado" => "Cancelado"
  );
  return $mapa[$estado] ?? ucfirst(str_replace("_", " ", (string)$estado));
}

function tmRepuestoResumenServicio($items){
  $primero = $items[0];
  $cliente = ControladorClientes::ctrMostrarClientes("id", $primero["id_cliente"]);
  $tecnico = !empty($primero["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $primero["id_tecnico"]) : null;
  $total = 0;
  $cantidadTotal = 0;
  $stockInsuficiente = false;
  $estados = array();

  foreach($items as $item){
    $total += (float)($item["subtotal"] ?? 0);
    $cantidadTotal += (int)($item["cantidad"] ?? 0);
    if(($item["estado"] ?? "") == "solicitado" && (int)($item["stock"] ?? 0) < (int)($item["cantidad"] ?? 0)){
      $stockInsuficiente = true;
    }
    $estados[$item["estado"] ?? ""] = true;
  }

  $estadoGeneral = count($estados) == 1 ? array_key_first($estados) : "mixto";

  return array(
    "codigo_servicio" => $primero["codigo_servicio"] ?? "",
    "cliente" => $cliente["nombre"] ?? "Sin cliente",
    "tecnico" => $tecnico["nombre"] ?? "Sin tecnico",
    "equipo" => tmRepuestoEquipoTexto($primero),
    "total" => $total,
    "cantidad" => $cantidadTotal,
    "stock_insuficiente" => $stockInsuficiente,
    "estado" => $estadoGeneral,
    "fecha" => $primero["fecha_solicitud"] ?? ($primero["fecha_entrega"] ?? "")
  );
}

function tmRepuestoItemsJson($items){
  return tmRepuestoEsc(json_encode(array_map(function($item){
    $codigosDisponibles = array();
    $codigosProducto = ControladorProductos::ctrMostrarCodigosUnicosProducto((int)$item["id_producto"]);
    foreach($codigosProducto as $codigoProducto){
      $estadoCodigo = strtolower(trim((string)($codigoProducto["estado"] ?? "")));
      $codigoUnico = trim((string)($codigoProducto["codigo_barras_unico"] ?? ""));
      if($codigoUnico !== "" && $estadoCodigo == "disponible"){
        $codigosDisponibles[] = $codigoUnico;
      }
    }

    return array(
      "id_producto" => (int)$item["id_producto"],
      "descripcion" => $item["descripcion"],
      "codigo" => $item["codigo"] ?? "",
      "cantidad" => (int)$item["cantidad"],
      "stock" => (int)$item["stock"],
      "subtotal" => (float)$item["subtotal"],
      "codigos_disponibles" => $codigosDisponibles
    );
  }, $items)));
}

function tmRepuestoItemsHtml($items){
  ob_start();
  foreach($items as $item):
    $estado = tmRepuestoEstadoTexto($item["estado"] ?? "");
    $stockOk = (int)($item["stock"] ?? 0) >= (int)($item["cantidad"] ?? 0);
  ?>
    <div class="tm-spare-item">
      <div>
        <strong><?php echo tmRepuestoEsc($item["descripcion"] ?? "Producto"); ?></strong>
        <span><?php echo tmRepuestoEsc($item["codigo"] ?? ""); ?> | Cantidad: <?php echo (int)($item["cantidad"] ?? 0); ?> | Stock: <?php echo (int)($item["stock"] ?? 0); ?></span>
      </div>
      <div class="tm-spare-item-right">
        <b>Bs <?php echo number_format((float)($item["subtotal"] ?? 0), 2); ?></b>
        <em class="<?php echo $stockOk ? "ok" : "bad"; ?>"><?php echo tmRepuestoEsc($estado); ?></em>
      </div>
    </div>
  <?php
  endforeach;
  return ob_get_clean();
}

function tmRepuestoAcciones($idServicio, $items, $modo, $stockInsuficiente){
  $idServicio = (int)$idServicio;
  $itemsJson = tmRepuestoItemsJson($items);
  $html = "";

  if($modo == "pendiente"){
    if($stockInsuficiente){
      $html .= '<span class="tm-spare-alert"><i class="fa fa-exclamation-triangle"></i> Stock insuficiente</span>';
    }else{
      $html .= '<button type="button" class="tm-spare-btn success btnEntregarRepuestosTaller" title="Entregar repuestos al tecnico" data-id-servicio="'.$idServicio.'" data-repuestos="'.$itemsJson.'"><i class="fa fa-check"></i><span>Entregar</span></button>';
    }
  }

  $html .= '<button type="button" class="tm-spare-btn light btnImprimirRepuestosTaller" title="Imprimir constancia de repuestos" data-id-servicio="'.$idServicio.'"><i class="fa fa-print"></i><span>Constancia</span></button>';
  return $html;
}

function renderTarjetasRepuestosTaller($porServicio, $modo){
  if(empty($porServicio)){
    echo '<div class="tm-spare-empty"><i class="fa fa-check-circle-o"></i><h4>Sin solicitudes en esta etapa</h4><p>No hay repuestos para mostrar.</p></div>';
    return;
  }

  foreach($porServicio as $idServicio => $items):
    $resumen = tmRepuestoResumenServicio($items);
    $estadoTexto = $modo == "pendiente" ? "Pendiente entrega" : tmRepuestoEstadoTexto($resumen["estado"]);
    $itemsHtml = tmRepuestoItemsHtml($items);
    $acciones = tmRepuestoAcciones($idServicio, $items, $modo, $resumen["stock_insuficiente"]);
    $search = strtolower($resumen["codigo_servicio"]." ".$resumen["cliente"]." ".$resumen["tecnico"]." ".$resumen["equipo"]." ".$estadoTexto);
  ?>
    <article class="tm-spare-card <?php echo $resumen["stock_insuficiente"] ? "stock-bad" : ""; ?>"
      data-search="<?php echo tmRepuestoEsc($search); ?>"
      data-servicio="<?php echo tmRepuestoEsc($resumen["codigo_servicio"]); ?>"
      data-cliente="<?php echo tmRepuestoEsc($resumen["cliente"]); ?>"
      data-tecnico="<?php echo tmRepuestoEsc($resumen["tecnico"]); ?>"
      data-equipo="<?php echo tmRepuestoEsc($resumen["equipo"]); ?>"
      data-total="Bs <?php echo number_format($resumen["total"], 2); ?>"
      data-cantidad="<?php echo (int)$resumen["cantidad"]; ?>"
      data-estado="<?php echo tmRepuestoEsc($estadoTexto); ?>"
      data-fecha="<?php echo tmRepuestoEsc($resumen["fecha"]); ?>">
      <div class="tm-spare-card-head">
        <span class="tm-spare-code"><i class="fa fa-wrench"></i> <?php echo tmRepuestoEsc($resumen["codigo_servicio"]); ?></span>
        <span class="tm-spare-status"><?php echo tmRepuestoEsc($estadoTexto); ?></span>
      </div>
      <h3><?php echo tmRepuestoEsc($resumen["cliente"]); ?></h3>
      <p class="tm-spare-equipo"><i class="fa fa-laptop"></i> <?php echo tmRepuestoEsc($resumen["equipo"]); ?></p>
      <div class="tm-spare-mini">
        <div><span>Tecnico</span><strong><?php echo tmRepuestoEsc($resumen["tecnico"]); ?></strong></div>
        <div><span>Total posterior</span><strong>Bs <?php echo number_format($resumen["total"], 2); ?></strong></div>
        <div><span>Unidades</span><strong><?php echo (int)$resumen["cantidad"]; ?></strong></div>
      </div>
      <div class="tm-spare-preview">
        <?php echo $itemsHtml; ?>
      </div>
      <div class="tm-spare-actions"><?php echo $acciones; ?></div>
      <div class="tm-spare-items-template" style="display:none"><?php echo $itemsHtml; ?></div>
    </article>
  <?php
  endforeach;
}
?>

<style>
.tm-spares-page .content{padding-top:12px}
.tm-spares-hero{background:linear-gradient(135deg,#12384a,#1d86c8);color:#fff;border-radius:18px;padding:22px 24px;margin-bottom:16px;box-shadow:0 16px 35px rgba(18,56,74,.16);display:flex;align-items:center;justify-content:space-between;gap:18px}
.tm-spares-hero h1{margin:0;font-size:25px;font-weight:900}.tm-spares-hero p{margin:6px 0 0;opacity:.93;max-width:780px}.tm-spares-hero-icon{width:58px;height:58px;border-radius:18px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:27px}
.tm-spares-stats{display:grid;grid-template-columns:repeat(3,minmax(160px,1fr));gap:12px;margin-bottom:16px}
.tm-spares-stat{background:rgba(255,255,255,.78);border:1px solid rgba(45,111,181,.14);border-radius:16px;padding:14px 16px;box-shadow:0 12px 25px rgba(30,80,120,.08)}
.tm-spares-stat span{display:block;color:#668099;font-size:12px;font-weight:900;text-transform:uppercase}.tm-spares-stat strong{display:block;color:#12384a;font-size:27px;line-height:1;margin-top:6px}
.tm-spares-panel{background:rgba(255,255,255,.74);border:1px solid rgba(45,111,181,.16);border-radius:18px;box-shadow:0 14px 35px rgba(30,80,120,.09);overflow:hidden}
.tm-spares-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid rgba(45,111,181,.12)}
.tm-spares-toolbar h3{margin:0;font-size:18px;font-weight:900;color:#16324a}.tm-spares-search{max-width:390px;width:100%;position:relative}.tm-spares-search i{position:absolute;left:13px;top:12px;color:#5c7da0}.tm-spares-search input{width:100%;height:40px;border:1px solid rgba(45,111,181,.18);border-radius:12px;padding:0 14px 0 36px;background:rgba(255,255,255,.86);outline:0}
.tm-spares-tabs{padding:0 18px;border-bottom:1px solid rgba(45,111,181,.12)}.tm-spares-tabs.nav-tabs>li>a{border:0!important;border-radius:12px 12px 0 0;color:#51697f;font-weight:900;padding:13px 16px}.tm-spares-tabs.nav-tabs>li.active>a,.tm-spares-tabs.nav-tabs>li.active>a:focus,.tm-spares-tabs.nav-tabs>li.active>a:hover{color:#0d5ea3;background:#fff;border-bottom:3px solid #16a9e0!important}
.tm-spares-panel .tab-content{padding:18px}.tm-spares-note{background:rgba(22,169,224,.09);border:1px solid rgba(22,169,224,.18);color:#245066;border-radius:14px;padding:12px 14px;margin-bottom:14px;font-weight:800}
.tm-spares-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}.tm-spare-card{background:rgba(255,255,255,.9);border:1px solid rgba(45,111,181,.16);border-radius:16px;padding:14px;box-shadow:0 12px 25px rgba(30,80,120,.08);cursor:pointer;transition:.18s ease;position:relative;overflow:hidden}.tm-spare-card:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(30,80,120,.14);border-color:rgba(22,169,224,.42)}.tm-spare-card:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:#16a9e0}.tm-spare-card.stock-bad:before{background:#dd4b39}
.tm-spare-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}.tm-spare-code{font-size:12px;font-weight:900;color:#114d85;background:#edf7ff;border:1px solid rgba(45,111,181,.14);border-radius:999px;padding:6px 9px;line-height:1.2}.tm-spare-status{display:inline-flex;border-radius:999px;background:#f39c12;color:#fff;font-size:11px;font-weight:900;line-height:1.15;padding:6px 9px;text-align:center}
.tm-spare-card h3{margin:0 0 8px;color:#142b3f;font-size:17px;font-weight:900;line-height:1.25;min-height:42px}.tm-spare-equipo{margin:0 0 10px;color:#39556f;font-weight:800;line-height:1.35}
.tm-spare-mini{display:grid;grid-template-columns:1fr 1fr 72px;gap:8px;margin-bottom:10px}.tm-spare-mini div{background:#f6f9fc;border:1px solid rgba(45,111,181,.11);border-radius:12px;padding:9px}.tm-spare-mini span{display:block;font-size:10px;text-transform:uppercase;color:#6b8299;font-weight:900;margin-bottom:4px}.tm-spare-mini strong{display:block;font-size:12px;color:#1d3348;line-height:1.25}
.tm-spare-preview{display:flex;flex-direction:column;gap:7px;max-height:150px;overflow:auto;padding-right:2px}.tm-spare-item{display:flex;justify-content:space-between;gap:10px;background:#fff;border:1px solid rgba(45,111,181,.1);border-radius:12px;padding:9px}.tm-spare-item strong{display:block;color:#1d3348;font-size:12px;line-height:1.25}.tm-spare-item span{display:block;color:#657c93;font-size:11px;margin-top:3px}.tm-spare-item-right{text-align:right;min-width:82px}.tm-spare-item-right b{display:block;color:#114d85;font-size:12px}.tm-spare-item-right em{display:inline-block;margin-top:4px;border-radius:999px;padding:3px 6px;font-style:normal;font-size:10px;font-weight:900;background:#e9f8ef;color:#008d4c}.tm-spare-item-right em.bad{background:#ffecec;color:#c0392b}
.tm-spare-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}.tm-spare-btn{border:0;border-radius:10px;padding:8px 10px;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:900;line-height:1;text-decoration:none!important;white-space:normal;min-height:34px}.tm-spare-btn.success{background:#00a65a;color:#fff}.tm-spare-btn.light{background:#eef5fb;color:#184a78;border:1px solid rgba(45,111,181,.16)}
.tm-spare-alert{display:inline-flex;align-items:center;gap:6px;background:#ffecec;color:#c0392b;border:1px solid #ffcfcf;border-radius:999px;padding:8px 10px;font-weight:900}.tm-spare-empty{grid-column:1/-1;text-align:center;border:1px dashed rgba(45,111,181,.24);border-radius:16px;padding:34px;background:rgba(255,255,255,.58);color:#5f7690}.tm-spare-empty i{font-size:38px;color:#00a65a}.tm-spare-empty h4{font-weight:900;color:#17344c}
.tm-spare-modal .modal-dialog{width:min(900px,calc(100vw - 34px))}.tm-spare-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 24px 60px rgba(11,42,68,.28)}.tm-spare-modal .modal-header{border:0;background:linear-gradient(135deg,#12384a,#178bd0);color:#fff;padding:18px 22px}.tm-spare-modal .modal-header h4{margin:0;font-size:19px;font-weight:900}.tm-spare-modal .close{color:#fff;opacity:.85}
.tm-spare-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.tm-spare-detail{border:1px solid rgba(45,111,181,.14);border-radius:14px;padding:12px;background:#f8fbfd}.tm-spare-detail span{display:block;color:#6b8299;font-size:11px;text-transform:uppercase;font-weight:900;margin-bottom:4px}.tm-spare-detail strong{display:block;color:#153047;font-size:14px;margin:0;font-weight:900;line-height:1.35}.tm-spare-detail.full{grid-column:1/-1}.tm-spare-detail-items{display:flex;flex-direction:column;gap:8px;max-height:270px;overflow:auto}
.tm-spare-modal-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}.tm-code-products{display:flex;flex-direction:column;gap:12px}.tm-code-product{border:1px solid rgba(45,111,181,.16);background:#f8fbfd;border-radius:14px;padding:12px}.tm-code-product h5{margin:0 0 10px;font-weight:900;color:#17344c}.tm-code-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px}.tm-code-grid label{font-size:11px;color:#627b94;font-weight:900;text-transform:uppercase}.tm-code-grid input{border-radius:10px;border:1px solid rgba(45,111,181,.18);box-shadow:none}.tm-code-available{background:#fff;border:1px solid rgba(45,111,181,.13);border-radius:12px;padding:10px;margin-bottom:10px}.tm-code-available strong{display:block;color:#17344c;font-size:12px;font-weight:900;margin-bottom:7px;text-transform:uppercase}.tm-code-chip-list{display:flex;flex-wrap:wrap;gap:6px;max-height:92px;overflow:auto}.tm-code-chip{border:1px solid rgba(22,169,224,.26);background:#edf8ff;color:#0b5d95;border-radius:999px;padding:5px 8px;font-size:11px;font-weight:900;line-height:1;cursor:pointer}.tm-code-chip:hover,.tm-code-chip.used{background:#16a9e0;color:#fff}.tm-code-alert{display:flex;gap:8px;align-items:flex-start;background:#fff7e6;border:1px solid #ffd999;color:#8a5b00;border-radius:12px;padding:9px 10px;margin-bottom:10px;font-weight:800;font-size:12px;line-height:1.3}
body.dark-mode .tm-spares-panel,body.tm-dark .tm-spares-panel,body.dark-mode .tm-spare-card,body.tm-dark .tm-spare-card,body.dark-mode .tm-spares-stat,body.tm-dark .tm-spares-stat{background:rgba(15,27,48,.78);border-color:rgba(255,255,255,.12);color:#eaf3ff}
body.dark-mode .tm-spares-toolbar h3,body.dark-mode .tm-spares-stat strong,body.dark-mode .tm-spare-card h3,body.tm-dark .tm-spares-toolbar h3,body.tm-dark .tm-spares-stat strong,body.tm-dark .tm-spare-card h3{color:#fff}
body.dark-mode .tm-spare-mini div,body.dark-mode .tm-spare-item,body.tm-dark .tm-spare-mini div,body.tm-dark .tm-spare-item{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-spare-mini strong,body.dark-mode .tm-spare-item strong,body.tm-dark .tm-spare-mini strong,body.tm-dark .tm-spare-item strong{color:#edf6ff}
@media(max-width:900px){.tm-spares-hero,.tm-spares-toolbar{flex-direction:column;align-items:flex-start}.tm-spares-stats{grid-template-columns:1fr 1fr}.tm-spare-detail-grid{grid-template-columns:1fr}}
@media(max-width:540px){.tm-spares-stats{grid-template-columns:1fr}.tm-spares-grid{grid-template-columns:1fr}.tm-spare-mini{grid-template-columns:1fr}.tm-spares-tabs.nav-tabs>li{float:none}}
</style>

<div class="content-wrapper tm-spares-page">
  <section class="content-header">
    <h1>Repuestos taller</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Repuestos taller</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-spares-hero">
      <div>
        <h1>Entrega de repuestos para soporte tecnico</h1>
        <p>Almacen valida stock, registra codigos unicos por unidad y entrega las piezas al tecnico con constancia.</p>
      </div>
      <div class="tm-spares-hero-icon"><i class="fa fa-cubes"></i></div>
    </div>

    <div class="tm-spares-stats">
      <div class="tm-spares-stat"><span>Servicios pendientes</span><strong><?php echo count($pendientesPorServicio); ?></strong></div>
      <div class="tm-spares-stat"><span>Items pendientes</span><strong><?php echo count(array_filter($repuestos, function($r){ return ($r["estado"] ?? "") == "solicitado"; })); ?></strong></div>
      <div class="tm-spares-stat"><span>Historial</span><strong><?php echo count($historialPorServicio); ?></strong></div>
    </div>

    <div class="tm-spares-panel">
      <div class="tm-spares-toolbar">
        <h3><i class="fa fa-cubes"></i> Solicitudes de repuestos</h3>
        <div class="tm-spares-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarRepuestoTaller" placeholder="Buscar por servicio, cliente, tecnico o equipo">
        </div>
      </div>

      <ul class="nav nav-tabs tm-spares-tabs">
        <li class="active"><a href="#tabRepuestosPendientes" data-toggle="tab">Pendientes <span class="badge bg-yellow"><?php echo count($pendientesPorServicio); ?></span></a></li>
        <li><a href="#tabRepuestosHistorial" data-toggle="tab">Historial <span class="badge bg-green"><?php echo count($historialPorServicio); ?></span></a></li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane active" id="tabRepuestosPendientes">
          <div class="tm-spares-note">Solicitudes creadas por el tecnico. Al entregar, se descuenta stock y se bloquea el proceso hasta registrar codigos validos.</div>
          <div class="tm-spares-grid"><?php renderTarjetasRepuestosTaller($pendientesPorServicio, "pendiente"); ?></div>
        </div>
        <div class="tab-pane" id="tabRepuestosHistorial">
          <div class="tm-spares-note">Entregas o solicitudes cerradas. Desde aqui puede reimprimir la constancia de repuestos.</div>
          <div class="tm-spares-grid"><?php renderTarjetasRepuestosTaller($historialPorServicio, "historial"); ?></div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleRepuestosTaller" class="modal fade tm-spare-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4><i class="fa fa-cubes"></i> Servicio <span id="detalleServicioRepuesto"></span></h4>
      </div>
      <div class="modal-body">
        <div class="tm-spare-detail-grid">
          <div class="tm-spare-detail"><span>Cliente</span><strong id="detalleClienteRepuesto"></strong></div>
          <div class="tm-spare-detail"><span>Tecnico</span><strong id="detalleTecnicoRepuesto"></strong></div>
          <div class="tm-spare-detail"><span>Equipo</span><strong id="detalleEquipoRepuesto"></strong></div>
          <div class="tm-spare-detail"><span>Estado</span><strong id="detalleEstadoRepuesto"></strong></div>
          <div class="tm-spare-detail"><span>Total posterior</span><strong id="detalleTotalRepuesto"></strong></div>
          <div class="tm-spare-detail"><span>Unidades solicitadas</span><strong id="detalleCantidadRepuesto"></strong></div>
          <div class="tm-spare-detail full"><span>Productos solicitados</span><div class="tm-spare-detail-items" id="detalleItemsRepuesto"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="tm-spare-modal-actions" id="detalleAccionesRepuesto"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalEntregarRepuestosTaller" class="modal fade tm-spare-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4><i class="fa fa-check"></i> Confirmar entrega de repuestos</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="entregarRepuestosTaller" value="1">
          <input type="hidden" id="idServicioEntregaRepuestos" name="idServicioEntregaRepuestos">
          <input type="hidden" id="codigosEntregaRepuestosTaller" name="codigosEntregaRepuestosTaller">
          <div class="tm-spares-note">Registre el codigo unico de cada unidad. Al confirmar se descuenta stock y el tecnico puede continuar la reparacion.</div>
          <div class="tm-code-products" id="contenedorCodigosRepuestosTaller"></div>
          <div class="form-group" style="margin-top:12px">
            <label>Observacion de entrega</label>
            <textarea class="form-control" name="observacionEntregaRepuestos" rows="3" placeholder="Entregado a tecnico, condicion, codigos internos si corresponde"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Confirmar entrega</button>
        </div>
        <?php ControladorServicios::ctrEntregarRepuestosTaller(); ?>
      </form>
    </div>
  </div>
</div>

<script>
function tmSpareEscape(text){
  return $("<div>").text(text || "").html();
}

$(function(){
  $('[title]').tooltip({container:'body'});

  $("#buscarRepuestoTaller").on("input", function(){
    var term = ($(this).val() || "").toLowerCase().trim();
    $(".tm-spare-card").each(function(){
      var text = ($(this).attr("data-search") || "").toLowerCase();
      $(this).toggle(text.indexOf(term) !== -1);
    });
  });
});

$(document).on("click", ".tm-spare-card", function(event){
  if($(event.target).closest("a,button,.tm-spare-actions").length){
    return;
  }

  var card = $(this);
  $("#detalleServicioRepuesto").text(card.data("servicio") || "-");
  $("#detalleClienteRepuesto").text(card.data("cliente") || "-");
  $("#detalleTecnicoRepuesto").text(card.data("tecnico") || "-");
  $("#detalleEquipoRepuesto").text(card.data("equipo") || "-");
  $("#detalleEstadoRepuesto").text(card.data("estado") || "-");
  $("#detalleTotalRepuesto").text(card.data("total") || "-");
  $("#detalleCantidadRepuesto").text(card.data("cantidad") || "0");
  $("#detalleItemsRepuesto").html(card.find(".tm-spare-items-template").html());
  $("#detalleAccionesRepuesto").html(card.find(".tm-spare-actions").html());
  $("#detalleAccionesRepuesto [title]").tooltip({container:"body"});
  $("#modalDetalleRepuestosTaller").modal("show");
});

$(document).on("click", ".btnEntregarRepuestosTaller", function(event){
  event.preventDefault();
  event.stopPropagation();
  var idServicio = $(this).data("idServicio") || $(this).attr("data-id-servicio") || $(this).attr("idServicio") || $(this).attr("idservicio") || "";
  $("#idServicioEntregaRepuestos").val(idServicio);
  $("#codigosEntregaRepuestosTaller").val("");
  var repuestos = [];
  try{
    repuestos = JSON.parse($(this).attr("data-repuestos") || "[]");
  }catch(e){
    repuestos = [];
  }

  var html = "";
  repuestos.forEach(function(repuesto){
    var disponibles = Array.isArray(repuesto.codigos_disponibles) ? repuesto.codigos_disponibles : [];
    var cantidad = Number(repuesto.cantidad || 0);
    html += '<div class="tm-code-product">'+
      '<h5>'+tmSpareEscape(repuesto.descripcion)+' <small>('+tmSpareEscape(repuesto.codigo)+')</small> | Cantidad: '+cantidad+'</h5>';
    if(disponibles.length < cantidad){
      html += '<div class="tm-code-alert"><i class="fa fa-exclamation-triangle"></i><span>Este producto tiene '+disponibles.length+' codigo(s) unico(s) disponible(s), pero se solicitaron '+cantidad+'. Registre codigos unicos en almacen antes de entregar.</span></div>';
    }
    if(disponibles.length){
      html += '<div class="tm-code-available"><strong>Codigos disponibles para entregar</strong><div class="tm-code-chip-list">';
      disponibles.forEach(function(codigoDisponible){
        html += '<button type="button" class="tm-code-chip btnCodigoDisponibleRepuesto" data-code="'+tmSpareEscape(codigoDisponible)+'">'+tmSpareEscape(codigoDisponible)+'</button>';
      });
      html += '</div></div>';
    }else{
      html += '<div class="tm-code-alert"><i class="fa fa-barcode"></i><span>No hay codigos unicos disponibles para este producto. La entrega necesita un codigo unico por unidad.</span></div>';
    }
    html += '<div class="tm-code-grid">';
    for(var i = 0; i < cantidad; i++){
      html += '<div class="form-group">'+
        '<label>Codigo unidad '+(i + 1)+'</label>'+
        '<input type="text" class="form-control codigoRepuestoTaller" data-id-producto="'+Number(repuesto.id_producto || 0)+'" placeholder="Escanee o escriba codigo unico" required>'+
      '</div>';
    }
    html += '</div></div>';
  });

  $("#contenedorCodigosRepuestosTaller").html(html || '<div class="alert alert-danger">No se pudieron cargar los repuestos.</div>');
  $("#modalDetalleRepuestosTaller").modal("hide");
  setTimeout(function(){
    $("#modalEntregarRepuestosTaller").modal("show");
  }, 140);
});

$(document).on("click", ".btnCodigoDisponibleRepuesto", function(){
  var chip = $(this);
  var contenedor = chip.closest(".tm-code-product");
  var codigo = chip.attr("data-code") || "";
  var inputLibre = contenedor.find(".codigoRepuestoTaller").filter(function(){
    return ($(this).val() || "").trim() === "";
  }).first();

  if(!inputLibre.length){
    swal({type:"warning", title:"Campos completos", text:"Ya se llenaron todos los codigos para este producto.", confirmButtonText:"Cerrar"});
    return;
  }

  inputLibre.val(codigo).trigger("change");
  chip.addClass("used");
});

$("#modalEntregarRepuestosTaller form").on("submit", function(e){
  var idServicio = ($("#idServicioEntregaRepuestos").val() || "").trim();
  if(idServicio === "" || Number(idServicio) <= 0){
    e.preventDefault();
    swal({type:"error", title:"Solicitud sin referencia", text:"No se pudo identificar la solicitud de repuestos. Cierre el modal y vuelva a presionar Entregar.", confirmButtonText:"Cerrar"});
    return false;
  }

  var codigos = {};
  var repetidos = {};
  var repetido = "";
  var faltante = false;
  $(".codigoRepuestoTaller").each(function(){
    var idProducto = $(this).attr("data-id-producto");
    var codigo = ($(this).val() || "").trim();
    var llave = codigo.toLowerCase();
    if(!codigos[idProducto]){
      codigos[idProducto] = [];
    }
    if(codigo === ""){
      faltante = true;
    }else{
      if(repetidos[llave]){
        repetido = codigo;
      }
      repetidos[llave] = true;
    }
    codigos[idProducto].push(codigo);
  });
  if(faltante){
    e.preventDefault();
    swal({type:"error", title:"Faltan codigos", text:"Debe registrar un codigo unico por cada unidad solicitada.", confirmButtonText:"Cerrar"});
    return false;
  }
  if(repetido !== ""){
    e.preventDefault();
    swal({type:"error", title:"Codigo repetido", text:"El codigo "+repetido+" esta repetido.", confirmButtonText:"Cerrar"});
    return false;
  }
  $("#codigosEntregaRepuestosTaller").val(JSON.stringify(codigos));
});

$(document).on("click", ".btnImprimirRepuestosTaller", function(event){
  event.preventDefault();
  event.stopPropagation();
  var idServicio = $(this).data("idServicio") || $(this).attr("data-id-servicio") || $(this).attr("idServicio") || $(this).attr("idservicio") || "";
  window.open("extensiones/tcpdf/pdf/boleta-repuestos-taller.php?idServicio=" + idServicio, "_blank");
});
</script>
