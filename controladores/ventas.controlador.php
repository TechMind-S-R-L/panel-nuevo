<?php

class ControladorVentas{

	/*=============================================
	MOSTRAR VENTAS
	=============================================*/

	static public function ctrMostrarVentas($item, $valor){

		$tabla = "ventas";

		$respuesta = ModeloVentas::mdlMostrarVentas($tabla, $item, $valor);

		return $respuesta;

	}

	static public function ctrSiguienteCodigoVenta(){
		return ModeloVentas::mdlSiguienteCodigoVenta();
	}

	/*=============================================
	CREAR VENTA
	=============================================*/

	static public function ctrCrearVenta(){

		if(isset($_POST["nuevaVenta"])){

			/*=============================================
			ACTUALIZAR LAS COMPRAS DEL CLIENTE Y REDUCIR EL STOCK Y AUMENTAR LAS VENTAS DE LOS PRODUCTOS
			=============================================*/

			if($_POST["listaProductos"] == ""){

					echo'<script>

				swal({
					  type: "error",
					  title: "La venta no se ha ejecuta si no hay productos",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "ventas";

								}
							})

				</script>';

				return;
			}


			$listaProductos = json_decode($_POST["listaProductos"], true);

			$totalProductosComprados = array();

			foreach ($listaProductos as $key => $value) {

			   array_push($totalProductosComprados, $value["cantidad"]);
				
			   $tablaProductos = "productos";

			    $item = "id";
			    $valor = $value["id"];
			    $orden = "id";

			    $traerProducto = ModeloProductos::mdlMostrarProductos($tablaProductos, $item, $valor, $orden);

				$item1a = "ventas";
				$valor1a = $value["cantidad"] + $traerProducto["ventas"];

			    $nuevasVentas = ModeloProductos::mdlActualizarProducto($tablaProductos, $item1a, $valor1a, $valor);

				$item1b = "stock";
				$valor1b = $value["stock"];

				$nuevoStock = ModeloProductos::mdlActualizarProducto($tablaProductos, $item1b, $valor1b, $valor);

			}

			$tablaClientes = "clientes";

			$item = "id";
			$valor = $_POST["seleccionarCliente"];

			$traerCliente = ModeloClientes::mdlMostrarClientes($tablaClientes, $item, $valor);

			$item1a = "compras";
			$valor1a = array_sum($totalProductosComprados) + $traerCliente["compras"];

			$comprasCliente = ModeloClientes::mdlActualizarCliente($tablaClientes, $item1a, $valor1a, $valor);

			$item1b = "ultima_compra";

			date_default_timezone_set('America/La_Paz');

			$fecha = date('Y-m-d');
			$hora = date('H:i:s');
			$valor1b = $fecha.' '.$hora;

			$fechaCliente = ModeloClientes::mdlActualizarCliente($tablaClientes, $item1b, $valor1b, $valor);

			/*=============================================
			GUARDAR LA VENTA
			=============================================*/	

			$tabla = "ventas";

			$datos = array("id_vendedor"=>$_POST["idVendedor"],
						   "id_cliente"=>$_POST["seleccionarCliente"],
						   "codigo"=>$_POST["nuevaVenta"],
						   "productos"=>$_POST["listaProductos"],
						   "descuento"=>$_POST["nuevoPrecioImpuesto"],
						   "neto"=>$_POST["nuevoPrecioNeto"],
						   "total"=>$_POST["totalVenta"],
						   "metodo_pago"=>"Pendiente de pago");

			$respuesta = ModeloVentas::mdlIngresarVenta($tabla, $datos);

			if($respuesta == "ok"){
				if (class_exists("ControladorLogs")) {
					ControladorLogs::ctrRegistrarLog("crear", "ventas", "Venta ".$_POST["nuevaVenta"]." registrada con pago pendiente");
				}

				echo'<script>

				localStorage.removeItem("rango");

				swal({
					  type: "success",
					  title: "La venta ha sido guardada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

							window.open("extensiones/tcpdf/pdf/boleta-caja.php?codigo='.$_POST["nuevaVenta"].'", "_blank");
							window.location = "ventas";

								}
							})

				</script>';

			}

		}

	}

	static public function ctrMostrarVentasPendientesPago(){
		return ModeloVentas::mdlMostrarVentasPorEstado("pendiente");
	}

	static public function ctrMostrarVentasCobradas(){
		return ModeloVentas::mdlMostrarVentasPorEstado("aprobado");
	}

	static public function ctrMostrarVentasPendientesDespacho(){
		return ModeloVentas::mdlMostrarVentasPorEstado("aprobado", "pendiente");
	}

	static public function ctrMostrarCodigosDespacho($idVenta){
		return ModeloVentas::mdlMostrarCodigosDespacho($idVenta);
	}

	static public function ctrAprobarPagoVenta(){
		if(isset($_GET["aprobarPagoVenta"])){
			if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "cajero"){
				return;
			}
			if(!ControladorCaja::ctrPuedeOperar()){
				echo '<script>swal({type:"warning",title:"Debe abrir su caja",text:"Registre el efectivo inicial antes de realizar cobros.",confirmButtonText:"Ir a caja"}).then(function(){window.location="caja";});</script>';
				return;
			}

			$idVenta = (int)$_GET["aprobarPagoVenta"];
			$item = "id";
			$venta = ModeloVentas::mdlMostrarVentas("ventas", $item, $idVenta);
			$total = (float)($venta["total"] ?? 0);
			$metodoPago = $_GET["metodoPago"] ?? "Efectivo";
			$montoRecibido = isset($_GET["montoRecibido"]) ? (float)$_GET["montoRecibido"] : $total;
			$cambio = max(0, $montoRecibido - $total);

			$datosPago = array(
				"id" => $idVenta,
				"id_cajero" => $_SESSION["id"],
				"metodo_pago" => $metodoPago,
				"monto_recibido" => $montoRecibido,
				"cambio" => $cambio,
				"codigo_transaccion" => $_GET["codigoTransaccion"] ?? ""
			);

			$respuesta = ModeloVentas::mdlCambiarEstadoPago($datosPago);

			if($respuesta == "ok" && class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("aprobar_pago", "ventas", "Pago aprobado para venta ".$idVenta." con ".$metodoPago);
			}
			if($respuesta == "ok"){
				ControladorCaja::ctrRegistrarMovimiento(array(
					"tipo" => "ingreso",
					"origen" => "venta",
					"referencia_tipo" => "venta",
					"id_referencia" => $idVenta,
					"codigo_referencia" => $venta["codigo"] ?? "",
					"metodo_pago" => $metodoPago,
					"monto" => $total,
					"descripcion" => "Cobro de venta ".($venta["codigo"] ?? $idVenta)
				));
			}

			echo '<script>
				swal({
					type: "success",
					title: "Pago aprobado correctamente",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then(function(result){
					if(result.value){
							window.open("extensiones/tcpdf/pdf/boleta-despacho.php?idVenta='.$idVenta.'&codigo='.$venta["codigo"].'", "_blank");
						window.location = "pagos-ventas";
					}
				})
			</script>';
		}
	}

	static public function ctrEntregarVenta(){
		if(isset($_POST["entregarVenta"])){
			if($_SESSION["perfil"] != "Administrador" && $_SESSION["rol"] != "almacen"){
				return;
			}

			$idVenta = (int)$_POST["entregarVenta"];
			$venta = ModeloVentas::mdlMostrarVentas("ventas", "id", $idVenta);
			$codigosPorProducto = [];

			if(isset($_POST["codigosDespacho"])){
				$codigosPorProducto = json_decode($_POST["codigosDespacho"], true);
			}

			if(!is_array($codigosPorProducto)){
				$codigosPorProducto = [];
			}

			$respuesta = ModeloVentas::mdlRegistrarDespachoConCodigos($idVenta, $_SESSION["id"], $codigosPorProducto);

			if(($respuesta["status"] ?? "") == "ok" && class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("entregar", "despacho", "Venta ".$idVenta." entregada al cliente con ".$respuesta["total_entregado"]." codigo(s) registrados");
			}

			if(($respuesta["status"] ?? "") != "ok"){
				$mensaje = $respuesta["message"] ?? "No se pudo registrar la entrega.";
				echo '<script>
					swal({
						type: "error",
						title: "No se pudo entregar",
						text: '.json_encode($mensaje).',
						showConfirmButton: true,
						confirmButtonText: "Cerrar"
					}).then(function(result){
						if(result.value){
							window.location = "despacho";
						}
					})
				</script>';
				return;
			}

			echo '<script>
				swal({
					type: "success",
					title: "Producto entregado correctamente",
					text: "Se registraron '.$respuesta["total_entregado"].' codigo(s) de producto.",
					showConfirmButton: true,
					confirmButtonText: "Cerrar"
				}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/factura.php?idVenta='.$idVenta.'&codigo='.$venta["codigo"].'", "_blank");
						window.open("extensiones/tcpdf/pdf/conformidad.php?idVenta='.$idVenta.'", "_blank");
						window.location = "despacho";
					}
				})
			</script>';
		}
	}

	/*=============================================
	EDITAR VENTA
	=============================================*/

	static public function ctrEditarVenta(){

		if(isset($_POST["editarVenta"])){

			if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "cajero"){
				echo '<script>window.location = "ventas";</script>';
				return;
			}

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

			if($_POST["listaProductos"] == ""){

				$listaProductos = $traerVenta["productos"];
				$cambioProducto = false;


			}else{

				$listaProductos = $_POST["listaProductos"];
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

				date_default_timezone_set('America/La_Paz');

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
				if (class_exists("ControladorLogs")) {
					ControladorLogs::ctrRegistrarLog("editar", "ventas", "Venta ".$_POST["editarVenta"]." editada");
				}

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
	ELIMINAR VENTA
	=============================================*/

	static public function ctrEliminarVenta(){

		if(isset($_GET["idVenta"])){

			if(($_SESSION["perfil"] ?? "") != "Administrador"){
				echo '<script>window.location = "ventas";</script>';
				return;
			}

			$tabla = "ventas";

			$item = "id";
			$valor = $_GET["idVenta"];

			$traerVenta = ModeloVentas::mdlMostrarVentas($tabla, $item, $valor);
			if(!$traerVenta){
				echo '<script>swal({type:"error",title:"La venta ya no existe",confirmButtonText:"Cerrar"}).then(function(){window.location="ventas";});</script>';
				return;
			}

			$respuesta = ModeloVentas::mdlEliminarVenta($tabla, $_GET["idVenta"]);

			if($respuesta == "ok"){
				if (class_exists("ControladorLogs")) {
					ControladorLogs::ctrRegistrarLog("eliminar", "ventas", "Venta ".$_GET["idVenta"]." eliminada");
				}

				echo'<script>

				swal({
					  type: "success",
					  title: "La venta ha sido borrada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "ventas";

								}
							})

				</script>';

			}else{
				echo '<script>
					swal({
						type:"error",
						title:"No se pudo eliminar la venta",
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

	static public function ctrRangoFechasVentas($fechaInicial, $fechaFinal){

		$tabla = "ventas";

		$respuesta = ModeloVentas::mdlRangoFechasVentas($tabla, $fechaInicial, $fechaFinal);

		return $respuesta;
		
	}

	/*=============================================
	DESCARGAR EXCEL
	=============================================*/

	public function ctrDescargarReporte(){

		if(isset($_GET["reporte"])){

			$tabla = "ventas";

			if(isset($_GET["fechaInicial"]) && isset($_GET["fechaFinal"])){

				$ventas = ModeloVentas::mdlRangoFechasVentas($tabla, $_GET["fechaInicial"], $_GET["fechaFinal"]);

			}else{

				$item = null;
				$valor = null;

				$ventas = ModeloVentas::mdlMostrarVentas($tabla, $item, $valor);

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
					<td style='font-weight:bold; border:1px solid #eee;'>CLIENTE</td>
					<td style='font-weight:bold; border:1px solid #eee;'>VENDEDOR</td>
					<td style='font-weight:bold; border:1px solid #eee;'>CANTIDAD</td>
					<td style='font-weight:bold; border:1px solid #eee;'>PRODUCTOS</td>
					<td style='font-weight:bold; border:1px solid #eee;'>DESCUENTO</td>
					<td style='font-weight:bold; border:1px solid #eee;'>NETO</td>		
					<td style='font-weight:bold; border:1px solid #eee;'>TOTAL</td>		
					<td style='font-weight:bold; border:1px solid #eee;'>METODO DE PAGO</td	
					<td style='font-weight:bold; border:1px solid #eee;'>FECHA</td>		
					</tr>");

			foreach ($ventas as $row => $item){

				$cliente = ControladorClientes::ctrMostrarClientes("id", $item["id_cliente"]);
				$vendedor = ControladorUsuarios::ctrMostrarUsuarios("id", $item["id_vendedor"]);

			 echo utf8_decode("<tr>
			 			<td style='border:1px solid #eee;'>".$item["codigo"]."</td> 
			 			<td style='border:1px solid #eee;'>".$cliente["nombre"]."</td>
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
					<td style='border:1px solid #eee;'>Bs".number_format($item["descuento"],2)."</td>
					<td style='border:1px solid #eee;'>Bs ".number_format($item["neto"],2)."</td>	
					<td style='border:1px solid #eee;'>Bs ".number_format($item["total"],2)."</td>
					<td style='border:1px solid #eee;'>".$item["metodo_pago"]."</td>
					<td style='border:1px solid #eee;'>".substr($item["fecha"],0,10)."</td>		
		 			</tr>");


			}


			echo "</table>";

		}

	}


	/*=============================================
	SUMA TOTAL VENTAS
	=============================================*/

	public function ctrSumaTotalVentas(){

		$tabla = "ventas";

		$respuesta = ModeloVentas::mdlSumaTotalVentas($tabla);

		return $respuesta;

	}

}
