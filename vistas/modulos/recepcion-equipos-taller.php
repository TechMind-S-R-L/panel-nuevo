<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$equipos = ControladorServicios::ctrMostrarEquiposTaller();
$pendientesRecepcion = array_values(array_filter($equipos, function($equipo){
  return ($equipo["estado_equipo"] ?? "") == "ingresado";
}));
$enAlmacen = array_values(array_filter($equipos, function($equipo){
  return in_array(($equipo["estado_equipo"] ?? ""), array("recibido_almacen", "retiro_solicitado"))
    || (($equipo["estado_equipo"] ?? "") == "retirado_tecnico" && empty($equipo["id_almacenero_retiro"]));
}));
$pendientesReingreso = array_values(array_filter($equipos, function($equipo){
  return ($equipo["estado_equipo"] ?? "") == "pendiente_reingreso";
}));
$porEntregarCliente = array_values(array_filter($equipos, function($equipo){
  return ($equipo["estado_equipo"] ?? "") == "devuelto_almacen";
}));
$historial = array_values(array_filter($equipos, function($equipo){
  return ($equipo["estado_equipo"] ?? "") != "ingresado"
    || !empty($equipo["fecha_recepcion_almacen"])
    || !empty($equipo["fecha_retiro_tecnico"])
    || !empty($equipo["fecha_reingreso_almacen"])
    || !empty($equipo["fecha_entrega_cliente"]);
}));

function tmTallerEsc($valor){
  return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

function tmTallerEstadoTexto($estado){
  $mapa = array(
    "ingresado" => "Pendiente recibir",
    "recibido_almacen" => "En almacen",
    "retiro_solicitado" => "Retiro solicitado",
    "retirado_tecnico" => "Con tecnico",
    "pendiente_reingreso" => "Pendiente reingreso",
    "devuelto_almacen" => "Listo para cliente",
    "entregado_cliente" => "Entregado al cliente"
  );
  return $mapa[$estado] ?? ucfirst(str_replace("_", " ", (string)$estado));
}

function tmTallerEstadoClase($estado){
  $mapa = array(
    "ingresado" => "warning",
    "recibido_almacen" => "info",
    "retiro_solicitado" => "purple",
    "retirado_tecnico" => "primary",
    "pendiente_reingreso" => "orange",
    "devuelto_almacen" => "success",
    "entregado_cliente" => "dark"
  );
  return $mapa[$estado] ?? "default";
}

function ultimoMovimientoEquipoTaller($equipo){
  if(!empty($equipo["fecha_entrega_cliente"])){
    return "Entregado al cliente - ".$equipo["fecha_entrega_cliente"];
  }
  if(!empty($equipo["fecha_reingreso_almacen"])){
    return "Reingreso a almacen - ".$equipo["fecha_reingreso_almacen"];
  }
  if(!empty($equipo["fecha_retiro_tecnico"])){
    return "Retirado por tecnico - ".$equipo["fecha_retiro_tecnico"];
  }
  if(($equipo["estado_equipo"] ?? "") == "retiro_solicitado"){
    return "Retiro solicitado - pendiente entrega de almacen";
  }
  if(!empty($equipo["fecha_recepcion_almacen"])){
    return "Recibido en almacen - ".$equipo["fecha_recepcion_almacen"];
  }
  return "Ingreso registrado - ".($equipo["fecha_registro"] ?? "");
}

function tmTallerEquipoTexto($equipo){
  $partes = array_filter(array($equipo["tipo_equipo"] ?? "", $equipo["marca"] ?? "", $equipo["modelo"] ?? ""));
  return trim(implode(" ", $partes)) ?: "Equipo sin detalle";
}

function tmTallerDatosEquipo($equipo){
  $cliente = ControladorClientes::ctrMostrarClientes("id", $equipo["id_cliente"]);
  $vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $equipo["id_vendedor"]);
  $tecnico = !empty($equipo["id_tecnico"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $equipo["id_tecnico"]) : null;
  return array(
    "cliente" => $cliente["nombre"] ?? "Sin cliente",
    "vendedor" => $vendedor["nombre"] ?? "Sin vendedor",
    "tecnico" => $tecnico["nombre"] ?? "Sin tecnico",
    "equipo" => tmTallerEquipoTexto($equipo)
  );
}

function tmTallerBoton($clase, $icono, $texto, $titulo, $extra = "", $href = ""){
  $contenido = '<i class="fa '.$icono.'"></i><span>'.$texto.'</span>';
  if($href){
    return '<a class="tm-action-btn '.$clase.'" href="'.$href.'" title="'.tmTallerEsc($titulo).'" '.$extra.'>'.$contenido.'</a>';
  }
  return '<button type="button" class="tm-action-btn '.$clase.'" title="'.tmTallerEsc($titulo).'" '.$extra.'>'.$contenido.'</button>';
}

function tmTallerAcciones($equipo, $modo){
  $idServicio = (int)($equipo["id_servicio"] ?? 0);
  $codigo = tmTallerEsc($equipo["codigo_equipo"] ?? "");
  $html = "";

  if($modo == "recepcion"){
    $html .= tmTallerBoton("success", "fa-check", "Recibir", "Recibir equipo en almacen", "", "index.php?ruta=recepcion-equipos-taller&recibirEquipoTaller=".$idServicio);
    $html .= tmTallerBoton("light btnImprimirCustodiaEquipo", "fa-print", "Ingreso", "Imprimir boleta de ingreso", 'idServicio="'.$idServicio.'" tipo="ingreso"');
  }else if($modo == "almacen"){
    $html .= tmTallerBoton("warning", "fa-share", "Entregar tecnico", "Entregar fisicamente al tecnico", "", "index.php?ruta=recepcion-equipos-taller&entregarEquipoTecnicoTaller=".$idServicio);
    $html .= tmTallerBoton("light btnImprimirCustodiaEquipo", "fa-print", "Recepcion", "Imprimir recepcion en almacen", 'idServicio="'.$idServicio.'" tipo="recepcion"');
    $html .= tmTallerBoton("light btnImprimirCustodiaEquipo", "fa-print", "Retiro", "Imprimir retiro del tecnico", 'idServicio="'.$idServicio.'" tipo="retiro"');
  }else if($modo == "reingreso"){
    $html .= tmTallerBoton("primary btnReingresarEquipoTaller", "fa-sign-in", "Recibir tecnico", "Recibir equipo de vuelta en almacen", 'idServicio="'.$idServicio.'" equipo="'.$codigo.'"');
    $html .= tmTallerBoton("light btnImprimirTallerAlmacen", "fa-file-text-o", "Detalle", "Imprimir detalle correctivo", 'idServicio="'.$idServicio.'" tipo="correctivo"');
  }else if($modo == "cliente"){
    if(($equipo["estado_pago"] ?? "") == "aprobado"){
      $html .= tmTallerBoton("success", "fa-check", "Entregar cliente", "Devolver equipo al cliente", "", "index.php?ruta=recepcion-equipos-taller&entregarEquipoClienteTaller=".$idServicio);
      $html .= tmTallerBoton("primary btnImprimirNotaVentaServicio", "fa-file-text-o", "Nota final", "Imprimir nota final para cliente", 'idServicio="'.$idServicio.'"');
      $html .= tmTallerBoton("light btnImprimirTallerAlmacen", "fa-wrench", "Boleta tecnica", "Imprimir boleta tecnica para cliente", 'idServicio="'.$idServicio.'" tipo="correctivo"');
    }else{
      $html .= '<span class="tm-pay-wait"><i class="fa fa-clock-o"></i> Esperando pago en caja</span>';
    }
  }else{
    $html .= tmTallerBoton("light btnImprimirCustodiaEquipo", "fa-print", "Ingreso", "Reimprimir ingreso", 'idServicio="'.$idServicio.'" tipo="ingreso"');
    if(!empty($equipo["fecha_recepcion_almacen"])){
      $html .= tmTallerBoton("light btnImprimirCustodiaEquipo", "fa-print", "Recepcion", "Reimprimir recepcion en almacen", 'idServicio="'.$idServicio.'" tipo="recepcion"');
    }
    if(!empty($equipo["fecha_retiro_tecnico"])){
      $html .= tmTallerBoton("light btnImprimirCustodiaEquipo", "fa-print", "Retiro", "Reimprimir retiro del tecnico", 'idServicio="'.$idServicio.'" tipo="retiro"');
    }
    if(!empty($equipo["fecha_reingreso_almacen"])){
      $html .= tmTallerBoton("light btnImprimirCustodiaEquipo", "fa-print", "Reingreso", "Reimprimir reingreso a almacen", 'idServicio="'.$idServicio.'" tipo="reingreso"');
    }
    if(!empty($equipo["fecha_entrega_cliente"]) || ($equipo["estado_equipo"] ?? "") == "entregado_cliente"){
      $html .= tmTallerBoton("primary btnImprimirNotaVentaServicio", "fa-file-text-o", "Nota final", "Reimprimir nota final entregada al cliente", 'idServicio="'.$idServicio.'"');
    }
    if(!empty($equipo["reparacion_realizada"]) || ($equipo["estado_equipo"] ?? "") == "entregado_cliente"){
      $html .= tmTallerBoton("light btnImprimirTallerAlmacen", "fa-wrench", "Boleta tecnica", "Reimprimir boleta tecnica para cliente", 'idServicio="'.$idServicio.'" tipo="correctivo"');
    }
  }

  return $html;
}

function renderTarjetasEquiposTaller($lista, $modo){
  if(empty($lista)){
    echo '<div class="tm-empty-state"><i class="fa fa-check-circle-o"></i><h4>Sin equipos en esta etapa</h4><p>No hay movimientos pendientes para esta pestaña.</p></div>';
    return;
  }

  foreach($lista as $equipo){
    $datos = tmTallerDatosEquipo($equipo);
    $estado = $equipo["estado_equipo"] ?? "";
    $estadoTexto = tmTallerEstadoTexto($estado);
    $estadoClase = tmTallerEstadoClase($estado);
    $falla = $equipo["falla_reportada"] ?? "Sin falla registrada";
    $movimiento = ultimoMovimientoEquipoTaller($equipo);
    $busqueda = strtolower(($equipo["codigo_equipo"] ?? "")." ".$datos["cliente"]." ".$datos["equipo"]." ".$falla." ".$datos["tecnico"]." ".$estadoTexto);
    ?>
    <article class="tm-taller-card tm-status-<?php echo tmTallerEsc($estadoClase); ?>"
      data-search="<?php echo tmTallerEsc($busqueda); ?>"
      data-codigo="<?php echo tmTallerEsc($equipo["codigo_equipo"] ?? ""); ?>"
      data-cliente="<?php echo tmTallerEsc($datos["cliente"]); ?>"
      data-equipo="<?php echo tmTallerEsc($datos["equipo"]); ?>"
      data-falla="<?php echo tmTallerEsc($falla); ?>"
      data-vendedor="<?php echo tmTallerEsc($datos["vendedor"]); ?>"
      data-tecnico="<?php echo tmTallerEsc($datos["tecnico"]); ?>"
      data-estado="<?php echo tmTallerEsc($estadoTexto); ?>"
      data-estado-clase="<?php echo tmTallerEsc($estadoClase); ?>"
      data-movimiento="<?php echo tmTallerEsc($movimiento); ?>">
      <div class="tm-card-head">
        <div class="tm-card-icon"><i class="fa fa-laptop"></i></div>
        <div class="tm-card-title">
          <span class="tm-equipo-code"><i class="fa fa-barcode"></i> <?php echo tmTallerEsc($equipo["codigo_equipo"] ?? ""); ?></span>
          <h3><?php echo tmTallerEsc($datos["equipo"]); ?></h3>
        </div>
        <span class="tm-state-badge <?php echo tmTallerEsc($estadoClase); ?>"><?php echo tmTallerEsc($estadoTexto); ?></span>
      </div>
      <div class="tm-client-strip">
        <i class="fa fa-user"></i>
        <div><span>Cliente</span><strong><?php echo tmTallerEsc($datos["cliente"]); ?></strong></div>
      </div>
      <div class="tm-card-info">
        <div><i class="fa fa-user-circle"></i><span>Vendedor</span><strong><?php echo tmTallerEsc($datos["vendedor"]); ?></strong></div>
        <div><i class="fa fa-wrench"></i><span>Tecnico</span><strong><?php echo tmTallerEsc($datos["tecnico"]); ?></strong></div>
      </div>
      <div class="tm-falla">
        <span>Falla / detalle</span>
        <p><?php echo tmTallerEsc($falla); ?></p>
      </div>
      <div class="tm-last-move"><i class="fa fa-history"></i> <?php echo tmTallerEsc($movimiento); ?></div>
      <div class="tm-taller-actions"><?php echo tmTallerAcciones($equipo, $modo); ?></div>
    </article>
    <?php
  }
}
?>

<style>
.tm-taller-page .content{padding-top:12px}
.tm-taller-hero{background:linear-gradient(135deg,#12384a,#1d86c8);color:#fff;border-radius:18px;padding:22px 24px;margin-bottom:16px;box-shadow:0 16px 35px rgba(18,56,74,.16);display:flex;align-items:center;justify-content:space-between;gap:18px}
.tm-taller-hero h1{margin:0;font-size:25px;font-weight:800;letter-spacing:0}
.tm-taller-hero p{margin:6px 0 0;opacity:.92;max-width:780px}
.tm-taller-hero .tm-hero-icon{width:58px;height:58px;border-radius:18px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:27px}
.tm-taller-metrics{display:grid;grid-template-columns:repeat(5,minmax(140px,1fr));gap:12px;margin-bottom:16px}
.tm-taller-metric{background:rgba(255,255,255,.76);border:1px solid rgba(45,111,181,.14);border-radius:16px;padding:14px 16px;box-shadow:0 12px 25px rgba(30,80,120,.08)}
.tm-taller-metric span{display:block;color:#668099;font-size:12px;font-weight:800;text-transform:uppercase}
.tm-taller-metric strong{display:block;color:#12384a;font-size:26px;line-height:1;margin-top:6px}
.tm-taller-panel{background:rgba(255,255,255,.74);border:1px solid rgba(45,111,181,.16);border-radius:18px;box-shadow:0 14px 35px rgba(30,80,120,.09);overflow:hidden}
.tm-taller-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid rgba(45,111,181,.12)}
.tm-taller-toolbar h3{margin:0;font-size:18px;font-weight:800;color:#16324a}
.tm-taller-search{max-width:380px;width:100%;position:relative}
.tm-taller-search i{position:absolute;left:13px;top:12px;color:#5c7da0}
.tm-taller-search input{width:100%;height:40px;border:1px solid rgba(45,111,181,.18);border-radius:12px;padding:0 14px 0 36px;background:rgba(255,255,255,.86);outline:0}
.tm-taller-tabs{padding:0 18px;border-bottom:1px solid rgba(45,111,181,.12)}
.tm-taller-tabs.nav-tabs>li>a{border:0!important;border-radius:12px 12px 0 0;color:#51697f;font-weight:800;padding:13px 16px}
.tm-taller-tabs.nav-tabs>li.active>a,.tm-taller-tabs.nav-tabs>li.active>a:focus,.tm-taller-tabs.nav-tabs>li.active>a:hover{color:#0d5ea3;background:#fff;border-bottom:3px solid #16a9e0!important}
.tm-taller-panel .tab-content{padding:18px}
.tm-taller-note{background:rgba(22,169,224,.09);border:1px solid rgba(22,169,224,.18);color:#245066;border-radius:14px;padding:12px 14px;margin-bottom:14px;font-weight:700}
.tm-taller-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(292px,1fr));gap:14px}
.tm-taller-card{background:rgba(255,255,255,.88);border:1px solid rgba(45,111,181,.16);border-radius:16px;padding:14px;box-shadow:0 12px 25px rgba(30,80,120,.08);cursor:pointer;transition:.18s ease;position:relative;overflow:hidden}
.tm-taller-card:hover{transform:translateY(-2px);border-color:rgba(22,169,224,.42);box-shadow:0 16px 34px rgba(30,80,120,.14)}
.tm-taller-card:before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:#16a9e0}
.tm-taller-card.tm-status-warning:before{background:#f39c12}
.tm-taller-card.tm-status-orange:before{background:#f97316}
.tm-taller-card.tm-status-success:before{background:#00a65a}
.tm-taller-card.tm-status-purple:before{background:#7c3aed}
.tm-taller-card.tm-status-dark:before{background:#1f2937}
.tm-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}
.tm-equipo-code{font-size:12px;font-weight:900;color:#114d85;background:#edf7ff;border:1px solid rgba(45,111,181,.14);border-radius:999px;padding:6px 9px;line-height:1.2}
.tm-state-badge{display:inline-flex;align-items:center;justify-content:center;text-align:center;border-radius:999px;color:#fff;font-size:11px;font-weight:900;line-height:1.15;padding:6px 9px;max-width:128px}
.tm-state-badge.warning{background:#f39c12}.tm-state-badge.info{background:#00c0ef}.tm-state-badge.purple{background:#7c3aed}.tm-state-badge.primary{background:#3c8dbc}.tm-state-badge.orange{background:#f97316}.tm-state-badge.success{background:#00a65a}.tm-state-badge.dark{background:#1f2937}.tm-state-badge.default{background:#6b7280}
.tm-taller-card h3{margin:0 0 8px;color:#142b3f;font-size:17px;font-weight:900;line-height:1.25;min-height:42px}
.tm-client{margin:0 0 10px;color:#39556f;font-weight:800}
.tm-card-info{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}
.tm-card-info div,.tm-falla{background:#f6f9fc;border:1px solid rgba(45,111,181,.11);border-radius:12px;padding:9px}
.tm-card-info span,.tm-falla span{display:block;font-size:10px;text-transform:uppercase;color:#6b8299;font-weight:900;margin-bottom:4px}
.tm-card-info strong{display:block;font-size:12px;color:#1d3348;line-height:1.25}
.tm-falla p{margin:0;color:#253f57;line-height:1.35;min-height:38px}
.tm-last-move{font-size:12px;color:#597086;background:#fff7e6;border:1px solid rgba(243,156,18,.18);border-radius:12px;padding:8px 9px;margin-top:10px;font-weight:700}
.tm-taller-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}
.tm-action-btn{border:0;border-radius:10px;padding:8px 10px;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:12px;font-weight:900;line-height:1;text-decoration:none!important;white-space:normal;min-height:34px}
.tm-action-btn.success{background:#00a65a;color:#fff}.tm-action-btn.warning{background:#f39c12;color:#fff}.tm-action-btn.primary{background:#248fce;color:#fff}.tm-action-btn.light{background:#eef5fb;color:#184a78;border:1px solid rgba(45,111,181,.16)}
.tm-pay-wait{display:inline-flex;align-items:center;gap:6px;background:#fff3cd;color:#8a5b00;border:1px solid #ffe4a3;border-radius:999px;padding:8px 10px;font-weight:900}
.tm-empty-state{grid-column:1/-1;text-align:center;border:1px dashed rgba(45,111,181,.24);border-radius:16px;padding:34px;background:rgba(255,255,255,.58);color:#5f7690}
.tm-empty-state i{font-size:38px;color:#00a65a}.tm-empty-state h4{font-weight:900;color:#17344c}
.tm-taller-modal .modal-dialog{width:min(920px,calc(100vw - 34px))}
.tm-taller-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 24px 60px rgba(11,42,68,.28)}
.tm-taller-modal .modal-header{border:0;background:linear-gradient(135deg,#12384a,#178bd0);color:#fff;padding:20px 22px}
.tm-taller-modal .modal-header h4{margin:0;font-size:20px;font-weight:900}.tm-taller-modal .close{color:#fff;opacity:.85}
.tm-detail-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:14px}
.tm-detail-card{border:1px solid rgba(45,111,181,.14);border-radius:14px;padding:13px;background:#f8fbfd}
.tm-detail-card span{display:block;color:#6b8299;font-size:11px;text-transform:uppercase;font-weight:900;margin-bottom:4px}
.tm-detail-card strong,.tm-detail-card p{display:block;color:#153047;font-size:14px;margin:0;font-weight:800;line-height:1.35}
.tm-detail-falla{grid-column:1/-1}
.tm-modal-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.tm-reingreso-box{background:#eef8ff;border:1px solid rgba(22,169,224,.2);border-radius:14px;color:#16425f;font-weight:900;padding:12px;margin-bottom:12px}
.tm-taller-grid{grid-template-columns:repeat(auto-fill,minmax(318px,1fr));gap:16px}
.tm-taller-card{padding:0;border-radius:18px;background:rgba(255,255,255,.82);backdrop-filter:blur(8px);display:flex;flex-direction:column;min-height:338px}
.tm-taller-card:before{display:none}
.tm-taller-card:after{content:"";position:absolute;right:-36px;bottom:-42px;width:118px;height:118px;border-radius:50%;background:rgba(36,143,206,.12);pointer-events:none}
.tm-card-head{display:grid;grid-template-columns:44px minmax(0,1fr) auto;gap:10px;align-items:start;padding:14px 14px 10px;background:linear-gradient(135deg,rgba(18,56,74,.08),rgba(22,169,224,.07));border-bottom:1px solid rgba(45,111,181,.12)}
.tm-card-icon{width:42px;height:42px;border-radius:14px;background:linear-gradient(135deg,#155a9c,#19aee8);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 10px 22px rgba(24,113,177,.2)}
.tm-card-title{min-width:0}
.tm-card-title h3{margin:7px 0 0;font-size:16px;min-height:0;line-height:1.25;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-equipo-code{display:inline-flex;align-items:center;gap:5px;max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tm-state-badge{max-width:116px;min-width:88px;padding:7px 9px;box-shadow:0 8px 18px rgba(12,55,90,.12)}
.tm-client-strip{display:flex;gap:10px;align-items:center;margin:12px 14px 10px;padding:10px 12px;border-radius:14px;background:#f4f9fd;border:1px solid rgba(45,111,181,.1)}
.tm-client-strip>i{width:32px;height:32px;border-radius:10px;background:#e5f3ff;color:#176ca9;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
.tm-client-strip span{display:block;font-size:10px;text-transform:uppercase;color:#6b8299;font-weight:900;margin-bottom:2px}
.tm-client-strip strong{display:block;color:#18344d;font-size:13px;line-height:1.25}
.tm-card-info{padding:0 14px;grid-template-columns:1fr 1fr}
.tm-card-info div{display:grid;grid-template-columns:22px 1fr;column-gap:7px;align-items:start}
.tm-card-info div>i{grid-row:1 / span 2;color:#248fce;margin-top:2px}
.tm-card-info span,.tm-card-info strong{min-width:0}
.tm-falla{margin:10px 14px 0}
.tm-falla p{min-height:42px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-last-move{margin:10px 14px 0;display:flex;gap:8px;align-items:flex-start;min-height:44px}
.tm-taller-actions{margin-top:auto;padding:12px 14px 14px;border-top:1px solid rgba(45,111,181,.1)}
.tm-action-btn{flex:1 1 118px;min-height:36px;padding:9px 10px;border-radius:11px}
.tm-pay-wait{width:100%;justify-content:center;border-radius:12px}
.tm-taller-modal .modal-dialog{width:min(820px,calc(100vw - 34px))}
.tm-taller-modal .modal-content{border-radius:20px;background:rgba(255,255,255,.98)}
.tm-taller-modal .modal-header{padding:0;background:linear-gradient(135deg,#12384a,#1d86c8);overflow:hidden}
.tm-modal-title-row{display:grid;grid-template-columns:52px minmax(0,1fr) auto;gap:12px;align-items:center;padding:18px 22px;position:relative}
.tm-modal-title-row:after{content:"";position:absolute;right:-38px;top:-48px;width:132px;height:132px;border-radius:50%;background:rgba(255,255,255,.14)}
.tm-modal-title-icon{width:48px;height:48px;border-radius:16px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:21px}
.tm-modal-title-row span{display:block;text-transform:uppercase;font-size:11px;font-weight:900;opacity:.82;letter-spacing:.03em}
.tm-modal-title-row h4{font-size:21px!important;line-height:1.15;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tm-modal-state{position:relative;z-index:1;display:inline-flex;align-items:center;justify-content:center;min-width:120px;max-width:160px;text-align:center;border-radius:999px;background:rgba(255,255,255,.2);padding:8px 10px;font-size:12px;line-height:1.1}
.tm-modal-state.warning{background:#f39c12}.tm-modal-state.info{background:#00c0ef}.tm-modal-state.purple{background:#7c3aed}.tm-modal-state.primary{background:#3c8dbc}.tm-modal-state.orange{background:#f97316}.tm-modal-state.success{background:#00a65a}.tm-modal-state.dark{background:#1f2937}.tm-modal-state.default{background:#6b7280}
.tm-modal-summary{display:grid;grid-template-columns:1fr 1.1fr;gap:12px;margin-bottom:12px}
.tm-modal-summary div{border:1px solid rgba(45,111,181,.13);background:linear-gradient(135deg,#f8fbfd,#eef7ff);border-radius:16px;padding:13px}
.tm-modal-summary span,.tm-detail-card span{display:block;color:#6b8299;font-size:10px;text-transform:uppercase;font-weight:900;margin-bottom:5px}
.tm-modal-summary strong{display:block;color:#153047;font-size:15px;line-height:1.25}
.tm-detail-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.tm-detail-card{display:grid;grid-template-columns:28px minmax(0,1fr);column-gap:8px;padding:12px;border-radius:15px}
.tm-detail-card>i{grid-row:1 / span 2;width:28px;height:28px;border-radius:9px;background:#eaf5ff;color:#176ca9;display:flex;align-items:center;justify-content:center}
.tm-detail-card span,.tm-detail-card strong,.tm-detail-card p{min-width:0}
.tm-detail-falla{grid-column:1/-1}
.tm-detail-falla p{white-space:pre-line}
.tm-taller-modal .modal-footer{border-top:1px solid rgba(45,111,181,.12);background:#f7fbfe}
.tm-modal-actions .tm-action-btn{flex:0 1 auto;min-width:130px}
.tm-reingreso-box{border-radius:16px;background:linear-gradient(135deg,#eef8ff,#f8fbfd)}
body.dark-mode .tm-taller-hero,body.tm-dark .tm-taller-hero{background:linear-gradient(135deg,#0b1325,#0f5d99)}
body.dark-mode .tm-taller-panel,body.tm-dark .tm-taller-panel,body.dark-mode .tm-taller-card,body.tm-dark .tm-taller-card,body.dark-mode .tm-taller-metric,body.tm-dark .tm-taller-metric{background:rgba(15,27,48,.78);border-color:rgba(255,255,255,.12);color:#eaf3ff}
body.dark-mode .tm-taller-card h3,body.dark-mode .tm-taller-toolbar h3,body.dark-mode .tm-taller-metric strong,body.tm-dark .tm-taller-card h3,body.tm-dark .tm-taller-toolbar h3,body.tm-dark .tm-taller-metric strong{color:#fff}
body.dark-mode .tm-card-info div,body.dark-mode .tm-falla,body.tm-dark .tm-card-info div,body.tm-dark .tm-falla{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-card-info strong,body.dark-mode .tm-falla p,body.tm-dark .tm-card-info strong,body.tm-dark .tm-falla p{color:#edf6ff}
body.dark-mode .tm-card-head,body.tm-dark .tm-card-head{background:linear-gradient(135deg,rgba(255,255,255,.08),rgba(36,143,206,.08));border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-client-strip,body.dark-mode .tm-detail-card,body.dark-mode .tm-modal-summary div,body.tm-dark .tm-client-strip,body.tm-dark .tm-detail-card,body.tm-dark .tm-modal-summary div{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.1)}
body.dark-mode .tm-client-strip strong,body.dark-mode .tm-modal-summary strong,body.dark-mode .tm-detail-card strong,body.tm-dark .tm-client-strip strong,body.tm-dark .tm-modal-summary strong,body.tm-dark .tm-detail-card strong{color:#fff}
.tm-taller-grid{grid-template-columns:repeat(auto-fill,minmax(286px,1fr))!important;gap:12px!important}
.tm-taller-card{min-height:304px!important;border-radius:16px!important}
.tm-card-head{grid-template-columns:38px minmax(0,1fr) minmax(88px,112px)!important;grid-template-rows:auto auto!important;gap:8px 9px!important;padding:10px 12px!important;align-items:center!important;background:linear-gradient(135deg,rgba(14,73,116,.08),rgba(27,158,215,.09))!important}
.tm-card-icon{grid-column:1!important;grid-row:2!important;width:36px!important;height:36px!important;border-radius:12px!important;font-size:15px!important}
.tm-card-head .tm-card-title{display:contents!important}
.tm-equipo-code{grid-column:1 / 4!important;grid-row:1!important;display:flex!important;align-items:center;gap:5px;width:100%;min-width:0;padding:6px 8px!important;border-radius:10px!important;background:rgba(255,255,255,.82)!important;border:1px solid rgba(45,111,181,.14)!important;color:#0f5d99!important;font-family:Consolas,Monaco,monospace;font-size:10px!important;font-weight:900;line-height:1.15!important;white-space:normal!important;overflow:visible!important;text-overflow:clip!important;overflow-wrap:anywhere!important;word-break:normal!important}
.tm-card-head .tm-card-title h3{grid-column:2!important;grid-row:2!important;font-size:14px!important;line-height:1.18!important;margin:0!important;-webkit-line-clamp:2!important}
.tm-card-head .tm-state-badge{grid-column:3!important;grid-row:2!important;justify-self:end;align-self:center;margin:0}
.tm-state-badge{min-width:0!important;max-width:112px!important;width:100%;font-size:9.5px!important;padding:6px 7px!important;line-height:1.08!important;white-space:normal;overflow-wrap:anywhere}
.tm-client-strip{margin:9px 12px 7px!important;padding:8px 9px!important;border-radius:12px!important;gap:8px!important}
.tm-client-strip>i{width:28px!important;height:28px!important;border-radius:9px!important;font-size:12px!important}
.tm-client-strip span{font-size:9px!important}
.tm-client-strip strong{font-size:11.5px!important;line-height:1.16!important;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-card-info{padding:0 12px!important;gap:6px!important}
.tm-card-info div{grid-template-columns:18px minmax(0,1fr)!important;column-gap:5px!important;padding:7px!important;border-radius:11px!important}
.tm-card-info div>i{font-size:12px!important}
.tm-card-info span{font-size:8.5px!important;line-height:1.05!important;margin-bottom:2px!important}
.tm-card-info strong{font-size:10.5px!important;line-height:1.12!important;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-falla{margin:8px 12px 0!important;padding:8px!important;border-radius:11px!important}
.tm-falla span{font-size:8.5px!important;margin-bottom:3px!important}
.tm-falla p{min-height:34px!important;font-size:11px!important;line-height:1.22!important;-webkit-line-clamp:2!important}
.tm-last-move{margin:8px 12px 0!important;min-height:34px!important;padding:7px 8px!important;font-size:10.5px!important;line-height:1.25!important;border-radius:11px!important;display:-webkit-box!important;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tm-taller-actions{padding:9px 12px 11px!important;gap:6px!important}
.tm-action-btn{flex:1 1 104px!important;min-height:31px!important;padding:7px 8px!important;border-radius:10px!important;font-size:10.5px!important}
.tm-pay-wait{font-size:10.5px!important;min-height:31px!important;padding:7px 8px!important}
.tm-taller-modal .modal-header{position:relative!important;overflow:hidden}
.tm-taller-modal .modal-header .close{position:absolute;right:13px;top:10px;z-index:50;color:#fff!important;opacity:.96!important;text-shadow:none!important;pointer-events:auto!important;width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}
.tm-modal-title-row{padding:15px 58px 15px 18px!important;grid-template-columns:44px minmax(0,1fr) auto!important}
.tm-modal-title-row:after{pointer-events:none!important}
.tm-modal-title-icon{width:40px!important;height:40px!important;border-radius:13px!important;font-size:18px!important}
.tm-modal-title-row h4{font-size:18px!important}
.tm-modal-state{min-width:96px!important;max-width:132px!important;font-size:11px!important;padding:7px 8px!important}
.tm-taller-modal .modal-header>h4{padding:16px 58px 16px 20px!important;margin:0!important;font-size:18px!important}
@media(max-width:900px){.tm-taller-hero,.tm-taller-toolbar{flex-direction:column;align-items:flex-start}.tm-taller-metrics{grid-template-columns:repeat(2,1fr)}.tm-detail-grid{grid-template-columns:1fr}}
@media(max-width:520px){.tm-taller-metrics{grid-template-columns:1fr}.tm-taller-grid{grid-template-columns:1fr}.tm-card-info{grid-template-columns:1fr}.tm-taller-tabs.nav-tabs>li{float:none}.tm-taller-tabs.nav-tabs>li>a{border-radius:10px}}
</style>

<div class="content-wrapper tm-taller-page">
  <section class="content-header">
    <h1>Recepcion de equipos taller</h1>
    <ol class="breadcrumb">
      <li><a href="inicio"><i class="fa fa-home"></i> Inicio</a></li>
      <li class="active">Recepcion equipos taller</li>
    </ol>
  </section>

  <section class="content">
    <div class="tm-taller-hero">
      <div>
        <h1>Control de equipos para soporte tecnico</h1>
        <p>Almacen recibe, custodia, entrega al tecnico, recibe el equipo reparado y finalmente lo entrega al cliente con constancia.</p>
      </div>
      <div class="tm-hero-icon"><i class="fa fa-laptop"></i></div>
    </div>

    <div class="tm-taller-metrics">
      <div class="tm-taller-metric"><span>Pendiente recibir</span><strong><?php echo count($pendientesRecepcion); ?></strong></div>
      <div class="tm-taller-metric"><span>En almacen</span><strong><?php echo count($enAlmacen); ?></strong></div>
      <div class="tm-taller-metric"><span>Reingreso tecnico</span><strong><?php echo count($pendientesReingreso); ?></strong></div>
      <div class="tm-taller-metric"><span>Entrega cliente</span><strong><?php echo count($porEntregarCliente); ?></strong></div>
      <div class="tm-taller-metric"><span>Historial</span><strong><?php echo count($historial); ?></strong></div>
    </div>

    <div class="tm-taller-panel">
      <div class="tm-taller-toolbar">
        <h3><i class="fa fa-archive"></i> Movimientos de almacen</h3>
        <div class="tm-taller-search">
          <i class="fa fa-search"></i>
          <input type="text" id="buscarEquipoTaller" placeholder="Buscar por codigo, cliente, tecnico, equipo o estado">
        </div>
      </div>

      <ul class="nav nav-tabs tm-taller-tabs">
        <li class="active"><a href="#tabRecibirTaller" data-toggle="tab">Pendiente recibir <span class="badge bg-yellow"><?php echo count($pendientesRecepcion); ?></span></a></li>
        <li><a href="#tabEnAlmacenTaller" data-toggle="tab">En almacen <span class="badge bg-aqua"><?php echo count($enAlmacen); ?></span></a></li>
        <li><a href="#tabReingresoTaller" data-toggle="tab">Reingreso tecnico <span class="badge bg-green"><?php echo count($pendientesReingreso); ?></span></a></li>
        <li><a href="#tabClienteTaller" data-toggle="tab">Entrega cliente <span class="badge bg-green"><?php echo count($porEntregarCliente); ?></span></a></li>
        <li><a href="#tabHistorialTaller" data-toggle="tab">Historial</a></li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane active" id="tabRecibirTaller">
          <div class="tm-taller-note">Equipos que el vendedor ya ingreso y debe entregar fisicamente a almacen.</div>
          <div class="tm-taller-grid"><?php renderTarjetasEquiposTaller($pendientesRecepcion, "recepcion"); ?></div>
        </div>
        <div class="tab-pane" id="tabEnAlmacenTaller">
          <div class="tm-taller-note">Equipos bajo custodia de almacen, listos para que el tecnico los retire con constancia.</div>
          <div class="tm-taller-grid"><?php renderTarjetasEquiposTaller($enAlmacen, "almacen"); ?></div>
        </div>
        <div class="tab-pane" id="tabReingresoTaller">
          <div class="tm-taller-note">Cuando el tecnico termine diagnostico, reparacion o devolucion, almacen debe recibir el equipo reparado o devuelto.</div>
          <div class="tm-taller-grid"><?php renderTarjetasEquiposTaller($pendientesReingreso, "reingreso"); ?></div>
        </div>
        <div class="tab-pane" id="tabClienteTaller">
          <div class="tm-taller-note">Equipos reparados o devueltos que permanecen en almacen hasta que el cliente pague en caja.</div>
          <div class="tm-taller-grid"><?php renderTarjetasEquiposTaller($porEntregarCliente, "cliente"); ?></div>
        </div>
        <div class="tab-pane" id="tabHistorialTaller">
          <div class="tm-taller-note">Historial de movimientos y entregas realizadas, con opciones para reimprimir constancias.</div>
          <div class="tm-taller-grid"><?php renderTarjetasEquiposTaller($historial, "historial"); ?></div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleEquipoTaller" class="modal fade tm-taller-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <div class="tm-modal-title-row">
          <div class="tm-modal-title-icon"><i class="fa fa-laptop"></i></div>
          <div>
            <span>Detalle de equipo en taller</span>
            <h4 id="detalleCodigoEquipoTaller">Equipo</h4>
          </div>
          <strong class="tm-modal-state" id="detalleEstadoBadgeEquipoTaller">Estado</strong>
        </div>
      </div>
      <div class="modal-body">
        <div class="tm-modal-summary">
          <div>
            <span>Cliente</span>
            <strong id="detalleClienteEquipoTaller"></strong>
          </div>
          <div>
            <span>Equipo</span>
            <strong id="detalleEquipoTextoTaller"></strong>
          </div>
        </div>

        <div class="tm-detail-grid">
          <div class="tm-detail-card"><i class="fa fa-user-circle"></i><span>Vendedor</span><strong id="detalleVendedorEquipoTaller"></strong></div>
          <div class="tm-detail-card"><i class="fa fa-wrench"></i><span>Tecnico asignado</span><strong id="detalleTecnicoEquipoTaller"></strong></div>
          <div class="tm-detail-card"><i class="fa fa-info-circle"></i><span>Estado actual</span><strong id="detalleEstadoEquipoTaller"></strong></div>
          <div class="tm-detail-card"><i class="fa fa-history"></i><span>Ultimo movimiento</span><strong id="detalleMovimientoEquipoTaller"></strong></div>
          <div class="tm-detail-card tm-detail-falla"><i class="fa fa-clipboard"></i><span>Falla / detalle registrado</span><p id="detalleFallaEquipoTaller"></p></div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="tm-modal-actions" id="detalleAccionesEquipoTaller"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalReingresoEquipoTaller" class="modal fade tm-taller-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4><i class="fa fa-sign-in"></i> Reingreso de equipo a almacen</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="reingresarEquipoTaller" value="1">
          <input type="hidden" id="idServicioReingresoTaller" name="idServicioReingresoTaller">
          <div class="tm-reingreso-box" id="equipoReingresoTaller"></div>
          <div class="form-group">
            <label>Detalle recibido por almacen</label>
            <textarea class="form-control" name="observacionReingresoTaller" rows="4" required placeholder="Estado final, accesorios que vuelven, observaciones de reparacion y conformidad interna"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Confirmar reingreso</button>
        </div>
        <?php ControladorServicios::ctrReingresarEquipoAlmacen(); ?>
      </form>
    </div>
  </div>
</div>

<script>
$(function(){
  $('[title]').tooltip({container:'body'});

  $("#buscarEquipoTaller").on("input", function(){
    var term = ($(this).val() || "").toLowerCase().trim();
    $(".tm-taller-card").each(function(){
      var text = ($(this).attr("data-search") || "").toLowerCase();
      $(this).toggle(text.indexOf(term) !== -1);
    });
  });
});

$(document).on("click", ".tm-taller-card", function(event){
  if($(event.target).closest("a,button,.tm-taller-actions").length){
    return;
  }

  var card = $(this);
  $("#detalleCodigoEquipoTaller").text(card.data("codigo") || "Equipo");
  $("#detalleClienteEquipoTaller").text(card.data("cliente") || "-");
  $("#detalleEstadoEquipoTaller").text(card.data("estado") || "-");
  $("#detalleEstadoBadgeEquipoTaller")
    .removeClass("warning info purple primary orange success dark default")
    .addClass(card.data("estadoClase") || "default")
    .text(card.data("estado") || "Estado");
  $("#detalleEquipoTextoTaller").text(card.data("equipo") || "-");
  $("#detalleTecnicoEquipoTaller").text(card.data("tecnico") || "-");
  $("#detalleVendedorEquipoTaller").text(card.data("vendedor") || "-");
  $("#detalleMovimientoEquipoTaller").text(card.data("movimiento") || "-");
  $("#detalleFallaEquipoTaller").text(card.data("falla") || "-");
  $("#detalleAccionesEquipoTaller").html(card.find(".tm-taller-actions").html());
  $("#detalleAccionesEquipoTaller [title]").tooltip({container:"body"});
  $("#modalDetalleEquipoTaller").modal("show");
});

$(document).on("click", ".btnImprimirCustodiaEquipo", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-custodia-equipo.php?idServicio=" + $(this).attr("idServicio") + "&tipo=" + $(this).attr("tipo"), "_blank");
});

$(document).on("click", ".btnImprimirTallerAlmacen", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-taller.php?idServicio=" + $(this).attr("idServicio") + "&tipo=" + $(this).attr("tipo"), "_blank");
});

$(document).on("click", ".btnImprimirNotaVentaServicio", function(event){
  event.preventDefault();
  event.stopPropagation();
  window.open("extensiones/tcpdf/pdf/nota-venta-servicio.php?idServicio=" + $(this).attr("idServicio") + "&destino=cliente", "_blank");
});

$(document).on("click", ".btnReingresarEquipoTaller", function(event){
  event.preventDefault();
  event.stopPropagation();
  $("#idServicioReingresoTaller").val($(this).attr("idServicio"));
  $("#equipoReingresoTaller").text("Equipo: " + $(this).attr("equipo"));
  $("#modalDetalleEquipoTaller").modal("hide");
  setTimeout(function(){
    $("#modalReingresoEquipoTaller").modal("show");
  }, 140);
});
</script>

<?php ControladorServicios::ctrRecepcionarEquipoAlmacen(); ?>
<?php ControladorServicios::ctrEntregarEquipoTecnicoAlmacen(); ?>
<?php ControladorServicios::ctrEntregarEquipoClienteAlmacen(); ?>
