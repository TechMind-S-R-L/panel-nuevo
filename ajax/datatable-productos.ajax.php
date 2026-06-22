<?php

require_once "../controladores/productos.controlador.php";
require_once "../modelos/productos.modelo.php";

require_once "../controladores/categorias.controlador.php";
require_once "../modelos/categorias.modelo.php";


class TablaProductos{

 	/*=============================================
 	 MOSTRAR LA TABLA DE PRODUCTOS
  	=============================================*/ 

	public function mostrarTablaProductos(){

		$item = null;
    	$valor = null;
    	$orden = "id";

  		$productos = ControladorProductos::ctrMostrarProductosAlmacen();

  		if(count($productos) == 0){

  			echo '{"data": []}';

		  	return;
  		}
		
  		$datos = array();

		  for($i = 0; $i < count($productos); $i++){

		  	/*=============================================
 	 		TRAEMOS LA IMAGEN
  			=============================================*/ 

		  	$imagen = "<img src='".htmlspecialchars($productos[$i]["imagen"] ?? "", ENT_QUOTES, "UTF-8")."' width='40px'>";
			

		  	/*=============================================
 	 		TRAEMOS LA CATEGORÍA
  			=============================================*/ 

		  	/*=============================================
 	 		STOCK
  			=============================================*/ 

  			if($productos[$i]["stock"] <= 10){

  				$stock = "<button class='btn btn-danger'>".$productos[$i]["stock"]."</button>";

  			}else if($productos[$i]["stock"] > 11 && $productos[$i]["stock"] <= 15){

  				$stock = "<button class='btn btn-warning'>".$productos[$i]["stock"]."</button>";

  			}else{

  				$stock = "<button class='btn btn-success'>".$productos[$i]["stock"]."</button>";

  			}

		  	/*=============================================
 	 		TRAEMOS LAS ACCIONES
  			=============================================*/ 

			$perfilOculto = strtolower(trim($_GET["perfilOculto"] ?? ""));
			$rolOculto = strtolower(trim($_GET["rolOculto"] ?? ""));
			$puedeEliminar = strpos($perfilOculto, "administrador") !== false || $rolOculto == "cajero";
			$puedeVerCodigosUnicos = strpos($perfilOculto, "administrador") !== false || $rolOculto == "almacen";
			$codigoSeguro = htmlspecialchars($productos[$i]["codigo"] ?? "", ENT_QUOTES, "UTF-8");
			$imagenSeguro = htmlspecialchars($productos[$i]["imagen"] ?? "", ENT_QUOTES, "UTF-8");
			$codigoUnicoVista = $puedeVerCodigosUnicos
				? "<button type='button' class='btn btn-info btn-xs btnVerCodigosUnicos' idProducto='".(int)$productos[$i]["id"]."' codigo='".$codigoSeguro."' data-toggle='modal' data-target='#modalCodigosUnicosProducto'><i class='fa fa-list'></i> Ver codigos</button>"
				: "<span class='text-muted'>Reservado almacen</span>";

  			if(!$puedeEliminar){

  				$botones =  "<div class='btn-group'><button class='btn btn-warning btnEditarProducto' idProducto='".(int)$productos[$i]["id"]."' data-toggle='modal' data-target='#modalEditarProducto'><i class='fa fa-pencil'></i></button></div>"; 

  			}else{

  				 $botones =  "<div class='btn-group'><button class='btn btn-warning btnEditarProducto' idProducto='".(int)$productos[$i]["id"]."' data-toggle='modal' data-target='#modalEditarProducto'><i class='fa fa-pencil'></i></button><button class='btn btn-danger btnEliminarProducto' idProducto='".(int)$productos[$i]["id"]."' codigo='".$codigoSeguro."' imagen='".$imagenSeguro."'><i class='fa fa-times'></i></button></div>"; 

  			}

			$datos[] = array(
				(string)($i + 1),
				(string)($productos[$i]["codigo"] ?? ""),
				(string)($productos[$i]["codigo_producto_generico"] ?? ""),
				$codigoUnicoVista,
				(string)($productos[$i]["descripcion"] ?? ""),
				(string)($productos[$i]["ruta_categoria"] ?? "Sin categoria"),
				$stock,
				$imagen,
				(string)($productos[$i]["precio_compra"] ?? "0"),
				(string)($productos[$i]["precio_venta"] ?? "0"),
				(string)($productos[$i]["fecha"] ?? ""),
				$botones
			);

		  }

		echo json_encode(array("data" => $datos), JSON_UNESCAPED_UNICODE);

if (isset($_POST["idProducto"])) {
    $id = $_POST["idProducto"];
    $producto = ModeloProductos::mdlMostrarProducto("id", $id);
    echo json_encode($producto);
    return;
}
	}


}

/*=============================================
ACTIVAR TABLA DE PRODUCTOS
=============================================*/ 
$activarProductos = new TablaProductos();
$activarProductos -> mostrarTablaProductos();

