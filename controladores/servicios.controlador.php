<?php

class ControladorServicios{

	static public function ctrMostrarServicios($item = null, $valor = null){
		return ModeloServicios::mdlMostrarServicios($item, $valor);
	}

	static public function ctrMostrarServiciosPendientesPago(){
		return ModeloServicios::mdlMostrarServiciosPendientesPago();
	}

	static public function ctrMostrarServiciosCobrados(){
		return ModeloServicios::mdlMostrarServiciosCobrados();
	}

	static public function ctrMostrarServiciosTecnico($idTecnico){
		return ModeloServicios::mdlMostrarServiciosTecnico($idTecnico);
	}

	static public function ctrEliminarServicioCompleto(){
		if(!isset($_GET["eliminarServicio"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador"){
			echo '<script>window.location = "administrar-servicios";</script>';
			return;
		}

		$idServicio = (int)$_GET["eliminarServicio"];
		$respuesta = ModeloServicios::mdlEliminarServicioCompleto($idServicio);
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("eliminar", "servicios", "Servicio ".$idServicio." eliminado completamente");
			}
			echo '<script>swal({type:"success",title:"Servicio eliminado completamente",confirmButtonText:"Cerrar"}).then(function(result){if(result.value){window.location="administrar-servicios";}});</script>';
		}else if($respuesta == "con_movimientos"){
			echo '<script>swal({type:"warning",title:"El servicio tiene movimientos contables",text:"No se puede eliminar porque ya registra uno o mas pagos. Debe conservarse para la trazabilidad de caja.",confirmButtonText:"Cerrar"}).then(function(){window.location="administrar-servicios";});</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo eliminar el servicio",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrMostrarPrecios(){
		return ModeloServicios::mdlMostrarPrecios();
	}

	static public function ctrMostrarEquipoTaller($idServicio){
		return ModeloServicios::mdlMostrarEquipoTaller($idServicio);
	}

	static private function ctrGuardarDocumentoSoftwareInicial($proyecto, $campoArchivo, $tipoDocumento, $titulo, $observacion = ""){
		if(!isset($_FILES[$campoArchivo]) || $_FILES[$campoArchivo]["error"] !== UPLOAD_ERR_OK){
			return;
		}

		$extension = strtolower(pathinfo($_FILES[$campoArchivo]["name"], PATHINFO_EXTENSION));
		if($extension !== "pdf"){
			return;
		}

		$directorio = "vistas/documentos/proyectos/".(int)$proyecto["id"];
		if(!is_dir($directorio)){
			mkdir($directorio, 0775, true);
		}

		$nombreSeguro = strtolower($tipoDocumento)."-".date("YmdHis")."-".uniqid().".pdf";
		$ruta = $directorio."/".$nombreSeguro;
		if(!move_uploaded_file($_FILES[$campoArchivo]["tmp_name"], $ruta)){
			return;
		}

		ModeloProyectos::mdlGuardarDocumento(array(
			"id_proyecto" => (int)$proyecto["id"],
			"id_usuario" => (int)$_SESSION["id"],
			"tipo_documento" => $tipoDocumento,
			"titulo" => $titulo,
			"archivo" => $ruta,
			"observacion" => $observacion,
			"visible_cliente" => 1
		));
	}

	static public function ctrMostrarEquiposTaller(){
		return ModeloServicios::mdlMostrarEquiposTaller();
	}

	static public function ctrMostrarRepuestosTaller($idServicio = null){
		return ModeloServicios::mdlMostrarRepuestosTaller($idServicio);
	}

	static public function ctrCrearServicio(){

		if(!isset($_POST["nuevoServicio"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "vendedor"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$cantidadCamaras = (int)($_POST["cantidadCamaras"] ?? 0);
		$metrosDistancia = (float)($_POST["metrosDistancia"] ?? 0);
		$metrosCanalizacion = (float)($_POST["metrosCanalizacion"] ?? 0);
		$esTaller = ($_POST["tipoServicio"] ?? "") == "Soporte tecnico en taller";
		$esSoftware = ($_POST["tipoServicio"] ?? "") == "Desarrollo de software";
		$precio = ($esTaller || $esSoftware) ? false : ModeloServicios::mdlMostrarPrecioServicio($_POST["tipoServicio"], $_POST["tipoInstalacion"]);

		if(!$esTaller && !$esSoftware && !$precio){
			echo '<script>swal({type:"error",title:"No hay precio configurado para este servicio",text:"Admin o caja debe registrar el tarifario primero.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$precioMetro = ($esTaller || $esSoftware) ? 0 : (float)$precio["precio_por_metro"];
		$precioCanalizacionMetro = ($esTaller || $esSoftware) ? 0 : (float)$precio["precio_canalizacion_metro"];
		$precioCamara = ($esTaller || $esSoftware) ? 0 : (float)$precio["precio_por_camara"];
		$costoVisita = ($esTaller || $esSoftware) ? 0 : (float)$precio["costo_visita"];
		$costoDiagnostico = ($esTaller || $esSoftware) ? 0 : (float)$precio["costo_diagnostico"];
		$costoTransporte = ($esTaller || $esSoftware) ? 0 : (float)$precio["costo_transporte"];
		$costoManoObra = ($esTaller || $esSoftware) ? 0 : (float)$precio["mano_obra_base"];
		$recargoAltura = (!$esTaller && !$esSoftware && isset($_POST["requiereAltura"])) ? (float)$precio["recargo_altura"] : 0;
		$recargoUrgencia = (!$esTaller && !$esSoftware && isset($_POST["servicioUrgente"])) ? (float)$precio["recargo_urgencia"] : 0;
		$total = $esSoftware
			? (float)($_POST["precioTotalSoftware"] ?? 0)
			: (($metrosDistancia * $precioMetro) + ($metrosCanalizacion * $precioCanalizacionMetro) + ($cantidadCamaras * $precioCamara) + $costoVisita + $costoDiagnostico + $costoTransporte + $costoManoObra + $recargoAltura + $recargoUrgencia);
		$montoAdelantoSoftware = $esSoftware ? round((float)($_POST["montoAdelantoSoftware"] ?? 0), 2) : 0;
		$numeroCuotasSoftware = $esSoftware ? max(1, min(12, (int)($_POST["numeroCuotasSoftware"] ?? 1))) : 0;
		$fechaPrimeraCuotaSoftware = trim($_POST["fechaPrimeraCuotaSoftware"] ?? "");

		if(!$esTaller && !$esSoftware && $_POST["tipoServicio"] != "Diagnostico tecnico" && $cantidadCamaras <= 0){
			echo '<script>swal({type:"error",title:"Ingrese la cantidad de camaras",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if(!$esTaller && !$esSoftware && ($total <= 0 || empty($_POST["idClienteServicio"]))){
			echo '<script>swal({type:"error",title:"Complete cliente, camaras y datos del servicio",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if($esTaller && (empty($_POST["idClienteServicio"]) || empty($_POST["tipoEquipoTaller"]) || empty($_POST["fallaReportadaTaller"]))){
			echo '<script>swal({type:"error",title:"Complete cliente, equipo y falla reportada",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if($esSoftware && (empty($_POST["idClienteServicio"]) || empty($_POST["nombreProyectoSoftware"]) || empty($_POST["tipoSoftwareProyecto"]) || $total <= 0 || $montoAdelantoSoftware <= 0 || $montoAdelantoSoftware > $total || $fechaPrimeraCuotaSoftware == "")){
			echo '<script>swal({type:"error",title:"Complete cliente, proyecto, precio total, adelanto y plan de cuotas",text:"El adelanto debe ser mayor a cero, no puede superar el precio total y debe registrar fecha de primera cuota.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$tecnicoAsignado = null;
		if($esTaller){
			$tecnicoAsignado = ModeloServicios::mdlBuscarTecnicoLibre();
			if(!$tecnicoAsignado){
				echo '<script>swal({type:"error",title:"No hay tecnicos activos",text:"Cree o active un usuario tecnico antes de registrar el equipo.",confirmButtonText:"Cerrar"});</script>';
				return;
			}
		}

		$datos = array(
			"codigo" => time(),
			"id_cliente" => $_POST["idClienteServicio"],
			"id_vendedor" => $_SESSION["id"],
			"tipo_servicio" => $_POST["tipoServicio"],
			"tipo_instalacion" => $esTaller ? "Taller" : ($esSoftware ? ($_POST["tipoSoftwareProyecto"] ?? "Software") : $_POST["tipoInstalacion"]),
			"cantidad_camaras" => $cantidadCamaras,
			"metros_distancia" => $metrosDistancia,
			"metros_canalizacion" => $metrosCanalizacion,
			"precio_por_metro" => $precioMetro,
			"precio_canalizacion_metro" => $precioCanalizacionMetro,
			"precio_por_camara" => $precioCamara,
			"costo_visita" => $costoVisita,
			"costo_diagnostico" => $costoDiagnostico,
			"costo_transporte" => $costoTransporte,
			"costo_mano_obra" => $costoManoObra,
			"recargo_altura" => $recargoAltura,
			"recargo_urgencia" => $recargoUrgencia,
			"total" => $total,
			"direccion_instalacion" => $esTaller ? "Equipo recibido en taller" : ($esSoftware ? "Proyecto de software" : $_POST["direccionInstalacion"]),
			"referencia" => $esTaller ? ($_POST["observacionesTaller"] ?? "") : ($esSoftware ? ($_POST["plazoEntregaSoftware"] ?? "") : $_POST["referenciaInstalacion"]),
			"latitud" => ($esTaller || $esSoftware) ? "" : $_POST["latitudInstalacion"],
			"longitud" => ($esTaller || $esSoftware) ? "" : $_POST["longitudInstalacion"],
			"preguntas_cliente" => $_POST["preguntasClienteServicio"],
			"diagnostico_inicial" => $esTaller ? ($_POST["fallaReportadaTaller"] ?? "") : ($esSoftware ? ($_POST["alcanceSoftware"] ?? "") : $_POST["diagnosticoInicialServicio"]),
			"observaciones" => $esTaller ? ($_POST["estadoFisicoTaller"] ?? "") : ($esSoftware ? ($_POST["observacionesSoftware"] ?? "") : $_POST["observacionesServicio"]),
			"estado_pago" => $esTaller ? "pendiente_retiro" : ($esSoftware ? "pendiente_adelanto" : "pendiente"),
			"estado_servicio" => $esTaller ? "pendiente_almacen" : ($esSoftware ? "pendiente_adelanto" : "pendiente")
		);

		$respuesta = ModeloServicios::mdlIngresarServicio($datos);

		if($respuesta == "ok"){
			$servicio = ModeloServicios::mdlMostrarServicios("codigo", $datos["codigo"]);

			if($esTaller){
				$fotoEquipo = "";
				if(isset($_FILES["fotoEquipoTaller"]) && $_FILES["fotoEquipoTaller"]["error"] === UPLOAD_ERR_OK){
					$directorio = "vistas/img/servicios_taller/".$servicio["id"];
					if(!is_dir($directorio)){
						mkdir($directorio, 0755, true);
					}
					$extension = strtolower(pathinfo($_FILES["fotoEquipoTaller"]["name"], PATHINFO_EXTENSION));
					if(in_array($extension, array("jpg", "jpeg", "png"))){
						$fotoEquipo = $directorio."/equipo.".$extension;
						move_uploaded_file($_FILES["fotoEquipoTaller"]["tmp_name"], $fotoEquipo);
					}
				}

				$codigoEquipo = "EQ-".$servicio["codigo"]."-".$servicio["id"];
				ModeloServicios::mdlIngresarEquipoTaller(array(
					"id_servicio" => $servicio["id"],
					"codigo_equipo" => $codigoEquipo,
					"tipo_equipo" => $_POST["tipoEquipoTaller"],
					"marca" => $_POST["marcaEquipoTaller"] ?? "",
					"modelo" => $_POST["modeloEquipoTaller"] ?? "",
					"serie" => $_POST["serieEquipoTaller"] ?? "",
					"accesorios" => $_POST["accesoriosEquipoTaller"] ?? "",
					"falla_reportada" => $_POST["fallaReportadaTaller"] ?? "",
					"estado_fisico" => $_POST["estadoFisicoTaller"] ?? "",
					"foto_equipo" => $fotoEquipo
				));

				ModeloServicios::mdlAsignarTecnicoServicio($servicio["id"], $tecnicoAsignado["id"], "pendiente_almacen");
				$servicio = ModeloServicios::mdlMostrarServicios("id", $servicio["id"]);
			}

			if($esSoftware){
				$montoAdelanto = min($total, $montoAdelantoSoftware);
				$porcentajeAdelanto = $total > 0 ? round(($montoAdelanto / $total) * 100, 2) : 0;
				$codigoProyecto = "SW-".$servicio["codigo"]."-".$servicio["id"];
				ModeloProyectos::mdlCrearProyectoSoftware(array(
					"id_servicio" => $servicio["id"],
					"codigo" => $codigoProyecto,
					"nombre_proyecto" => $_POST["nombreProyectoSoftware"],
					"tipo_software" => $_POST["tipoSoftwareProyecto"],
					"alcance" => $_POST["alcanceSoftware"] ?? "",
					"entregables" => $_POST["entregablesSoftware"] ?? "",
					"exclusiones" => $_POST["exclusionesSoftware"] ?? "",
					"plazo_entrega" => $_POST["plazoEntregaSoftware"] ?? "",
					"fecha_entrega_estimada" => $_POST["fechaEntregaSoftware"] ?? null,
					"precio_total" => $total,
					"porcentaje_adelanto" => $porcentajeAdelanto,
					"monto_adelanto" => $montoAdelanto,
					"saldo_pendiente" => max(0, $total - $montoAdelanto),
					"id_desarrollador" => null,
					"estado" => "pendiente_adelanto",
					"observaciones" => $_POST["observacionesSoftware"] ?? ""
				));

				$proyectoSoftware = ModeloProyectos::mdlMostrarProyectoPorServicio($servicio["id"]);
				if($proyectoSoftware){
					$saldoSoftware = max(0, $total - $montoAdelanto);
					$cuotasSoftware = array();
					if($saldoSoftware > 0){
						$montoBaseCuota = floor(($saldoSoftware / $numeroCuotasSoftware) * 100) / 100;
						$acumuladoCuotas = 0;
						for($i = 1; $i <= $numeroCuotasSoftware; $i++){
							$montoCuota = ($i == $numeroCuotasSoftware) ? round($saldoSoftware - $acumuladoCuotas, 2) : $montoBaseCuota;
							$acumuladoCuotas += $montoCuota;
							$fechaCuota = date("Y-m-d", strtotime($fechaPrimeraCuotaSoftware." +".($i - 1)." month"));
							$cuotasSoftware[] = array(
								"numero" => $i,
								"concepto" => "Cuota ".$i." de ".$numeroCuotasSoftware." - saldo de desarrollo",
								"monto" => $montoCuota,
								"fecha_vencimiento" => $fechaCuota
							);
						}
					}
					ModeloProyectos::mdlCrearCuotasSoftware($proyectoSoftware["id"], $cuotasSoftware);
					self::ctrGuardarDocumentoSoftwareInicial($proyectoSoftware, "propuestaTecnicaSoftware", "propuesta_tecnica", "Propuesta tecnica", "Documento cargado al crear el contrato.");
					self::ctrGuardarDocumentoSoftwareInicial($proyectoSoftware, "propuestaComercialSoftware", "propuesta_comercial", "Propuesta comercial", "Documento cargado al crear el contrato.");
				}
			}

			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("crear", "servicios", "Servicio ".$datos["codigo"]." creado pendiente de pago y asignacion tecnica");
			}

			$pdfIngreso = $esTaller ? "boleta-ingreso-equipo.php" : ($esSoftware ? "contrato-software.php" : "boleta-servicio.php");
			$tituloOk = $esTaller ? "Equipo ingresado. Debe entregarse a almacen para control interno." : ($esSoftware ? "Proyecto registrado. Imprima el contrato y pase a caja para cobrar el adelanto." : "Servicio registrado. Debe pasar por caja.");
			echo '<script>
				swal({type:"success",title:"'.$tituloOk.'",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/'.$pdfIngreso.'?idServicio='.$servicio["id"].'", "_blank");
						window.location = "servicios";
					}
				});
			</script>';
		}
	}

	static public function ctrAprobarPagoServicio(){

		if(!isset($_GET["aprobarPagoServicio"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "cajero"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}
		if(!ControladorCaja::ctrPuedeOperar()){
			echo '<script>swal({type:"warning",title:"Debe abrir su caja",text:"Registre el efectivo inicial antes de realizar cobros.",confirmButtonText:"Ir a caja"}).then(function(){window.location="caja";});</script>';
			return;
		}

		$id = (int)$_GET["aprobarPagoServicio"];
		$servicio = ModeloServicios::mdlMostrarServicios("id", $id);

		if(!$servicio){
			return;
		}

		$esTaller = ($servicio["tipo_servicio"] ?? "") == "Soporte tecnico en taller";
		$esSoftware = ($servicio["tipo_servicio"] ?? "") == "Desarrollo de software";
		if($esTaller){
			$manoObraServicio = max(0, (float)($_GET["manoObraServicio"] ?? 0));
			$baseRepuestosServicio = max(0, (float)$servicio["total"] - (float)($servicio["costo_mano_obra"] ?? 0));
			$totalTallerServicio = $baseRepuestosServicio + $manoObraServicio;
			ModeloServicios::mdlActualizarCostosTallerServicio($id, $manoObraServicio, $totalTallerServicio);
			$servicio["costo_mano_obra"] = $manoObraServicio;
			$servicio["total"] = $totalTallerServicio;
		}
		$montoRecibido = (float)($_GET["montoRecibido"] ?? $servicio["total"]);
		$cambio = max(0, $montoRecibido - (float)$servicio["total"]);
		$proyectoSoftware = $esSoftware ? ModeloProyectos::mdlMostrarProyectoPorServicio($id) : null;
		$montoCobroSoftware = 0;
		$montoAplicadoSoftware = 0;
		$adelantoCompletoSoftware = false;
		if($esSoftware && $proyectoSoftware){
			if(($servicio["estado_pago"] ?? "") == "pendiente_final"){
				$montoCobroSoftware = (float)$proyectoSoftware["saldo_pendiente"];
			}else{
				$adelantoPactado = (float)($proyectoSoftware["monto_adelanto"] ?? 0);
				$adelantoPagado = (float)($proyectoSoftware["pago_adelanto"] ?? 0);
				$montoCobroSoftware = max(0, $adelantoPactado - $adelantoPagado);
				if($montoCobroSoftware <= 0){
					$montoCobroSoftware = $adelantoPactado;
				}
				$montoAplicadoSoftware = min($montoRecibido, $montoCobroSoftware);
				$adelantoCompletoSoftware = ($adelantoPagado + $montoAplicadoSoftware) >= ($adelantoPactado - 0.01);
			}
			$cambio = max(0, $montoRecibido - $montoCobroSoftware);
		}
		$requiereAsignacionSoftware = $esSoftware && (($servicio["estado_pago"] ?? "") == "pendiente_final" || $adelantoCompletoSoftware);
		$tecnico = $esSoftware
			? ($requiereAsignacionSoftware ? (!empty($proyectoSoftware["id_desarrollador"]) ? ControladorUsuarios::ctrMostrarUsuarios("id", $proyectoSoftware["id_desarrollador"]) : ModeloProyectos::mdlBuscarDesarrolladorLibre()) : array("id" => null, "nombre" => "Pendiente de completar adelanto"))
			: ($esTaller && !empty($servicio["id_tecnico"])
			? ControladorUsuarios::ctrMostrarUsuarios("id", $servicio["id_tecnico"])
			: ModeloServicios::mdlBuscarTecnicoLibre());

		if(!$tecnico){
			$textoRolFaltante = $esSoftware ? "desarrollador" : "tecnico";
			echo '<script>swal({type:"error",title:"No hay '.$textoRolFaltante.' activo",text:"Cree o active un usuario con rol '.$textoRolFaltante.' antes de cobrar este servicio.",confirmButtonText:"Cerrar"}).then(function(){window.location="pagos-servicios";});</script>';
			return;
		}

		$datos = array(
			"id" => $id,
			"id_tecnico" => $tecnico["id"],
			"metodo_pago" => $_GET["metodoPago"] ?? "Efectivo",
			"monto_recibido" => $montoRecibido,
			"cambio" => $cambio,
			"codigo_transaccion" => $_GET["codigoTransaccion"] ?? "",
			"id_cajero" => $_SESSION["id"],
			"estado_pago" => ($esSoftware && ($servicio["estado_pago"] ?? "") != "pendiente_final")
				? ($adelantoCompletoSoftware ? "adelanto_pagado" : "pendiente_adelanto")
				: null,
			"estado_servicio" => ($esSoftware && ($servicio["estado_pago"] ?? "") != "pendiente_final")
				? ($adelantoCompletoSoftware ? "en_desarrollo" : "pendiente_adelanto")
				: null
		);

		$respuesta = ModeloServicios::mdlAprobarPagoServicio($datos);

		if($respuesta == "ok"){
			$montoAplicadoServicio = $esSoftware
				? ((($servicio["estado_pago"] ?? "") == "pendiente_final") ? min($montoRecibido, $montoCobroSoftware) : $montoAplicadoSoftware)
				: min($montoRecibido, (float)$servicio["total"]);
			$saldoAntesPago = $esSoftware ? $montoCobroSoftware : (float)$servicio["total"];
			$saldoDespuesPago = max(0, $saldoAntesPago - $montoAplicadoServicio);
			$tipoPagoServicio = $esSoftware
				? ((($servicio["estado_pago"] ?? "") == "pendiente_final") ? "saldo_final_software" : ($adelantoCompletoSoftware ? "adelanto_software" : "adelanto_parcial_software"))
				: "servicio";
			$idPagoServicio = ModeloServicios::mdlRegistrarPagoServicio(array(
				"id_servicio" => $id,
				"id_cajero" => (int)$_SESSION["id"],
				"tipo_pago" => $tipoPagoServicio,
				"monto" => $montoAplicadoServicio,
				"metodo_pago" => $_GET["metodoPago"] ?? "Efectivo",
				"cambio" => $cambio,
				"codigo_transaccion" => $_GET["codigoTransaccion"] ?? "",
				"saldo_antes" => $saldoAntesPago,
				"saldo_despues" => $saldoDespuesPago
			));

			ControladorCaja::ctrRegistrarMovimiento(array(
				"tipo" => "ingreso",
				"origen" => "servicio",
				"referencia_tipo" => $idPagoServicio ? "pago_servicio" : "servicio",
				"id_referencia" => $idPagoServicio ? $idPagoServicio : $id,
				"codigo_referencia" => $servicio["codigo"] ?? "",
				"metodo_pago" => $_GET["metodoPago"] ?? "Efectivo",
				"monto" => $montoAplicadoServicio,
				"descripcion" => "Cobro de servicio ".($servicio["codigo"] ?? $id)." (".$tipoPagoServicio.")"
			));

			if($esSoftware && $proyectoSoftware){
				if(($servicio["estado_pago"] ?? "") == "pendiente_final"){
					ModeloProyectos::mdlRegistrarPagoFinal($id, $montoCobroSoftware);
				}else{
					ModeloProyectos::mdlRegistrarAdelanto($id, $montoAplicadoSoftware, (int)($tecnico["id"] ?? 0), $adelantoCompletoSoftware);
				}
			}
			if(class_exists("ControladorLogs")){
				$detalleLogPago = $esSoftware && ($servicio["estado_pago"] ?? "") != "pendiente_final" && !$adelantoCompletoSoftware
					? "Pago parcial de adelanto registrado para servicio ".$id
					: "Pago aprobado para servicio ".$id." y asignado a responsable ".$tecnico["nombre"];
				ControladorLogs::ctrRegistrarLog("aprobar_pago", "servicios", $detalleLogPago);
			}

			$abrirDetalleTaller = $esTaller
				? 'window.open("extensiones/tcpdf/pdf/boleta-taller.php?idServicio='.$id.'&tipo=correctivo", "_blank");'
				: '';
			$abrirSoftware = $esSoftware
				? 'window.open("extensiones/tcpdf/pdf/boleta-software-pago.php?idServicio='.$id.($idPagoServicio ? '&idPago='.$idPagoServicio : '').'", "_blank");'
				: '';
			$textoCobro = $esSoftware
				? ((($servicio["estado_pago"] ?? "") == "pendiente_final") ? "Saldo final cobrado. Ya puede emitirse el acta de entrega." : ($adelantoCompletoSoftware ? "Adelanto completado. El proyecto fue asignado al desarrollador." : "Pago parcial registrado. El proyecto seguira pendiente hasta completar el adelanto."))
				: ($esTaller
				? "Entregue la nota de venta al cliente para que pase por almacen a retirar su equipo."
				: "Entregue la nota de venta al cliente como comprobante de pago.");

			echo '<script>
				swal({type:"success",title:"Pago del servicio aprobado",text:"'.$textoCobro.'",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						'.($esSoftware ? '' : 'window.open("extensiones/tcpdf/pdf/nota-venta-servicio.php?idServicio='.$id.'&destino=almacen", "_blank");').'
						'.$abrirDetalleTaller.'
						'.$abrirSoftware.'
						window.location = "pagos-servicios";
					}
				});
			</script>';
		}
	}

	static public function ctrGuardarPrecioServicio(){
		if(!isset($_POST["guardarPrecioServicio"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "cajero"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$datos = array(
			"tipo_servicio" => $_POST["tipoServicioPrecio"],
			"tipo_instalacion" => $_POST["tipoInstalacionPrecio"],
			"precio_por_metro" => (float)$_POST["precioPorMetro"],
			"precio_canalizacion_metro" => (float)$_POST["precioCanalizacionMetro"],
			"mano_obra_base" => (float)$_POST["manoObraBase"],
			"precio_por_camara" => (float)$_POST["precioPorCamara"],
			"costo_visita" => (float)$_POST["costoVisita"],
			"costo_diagnostico" => (float)$_POST["costoDiagnostico"],
			"recargo_altura" => (float)$_POST["recargoAltura"],
			"recargo_urgencia" => (float)$_POST["recargoUrgencia"],
			"costo_transporte" => (float)$_POST["costoTransporte"],
			"estado" => isset($_POST["precioActivo"]) ? 1 : 0
		);

		if($datos["tipo_servicio"] == "Instalacion de camaras"){
			$datos["mano_obra_base"] = 0;
			$datos["costo_visita"] = 0;
			$datos["costo_diagnostico"] = 0;
		}else if($datos["tipo_servicio"] == "Mantenimiento de camaras"){
			$datos["tipo_instalacion"] = "Interior";
			$datos["precio_por_metro"] = 0;
			$datos["precio_canalizacion_metro"] = 0;
			$datos["costo_diagnostico"] = 0;
			$datos["recargo_altura"] = 0;
		}else if($datos["tipo_servicio"] == "Reubicacion de camaras"){
			$datos["costo_visita"] = 0;
			$datos["costo_diagnostico"] = 0;
		}else if($datos["tipo_servicio"] == "Diagnostico tecnico"){
			$datos["tipo_instalacion"] = "Interior";
			$datos["precio_por_metro"] = 0;
			$datos["precio_canalizacion_metro"] = 0;
			$datos["mano_obra_base"] = 0;
			$datos["precio_por_camara"] = 0;
			$datos["recargo_altura"] = 0;
		}else if($datos["tipo_servicio"] == "Domotica"){
			$opcionesDomotica = array("Sensor de movimiento", "Luces inteligentes", "Chapas electricas", "Apertura de puertas", "Automatizacion integral");
			if(!in_array($datos["tipo_instalacion"], $opcionesDomotica, true)){
				$datos["tipo_instalacion"] = "Sensor de movimiento";
			}
			$datos["precio_por_camara"] = 0;
			$datos["costo_diagnostico"] = 0;
			$datos["recargo_altura"] = 0;
		}else if($datos["tipo_servicio"] == "Instalacion de alarmas"){
			$datos["tipo_instalacion"] = "Instalacion";
			$datos["precio_por_camara"] = 0;
			$datos["costo_diagnostico"] = 0;
			$datos["recargo_altura"] = 0;
		}

		$respuesta = ModeloServicios::mdlGuardarPrecioServicio($datos);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("guardar_precio", "precios_servicios", "Tarifa ".$datos["tipo_servicio"]." / ".$datos["tipo_instalacion"]." actualizada");
			}

			echo '<script>swal({type:"success",title:"Precio de servicio guardado",confirmButtonText:"Cerrar"}).then(function(result){if(result.value){window.location="precios-servicios";}});</script>';
		}
	}

	static public function ctrCambiarEstadoServicio(){

		if(!isset($_GET["servicioEstado"]) || !isset($_GET["idServicio"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "tecnico"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$servicio = ModeloServicios::mdlMostrarServicios("id", (int)$_GET["idServicio"]);
		if(!$servicio){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador"){
			if((int)$servicio["id_tecnico"] != (int)$_SESSION["id"]){
				echo '<script>window.location = "ordenes-servicio";</script>';
				return;
			}
		}

		$estado = $_GET["servicioEstado"];
		if(!in_array($estado, array("asignado", "atendiendo", "en_proceso", "completado"))){
			return;
		}

		$respuesta = ModeloServicios::mdlCambiarEstadoServicio((int)$_GET["idServicio"], $estado);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("cambiar_estado", "servicios", "Servicio ".$_GET["idServicio"]." marcado como ".$estado);
			}
			$esOrdenCampoTomada = $estado == "atendiendo"
				&& ($servicio["tipo_servicio"] ?? "") != "Soporte tecnico en taller"
				&& stripos((string)($servicio["tipo_servicio"] ?? ""), "software") === false;

			if($esOrdenCampoTomada){
				$idServicio = (int)$_GET["idServicio"];
				echo '<script>
					swal({
						type:"success",
						title:"Orden de campo tomada",
						text:"La boleta de conformidad esta lista para llevar a la instalacion.",
						showCancelButton:true,
						confirmButtonText:"Imprimir boleta",
						cancelButtonText:"Continuar sin imprimir"
					}).then(function(result){
						if(result.value){
							window.open("extensiones/tcpdf/pdf/boleta-conformidad-instalacion.php?idServicio='.$idServicio.'", "_blank");
						}
						window.location = "ordenes-servicio";
					});
				</script>';
			}else{
				echo '<script>window.location = "ordenes-servicio";</script>';
			}
		}
	}

	static public function ctrRecepcionarEquipoAlmacen(){
		if(!isset($_GET["recibirEquipoTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "almacen"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_GET["recibirEquipoTaller"];
		$respuesta = ModeloServicios::mdlRecepcionarEquipoAlmacen($idServicio, (int)$_SESSION["id"]);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("recibir_equipo_taller", "almacen", "Almacen recibio equipo de taller del servicio ".$idServicio);
			}
			echo '<script>
				swal({type:"success",title:"Equipo recibido en almacen",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/boleta-custodia-equipo.php?idServicio='.$idServicio.'&tipo=recepcion", "_blank");
						window.location = "recepcion-equipos-taller";
					}
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo recibir el equipo",text:"Verifique que siga pendiente de recepcion.",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrRetirarEquipoTecnico(){
		if(!isset($_GET["retirarEquipoTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "tecnico"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_GET["retirarEquipoTaller"];
		$servicio = ModeloServicios::mdlMostrarServicios("id", $idServicio);
		if(!$servicio || $servicio["tipo_servicio"] != "Soporte tecnico en taller"){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && (int)$servicio["id_tecnico"] != (int)$_SESSION["id"]){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		if(($servicio["estado_servicio"] ?? "") != "atendiendo"){
			echo '<script>
				swal({
					type:"error",
					title:"Primero debe atender la solicitud",
					text:"Marque Atender solicitud antes de solicitar el retiro del equipo a almacen.",
					confirmButtonText:"Cerrar"
				}).then(function(result){
					window.location = "ordenes-servicio";
				});
			</script>';
			return;
		}

		$idTecnico = ($_SESSION["perfil"] ?? "") == "Administrador" ? (int)$servicio["id_tecnico"] : (int)$_SESSION["id"];
		$respuesta = ModeloServicios::mdlSolicitarRetiroEquipoTecnico($idServicio, $idTecnico);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("solicitud_retiro_equipo_taller", "servicios", "Tecnico solicito retiro de equipo de almacen del servicio ".$idServicio);
			}
			echo '<script>
				swal({
					type:"success",
					title:"Solicitud de retiro enviada",
					text:"Almacen debe confirmar la entrega fisica del equipo e imprimir la constancia.",
					confirmButtonText:"Cerrar"
				}).then(function(result){
					if(result.value){
						window.location = "ordenes-servicio";
					}
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"El equipo no esta disponible para solicitar retiro",text:"Verifique que almacen haya recibido el equipo y que no exista una solicitud previa.",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrEntregarEquipoTecnicoAlmacen(){
		if(!isset($_GET["entregarEquipoTecnicoTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "almacen"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_GET["entregarEquipoTecnicoTaller"];
		$servicio = ModeloServicios::mdlMostrarServicios("id", $idServicio);
		if(!$servicio || $servicio["tipo_servicio"] != "Soporte tecnico en taller" || empty($servicio["id_tecnico"])){
			echo '<script>window.location = "recepcion-equipos-taller";</script>';
			return;
		}

		$respuesta = ModeloServicios::mdlRetirarEquipoTecnico($idServicio, (int)$servicio["id_tecnico"], (int)$_SESSION["id"]);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("entregar_equipo_tecnico", "almacen", "Almacen entrego equipo de taller al tecnico del servicio ".$idServicio);
			}
			echo '<script>
				swal({type:"success",title:"Equipo entregado al tecnico",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/boleta-custodia-equipo.php?idServicio='.$idServicio.'&tipo=retiro", "_blank");
						window.location = "recepcion-equipos-taller";
					}
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo entregar el equipo",text:"Verifique que el equipo este recibido en almacen.",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrReingresarEquipoAlmacen(){
		if(!isset($_POST["reingresarEquipoTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "almacen"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_POST["idServicioReingresoTaller"];
		$respuesta = ModeloServicios::mdlReingresarEquipoAlmacen(array(
			"id_servicio" => $idServicio,
			"id_almacenero" => (int)$_SESSION["id"],
			"observacion" => $_POST["observacionReingresoTaller"] ?? ""
		));

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("reingreso_equipo_taller", "almacen", "Almacen recibio equipo reparado/devuelto del servicio ".$idServicio);
			}
			echo '<script>
				swal({type:"success",title:"Equipo reingresado a almacen",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/boleta-custodia-equipo.php?idServicio='.$idServicio.'&tipo=reingreso", "_blank");
						window.location = "recepcion-equipos-taller";
					}
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo reingresar el equipo",text:"Verifique que el tecnico ya haya registrado diagnostico, reparacion o devolucion.",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrGuardarRepuestosTaller(){
		if(!isset($_POST["guardarRepuestosTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "tecnico"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_POST["idServicioRepuestosTaller"];
		$servicio = ModeloServicios::mdlMostrarServicios("id", $idServicio);
		if(!$servicio || $servicio["tipo_servicio"] != "Soporte tecnico en taller"){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && (int)$servicio["id_tecnico"] != (int)$_SESSION["id"]){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		$productos = json_decode($_POST["listaRepuestosTaller"] ?? "[]", true);
		if(!is_array($productos) || count($productos) == 0){
			echo '<script>swal({type:"error",title:"Seleccione al menos un repuesto",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$respuesta = ModeloServicios::mdlGuardarRepuestosTaller(array(
			"id_servicio" => $idServicio,
			"id_tecnico" => ($_SESSION["perfil"] ?? "") == "Administrador" ? (int)$servicio["id_tecnico"] : (int)$_SESSION["id"],
			"productos" => $productos
		));

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("solicitar_repuestos", "servicios", "Tecnico solicito repuestos para servicio ".$idServicio);
			}
			echo '<script>swal({type:"success",title:"Repuestos solicitados a almacen",confirmButtonText:"Cerrar"}).then(function(result){if(result.value){window.location="ordenes-servicio";}});</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo guardar la solicitud de repuestos",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrEntregarRepuestosTaller(){
		if(!isset($_POST["entregarRepuestosTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "almacen"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_POST["idServicioEntregaRepuestos"];
		if($idServicio <= 0){
			echo '<script>swal({type:"error",title:"Solicitud sin referencia",text:"No se pudo identificar la solicitud de repuestos. Vuelva a abrir el modal desde la tarjeta pendiente.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$servicioReferencia = ModeloServicios::mdlResolverServicioRepuestoTaller($idServicio);
		if($servicioReferencia && !empty($servicioReferencia["id"])){
			$idServicio = (int)$servicioReferencia["id"];
		}

		$codigos = json_decode($_POST["codigosEntregaRepuestosTaller"] ?? "[]", true);
		if(!is_array($codigos)){
			$codigos = array();
		}
		$respuesta = ModeloServicios::mdlEntregarRepuestosTaller(array(
			"id_servicio" => $idServicio,
			"id_almacenero" => (int)$_SESSION["id"],
			"observacion" => $_POST["observacionEntregaRepuestos"] ?? "",
			"codigos_por_producto" => $codigos
		));

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("entregar_repuestos", "almacen", "Almacen entrego repuestos de taller para servicio ".$idServicio);
			}
			echo '<script>
				swal({type:"success",title:"Repuestos entregados al tecnico",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/boleta-repuestos-taller.php?idServicio='.$idServicio.'", "_blank");
						window.location = "repuestos-taller-almacen";
					}
				});
			</script>';
		}else if($respuesta == "stock_insuficiente"){
			echo '<script>swal({type:"error",title:"Stock insuficiente",text:"Revise el stock antes de entregar los repuestos.",confirmButtonText:"Cerrar"});</script>';
		}else if($respuesta == "sin_codigos_disponibles"){
			echo '<script>swal({type:"error",title:"Faltan codigos unicos",text:"El producto tiene stock pendiente, pero no tiene suficientes codigos unicos disponibles para validar la entrega. Registre o corrija los codigos en almacen.",confirmButtonText:"Cerrar"});</script>';
		}else if($respuesta == "codigo_no_existe"){
			echo '<script>swal({type:"error",title:"Codigo no encontrado",text:"Uno de los codigos ingresados no existe en el inventario de codigos unicos.",confirmButtonText:"Cerrar"});</script>';
		}else if($respuesta == "codigo_no_pertenece"){
			echo '<script>swal({type:"error",title:"Codigo de otro producto",text:"Uno de los codigos ingresados existe, pero pertenece a otro producto. Use los codigos disponibles que muestra el modal.",confirmButtonText:"Cerrar"});</script>';
		}else if($respuesta == "codigo_no_disponible"){
			echo '<script>swal({type:"error",title:"Codigo no disponible",text:"Uno de los codigos ya fue vendido, entregado o no esta disponible para despacho.",confirmButtonText:"Cerrar"});</script>';
		}else if($respuesta == "codigos_invalidos"){
			echo '<script>swal({type:"error",title:"Codigos invalidos",text:"Debe registrar un codigo disponible y correcto por cada unidad solicitada.",confirmButtonText:"Cerrar"});</script>';
		}else{
			echo '<script>swal({type:"error",title:"No hay repuestos pendientes para entregar",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrEnviarEquipoAAlmacenTecnico(){
		if(!isset($_GET["enviarEquipoAlmacenTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "tecnico"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_GET["enviarEquipoAlmacenTaller"];
		$servicio = ModeloServicios::mdlMostrarServicios("id", $idServicio);
		if(!$servicio || $servicio["tipo_servicio"] != "Soporte tecnico en taller"){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && (int)$servicio["id_tecnico"] != (int)$_SESSION["id"]){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		$respuesta = ModeloServicios::mdlEnviarEquipoAAlmacenTecnico($idServicio);
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("enviar_equipo_almacen", "servicios", "Tecnico envio equipo a almacen del servicio ".$idServicio);
			}
			echo '<script>
				swal({type:"success",title:"Equipo enviado a almacen",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/boleta-custodia-equipo.php?idServicio='.$idServicio.'&tipo=reingreso", "_blank");
						window.location = "ordenes-servicio";
					}
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"Primero registre la reparacion o devolucion",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrEntregarEquipoClienteAlmacen(){
		if(!isset($_GET["entregarEquipoClienteTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "almacen"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$idServicio = (int)$_GET["entregarEquipoClienteTaller"];
		$servicio = ModeloServicios::mdlMostrarServicios("id", $idServicio);
		if(!$servicio || ($servicio["estado_pago"] ?? "") != "aprobado"){
			echo '<script>swal({type:"error",title:"El cliente aun no pago en caja",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$respuesta = ModeloServicios::mdlEntregarEquipoCliente($idServicio, (int)$_SESSION["id"]);
		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("entregar_equipo_cliente", "almacen", "Almacen entrego equipo al cliente del servicio ".$idServicio);
			}
			echo '<script>
				swal({type:"success",title:"Equipo entregado al cliente",confirmButtonText:"Cerrar"}).then(function(result){
					if(result.value){
						window.open("extensiones/tcpdf/pdf/nota-venta-servicio.php?idServicio='.$idServicio.'&destino=cliente", "_blank");
						window.open("extensiones/tcpdf/pdf/boleta-taller.php?idServicio='.$idServicio.'&tipo=correctivo", "_blank");
						window.location = "recepcion-equipos-taller";
					}
				});
			</script>';
		}else{
			echo '<script>swal({type:"error",title:"No se pudo entregar el equipo",text:"Debe estar reingresado en almacen.",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrGuardarInformeTecnico(){
		if(!isset($_POST["guardarInformeTecnico"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "tecnico"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$servicio = ModeloServicios::mdlMostrarServicios("id", (int)$_POST["idServicioInforme"]);
		if(!$servicio || ($servicio["tipo_servicio"] ?? "") == "Soporte tecnico en taller" || ($servicio["estado_servicio"] ?? "") != "en_proceso"){
			echo '<script>swal({type:"error",title:"La orden debe estar en trabajo iniciado",text:"Primero use Iniciar trabajo antes de concluir el informe.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador"){
			if((int)$servicio["id_tecnico"] != (int)$_SESSION["id"]){
				echo '<script>window.location = "ordenes-servicio";</script>';
				return;
			}
		}

		$hallazgos = trim($_POST["hallazgosTecnicos"] ?? "");
		$trabajo = trim($_POST["trabajoRealizado"] ?? "");
		if($hallazgos === "" || $trabajo === ""){
			echo '<script>swal({type:"error",title:"Complete el informe tecnico",text:"Los hallazgos y el trabajo ejecutado son obligatorios.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$archivoBoleta = $_FILES["boletaConformidadFirmada"] ?? null;
		if(!$archivoBoleta || ($archivoBoleta["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE){
			echo '<script>swal({type:"error",title:"Adjunte la boleta firmada",text:"Debe escanear o fotografiar la boleta de conformidad firmada antes de concluir el servicio.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if(($archivoBoleta["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK){
			echo '<script>swal({type:"error",title:"No se pudo recibir la boleta",text:"Vuelva a seleccionar el archivo e intente nuevamente.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if((int)($archivoBoleta["size"] ?? 0) <= 0 || (int)$archivoBoleta["size"] > (10 * 1024 * 1024)){
			echo '<script>swal({type:"error",title:"Archivo no valido",text:"La boleta debe pesar como maximo 10 MB.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$tiposPermitidos = array(
			"application/pdf" => "pdf",
			"image/jpeg" => "jpg",
			"image/png" => "png",
			"image/webp" => "webp"
		);
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$tipoReal = $finfo->file($archivoBoleta["tmp_name"]);
		if(!isset($tiposPermitidos[$tipoReal])){
			echo '<script>swal({type:"error",title:"Formato no permitido",text:"Use un archivo PDF, JPG, PNG o WEBP.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$idServicioInforme = (int)$_POST["idServicioInforme"];
		$directorioRelativo = "vistas/documentos/servicios_campo/".$idServicioInforme;
		$directorioAbsoluto = __DIR__."/../".$directorioRelativo;
		if(!is_dir($directorioAbsoluto) && !mkdir($directorioAbsoluto, 0755, true)){
			echo '<script>swal({type:"error",title:"No se pudo guardar la boleta",text:"No fue posible preparar la carpeta del documento.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$nombreSeguro = "conformidad_".date("Ymd_His")."_".bin2hex(random_bytes(4)).".".$tiposPermitidos[$tipoReal];
		$rutaRelativaBoleta = $directorioRelativo."/".$nombreSeguro;
		$rutaAbsolutaBoleta = $directorioAbsoluto."/".$nombreSeguro;
		if(!move_uploaded_file($archivoBoleta["tmp_name"], $rutaAbsolutaBoleta)){
			echo '<script>swal({type:"error",title:"No se pudo guardar la boleta",text:"Vuelva a adjuntar el documento e intente nuevamente.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$datos = array(
			"id" => $idServicioInforme,
			"hallazgos_tecnicos" => $hallazgos,
			"trabajo_realizado" => $trabajo,
			"recomendaciones" => trim($_POST["recomendacionesTecnicas"] ?? ""),
			"boleta_conformidad_archivo" => $rutaRelativaBoleta,
			"estado_servicio" => "completado"
		);

		$respuesta = ModeloServicios::mdlGuardarInformeTecnico($datos);

		if($respuesta == "ok"){
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("informe_tecnico", "servicios", "Informe tecnico y boleta firmada guardados para servicio ".$datos["id"]);
			}
			echo '<script>swal({type:"success",title:"Servicio concluido",text:"El informe y la boleta de conformidad firmada quedaron guardados en el sistema.",confirmButtonText:"Cerrar"}).then(function(result){if(result.value){window.location="ordenes-servicio";}});</script>';
		}else{
			if(is_file($rutaAbsolutaBoleta)){
				unlink($rutaAbsolutaBoleta);
			}
			echo '<script>swal({type:"error",title:"No se pudo concluir el servicio",text:"El informe no fue guardado. Intente nuevamente.",confirmButtonText:"Cerrar"});</script>';
		}
	}

	static public function ctrGuardarDiagnosticoTaller(){
		if(!isset($_POST["guardarDiagnosticoTaller"])){
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && ($_SESSION["rol"] ?? "") != "tecnico"){
			echo '<script>window.location = "inicio";</script>';
			return;
		}

		$servicio = ModeloServicios::mdlMostrarServicios("id", (int)$_POST["idServicioTaller"]);
		if(!$servicio || $servicio["tipo_servicio"] != "Soporte tecnico en taller"){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		if(($_SESSION["perfil"] ?? "") != "Administrador" && (int)$servicio["id_tecnico"] != (int)$_SESSION["id"]){
			echo '<script>window.location = "ordenes-servicio";</script>';
			return;
		}

		$respuestaCliente = $_POST["respuestaClienteTaller"] ?? "pendiente";
		$equipoTallerActual = ModeloServicios::mdlMostrarEquipoTaller((int)$_POST["idServicioTaller"]);
		$notificado = (isset($_POST["notificadoClienteTaller"]) || (int)($equipoTallerActual["notificado_cliente"] ?? 0) === 1 || !empty($equipoTallerActual["fecha_notificacion"])) ? 1 : 0;
		$repuestosTaller = ModeloServicios::mdlMostrarRepuestosTaller((int)$_POST["idServicioTaller"]);
		$hayRepuestosSolicitados = count(array_filter($repuestosTaller, function($repuesto){
			return ($repuesto["estado"] ?? "") == "solicitado";
		})) > 0;
		$totalRepuestosEntregados = array_reduce($repuestosTaller, function($total, $repuesto){
			return $total + ((($repuesto["estado"] ?? "") == "entregado") ? (float)$repuesto["subtotal"] : 0);
		}, 0);
		$hayRepuestosEntregados = $totalRepuestosEntregados > 0;
		$hayTrabajoCorrectivo = trim($_POST["reparacionRealizadaTaller"] ?? "") != "";

		if($respuestaCliente == "pendiente" && ($hayTrabajoCorrectivo || $hayRepuestosEntregados)){
			$respuestaCliente = "conforme";
		}
		if($hayRepuestosEntregados){
			$notificado = 1;
		}

		if($respuestaCliente != "pendiente" && $notificado != 1){
			echo '<script>swal({type:"error",title:"Debe registrar la notificacion al cliente antes de continuar",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		if($hayRepuestosSolicitados && $hayTrabajoCorrectivo){
			echo '<script>swal({type:"error",title:"Almacen aun no entrego los repuestos",text:"No puede cerrar la reparacion mientras haya piezas pendientes.",confirmButtonText:"Cerrar"});</script>';
			return;
		}

		$estadoEquipo = "diagnosticado";
		$estadoServicio = "diagnosticado";
		$totalFinal = $totalRepuestosEntregados;
		if($respuestaCliente == "conforme"){
			$estadoEquipo = $hayTrabajoCorrectivo ? "reparado" : "autorizado";
			$estadoServicio = $hayTrabajoCorrectivo ? "reparado" : "autorizado";
		}else if($respuestaCliente == "no_conforme"){
			$estadoEquipo = "rechazado";
			$estadoServicio = "devolucion_pend";
		}

		$evidenciasTecnicas = json_decode($equipoTallerActual["evidencias_tecnicas"] ?? "[]", true);
		if(!is_array($evidenciasTecnicas)){
			$evidenciasTecnicas = array();
		}
		if(isset($_FILES["evidenciasTaller"]) && isset($_FILES["evidenciasTaller"]["name"]) && is_array($_FILES["evidenciasTaller"]["name"])){
			$directorioEvidencias = "vistas/img/servicios_taller/".(int)$_POST["idServicioTaller"]."/evidencias";
			if(!is_dir($directorioEvidencias)){
				mkdir($directorioEvidencias, 0755, true);
			}
			$extensionesPermitidas = array("jpg", "jpeg", "png", "webp");
			foreach($_FILES["evidenciasTaller"]["name"] as $indice => $nombreArchivo){
				if(($_FILES["evidenciasTaller"]["error"][$indice] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK){
					continue;
				}
				$extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
				if(!in_array($extension, $extensionesPermitidas)){
					continue;
				}
				$destino = $directorioEvidencias."/evidencia_".date("YmdHis")."_".$indice.".".$extension;
				if(move_uploaded_file($_FILES["evidenciasTaller"]["tmp_name"][$indice], $destino)){
					$evidenciasTecnicas[] = array(
						"archivo" => $destino,
						"fecha" => date("Y-m-d H:i:s")
					);
				}
			}
		}

		$datos = array(
			"id_servicio" => (int)$_POST["idServicioTaller"],
			"diagnostico_tecnico" => trim($_POST["diagnosticoTaller"] ?? "") !== "" ? $_POST["diagnosticoTaller"] : ($equipoTallerActual["diagnostico_tecnico"] ?? "Diagnostico registrado en soporte tecnico"),
			"notificado_cliente" => $notificado,
			"notificado_cliente_fecha" => $notificado,
			"respuesta_cliente" => $respuestaCliente,
			"respuesta_cliente_fecha" => $respuestaCliente,
			"detalle_notificacion" => trim($_POST["detalleNotificacionTaller"] ?? "") !== "" ? $_POST["detalleNotificacionTaller"] : ($equipoTallerActual["detalle_notificacion"] ?? ""),
			"reparacion_realizada" => $_POST["reparacionRealizadaTaller"],
			"reparacion_realizada_fecha" => $_POST["reparacionRealizadaTaller"],
			"repuestos_detalle" => $_POST["repuestosDetalleTaller"],
			"garantia_detalle" => $_POST["garantiaDetalleTaller"],
			"evidencias_tecnicas" => json_encode($evidenciasTecnicas),
			"estado_equipo" => $estadoEquipo
		);

		$respuesta = ModeloServicios::mdlGuardarDiagnosticoTaller($datos);

		if($respuesta == "ok"){
			ModeloServicios::mdlCambiarEstadoServicio((int)$_POST["idServicioTaller"], $estadoServicio);
			if($estadoServicio == "reparado"){
				ModeloServicios::mdlActualizarTotalServicio((int)$_POST["idServicioTaller"], $totalFinal);
			}
			if(class_exists("ControladorLogs")){
				ControladorLogs::ctrRegistrarLog("taller", "servicios", "Informe tecnico final registrado para servicio ".$_POST["idServicioTaller"]);
			}
			echo '<script>swal({type:"success",title:"Equipo reparado",text:"El informe tecnico final fue guardado. Cuando corresponda, use Devolver a almacen para entregar fisicamente el equipo.",confirmButtonText:"Cerrar"}).then(function(result){if(result.value){window.location="ordenes-servicio";}});</script>';
		}
	}
}
