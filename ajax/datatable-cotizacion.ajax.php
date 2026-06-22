<?php

ob_start();

require_once "../controladores/productos.controlador.php";
require_once "../modelos/productos.modelo.php";


class TablaProductosCotizacion{

 	/*=============================================
 	 MOSTRAR LA TABLA DE PRODUCTOS
  	=============================================*/ 

	public function mostrarTablaProductosCotizacion(){

		$item = null;
    	$valor = null;
    	$orden = "stock";

  		$productos = ControladorProductos::ctrMostrarProductos($item, $valor, $orden);
		$productos = is_array($productos) ? $productos : [];
 		
		$datos = [];

		  for($i = 0; $i < count($productos); $i++){

		  	/*=============================================
 	 		TRAEMOS LA IMAGEN
  			=============================================*/ 

		  	$rutaImagen = trim($productos[$i]["imagen"] ?? "");
		  	if($rutaImagen == "" || !file_exists("../".$rutaImagen)){
		  		$rutaImagen = "vistas/img/productos/default/anonymous.png";
		  	}
		  	$rutaImagen = htmlspecialchars($rutaImagen, ENT_QUOTES, "UTF-8");
		  	$imagen = "<img class='img-producto-cotizacion' src='".$rutaImagen."' alt='Producto'>";

		  	/*=============================================
 	 		STOCK
  			=============================================*/ 

  			$stockNumero = (int)($productos[$i]["stock"] ?? 0);

  			if($stockNumero <= 10){

  				$stock = "<button class='btn btn-danger'>".$stockNumero."</button>";

  			}else if($stockNumero > 11 && $stockNumero <= 15){

  				$stock = "<button class='btn btn-warning'>".$stockNumero."</button>";

  			}else{

  				$stock = "<button class='btn btn-success'>".$stockNumero."</button>";

  			}

		  	/*=============================================
 	 		TRAEMOS LAS ACCIONES
  			=============================================*/ 

		  	$idProducto = (int)($productos[$i]["id"] ?? 0);
		  	$precioVenta = (float)($productos[$i]["precio_venta"] ?? 0);
		  	$precio = $precioVenta > 0
		  		? "<span class='cotizacion-card-price'>Bs ".number_format($precioVenta, 2)."</span>"
		  		: "<span class='label label-default'>Sin precio</span>";

		  	if($stockNumero <= 0){
		  		$botones = "<div class='btn-group'><button type='button' class='btn btn-warning agregarProducto recuperarBoton' idProducto='".$idProducto."'>Cotizar sin stock</button></div>";
		  	}else{
		  		$botones = "<div class='btn-group'><button type='button' class='btn btn-primary agregarProducto recuperarBoton' idProducto='".$idProducto."'>Agregar</button></div>";
		  	}

		  	$datos[] = [
			      (string)($i+1),
			      $imagen,
			      htmlspecialchars($productos[$i]["codigo"] ?? "", ENT_QUOTES, "UTF-8"),
			      htmlspecialchars($productos[$i]["descripcion"] ?? "", ENT_QUOTES, "UTF-8"),
			      $stock,
			      $botones,
			      $precio
			    ];

		  }

		if(ob_get_length()){
			ob_clean();
		}

		header("Content-Type: application/json; charset=utf-8");
		echo json_encode(["data" => $datos], JSON_UNESCAPED_UNICODE);


	}


}

/*=============================================
ACTIVAR TABLA DE PRODUCTOS
=============================================*/ 
$activarProductosCotizacion = new TablaProductosCotizacion();
$activarProductosCotizacion -> mostrarTablaProductosCotizacion();

