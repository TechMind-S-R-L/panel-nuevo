<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once "../modelos/conexion.php";

if (empty($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    http_response_code(401);
    echo json_encode(["ok" => false, "message" => "Sesión finalizada."]);
    exit;
}

$db = Conexion::conectar();
$idUsuario = (int)($_SESSION["id"] ?? 0);
$perfil = (string)($_SESSION["perfil"] ?? "");
$rol = (string)($_SESSION["rol"] ?? "");
$vistaRol = (string)($_SESSION["vistaRolMenu"] ?? "");
$esAdminReal = $perfil === "Administrador";
if ($esAdminReal && $vistaRol !== "" && $vistaRol !== "administrador") {
    $rol = $vistaRol;
    $perfil = $vistaRol === "vendedor" ? "Vendedor" : "Especial";
}

function tmRtCount($db, $sql, $params = []) {
    try {
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

$badges = [];
$badges["ventas"] = ($rol === "vendedor" && $perfil !== "Administrador")
    ? tmRtCount($db, "SELECT COUNT(*) FROM ventas WHERE id_vendedor=:id AND (estado_pago='pendiente' OR (estado_pago='aprobado' AND estado_despacho='pendiente'))", [":id" => $idUsuario])
    : tmRtCount($db, "SELECT COUNT(*) FROM ventas WHERE estado_pago='pendiente' OR (estado_pago='aprobado' AND estado_despacho='pendiente')");
$badges["pagos-ventas"] = tmRtCount($db, "SELECT COUNT(*) FROM ventas WHERE estado_pago='pendiente'");
$badges["despacho"] = tmRtCount($db, "SELECT COUNT(*) FROM ventas WHERE estado_pago='aprobado' AND estado_despacho='pendiente'");
$badges["solicitudes-web"] = tmRtCount($db, "SELECT COUNT(*) FROM cotizaciones WHERE origen='web' AND estado_web IN ('pendiente','en_revision')");
$badges["consultas-web"] = tmRtCount($db, "SELECT COUNT(*) FROM web_consulta_mensajes WHERE emisor='cliente' AND leido_interno=0");
$badges["pagos-servicios"] = tmRtCount($db, "SELECT COUNT(*) FROM servicios_ventas WHERE estado_pago IN ('pendiente','pendiente_adelanto','pendiente_final') OR (tipo_servicio='Soporte tecnico en taller' AND estado_pago='pendiente_retiro' AND estado_servicio='listo_cobro')");
$badges["administrar-servicios"] = ($rol === "vendedor" && $perfil !== "Administrador")
    ? tmRtCount($db, "SELECT COUNT(*) FROM servicios_ventas WHERE id_vendedor=:id AND estado_servicio NOT IN ('completado','cancelado') AND (estado_pago LIKE 'pendiente%' OR estado_servicio LIKE 'pendiente%' OR estado_servicio='listo_cobro')", [":id" => $idUsuario])
    : tmRtCount($db, "SELECT COUNT(*) FROM servicios_ventas WHERE estado_servicio NOT IN ('completado','cancelado') AND (estado_pago LIKE 'pendiente%' OR estado_servicio LIKE 'pendiente%' OR estado_servicio='listo_cobro')");
$badges["ordenes-servicio"] = ($rol === "tecnico" && !$esAdminReal)
    ? tmRtCount($db, "SELECT COUNT(*) FROM servicios_ventas WHERE id_tecnico=:id AND tipo_servicio<>'Desarrollo de software' AND tipo_servicio NOT LIKE '%software%' AND estado_servicio IN ('asignado','en_almacen','atendiendo','retiro_solicitado','en_proceso','diagnosticado','autorizado','rep_solicitado','rep_entregado','reparado')", [":id" => $idUsuario])
    : tmRtCount($db, "SELECT COUNT(*) FROM servicios_ventas WHERE tipo_servicio<>'Desarrollo de software' AND tipo_servicio NOT LIKE '%software%' AND estado_servicio IN ('asignado','en_almacen','atendiendo','retiro_solicitado','en_proceso','diagnosticado','autorizado','rep_solicitado','rep_entregado','reparado')");
$badges["proyectos"] = ($rol === "desarrollador" && $perfil !== "Administrador")
    ? tmRtCount($db, "SELECT COUNT(*) FROM proyectos_software p INNER JOIN servicios_ventas s ON s.id=p.id_servicio WHERE p.id_desarrollador=:id AND p.estado NOT IN ('completado','cancelado')", [":id" => $idUsuario])
    : tmRtCount($db, "SELECT COUNT(*) FROM proyectos_software p INNER JOIN servicios_ventas s ON s.id=p.id_servicio WHERE p.estado NOT IN ('completado','cancelado')");
$badges["solicitudes-de-compra"] = $rol === "cajero"
    ? tmRtCount($db, "SELECT COUNT(*) FROM compra WHERE estado IN ('pendiente','en_compra','rendicion_pendiente')")
    : tmRtCount($db, "SELECT COUNT(*) FROM compra WHERE estado IN ('pendiente','aprobado','en_compra','desembolsado','rendicion_pendiente','compra_rendida','entregado_almacen')");
$badges["solicitudes-aprobadas"] = ($esAdminReal && $rol === "mensajero")
    ? tmRtCount($db, "SELECT COUNT(*) FROM compra WHERE estado IN ('aprobado','en_compra','desembolsado','rendicion_pendiente','compra_rendida')")
    : tmRtCount($db, "SELECT COUNT(*) FROM compra WHERE estado IN ('aprobado','en_compra','desembolsado','rendicion_pendiente','compra_rendida') AND (id_mensajero IS NULL OR id_mensajero=0 OR id_mensajero=:id)", [":id" => $idUsuario]);
$badges["ordenes-ingreso-material"] = tmRtCount($db, "SELECT COUNT(*) FROM compra WHERE estado IN ('compra_rendida','entregado_almacen')");
$badges["recepcion-equipos-taller"] = tmRtCount($db, "SELECT COUNT(*) FROM servicio_taller_equipos WHERE estado_equipo IN ('ingresado','recibido_almacen','retiro_solicitado','pendiente_reingreso','devuelto_almacen') OR (estado_equipo='retirado_tecnico' AND (id_almacenero_retiro IS NULL OR id_almacenero_retiro=0))");
$badges["repuestos-taller-almacen"] = tmRtCount($db, "SELECT COUNT(*) FROM servicio_taller_repuestos WHERE estado='solicitado'");
$badges["productos-cajero"] = tmRtCount($db, "SELECT COUNT(*) FROM productos WHERE stock>0 AND (requiere_precio=1 OR precio_venta<=0)");

$ultimaActividad = tmRtCount(
    $db,
    "SELECT COALESCE(MAX(id),0) FROM sistema_logs WHERE modulo NOT IN ('login','logs-sistema')"
);
$firma = hash("sha256", json_encode([$badges, $ultimaActividad]));
echo json_encode([
    "ok" => true,
    "badges" => $badges,
    "ultima_actividad" => $ultimaActividad,
    "firma" => $firma,
    "hora" => date("H:i:s")
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
