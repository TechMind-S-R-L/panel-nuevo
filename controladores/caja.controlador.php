<?php

class ControladorCaja{

	static public function ctrAsegurarTablas(){
		return ModeloCaja::mdlAsegurarTablas();
	}

	static public function ctrAperturaActiva($idCajero = null){
		$idCajero = $idCajero === null ? (int)($_SESSION["id"] ?? 0) : (int)$idCajero;
		if($idCajero <= 0){
			return false;
		}
		return ModeloCaja::mdlAperturaActiva($idCajero);
	}

	static public function ctrRequiereApertura(){
		return (($_SESSION["rol"] ?? "") === "cajero") && (($_SESSION["perfil"] ?? "") !== "Administrador");
	}

	static public function ctrPuedeOperar(){
		$esAdministrador = (($_SESSION["perfil"] ?? "") === "Administrador");
		$esCajero = (($_SESSION["rol"] ?? "") === "cajero");

		if(!$esAdministrador && !$esCajero){
			return false;
		}

		return (bool)self::ctrAperturaActiva();
	}

	static public function ctrAbrirCaja(){
		if(!isset($_POST["abrirCaja"])){
			return null;
		}
		if(($_SESSION["rol"] ?? "") !== "cajero" && ($_SESSION["perfil"] ?? "") !== "Administrador"){
			return "sin_permiso";
		}

		$montoInicial = isset($_POST["montoInicialCaja"]) ? (float)$_POST["montoInicialCaja"] : -1;
		if($montoInicial < 0){
			return "monto_invalido";
		}

		$respuesta = ModeloCaja::mdlAbrirCaja(array(
			"id_cajero" => (int)$_SESSION["id"],
			"monto_inicial" => $montoInicial,
			"observacion" => $_POST["observacionAperturaCaja"] ?? ""
		));

		if(is_int($respuesta) && class_exists("ControladorLogs")){
			ControladorLogs::ctrRegistrarLog("apertura_caja", "caja", "Caja abierta con Bs ".number_format($montoInicial, 2, ".", ""));
		}
		return $respuesta;
	}

	static public function ctrRegistrarMovimiento($datos){
		$apertura = self::ctrAperturaActiva();
		if(!$apertura){
			return "sin_apertura";
		}

		$metodo = trim((string)($datos["metodo_pago"] ?? "Efectivo"));
		$afectaEfectivo = strcasecmp($metodo, "Efectivo") === 0 ? 1 : 0;
		if(isset($datos["afecta_efectivo"])){
			$afectaEfectivo = (int)(bool)$datos["afecta_efectivo"];
		}

		return ModeloCaja::mdlRegistrarMovimiento(array(
			"id_apertura" => (int)$apertura["id"],
			"id_usuario" => (int)($_SESSION["id"] ?? 0),
			"tipo" => ($datos["tipo"] ?? "") === "egreso" ? "egreso" : "ingreso",
			"origen" => trim((string)($datos["origen"] ?? "manual")),
			"referencia_tipo" => trim((string)($datos["referencia_tipo"] ?? "")),
			"id_referencia" => (int)($datos["id_referencia"] ?? 0),
			"codigo_referencia" => trim((string)($datos["codigo_referencia"] ?? "")),
			"metodo_pago" => $metodo,
			"monto" => max(0, (float)($datos["monto"] ?? 0)),
			"afecta_efectivo" => $afectaEfectivo,
			"descripcion" => trim((string)($datos["descripcion"] ?? "Movimiento de caja"))
		));
	}

	static public function ctrRegistrarMovimientoManual(){
		if(!isset($_POST["registrarMovimientoCaja"])){
			return null;
		}
		if(!self::ctrPuedeOperar()){
			return "sin_apertura";
		}

		$monto = (float)($_POST["montoMovimientoCaja"] ?? 0);
		$descripcion = trim((string)($_POST["descripcionMovimientoCaja"] ?? ""));
		if($monto <= 0 || $descripcion === ""){
			return "datos_invalidos";
		}
		$esEgresoEfectivo = ($_POST["tipoMovimientoCaja"] ?? "") === "egreso"
			&& strcasecmp((string)($_POST["metodoMovimientoCaja"] ?? "Efectivo"), "Efectivo") === 0;
		if($esEgresoEfectivo && !self::ctrPuedeEgresarEfectivo($monto)){
			return "saldo_insuficiente";
		}

		$respuesta = self::ctrRegistrarMovimiento(array(
			"tipo" => ($_POST["tipoMovimientoCaja"] ?? "") === "egreso" ? "egreso" : "ingreso",
			"origen" => "manual",
			"metodo_pago" => $_POST["metodoMovimientoCaja"] ?? "Efectivo",
			"monto" => $monto,
			"descripcion" => $descripcion
		));

		if(is_int($respuesta) && class_exists("ControladorLogs")){
			ControladorLogs::ctrRegistrarLog("movimiento_manual", "caja", ucfirst($_POST["tipoMovimientoCaja"])." manual por Bs ".number_format($monto, 2, ".", "").": ".$descripcion);
		}
		return $respuesta;
	}

	static public function ctrResumenActual(){
		$apertura = self::ctrAperturaActiva();
		return $apertura ? ModeloCaja::mdlResumenApertura($apertura["id"]) : false;
	}

	static public function ctrPuedeEgresarEfectivo($monto){
		$resumen = self::ctrResumenActual();
		if(!$resumen){
			return false;
		}
		return (float)$monto <= ((float)$resumen["efectivo_esperado"] + 0.001);
	}

	static public function ctrMovimientosActuales(){
		$apertura = self::ctrAperturaActiva();
		return $apertura ? ModeloCaja::mdlMovimientosApertura($apertura["id"]) : array();
	}

	static public function ctrCerrarCaja(){
		if(!isset($_POST["cerrarCaja"])){
			return null;
		}
		$resumen = self::ctrResumenActual();
		if(!$resumen){
			return "sin_apertura";
		}

		$contado = max(0, (float)($_POST["montoContadoCaja"] ?? 0));
		$esperado = (float)$resumen["efectivo_esperado"];
		$diferencia = $contado - $esperado;
		$respuesta = ModeloCaja::mdlCerrarCaja(array(
			"id" => (int)$resumen["id"],
			"id_cajero" => (int)$_SESSION["id"],
			"esperado" => $esperado,
			"contado" => $contado,
			"diferencia" => $diferencia,
			"observacion" => $_POST["observacionCierreCaja"] ?? ""
		));

		if($respuesta === "ok" && class_exists("ControladorLogs")){
			ControladorLogs::ctrRegistrarLog("cierre_caja", "caja", "Caja cerrada. Esperado Bs ".number_format($esperado, 2, ".", "").", contado Bs ".number_format($contado, 2, ".", "").", diferencia Bs ".number_format($diferencia, 2, ".", ""));
		}
		return $respuesta;
	}

	static public function ctrHistorial($limite = 30){
		$idCajero = (($_SESSION["perfil"] ?? "") === "Administrador") ? null : (int)($_SESSION["id"] ?? 0);
		return ModeloCaja::mdlHistorial($limite, $idCajero);
	}
}
