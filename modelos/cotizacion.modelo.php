<?php

require_once "conexion.php";

class ModeloCotizacion{
    static public function mdlMostrarCotizacion($tabla, $item, $valor){

		if($item != null){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item ORDER BY id ASC");

			$stmt -> bindParam(":".$item, $valor, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY id ASC");

			$stmt -> execute();

			return $stmt -> fetchAll();

		}
		
		$stmt -> close();

		$stmt = null;

	}
	static public function mdlIngresarCotizacion($tabla, $datos){

		$conexion = Conexion::conectar();
		$stmt = $conexion->prepare("INSERT INTO $tabla(codigo, id_cliente, id_user, productos, descuento, neto, total, valido_hasta, condiciones) VALUES (:codigo, :id_cliente, :id_user, :productos, :descuento, :neto, :total, :valido_hasta, :condiciones)");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":id_cliente", $datos["id_cliente"], PDO::PARAM_INT);
		$stmt->bindParam(":id_user", $datos["id_user"], PDO::PARAM_INT);
		$stmt->bindParam(":productos", $datos["productos"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento", $datos["descuento"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":valido_hasta", $datos["valido_hasta"], PDO::PARAM_STR);
		$stmt->bindParam(":condiciones", $datos["condiciones"], PDO::PARAM_STR);
		

		if($stmt->execute()){

			return array("respuesta" => "ok", "id" => $conexion->lastInsertId());

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

	static public function mdlMostrarSolicitudesWeb($estado = null){
		$sql = "SELECT co.*, c.nombre AS cliente, c.email, c.telefono, c.documento, u.nombre AS vendedor
		        FROM cotizaciones co
		        LEFT JOIN clientes c ON c.id = co.id_cliente
		        LEFT JOIN usuarios u ON u.id = co.id_user
		        WHERE co.origen = 'web'";
		if($estado != null){
			$sql .= " AND co.estado_web = :estado";
		}
		$sql .= " ORDER BY co.fecha DESC, co.id DESC";
		$stmt = Conexion::conectar()->prepare($sql);
		if($estado != null){
			$stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarCotizacionesClienteWeb($idCliente){
		$stmt = Conexion::conectar()->prepare(
			"SELECT *
			 FROM cotizaciones
			 WHERE id_cliente = :id_cliente
			   AND origen = 'web'
			 ORDER BY fecha DESC, id DESC"
		);
		$stmt->bindParam(":id_cliente", $idCliente, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlActualizarSolicitudWebCotizada($datos){
		$stmt = Conexion::conectar()->prepare(
			"UPDATE cotizaciones
			 SET id_user = :id_user,
			     productos = :productos,
			     descuento = :descuento,
			     neto = :neto,
			     total = :total,
			     valido_hasta = :valido_hasta,
			     condiciones = :condiciones,
			     estado_web = 'cotizada',
			     visible_cliente = 1
			 WHERE id = :id
			   AND origen = 'web'"
		);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":id_user", $datos["id_user"], PDO::PARAM_INT);
		$stmt->bindParam(":productos", $datos["productos"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento", $datos["descuento"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":valido_hasta", $datos["valido_hasta"], PDO::PARAM_STR);
		$stmt->bindParam(":condiciones", $datos["condiciones"], PDO::PARAM_STR);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEliminarSolicitudWeb($idSolicitud){
		$stmt = Conexion::conectar()->prepare("DELETE FROM cotizaciones WHERE id = :id AND origen = 'web'");
		$stmt->bindValue(":id", (int)$idSolicitud, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlEliminarCotizacion($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("DELETE FROM $tabla WHERE id = :id");

		$stmt -> bindParam(":id", $datos, PDO::PARAM_INT);

		if($stmt -> execute()){

			return "ok";
		
		}else{

			return "error";	

		}

		$stmt -> close();

		$stmt = null;

	}

}
