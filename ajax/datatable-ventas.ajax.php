<?php

require_once "../controladores/productos.controlador.php";
require_once "../modelos/productos.modelo.php";


class TablaProductosVentas{

 	/*=============================================
 	 MOSTRAR LA TABLA DE PRODUCTOS
  	=============================================*/ 

	public function mostrarTablaProductosVentas(){

		$item = null;
    	$valor = null;
    	$orden = "stock";

  		$productos = ControladorProductos::ctrMostrarProductosDisponiblesVenta();
 		
  		if(count($productos) == 0){

  			echo '{"data": []}';

		  	return;
  		}	
		
  		$datosJson = array("data" => array());

		  for($i = 0; $i < count($productos); $i++){

		  	/*=============================================
 	 		TRAEMOS LA IMAGEN
  			=============================================*/ 

		  	$rutaImagen = trim($productos[$i]["imagen"] ?? "");
		  	if($rutaImagen == "" || !file_exists("../".$rutaImagen)){
		  		$rutaImagen = "vistas/img/productos/default/anonymous.png";
		  	}
		  	$imagen = "<img class='img-producto-venta' src='".$rutaImagen."' alt='Producto'>";

		  	/*=============================================
 	 		STOCK
  			=============================================*/ 

  			$stockReal = (int)($productos[$i]["stock_real"] ?? $productos[$i]["stock"]);

  			if($stockReal <= 0){

  				$stock = "<span class='label label-danger'>Sin stock</span>";

  			}else if($stockReal <= 10){

  				$stock = "<button class='btn btn-danger'>".$stockReal."</button>";

  			}else if($stockReal <= 15){

  				$stock = "<button class='btn btn-warning'>".$stockReal."</button>";

  			}else{

  				$stock = "<button class='btn btn-success'>".$stockReal."</button>";

  			}

		  	/*=============================================
 	 		TRAEMOS LAS ACCIONES
  			=============================================*/ 

		  	$precioVenta = (float)($productos[$i]["precio_venta"] ?? 0);
		  	$requierePrecio = (int)($productos[$i]["requiere_precio"] ?? 0);

		  	$precioHtml = "";

		  	if($stockReal <= 0){
		  		$botones = "<div class='btn-group'><button type='button' class='btn btn-default' disabled>No tiene stock</button></div>";
		  	}else if($precioVenta <= 0 || $requierePrecio == 1){
		  		$botones = "<div class='btn-group'><button type='button' class='btn btn-default' disabled>Sin precio</button></div>";
		  	}else{
		  		$precioHtml = "<span class='venta-card-price'>Bs ".number_format($precioVenta, 2)."</span>";
		  		$botones = "<div class='btn-group'><button type='button' class='btn btn-primary agregarProducto recuperarBoton' idProducto='".$productos[$i]["id"]."'>Agregar</button></div>";
		  	}

		  	$datosJson["data"][] = array(
		  		(string)($i+1),
		  		$imagen,
		  		(string)$productos[$i]["codigo"],
		  		(string)$productos[$i]["descripcion"],
		  		$stock,
		  		$botones,
		  		$precioHtml
		  	);

		  }

		$opcionesJson = JSON_UNESCAPED_UNICODE;
		if(defined("JSON_INVALID_UTF8_SUBSTITUTE")){
			$opcionesJson = $opcionesJson | JSON_INVALID_UTF8_SUBSTITUTE;
		}

		echo json_encode($datosJson, $opcionesJson);


	}


}

/*=============================================
ACTIVAR TABLA DE PRODUCTOS
=============================================*/ 
$activarProductosVentas = new TablaProductosVentas();
$activarProductosVentas -> mostrarTablaProductosVentas();

