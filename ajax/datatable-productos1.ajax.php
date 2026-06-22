<?php

require_once "../controladores/productos.controlador.php";
require_once "../modelos/productos.modelo.php";

class TablaProductosCompras {

    /*=============================================
     MOSTRAR LA TABLA DE PRODUCTOS
    =============================================*/ 
    public function mostrarTablaProductosCompras() {

        $item = null;
        $valor = null;
        $orden = "id";

        // Obtener todos los productos
        $productos = ControladorProductos::ctrMostrarProductos($item, $valor, $orden);

        // Filtrar productos de compras aprobadas
        $productosAprobados = array_filter($productos, function($producto) {
            return isset($producto["estado_compra"]) && strtolower(trim($producto["estado_compra"])) === "aprobado";
        });

        if (count($productosAprobados) == 0) {
            echo json_encode(array("data" => array()));
            return;
        }

        $datos = array();

        // Convertimos el array filtrado en una lista indexada
        $productosAprobados = array_values($productosAprobados);

        for ($i = 0; $i < count($productosAprobados); $i++) {

            /*=============================================
            TRAEMOS LA IMAGEN
            =============================================*/ 
            $imagenSeguro = htmlspecialchars($productosAprobados[$i]["imagen"] ?? "", ENT_QUOTES, "UTF-8");
            $imagen = "<img src='" . $imagenSeguro . "' width='40px'>";

            /*=============================================
            STOCK
            =============================================*/ 
            if ($productosAprobados[$i]["stock"] <= 10) {
                $stock = "<button class='btn btn-danger'>" . $productosAprobados[$i]["stock"] . "</button>";
            } else if ($productosAprobados[$i]["stock"] > 11 && $productosAprobados[$i]["stock"] <= 15) {
                $stock = "<button class='btn btn-warning'>" . $productosAprobados[$i]["stock"] . "</button>";
            } else {
                $stock = "<button class='btn btn-success'>" . $productosAprobados[$i]["stock"] . "</button>";
            }

            /*=============================================
            TRAEMOS LAS ACCIONES
            =============================================*/ 
            $idSeguro = htmlspecialchars($productosAprobados[$i]["id"] ?? "", ENT_QUOTES, "UTF-8");
            $botones = "<div class='btn-group'><button class='btn btn-primary agregarProducto recuperarBoton' idProducto='" . $idSeguro . "'>Agregar</button></div>"; 

            $datos[] = array(
                $i + 1,
                $imagen,
                $productosAprobados[$i]["codigo"] ?? "",
                $productosAprobados[$i]["descripcion"] ?? "",
                $stock,
                $botones
            );
        }

        echo json_encode(array("data" => $datos), JSON_UNESCAPED_UNICODE);
    }
}

/*=============================================
ACTIVAR TABLA DE PRODUCTOS
=============================================*/ 
$activarProductosVentas = new TablaProductosCompras();
$activarProductosVentas->mostrarTablaProductosCompras();
