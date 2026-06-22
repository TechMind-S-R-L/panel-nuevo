<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

require_once "../modelos/ModeloInventario.php";
require_once "../modelos/logs.modelo.php";
require_once "../controladores/logs.controlador.php";

if (($_SESSION["perfil"] ?? "") !== "Administrador") {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Este proceso es exclusivo del administrador."]);
    exit;
}

$accion = $_POST["accion"] ?? "";

if ($accion === "buscarProductos") {
    echo json_encode([
        "status" => "ok",
        "productos" => ModeloInventario::mdlBuscarProductosIngresoAdmin($_POST["termino"] ?? "", 15)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($accion !== "registrarIngresoDirecto") {
    echo json_encode(["status" => "error", "message" => "Acción no reconocida."]);
    exit;
}

$esNuevo = ($_POST["tipoProducto"] ?? "existente") === "nuevo";
$rutaImagen = "vistas/img/productos/default/anonymous.png";

if ($esNuevo && isset($_FILES["imagenProducto"]) && is_uploaded_file($_FILES["imagenProducto"]["tmp_name"])) {
    $tipos = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];
    $tipo = mime_content_type($_FILES["imagenProducto"]["tmp_name"]);
    if (!isset($tipos[$tipo])) {
        echo json_encode(["status" => "error", "message" => "La imagen debe ser JPG, PNG o WEBP."]);
        exit;
    }
    if ((int)$_FILES["imagenProducto"]["size"] > 5 * 1024 * 1024) {
        echo json_encode(["status" => "error", "message" => "La imagen no puede superar 5 MB."]);
        exit;
    }

    $carpeta = "../vistas/img/productos/ingresos-admin";
    if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
        echo json_encode(["status" => "error", "message" => "No se pudo crear la carpeta de imágenes."]);
        exit;
    }
    $nombreArchivo = "producto-".date("YmdHis")."-".mt_rand(1000, 9999).".".$tipos[$tipo];
    if (!move_uploaded_file($_FILES["imagenProducto"]["tmp_name"], $carpeta."/".$nombreArchivo)) {
        echo json_encode(["status" => "error", "message" => "No se pudo guardar la imagen."]);
        exit;
    }
    $rutaImagen = "vistas/img/productos/ingresos-admin/".$nombreArchivo;
}

$respuesta = ModeloInventario::mdlRegistrarIngresoDirectoAdmin([
    "id_admin" => (int)($_SESSION["id"] ?? 0),
    "es_nuevo" => $esNuevo,
    "id_producto" => (int)($_POST["idProducto"] ?? 0),
    "id_categoria" => (int)($_POST["idCategoria"] ?? 0),
    "id_marca" => (int)($_POST["idMarca"] ?? 0),
    "descripcion" => $_POST["descripcion"] ?? "",
    "detalle" => $_POST["detalle"] ?? "",
    "imagen" => $rutaImagen,
    "cantidad" => (int)($_POST["cantidad"] ?? 0),
    "precio_compra" => (float)($_POST["precioCompra"] ?? 0),
    "precio_venta" => (float)($_POST["precioVenta"] ?? 0),
    "codigo_producto" => $_POST["codigoProducto"] ?? "",
    "codigo_general" => $_POST["codigoGeneral"] ?? "",
    "prefijo_unico" => $_POST["prefijoUnico"] ?? "",
    "codigos_unicos" => (function() {
        $codigos = json_decode($_POST["codigosUnicos"] ?? "[]", true);
        return is_array($codigos) ? $codigos : [];
    })(),
    "observacion" => $_POST["observacion"] ?? ""
]);

if (($respuesta["status"] ?? "") === "ok") {
    ControladorLogs::ctrRegistrarLog(
        "ingreso_directo_inventario",
        "ingreso-directo-admin",
        "Ingreso #".$respuesta["id_ingreso"].": ".$respuesta["cantidad"]." unidad(es) del producto ".$respuesta["producto"]
    );
} elseif ($esNuevo && $rutaImagen !== "vistas/img/productos/default/anonymous.png") {
    $archivoImagen = "../".$rutaImagen;
    if (is_file($archivoImagen)) {
        @unlink($archivoImagen);
    }
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
