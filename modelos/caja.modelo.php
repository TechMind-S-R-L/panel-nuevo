<?php

require_once "conexion.php";

class ModeloCaja{

	static public function mdlAsegurarTablas(){
		$conexion = Conexion::conectar();

		$conexion->exec(
			"CREATE TABLE IF NOT EXISTS caja_aperturas (
				id INT NOT NULL AUTO_INCREMENT,
				id_cajero INT NOT NULL,
				monto_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
				estado VARCHAR(20) NOT NULL DEFAULT 'abierta',
				fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				fecha_cierre DATETIME NULL,
				monto_esperado_cierre DECIMAL(12,2) NULL,
				monto_contado_cierre DECIMAL(12,2) NULL,
				diferencia DECIMAL(12,2) NULL,
				observacion_apertura VARCHAR(500) NULL,
				observacion_cierre VARCHAR(500) NULL,
				PRIMARY KEY (id),
				KEY idx_caja_apertura_cajero_estado (id_cajero, estado),
				KEY idx_caja_apertura_fecha (fecha_apertura)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8"
		);

		$conexion->exec(
			"CREATE TABLE IF NOT EXISTS caja_movimientos (
				id BIGINT NOT NULL AUTO_INCREMENT,
				id_apertura INT NOT NULL,
				id_usuario INT NOT NULL,
				tipo VARCHAR(20) NOT NULL,
				origen VARCHAR(40) NOT NULL,
				referencia_tipo VARCHAR(40) NULL,
				id_referencia BIGINT NULL,
				codigo_referencia VARCHAR(100) NULL,
				metodo_pago VARCHAR(50) NOT NULL DEFAULT 'Efectivo',
				monto DECIMAL(12,2) NOT NULL,
				afecta_efectivo TINYINT(1) NOT NULL DEFAULT 1,
				descripcion VARCHAR(500) NOT NULL,
				fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY idx_caja_movimiento_apertura (id_apertura, fecha),
				KEY idx_caja_movimiento_referencia (referencia_tipo, id_referencia),
				CONSTRAINT fk_caja_movimiento_apertura
					FOREIGN KEY (id_apertura) REFERENCES caja_aperturas(id)
					ON UPDATE CASCADE ON DELETE RESTRICT
			) ENGINE=InnoDB DEFAULT CHARSET=utf8"
		);

		return "ok";
	}

	static public function mdlAperturaActiva($idCajero){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"SELECT a.*, u.nombre AS cajero
			 FROM caja_aperturas a
			 LEFT JOIN usuarios u ON u.id = a.id_cajero
			 WHERE a.id_cajero = :id_cajero AND a.estado = 'abierta'
			 ORDER BY a.id DESC
			 LIMIT 1"
		);
		$stmt->bindValue(":id_cajero", (int)$idCajero, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlAbrirCaja($datos){
		self::mdlAsegurarTablas();
		$conexion = Conexion::conectar();

		try{
			$conexion->beginTransaction();

			$stmtBloqueo = $conexion->prepare(
				"SELECT id
				 FROM caja_aperturas
				 WHERE id_cajero = :id_cajero AND estado = 'abierta'
				 LIMIT 1
				 FOR UPDATE"
			);
			$stmtBloqueo->bindValue(":id_cajero", (int)$datos["id_cajero"], PDO::PARAM_INT);
			$stmtBloqueo->execute();

			if($stmtBloqueo->fetch()){
				$conexion->rollBack();
				return "ya_abierta";
			}

			$stmt = $conexion->prepare(
				"INSERT INTO caja_aperturas
				 (id_cajero, monto_inicial, observacion_apertura)
				 VALUES (:id_cajero, :monto_inicial, :observacion)"
			);
			$stmt->bindValue(":id_cajero", (int)$datos["id_cajero"], PDO::PARAM_INT);
			$stmt->bindValue(":monto_inicial", (float)$datos["monto_inicial"]);
			$stmt->bindValue(":observacion", trim((string)($datos["observacion"] ?? "")), PDO::PARAM_STR);
			$stmt->execute();
			$idApertura = (int)$conexion->lastInsertId();

			$conexion->commit();
			return $idApertura;
		}catch(Throwable $e){
			if($conexion->inTransaction()){
				$conexion->rollBack();
			}
			error_log("Error al abrir caja: ".$e->getMessage());
			return "error";
		}
	}

	static public function mdlRegistrarMovimiento($datos){
		self::mdlAsegurarTablas();
		$conexion = Conexion::conectar();

		try{
			$conexion->beginTransaction();

			$stmtCaja = $conexion->prepare(
				"SELECT id
				 FROM caja_aperturas
				 WHERE id = :id_apertura AND estado = 'abierta'
				 LIMIT 1
				 FOR UPDATE"
			);
			$stmtCaja->bindValue(":id_apertura", (int)$datos["id_apertura"], PDO::PARAM_INT);
			$stmtCaja->execute();
			if(!$stmtCaja->fetch()){
				$conexion->rollBack();
				return "caja_cerrada";
			}

			if(!empty($datos["referencia_tipo"]) && !empty($datos["id_referencia"])){
				$stmtExiste = $conexion->prepare(
					"SELECT id
					 FROM caja_movimientos
					 WHERE referencia_tipo = :referencia_tipo
					   AND id_referencia = :id_referencia
					   AND origen = :origen
					 LIMIT 1"
				);
				$stmtExiste->bindValue(":referencia_tipo", $datos["referencia_tipo"], PDO::PARAM_STR);
				$stmtExiste->bindValue(":id_referencia", (int)$datos["id_referencia"], PDO::PARAM_INT);
				$stmtExiste->bindValue(":origen", $datos["origen"], PDO::PARAM_STR);
				$stmtExiste->execute();
				if($stmtExiste->fetch()){
					$conexion->rollBack();
					return "duplicado";
				}
			}

			$stmt = $conexion->prepare(
				"INSERT INTO caja_movimientos
				 (id_apertura, id_usuario, tipo, origen, referencia_tipo, id_referencia,
				  codigo_referencia, metodo_pago, monto, afecta_efectivo, descripcion)
				 VALUES
				 (:id_apertura, :id_usuario, :tipo, :origen, :referencia_tipo, :id_referencia,
				  :codigo_referencia, :metodo_pago, :monto, :afecta_efectivo, :descripcion)"
			);
			$stmt->bindValue(":id_apertura", (int)$datos["id_apertura"], PDO::PARAM_INT);
			$stmt->bindValue(":id_usuario", (int)$datos["id_usuario"], PDO::PARAM_INT);
			$stmt->bindValue(":tipo", $datos["tipo"], PDO::PARAM_STR);
			$stmt->bindValue(":origen", $datos["origen"], PDO::PARAM_STR);
			$stmt->bindValue(":referencia_tipo", $datos["referencia_tipo"] ?: null, $datos["referencia_tipo"] ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$stmt->bindValue(":id_referencia", !empty($datos["id_referencia"]) ? (int)$datos["id_referencia"] : null, !empty($datos["id_referencia"]) ? PDO::PARAM_INT : PDO::PARAM_NULL);
			$stmt->bindValue(":codigo_referencia", $datos["codigo_referencia"] ?: null, $datos["codigo_referencia"] ? PDO::PARAM_STR : PDO::PARAM_NULL);
			$stmt->bindValue(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
			$stmt->bindValue(":monto", (float)$datos["monto"]);
			$stmt->bindValue(":afecta_efectivo", (int)$datos["afecta_efectivo"], PDO::PARAM_INT);
			$stmt->bindValue(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
			$stmt->execute();

			$idMovimiento = (int)$conexion->lastInsertId();
			$conexion->commit();
			return $idMovimiento;
		}catch(Throwable $e){
			if($conexion->inTransaction()){
				$conexion->rollBack();
			}
			error_log("Error al registrar movimiento de caja: ".$e->getMessage());
			return "error";
		}
	}

	static public function mdlResumenApertura($idApertura){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"SELECT
				a.*,
				u.nombre AS cajero,
				COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' THEN m.monto ELSE 0 END), 0) AS total_ingresos,
				COALESCE(SUM(CASE WHEN m.tipo = 'egreso' THEN m.monto ELSE 0 END), 0) AS total_egresos,
				COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' AND m.afecta_efectivo = 1 THEN m.monto ELSE 0 END), 0) AS ingresos_efectivo,
				COALESCE(SUM(CASE WHEN m.tipo = 'egreso' AND m.afecta_efectivo = 1 THEN m.monto ELSE 0 END), 0) AS egresos_efectivo,
				COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' AND m.afecta_efectivo = 0 THEN m.monto ELSE 0 END), 0) AS ingresos_electronicos,
				COUNT(m.id) AS cantidad_movimientos
			 FROM caja_aperturas a
			 LEFT JOIN usuarios u ON u.id = a.id_cajero
			 LEFT JOIN caja_movimientos m ON m.id_apertura = a.id
			 WHERE a.id = :id
			 GROUP BY a.id, u.nombre"
		);
		$stmt->bindValue(":id", (int)$idApertura, PDO::PARAM_INT);
		$stmt->execute();
		$resumen = $stmt->fetch(PDO::FETCH_ASSOC);
		if($resumen){
			$resumen["efectivo_esperado"] = (float)$resumen["monto_inicial"] + (float)$resumen["ingresos_efectivo"] - (float)$resumen["egresos_efectivo"];
		}
		return $resumen;
	}

	static public function mdlMovimientosApertura($idApertura){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"SELECT m.*, u.nombre AS usuario
			 FROM caja_movimientos m
			 LEFT JOIN usuarios u ON u.id = m.id_usuario
			 WHERE m.id_apertura = :id_apertura
			 ORDER BY m.fecha DESC, m.id DESC"
		);
		$stmt->bindValue(":id_apertura", (int)$idApertura, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlCerrarCaja($datos){
		self::mdlAsegurarTablas();
		$conexion = Conexion::conectar();

		try{
			$conexion->beginTransaction();
			$stmt = $conexion->prepare(
				"UPDATE caja_aperturas
				 SET estado = 'cerrada',
				     fecha_cierre = NOW(),
				     monto_esperado_cierre = :esperado,
				     monto_contado_cierre = :contado,
				     diferencia = :diferencia,
				     observacion_cierre = :observacion
				 WHERE id = :id AND id_cajero = :id_cajero AND estado = 'abierta'"
			);
			$stmt->bindValue(":esperado", (float)$datos["esperado"]);
			$stmt->bindValue(":contado", (float)$datos["contado"]);
			$stmt->bindValue(":diferencia", (float)$datos["diferencia"]);
			$stmt->bindValue(":observacion", trim((string)($datos["observacion"] ?? "")), PDO::PARAM_STR);
			$stmt->bindValue(":id", (int)$datos["id"], PDO::PARAM_INT);
			$stmt->bindValue(":id_cajero", (int)$datos["id_cajero"], PDO::PARAM_INT);
			$stmt->execute();
			if($stmt->rowCount() === 0){
				$conexion->rollBack();
				return "error";
			}
			$conexion->commit();
			return "ok";
		}catch(Throwable $e){
			if($conexion->inTransaction()){
				$conexion->rollBack();
			}
			error_log("Error al cerrar caja: ".$e->getMessage());
			return "error";
		}
	}

	static public function mdlHistorial($limite = 30, $idCajero = null){
		self::mdlAsegurarTablas();
		$sql = "SELECT a.*, u.nombre AS cajero
		        FROM caja_aperturas a
		        LEFT JOIN usuarios u ON u.id = a.id_cajero";
		if($idCajero !== null){
			$sql .= " WHERE a.id_cajero = :id_cajero";
		}
		$sql .= " ORDER BY a.id DESC LIMIT ".max(1, min(200, (int)$limite));
		$stmt = Conexion::conectar()->prepare($sql);
		if($idCajero !== null){
			$stmt->bindValue(":id_cajero", (int)$idCajero, PDO::PARAM_INT);
		}
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}

