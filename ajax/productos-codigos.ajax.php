<?php

require_once "../controladores/productos.controlador.php";
require_once "../modelos/productos.modelo.php";

header("Content-Type: application/json; charset=utf-8");

$idProducto = (int)($_POST["idProducto"] ?? 0);

if($idProducto <= 0){
	echo json_encode(array("ok" => false, "codigos" => array()));
	return;
}

$codigos = ControladorProductos::ctrMostrarCodigosUnicosProducto($idProducto);

echo json_encode(array(
	"ok" => true,
	"codigos" => $codigos
));

