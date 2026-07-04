<?php
class ControladorCotizacion{
    static public function ctrMostrarCotizacion($item, $valor){

		$tabla = "cotizaciones";

		$respuesta = ModeloCotizacion::mdlMostrarCotizacion($tabla, $item, $valor);

		return $respuesta;

	}
	
	static public function ctrCrearCotizacion(){

		if(isset($_POST["nuevaCotizacion"])){

			

			if($_POST["listaProductosCotizacion"] == ""){

					echo'<script>

				swal({
					  type: "error",
					  title: "La cotizacion no se ha ejecuta si no hay productos",
					  title: "La cotizacion ha sido guardada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar"
					  }).then(function(result){
								if (result.value) {

								window.location = "cotizacion";

								}
							})

				</script>';

				return;
			}


			

		

			$tabla = "cotizaciones";
			$condiciones = trim($_POST["condicionesCotizacion"] ?? "");
			if($condiciones == ""){
				$condiciones = "Forma de pago: efectivo, transferencia o segun acuerdo con el cliente.\nForma de entrega: en instalaciones del cliente o punto acordado.\nPrecios: incluyen impuestos de ley.\nGarantia: segun condiciones del fabricante y servicio contratado.";
			}

			$datos = array("id_user"=>$_POST["idUser"],
						   "id_cliente"=>$_POST["seleccionarCliente"],
						   "codigo"=>$_POST["nuevaCotizacion"],
						   "productos"=>$_POST["listaProductosCotizacion"],
						   "descuento"=>$_POST["nuevoPrecioImpuesto"],
						   "neto"=>$_POST["nuevoPrecioNeto"],
						   "total"=>$_POST["totalCotizacion"],
						   "valido_hasta"=>$_POST["validoHastaCotizacion"] ?? null,
						   "condiciones"=>$condiciones);

			$respuesta = ModeloCotizacion::mdlIngresarCotizacion($tabla, $datos);

			if($respuesta == "ok" || (($respuesta["respuesta"] ?? "") == "ok")){

				$codigoCotizacion = htmlspecialchars($_POST["nuevaCotizacion"], ENT_QUOTES, "UTF-8");
				$idCotizacion = htmlspecialchars($respuesta["id"] ?? "", ENT_QUOTES, "UTF-8");

				echo'<script>

				localStorage.removeItem("rango");

				swal({
					  type: "success",
					  title: "La cotización ha sido guardada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Ver boleta"
					  }).then(function(result){
								if (result.value) {

								window.open("extensiones/tcpdf/pdf/cotizacion.php?idCotizacion='.$idCotizacion.'&codigoCotizacion='.$codigoCotizacion.'", "_blank");
								window.location = "cotizacion";

								}
							})

				</script>';

			}

		}

	}

	static public function ctrMostrarSolicitudesWeb($estado = null){
		return ModeloCotizacion::mdlMostrarSolicitudesWeb($estado);
	}

	static public function ctrMostrarCotizacionesClienteWeb($idCliente){
		return ModeloCotizacion::mdlMostrarCotizacionesClienteWeb((int)$idCliente);
	}

	static public function ctrActualizarSolicitudWebCotizada(){
		if(isset($_POST["procesarSolicitudWeb"])){
			$productos = json_decode($_POST["productosSolicitudWeb"] ?? "[]", true);
			if(!is_array($productos) || count($productos) == 0){
				echo '<script>
					Swal.fire({icon:"error", title:"Debe existir al menos un producto"}).then(function(){ window.location="solicitudes-web"; });
				</script>';
				return;
			}

			$productosFinales = array();
			$neto = 0;
			foreach($productos as $producto){
				$cantidad = max(1, (int)($producto["cantidad"] ?? 1));
				$precio = max(0, (float)($producto["precio"] ?? 0));
				$total = $cantidad * $precio;
				$neto += $total;
				$productosFinales[] = array(
					"id" => (int)($producto["id"] ?? 0),
					"descripcion" => $producto["descripcion"] ?? "Producto",
					"cantidad" => $cantidad,
					"stock" => $cantidad,
					"precio" => $precio,
					"total" => $total
				);
			}

			$descuentoPorcentaje = max(0, (float)($_POST["descuentoSolicitudWeb"] ?? 0));
			$descuentoMonto = $neto * ($descuentoPorcentaje / 100);
			$totalCotizacion = $neto - $descuentoMonto;
			$condiciones = trim($_POST["condicionesSolicitudWeb"] ?? "");
			if($condiciones == ""){
				$condiciones = "Forma de pago: efectivo, transferencia o segun acuerdo con el cliente.\nForma de entrega: en instalaciones del cliente o punto acordado.\nPrecios: incluyen impuestos de ley.\nGarantia: segun condiciones del fabricante y servicio contratado.";
			}

			$datos = array(
				"id" => (int)$_POST["idSolicitudWeb"],
				"id_user" => (int)$_SESSION["id"],
				"productos" => json_encode($productosFinales, JSON_UNESCAPED_UNICODE),
				"descuento" => $descuentoMonto,
				"neto" => $neto,
				"total" => $totalCotizacion,
				"valido_hasta" => $_POST["validoHastaSolicitudWeb"] ?? date("Y-m-d", strtotime("+7 days")),
				"condiciones" => $condiciones
			);

			$respuesta = ModeloCotizacion::mdlActualizarSolicitudWebCotizada($datos);
			if($respuesta == "ok"){
				$codigoSolicitudWeb = htmlspecialchars($_POST["codigoSolicitudWeb"] ?? "", ENT_QUOTES, "UTF-8");
				$idSolicitudWeb = htmlspecialchars($_POST["idSolicitudWeb"] ?? "", ENT_QUOTES, "UTF-8");
				echo '<script>
					(function(){
						var boletaUrl = "extensiones/tcpdf/pdf/cotizacion.php?idCotizacion='.$idSolicitudWeb.'&codigoCotizacion='.$codigoSolicitudWeb.'";
						var volver = function(){ window.location = "solicitudes-web"; };
						var verBoleta = function(){ window.open(boletaUrl, "_blank"); };
						var mostrarModal = function(){
							if(window.Swal && typeof window.Swal.fire === "function"){
								window.Swal.fire({
									icon: "success",
									title: "Envio exitoso",
									text: "La cotizacion fue publicada para el cliente correctamente.",
									showCancelButton: true,
									confirmButtonText: "Ver boleta",
									cancelButtonText: "Volver a solicitudes web",
									allowOutsideClick: false,
									allowEscapeKey: false
								}).then(function(result){
									if(result.value || result.isConfirmed){ verBoleta(); }
									volver();
								});
								return;
							}
							if(window.swal){
								window.swal({
									type: "success",
									title: "Envio exitoso",
									text: "La cotizacion fue publicada para el cliente correctamente.",
									showCancelButton: true,
									confirmButtonText: "Ver boleta",
									cancelButtonText: "Volver a solicitudes web",
									closeOnConfirm: false
								}).then(function(result){
									if(result.value){ verBoleta(); }
									volver();
								});
								return;
							}
							alert("Envio exitoso. La cotizacion fue publicada para el cliente correctamente.");
							window.location = "solicitudes-web";
						};
						if(document.readyState === "loading"){
							document.addEventListener("DOMContentLoaded", mostrarModal);
						}else{
							mostrarModal();
						}
						setTimeout(function(){
							if(document.visibilityState !== "hidden" && location.href.indexOf("procesar-solicitud-web") !== -1){
								volver();
							}
						}, 15000);
					})();
				</script>';
			}else{
				echo '<script>
					Swal.fire({icon:"error", title:"No se pudo actualizar la solicitud"}).then(function(){ window.location="solicitudes-web"; });
				</script>';
			}
		}
	}
	static public function ctrEliminarCotizacion(){

		if(isset($_GET["idCotizar"])){

			$tabla ="cotizaciones";
			$datos = $_GET["idCotizar"];

			$respuesta = ModeloCotizacion::mdlEliminarCotizacion($tabla, $datos);

			if($respuesta == "ok"){

				echo'<script>

				swal({
					  type: "success",
					  title: "La cotizacion ha sido eliminada correctamente",
					  showConfirmButton: true,
					  confirmButtonText: "Cerrar",
					  closeOnConfirm: false
					  }).then(function(result){
								if (result.value) {

								window.location = "cotizacion";

								}
							})

				</script>';

			}		

		}

	}

	static public function ctrEliminarSolicitudWeb(){
		if(!isset($_GET["eliminarSolicitudWeb"])){
			return;
		}
		if(($_SESSION["perfil"] ?? "") !== "Administrador"){
			echo '<script>swal({type:"error",title:"Sin permiso",text:"Solo el administrador puede eliminar solicitudes web.",confirmButtonText:"Cerrar"}).then(function(){window.location="solicitudes-web";});</script>';
			return;
		}
		$idSolicitud = (int)$_GET["eliminarSolicitudWeb"];
		$respuesta = ModeloCotizacion::mdlEliminarSolicitudWeb($idSolicitud);
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("eliminar", "solicitudes_web", "Solicitud web/cotizacion ".$idSolicitud." eliminada por administrador");
			}
			echo '<script>swal({type:"success",title:"Solicitud web eliminada",confirmButtonText:"Cerrar"}).then(function(){window.location="solicitudes-web";});</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo eliminar",confirmButtonText:"Cerrar"}).then(function(){window.location="solicitudes-web";});</script>';
		}
	}

}
