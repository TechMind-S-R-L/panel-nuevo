<?php

require_once "../controladores/compras.controlador.php";
require_once "../modelos/compras.modelo.php";

if (isset($_POST["idCompra"]) && isset($_POST["estado"])) {
    $idCompra = $_POST["idCompra"];
    $nuevoEstado = $_POST["estado"];

    $respuesta = ControladorCompras::ctrCambiarEstadoCompra($idCompra, $nuevoEstado);

    echo $respuesta; // Devuelve "ok" o "error"
}

if (isset($_POST["verificarCompleta"])) {
    $idCompra = intval($_POST["idCompra"]);

    // Revisa si todos los productos de esa compra ya tienen el número correcto de códigos únicos registrados
    $completa = ModeloCompras::mdlVerificarCompraCompleta($idCompra);

    if ($completa) {
        ModeloCompras::mdlActualizarEstadoCompra($idCompra, "completado");
        echo "completa";
    } else {
        echo "incompleta";
    }
    exit();
}

?>




