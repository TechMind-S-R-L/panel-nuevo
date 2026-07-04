<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once "../modelos/conexion.php";
require_once "../modelos/web-consultas.modelo.php";

if (empty($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
    http_response_code(401);
    echo json_encode(["ok" => false, "message" => "Sesion finalizada."]);
    exit;
}

$puedeResponder = ($_SESSION["perfil"] ?? "") === "Administrador" || ($_SESSION["rol"] ?? "") === "vendedor";
if (!$puedeResponder) {
    http_response_code(403);
    echo json_encode(["ok" => false, "message" => "Sin permiso."]);
    exit;
}

ModeloWebConsultas::mdlAsegurarTablas();
$accion = $_POST["accion"] ?? $_GET["accion"] ?? "estado";
$idConsulta = (int)($_POST["id_consulta"] ?? $_GET["id_consulta"] ?? 0);

function tmConsultaFechaAjax($fecha) {
    $ts = strtotime((string)$fecha);
    return $ts ? date("d/m/Y H:i", $ts) : (string)$fecha;
}

function tmConsultaPayload($idConsulta) {
    $bandeja = ModeloWebConsultas::mdlConsultasBandeja();
    $consulta = $idConsulta > 0 ? ModeloWebConsultas::mdlConsultaPorId($idConsulta) : null;
    $mensajes = [];

    if ($consulta) {
        ModeloWebConsultas::mdlMarcarLeidosInterno($idConsulta);
        foreach (ModeloWebConsultas::mdlMensajesConsulta($idConsulta) as $mensaje) {
            $mensajes[] = [
                "id" => (int)$mensaje["id"],
                "emisor" => (string)$mensaje["emisor"],
                "mensaje" => (string)$mensaje["mensaje"],
                "autor" => $mensaje["emisor"] === "cliente"
                    ? (string)($consulta["cliente"] ?? "Cliente")
                    : (string)($mensaje["usuario_nombre"] ?: "TechMind"),
                "fecha" => tmConsultaFechaAjax($mensaje["fecha"])
            ];
        }
    }

    $items = [];
    foreach ($bandeja as $item) {
        $items[] = [
            "id" => (int)$item["id"],
            "cliente" => (string)$item["cliente"],
            "asunto" => (string)$item["asunto"],
            "ultimo_mensaje" => (string)$item["ultimo_mensaje"],
            "estado" => (string)$item["estado"],
            "no_leidos" => (int)$item["no_leidos"]
        ];
    }

    return [
        "ok" => true,
        "consulta" => $consulta ? [
            "id" => (int)$consulta["id"],
            "cliente" => (string)$consulta["cliente"],
            "estado" => (string)$consulta["estado"]
        ] : null,
        "bandeja" => $items,
        "mensajes" => $mensajes,
        "firma" => hash("sha256", json_encode([$items, $mensajes], JSON_UNESCAPED_UNICODE))
    ];
}

if ($accion === "responder") {
    $mensaje = trim((string)($_POST["mensaje"] ?? ""));
    if ($idConsulta <= 0 || $mensaje === "") {
        echo json_encode(["ok" => false, "message" => "Mensaje invalido."]);
        exit;
    }
    ModeloWebConsultas::mdlResponder($idConsulta, (int)($_SESSION["id"] ?? 0), mb_substr($mensaje, 0, 3000));
}

echo json_encode(tmConsultaPayload($idConsulta), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
