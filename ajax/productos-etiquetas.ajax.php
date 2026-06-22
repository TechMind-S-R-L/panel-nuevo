<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

require_once "../modelos/ModeloInventario.php";
require_once "../modelos/logs.modelo.php";
require_once "../controladores/logs.controlador.php";

$esAdministrador = ($_SESSION["perfil"] ?? "") === "Administrador";
$esAlmacen = ($_SESSION["rol"] ?? "") === "almacen";
if (!$esAdministrador && !$esAlmacen) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "No tiene permiso para imprimir etiquetas."]);
    exit;
}

$idProducto = (int)($_POST["idProducto"] ?? 0);
$respuesta = ModeloInventario::mdlPrepararEtiquetasProducto($idProducto);

if (($respuesta["status"] ?? "") === "ok" && (int)$respuesta["generados"] > 0) {
    ControladorLogs::ctrRegistrarLog(
        "generar_etiquetas_producto",
        "productos-almacen",
        "Producto ".$idProducto.": ".$respuesta["generados"]." código(s) generado(s) para impresión"
    );
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
