<?php

class ControladorLogs{

	static public function ctrObtenerIp(){
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			return $_SERVER["HTTP_CLIENT_IP"];
		}

		if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			return trim(explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"])[0]);
		}

		return $_SERVER["REMOTE_ADDR"] ?? "";
	}

	static public function ctrRegistrarLog($accion, $modulo, $detalle = ""){

		if (!class_exists("ModeloLogs")) {
			return "error";
		}

		$datos = array(
			"id_usuario" => $_SESSION["id"] ?? null,
			"usuario" => $_SESSION["nombre"] ?? null,
			"rol" => $_SESSION["rol"] ?? ($_SESSION["perfil"] ?? null),
			"ip" => self::ctrObtenerIp(),
			"accion" => $accion,
			"modulo" => $modulo,
			"detalle" => $detalle
		);

		return ModeloLogs::mdlRegistrarLog($datos);
	}

	static public function ctrMostrarLogs(){
		return ModeloLogs::mdlMostrarLogs();
	}
}
