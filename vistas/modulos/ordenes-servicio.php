<?php
if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "tecnico"){
  echo '<script>window.location = "inicio";</script>';
  return;
}

$servicios = $_SESSION["perfil"] == "Administrador"
  ? ControladorServicios::ctrMostrarServicios()
  : ControladorServicios::ctrMostrarServiciosTecnico($_SESSION["id"]);
$tmOrdenPuedeVerMontos = ($_SESSION["perfil"] ?? "") == "Administrador";

function tmOrdenEsServicioTecnico($servicio){
  $tipo = trim((string)($servicio["tipo_servicio"] ?? ""));
  if($tipo === "Desarrollo de software"){
    return false;
  }
  if(stripos($tipo, "software") !== false){
    return false;
  }
  return true;
}

$servicios = array_values(array_filter($servicios, "tmOrdenEsServicioTecnico"));

function tmOrdenEsc($valor){
  return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function tmOrdenEquipoTallerLigero($servicio){
  static $cache = array();
  if(($servicio["tipo_servicio"] ?? "") != "Soporte tecnico en taller"){
    return null;
  }
  $idServicio = (int)($servicio["id"] ?? 0);
  if(!isset($cache[$idServicio])){
    $cache[$idServicio] = ControladorServicios::ctrMostrarEquipoTaller($idServicio);
  }
  return $cache[$idServicio];
}

function tmOrdenBandejaServicio($servicio){
  $tipo = $servicio["tipo_servicio"] ?? "";
  $estadoServicio = $servicio["estado_servicio"] ?? "";
  $estadoPago = $servicio["estado_pago"] ?? "";
  $cerrados = array("retorno_almacen", "listo_cobro", "pagado_retiro", "completado");

  if($tipo == "Soporte tecnico en taller"){
    $equipo = tmOrdenEquipoTallerLigero($servicio);
    $estadoEquipo = $equipo["estado_equipo"] ?? "";

    if(in_array($estadoServicio, array("completado")) || in_array($estadoEquipo, array("entregado_cliente"))){
      return "cerradas";
    }

    if(in_array($estadoServicio, array("retorno_almacen", "listo_cobro", "pagado_retiro")) || in_array($estadoEquipo, array("pendiente_reingreso", "devuelto_almacen"))){
      return "espera";
    }

    if($estadoServicio == "rep_solicitado" || $estadoEquipo == "retiro_solicitado"){
      return "espera";
    }

    if(in_array($estadoEquipo, array("ingresado", "recibido_almacen")) || in_array($estadoServicio, array("pendiente_almacen", "en_almacen"))){
      return "almacen";
    }

    if(in_array($estadoEquipo, array("retirado_tecnico", "diagnosticado", "autorizado", "reparado", "rechazado")) || in_array($estadoServicio, array("diagnosticado", "autorizado", "rep_entregado", "reparado", "devolucion_pend"))){
      return "taller";
    }

    return "por_tomar";
  }

  if(($estadoPago ?? "") != "aprobado"){
    return "espera";
  }

  if(in_array($estadoServicio, $cerrados)){
    return "cerradas";
  }

  if(in_array($estadoServicio, array("atendiendo", "en_proceso"))){
    return "campo";
  }

  return "por_tomar";
}

$ordenBandejas = array(
  "por_tomar" => array(
    "titulo" => "Por tomar",
    "ayuda" => "Ordenes asignadas que aun no fueron tomadas por el tecnico.",
    "icono" => "fa-hand-pointer-o",
    "items" => array()
  ),
  "campo" => array(
    "titulo" => "Campo / instalacion",
    "ayuda" => "Cola ordenada por llegada: primero trabajos iniciados y luego ordenes tomadas pendientes de iniciar.",
    "icono" => "fa-map-marker",
    "items" => array()
  ),
  "almacen" => array(
    "titulo" => "Equipo en almacen",
    "ayuda" => "Equipos de taller que siguen bajo custodia de almacen o requieren retiro.",
    "icono" => "fa-archive",
    "items" => array()
  ),
  "taller" => array(
    "titulo" => "En taller",
    "ayuda" => "Equipos retirados por el tecnico para diagnostico, repuestos o reparacion.",
    "icono" => "fa-laptop",
    "items" => array()
  ),
  "espera" => array(
    "titulo" => "En espera",
    "ayuda" => "Casos pausados por almacen, repuestos, caja o devolucion.",
    "icono" => "fa-clock-o",
    "items" => array()
  ),
  "cerradas" => array(
    "titulo" => "Cerradas",
    "ayuda" => "Ordenes completadas o ya fuera del trabajo activo del tecnico.",
    "icono" => "fa-check-circle",
    "items" => array()
  )
);

foreach($servicios as $servicio){
  $bandeja = tmOrdenBandejaServicio($servicio);
  if(!isset($ordenBandejas[$bandeja])){
    $bandeja = "por_tomar";
  }
  $ordenBandejas[$bandeja]["items"][] = $servicio;
}

usort($ordenBandejas["campo"]["items"], function($a, $b){
  $estadoA = $a["estado_servicio"] ?? "";
  $estadoB = $b["estado_servicio"] ?? "";
  if($estadoA === "en_proceso" && $estadoB !== "en_proceso"){
    return -1;
  }
  if($estadoB === "en_proceso" && $estadoA !== "en_proceso"){
    return 1;
  }
  $fechaA = strtotime($a["fecha_pago"] ?? $a["fecha"] ?? "now");
  $fechaB = strtotime($b["fecha_pago"] ?? $b["fecha"] ?? "now");
  if($fechaA === $fechaB){
    return (int)($a["id"] ?? 0) <=> (int)($b["id"] ?? 0);
  }
  return $fechaA <=> $fechaB;
});

$ordenActivas = array_merge(
  $ordenBandejas["por_tomar"]["items"],
  $ordenBandejas["campo"]["items"],
  $ordenBandejas["almacen"]["items"],
  $ordenBandejas["taller"]["items"],
  $ordenBandejas["espera"]["items"]
);

$requiereInventarioTaller = count(array_filter($ordenActivas, function($servicio){
  return ($servicio["tipo_servicio"] ?? "") == "Soporte tecnico en taller";
})) > 0;
$productosInventario = $requiereInventarioTaller ? ControladorProductos::ctrMostrarProductos(null, null, "stock") : array();
$requiereMapaOrden = count(array_filter($servicios, function($servicio){
  return !empty($servicio["latitud"]) && !empty($servicio["longitud"]);
})) > 0;

function tmOrdenEstadoClase($estado){
  $estado = strtolower((string)$estado);
  if(strpos($estado, "complet") !== false || strpos($estado, "devuelto") !== false || strpos($estado, "reparado") !== false){
    return "success";
  }
  if(strpos($estado, "esperando") !== false || strpos($estado, "pendiente") !== false || strpos($estado, "solicitado") !== false){
    return "warning";
  }
  if(strpos($estado, "rechaz") !== false || strpos($estado, "devolucion") !== false){
    return "danger";
  }
  if(strpos($estado, "atend") !== false || strpos($estado, "proceso") !== false || strpos($estado, "curso") !== false || strpos($estado, "almacen") !== false){
    return "info";
  }
  return "default";
}

function tmOrdenBoton($tipo, $clase, $icono, $texto, $titulo, $attrs = "", $href = ""){
  $contenido = '<i class="fa '.$icono.'"></i>'.($texto !== "" ? ' <span>'.$texto.'</span>' : '');
  if($tipo == "link"){
    return '<a class="tm-order-btn '.$clase.'" title="'.tmOrdenEsc($titulo).'" href="'.$href.'" '.$attrs.'>'.$contenido.'</a>';
  }
  return '<button type="button" class="tm-order-btn '.$clase.'" title="'.tmOrdenEsc($titulo).'" '.$attrs.'>'.$contenido.'</button>';
}

function tmOrdenBotonInformeFinalTaller($servicio, $equipoTaller, $resumenRepuestos, $estadoFinal){
  return ' <button type="button" class="tm-order-btn tm-btn-success btnInformeFinalTaller"
      title="Ver informe final de taller"
      idServicio="'.$servicio["id"].'"
      codigoEquipo="'.tmOrdenEsc($equipoTaller["codigo_equipo"] ?? "").'"
      equipo="'.tmOrdenEsc(($equipoTaller["tipo_equipo"] ?? "")." ".($equipoTaller["marca"] ?? "")." ".($equipoTaller["modelo"] ?? "")." ".($equipoTaller["serie"] ?? "")).'"
      diagnostico="'.tmOrdenEsc($equipoTaller["diagnostico_tecnico"] ?? "").'"
      notificacion="'.tmOrdenEsc($equipoTaller["detalle_notificacion"] ?? "").'"
      reparacion="'.tmOrdenEsc($equipoTaller["reparacion_realizada"] ?? "").'"
      repuestos="'.tmOrdenEsc($equipoTaller["repuestos_detalle"] ?? "").'"
      garantia="'.tmOrdenEsc($equipoTaller["garantia_detalle"] ?? "").'"
      resumenRepuestos="'.tmOrdenEsc($resumenRepuestos).'"
      evidencias="'.tmOrdenEsc($equipoTaller["evidencias_tecnicas"] ?? "[]").'"
      estadoFinal="'.tmOrdenEsc($estadoFinal).'"><i class="fa fa-clipboard"></i> <span>Informe final</span></button>';
}

function tmOrdenContexto($servicio, $mostrarTecnico){
    static $clientesCache = array();
    static $tecnicosCache = array();
    static $equiposCache = array();
    static $repuestosCache = array();

    $idCliente = $servicio["id_cliente"] ?? 0;
    if(!isset($clientesCache[$idCliente])){
      $clientesCache[$idCliente] = ControladorClientes::ctrMostrarClientes("id", $idCliente);
    }
    $cliente = $clientesCache[$idCliente];

    $tecnico = null;
    if($mostrarTecnico && !empty($servicio["id_tecnico"])){
      $idTecnico = $servicio["id_tecnico"];
      if(!isset($tecnicosCache[$idTecnico])){
        $tecnicosCache[$idTecnico] = ControladorUsuarios::ctrMostrarUsuarios("id", $idTecnico);
      }
      $tecnico = $tecnicosCache[$idTecnico];
    }

    $equipoTaller = null;
    $repuestosTaller = array();
    if(($servicio["tipo_servicio"] ?? "") == "Soporte tecnico en taller"){
      $idServicio = $servicio["id"];
      if(!isset($equiposCache[$idServicio])){
        $equiposCache[$idServicio] = ControladorServicios::ctrMostrarEquipoTaller($idServicio);
      }
      $equipoTaller = $equiposCache[$idServicio];
      if($equipoTaller){
        if(!isset($repuestosCache[$idServicio])){
          $repuestosCache[$idServicio] = ControladorServicios::ctrMostrarRepuestosTaller($idServicio);
        }
        $repuestosTaller = $repuestosCache[$idServicio];
      }
    }
    $hayRepuestosSolicitados = count(array_filter($repuestosTaller, function($repuesto){ return ($repuesto["estado"] ?? "") == "solicitado"; })) > 0;
    $hayRepuestosEntregados = count(array_filter($repuestosTaller, function($repuesto){ return ($repuesto["estado"] ?? "") == "entregado"; })) > 0;
    $totalRepuestosEntregados = array_reduce($repuestosTaller, function($total, $repuesto){
      return $total + ((($repuesto["estado"] ?? "") == "entregado") ? (float)$repuesto["subtotal"] : 0);
    }, 0);
    $resumenRepuestos = "";
    foreach($repuestosTaller as $repuesto){
      if(($repuesto["estado"] ?? "") == "cancelado"){
        continue;
      }
      $resumenRepuestos .= htmlspecialchars($repuesto["descripcion"], ENT_QUOTES, "UTF-8")." x ".(int)$repuesto["cantidad"]." - ".htmlspecialchars($repuesto["estado"], ENT_QUOTES, "UTF-8")."\\n";
    }
    $estadoActual = $servicio["estado_servicio"] ?? "";
    $estaAtendiendo = in_array($estadoActual, array("atendiendo", "en_proceso"));
    if($estadoActual === "atendiendo"){
      $estadoTexto = "Tomada - pendiente de iniciar";
    }else if($estadoActual === "en_proceso"){
      $estadoTexto = "Trabajo en curso";
    }else if($estadoActual === "asignado"){
      $estadoTexto = "Pendiente de tomar";
    }else{
      $estadoTexto = $estadoActual;
    }
    if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "ingresado"){
      $estadoTexto = $estaAtendiendo ? "Atendiendo - pendiente almacen" : "Pendiente almacen";
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "recibido_almacen"){
      $estadoTexto = $estaAtendiendo ? "Atendiendo - en almacen" : "En almacen";
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "retiro_solicitado"){
      $estadoTexto = "Retiro solicitado - esperando almacen";
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "reparado"){
      $estadoTexto = "Reparado";
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "rechazado"){
      $estadoTexto = "Devolucion pendiente";
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "devuelto_almacen"){
      $estadoTexto = "Devuelto a almacen";
    }
    $direccionServicio = $equipoTaller ? ("Equipo: ".$equipoTaller["codigo_equipo"]." - ".$equipoTaller["tipo_equipo"]." ".$equipoTaller["marca"]." ".$equipoTaller["modelo"]) : $servicio["direccion_instalacion"];
    return array(
      "servicio" => $servicio,
      "cliente" => $cliente,
      "tecnico" => $tecnico,
      "equipoTaller" => $equipoTaller,
      "hayRepuestosSolicitados" => $hayRepuestosSolicitados,
      "hayRepuestosEntregados" => $hayRepuestosEntregados,
      "totalRepuestosEntregados" => $totalRepuestosEntregados,
      "resumenRepuestos" => $resumenRepuestos,
      "estadoTexto" => $estadoTexto,
      "estadoClase" => tmOrdenEstadoClase($estadoTexto),
      "direccionServicio" => $direccionServicio
    );
}

function tmOrdenAcciones($ctx){
    global $tmOrdenPuedeVerMontos;
    $servicio = $ctx["servicio"];
    $equipoTaller = $ctx["equipoTaller"];
    $hayRepuestosSolicitados = $ctx["hayRepuestosSolicitados"];
    $hayRepuestosEntregados = $ctx["hayRepuestosEntregados"];
    $totalRepuestosEntregados = $ctx["totalRepuestosEntregados"];
    $resumenRepuestos = $ctx["resumenRepuestos"];
    $acciones = '';

    $acciones .= tmOrdenBoton("button", "tm-btn-neutral btnImprimirOrdenServicio", "fa-print", "Orden", "Imprimir orden de servicio", 'idServicio="'.$servicio["id"].'"');

    if(!$equipoTaller && !empty($servicio["boleta_conformidad_archivo"])){
      $acciones .= tmOrdenBoton("link", "tm-btn-success btnVerConformidadFirmada", "fa-check-square-o", "Boleta firmada", "Ver boleta de conformidad firmada", 'target="_blank" rel="noopener"', tmOrdenEsc($servicio["boleta_conformidad_archivo"]));
    }

    if(!$equipoTaller && in_array(($servicio["estado_servicio"] ?? ""), array("atendiendo", "en_proceso", "completado"))){
      $acciones .= tmOrdenBoton("button", "tm-btn-primary btnImprimirConformidadInstalacion", "fa-file-text-o", "Conformidad", "Imprimir boleta de conformidad de instalacion", 'idServicio="'.$servicio["id"].'"');
    }

    if($equipoTaller){
      $acciones .= tmOrdenBoton("button", "tm-btn-primary btnImprimirIngresoEquipo", "fa-file-text-o", "Ingreso", "Imprimir ingreso de equipo", 'idServicio="'.$servicio["id"].'"');
      if(!empty($equipoTaller["diagnostico_tecnico"])){
        $acciones .= tmOrdenBoton("button", "tm-btn-neutral btnImprimirTaller", "fa-stethoscope", "Diag.", "Imprimir diagnostico", 'idServicio="'.$servicio["id"].'" tipo="diagnostico"');
      }
      if((int)($equipoTaller["notificado_cliente"] ?? 0) === 1){
        $acciones .= tmOrdenBoton("button", "tm-btn-neutral btnImprimirTaller", "fa-phone", "Notif.", "Imprimir notificacion", 'idServicio="'.$servicio["id"].'" tipo="notificacion"');
      }
      if(!empty($equipoTaller["reparacion_realizada"])){
        $acciones .= tmOrdenBoton("button", "tm-btn-neutral btnImprimirTaller", "fa-wrench", "Correctivo", "Imprimir soporte correctivo", 'idServicio="'.$servicio["id"].'" tipo="correctivo"');
      }
      if(($equipoTaller["respuesta_cliente"] ?? "") == "no_conforme"){
        $acciones .= tmOrdenBoton("button", "tm-btn-neutral btnImprimirTaller", "fa-undo", "Devol.", "Imprimir devolucion", 'idServicio="'.$servicio["id"].'" tipo="devolucion"');
      }
    }

    if($servicio["latitud"] != "" && $servicio["longitud"] != ""){
      $acciones .= tmOrdenBoton("button", "tm-btn-primary btnVerMapaServicio", "fa-map-marker", "Mapa", "Ver mapa", 'lat="'.tmOrdenEsc($servicio["latitud"]).'" lng="'.tmOrdenEsc($servicio["longitud"]).'" direccion="'.tmOrdenEsc($servicio["direccion_instalacion"]).'"');
      $acciones .= '<a class="tm-order-btn tm-btn-info" title="Abrir en Google Maps" target="_blank" href="https://www.google.com/maps?q='.tmOrdenEsc($servicio["latitud"]).','.tmOrdenEsc($servicio["longitud"]).'"><i class="fa fa-external-link"></i> <span>Maps</span></a>';
    }

    $estadosPuedeAtender = array("asignado", "pendiente_almacen", "en_almacen", "pagado_retiro");
    if(in_array(($servicio["estado_servicio"] ?? ""), $estadosPuedeAtender)){
      $acciones .= tmOrdenBoton("link", "tm-btn-warning btnAtenderOrden", "fa-play", "Atender solicitud", "Marcar esta solicitud como la que esta atendiendo", "", "index.php?ruta=ordenes-servicio&idServicio=".$servicio["id"]."&servicioEstado=atendiendo");
    }

    if(!$equipoTaller && $servicio["estado_servicio"] == "atendiendo"){
      $acciones .= tmOrdenBoton("link", "tm-btn-info btnIniciarTrabajoOrden", "fa-check", "Iniciar trabajo", "Iniciar trabajo", "", "index.php?ruta=ordenes-servicio&idServicio=".$servicio["id"]."&servicioEstado=en_proceso");
    }

    if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "recibido_almacen" && ($servicio["estado_servicio"] ?? "") == "atendiendo"){
      $acciones .= tmOrdenBoton("link", "tm-btn-warning btnSolicitarRetiroOrden", "fa-sign-out", "Solicitar retiro", "Solicitar retiro de almacen", "", "index.php?ruta=ordenes-servicio&retirarEquipoTaller=".$servicio["id"]);
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "recibido_almacen"){
      $acciones .= '<span class="tm-action-note">Primero atender solicitud</span>';
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "retiro_solicitado"){
      $acciones .= '<span class="tm-action-note tm-note-warning">Esperando entrega de almacen</span>';
    }

    if($equipoTaller && (in_array(($equipoTaller["estado_equipo"] ?? ""), array("reparado")) || in_array(($servicio["estado_servicio"] ?? ""), array("reparado")))){
      $acciones .= tmOrdenBotonInformeFinalTaller($servicio, $equipoTaller, $resumenRepuestos, "Reparado, pendiente de devolver a almacen");
      $acciones .= tmOrdenBoton("link", "tm-btn-success btnDevolverAlmacenOrden", "fa-sign-in", "Devolver a almacen", "Devolver fisicamente el equipo reparado a almacen", "", "index.php?ruta=ordenes-servicio&enviarEquipoAlmacenTaller=".$servicio["id"]);
    }else if($equipoTaller && (in_array(($equipoTaller["estado_equipo"] ?? ""), array("rechazado")) || in_array(($servicio["estado_servicio"] ?? ""), array("devolucion_pend")))){
      $acciones .= tmOrdenBoton("link", "tm-btn-success btnDevolverAlmacenOrden", "fa-sign-in", "Devolver a almacen", "Devolver a almacen", "", "index.php?ruta=ordenes-servicio&enviarEquipoAlmacenTaller=".$servicio["id"]);
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "pendiente_reingreso"){
      $acciones .= '<span class="tm-action-note tm-note-success">Esperando recepcion de almacen</span>';
    }else if($equipoTaller && (in_array(($equipoTaller["estado_equipo"] ?? ""), array("devuelto_almacen", "entregado_cliente")) || in_array(($servicio["estado_servicio"] ?? ""), array("listo_cobro", "pagado_retiro", "completado")))){
      $estadoEsperaTaller = ($equipoTaller["estado_equipo"] ?? "") == "entregado_cliente" || ($servicio["estado_servicio"] ?? "") == "completado"
        ? "Equipo entregado al cliente"
        : ((($servicio["estado_pago"] ?? "") == "aprobado" || ($servicio["estado_servicio"] ?? "") == "pagado_retiro") ? "Cliente pagado, esperando retiro en almacen" : "Listo para caja, esperando cobro y retiro");
      $acciones .= tmOrdenBotonInformeFinalTaller($servicio, $equipoTaller, $resumenRepuestos, $estadoEsperaTaller);
      $acciones .= '<span class="tm-action-note tm-note-success">'.$estadoEsperaTaller.'</span>';
    }else if($equipoTaller && $servicio["estado_servicio"] != "completado" && in_array(($equipoTaller["estado_equipo"] ?? ""), array("retirado_tecnico", "diagnosticado", "autorizado"))){
      $acciones .= ' <button type="button" class="tm-order-btn tm-btn-info btnTallerServicio"
          title="Abrir taller"
          idServicio="'.$servicio["id"].'"
          codigoEquipo="'.tmOrdenEsc($equipoTaller["codigo_equipo"] ?? "").'"
          equipo="'.tmOrdenEsc(($equipoTaller["tipo_equipo"] ?? "")." ".($equipoTaller["marca"] ?? "")." ".($equipoTaller["modelo"] ?? "")).'"
          diagnostico="'.tmOrdenEsc($equipoTaller["diagnostico_tecnico"] ?? "").'"
          notificado="'.(int)($equipoTaller["notificado_cliente"] ?? 0).'"
          respuesta="'.tmOrdenEsc($equipoTaller["respuesta_cliente"] ?? "pendiente").'"
          detalle="'.tmOrdenEsc($equipoTaller["detalle_notificacion"] ?? "").'"
          reparacion="'.tmOrdenEsc($equipoTaller["reparacion_realizada"] ?? "").'"
          repuestos="'.tmOrdenEsc($equipoTaller["repuestos_detalle"] ?? "").'"
          garantia="'.tmOrdenEsc($equipoTaller["garantia_detalle"] ?? "").'"
          repuestosEntregados="'.($hayRepuestosEntregados ? "1" : "0").'"
          repuestosSolicitados="'.($hayRepuestosSolicitados ? "1" : "0").'"
          totalRepuestos="'.number_format($totalRepuestosEntregados, 2, ".", "").'"
          resumenRepuestos="'.tmOrdenEsc($resumenRepuestos).'"><i class="fa fa-laptop"></i> <span>Taller</span></button>';
      if(($equipoTaller["respuesta_cliente"] ?? "") == "conforme" && !$hayRepuestosEntregados && !$hayRepuestosSolicitados && empty($equipoTaller["reparacion_realizada"])){
        $acciones .= tmOrdenBoton("button", "tm-btn-primary btnSolicitarRepuestosTaller", "fa-cubes", "Solicitar repuestos", "Solicitar repuestos", 'idServicio="'.$servicio["id"].'"');
      }else if($hayRepuestosSolicitados){
        $acciones .= '<span class="tm-action-note tm-note-warning">Esperando repuestos de almacen</span>';
      }
    }else if($equipoTaller && ($equipoTaller["estado_equipo"] ?? "") == "ingresado"){
      $acciones .= '<span class="tm-action-note tm-note-warning">Entregar a almacen</span>';
    }else if($equipoTaller && $servicio["estado_servicio"] == "rep_solicitado"){
      $acciones .= '<span class="tm-action-note tm-note-warning">Esperando repuestos de almacen</span>';
    }else if($equipoTaller && $servicio["estado_servicio"] == "rep_entregado"){
      $acciones .= ' <button type="button" class="tm-order-btn tm-btn-info btnTallerServicio"
          title="Abrir taller"
          idServicio="'.$servicio["id"].'"
          codigoEquipo="'.tmOrdenEsc($equipoTaller["codigo_equipo"] ?? "").'"
          equipo="'.tmOrdenEsc(($equipoTaller["tipo_equipo"] ?? "")." ".($equipoTaller["marca"] ?? "")." ".($equipoTaller["modelo"] ?? "")).'"
          diagnostico="'.tmOrdenEsc($equipoTaller["diagnostico_tecnico"] ?? "").'"
          notificado="'.(int)($equipoTaller["notificado_cliente"] ?? 0).'"
          respuesta="'.tmOrdenEsc($equipoTaller["respuesta_cliente"] ?? "pendiente").'"
          detalle="'.tmOrdenEsc($equipoTaller["detalle_notificacion"] ?? "").'"
          reparacion="'.tmOrdenEsc($equipoTaller["reparacion_realizada"] ?? "").'"
          repuestos="'.tmOrdenEsc($equipoTaller["repuestos_detalle"] ?? "").'"
          garantia="'.tmOrdenEsc($equipoTaller["garantia_detalle"] ?? "").'"
          repuestosEntregados="1"
          repuestosSolicitados="0"
          totalRepuestos="'.number_format($totalRepuestosEntregados, 2, ".", "").'"
          resumenRepuestos="'.tmOrdenEsc($resumenRepuestos).'"><i class="fa fa-laptop"></i> <span>Taller</span></button>';
    }else if(!$equipoTaller && $servicio["estado_servicio"] == "en_proceso"){
      $acciones .= ' <button type="button" class="tm-order-btn tm-btn-success btnInformeServicio"
          title="Concluir trabajo con informe"
          idServicio="'.$servicio["id"].'"
          codigoServicio="'.tmOrdenEsc($servicio["codigo"] ?? "").'"
          tipoServicio="'.tmOrdenEsc($servicio["tipo_servicio"] ?? "").'"
          direccionServicio="'.tmOrdenEsc($servicio["direccion_instalacion"] ?? "").'"
          hallazgos="'.tmOrdenEsc($servicio["hallazgos_tecnicos"] ?? "").'"
          trabajo="'.tmOrdenEsc($servicio["trabajo_realizado"] ?? "").'"
          recomendaciones="'.tmOrdenEsc($servicio["recomendaciones"] ?? "").'"><i class="fa fa-check-circle"></i> <span>Concluir trabajo</span></button>';
    }

    return $acciones;
}

function renderTarjetasServiciosTecnicos($lista, $mostrarTecnico, $etapaTitulo = "Orden tecnica"){
  global $tmOrdenPuedeVerMontos;
  if(count($lista) == 0){
    echo '<div class="tm-order-empty"><i class="fa fa-check-circle"></i><h3>Sin ordenes en esta pestana</h3><p>No hay solicitudes para mostrar en este estado.</p></div>';
    return;
  }
  foreach($lista as $indiceOrden => $servicio){
    $ctx = tmOrdenContexto($servicio, $mostrarTecnico);
    $cliente = $ctx["cliente"];
    $tecnico = $ctx["tecnico"];
    $estadoClase = $ctx["estadoClase"];
    $acciones = tmOrdenAcciones($ctx);
    $pasoTexto = "Abra el detalle para revisar la orden y sus documentos.";
    if(strpos($acciones, "btnAtenderOrden") !== false){
      $pasoTexto = "Primero tome la orden con el boton Atender solicitud.";
    }else if(strpos($acciones, "btnSolicitarRetiroOrden") !== false){
      $pasoTexto = "Solicite el retiro del equipo desde almacen.";
    }else if(strpos($acciones, "btnTallerServicio") !== false){
      $pasoTexto = "Continue el registro tecnico en Taller.";
    }else if(strpos($acciones, "btnSolicitarRepuestosTaller") !== false){
      $pasoTexto = "Solicite los repuestos necesarios a almacen.";
    }else if(strpos($acciones, "btnDevolverAlmacenOrden") !== false){
      $pasoTexto = "La reparacion termino. Devuelva fisicamente el equipo a almacen cuando corresponda.";
    }else if(strpos($acciones, "btnInformeFinalTaller") !== false){
      $pasoTexto = "El informe tecnico ya esta cerrado. Revise el detalle y espere cobro/retiro del cliente.";
    }else if(strpos($acciones, "btnInformeServicio") !== false){
      $pasoTexto = "Registre el informe tecnico del servicio.";
    }
    $clienteNombre = $cliente["nombre"] ?? "Sin cliente";
    $telefono = $cliente["telefono"] ?? "-";
    $tecnicoNombre = $mostrarTecnico ? ($tecnico["nombre"] ?? "Sin tecnico") : ($_SESSION["nombre"] ?? "Tecnico");
    $total = number_format((float)($servicio["total"] ?? 0), 2);
    $fecha = $servicio["fecha"] ?? ($servicio["fecha_creacion"] ?? "-");
    $esCampo = $etapaTitulo == "Campo / instalacion";
    $numeroCola = $esCampo ? ($indiceOrden + 1) : 0;
    $estadoFlujo = ($servicio["estado_servicio"] ?? "") === "en_proceso"
      ? "Trabajo iniciado"
      : ((($servicio["estado_servicio"] ?? "") === "atendiendo") ? "Pendiente de iniciar" : $ctx["estadoTexto"]);
    $flujoCampoClase = ($servicio["estado_servicio"] ?? "") === "en_proceso" ? "is-running" : "is-ready";
    $referenciaCampo = trim((string)($servicio["referencia"] ?? ""));
    $referenciaCampo = $referenciaCampo !== "" ? $referenciaCampo : "Sin referencia adicional";
    $alcanceCampo = array();
    if((int)($servicio["cantidad_camaras"] ?? 0) > 0){
      $alcanceCampo[] = (int)$servicio["cantidad_camaras"]." equipo(s)";
    }
    if((float)($servicio["metros_distancia"] ?? 0) > 0){
      $alcanceCampo[] = number_format((float)$servicio["metros_distancia"], 0)." m estimados";
    }
    $alcanceCampo = count($alcanceCampo) ? implode(" · ", $alcanceCampo) : ($servicio["tipo_instalacion"] ?? "Trabajo en campo");
    $search = strtolower($servicio["codigo"]." ".$clienteNombre." ".$telefono." ".$tecnicoNombre." ".$servicio["tipo_servicio"]." ".$ctx["direccionServicio"]." ".$ctx["estadoTexto"]);
    echo '<article class="tm-order-card '.($esCampo ? 'tm-field-card '.$flujoCampoClase : '').' tm-state-'.$estadoClase.'" data-search="'.tmOrdenEsc($search).'"
        data-codigo="'.tmOrdenEsc($servicio["codigo"]).'"
        data-cliente="'.tmOrdenEsc($clienteNombre).'"
        data-telefono="'.tmOrdenEsc($telefono).'"
        data-tecnico="'.tmOrdenEsc($tecnicoNombre).'"
        data-servicio="'.tmOrdenEsc($servicio["tipo_servicio"] ?? "-").'"
        data-direccion="'.tmOrdenEsc($ctx["direccionServicio"]).'"
        data-estado="'.tmOrdenEsc($ctx["estadoTexto"]).'"
        data-etapa="'.tmOrdenEsc($etapaTitulo).'"
        data-paso="'.tmOrdenEsc($pasoTexto).'"
        data-cola="'.(int)$numeroCola.'"
        data-flujo="'.tmOrdenEsc($estadoFlujo).'"
        data-referencia="'.tmOrdenEsc($servicio["referencia"] ?? "-").'"
        data-preguntas="'.tmOrdenEsc($servicio["preguntas_cliente"] ?? "-").'"
        data-diagnostico-inicial="'.tmOrdenEsc($servicio["diagnostico_inicial"] ?? "-").'"
        data-observaciones="'.tmOrdenEsc($servicio["observaciones"] ?? "-").'"
        data-cantidad="'.tmOrdenEsc($servicio["cantidad_camaras"] ?? "0").'"
        data-metros="'.tmOrdenEsc($servicio["metros_distancia"] ?? "0").'"
        '.($tmOrdenPuedeVerMontos ? 'data-total="Bs '.$total.'"' : 'data-total="Trabajo tecnico"').'
        data-fecha="'.tmOrdenEsc($fecha).'">
      <div class="tm-order-card-head">
        <div class="tm-order-topline">
          <span class="tm-order-code"><i class="fa fa-file-text-o"></i> '.$servicio["codigo"].'</span>
          '.($esCampo ? '<span class="tm-order-queue"><b>#'.$numeroCola.'</b> en la ruta</span>' : '').'
          <span class="tm-order-status tm-badge-'.$estadoClase.'">'.tmOrdenEsc($ctx["estadoTexto"]).'</span>
        </div>
        <div class="tm-order-title-row">
          <div class="tm-order-icon"><i class="fa fa-wrench"></i></div>
          <div class="tm-order-main">
            <h3>'.tmOrdenEsc($clienteNombre).'</h3>
            <p>'.tmOrdenEsc($servicio["tipo_servicio"] ?? "Servicio").'</p>
          </div>
        </div>
      </div>
      '.($esCampo ? '
      <div class="tm-field-progress">
        <span class="is-done"><i class="fa fa-check"></i><b>Orden tomada</b></span>
        <i class="fa fa-angle-right"></i>
        <span class="'.(($servicio["estado_servicio"] ?? "") === "en_proceso" ? "is-done" : "is-current").'"><i class="fa '.(($servicio["estado_servicio"] ?? "") === "en_proceso" ? "fa-check" : "fa-play").'"></i><b>Trabajo</b></span>
        <i class="fa fa-angle-right"></i>
        <span class="'.(($servicio["estado_servicio"] ?? "") === "en_proceso" ? "is-current" : "").'"><i class="fa fa-clipboard"></i><b>Informe</b></span>
      </div>
      <div class="tm-field-summary">
        <div class="tm-field-next"><i class="fa fa-arrow-circle-right"></i><span><b>Siguiente paso</b>'.tmOrdenEsc($pasoTexto).'</span></div>
        <div class="tm-field-scope"><i class="fa fa-wrench"></i><span><b>Alcance</b>'.tmOrdenEsc($alcanceCampo).'</span></div>
      </div>
      <div class="tm-field-contact">
        <a href="tel:'.tmOrdenEsc($telefono).'"><i class="fa fa-phone"></i><span><b>Cliente</b>'.tmOrdenEsc($telefono).'</span></a>
        <span><i class="fa fa-user"></i><span><b>Tecnico</b>'.tmOrdenEsc($tecnicoNombre).'</span></span>
      </div>
      <div class="tm-field-location">
        <i class="fa fa-map-marker"></i>
        <span><b>'.tmOrdenEsc($ctx["direccionServicio"]).'</b><small>'.tmOrdenEsc($referenciaCampo).'</small></span>
      </div>' : '
      <div class="tm-order-stage"><i class="fa fa-folder-open-o"></i><span>'.tmOrdenEsc($etapaTitulo).'</span></div>
      <div class="tm-order-step"><b>Siguiente paso</b><span>'.tmOrdenEsc($pasoTexto).'</span></div>
      <div class="tm-order-data">
        <span><i class="fa fa-phone"></i><b>Telefono</b>'.tmOrdenEsc($telefono).'</span>
        <span><i class="fa fa-user"></i><b>Tecnico</b>'.tmOrdenEsc($tecnicoNombre).'</span>
        '.($tmOrdenPuedeVerMontos ? '<span><i class="fa fa-money"></i><b>Total</b>Bs '.$total.'</span>' : '<span><i class="fa fa-tasks"></i><b>Enfoque</b>Trabajo tecnico</span>').'
      </div>
      <div class="tm-order-address"><i class="fa fa-map-marker"></i><span>'.tmOrdenEsc($ctx["direccionServicio"]).'</span></div>').'
      <div class="tm-order-actions">'.$acciones.'</div>
      <div class="tm-order-actions-template">'.$acciones.'</div>
    </article>';
  }
}
?>

<?php if($requiereMapaOrden): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<style>
  .tm-order-page{
    color:#14213d;
  }
  .tm-order-hero{
    background:linear-gradient(135deg,#0f3d67,#1597d4);
    color:#fff;
    border-radius:14px;
    padding:20px 22px;
    margin-bottom:16px;
    display:flex;
    justify-content:space-between;
    gap:14px;
    align-items:center;
    box-shadow:0 18px 35px rgba(16,64,112,.16);
  }
  .tm-order-hero h1{
    margin:0 0 5px;
    font-size:25px;
    font-weight:800;
  }
  .tm-order-hero p{
    margin:0;
    opacity:.9;
  }
  .tm-order-metrics{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }
  .tm-order-metric{
    min-width:120px;
    background:rgba(255,255,255,.16);
    border:1px solid rgba(255,255,255,.28);
    border-radius:12px;
    padding:10px 12px;
    text-align:right;
  }
  .tm-order-metric strong{
    display:block;
    font-size:24px;
    line-height:1;
  }
  .tm-order-board{
    background:rgba(255,255,255,.82);
    border:1px solid rgba(31,96,150,.14);
    border-radius:14px;
    box-shadow:0 12px 28px rgba(24,64,105,.08);
    padding:14px;
  }
  .tm-order-toolbar{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    margin-bottom:12px;
  }
  .tm-order-toolbar h3{
    margin:0;
    font-size:18px;
    font-weight:800;
  }
  .tm-order-search{
    max-width:360px;
    width:100%;
    position:relative;
  }
  .tm-order-search i{
    position:absolute;
    left:13px;
    top:50%;
    transform:translateY(-50%);
    color:#3b82c4;
  }
  .tm-order-search input{
    border:1px solid #d9e7f5;
    border-radius:10px;
    height:40px;
    padding-left:36px;
    box-shadow:none;
  }
  .tm-order-tabs{
    display:flex;
    gap:8px;
    margin:0 0 14px;
    padding:0;
    border:0;
    flex-wrap:wrap;
  }
  .tm-order-tabs>li>a{
    border:1px solid #dbe8f6 !important;
    border-radius:999px !important;
    color:#1f4e7d;
    font-weight:800;
    background:#f7fbff;
    padding:8px 14px;
  }
  .tm-order-tabs>li.active>a,
  .tm-order-tabs>li.active>a:focus,
  .tm-order-tabs>li.active>a:hover{
    color:#fff;
    background:#168fd0;
    border-color:#168fd0 !important;
  }
  .tm-order-count{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:22px;
    height:22px;
    margin-left:6px;
    border-radius:999px;
    background:rgba(0,0,0,.12);
    font-size:12px;
  }
  .tm-order-lane-info{
    margin:0 0 12px;
    padding:12px 14px;
    border:1px solid #d5e8f8;
    border-radius:13px;
    background:linear-gradient(135deg,rgba(232,246,255,.92),rgba(255,255,255,.82));
    display:flex;
    gap:11px;
    align-items:center;
    color:#244967;
  }
  .tm-order-lane-info>i{
    width:38px;
    height:38px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    background:linear-gradient(135deg,#1262a3,#18a3dc);
    box-shadow:0 10px 18px rgba(21,151,212,.18);
  }
  .tm-order-lane-info strong{
    display:block;
    font-size:15px;
    font-weight:900;
    color:#10233f;
  }
  .tm-order-lane-info span{
    display:block;
    font-weight:700;
    color:#60778f;
    line-height:1.3;
  }
  .tm-order-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(285px,1fr));
    gap:14px;
  }
  .tm-order-card{
    background:rgba(255,255,255,.92);
    border:1px solid #dce9f7;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 12px 24px rgba(26,67,112,.08);
    cursor:pointer;
    transition:.18s ease;
    min-height:340px;
    display:flex;
    flex-direction:column;
  }
  .tm-order-card:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 34px rgba(26,67,112,.14);
    border-color:#9fc8ef;
  }
  .tm-field-grid{
    grid-template-columns:repeat(auto-fit,minmax(420px,1fr));
  }
  .tm-field-card{
    min-height:0;
    border-radius:18px;
    border-color:#cfe2f3;
    box-shadow:0 14px 30px rgba(26,67,112,.10);
  }
  .tm-field-card.is-running{
    border-color:#9edbc4;
    box-shadow:0 14px 30px rgba(12,168,106,.10);
  }
  .tm-field-card .tm-order-card-head{
    padding:14px 16px;
    background:linear-gradient(135deg,#f0f8ff 0%,#fff 68%);
  }
  .tm-field-card.is-running .tm-order-card-head{
    background:linear-gradient(135deg,#ecfdf5 0%,#fff 68%);
  }
  .tm-field-card .tm-order-topline{
    margin-bottom:12px;
    flex-wrap:nowrap;
    align-items:center;
  }
  .tm-field-card .tm-order-queue{
    margin-right:0;
  }
  .tm-field-card .tm-order-status{
    margin-left:auto;
    max-width:none;
    padding-left:11px;
    padding-right:11px;
  }
  .tm-field-card .tm-order-title-row{
    grid-template-columns:48px minmax(0,1fr);
  }
  .tm-field-card .tm-order-icon{
    width:44px;
    height:44px;
    border-radius:14px;
    font-size:17px;
  }
  .tm-field-card.is-running .tm-order-icon{
    background:linear-gradient(135deg,#07875a,#21bd83);
    box-shadow:0 8px 18px rgba(12,168,106,.22);
  }
  .tm-field-card .tm-order-main h3{
    min-height:0;
    margin:0 0 3px;
    font-size:17px;
  }
  .tm-field-card .tm-order-main p{
    font-size:12px;
  }
  .tm-field-progress{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    padding:12px 16px 4px;
    color:#9aa9b8;
  }
  .tm-field-progress>i{
    color:#c4d1dc;
    font-size:16px;
  }
  .tm-field-progress span{
    display:inline-flex;
    align-items:center;
    gap:5px;
    font-size:10px;
    text-transform:uppercase;
    font-weight:900;
    white-space:nowrap;
  }
  .tm-field-progress span i{
    width:23px;
    height:23px;
    border-radius:50%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:#edf2f7;
  }
  .tm-field-progress .is-done{color:#0b8f61;}
  .tm-field-progress .is-done i{background:#dcfce7;}
  .tm-field-progress .is-current{color:#167fc0;}
  .tm-field-progress .is-current i{
    color:#fff;
    background:#168fd0;
    box-shadow:0 5px 12px rgba(22,143,208,.24);
  }
  .tm-field-summary{
    display:grid;
    grid-template-columns:minmax(0,1.35fr) minmax(150px,.65fr);
    gap:8px;
    padding:10px 16px 8px;
  }
  .tm-field-next,
  .tm-field-scope{
    display:flex;
    gap:9px;
    align-items:flex-start;
    border-radius:12px;
    padding:10px;
    min-width:0;
  }
  .tm-field-next{
    background:#eaf6ff;
    border:1px solid #d2eafa;
    color:#1f426b;
  }
  .tm-field-scope{
    background:#f7f9fc;
    border:1px solid #e3eaf2;
    color:#41566f;
  }
  .tm-field-next>i,
  .tm-field-scope>i{
    margin-top:2px;
    color:#168fd0;
  }
  .tm-field-next span,
  .tm-field-scope span{
    min-width:0;
    font-size:11px;
    line-height:1.3;
    font-weight:800;
  }
  .tm-field-next b,
  .tm-field-scope b,
  .tm-field-contact b{
    display:block;
    margin-bottom:2px;
    color:#71869b;
    font-size:9px;
    text-transform:uppercase;
  }
  .tm-field-contact{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    padding:0 16px 8px;
  }
  .tm-field-contact>a,
  .tm-field-contact>span{
    display:flex;
    align-items:center;
    gap:9px;
    min-width:0;
    border:1px solid #e3ebf4;
    border-radius:11px;
    padding:8px 10px;
    color:#244967;
    background:#fff;
    font-size:11px;
    font-weight:900;
  }
  .tm-field-contact>a:hover{
    border-color:#9fc8ef;
    text-decoration:none;
  }
  .tm-field-contact>*>i{
    color:#168fd0;
    font-size:14px;
  }
  .tm-field-location{
    margin:0 16px 10px;
    display:flex;
    gap:10px;
    align-items:flex-start;
    border-left:3px solid #168fd0;
    border-radius:4px 11px 11px 4px;
    padding:9px 11px;
    color:#344b65;
    background:#f8fbff;
  }
  .tm-field-location>i{
    margin-top:3px;
    color:#168fd0;
    font-size:15px;
  }
  .tm-field-location span{min-width:0;}
  .tm-field-location b,
  .tm-field-location small{
    display:block;
    overflow-wrap:anywhere;
  }
  .tm-field-location b{
    color:#183753;
    font-size:12px;
    line-height:1.3;
  }
  .tm-field-location small{
    margin-top:3px;
    color:#71869b;
    font-size:10px;
  }
  .tm-order-card-head{
    display:block;
    padding:12px;
    background:linear-gradient(135deg,#eef8ff,#fff);
    border-bottom:1px solid #e3edf8;
  }
  .tm-order-topline{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:8px;
    flex-wrap:wrap;
    margin-bottom:10px;
  }
  .tm-order-queue{
    margin-right:auto;
    display:inline-flex;
    align-items:center;
    gap:5px;
    border-radius:999px;
    padding:4px 8px;
    background:#fff4d8;
    border:1px solid #f5d58c;
    color:#8a5a00;
    font-size:10px;
    font-weight:900;
  }
  .tm-order-queue b{font-size:12px;}
  .tm-order-title-row{
    display:grid;
    grid-template-columns:42px minmax(0,1fr);
    gap:10px;
    align-items:center;
  }
  .tm-order-icon{
    width:38px;
    height:38px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#0f78bd,#25b7e8);
    color:#fff;
    box-shadow:0 8px 18px rgba(21,151,212,.25);
  }
  .tm-order-main{
    min-width:0;
  }
  .tm-order-code{
    display:inline-flex;
    max-width:100%;
    padding:3px 8px;
    border-radius:999px;
    background:#e8f3ff;
    color:#16558c;
    font-size:11px;
    font-weight:800;
    white-space:nowrap;
    align-items:center;
    gap:5px;
  }
  .tm-order-main h3{
    margin:6px 0 2px;
    font-size:14px;
    line-height:1.25;
    font-weight:900;
    color:#10233f;
    min-height:34px;
  }
  .tm-order-main p{
    margin:0;
    color:#5b718b;
    font-size:12px;
    font-weight:700;
  }
  .tm-order-stage{
    margin:10px 12px 0;
    display:flex;
    align-items:center;
    gap:7px;
    color:#155c91;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.02em;
  }
  .tm-order-stage i{
    color:#18a3dc;
  }
  .tm-order-status{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    max-width:136px;
    min-height:24px;
    border-radius:999px;
    padding:5px 8px;
    color:#fff;
    font-size:10px;
    line-height:1.1;
    text-align:center;
    font-weight:900;
    text-transform:uppercase;
    overflow-wrap:anywhere;
  }
  .tm-badge-success{background:#0ca86a;}
  .tm-badge-warning{background:#f59e0b;}
  .tm-badge-danger{background:#e74c3c;}
  .tm-badge-info{background:#14a8d8;}
  .tm-badge-default{background:#64748b;}
  .tm-order-data{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:7px;
    padding:10px 12px;
  }
  .tm-order-step{
    margin:10px 12px 0;
    padding:10px 11px;
    border-radius:12px;
    background:linear-gradient(135deg,#e8f6ff,#f8fbff);
    border:1px solid #d7eafa;
    color:#1f426b;
    display:flex;
    gap:8px;
    align-items:flex-start;
  }
  .tm-order-step b{
    flex:0 0 auto;
    font-size:10px;
    text-transform:uppercase;
    color:#168fd0;
  }
  .tm-order-step span{
    min-width:0;
    font-size:12px;
    font-weight:800;
    line-height:1.25;
  }
  .tm-order-data span{
    background:#f6faff;
    border:1px solid #e2edf8;
    border-radius:10px;
    padding:7px;
    min-width:0;
    color:#1e3555;
    font-size:11px;
    font-weight:800;
    overflow-wrap:anywhere;
  }
  .tm-order-data i{
    color:#168fd0;
    margin-right:4px;
  }
  .tm-order-data b{
    display:block;
    color:#70849a;
    font-size:9px;
    text-transform:uppercase;
    margin-bottom:2px;
  }
  .tm-order-address{
    margin:0 12px 10px;
    background:#f8fbff;
    border:1px solid #e2edf8;
    border-radius:10px;
    padding:8px;
    display:flex;
    gap:7px;
    color:#41566f;
    font-size:12px;
    line-height:1.25;
    min-height:48px;
    overflow-wrap:anywhere;
  }
  .tm-order-address i{color:#168fd0;margin-top:2px;}
  .tm-order-actions{
    margin-top:auto;
    padding:10px 12px 12px;
    border-top:1px solid #edf3fb;
    display:flex;
    flex-wrap:wrap;
    gap:6px;
  }
  .tm-order-actions-template{
    display:none;
  }
  .tm-order-btn{
    border:0;
    border-radius:9px;
    padding:7px 9px;
    color:#fff;
    font-size:11px;
    font-weight:900;
    display:inline-flex;
    align-items:center;
    gap:4px;
    line-height:1;
    white-space:nowrap;
    box-shadow:0 7px 14px rgba(20,88,140,.12);
  }
  .tm-order-card .btnAtenderOrden,
  .tm-order-card .btnIniciarTrabajoOrden,
  .tm-order-card .btnSolicitarRetiroOrden,
  .tm-order-card .btnTallerServicio,
  .tm-order-card .btnSolicitarRepuestosTaller,
  .tm-order-card .btnInformeFinalTaller,
  .tm-order-card .btnInformeServicio,
  .tm-order-card .btnDevolverAlmacenOrden{
    order:-5;
    flex:1 1 100%;
    justify-content:center;
    padding:10px 12px;
    font-size:12px;
    border-radius:11px;
  }
  .tm-order-card .btnImprimirOrdenServicio,
  .tm-order-card .btnImprimirIngresoEquipo,
  .tm-order-card .btnImprimirTaller,
  .tm-order-card .btnVerMapaServicio,
  .tm-order-card .tm-order-actions>a[target="_blank"]{
    flex:1 1 calc(50% - 5px);
    justify-content:center;
  }
  .tm-field-card .tm-order-actions{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:7px;
    padding:11px 16px 14px;
    background:#fbfdff;
  }
  .tm-field-card .tm-order-actions .tm-order-btn{
    width:100%;
    min-width:0;
    min-height:35px;
    justify-content:center;
    padding:8px 7px;
    white-space:normal;
    text-align:center;
  }
  .tm-field-card .tm-order-actions .btnIniciarTrabajoOrden,
  .tm-field-card .tm-order-actions .btnInformeServicio{
    grid-column:1/-1;
    order:-10;
    min-height:42px;
    font-size:12px;
  }
  .tm-field-card .tm-order-actions .btnImprimirConformidadInstalacion{
    grid-column:span 2;
  }
  .tm-field-card .tm-order-actions .btnImprimirOrdenServicio{
    grid-column:span 2;
  }
  .tm-order-btn:hover,
  .tm-order-btn:focus{
    color:#fff;
    text-decoration:none;
    filter:brightness(.96);
  }
  .tm-btn-neutral{background:#64748b;}
  .tm-btn-primary{background:#167fc0;}
  .tm-btn-info{background:#16a7d8;}
  .tm-btn-warning{background:#f59e0b;}
  .tm-btn-success{background:#0ca86a;}
  .tm-action-note{
    border-radius:999px;
    padding:7px 9px;
    background:#eef2f7;
    color:#475569;
    font-size:11px;
    font-weight:900;
  }
  .tm-note-warning{background:#fff2d8;color:#9a6100;}
  .tm-note-success{background:#dcfce7;color:#166534;}
  .tm-order-empty{
    grid-column:1/-1;
    border:1px dashed #b9d5ee;
    border-radius:14px;
    padding:34px;
    text-align:center;
    color:#5b718b;
    background:rgba(255,255,255,.7);
  }
  .tm-order-empty i{
    font-size:34px;
    color:#168fd0;
  }
  .tm-order-empty h3{
    margin:10px 0 5px;
    font-size:18px;
    font-weight:900;
  }
  .tm-order-detail .modal-dialog{
    width:min(860px,94vw);
  }
  .tm-order-detail .modal-content{
    border:0;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 25px 60px rgba(12,36,68,.28);
  }
  .tm-order-detail-head{
    background:linear-gradient(135deg,#0f3d67,#18a3dc);
    color:#fff;
    padding:14px 16px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:14px;
  }
  .tm-order-detail-head h3{
    margin:4px 0 3px;
    font-size:20px;
    font-weight:900;
  }
  .tm-order-detail-head p{
    margin:0;
    opacity:.92;
  }
  .tm-order-detail-close{
    border:0;
    width:34px;
    height:34px;
    border-radius:50%;
    color:#fff;
    background:rgba(255,255,255,.2);
    font-size:18px;
  }
  .tm-order-detail-body{
    padding:14px;
    background:#f7fbff;
  }
  .tm-field-route{
    display:none;
    margin-bottom:12px;
    border:1px solid #d8e9f8;
    border-radius:14px;
    background:#fff;
    padding:12px;
  }
  .tm-field-route-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:10px;
  }
  .tm-field-route-head h4{
    margin:0;
    color:#102b4c;
    font-weight:900;
  }
  .tm-field-route-position{
    border-radius:999px;
    background:#0f76bd;
    color:#fff;
    padding:6px 10px;
    font-size:11px;
    font-weight:900;
  }
  .tm-field-route-steps{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:7px;
  }
  .tm-field-route-step{
    border:1px solid #dfeaf5;
    border-radius:10px;
    padding:8px;
    color:#6b7f95;
    background:#f7fbff;
    font-size:10px;
    font-weight:900;
    text-align:center;
  }
  .tm-field-route-step.is-done{
    color:#08764b;
    border-color:#a9e3c8;
    background:#eafaf3;
  }
  .tm-field-route-step.is-current{
    color:#8a5800;
    border-color:#f3cf81;
    background:#fff6df;
    box-shadow:0 0 0 2px rgba(245,166,35,.1);
  }
  .tm-field-brief{
    display:none;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    margin-bottom:12px;
  }
  .tm-field-brief article{
    border:1px solid #e0ebf6;
    border-radius:12px;
    background:#fff;
    padding:10px;
    min-height:72px;
  }
  .tm-field-brief span{
    display:block;
    color:#6c8198;
    font-size:10px;
    font-weight:900;
    text-transform:uppercase;
    margin-bottom:4px;
  }
  .tm-field-brief strong{
    display:block;
    color:#14213d;
    font-size:12px;
    line-height:1.35;
    white-space:pre-line;
    overflow-wrap:anywhere;
  }
  .tm-order-detail-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
    margin-bottom:12px;
  }
  .tm-order-detail-box{
    background:#fff;
    border:1px solid #e2edf8;
    border-radius:12px;
    padding:10px;
    min-height:70px;
    overflow-wrap:anywhere;
  }
  .tm-order-detail-box b{
    display:block;
    color:#6a7f99;
    font-size:11px;
    text-transform:uppercase;
    margin-bottom:4px;
  }
  .tm-order-detail-address{
    background:#fff;
    border:1px solid #e2edf8;
    border-radius:12px;
    padding:12px;
    margin-bottom:12px;
    overflow-wrap:anywhere;
  }
  #detalleOrdenAcciones{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
  }
  .tm-detail-action-panel{
    background:#fff;
    border:1px solid #d8e8f7;
    border-radius:14px;
    padding:12px;
    margin-bottom:12px;
    box-shadow:0 10px 20px rgba(26,67,112,.06);
  }
  .tm-detail-action-panel h4{
    margin:0 0 4px;
    font-size:15px;
    font-weight:900;
    color:#10233f;
  }
  .tm-detail-action-panel p{
    margin:0 0 10px;
    color:#5b718b;
    font-weight:700;
  }
  .tm-detail-action-panel .tm-order-btn{
    padding:9px 12px;
    font-size:12px;
  }
  .tm-detail-action-panel .btnAtenderOrden,
  .tm-detail-action-panel .btnIniciarTrabajoOrden,
  .tm-detail-action-panel .btnSolicitarRetiroOrden,
  .tm-detail-action-panel .btnTallerServicio,
  .tm-detail-action-panel .btnSolicitarRepuestosTaller,
  .tm-detail-action-panel .btnInformeFinalTaller,
  .tm-detail-action-panel .btnInformeServicio,
  .tm-detail-action-panel .btnDevolverAlmacenOrden{
    min-width:190px;
    justify-content:center;
  }
  .tm-service-modal .modal-dialog{
    width:min(980px,94vw);
  }
  .tm-final-report-head{
    background:linear-gradient(135deg,#0f4c81,#159bd3);
    color:#fff;
    border-radius:6px 6px 0 0;
    padding:16px 18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
  }
  .tm-final-report-head h4{
    margin:0;
    font-weight:900;
  }
  .tm-final-report-head p{
    margin:3px 0 0;
    opacity:.9;
    font-weight:700;
  }
  .tm-final-report-status{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 12px;
    border-radius:999px;
    background:rgba(255,255,255,.18);
    font-weight:900;
    white-space:normal;
    text-align:right;
  }
  .tm-final-report-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
  }
  .tm-final-report-box{
    border:1px solid rgba(184,205,232,.8);
    border-radius:12px;
    background:rgba(248,252,255,.9);
    padding:11px;
    min-height:78px;
  }
  .tm-final-report-box span{
    display:block;
    font-size:10px;
    color:#64748b;
    font-weight:900;
    text-transform:uppercase;
    margin-bottom:5px;
  }
  .tm-final-report-box strong{
    display:block;
    color:#172033;
    font-size:13px;
    line-height:1.35;
    white-space:pre-line;
    overflow-wrap:anywhere;
  }
  .tm-final-report-evidence{
    margin-top:12px;
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(105px,1fr));
    gap:10px;
  }
  .tm-final-report-evidence a{
    display:block;
    border:1px solid rgba(184,205,232,.85);
    background:#fff;
    border-radius:12px;
    padding:5px;
  }
  .tm-final-report-evidence img{
    width:100%;
    height:86px;
    object-fit:cover;
    border-radius:9px;
    display:block;
  }
  body.tm-dark-mode .tm-final-report-box{
    background:rgba(15,27,48,.82);
    border-color:rgba(147,197,253,.22);
  }
  body.tm-dark-mode .tm-final-report-box strong{color:#fff;}
  .tm-field-report .modal-dialog{
    width:min(820px,94vw);
  }
  .tm-field-report .modal-content{
    border:0;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 26px 65px rgba(10,35,67,.32);
  }
  .tm-field-report-head{
    padding:16px 18px;
    color:#fff;
    background:linear-gradient(135deg,#0f4c81,#16a0d5);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
  }
  .tm-field-report-head h4{
    margin:0;
    font-weight:900;
    font-size:20px;
  }
  .tm-field-report-head p{
    margin:4px 0 0;
    opacity:.9;
  }
  .tm-field-report-code{
    background:rgba(255,255,255,.18);
    border:1px solid rgba(255,255,255,.25);
    padding:7px 11px;
    border-radius:999px;
    font-weight:900;
  }
  .tm-field-report .modal-body{
    background:#f6faff;
    padding:15px;
  }
  .tm-field-report-summary{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:9px;
    margin-bottom:12px;
  }
  .tm-field-report-summary div{
    border:1px solid #dce9f6;
    border-radius:11px;
    background:#fff;
    padding:9px;
  }
  .tm-field-report-summary span{
    display:block;
    color:#6b8098;
    font-size:10px;
    text-transform:uppercase;
    font-weight:900;
  }
  .tm-field-report-summary strong{
    display:block;
    color:#14213d;
    margin-top:3px;
  }
  .tm-field-report-section{
    border:1px solid #dce9f6;
    border-radius:13px;
    background:#fff;
    padding:12px;
    margin-bottom:10px;
  }
  .tm-field-report-section h5{
    margin:0 0 8px;
    font-size:13px;
    color:#123454;
    font-weight:900;
  }
  .tm-field-report-section textarea{
    resize:vertical;
    min-height:88px;
    border-radius:10px;
  }
  .tm-field-signed-upload{
    border-color:#b9dff3;
    background:linear-gradient(135deg,#f1f9ff,#fff);
  }
  .tm-field-file-box{
    margin:0;
    width:100%;
    min-height:76px;
    border:2px dashed #8fc8e8;
    border-radius:12px;
    padding:12px;
    display:flex;
    align-items:center;
    gap:12px;
    color:#285b7f;
    background:rgba(255,255,255,.8);
    cursor:pointer;
    transition:.18s ease;
  }
  .tm-field-file-box:hover{
    border-color:#168fd0;
    background:#fff;
  }
  .tm-field-file-box>i{
    width:42px;
    height:42px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    background:linear-gradient(135deg,#167fc0,#20addf);
    font-size:18px;
  }
  .tm-field-file-box span{min-width:0;}
  .tm-field-file-box b,
  .tm-field-file-box small{
    display:block;
    overflow-wrap:anywhere;
  }
  .tm-field-file-box b{
    color:#163d5c;
    font-size:12px;
  }
  .tm-field-file-box small{
    margin-top:3px;
    color:#668097;
    font-weight:700;
  }
  #boletaConformidadFirmada{
    position:absolute;
    width:1px;
    height:1px;
    opacity:0;
    overflow:hidden;
  }
  .tm-field-report-confirm{
    border:1px solid #b8e4ce;
    background:#edfbf4;
    border-radius:12px;
    padding:10px 12px;
    color:#176443;
    font-weight:800;
  }
  body.tm-dark-mode .tm-field-route,
  body.tm-dark-mode .tm-field-brief article,
  body.tm-dark-mode .tm-field-report .modal-body,
  body.tm-dark-mode .tm-field-report-summary div,
  body.tm-dark-mode .tm-field-report-section{
    background:rgba(15,27,48,.9);
    border-color:rgba(147,197,253,.2);
  }
  body.tm-dark-mode .tm-field-route-head h4,
  body.tm-dark-mode .tm-field-brief strong,
  body.tm-dark-mode .tm-field-report-summary strong,
  body.tm-dark-mode .tm-field-report-section h5{
    color:#fff;
  }
  .tm-service-modal .modal-content{
    border:0;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 28px 70px rgba(10,34,62,.32);
  }
  .tm-service-modal .modal-header{
    background:linear-gradient(135deg,#0f3d67,#18a3dc) !important;
    color:#fff !important;
    border:0;
    padding:16px 18px;
  }
  .tm-service-modal .modal-header .close{
    width:34px;
    height:34px;
    border-radius:50%;
    background:rgba(255,255,255,.18);
    color:#fff;
    opacity:1;
    text-shadow:none;
  }
  .tm-service-modal .modal-title{
    font-weight:900;
  }
  .tm-service-modal .modal-body{
    background:#f6fbff;
    padding:16px;
  }
  .tm-service-modal .modal-footer{
    background:#f9fcff;
    border-top:1px solid #dbeaf8;
    padding:12px 16px;
  }
  .tm-taller-summary{
    border:1px solid #cfe5f8;
    background:linear-gradient(135deg,#e8f6ff,#fff);
    color:#173b5e;
    border-radius:14px;
    padding:12px 14px;
    font-weight:800;
    margin-bottom:12px;
  }
  .tm-taller-cost-note{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:10px;
    margin:10px 0;
  }
  .tm-taller-cost-note span{
    border:1px solid #d9eafa;
    border-radius:12px;
    background:#f8fcff;
    padding:10px 12px;
    color:#173b5e;
    font-weight:900;
  }
  .tm-taller-cost-note b{
    display:block;
    color:#6b8197;
    font-size:10px;
    text-transform:uppercase;
    margin-bottom:3px;
  }
  .tm-taller-file{
    border:1px dashed #9bc9ed;
    background:#f4fbff;
    border-radius:13px;
    padding:12px;
  }
  .tm-taller-file input{
    width:100%;
  }
  .servicio-paso{
    border:1px solid #dbeaf8;
    border-radius:14px;
    padding:13px;
    margin-bottom:12px;
    background:#fff;
    box-shadow:0 10px 22px rgba(24,70,118,.06);
  }
  .servicio-paso h4{
    margin:0 0 10px;
    font-weight:900;
    color:#10233f;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .servicio-paso h4 .badge{
    background:#168fd0;
    min-width:25px;
    height:25px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }
  .tm-repuestos-head{
    display:grid;
    grid-template-columns:minmax(0,1fr) 320px;
    gap:12px;
    align-items:center;
    margin-bottom:12px;
  }
  .tm-repuestos-head .alert{
    margin:0;
    border-radius:12px;
    border:1px solid #cfe5f8;
  }
  .tm-repuestos-search{
    position:relative;
  }
  .tm-repuestos-search i{
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    color:#168fd0;
  }
  .tm-repuestos-search input{
    height:40px;
    border-radius:11px;
    border:1px solid #d7e7f6;
    padding-left:36px;
    box-shadow:none;
  }
  .tm-repuestos-grid{
    max-height:470px;
    overflow-y:auto;
    padding:2px 6px 2px 2px;
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
    gap:9px;
  }
  .tm-repuesto-card{
    background:#fff;
    border:1px solid #dce9f7;
    border-radius:14px;
    padding:9px;
    min-height:166px;
    display:flex;
    flex-direction:column;
    gap:8px;
    transition:.18s ease;
    cursor:pointer;
    position:relative;
  }
  .tm-repuesto-card:hover{
    transform:translateY(-1px);
    border-color:#9fc8ef;
    box-shadow:0 14px 26px rgba(24,70,118,.11);
  }
  .tm-repuesto-card.is-selected{
    border-color:#18a3dc;
    background:linear-gradient(180deg,#eff9ff,#fff);
    box-shadow:0 14px 30px rgba(24,163,220,.16);
  }
  .tm-repuesto-card.is-disabled{
    opacity:.62;
    cursor:not-allowed;
  }
  .tm-repuesto-top{
    display:flex;
    justify-content:space-between;
    gap:8px;
    align-items:flex-start;
  }
  .tm-repuesto-code{
    border-radius:999px;
    background:#e8f3ff;
    color:#16558c;
    padding:4px 8px;
    font-size:10px;
    font-weight:900;
    max-width:142px;
    overflow-wrap:anywhere;
  }
  .tm-repuesto-check{
    width:26px;
    height:26px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:9px;
    background:#f0f7ff;
    border:1px solid #d7e9fa;
  }
  .tm-repuesto-title{
    margin:0;
    font-size:11px;
    line-height:1.25;
    color:#10233f;
    font-weight:900;
    min-height:40px;
    overflow-wrap:anywhere;
  }
  .tm-repuesto-meta{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:6px;
  }
  .tm-repuesto-meta span{
    background:#f8fbff;
    border:1px solid #e2edf8;
    border-radius:10px;
    padding:6px;
    font-size:10.5px;
    font-weight:900;
    color:#1e3555;
  }
  .tm-repuesto-meta b{
    display:block;
    color:#6c8199;
    font-size:9px;
    text-transform:uppercase;
    margin-bottom:2px;
  }
  .tm-repuesto-qty{
    display:flex;
    gap:7px;
    align-items:center;
    margin-top:auto;
  }
  .tm-repuesto-qty label{
    margin:0;
    color:#637891;
    font-size:10px;
    text-transform:uppercase;
    font-weight:900;
  }
  .tm-repuesto-qty input{
    height:34px;
    border-radius:9px;
    border:1px solid #d7e7f6;
  }
  .tm-repuesto-card.is-limited-hidden{
    display:none !important;
  }
  .tm-repuestos-counter{
    margin-top:8px;
    color:#58718d;
    font-size:11px;
    font-weight:800;
  }
  @media(max-width: 767px){
    .tm-repuestos-head{
      grid-template-columns:1fr;
    }
    .tm-repuestos-grid{
      grid-template-columns:1fr;
    }
  }
  @media(max-width: 767px){
    .tm-order-hero,
    .tm-order-toolbar{
      flex-direction:column;
      align-items:stretch;
    }
    .tm-order-grid{
      grid-template-columns:1fr;
    }
    .tm-order-detail-grid{
      grid-template-columns:1fr;
    }
    .tm-order-data{
      grid-template-columns:1fr;
    }
    .tm-field-grid{
      grid-template-columns:1fr;
    }
    .tm-field-card .tm-order-topline{
      flex-wrap:wrap;
    }
    .tm-field-card .tm-order-status{
      margin-left:0;
    }
    .tm-field-progress{
      gap:4px;
      padding-left:10px;
      padding-right:10px;
    }
    .tm-field-progress span b{
      display:none;
    }
    .tm-field-summary,
    .tm-field-contact{
      grid-template-columns:1fr;
    }
    .tm-field-card .tm-order-actions{
      grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .tm-field-card .tm-order-actions .btnIniciarTrabajoOrden,
    .tm-field-card .tm-order-actions .btnInformeServicio,
    .tm-field-card .tm-order-actions .btnImprimirConformidadInstalacion,
    .tm-field-card .tm-order-actions .btnImprimirOrdenServicio{
      grid-column:1/-1;
    }
  }
</style>

<div class="content-wrapper tm-order-page">
  <section class="content-header">
    <h1>Ordenes de servicio tecnico</h1>
  </section>
  <section class="content">
    <div class="tm-order-hero">
      <div>
        <h1>Trabajo tecnico por atender</h1>
        <p>Trabaje por bandejas: tome una orden, espere almacen cuando corresponda y continue solo con lo que ya esta en taller o campo.</p>
      </div>
      <div class="tm-order-metrics">
        <div class="tm-order-metric"><span>Por tomar</span><strong><?php echo count($ordenBandejas["por_tomar"]["items"]); ?></strong></div>
        <div class="tm-order-metric"><span>Campo</span><strong><?php echo count($ordenBandejas["campo"]["items"]); ?></strong></div>
        <div class="tm-order-metric"><span>En taller</span><strong><?php echo count($ordenBandejas["taller"]["items"]); ?></strong></div>
        <div class="tm-order-metric"><span>En espera</span><strong><?php echo count($ordenBandejas["espera"]["items"]); ?></strong></div>
        <div class="tm-order-metric"><span>Cerradas</span><strong><?php echo count($ordenBandejas["cerradas"]["items"]); ?></strong></div>
      </div>
    </div>
    <div class="tm-order-board">
      <div class="tm-order-toolbar">
        <h3><i class="fa fa-clipboard"></i> Ordenes asignadas</h3>
        <div class="tm-order-search">
          <i class="fa fa-search"></i>
          <input type="text" class="form-control" id="buscarOrdenServicioCard" placeholder="Buscar por cliente, codigo, tecnico, estado o equipo">
        </div>
      </div>
      <ul class="nav nav-tabs tm-order-tabs">
        <?php $primeraBandeja = true; ?>
        <?php foreach($ordenBandejas as $claveBandeja => $bandeja): ?>
          <li class="<?php echo $primeraBandeja ? 'active' : ''; ?>">
            <a href="#tabOrden_<?php echo $claveBandeja; ?>" data-toggle="tab">
              <i class="fa <?php echo $bandeja["icono"]; ?>"></i>
              <?php echo tmOrdenEsc($bandeja["titulo"]); ?>
              <span class="tm-order-count"><?php echo count($bandeja["items"]); ?></span>
            </a>
          </li>
          <?php $primeraBandeja = false; ?>
        <?php endforeach; ?>
      </ul>
      <div class="tab-content">
        <?php $primeraBandeja = true; ?>
        <?php foreach($ordenBandejas as $claveBandeja => $bandeja): ?>
          <div class="tab-pane <?php echo $primeraBandeja ? 'active' : ''; ?>" id="tabOrden_<?php echo $claveBandeja; ?>">
            <div class="tm-order-lane-info">
              <i class="fa <?php echo $bandeja["icono"]; ?>"></i>
              <div>
                <strong><?php echo tmOrdenEsc($bandeja["titulo"]); ?></strong>
                <span><?php echo tmOrdenEsc($bandeja["ayuda"]); ?></span>
              </div>
            </div>
            <div class="tm-order-grid <?php echo $claveBandeja == 'campo' ? 'tm-field-grid' : ''; ?>"><?php renderTarjetasServiciosTecnicos($bandeja["items"], $_SESSION["perfil"] == "Administrador", $bandeja["titulo"]); ?></div>
          </div>
          <?php $primeraBandeja = false; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>

<div id="modalDetalleOrdenServicio" class="modal fade tm-order-detail" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="tm-order-detail-head">
        <div>
          <span id="detalleOrdenCodigo" class="tm-order-code"></span>
          <h3 id="detalleOrdenCliente"></h3>
          <p id="detalleOrdenServicio"></p>
        </div>
        <button type="button" class="tm-order-detail-close" data-dismiss="modal">&times;</button>
      </div>
      <div class="tm-order-detail-body">
        <div class="tm-field-route" id="detalleCampoRuta">
          <div class="tm-field-route-head">
            <h4><i class="fa fa-road"></i> Ruta de atencion en campo</h4>
            <span class="tm-field-route-position" id="detalleCampoTurno">Turno #1</span>
          </div>
          <div class="tm-field-route-steps">
            <div class="tm-field-route-step is-done">1. Orden recibida</div>
            <div class="tm-field-route-step" id="detallePasoTomada">2. Orden tomada</div>
            <div class="tm-field-route-step" id="detallePasoInicio">3. Trabajo iniciado</div>
            <div class="tm-field-route-step" id="detallePasoInforme">4. Informe y cierre</div>
          </div>
        </div>
        <div class="tm-field-brief" id="detalleCampoResumen">
          <article><span>Referencia del lugar</span><strong id="detalleCampoReferencia">-</strong></article>
          <article><span>Alcance solicitado</span><strong id="detalleCampoAlcance">-</strong></article>
          <article><span>Diagnostico inicial</span><strong id="detalleCampoDiagnostico">-</strong></article>
          <article><span>Observaciones</span><strong id="detalleCampoObservaciones">-</strong></article>
        </div>
        <div class="tm-detail-action-panel">
          <h4><i class="fa fa-bolt"></i> Siguiente accion</h4>
          <p id="detalleOrdenPaso"></p>
          <div id="detalleOrdenAcciones"></div>
        </div>
        <div class="tm-order-detail-grid">
          <div class="tm-order-detail-box"><b>Telefono</b><span id="detalleOrdenTelefono"></span></div>
          <div class="tm-order-detail-box"><b>Tecnico</b><span id="detalleOrdenTecnico"></span></div>
          <div class="tm-order-detail-box"><b><?php echo $tmOrdenPuedeVerMontos ? "Total" : "Enfoque"; ?></b><span id="detalleOrdenTotal"></span></div>
          <div class="tm-order-detail-box"><b>Estado</b><span id="detalleOrdenEstado"></span></div>
          <div class="tm-order-detail-box"><b>Fecha</b><span id="detalleOrdenFecha"></span></div>
          <div class="tm-order-detail-box"><b>Bandeja</b><span id="detalleOrdenEtapa"></span></div>
        </div>
        <div class="tm-order-detail-address">
          <b>Direccion o equipo</b>
          <p id="detalleOrdenDireccion"></p>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="modalInformeFinalTaller" class="modal fade tm-service-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="tm-final-report-head">
        <div>
          <h4><i class="fa fa-clipboard"></i> Informe tecnico final</h4>
          <p id="informeFinalEquipo">Equipo</p>
        </div>
        <div class="tm-final-report-status" id="informeFinalEstado">En espera</div>
      </div>
      <div class="modal-body">
        <div class="tm-final-report-grid">
          <div class="tm-final-report-box"><span>Codigo equipo</span><strong id="informeFinalCodigo">-</strong></div>
          <div class="tm-final-report-box"><span>Diagnostico</span><strong id="informeFinalDiagnostico">-</strong></div>
          <div class="tm-final-report-box"><span>Notificacion al cliente</span><strong id="informeFinalNotificacion">-</strong></div>
          <div class="tm-final-report-box"><span>Trabajo correctivo realizado</span><strong id="informeFinalReparacion">-</strong></div>
          <div class="tm-final-report-box"><span>Repuestos usados</span><strong id="informeFinalRepuestos">-</strong></div>
          <div class="tm-final-report-box"><span>Garantia / observaciones</span><strong id="informeFinalGarantia">-</strong></div>
        </div>
        <div class="tm-final-report-evidence" id="informeFinalEvidencias"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary btnImprimirFinalDesdeModal"><i class="fa fa-print"></i> Imprimir soporte correctivo</button>
      </div>
    </div>
  </div>
</div>

<div id="modalTallerServicio" class="modal fade tm-service-modal" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <div class="modal-header" style="background:#3c8dbc;color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Diagnostico y soporte tecnico de equipo</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="guardarDiagnosticoTaller" value="1">
          <input type="hidden" id="idServicioTaller" name="idServicioTaller">
          <div class="tm-taller-summary" id="resumenEquipoTaller"></div>

          <div class="servicio-paso">
            <h4><span class="badge">1</span> Diagnostico tecnico</h4>
            <div class="form-group">
              <label>Diagnostico tecnico de la falla</label>
              <textarea class="form-control" id="diagnosticoTaller" name="diagnosticoTaller" rows="4"></textarea>
            </div>
          </div>

          <div class="servicio-paso">
            <h4><span class="badge">2</span> Notificacion al cliente</h4>
            <div class="row">
              <div class="col-md-6">
                <div class="checkbox">
                  <label><input type="checkbox" id="notificadoClienteTaller" name="notificadoClienteTaller"> Cliente notificado por llamada / WhatsApp / presencial</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Fecha y hora del sistema</label>
                  <input type="text" class="form-control" id="fechaHoraNotificacionTaller" readonly>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Detalle de notificacion</label>
              <textarea class="form-control" id="detalleNotificacionTaller" name="detalleNotificacionTaller" rows="3" placeholder="Fecha/hora, medio, quien atendio, que autorizo o rechazo"></textarea>
            </div>
          </div>

          <div class="servicio-paso">
            <h4><span class="badge">3</span> Decision del cliente</h4>
            <div class="form-group">
              <label>Respuesta del cliente</label>
              <select class="form-control" id="respuestaClienteTaller" name="respuestaClienteTaller">
                <option value="pendiente">Pendiente de respuesta</option>
                <option value="conforme">Conforme, autoriza reparacion</option>
                <option value="no_conforme">No conforme, devolver equipo</option>
              </select>
            </div>
          </div>

          <div class="servicio-paso" id="pasoRepuestosTaller">
            <h4><span class="badge">4</span> Repuestos / componentes</h4>
            <div class="alert alert-warning" id="alertaRepuestosTaller" style="display:none"></div>
            <p class="help-block">Si la reparacion necesita piezas, seleccione productos reales del inventario. Esto funciona como cobro posterior dentro del servicio.</p>
            <button type="button" class="btn btn-primary" id="btnAbrirRepuestosDesdeTaller"><i class="fa fa-cubes"></i> Solicitar repuestos / venta posterior</button>
            <div class="well well-sm" id="resumenRepuestosTaller" style="margin-top:10px;display:none;white-space:pre-line"></div>
          </div>

          <div class="servicio-paso" id="pasoCierreCorrectivoTaller">
            <h4><span class="badge">5</span> Cierre correctivo</h4>
            <div class="form-group">
              <label>Trabajo correctivo realizado</label>
              <textarea class="form-control" id="reparacionRealizadaTaller" name="reparacionRealizadaTaller" rows="3" placeholder="Ej: cambio de memoria RAM, limpieza, formateo, cambio de fuente"></textarea>
            </div>
            <div class="form-group">
              <label>Componentes/repuestos utilizados</label>
              <textarea class="form-control" id="repuestosDetalleTaller" name="repuestosDetalleTaller" rows="3" placeholder="Se completa con los repuestos entregados por almacen. Puede agregar numero de serie/codigo unico si corresponde."></textarea>
            </div>
            <input type="hidden" name="costoManoObraTaller" value="0">
            <input type="hidden" name="costoRepuestosTaller" value="0">
            <div class="tm-taller-cost-note">
              <span><b>Repuestos usados</b>Bs <em id="totalRepuestosTallerVista">0.00</em></span>
              <span><b>Mano de obra</b>La define caja segun el informe correctivo</span>
            </div>
            <div class="alert alert-info">El tecnico registra que se hizo y que piezas uso. Caja revisa este informe, suma repuestos y define el costo de mano de obra antes de cobrar.</div>
            <div class="form-group tm-taller-file">
              <label>Evidencias fotograficas del trabajo</label>
              <input type="file" name="evidenciasTaller[]" id="evidenciasTaller" accept="image/*" multiple>
              <p class="help-block">Adjunte fotos del diagnostico, pieza retirada, pieza instalada, pruebas o estado final del equipo.</p>
            </div>
            <div class="form-group">
              <label>Garantia y observaciones finales</label>
              <textarea class="form-control" id="garantiaDetalleTaller" name="garantiaDetalleTaller" rows="2" placeholder="Tiempo de garantia, condiciones, pruebas realizadas"></textarea>
            </div>
          </div>
          <div class="servicio-paso" id="pasoDevolucionAlmacenTaller" style="display:none">
            <h4><span class="badge">6</span> Informe final para caja</h4>
            <div class="alert alert-info">Al concluir este registro, el informe tecnico queda cerrado con evidencias y el caso pasa a caja para definir mano de obra y cobrar.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success" id="btnGuardarTallerServicio">Concluir registro tecnico</button>
        </div>
        <?php ControladorServicios::ctrGuardarDiagnosticoTaller(); ?>
      </form>
    </div>
  </div>
</div>

<div id="modalRepuestosTaller" class="modal fade tm-service-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="formRepuestosTaller">
        <div class="modal-header" style="background:#3c8dbc;color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Solicitar piezas/componentes a almacen</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="guardarRepuestosTaller" value="1">
          <input type="hidden" id="idServicioRepuestosTaller" name="idServicioRepuestosTaller">
          <input type="hidden" id="listaRepuestosTaller" name="listaRepuestosTaller">
          <div class="tm-repuestos-head">
            <div class="alert alert-info">Busque el repuesto, revise precio referencial y stock, marque lo necesario y envie la solicitud a almacen. No se carga mano de obra desde aqui.</div>
            <div class="tm-repuestos-search">
              <i class="fa fa-search"></i>
              <input type="text" class="form-control" id="buscarRepuestoTaller" placeholder="Buscar por nombre o codigo">
            </div>
          </div>
          <div class="tm-repuestos-grid" id="listaRepuestosTallerCards">
            <?php $contadorRepuestosRender = 0; ?>
            <?php foreach($productosInventario as $producto): ?>
              <?php
                $contadorRepuestosRender++;
                $stockProducto = (int)($producto["stock"] ?? 0);
                $sinStock = $stockProducto <= 0;
                $precioProducto = (float)($producto["precio_venta"] ?? 0);
                $sinPrecio = $precioProducto <= 0;
                $claseLimite = $contadorRepuestosRender > 48 ? " is-limited-hidden" : "";
              ?>
              <label class="tm-repuesto-card <?php echo $sinStock ? 'is-disabled' : ''; ?><?php echo $claseLimite; ?>">
                <div class="tm-repuesto-top">
                  <span class="tm-repuesto-code"><?php echo htmlspecialchars($producto["codigo"], ENT_QUOTES, "UTF-8"); ?></span>
                  <span class="tm-repuesto-check">
                    <input type="checkbox" class="checkRepuestoTaller" value="<?php echo $producto["id"]; ?>" <?php echo $sinStock ? 'disabled' : ''; ?>>
                  </span>
                </div>
                <h4 class="tm-repuesto-title"><?php echo htmlspecialchars($producto["descripcion"], ENT_QUOTES, "UTF-8"); ?></h4>
                <div class="tm-repuesto-meta">
                  <span><b>Stock</b><em class="label label-<?php echo $sinStock ? 'danger' : (($stockProducto <= 5) ? 'warning' : 'success'); ?>"><?php echo $stockProducto; ?></em></span>
                  <span><b>Precio ref.</b><?php echo $sinPrecio ? '<em class="label label-default">Sin precio</em>' : 'Bs '.number_format($precioProducto, 2); ?></span>
                  <span><b>Estado</b><?php echo $sinStock ? '<em class="label label-danger">Sin stock</em>' : '<em class="label label-success">Disponible</em>'; ?></span>
                </div>
                <div class="tm-repuesto-qty">
                  <label>Cantidad</label>
                  <input type="number" class="form-control cantidadRepuestoTaller" min="1" max="<?php echo max(1, $stockProducto); ?>" value="1" disabled>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="tm-repuestos-counter" id="contadorRepuestosTaller">Mostrando los primeros repuestos. Use el buscador para filtrar todo el inventario.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Solicitar a almacen</button>
        </div>
        <?php ControladorServicios::ctrGuardarRepuestosTaller(); ?>
      </form>
    </div>
  </div>
</div>

<div id="modalMapaOrdenServicio" class="modal fade tm-service-modal" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:#3c8dbc;color:white">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Ubicacion del servicio</h4>
      </div>
      <div class="modal-body">
        <p id="direccionMapaOrden" class="help-block"></p>
        <div id="mapaOrdenServicio" style="height:420px;width:100%;border:1px solid #ddd"></div>
      </div>
    </div>
  </div>
</div>

<div id="modalInformeServicio" class="modal fade tm-field-report" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data" id="formInformeCampo">
        <div class="tm-field-report-head">
          <div>
            <h4><i class="fa fa-check-circle"></i> Cierre de trabajo en campo</h4>
            <p>Registre el resultado real antes de concluir la orden.</p>
          </div>
          <span class="tm-field-report-code" id="informeCampoCodigo">Orden</span>
        </div>
        <div class="modal-body">
          <input type="hidden" name="guardarInformeTecnico" value="1">
          <input type="hidden" id="idServicioInforme" name="idServicioInforme">
          <input type="hidden" name="estadoServicioInforme" value="completado">
          <div class="tm-field-report-summary">
            <div><span>Servicio</span><strong id="informeCampoServicio">-</strong></div>
            <div><span>Direccion</span><strong id="informeCampoDireccion">-</strong></div>
          </div>
          <div class="tm-field-report-section">
            <h5><i class="fa fa-search"></i> Hallazgos en el lugar</h5>
            <textarea class="form-control" id="hallazgosTecnicos" name="hallazgosTecnicos" required placeholder="Describa condiciones encontradas, fallas, cambios respecto a la solicitud y cualquier riesgo detectado."></textarea>
          </div>
          <div class="tm-field-report-section">
            <h5><i class="fa fa-wrench"></i> Trabajo ejecutado</h5>
            <textarea class="form-control" id="trabajoRealizado" name="trabajoRealizado" required placeholder="Detalle instalación, configuración, canalización, pruebas, equipos intervenidos y resultado obtenido."></textarea>
          </div>
          <div class="tm-field-report-section">
            <h5><i class="fa fa-shield"></i> Pruebas, recomendaciones y pendientes</h5>
            <textarea class="form-control" id="recomendacionesTecnicas" name="recomendacionesTecnicas" placeholder="Indique pruebas de funcionamiento, recomendaciones al cliente y trabajos que requieren otra autorización."></textarea>
          </div>
          <div class="tm-field-report-section tm-field-signed-upload">
            <h5><i class="fa fa-file-image-o"></i> Boleta de conformidad firmada</h5>
            <label class="tm-field-file-box" for="boletaConformidadFirmada">
              <i class="fa fa-cloud-upload"></i>
              <span>
                <b id="nombreBoletaFirmada">Seleccionar PDF o imagen</b>
                <small>Escanee o fotografíe la boleta firmada por el cliente. Máximo 10 MB.</small>
              </span>
            </label>
            <input type="file" id="boletaConformidadFirmada" name="boletaConformidadFirmada" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required>
          </div>
          <div class="tm-field-report-confirm">
            <label style="margin:0"><input type="checkbox" id="confirmarCierreCampo" required> Confirmo que el trabajo fue revisado y que la boleta adjunta corresponde a esta orden y está firmada por el cliente.</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Volver</button>
          <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Concluir servicio</button>
        </div>
        <?php ControladorServicios::ctrGuardarInformeTecnico(); ?>
      </form>
    </div>
  </div>
</div>

<script>
$(function(){
  $('[title]').tooltip({container:'body'});
  $("#buscarOrdenServicioCard").on("input", function(){
    var texto = ($(this).val() || "").toLowerCase();
    $(".tm-order-card").each(function(){
      $(this).toggle(($(this).data("search") || "").indexOf(texto) !== -1);
    });
  });

  $(document).on("click", ".tm-order-card", function(e){
    if($(e.target).closest("a, button, input, select, textarea").length){
      return;
    }
    var tarjeta = $(this);
    $("#detalleOrdenCodigo").text("Orden " + tarjeta.data("codigo"));
    $("#detalleOrdenCliente").text(tarjeta.data("cliente"));
    $("#detalleOrdenServicio").text(tarjeta.data("servicio"));
    $("#detalleOrdenTelefono").text(tarjeta.data("telefono"));
    $("#detalleOrdenTecnico").text(tarjeta.data("tecnico"));
    $("#detalleOrdenTotal").text(tarjeta.data("total"));
    $("#detalleOrdenEstado").text(tarjeta.data("estado"));
    $("#detalleOrdenFecha").text(tarjeta.data("fecha"));
    $("#detalleOrdenEtapa").text(tarjeta.data("etapa"));
    $("#detalleOrdenDireccion").text(tarjeta.data("direccion"));
    $("#detalleOrdenPaso").text(tarjeta.data("paso"));
    $("#detalleOrdenAcciones").html(tarjeta.find(".tm-order-actions-template").html());
    $("#detalleOrdenAcciones [title]").tooltip({container:"body"});
    var esCampo = tarjeta.data("etapa") === "Campo / instalacion";
    $("#detalleCampoRuta, #detalleCampoResumen").toggle(esCampo);
    $("#modalDetalleOrdenServicio").toggleClass("tm-field-detail", esCampo);
    if(esCampo){
      var flujo = tarjeta.data("flujo") || "";
      var enCurso = flujo === "Trabajo iniciado";
      $("#detalleCampoTurno").text("Turno #" + (tarjeta.data("cola") || 1));
      $("#detallePasoTomada").addClass("is-done").removeClass("is-current");
      $("#detallePasoInicio").toggleClass("is-done", enCurso).toggleClass("is-current", !enCurso);
      $("#detallePasoInforme").toggleClass("is-current", enCurso).removeClass("is-done");
      $("#detalleCampoReferencia").text(tarjeta.data("referencia") || "-");
      var alcance = tarjeta.data("preguntas") || "-";
      var cantidad = Number(tarjeta.data("cantidad")) || 0;
      var metros = Number(tarjeta.data("metros")) || 0;
      if(cantidad > 0 || metros > 0){
        alcance += "\n" + (cantidad > 0 ? "Cantidad: " + cantidad : "") + (cantidad > 0 && metros > 0 ? " | " : "") + (metros > 0 ? "Metros estimados: " + metros : "");
      }
      $("#detalleCampoAlcance").text(alcance);
      $("#detalleCampoDiagnostico").text(tarjeta.data("diagnostico-inicial") || "-");
      $("#detalleCampoObservaciones").text(tarjeta.data("observaciones") || "-");
    }
    $("#modalDetalleOrdenServicio").modal("show");
  });
});

function fechaHoraSistemaTaller(){
  var ahora = new Date();
  var pad = function(n){ return String(n).padStart(2, "0"); };
  return ahora.getFullYear() + "-" + pad(ahora.getMonth() + 1) + "-" + pad(ahora.getDate()) + " " + pad(ahora.getHours()) + ":" + pad(ahora.getMinutes()) + ":" + pad(ahora.getSeconds());
}

function aplicarPasosTaller(){
  var respuesta = $("#respuestaClienteTaller").val();
  var notificado = $("#notificadoClienteTaller").is(":checked");
  var solicitados = $("#modalTallerServicio").data("repuestosSolicitados") === true;
  var entregados = $("#modalTallerServicio").data("repuestosEntregados") === true;
  var yaRegistrado = $("#modalTallerServicio").data("diagnosticoRegistrado") === true;
  var idServicio = $("#idServicioTaller").val();

  $("#pasoRepuestosTaller, #pasoCierreCorrectivoTaller").hide();
  $("#modalTallerServicio .servicio-paso").show();
  $("#btnGuardarTallerServicio").show();
  $("#alertaRepuestosTaller").hide().text("");
  $("#btnAbrirRepuestosDesdeTaller").attr("idServicio", idServicio);
  $("#reparacionRealizadaTaller, #repuestosDetalleTaller, #garantiaDetalleTaller").prop("disabled", false);
  $("#diagnosticoTaller").prop("required", true);
  $("#reparacionRealizadaTaller").prop("required", false);

  if(respuesta === "conforme" && notificado){
    $("#pasoRepuestosTaller").show();
    $("#pasoCierreCorrectivoTaller").show();
  }

  if(yaRegistrado && respuesta === "conforme" && !solicitados && !entregados){
    $("#modalTallerServicio .servicio-paso").hide();
    $("#pasoRepuestosTaller").show();
    $("#pasoCierreCorrectivoTaller").show();
  }

  if(respuesta === "no_conforme" && notificado){
    $("#pasoCierreCorrectivoTaller").hide();
    if(yaRegistrado){
      $("#modalTallerServicio .servicio-paso").hide();
      $("#pasoDevolucionAlmacenTaller").show();
      $("#btnGuardarTallerServicio").hide();
    }
  }

  if(solicitados && !entregados){
    $("#modalTallerServicio .servicio-paso").hide();
    $("#pasoRepuestosTaller").show();
    $("#pasoCierreCorrectivoTaller").hide();
    $("#btnGuardarTallerServicio").hide();
    $("#alertaRepuestosTaller").show().text("Hay repuestos solicitados a almacen. No se puede cerrar el trabajo correctivo hasta que almacen los entregue.");
    $("#reparacionRealizadaTaller, #repuestosDetalleTaller, #garantiaDetalleTaller").prop("disabled", true);
    $("#diagnosticoTaller").prop("required", false);
  }

  if(entregados){
    $("#diagnosticoTaller").prop("required", false);
    $("#reparacionRealizadaTaller").prop("required", true);
    if(respuesta === "pendiente"){
      $("#respuestaClienteTaller").val("conforme");
      respuesta = "conforme";
    }
    $("#modalTallerServicio .servicio-paso").hide();
    $("#pasoRepuestosTaller").show();
    $("#pasoCierreCorrectivoTaller").show();
    $("#pasoDevolucionAlmacenTaller").show();
    $("#alertaRepuestosTaller").show().text("Almacen ya entrego los repuestos. Registre el trabajo correctivo, detalle usado, evidencia y garantia. Caja definira la mano de obra.");
  }
}

$(document).on("click", ".btnImprimirOrdenServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/orden-servicio.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});
$(document).on("click", ".btnImprimirConformidadInstalacion", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-conformidad-instalacion.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});
$(document).on("click", ".btnImprimirIngresoEquipo", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-ingreso-equipo.php?idServicio=" + $(this).attr("idServicio"), "_blank");
});
$(document).on("click", ".btnImprimirTaller", function(e){
  e.preventDefault();
  e.stopPropagation();
  window.open("extensiones/tcpdf/pdf/boleta-taller.php?idServicio=" + $(this).attr("idServicio") + "&tipo=" + $(this).attr("tipo"), "_blank");
});

$(document).on("click", ".btnTallerServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  $("#modalDetalleOrdenServicio").modal("hide");
  $("#idServicioTaller").val($(this).attr("idServicio"));
  $("#resumenEquipoTaller").html("<b>" + $(this).attr("codigoEquipo") + "</b><br>" + $(this).attr("equipo"));
  $("#diagnosticoTaller").val($(this).attr("diagnostico"));
  $("#notificadoClienteTaller").prop("checked", false);
  $("#fechaHoraNotificacionTaller").val(fechaHoraSistemaTaller());
  $("#respuestaClienteTaller").val($(this).attr("respuesta") || "pendiente");
  $("#detalleNotificacionTaller").val($(this).attr("detalle"));
  $("#reparacionRealizadaTaller").val($(this).attr("reparacion"));
  $("#repuestosDetalleTaller").val($(this).attr("repuestos"));
  $("#garantiaDetalleTaller").val($(this).attr("garantia"));
  var solicitados = $(this).attr("repuestosSolicitados") === "1";
  var entregados = $(this).attr("repuestosEntregados") === "1";
  var totalRepuestos = Number($(this).attr("totalRepuestos")) || 0;
  $("[name='costoRepuestosTaller']").val(totalRepuestos.toFixed(2));
  $("#totalRepuestosTallerVista").text(totalRepuestos.toFixed(2));
  $("#modalTallerServicio").data("diagnosticoRegistrado", ($(this).attr("diagnostico") || "").trim() !== "" && ($(this).attr("respuesta") || "pendiente") !== "pendiente");
  $("#modalTallerServicio").data("repuestosSolicitados", solicitados);
  $("#modalTallerServicio").data("repuestosEntregados", entregados);
  var resumenRepuestos = ($(this).attr("resumenRepuestos") || "").replace(/\\n/g, "\n");
  if(resumenRepuestos.trim() !== ""){
    $("#resumenRepuestosTaller").show().text(resumenRepuestos);
  }else{
    $("#resumenRepuestosTaller").hide().text("");
  }
  aplicarPasosTaller();
  $("#modalTallerServicio").modal("show");
});

$("#notificadoClienteTaller, #respuestaClienteTaller").on("change", aplicarPasosTaller);

$("#modalTallerServicio form").on("submit", function(e){
  $("#modalTallerServicio .servicio-paso:hidden").find("input, textarea, select").prop("required", false);
  if($("#modalTallerServicio").data("repuestosEntregados") === true){
    $("#respuestaClienteTaller").val("conforme");
    $("#notificadoClienteTaller").prop("checked", true);
    if(($("#reparacionRealizadaTaller").val() || "").trim() === ""){
      e.preventDefault();
      swal({type:"error", title:"Debe detallar el trabajo correctivo realizado", confirmButtonText:"Cerrar"});
      return false;
    }
  }
});

$(document).on("click", ".btnSolicitarRepuestosTaller", function(e){
  e.preventDefault();
  e.stopPropagation();
  $("#modalDetalleOrdenServicio").modal("hide");
  $("#idServicioRepuestosTaller").val($(this).attr("idServicio"));
  $("#listaRepuestosTaller").val("");
  resetModalRepuestosTaller();
  $("#modalRepuestosTaller").modal("show");
});

$("#btnAbrirRepuestosDesdeTaller").on("click", function(){
  $("#idServicioRepuestosTaller").val($(this).attr("idServicio"));
  $("#listaRepuestosTaller").val("");
  resetModalRepuestosTaller();
  $("#modalRepuestosTaller").modal("show");
});

function resetModalRepuestosTaller(){
  $("#modalRepuestosTaller .checkRepuestoTaller").prop("checked", false);
  $("#modalRepuestosTaller .cantidadRepuestoTaller").prop("disabled", true).val(1);
  $("#listaRepuestosTallerCards .tm-repuesto-card").removeClass("is-selected").addClass("is-limited-hidden");
  $("#listaRepuestosTallerCards .tm-repuesto-card:lt(48)").removeClass("is-limited-hidden");
  $("#buscarRepuestoTaller").val("");
  actualizarContadorRepuestosTaller();
}

function actualizarContadorRepuestosTaller(){
  var visibles = $("#listaRepuestosTallerCards .tm-repuesto-card:visible").length;
  var total = $("#listaRepuestosTallerCards .tm-repuesto-card").length;
  $("#contadorRepuestosTaller").text("Mostrando " + visibles + " de " + total + " repuestos. Use el buscador para encontrar productos especificos.");
}

$("#listaRepuestosTallerCards").on("change", ".checkRepuestoTaller", function(){
  var tarjeta = $(this).closest(".tm-repuesto-card");
  var activo = $(this).is(":checked");
  tarjeta.toggleClass("is-selected", activo);
  tarjeta.find(".cantidadRepuestoTaller").prop("disabled", !activo);
});

$("#buscarRepuestoTaller").on("input", function(){
  var texto = ($(this).val() || "").toLowerCase();
  var encontrados = 0;
  $("#listaRepuestosTallerCards .tm-repuesto-card").addClass("is-limited-hidden").each(function(){
    var coincide = $(this).text().toLowerCase().indexOf(texto) !== -1;
    if(texto === ""){
      coincide = encontrados < 48;
    }else if(coincide){
      coincide = encontrados < 80;
    }
    if(coincide){
      $(this).removeClass("is-limited-hidden");
      encontrados++;
    }else if(texto !== "" && $(this).text().toLowerCase().indexOf(texto) !== -1){
      encontrados++;
    }else if(texto === ""){
      encontrados++;
    }
  });
  actualizarContadorRepuestosTaller();
});

$("#formRepuestosTaller").on("submit", function(e){
  var lista = [];
  $("#listaRepuestosTallerCards .tm-repuesto-card").each(function(){
    var check = $(this).find(".checkRepuestoTaller");
    if(check.is(":checked")){
      lista.push({
        id_producto: check.val(),
        cantidad: $(this).find(".cantidadRepuestoTaller").val()
      });
    }
  });
  if(lista.length === 0){
    e.preventDefault();
    swal({type:"error", title:"Seleccione al menos un repuesto", confirmButtonText:"Cerrar"});
    return false;
  }
  $("#listaRepuestosTaller").val(JSON.stringify(lista));
});

function textoInformeFinalTaller(valor){
  valor = $.trim(valor || "");
  return valor.length ? valor : "-";
}

function pintarEvidenciasInformeFinal(evidenciasRaw){
  var evidencias = [];
  try{
    evidencias = JSON.parse(evidenciasRaw || "[]");
  }catch(error){
    evidencias = [];
  }
  var contenedor = $("#informeFinalEvidencias").empty();
  if(!Array.isArray(evidencias) || evidencias.length === 0){
    contenedor.append($("<div>").addClass("text-muted").text("Sin evidencias fotograficas adjuntas."));
    return;
  }
  evidencias.forEach(function(item, indice){
    var archivo = item && item.archivo ? item.archivo : "";
    if(!archivo){ return; }
    $("<a>")
      .attr("href", archivo)
      .attr("target", "_blank")
      .attr("title", "Ver evidencia " + (indice + 1))
      .append($("<img>").attr("src", archivo).attr("alt", "Evidencia tecnica " + (indice + 1)))
      .appendTo(contenedor);
  });
}

$(document).on("click", ".btnInformeFinalTaller", function(e){
  e.preventDefault();
  e.stopPropagation();
  $("#modalDetalleOrdenServicio").modal("hide");
  var boton = $(this);
  var resumenRepuestos = (boton.attr("resumenRepuestos") || "").replace(/\\n/g, "\n");
  var repuestos = resumenRepuestos.trim() !== "" ? resumenRepuestos : boton.attr("repuestos");

  $("#modalInformeFinalTaller").data("idServicio", boton.attr("idServicio"));
  $("#informeFinalEquipo").text(textoInformeFinalTaller(boton.attr("equipo")));
  $("#informeFinalEstado").text(textoInformeFinalTaller(boton.attr("estadoFinal")));
  $("#informeFinalCodigo").text(textoInformeFinalTaller(boton.attr("codigoEquipo")));
  $("#informeFinalDiagnostico").text(textoInformeFinalTaller(boton.attr("diagnostico")));
  $("#informeFinalNotificacion").text(textoInformeFinalTaller(boton.attr("notificacion")));
  $("#informeFinalReparacion").text(textoInformeFinalTaller(boton.attr("reparacion")));
  $("#informeFinalRepuestos").text(textoInformeFinalTaller(repuestos));
  $("#informeFinalGarantia").text(textoInformeFinalTaller(boton.attr("garantia")));
  pintarEvidenciasInformeFinal(boton.attr("evidencias"));
  $("#modalInformeFinalTaller").modal("show");
});

$(document).on("click", ".btnImprimirFinalDesdeModal", function(e){
  e.preventDefault();
  var idServicio = $("#modalInformeFinalTaller").data("idServicio");
  if(idServicio){
    window.open("extensiones/tcpdf/pdf/boleta-taller.php?idServicio=" + idServicio + "&tipo=correctivo", "_blank");
  }
});

$(document).on("click", ".btnInformeServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  $("#modalDetalleOrdenServicio").modal("hide");
  $("#idServicioInforme").val($(this).attr("idServicio"));
  $("#informeCampoCodigo").text("Orden " + ($(this).attr("codigoServicio") || ""));
  $("#informeCampoServicio").text($(this).attr("tipoServicio") || "-");
  $("#informeCampoDireccion").text($(this).attr("direccionServicio") || "-");
  $("#hallazgosTecnicos").val($(this).attr("hallazgos"));
  $("#trabajoRealizado").val($(this).attr("trabajo"));
  $("#recomendacionesTecnicas").val($(this).attr("recomendaciones"));
  $("#confirmarCierreCampo").prop("checked", false);
  $("#boletaConformidadFirmada").val("");
  $("#nombreBoletaFirmada").text("Seleccionar PDF o imagen");
  $("#modalInformeServicio").modal("show");
});

$("#boletaConformidadFirmada").on("change", function(){
  var archivo = this.files && this.files.length ? this.files[0] : null;
  $("#nombreBoletaFirmada").text(archivo ? archivo.name : "Seleccionar PDF o imagen");
});

$("#formInformeCampo").on("submit", function(e){
  var controlArchivo = $("#boletaConformidadFirmada")[0];
  var archivo = controlArchivo && controlArchivo.files.length ? controlArchivo.files[0] : null;
  if(!archivo){
    e.preventDefault();
    swal({type:"error", title:"Adjunte la boleta firmada", text:"Seleccione el PDF o la imagen firmada antes de concluir.", confirmButtonText:"Cerrar"});
    return false;
  }
  if(archivo.size > 10 * 1024 * 1024){
    e.preventDefault();
    swal({type:"error", title:"Archivo demasiado grande", text:"La boleta debe pesar como maximo 10 MB.", confirmButtonText:"Cerrar"});
    return false;
  }
});

var mapaOrdenServicio = null;
var marcadorOrdenServicio = null;
$(document).on("click", ".btnVerMapaServicio", function(e){
  e.preventDefault();
  e.stopPropagation();
  $("#modalDetalleOrdenServicio").modal("hide");
  var lat = Number($(this).attr("lat"));
  var lng = Number($(this).attr("lng"));
  $("#direccionMapaOrden").text($(this).attr("direccion"));
  $("#modalMapaOrdenServicio").modal("show");

  $("#modalMapaOrdenServicio").one("shown.bs.modal", function(){
    if(!window.L){
      swal({type:"error", title:"No se pudo cargar el mapa", text:"Revise conexion a internet.", confirmButtonText:"Cerrar"});
      return;
    }
    if(!mapaOrdenServicio){
      mapaOrdenServicio = L.map("mapaOrdenServicio").setView([lat, lng], 16);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {maxZoom:19, attribution:"&copy; OpenStreetMap"}).addTo(mapaOrdenServicio);
    }
    setTimeout(function(){
      mapaOrdenServicio.invalidateSize();
      mapaOrdenServicio.setView([lat, lng], 16);
      if(marcadorOrdenServicio){
        marcadorOrdenServicio.setLatLng([lat, lng]);
      }else{
        marcadorOrdenServicio = L.marker([lat, lng]).addTo(mapaOrdenServicio);
      }
    }, 250);
  });
});
</script>

<?php
ControladorServicios::ctrCambiarEstadoServicio();
ControladorServicios::ctrRetirarEquipoTecnico();
ControladorServicios::ctrEnviarEquipoAAlmacenTecnico();
?>
