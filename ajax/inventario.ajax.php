<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../modelos/ModeloInventario.php";

header('Content-Type: application/json');

$esAdministrador = ($_SESSION["perfil"] ?? "") === "Administrador";
$esAlmacen = ($_SESSION["rol"] ?? "") === "almacen";
if (!$esAdministrador && !$esAlmacen) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "No tiene permiso para registrar ingresos de almacén."]);
    return;
}

if (isset($_POST["accion"]) && $_POST["accion"] === "resumenIngreso" && isset($_POST["idCompra"])) {
    echo json_encode(ModeloInventario::mdlResumenIngresoCompra((int)$_POST["idCompra"]));
    return;
}

if (isset($_POST["idCompra"], $_POST["idProducto"], $_POST["codigoBarrasUnico"])) {
    $respuesta = ModeloInventario::mdlRegistrarUnidadAprobada([
        "id_compra" => (int)$_POST["idCompra"],
        "id_producto" => (int)$_POST["idProducto"],
        "codigo_barras_unico" => trim($_POST["codigoBarrasUnico"]),
        "generar_codigo" => !empty($_POST["generarCodigo"])
    ]);

    echo json_encode($respuesta);
    return;
}

echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
