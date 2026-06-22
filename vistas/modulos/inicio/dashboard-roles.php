<?php

$dbDashboard = Conexion::conectar();
$idUsuarioDashboard = (int)($_SESSION["id"] ?? 0);
$nombreDashboard = $_SESSION["nombre"] ?? "Usuario";
$perfilDashboard = $_SESSION["perfil"] ?? "";
$rolRealDashboard = $_SESSION["rol"] ?? "";
$vistaRolDashboard = $_SESSION["vistaRolMenu"] ?? "administrador";
$rolDashboard = ($perfilDashboard == "Administrador") ? $vistaRolDashboard : $rolRealDashboard;

if($rolDashboard == "" || $rolDashboard == "administrador"){
  $rolDashboard = ($perfilDashboard == "Administrador") ? "administrador" : $rolRealDashboard;
}

$labelsRolesDashboard = array(
  "administrador" => "Administrador",
  "vendedor" => "Vendedor",
  "cajero" => "Cajero",
  "almacen" => "Almacen",
  "mensajero" => "Mensajero",
  "tecnico" => "Tecnico",
  "desarrollador" => "Desarrollador"
);

$rolTextoDashboard = $labelsRolesDashboard[$rolDashboard] ?? ucfirst($rolDashboard);
$rolRealTextoDashboard = ($perfilDashboard == "Administrador")
  ? "Administrador".(($rolDashboard != "administrador") ? " viendo tablero de ".$rolTextoDashboard : "")
  : $rolTextoDashboard;

function dashboardCount($db, $sql, $params = array()){
  try{
    $stmt = $db->prepare($sql);
    foreach($params as $key => $value){
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
  }catch(Exception $e){
    return 0;
  }
}

function dashboardRows($db, $sql, $params = array()){
  try{
    $stmt = $db->prepare($sql);
    foreach($params as $key => $value){
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }catch(Exception $e){
    return array();
  }
}

function dashboardMoney($db, $sql, $params = array()){
  try{
    $stmt = $db->prepare($sql);
    foreach($params as $key => $value){
      $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return "Bs ".number_format((float)$stmt->fetchColumn(), 2);
  }catch(Exception $e){
    return "Bs 0.00";
  }
}

function dashboardCard($color, $icon, $numero, $titulo, $detalle, $link, $textoLink = "Atender"){
  echo '
    <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
      <a href="'.$link.'" class="tm-dashboard-card tm-card-'.$color.'">
        <div class="tm-card-top">
          <span class="tm-card-icon"><i class="fa '.$icon.'"></i></span>
          <span class="tm-card-action">'.$textoLink.' <i class="fa fa-angle-right"></i></span>
        </div>
        <strong>'.$numero.'</strong>
        <h3>'.$titulo.'</h3>
        <p>'.$detalle.'</p>
      </a>
    </div>';
}

function dashboardEstadoVisible($estado){
  $estado = trim((string)$estado);
  $textos = array(
    "desembolsado" => "En proceso de compra",
    "en_compra" => "En desembolso",
    "entregado_almacen" => "Entregado a almacen",
    "completado" => "Completado con exito"
  );
  return $textos[$estado] ?? str_replace("_", " ", $estado);
}

$cardsDashboard = array();
$tareasDashboard = array();
$stockCriticoDashboardSql = "SELECT codigo,
                                    0 AS total,
                                    CASE WHEN stock <= 0 THEN 'sin_stock' ELSE 'stock_bajo' END AS estado,
                                    fecha,
                                    CASE WHEN stock <= 0 THEN 'Crear solicitud de compra' ELSE 'Revisar reposicion' END AS proceso,
                                    CONCAT('Producto: ', descripcion, ' | Stock: ', stock) AS tipo,
                                    '-' AS vendedor,
                                    '-' AS cajero
                             FROM productos
                             WHERE stock <= 3
                             ORDER BY stock ASC, fecha DESC
                             LIMIT 8";
$stockCeroDashboardCount = dashboardCount($dbDashboard, "SELECT COUNT(*) FROM productos WHERE stock <= 0");
$stockBajoDashboardCount = dashboardCount($dbDashboard, "SELECT COUNT(*) FROM productos WHERE stock > 0 AND stock <= 3");
$ventasProductosHoyDashboard = dashboardMoney($dbDashboard, "SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado_pago = 'aprobado' AND DATE(COALESCE(fecha_pago, fecha)) = CURDATE()");
$ventasServiciosHoyDashboard = dashboardMoney($dbDashboard, "SELECT COALESCE(SUM(total), 0) FROM servicios_ventas WHERE estado_pago = 'aprobado' AND DATE(COALESCE(fecha_pago, fecha)) = CURDATE()");

switch($rolDashboard){
  case "vendedor":
    $cardsDashboard[] = array("green", "fa-shopping-cart", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM ventas WHERE id_vendedor = :id AND estado_pago = 'pendiente'", array(":id" => $idUsuarioDashboard)), "Ventas por cobrar", "Ventas creadas por usted que deben pasar por caja.", "ventas");
    $cardsDashboard[] = array("aqua", "fa-check-square-o", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM ventas WHERE id_vendedor = :id AND DATE(fecha) = CURDATE()", array(":id" => $idUsuarioDashboard)), "Ventas de hoy", "Movimiento registrado hoy por su usuario.", "ventas", "Ver ventas");
    $cardsDashboard[] = array("yellow", "fa-wrench", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicios_ventas WHERE id_vendedor = :id AND estado_pago IN ('pendiente','pendiente_retiro')", array(":id" => $idUsuarioDashboard)), "Servicios por cobrar", "Servicios registrados que aun no fueron cobrados.", "administrar-servicios");
    $cardsDashboard[] = array("purple", "fa-plus", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM clientes WHERE DATE(fecha) = CURDATE()"), "Clientes nuevos hoy", "Clientes cargados en el sistema durante el dia.", "clientes", "Ver clientes");
    $tareasDashboard = dashboardRows($dbDashboard, "
      SELECT v.codigo, v.total, v.estado_pago AS estado, v.fecha, 'Caja para cobro' AS proceso, 'Venta pendiente de cobro' AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM ventas v
      LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = v.id_cajero
      WHERE v.id_vendedor = :idVenta AND v.estado_pago = 'pendiente'
      UNION ALL
      SELECT s.codigo, s.total, s.estado_pago AS estado, s.fecha, 'Caja para cobro de servicio' AS proceso, s.tipo_servicio AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM servicios_ventas s
      LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = s.id_cajero
      WHERE s.id_vendedor = :idServicio AND s.estado_pago IN ('pendiente','pendiente_adelanto','pendiente_final','pendiente_retiro')
      UNION ALL
      SELECT c.codigo, c.total, c.estado_web AS estado, c.fecha, 'Responder solicitud web' AS proceso, 'Solicitud web / cotizacion' AS tipo, u.nombre AS vendedor, '-' AS cajero
      FROM cotizaciones c
      LEFT JOIN usuarios u ON u.id = c.id_user
      WHERE c.origen = 'web' AND c.estado_web IN ('pendiente','en_revision')
      ORDER BY fecha DESC
      LIMIT 10", array(":idVenta" => $idUsuarioDashboard, ":idServicio" => $idUsuarioDashboard));
    break;

  case "cajero":
    $cardsDashboard[] = array("yellow", "fa-money", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM ventas WHERE estado_pago = 'pendiente'"), "Ventas pendientes", "Clientes esperando cobro en caja.", "pagos-ventas");
    $cardsDashboard[] = array("aqua", "fa-credit-card", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicios_ventas WHERE estado_pago = 'pendiente' OR (tipo_servicio = 'Soporte tecnico en taller' AND estado_pago = 'pendiente_retiro' AND estado_servicio = 'listo_cobro')"), "Servicios pendientes", "Servicios que deben cobrarse.", "pagos-servicios");
    $cardsDashboard[] = array("red", "fa-cart-plus", $stockCeroDashboardCount, "Productos sin stock", "Coordinar solicitud de compra con almacen.", "solicitudes-de-compra", "Ver compras");
    $cardsDashboard[] = array("orange", "fa-warning", $stockBajoDashboardCount, "Stock bajo", "Productos que aun tienen unidades pero requieren reposicion.", "productos-almacen");
    $cardsDashboard[] = array("yellow", "fa-clipboard", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM compra WHERE estado = 'pendiente'"), "Compras por aprobar", "Solicitudes de compra esperando decision.", "compras-cajero");
    $cardsDashboard[] = array("purple", "fa-tags", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM productos WHERE requiere_precio = 1 OR precio_venta <= 0"), "Productos sin precio", "Productos ingresados que esperan precio de venta.", "productos-cajero");
    $tareasDashboard = dashboardRows($dbDashboard, "
      SELECT v.codigo, v.total, v.estado_pago AS estado, v.fecha, 'Caja para cobro' AS proceso, 'Venta por cobrar' AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM ventas v
      LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = v.id_cajero
      WHERE v.estado_pago = 'pendiente'
      UNION ALL
      SELECT s.codigo, s.total, s.estado_pago AS estado, s.fecha, 'Caja para cobro de servicio' AS proceso, s.tipo_servicio AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM servicios_ventas s
      LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = s.id_cajero
      WHERE s.estado_pago IN ('pendiente','pendiente_adelanto','pendiente_final') OR (s.tipo_servicio = 'Soporte tecnico en taller' AND s.estado_pago = 'pendiente_retiro' AND s.estado_servicio = 'listo_cobro')
      UNION ALL
      SELECT c.codigo, c.total, c.estado AS estado, c.fecha, 'Aprobar compra / desembolso' AS proceso, 'Solicitud de compra' AS tipo, '-' AS vendedor, '-' AS cajero
      FROM compra c
      WHERE c.estado IN ('pendiente','en_compra')
      UNION ALL
      SELECT p.codigo, 0 AS total, 'sin_precio' AS estado, p.fecha, 'Asignar precio de venta' AS proceso, p.descripcion AS tipo, '-' AS vendedor, '-' AS cajero
      FROM productos p
      WHERE p.requiere_precio = 1 OR p.precio_venta <= 0
      ORDER BY fecha DESC
      LIMIT 10");
    break;

  case "almacen":
    $cardsDashboard[] = array("aqua", "fa-truck", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM ventas WHERE estado_pago = 'aprobado' AND estado_despacho = 'pendiente'"), "Despachos pendientes", "Ventas pagadas que deben entregarse al cliente.", "despacho");
    $cardsDashboard[] = array("red", "fa-cart-plus", $stockCeroDashboardCount, "Productos sin stock", "Crear solicitud de compra con productos en cero.", "crear-compra-almacen?stock=0", "Hacer compra");
    $cardsDashboard[] = array("orange", "fa-warning", $stockBajoDashboardCount, "Stock bajo", "Productos que aun tienen unidades pero requieren reposicion.", "crear-compra-almacen?stock=bajo", "Hacer compra");
    $cardsDashboard[] = array("green", "fa-sign-in", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM compra WHERE estado = 'entregado_almacen'"), "Ingresos pendientes", "Compras entregadas a almacen para subir stock.", "ordenes-ingreso-material");
    $cardsDashboard[] = array("purple", "fa-laptop", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicio_taller_equipos WHERE estado_equipo IN ('ingresado','recibido_almacen','retiro_solicitado','pendiente_reingreso','devuelto_almacen') OR (estado_equipo = 'retirado_tecnico' AND (id_almacenero_retiro IS NULL OR id_almacenero_retiro = 0))"), "Equipos taller", "Equipos que almacen debe recibir o entregar.", "recepcion-equipos-taller");
    $cardsDashboard[] = array("blue", "fa-cubes", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicio_taller_repuestos WHERE estado = 'solicitado'"), "Repuestos taller", "Piezas solicitadas por tecnicos para entregar.", "repuestos-taller-almacen");
    $tareasDashboard = dashboardRows($dbDashboard, "
      SELECT v.codigo, v.total, v.estado_despacho AS estado, v.fecha, 'Almacen para entrega al cliente' AS proceso, 'Despacho de venta' AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM ventas v
      LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = v.id_cajero
      WHERE v.estado_pago = 'aprobado' AND v.estado_despacho = 'pendiente'
      UNION ALL
      SELECT c.codigo, c.total, c.estado AS estado, c.fecha, 'Subir productos a stock' AS proceso, 'Orden de ingreso' AS tipo, '-' AS vendedor, '-' AS cajero
      FROM compra c
      WHERE c.estado = 'entregado_almacen'
      UNION ALL
      SELECT s.codigo, s.total, e.estado_equipo AS estado, s.fecha, 'Recepcion / entrega de equipo taller' AS proceso, s.tipo_servicio AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM servicio_taller_equipos e
      INNER JOIN servicios_ventas s ON s.id = e.id_servicio
      LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = s.id_cajero
      WHERE e.estado_equipo IN ('ingresado','recibido_almacen','retiro_solicitado','pendiente_reingreso','devuelto_almacen') OR (e.estado_equipo = 'retirado_tecnico' AND (e.id_almacenero_retiro IS NULL OR e.id_almacenero_retiro = 0))
      UNION ALL
      SELECT s.codigo, s.total, r.estado, r.fecha_solicitud AS fecha, 'Entregar repuesto a tecnico' AS proceso, 'Repuesto taller' AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM servicio_taller_repuestos r
      INNER JOIN servicios_ventas s ON s.id = r.id_servicio
      LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = s.id_cajero
      WHERE r.estado = 'solicitado'
      ORDER BY fecha DESC
      LIMIT 10");
    break;

  case "mensajero":
    $adminViendoMensajero = ($perfilDashboard == "Administrador");
    $filtroMensajeroCompra = $adminViendoMensajero ? "1=1" : "id_mensajero = :id";
    $filtroMensajeroCompraPrefijo = $adminViendoMensajero ? "1=1" : "c.id_mensajero = :id";
    $filtroMensajeroPendientes = $adminViendoMensajero
      ? "c.estado IN ('aprobado','en_compra','desembolsado')"
      : "c.estado IN ('aprobado','en_compra','desembolsado') AND (c.id_mensajero IS NULL OR c.id_mensajero = 0 OR c.id_mensajero = :id)";
    $paramsMensajero = $adminViendoMensajero ? array() : array(":id" => $idUsuarioDashboard);
    $detalleMensajero = $adminViendoMensajero ? "Solicitudes tomadas por el equipo de mensajeria." : "Solicitudes tomadas por usted.";

    $cardsDashboard[] = array("yellow", "fa-print", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM compra WHERE estado = 'aprobado' AND (id_mensajero IS NULL OR id_mensajero = 0)"), "Solicitudes aprobadas", "Compras listas para tomar e imprimir.", "solicitudes-aprobadas");
    $cardsDashboard[] = array("aqua", "fa-motorcycle", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM compra WHERE estado = 'en_compra' AND ".$filtroMensajeroCompra, $paramsMensajero), "En desembolso", $detalleMensajero." Esperando desembolso.", "solicitudes-aprobadas");
    $cardsDashboard[] = array("green", "fa-shopping-bag", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM compra WHERE estado = 'desembolsado' AND ".$filtroMensajeroCompra, $paramsMensajero), "Por entregar", "Compras con dinero desembolsado que deben llegar a almacen.", "solicitudes-aprobadas");
    $cardsDashboard[] = array("purple", "fa-check", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM compra WHERE estado = 'completado' AND ".$filtroMensajeroCompra, $paramsMensajero), "Completadas", "Compras finalizadas.", "solicitudes-aprobadas", "Ver historial");
    $tareasDashboard = dashboardRows($dbDashboard, "
      SELECT c.codigo, c.total, c.estado, c.fecha,
        CASE
          WHEN c.estado = 'aprobado' AND (c.id_mensajero IS NULL OR c.id_mensajero = 0) THEN 'Libre: tomar solicitud e imprimir'
          WHEN c.estado = 'aprobado' THEN 'Asignada, pendiente de iniciar compra'
          WHEN c.estado = 'en_compra' THEN 'Pasar por caja para desembolso'
          WHEN c.estado = 'desembolsado' THEN 'Comprar y entregar a almacen'
          ELSE c.estado
        END AS proceso,
        'Solicitud de compra' AS tipo,
        COALESCE(u.nombre, 'Libre sin asignacion') AS mensajero,
        '-' AS vendedor,
        '-' AS cajero
      FROM compra c
      LEFT JOIN usuarios u ON u.id = c.id_mensajero
      WHERE ".$filtroMensajeroPendientes."
      ORDER BY c.fecha DESC
      LIMIT 10", $paramsMensajero);
    break;

  case "tecnico":
    $adminViendoTecnico = ($perfilDashboard == "Administrador");
    $filtroTecnicoServicio = $adminViendoTecnico ? "1=1" : "id_tecnico = :id";
    $filtroTecnicoServicioPrefijo = $adminViendoTecnico ? "1=1" : "s.id_tecnico = :id";
    $filtroTecnicoRepuesto = $adminViendoTecnico ? "1=1" : "id_tecnico_solicita = :id";
    $paramsTecnico = $adminViendoTecnico ? array() : array(":id" => $idUsuarioDashboard);
    $detalleTecnico = $adminViendoTecnico ? "Servicios asignados al equipo tecnico." : "Servicios asignados a su usuario.";

    $cardsDashboard[] = array("yellow", "fa-wrench", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicios_ventas WHERE ".$filtroTecnicoServicio." AND estado_servicio IN ('asignado','en_almacen','atendiendo','retiro_solicitado','en_proceso','diagnosticado','autorizado','rep_solicitado','rep_entregado','reparado')", $paramsTecnico), "Ordenes pendientes", $detalleTecnico, "ordenes-servicio");
    $cardsDashboard[] = array("aqua", "fa-cubes", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicio_taller_repuestos WHERE ".$filtroTecnicoRepuesto." AND estado = 'solicitado'", $paramsTecnico), "Repuestos solicitados", "Piezas esperando entrega de almacen.", "ordenes-servicio");
    $cardsDashboard[] = array("green", "fa-reply", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicios_ventas WHERE ".$filtroTecnicoServicio." AND estado_servicio = 'reparado'", $paramsTecnico), "Por devolver", "Equipos reparados que deben volver a almacen.", "ordenes-servicio");
    $cardsDashboard[] = array("purple", "fa-calendar-check-o", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicios_ventas WHERE ".$filtroTecnicoServicio." AND estado_servicio = 'completado' AND DATE(fecha) = CURDATE()", $paramsTecnico), "Completados hoy", "Servicios cerrados en la fecha.", "ordenes-servicio", "Ver ordenes");
    $tareasDashboard = dashboardRows($dbDashboard, "
      SELECT s.codigo, s.total, s.estado_servicio AS estado, s.fecha,
        CASE
          WHEN s.estado_servicio IN ('asignado','en_almacen') THEN 'Atender solicitud'
          WHEN s.estado_servicio = 'atendiendo' THEN 'Solicitar retiro o registrar diagnostico'
          WHEN s.estado_servicio = 'retiro_solicitado' THEN 'Esperando entrega de almacen'
          WHEN s.estado_servicio = 'rep_solicitado' THEN 'Esperando repuesto'
          WHEN s.estado_servicio = 'rep_entregado' THEN 'Continuar reparacion'
          WHEN s.estado_servicio = 'reparado' THEN 'Devolver a almacen'
          ELSE s.estado_servicio
        END AS proceso,
        s.tipo_servicio AS tipo,
        ut.nombre AS tecnico,
        uv.nombre AS vendedor,
        uc.nombre AS cajero
      FROM servicios_ventas s
      LEFT JOIN usuarios ut ON ut.id = s.id_tecnico
      LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = s.id_cajero
      WHERE ".$filtroTecnicoServicioPrefijo." AND s.estado_servicio NOT IN ('completado','cancelado')
      ORDER BY s.fecha DESC
      LIMIT 10", $paramsTecnico);
    break;

  case "desarrollador":
    $adminViendoDesarrollador = ($perfilDashboard == "Administrador");
    $filtroDesarrollador = $adminViendoDesarrollador ? "1=1" : "id_desarrollador = :id";
    $filtroDesarrolladorPrefijo = $adminViendoDesarrollador ? "1=1" : "p.id_desarrollador = :id";
    $paramsDesarrollador = $adminViendoDesarrollador ? array() : array(":id" => $idUsuarioDashboard);
    $detalleDesarrollador = $adminViendoDesarrollador ? "Desarrollos asignados al equipo." : "Desarrollos asignados a su usuario.";

    $cardsDashboard[] = array("aqua", "fa-code", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM proyectos_software WHERE ".$filtroDesarrollador." AND estado IN ('en_desarrollo','revision_interna','revision_cliente')", $paramsDesarrollador), "Proyectos activos", $detalleDesarrollador, "proyectos");
    $cardsDashboard[] = array("yellow", "fa-clock-o", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM proyectos_software WHERE ".$filtroDesarrollador." AND estado = 'pendiente_pago_final'", $paramsDesarrollador), "Listos para cobro", "Proyectos enviados a caja para cobrar saldo final.", "proyectos");
    $cardsDashboard[] = array("green", "fa-line-chart", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM proyecto_software_avances a INNER JOIN proyectos_software p ON p.id = a.id_proyecto WHERE ".$filtroDesarrolladorPrefijo." AND DATE(a.fecha) = CURDATE()", $paramsDesarrollador), "Avances de hoy", "Registros de avance cargados durante el dia.", "proyectos", "Ver proyectos");
    $cardsDashboard[] = array("purple", "fa-folder-open", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM proyecto_software_documentos d INNER JOIN proyectos_software p ON p.id = d.id_proyecto WHERE ".$filtroDesarrolladorPrefijo, $paramsDesarrollador), "Documentos", "Documentacion subida en proyectos.", "proyectos", "Ver documentos");
    $tareasDashboard = dashboardRows($dbDashboard, "SELECT p.codigo, p.precio_total AS total, p.estado, p.fecha, 'Desarrollo de software' AS proceso, p.nombre_proyecto AS tipo, uv.nombre AS vendedor, u.nombre AS cajero FROM proyectos_software p INNER JOIN servicios_ventas s ON s.id = p.id_servicio LEFT JOIN usuarios uv ON uv.id = s.id_vendedor LEFT JOIN usuarios u ON u.id = s.id_cajero WHERE ".$filtroDesarrolladorPrefijo." AND p.estado NOT IN ('completado','cancelado') ORDER BY p.fecha DESC LIMIT 8", $paramsDesarrollador);
    break;

  case "administrador":
  default:
    $cardsDashboard[] = array("green", "fa-shopping-cart", $ventasProductosHoyDashboard, "Productos vendidos hoy", "Total cobrado en ventas de productos durante el dia.", "reportes?fechaInicial=".date("Y-m-d")."&fechaFinal=".date("Y-m-d"), "Ver reporte");
    $cardsDashboard[] = array("teal", "fa-wrench", $ventasServiciosHoyDashboard, "Servicios cobrados hoy", "Total cobrado por servicios durante el dia.", "reportes?fechaInicial=".date("Y-m-d")."&fechaFinal=".date("Y-m-d"), "Ver reporte");
    $cardsDashboard[] = array("yellow", "fa-money", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM ventas WHERE estado_pago = 'pendiente'"), "Ventas por cobrar", "Pagos pendientes en caja.", "pagos-ventas");
    $cardsDashboard[] = array("aqua", "fa-truck", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM ventas WHERE estado_pago = 'aprobado' AND estado_despacho = 'pendiente'"), "Despachos pendientes", "Productos cobrados por entregar.", "despacho");
    $cardsDashboard[] = array("red", "fa-cart-plus", $stockCeroDashboardCount, "Productos sin stock", "Crear solicitud de compra con productos en cero.", "crear-compra-almacen?stock=0", "Hacer compra");
    $cardsDashboard[] = array("orange", "fa-warning", $stockBajoDashboardCount, "Stock bajo", "Productos que aun tienen unidades pero requieren reposicion.", "crear-compra-almacen?stock=bajo", "Hacer compra");
    $cardsDashboard[] = array("purple", "fa-wrench", dashboardCount($dbDashboard, "SELECT COUNT(*) FROM servicios_ventas WHERE estado_servicio NOT IN ('completado')"), "Servicios activos", "Servicios y taller con trabajo pendiente.", "administrar-servicios");
    $tareasDashboard = dashboardRows($dbDashboard, "
      SELECT v.codigo, v.total, v.estado_pago AS estado, v.fecha, 'Caja para cobro' AS proceso, 'Venta / cobro' AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM ventas v
      LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = v.id_cajero
      WHERE v.estado_pago = 'pendiente'
      UNION ALL
      SELECT v.codigo, v.total, v.estado_despacho AS estado, v.fecha, 'Almacen para entrega' AS proceso, 'Despacho de venta' AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM ventas v
      LEFT JOIN usuarios uv ON uv.id = v.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = v.id_cajero
      WHERE v.estado_pago = 'aprobado' AND v.estado_despacho = 'pendiente'
      UNION ALL
      SELECT s.codigo, s.total, s.estado_servicio AS estado, s.fecha,
        CASE
          WHEN s.estado_pago IN ('pendiente','pendiente_adelanto','pendiente_final','pendiente_retiro') THEN 'Caja para cobro'
          WHEN s.estado_servicio IN ('asignado','en_almacen','atendiendo','retiro_solicitado','en_proceso','diagnosticado','autorizado','rep_solicitado','rep_entregado','reparado') THEN 'Tecnico / taller'
          WHEN s.estado_servicio IN ('devuelto_almacen','listo_cobro') THEN 'Almacen / caja'
          ELSE s.estado_servicio
        END AS proceso,
        s.tipo_servicio AS tipo, uv.nombre AS vendedor, uc.nombre AS cajero
      FROM servicios_ventas s
      LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
      LEFT JOIN usuarios uc ON uc.id = s.id_cajero
      WHERE s.estado_servicio NOT IN ('completado','cancelado')
      UNION ALL
      SELECT c.codigo, c.total, c.estado AS estado, c.fecha, 'Compras / desembolso / ingreso' AS proceso, 'Solicitud de compra' AS tipo, '-' AS vendedor, '-' AS cajero
      FROM compra c
      WHERE c.estado IN ('pendiente','aprobado','en_compra','desembolsado','entregado_almacen')
      UNION ALL
      SELECT p.codigo, p.precio_total AS total, p.estado COLLATE utf8_spanish_ci AS estado, p.fecha, 'Proyecto de software' AS proceso, p.nombre_proyecto COLLATE utf8_spanish_ci AS tipo, uv.nombre AS vendedor, ud.nombre AS cajero
      FROM proyectos_software p
      INNER JOIN servicios_ventas s ON s.id = p.id_servicio
      LEFT JOIN usuarios uv ON uv.id = s.id_vendedor
      LEFT JOIN usuarios ud ON ud.id = p.id_desarrollador
      WHERE p.estado NOT IN ('completado','cancelado')
      ORDER BY fecha DESC
      LIMIT 12");
    break;
}

?>

<style>
  .tm-welcome{
    background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(239,246,255,.94));
    border:1px solid rgba(219,228,238,.96);
    border-radius:18px;
    padding:22px 24px;
    margin-bottom:20px;
    box-shadow:0 18px 45px rgba(15,23,42,.10);
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:center;
    flex-wrap:wrap;
    position:relative;
    overflow:hidden;
  }
  .tm-welcome:after{
    content:"";
    position:absolute;
    width:220px;
    height:220px;
    right:-70px;
    top:-110px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(37,99,235,.16),transparent 68%);
    pointer-events:none;
  }
  .tm-welcome h2{
    margin:0 0 6px;
    font-size:27px;
    font-weight:850;
    color:#172033;
  }
  .tm-welcome p{
    margin:0;
    color:#64748b;
    font-weight:600;
  }
  .tm-clock{
    min-width:220px;
    text-align:right;
    position:relative;
    z-index:1;
    background:rgba(255,255,255,.72);
    border:1px solid rgba(219,228,238,.8);
    border-radius:14px;
    padding:12px 14px;
  }
  .tm-clock strong{
    display:block;
    font-size:27px;
    line-height:1;
    color:#2563eb;
    font-weight:850;
  }
  .tm-clock span{
    color:#64748b;
    font-weight:750;
    font-size:12px;
  }
  .tm-dashboard-card{
    display:block;
    min-height:178px;
    margin-bottom:18px;
    padding:18px;
    border-radius:18px;
    color:#172033;
    background:#fff;
    border:1px solid rgba(219,228,238,.96);
    box-shadow:0 16px 38px rgba(15,23,42,.10);
    text-decoration:none !important;
    overflow:hidden;
    position:relative;
    transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease;
  }
  .tm-dashboard-card:hover{
    transform:translateY(-3px);
    box-shadow:0 24px 52px rgba(15,23,42,.14);
    border-color:rgba(37,99,235,.28);
    color:#172033;
  }
  .tm-dashboard-card:after{
    content:"";
    position:absolute;
    width:130px;
    height:130px;
    right:-42px;
    bottom:-58px;
    border-radius:50%;
    opacity:.12;
    background:currentColor;
  }
  .tm-card-top{
    display:flex;
    justify-content:space-between;
    gap:10px;
    align-items:flex-start;
    margin-bottom:16px;
    position:relative;
    z-index:1;
  }
  .tm-card-icon{
    width:44px;
    height:44px;
    border-radius:14px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    background:linear-gradient(135deg,#2563eb,#0ea5e9);
    box-shadow:0 12px 24px rgba(37,99,235,.20);
    font-size:18px;
  }
  .tm-card-action{
    color:#64748b;
    background:#f8fafc;
    border:1px solid #e7edf5;
    border-radius:999px;
    padding:5px 9px;
    font-size:11px;
    font-weight:850;
    white-space:nowrap;
  }
  .tm-dashboard-card strong{
    display:block;
    font-size:30px;
    line-height:1;
    color:#0f172a;
    font-weight:900;
    margin-bottom:8px;
    position:relative;
    z-index:1;
  }
  .tm-dashboard-card h3{
    margin:0 0 7px;
    font-size:16px;
    font-weight:850;
    color:#172033;
    position:relative;
    z-index:1;
  }
  .tm-dashboard-card p{
    margin:0;
    min-height:38px;
    color:#64748b;
    font-size:12px;
    line-height:1.45;
    font-weight:600;
    position:relative;
    z-index:1;
  }
  .tm-card-green .tm-card-icon{background:linear-gradient(135deg,#16a34a,#22c55e);}
  .tm-card-aqua .tm-card-icon,
  .tm-card-teal .tm-card-icon,
  .tm-card-blue .tm-card-icon{background:linear-gradient(135deg,#0284c7,#38bdf8);}
  .tm-card-yellow .tm-card-icon,
  .tm-card-orange .tm-card-icon{background:linear-gradient(135deg,#f59e0b,#f97316);}
  .tm-card-red .tm-card-icon{background:linear-gradient(135deg,#ef4444,#f97316);}
  .tm-card-purple .tm-card-icon{background:linear-gradient(135deg,#7c3aed,#a855f7);}
  .tm-card-green{color:#16a34a;}
  .tm-card-aqua,.tm-card-teal,.tm-card-blue{color:#0284c7;}
  .tm-card-yellow,.tm-card-orange{color:#f59e0b;}
  .tm-card-red{color:#ef4444;}
  .tm-card-purple{color:#7c3aed;}
  .tm-dashboard-panel{
    border:1px solid rgba(219,228,238,.96) !important;
    border-radius:18px !important;
    box-shadow:0 18px 45px rgba(15,23,42,.10) !important;
    overflow:hidden;
    background:#fff;
  }
  .tm-dashboard-panel .box-header{
    background:linear-gradient(180deg,#fff,#f8fafc);
    padding:16px 18px;
  }
  .tm-dashboard-panel .box-title{
    font-weight:850;
    color:#172033;
  }
  .tm-dashboard-table{
    margin:0;
  }
  .tm-dashboard-table tr:first-child th{
    border-top:0;
  }
  .tm-dashboard-table td{
    border-top:1px solid #edf2f7 !important;
  }
  .tm-task-state{
    text-transform:capitalize;
    background:#e0f2fe !important;
    color:#0369a1 !important;
  }
  @media(max-width:767px){
    .tm-clock{text-align:left; min-width:0;}
    .tm-welcome{padding:18px;}
    .tm-dashboard-card{min-height:158px;}
  }
  /* Ajuste final TechMind: mismo lenguaje visual del index publico */
  .tm-welcome{
    background:radial-gradient(circle at 92% 12%, rgba(93,135,255,.12), transparent 36%), rgba(255,255,255,.22);
    border-color:rgba(223,232,243,.82);
    box-shadow:0 18px 42px rgba(23,75,134,.10);
    backdrop-filter:none;
  }
  .tm-welcome:after{
    background:radial-gradient(circle,rgba(93,135,255,.18),transparent 68%);
  }
  .tm-welcome p,
  .tm-clock span,
  .tm-dashboard-card p{
    color:#61718b;
  }
  .tm-clock{
    background:rgba(255,255,255,.28);
    border-color:rgba(223,232,243,.75);
  }
  .tm-clock strong{
    color:#174b86;
  }
  .tm-dashboard-card{
    color:#174b86;
    background:rgba(255,255,255,.24);
    border-color:rgba(223,232,243,.86);
    box-shadow:0 16px 38px rgba(23,75,134,.09);
    backdrop-filter:none;
  }
  .tm-dashboard-card:hover{
    border-color:rgba(93,135,255,.30);
    box-shadow:0 24px 52px rgba(23,75,134,.13);
  }
  .tm-card-icon,
  .tm-card-green .tm-card-icon,
  .tm-card-aqua .tm-card-icon,
  .tm-card-teal .tm-card-icon,
  .tm-card-blue .tm-card-icon,
  .tm-card-yellow .tm-card-icon,
  .tm-card-orange .tm-card-icon,
  .tm-card-red .tm-card-icon,
  .tm-card-purple .tm-card-icon{
    background:linear-gradient(135deg,#174b86,#5d87ff);
    box-shadow:0 12px 24px rgba(23,75,134,.18);
  }
  .tm-card-action{
    color:#174b86;
    background:rgba(236,242,255,.54);
    border-color:rgba(93,135,255,.22);
  }
  .tm-card-green,
  .tm-card-aqua,
  .tm-card-teal,
  .tm-card-blue,
  .tm-card-yellow,
  .tm-card-orange,
  .tm-card-red,
  .tm-card-purple{
    color:#174b86;
  }
  .tm-dashboard-panel{
    box-shadow:0 18px 42px rgba(23,75,134,.10) !important;
    background:rgba(255,255,255,.30);
    backdrop-filter:none;
  }
  .tm-dashboard-panel .box-header{
    background:linear-gradient(180deg,rgba(255,255,255,.86),rgba(245,248,252,.72));
  }
  .tm-task-state{
    background:rgba(236,242,255,.78) !important;
    color:#174b86 !important;
  }
  body.tm-dark-mode .tm-welcome,
  body.tm-dark-mode .tm-dashboard-card,
  body.tm-dark-mode .tm-dashboard-panel,
  body.tm-dark-mode .tm-roles-panel,
  body.tm-dark-mode .tm-role-card{
    background:rgba(16,26,46,.72) !important;
    border-color:#22314e !important;
    box-shadow:0 18px 42px rgba(0,0,0,.28) !important;
  }
  body.tm-dark-mode .tm-welcome h2,
  body.tm-dark-mode .tm-dashboard-card strong,
  body.tm-dark-mode .tm-dashboard-card h3,
  body.tm-dark-mode .tm-dashboard-panel .box-title{
    color:#e5edf7;
  }
  body.tm-dark-mode .tm-welcome p,
  body.tm-dark-mode .tm-dashboard-card p,
  body.tm-dark-mode .tm-clock span{
    color:#9fb0c7;
  }
  body.tm-dark-mode .tm-clock,
  body.tm-dark-mode .tm-card-action{
    background:rgba(23,35,59,.72);
    border-color:#2b3b5e;
  }
  body.tm-dark-mode .tm-card-action,
  body.tm-dark-mode .tm-clock strong{
    color:#8fb3ff;
  }
  body.tm-dark-mode .tm-dashboard-panel .box-header{
    background:rgba(23,35,59,.88);
  }
  body.tm-dark-mode .tm-dashboard-table th,
  body.tm-dark-mode .tm-dashboard-table td{
    color:#e5edf7 !important;
  }
  body.tm-dark-mode .tm-dashboard-table td b{
    color:#ffffff !important;
  }
  body.tm-dark-mode .tm-task-state{
    background:rgba(93,135,255,.20) !important;
    color:#dbeafe !important;
  }
</style>

<?php
$mostrarTecnicoAsignadoDashboard = ($perfilDashboard == "Administrador" && $rolDashboard == "tecnico");
$mostrarMensajeroAsignadoDashboard = ($perfilDashboard == "Administrador" && $rolDashboard == "mensajero");
$columnasExtraAsignacionDashboard = ($mostrarTecnicoAsignadoDashboard || $mostrarMensajeroAsignadoDashboard) ? 1 : 0;
?>

<div class="tm-welcome">
  <div>
    <h2>Hola, <?php echo htmlspecialchars($nombreDashboard, ENT_QUOTES, "UTF-8"); ?></h2>
    <p>Rol: <b><?php echo htmlspecialchars($rolRealTextoDashboard, ENT_QUOTES, "UTF-8"); ?></b>. Estas son las tareas pendientes para atender.</p>
  </div>
  <div class="tm-clock">
    <strong id="tmDashboardHora">--:--</strong>
    <span id="tmDashboardFecha">Cargando fecha</span>
  </div>
</div>

<div class="row">
  <?php foreach($cardsDashboard as $card): ?>
    <?php dashboardCard($card[0], $card[1], $card[2], $card[3], $card[4], $card[5], $card[6] ?? "Atender"); ?>
  <?php endforeach; ?>
</div>

<div class="row">
  <div class="col-xs-12">
    <div class="box box-primary tm-dashboard-panel">
      <div class="box-header with-border">
        <h3 class="box-title">Pendientes y notificaciones del rol</h3>
      </div>
      <div class="box-body table-responsive no-padding">
        <table class="table table-hover tm-dashboard-table">
          <tr>
            <th>Tipo</th>
            <th>Codigo</th>
            <th>Estado</th>
            <th>Proceso</th>
            <?php if($mostrarTecnicoAsignadoDashboard): ?>
              <th>Tecnico asignado</th>
            <?php endif; ?>
            <?php if($mostrarMensajeroAsignadoDashboard): ?>
              <th>Mensajero asignado</th>
            <?php endif; ?>
            <th>Vendedor</th>
            <th>Cajero</th>
            <th>Total</th>
            <th>Fecha</th>
          </tr>
          <?php if(count($tareasDashboard) == 0): ?>
            <tr><td colspan="<?php echo 8 + $columnasExtraAsignacionDashboard; ?>" class="text-center text-muted">No hay pendientes para este rol en este momento.</td></tr>
          <?php endif; ?>
          <?php foreach($tareasDashboard as $tarea): ?>
            <tr>
              <td><?php echo htmlspecialchars($tarea["tipo"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
              <td><?php echo htmlspecialchars($tarea["codigo"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
              <td><span class="label label-info tm-task-state"><?php echo htmlspecialchars(dashboardEstadoVisible($tarea["estado"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span></td>
              <td><?php echo htmlspecialchars($tarea["proceso"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
              <?php if($mostrarTecnicoAsignadoDashboard): ?>
                <td><b><?php echo htmlspecialchars($tarea["tecnico"] ?? "Sin tecnico", ENT_QUOTES, "UTF-8"); ?></b></td>
              <?php endif; ?>
              <?php if($mostrarMensajeroAsignadoDashboard): ?>
                <td><b><?php echo htmlspecialchars($tarea["mensajero"] ?? "Libre sin asignacion", ENT_QUOTES, "UTF-8"); ?></b></td>
              <?php endif; ?>
              <td><?php echo htmlspecialchars($tarea["vendedor"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
              <td><?php echo htmlspecialchars($tarea["cajero"] ?? "-", ENT_QUOTES, "UTF-8"); ?></td>
              <td>Bs <?php echo number_format((float)($tarea["total"] ?? 0), 2); ?></td>
              <td><?php echo htmlspecialchars($tarea["fecha"] ?? "", ENT_QUOTES, "UTF-8"); ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function actualizarRelojDashboard(){
  var ahora = new Date();
  var hora = ahora.toLocaleTimeString("es-BO", {hour:"2-digit", minute:"2-digit", second:"2-digit"});
  var fecha = ahora.toLocaleDateString("es-BO", {weekday:"long", year:"numeric", month:"long", day:"numeric"});
  var horaEl = document.getElementById("tmDashboardHora");
  var fechaEl = document.getElementById("tmDashboardFecha");
  if(horaEl){ horaEl.textContent = hora; }
  if(fechaEl){ fechaEl.textContent = fecha; }
}
actualizarRelojDashboard();
setInterval(actualizarRelojDashboard, 1000);
</script>
