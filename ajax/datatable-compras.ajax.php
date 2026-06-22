<?php

require_once "../controladores/productos.controlador.php";
require_once "../modelos/productos.modelo.php";

class TablaProductosCompras{

	public function mostrarTablaProductosCompras(){

		$productos = ControladorProductos::ctrMostrarProductos(null, null, "id");
		$filtroStock = $_GET["stock"] ?? "";
		$soloSinStock = $filtroStock === "0";
		$stockBajo = $filtroStock === "bajo";

		if($soloSinStock){
			$productos = array_values(array_filter($productos, function($producto){
				return (int)($producto["stock"] ?? 0) <= 0;
			}));
		}else if($stockBajo){
			$productos = array_values(array_filter($productos, function($producto){
				return (int)($producto["stock"] ?? 0) <= 3;
			}));
		}

		usort($productos, function($a, $b){
			$stockA = (int)($a["stock"] ?? 0);
			$stockB = (int)($b["stock"] ?? 0);

			if($stockA !== $stockB){
				return $stockA <=> $stockB;
			}

			return (int)($b["id"] ?? 0) <=> (int)($a["id"] ?? 0);
		});

		if(count($productos) == 0){
			echo json_encode(array("data" => array()));
			return;
		}

		$datosJson = array("data" => array());

		foreach($productos as $i => $producto){
			$imagen = "<img src='".$producto["imagen"]."' width='40px'>";

			if($producto["stock"] <= 10){
				$stock = "<button class='btn btn-danger'>".$producto["stock"]."</button>";
			}else if($producto["stock"] > 11 && $producto["stock"] <= 15){
				$stock = "<button class='btn btn-warning'>".$producto["stock"]."</button>";
			}else{
				$stock = "<button class='btn btn-success'>".$producto["stock"]."</button>";
			}

			$botones = "<div class='btn-group'><button class='btn btn-primary agregarProducto recuperarBoton' idProducto='".$producto["id"]."'>Agregar</button></div>";

			$datosJson["data"][] = array(
				($i + 1),
				$imagen,
				$producto["codigo"],
				$producto["descripcion"],
				$stock,
				$botones
			);
		}

		echo json_encode($datosJson);
	}
}

$activarProductosVentas = new TablaProductosCompras();
$activarProductosVentas->mostrarTablaProductosCompras();
