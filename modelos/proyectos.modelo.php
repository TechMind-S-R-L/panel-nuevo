<?php

require_once "conexion.php";

class ModeloProyectos{

	static private function mdlAsegurarVisibilidad($conexion){
		foreach(array("proyecto_software_avances", "proyecto_software_documentos") as $tabla){
			$stmt = $conexion->query("SHOW COLUMNS FROM ".$tabla." LIKE 'visible_cliente'");
			if(!$stmt->fetch(PDO::FETCH_ASSOC)){
				$conexion->exec("ALTER TABLE ".$tabla." ADD visible_cliente TINYINT(1) NOT NULL DEFAULT 0");
			}
		}
	}

	static private function mdlAsegurarTablaCuotas($conexion){
		$conexion->exec(
			"CREATE TABLE IF NOT EXISTS proyecto_software_cuotas (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_proyecto INT NOT NULL,
				numero INT NOT NULL DEFAULT 1,
				concepto VARCHAR(120) NOT NULL DEFAULT 'Cuota de desarrollo',
				monto DECIMAL(12,2) NOT NULL DEFAULT 0,
				fecha_vencimiento DATE NULL,
				estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
				id_pago_servicio INT NULL,
				fecha_pago DATETIME NULL,
				fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_proyecto_estado (id_proyecto, estado),
				INDEX idx_vencimiento (fecha_vencimiento)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci"
		);
	}

	static public function mdlBuscarDesarrolladorLibre(){
		$stmt = Conexion::conectar()->prepare(
			"SELECT u.id, u.nombre, COUNT(p.id) AS proyectos_activos
			 FROM usuarios u
			 LEFT JOIN proyectos_software p ON p.id_desarrollador = u.id
			   AND p.estado IN ('en_desarrollo','revision_interna','revision_cliente','pendiente_pago_final')
			 WHERE u.rol = 'desarrollador' AND u.estado = 1
			 GROUP BY u.id, u.nombre
			 ORDER BY proyectos_activos ASC, u.id ASC
			 LIMIT 1"
		);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlCrearProyectoSoftware($datos){
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO proyectos_software
			 (id_servicio, codigo, nombre_proyecto, tipo_software, alcance, entregables, exclusiones, plazo_entrega, fecha_entrega_estimada, precio_total, porcentaje_adelanto, monto_adelanto, saldo_pendiente, id_desarrollador, estado, observaciones)
			 VALUES
			 (:id_servicio, :codigo, :nombre_proyecto, :tipo_software, :alcance, :entregables, :exclusiones, :plazo_entrega, :fecha_entrega_estimada, :precio_total, :porcentaje_adelanto, :monto_adelanto, :saldo_pendiente, :id_desarrollador, :estado, :observaciones)"
		);
		foreach($datos as $key => $value){
			$stmt->bindValue(":".$key, $value);
		}
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlCrearCuotasSoftware($idProyecto, $cuotas){
		$conexion = Conexion::conectar();
		self::mdlAsegurarTablaCuotas($conexion);
		$stmtBorrar = $conexion->prepare("DELETE FROM proyecto_software_cuotas WHERE id_proyecto = :id");
		$stmtBorrar->bindValue(":id", (int)$idProyecto, PDO::PARAM_INT);
		$stmtBorrar->execute();

		if(empty($cuotas)){
			return "ok";
		}

		$stmt = $conexion->prepare(
			"INSERT INTO proyecto_software_cuotas(id_proyecto, numero, concepto, monto, fecha_vencimiento, estado)
			 VALUES(:id_proyecto, :numero, :concepto, :monto, :fecha_vencimiento, 'pendiente')"
		);
		foreach($cuotas as $cuota){
			$stmt->bindValue(":id_proyecto", (int)$idProyecto, PDO::PARAM_INT);
			$stmt->bindValue(":numero", (int)$cuota["numero"], PDO::PARAM_INT);
			$stmt->bindValue(":concepto", $cuota["concepto"]);
			$stmt->bindValue(":monto", (float)$cuota["monto"]);
			$stmt->bindValue(":fecha_vencimiento", $cuota["fecha_vencimiento"] ?: null);
			if(!$stmt->execute()){
				return "error";
			}
		}
		return "ok";
	}

	static public function mdlMostrarCuotasSoftware($idProyecto){
		$conexion = Conexion::conectar();
		self::mdlAsegurarTablaCuotas($conexion);
		$stmt = $conexion->prepare("SELECT * FROM proyecto_software_cuotas WHERE id_proyecto = :id ORDER BY numero ASC, id ASC");
		$stmt->bindValue(":id", (int)$idProyecto, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarProyectoSoftware($item = null, $valor = null){
		$sql = "SELECT p.*, s.codigo AS codigo_servicio, s.id_cliente, s.id_vendedor, s.estado_pago, s.estado_servicio, s.fecha AS fecha_servicio,
		               c.nombre AS cliente, c.telefono, c.email, c.documento, u.nombre AS desarrollador, v.nombre AS vendedor
		        FROM proyectos_software p
		        INNER JOIN servicios_ventas s ON s.id = p.id_servicio
		        LEFT JOIN clientes c ON c.id = s.id_cliente
		        LEFT JOIN usuarios u ON u.id = p.id_desarrollador
		        LEFT JOIN usuarios v ON v.id = s.id_vendedor";
		if($item !== null){
			$sql .= " WHERE p.$item = :valor";
		}
		$sql .= " ORDER BY p.fecha DESC, p.id DESC";
		$stmt = Conexion::conectar()->prepare($sql);
		if($item !== null){
			$stmt->bindValue(":valor", $valor);
			$stmt->execute();
			return $stmt->fetch(PDO::FETCH_ASSOC);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarProyectoPorServicio($idServicio){
		return self::mdlMostrarProyectoSoftware("id_servicio", $idServicio);
	}

	static public function mdlMostrarProyectosDesarrollador($idDesarrollador){
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.*, s.codigo AS codigo_servicio, s.id_cliente, s.id_vendedor, s.estado_pago, s.estado_servicio,
			        c.nombre AS cliente, c.telefono, c.email, c.documento, v.nombre AS vendedor, u.nombre AS desarrollador
			 FROM proyectos_software p
			 INNER JOIN servicios_ventas s ON s.id = p.id_servicio
			 LEFT JOIN clientes c ON c.id = s.id_cliente
			 LEFT JOIN usuarios v ON v.id = s.id_vendedor
			 LEFT JOIN usuarios u ON u.id = p.id_desarrollador
			 WHERE p.id_desarrollador = :id
			 ORDER BY p.fecha DESC, p.id DESC"
		);
		$stmt->bindParam(":id", $idDesarrollador, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlRegistrarAdelanto($idServicio, $monto, $idDesarrollador, $adelantoCompleto = true){
		$conexion = Conexion::conectar();
		if($adelantoCompleto){
			$sql = "UPDATE proyectos_software
					 SET estado = 'en_desarrollo',
					     pago_adelanto = COALESCE(pago_adelanto, 0) + :monto,
					     fecha_adelanto = NOW(),
					     fecha_inicio = COALESCE(fecha_inicio, CURDATE()),
					     id_desarrollador = COALESCE(id_desarrollador, :id_desarrollador)
					 WHERE id_servicio = :id_servicio";
		}else{
			$sql = "UPDATE proyectos_software
					 SET estado = 'pendiente_adelanto',
					     pago_adelanto = COALESCE(pago_adelanto, 0) + :monto,
					     fecha_adelanto = NOW()
					 WHERE id_servicio = :id_servicio";
		}
		$stmt = $conexion->prepare($sql);
		$stmt->bindValue(":monto", $monto);
		if($adelantoCompleto){
			$stmt->bindValue(":id_desarrollador", $idDesarrollador, PDO::PARAM_INT);
		}
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlRegistrarPagoFinal($idServicio, $monto){
		$stmt = Conexion::conectar()->prepare(
			"UPDATE proyectos_software
			 SET estado = 'pagado_final',
			     pago_final = :monto,
			     fecha_pago_final = NOW()
			 WHERE id_servicio = :id_servicio"
		);
		$stmt->bindParam(":monto", $monto);
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlGuardarAvance($datos){
		try{
			$conexion = Conexion::conectar();
			self::mdlAsegurarVisibilidad($conexion);
			$conexion->beginTransaction();

			$stmt = $conexion->prepare(
				"INSERT INTO proyecto_software_avances(id_proyecto, id_usuario, porcentaje, estado, descripcion, visible_cliente)
				 VALUES(:id_proyecto, :id_usuario, :porcentaje, :estado, :descripcion, :visible_cliente)"
			);
			$stmt->bindParam(":id_proyecto", $datos["id_proyecto"], PDO::PARAM_INT);
			$stmt->bindParam(":id_usuario", $datos["id_usuario"], PDO::PARAM_INT);
			$stmt->bindParam(":porcentaje", $datos["porcentaje"], PDO::PARAM_INT);
			$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
			$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
			$stmt->bindParam(":visible_cliente", $datos["visible_cliente"], PDO::PARAM_INT);
			$stmt->execute();

			$stmtProyecto = $conexion->prepare(
				"UPDATE proyectos_software
				 SET porcentaje_avance = :porcentaje,
				     estado = :estado,
				     observaciones = :descripcion
				 WHERE id = :id_proyecto"
			);
			$stmtProyecto->bindParam(":porcentaje", $datos["porcentaje"], PDO::PARAM_INT);
			$stmtProyecto->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
			$stmtProyecto->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
			$stmtProyecto->bindParam(":id_proyecto", $datos["id_proyecto"], PDO::PARAM_INT);
			$stmtProyecto->execute();

			if($datos["estado"] == "pendiente_pago_final"){
				$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_pago = 'pendiente_final', estado_servicio = 'pendiente_pago_final' WHERE id = (SELECT id_servicio FROM proyectos_software WHERE id = :id)");
				$stmtServicio->bindParam(":id", $datos["id_proyecto"], PDO::PARAM_INT);
				$stmtServicio->execute();
			}

			$conexion->commit();
			return "ok";
		}catch(Exception $e){
			if(isset($conexion) && $conexion->inTransaction()){
				$conexion->rollBack();
			}
			return "error";
		}
	}

	static public function mdlGuardarDocumento($datos){
		$conexion = Conexion::conectar();
		self::mdlAsegurarVisibilidad($conexion);
		$stmt = $conexion->prepare(
			"INSERT INTO proyecto_software_documentos(id_proyecto, id_usuario, tipo_documento, titulo, archivo, observacion, visible_cliente)
			 VALUES(:id_proyecto, :id_usuario, :tipo_documento, :titulo, :archivo, :observacion, :visible_cliente)"
		);
		foreach($datos as $key => $value){
			$stmt->bindValue(":".$key, $value);
		}
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlMostrarDocumentos($idProyecto){
		$conexion = Conexion::conectar();
		self::mdlAsegurarVisibilidad($conexion);
		$stmt = $conexion->prepare(
			"SELECT d.*, u.nombre AS usuario
			 FROM proyecto_software_documentos d
			 LEFT JOIN usuarios u ON u.id = d.id_usuario
			 WHERE d.id_proyecto = :id
			 ORDER BY d.fecha DESC, d.id DESC"
		);
		$stmt->bindParam(":id", $idProyecto, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarDocumento($idDocumento){
		$conexion = Conexion::conectar();
		self::mdlAsegurarVisibilidad($conexion);
		$stmt = $conexion->prepare(
			"SELECT d.*, p.id_desarrollador, p.codigo AS codigo_proyecto
			 FROM proyecto_software_documentos d
			 INNER JOIN proyectos_software p ON p.id = d.id_proyecto
			 WHERE d.id = :id
			 LIMIT 1"
		);
		$stmt->bindParam(":id", $idDocumento, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlEliminarDocumento($idDocumento){
		$stmt = Conexion::conectar()->prepare("DELETE FROM proyecto_software_documentos WHERE id = :id");
		$stmt->bindParam(":id", $idDocumento, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlMostrarAvances($idProyecto){
		$conexion = Conexion::conectar();
		self::mdlAsegurarVisibilidad($conexion);
		$stmt = $conexion->prepare(
			"SELECT a.*, u.nombre AS usuario
			 FROM proyecto_software_avances a
			 LEFT JOIN usuarios u ON u.id = a.id_usuario
			 WHERE a.id_proyecto = :id
			 ORDER BY a.fecha DESC, a.id DESC"
		);
		$stmt->bindParam(":id", $idProyecto, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMarcarEntregado($idProyecto){
		try{
			$conexion = Conexion::conectar();
			$conexion->beginTransaction();
			$stmt = $conexion->prepare("UPDATE proyectos_software SET estado = 'completado', porcentaje_avance = 100, fecha_entrega = NOW() WHERE id = :id AND estado = 'pagado_final'");
			$stmt->bindParam(":id", $idProyecto, PDO::PARAM_INT);
			$stmt->execute();
			if($stmt->rowCount() == 0){
				$conexion->rollBack();
				return "error";
			}
			$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'completado' WHERE id = (SELECT id_servicio FROM proyectos_software WHERE id = :id)");
			$stmtServicio->bindParam(":id", $idProyecto, PDO::PARAM_INT);
			$stmtServicio->execute();
			$conexion->commit();
			return "ok";
		}catch(Exception $e){
			if(isset($conexion) && $conexion->inTransaction()){
				$conexion->rollBack();
			}
			return "error";
		}
	}

	static public function mdlEliminarProyectoSoftware($idProyecto){
		try{
			$conexion = Conexion::conectar();
			$conexion->beginTransaction();

			$stmt = $conexion->prepare("SELECT id, codigo, id_servicio FROM proyectos_software WHERE id = :id LIMIT 1");
			$stmt->bindValue(":id", (int)$idProyecto, PDO::PARAM_INT);
			$stmt->execute();
			$proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!$proyecto){
				$conexion->rollBack();
				return "no_existe";
			}

			self::mdlAsegurarTablaCuotas($conexion);
			$stmt = $conexion->prepare("DELETE FROM proyecto_software_cuotas WHERE id_proyecto = :id");
			$stmt->bindValue(":id", (int)$idProyecto, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare("DELETE FROM proyecto_software_documentos WHERE id_proyecto = :id");
			$stmt->bindValue(":id", (int)$idProyecto, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare("DELETE FROM proyecto_software_avances WHERE id_proyecto = :id");
			$stmt->bindValue(":id", (int)$idProyecto, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare("DELETE FROM proyectos_software WHERE id = :id");
			$stmt->bindValue(":id", (int)$idProyecto, PDO::PARAM_INT);
			$stmt->execute();

			$conexion->commit();
			return array("status" => "ok", "proyecto" => $proyecto);
		}catch(Exception $e){
			if(isset($conexion) && $conexion->inTransaction()){
				$conexion->rollBack();
			}
			return "error";
		}
	}
}
