<?php

class ControladorCompras{

	/*=============================================
	MOSTRAR compraS
	=============================================*/

	static public function ctrMostrarCompras($item, $valor){

		$tabla = "compra";

		$respuesta = ModeloCompras::mdlMostrarCompras($tabla, $item, $valor);

		return $respuesta;

	}

	static public function ctrMostrarSolicitudesCompra(){
		return self::ctrMostrarCompras(null, null);
	}

	static public function ctrSiguienteCodigoCompra(){
		return ModeloCompras::mdlSiguienteCodigoCompra();
	}

	static public function ctrCambiarEstadoSolicitud($idSolicitud, $estado){
		return self::ctrCambiarEstadoCompra($idSolicitud, $estado);
	}

	static public function ctrTomarSolicitudMensajero($idSolicitud){
		if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "mensajero") {
			return "error";
		}

		$respuesta = ModeloCompras::mdlTomarSolicitudMensajero((int)$idSolicitud, (int)$_SESSION["id"]);

		if ($respuesta == "ok" && class_exists("ControladorLogs")) {
			ControladorLogs::ctrRegistrarLog("tomar_solicitud", "mensajeria_compras", "Solicitud ".$idSolicitud." tomada por mensajero");
		}

		return $respuesta;
	}

	static public function ctrRegistrarDesembolsoMensajero($idSolicitud, $monto){
		if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero") {
			return "error";
		}
		$monto = round((float)$monto, 2);
		if($monto <= 0){
			return "monto_invalido";
		}
		if(!ControladorCaja::ctrAperturaActiva()){
			return "sin_apertura";
		}
		if(!ControladorCaja::ctrPuedeEgresarEfectivo($monto)){
			return "saldo_insuficiente";
		}

		$respuesta = ModeloCompras::mdlRegistrarDesembolsoMensajero((int)$idSolicitud, (int)$_SESSION["id"], $monto);

		if ($respuesta == "ok" && class_exists("ControladorLogs")) {
			ControladorLogs::ctrRegistrarLog("desembolso", "caja_compras", "Desembolso registrado para solicitud ".$idSolicitud." por Bs ".number_format((float)$monto, 2, ".", ""));
		}
		if($respuesta == "ok"){
			ControladorCaja::ctrRegistrarMovimiento(array(
				"tipo" => "egreso",
				"origen" => "desembolso_compra",
				"referencia_tipo" => "compra",
				"id_referencia" => (int)$idSolicitud,
				"codigo_referencia" => (string)$idSolicitud,
				"metodo_pago" => "Efectivo",
				"monto" => (float)$monto,
				"descripcion" => "Desembolso para solicitud de compra #".(int)$idSolicitud
			));
		}

		return $respuesta;
	}

	static public function ctrRegistrarRendicionMensajero($idSolicitud, $costos, $archivo, $numeroFactura = "", $observacion = ""){
		if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "mensajero") {
			return array("status" => "error", "message" => "Sin permiso.");
		}
		if (!is_array($costos) || empty($costos)) {
			return array("status" => "error", "message" => "Debe registrar los costos de compra.");
		}
		if (!is_array($archivo) || ($archivo["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			return array("status" => "error", "message" => "Debe adjuntar la factura o comprobante.");
		}

		$permitidos = array(
			"image/jpeg" => "jpg",
			"image/png" => "png",
			"image/webp" => "webp",
			"application/pdf" => "pdf"
		);
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime = $finfo->file($archivo["tmp_name"]);
		if (!isset($permitidos[$mime]) || (int)$archivo["size"] > 8 * 1024 * 1024) {
			return array("status" => "error", "message" => "Factura invalida. Use JPG, PNG, WEBP o PDF de hasta 8 MB.");
		}

		$directorioRelativo = "vistas/img/facturas_compras";
		$directorio = dirname(__DIR__)."/".$directorioRelativo;
		if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
			return array("status" => "error", "message" => "No se pudo preparar el almacenamiento de facturas.");
		}
		@chmod($directorio, 0775);
		if (!is_writable($directorio)) {
			return array("status" => "error", "message" => "La carpeta de facturas no tiene permisos de escritura: ".$directorioRelativo.".");
		}
		$nombre = "compra_".(int)$idSolicitud."_".date("Ymd_His")."_".bin2hex(random_bytes(4)).".".$permitidos[$mime];
		$rutaRelativa = $directorioRelativo."/".$nombre;
		$rutaCompleta = $directorio."/".$nombre;
		if (!move_uploaded_file($archivo["tmp_name"], $rutaCompleta)) {
			//return array("status" => "error", "message" => "No se pudo guardar la factura.");
			$detalle = is_uploaded_file($archivo["tmp_name"]) ? "El archivo temporal existe, pero el servidor no pudo moverlo." : "El archivo temporal ya no esta disponible.";
			return array("status" => "error", "message" => "No se pudo guardar la factura. ".$detalle);
		}
		@chmod($rutaCompleta, 0664);
		
		$detalles = array();
		foreach ($costos as $idProducto => $costo) {
			$detalles[] = array("id_producto" => (int)$idProducto, "costo_unitario" => (float)$costo);
		}
		$respuesta = ModeloCompras::mdlRegistrarRendicionMensajero(
			(int)$idSolicitud,
			(int)$_SESSION["id"],
			$detalles,
			$rutaRelativa,
			$numeroFactura,
			$observacion
		);
		if (($respuesta["status"] ?? "") !== "ok") {
			@unlink($rutaCompleta);
			return $respuesta;
		}
		if (class_exists("ControladorLogs")) {
			ControladorLogs::ctrRegistrarLog("rendir_compra", "mensajeria_compras", "Rendicion de compra ".$idSolicitud." registrada por Bs ".number_format($respuesta["total"], 2, ".", ""));
		}
		return $respuesta;
	}

	static public function ctrConfirmarRendicionCaja($idSolicitud){
		if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero") {
			return "error";
		}
		$apertura = ControladorCaja::ctrAperturaActiva();
		if (!$apertura) {
			return "sin_apertura";
		}
		$respuesta = ModeloCompras::mdlConfirmarRendicionCaja((int)$idSolicitud, (int)$_SESSION["id"], (int)$apertura["id"]);
		if ($respuesta === "ok" && class_exists("ControladorLogs")) {
			ControladorLogs::ctrRegistrarLog("confirmar_rendicion", "caja_compras", "Caja confirmo rendicion y devolucion de compra ".$idSolicitud);
		}
		return $respuesta;
	}

	static public function ctrConfirmarEntregaAlmacen($idSolicitud){
		if ($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen") {
			return "error";
		}

		$respuesta = ModeloCompras::mdlConfirmarEntregaAlmacen((int)$idSolicitud, (int)$_SESSION["id"]);

		if ($respuesta == "ok" && class_exists("ControladorLogs")) {
			ControladorLogs::ctrRegistrarLog("recibir_compra", "almacen", "Almacen confirmo recepcion de solicitud ".$idSolicitud);
		}

		return $respuesta;
	}
	static public function ctrMostrarDetalleCompras($idCompra){

		$respuesta = ModeloCompras::mdlMostrarDetalleCompras($idCompra);
		return $respuesta;

	}

	/*=============================================
	CREAR compra
	=============================================*/

	static public function ctrCrearCompra(){

		if(isset($_POST["nuevaCompra"])){

			/*=============================================
			ACTUALIZAR LAS COMPRAS DEL CLIENTE Y REDUCIR EL STOCK Y AUMENTAR LAS VENTAS DE LOS PRODUCTOS
			=============================================*/

			if($_POST["listaProductos"] == ""){

					 echo'<script>

				swal({
					  type: "error",
					  title: "La compra no se ha ejecuta si no hay productos",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "crear-compra-almacen";

								}
							})

				</script>'; 

				return;
			}


			$listaProductos = json_decode($_POST["listaProductos"], true);
			if(!is_array($listaProductos)){
				return;
			}
			foreach($listaProductos as &$productoSolicitud){
				$productoSolicitud["precio"] = 0;
				$productoSolicitud["total"] = 0;
			}
			unset($productoSolicitud);
			$listaProductosSolicitud = json_encode($listaProductos, JSON_UNESCAPED_UNICODE);
	

			$totalProductosComprados = array();

			// foreach ($listaProductos as $key => $value) {

			//    array_push($totalProductosComprados, $value["cantidad"]);
				
			//    $tablaProductos = "productos";

			//     $item = "id";
			//     $valor = $value["id"];
			//     $orden = "id";

			//     $traerProducto = ModeloProductos::mdlMostrarProductos($tablaProductos, $item, $valor, $orden);

			// 	$item1a = "stock";
			// 	$valor1a = $value["cantidad"] + $traerProducto["stock"];

			//     $nuevasVentas = ModeloProductos::mdlActualizarProducto($tablaProductos, $item1a, $valor1a, $valor);

			// }

			date_default_timezone_set('America/La_Paz');
	
			/*=============================================
			GUARDAR LA COMPRA CON ESTADO "PENDIENTE"
			=============================================*/
	
			$tabla = "compra";
	
			$datos = array(
				"id_usuario" => $_POST["idUsuario"],
				"id_proveedor" => $_POST["seleccionarProveedor"],
				"codigo" => $_POST["nuevaCompra"],
				"productos" => $listaProductosSolicitud,
				"total" => 0,
				"estado" => "pendiente" // Estado inicial de la compra
			);
	
			$respuesta = ModeloCompras::mdlIngresarCompras($tabla, $datos);
	
			if ($respuesta == "ok") {
				if (class_exists("ControladorLogs")) {
					ControladorLogs::ctrRegistrarLog("crear", "solicitudes_compra", "Solicitud de compra ".$_POST["nuevaCompra"]." registrada por almacén");
				}

				echo '<script>
					localStorage.removeItem("rango");
	
					swal({
						  type: "success",
						  title: "La compra ha sido registrada correctamente con estado pendiente.",
						  showConfirmButton: true,
						  confirmButtonText: "Cerrar"
					  }).then(function(result) {
						if (result.value) {
							window.location = "solicitudes-de-compra";
						}
					  })
				</script>';
			}
		}
	}
		/*=============================================
	CAMBIAR ESTADO CAMBIAR ESTADO
	=============================================*/

	/*=============================================
	CAMBIAR ESTADO DE LA COMPRA (SOLO ADMINISTRADOR)
	=============================================*/
	static public function ctrCambiarEstadoCompra($idCompra, $nuevoEstado) {
		$puedeAprobar = isset($_SESSION["perfil"], $_SESSION["rol"]) &&
			($_SESSION["perfil"] == "Administrador" || $_SESSION["rol"] == "cajero");

		if (!$puedeAprobar) {
			return "error";
		}

		if (!in_array($nuevoEstado, ["pendiente", "aprobado", "rechazado"])) {
			return "error";
		}
	
		$tabla = "compra";
		$respuesta = ModeloCompras::mdlCambiarEstadoCompra($tabla, $idCompra, $nuevoEstado);

		if ($respuesta == "ok" && class_exists("ControladorLogs")) {
			ControladorLogs::ctrRegistrarLog("cambiar_estado", "solicitudes_compra", "Solicitud ".$idCompra." marcada como ".$nuevoEstado);
		}
	
		return $respuesta;
	}
	
	

	/*=============================================
	EDITAR cmpra
	=============================================*/

	static public function ctrEditarCompra(){

		if(isset($_POST["editarVenta"])){

			/*=============================================
			FORMATEAR TABLA DE PRODUCTOS Y LA DE CLIENTES
			=============================================*/
			$tabla = "ventas";

			$item = "codigo";
			$valor = $_POST["editarVenta"];

			$traerVenta = ModeloVentas::mdlMostrarVentas($tabla, $item, $valor);

			/*=============================================
			REVISAR SI VIENE PRODUCTOS EDITADOS
			=============================================*/

			if($_POST["listaProductoscompra"] == ""){

				$listaProductos = $traerVenta["productos"];
				$cambioProducto = false;


			}else{

				$listaProductos = $_POST["listaProductoscompra"];
				$cambioProducto = true;
			}

			if($cambioProducto){

				$productos =  json_decode($traerVenta["productos"], true);

				$totalProductosComprados = array();

				foreach ($productos as $key => $value) {

					array_push($totalProductosComprados, $value["cantidad"]);
					
					$tablaProductos = "productos";

					$item = "id";
					$valor = $value["id"];
					$orden = "id";

					$traerProducto = ModeloProductos::mdlMostrarProductos($tablaProductos, $item, $valor, $orden);

					$item1a = "ventas";
					$valor1a = $traerProducto["ventas"] - $value["cantidad"];

					$nuevasVentas = ModeloProductos::mdlActualizarProducto($tablaProductos, $item1a, $valor1a, $valor);

					$item1b = "stock";
					$valor1b = $value["cantidad"] + $traerProducto["stock"];

					$nuevoStock = ModeloProductos::mdlActualizarProducto($tablaProductos, $item1b, $valor1b, $valor);

				}

				$tablaClientes = "clientes";

				$itemCliente = "id";
				$valorCliente = $_POST["seleccionarCliente"];

				$traerCliente = ModeloClientes::mdlMostrarClientes($tablaClientes, $itemCliente, $valorCliente);

				$item1a = "compras";
				$valor1a = $traerCliente["compras"] - array_sum($totalProductosComprados);

				$comprasCliente = ModeloClientes::mdlActualizarCliente($tablaClientes, $item1a, $valor1a, $valorCliente);

				/*=============================================
				ACTUALIZAR LAS COMPRAS DEL CLIENTE Y REDUCIR EL STOCK Y AUMENTAR LAS VENTAS DE LOS PRODUCTOS
				=============================================*/

				$listaProductos_2 = json_decode($listaProductos, true);

				$totalProductosComprados_2 = array();

				foreach ($listaProductos_2 as $key => $value) {

					array_push($totalProductosComprados_2, $value["cantidad"]);
					
					$tablaProductos_2 = "productos";

					$item_2 = "id";
					$valor_2 = $value["id"];
					$orden = "id";

					$traerProducto_2 = ModeloProductos::mdlMostrarProductos($tablaProductos_2, $item_2, $valor_2, $orden);

					$item1a_2 = "ventas";
					$valor1a_2 = $value["cantidad"] + $traerProducto_2["ventas"];

					$nuevasVentas_2 = ModeloProductos::mdlActualizarProducto($tablaProductos_2, $item1a_2, $valor1a_2, $valor_2);

					$item1b_2 = "stock";
					$valor1b_2 = $traerProducto_2["stock"] - $value["cantidad"];

					$nuevoStock_2 = ModeloProductos::mdlActualizarProducto($tablaProductos_2, $item1b_2, $valor1b_2, $valor_2);

				}

				$tablaClientes_2 = "clientes";

				$item_2 = "id";
				$valor_2 = $_POST["seleccionarCliente"];

				$traerCliente_2 = ModeloClientes::mdlMostrarClientes($tablaClientes_2, $item_2, $valor_2);

				$item1a_2 = "compras";
				$valor1a_2 = array_sum($totalProductosComprados_2) + $traerCliente_2["compras"];

				$comprasCliente_2 = ModeloClientes::mdlActualizarCliente($tablaClientes_2, $item1a_2, $valor1a_2, $valor_2);

				$item1b_2 = "ultima_compra";

				date_default_timezone_set('America/Bogota');

				$fecha = date('Y-m-d');
				$hora = date('H:i:s');
				$valor1b_2 = $fecha.' '.$hora;

				$fechaCliente_2 = ModeloClientes::mdlActualizarCliente($tablaClientes_2, $item1b_2, $valor1b_2, $valor_2);

			}

			/*=============================================
			GUARDAR CAMBIOS DE LA COMPRA
			=============================================*/	

			$datos = array("id_vendedor"=>$_POST["idVendedor"],
						   "id_cliente"=>$_POST["seleccionarCliente"],
						   "codigo"=>$_POST["editarVenta"],
						   "productos"=>$listaProductos,
						   "descuento"=>$_POST["nuevoPrecioImpuesto"],
						   "neto"=>$_POST["nuevoPrecioNeto"],
						   "total"=>$_POST["totalVenta"],
						   "metodo_pago"=>$_POST["listaMetodoPago"]);


			$respuesta = ModeloVentas::mdlEditarVenta($tabla, $datos);

			if($respuesta == "ok"){

				echo'<script>

				localStorage.removeItem("rango");

				swal({
					  type: "success",
					  title: "La venta ha sido editada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then((result) => {
								if (result.value) {

								window.location = "ventas";

								}
							})

				</script>';

			}

		}

	}


	/*=============================================
	ELIMINAR COMPRA
	=============================================*/

	static public function ctrEliminarCompra(){

		if(isset($_GET["idCompra"])){
			if(($_SESSION["perfil"] ?? "") != "Administrador"){
				echo '<script>window.location = "solicitudes-de-compra";</script>';
				return;
			}

			$tabla = "compra";

			$item = "id";
			$valor = $_GET["idCompra"];

			$traerCompra = ModeloCompras::mdlMostrarCompras($tabla, $item, $valor);
			if(!$traerCompra){
				echo '<script>swal({type:"error",title:"La solicitud ya no existe",confirmButtonText:"Cerrar"}).then(function(){window.location="solicitudes-de-compra";});</script>';
				return;
			}

			$respuesta = ModeloCompras::mdlEliminarCompra($tabla, $_GET["idCompra"]);

			if($respuesta == "ok"){
				if(class_exists("ControladorLogs")){
					ControladorLogs::ctrRegistrarLog("eliminar", "solicitudes_compra", "Solicitud de compra ".$_GET["idCompra"]." eliminada");
				}

				echo'<script>

				swal({
					  type: "success",
					  title: "La solicitud de compra ha sido eliminada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "solicitudes-de-compra";

								}
							})

				</script>';

			}else if($respuesta == "con_ingresos"){
				echo '<script>
					swal({
						type:"warning",
						title:"No se puede eliminar esta solicitud",
						text:"La solicitud ya tiene materiales ingresados al inventario. Debe conservarse para mantener la trazabilidad de stock y codigos.",
						confirmButtonText:"Cerrar"
					}).then(function(){window.location="solicitudes-de-compra";});
				</script>';
			}else if($respuesta == "con_flujo"){
				echo '<script>
					swal({
						type:"warning",
						title:"No se puede eliminar esta solicitud",
						text:"La solicitud ya fue aprobada o tiene movimiento de compra/caja. Debe conservarse para la trazabilidad.",
						confirmButtonText:"Cerrar"
					}).then(function(){window.location="solicitudes-de-compra";});
				</script>';
			}else{
				echo '<script>
					swal({
						type:"error",
						title:"No se pudo eliminar la solicitud",
						text:"No se realizaron cambios. Intente nuevamente.",
						confirmButtonText:"Cerrar"
					});
				</script>';
			}
		}

	}


	/*=============================================
	RANGO FECHAS
	=============================================*/	

	static public function ctrRangoFechasCompras($fechaInicial, $fechaFinal){

		$tabla = "compra";

		$respuesta = ModeloCompras::mdlRangoFechasCompras($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
		
	}

	/*=============================================
	DESCARGAR EXCEL
	=============================================*/

	public function ctrDescargarReporteCompra(){

		if(isset($_GET["reporte"])){

			$tabla = "compra";

			if(isset($_GET["fechaInicial"]) && isset($_GET["fechaFinal"])){

				$compras = Modelocompras::mdlRangoFechascompras($tabla, $_GET["fechaInicial"], $_GET["fechaFinal"]);

			}else{

				$item = null;
				$valor = null;

				$compras = Modelocompras::mdlMostrarcompras($tabla, $item, $valor);

			}


			/*=============================================
			CREAMOS EL ARCHIVO DE EXCEL
			=============================================*/

			$Name = $_GET["reporte"].'.xls';

			header('Expires: 0');
			header('Cache-control: private');
			header("Content-type: application/vnd.ms-excel"); // Archivo de Excel
			header("Cache-Control: cache, must-revalidate"); 
			header('Content-Description: File Transfer');
			header('Last-Modified: '.date('D, d M Y H:i:s'));
			header("Pragma: public"); 
			header('Content-Disposition:; filename="'.$Name.'"');
			header("Content-Transfer-Encoding: binary");

			echo utf8_decode("<table border='0'> 

					<tr> 
					<td style='font-weight:bold; border:1px solid #eee;'>CÓDIGO</td> 
					<td style='font-weight:bold; border:1px solid #eee;'>PROVEEDOR</td>
					<td style='font-weight:bold; border:1px solid #eee;'>USUARIO</td>
					<td style='font-weight:bold; border:1px solid #eee;'>CANTIDAD</td>
					<td style='font-weight:bold; border:1px solid #eee;'>PRODUCTOS</td>
					
					<td style='font-weight:bold; border:1px solid #eee;'>TOTAL</td>		
					
					<td style='font-weight:bold; border:1px solid #eee;'>FECHA</td>		
					</tr>");

			foreach ($compras as $row => $item){

				$Proveedor = ControladorProveedor::ctrMostrarProveedor("id", $item["id_proveedor"]);
				$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $item["id_usuario"]);

			 echo utf8_decode("<tr>
			 			<td style='border:1px solid #eee;'>".$item["codigo"]."</td> 
			 			<td style='border:1px solid #eee;'>".$Proveedor["nombre"]."</td>
			 			<td style='border:1px solid #eee;'>".$vendedor["nombre"]."</td>
			 			<td style='border:1px solid #eee;'>");

			 	$productos =  json_decode($item["productos"], true);

			 	foreach ($productos as $key => $valueProductos) {
			 			
			 			echo utf8_decode($valueProductos["cantidad"]."<br>");
			 		}

			 	echo utf8_decode("</td><td style='border:1px solid #eee;'>");	

		 		foreach ($productos as $key => $valueProductos) {
			 			
		 			echo utf8_decode($valueProductos["descripcion"]."<br>");
		 		
		 		}

		 		echo utf8_decode("</td>
					
					<td style='border:1px solid #eee;'>Bs ".number_format($item["total"],2)."</td>
					
					<td style='border:1px solid #eee;'>".substr($item["fecha"],0,10)."</td>		
		 			</tr>");


			}


			echo "</table>";

		}

	}


	/*=============================================
	SUMA TOTAL compraS
	=============================================*/

	public function ctrSumaTotalcompras(){

		$tabla = "compra";

		$respuesta = Modelocompras::mdlSumaTotalcompras($tabla);

		return $respuesta;

	}

	public static function ctrMostrarProductosCompra($idCompra) {
		return ModeloCompras::mdlMostrarProductosCompra($idCompra);
	}
	public static function ctrMostrarComprasAprobadas() {
        return ModeloCompras::mdlMostrarComprasAprobadas();
    }
	
	public static function ctrAgregarDetalleCompra() {
		if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["idCompra"]) && isset($_POST["idProducto"]) && isset($_POST["cantidad"])) {
			$datos = array(
				"id_compra" => $_POST["idCompra"],
				"id_producto" => $_POST["idProducto"],
				"cantidad" => $_POST["cantidad"],
				"precio_unitario" => $_POST["precio"],
				"total" => $_POST["cantidad"] * $_POST["precio"]
			);
	
			// Guardar en la tabla detalle_compra
			$respuesta = ModeloCompras::mdlAgregarOActualizarDetalleCompra("detalle_compra", $datos);
	
			if ($respuesta == "ok") {
				echo json_encode(["status" => "ok", "message" => "Producto guardado en detalle_compra"]);
			} else {
				echo json_encode(["status" => "error", "message" => "Error al guardar producto"]);
			}
		}
	}
	
	
    
    public static function ctrRestarCantidadProducto() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["idCompra"]) && isset($_POST["idProducto"]) && isset($_POST["cantidadReducida"])) {
            $respuesta = ModeloCompras::mdlRestarCantidad($_POST["idCompra"], $_POST["idProducto"], $_POST["cantidadReducida"]);
            echo json_encode(["status" => $respuesta, "message" => ($respuesta == "ok" ? "Cantidad actualizada" : "No se pudo actualizar")]);
        }
    }

}
