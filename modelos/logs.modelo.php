<?php

require_once "conexion.php";

class ModeloLogs{

	static public function mdlRegistrarLog($datos){

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO sistema_logs (id_usuario, usuario, rol, ip, accion, modulo, detalle)
			 VALUES (:id_usuario, :usuario, :rol, :ip, :accion, :modulo, :detalle)"
		);

		$stmt->bindValue(":id_usuario", $datos["id_usuario"] ?? null, PDO::PARAM_INT);
		$stmt->bindValue(":usuario", $datos["usuario"] ?? null, PDO::PARAM_STR);
		$stmt->bindValue(":rol", $datos["rol"] ?? null, PDO::PARAM_STR);
		$stmt->bindValue(":ip", $datos["ip"] ?? null, PDO::PARAM_STR);
		$stmt->bindParam(":accion", $datos["accion"], PDO::PARAM_STR);
		$stmt->bindParam(":modulo", $datos["modulo"], PDO::PARAM_STR);
		$stmt->bindParam(":detalle", $datos["detalle"], PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlMostrarLogs($limite = 300){

		$stmt = Conexion::conectar()->prepare("SELECT * FROM sistema_logs ORDER BY fecha DESC, id DESC LIMIT :limite");
		$stmt->bindValue(":limite", (int)$limite, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
