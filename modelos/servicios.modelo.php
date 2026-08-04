<?php

require_once "conexion.php";

class ModeloServicios{

	static private function mdlAsegurarColumnasConformidadCampo($conexion){
		static $asegurado = false;
		if($asegurado){
			return;
		}

		$columnas = array();
		$stmt = $conexion->query("SHOW COLUMNS FROM servicios_ventas");
		foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $columna){
			$columnas[$columna["Field"]] = true;
		}

		$agregar = array();
		if(!isset($columnas["boleta_conformidad_archivo"])){
			$agregar[] = "ADD COLUMN boleta_conformidad_archivo VARCHAR(255) NULL AFTER recomendaciones";
		}
		if(!isset($columnas["boleta_conformidad_fecha"])){
			$agregar[] = "ADD COLUMN boleta_conformidad_fecha DATETIME NULL AFTER boleta_conformidad_archivo";
		}
		if(!empty($agregar)){
			$conexion->exec("ALTER TABLE servicios_ventas ".implode(", ", $agregar));
		}

		$asegurado = true;
	}

	static public function mdlIngresarServicio($datos){

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO servicios_ventas
			(codigo, id_cliente, id_vendedor, tipo_servicio, tipo_instalacion, cantidad_camaras, metros_distancia, metros_canalizacion, precio_por_metro, precio_canalizacion_metro, precio_por_camara, costo_visita, costo_diagnostico, costo_transporte, costo_mano_obra, recargo_altura, recargo_urgencia, total, direccion_instalacion, referencia, latitud, longitud, preguntas_cliente, diagnostico_inicial, observaciones, estado_pago, estado_servicio)
			VALUES
			(:codigo, :id_cliente, :id_vendedor, :tipo_servicio, :tipo_instalacion, :cantidad_camaras, :metros_distancia, :metros_canalizacion, :precio_por_metro, :precio_canalizacion_metro, :precio_por_camara, :costo_visita, :costo_diagnostico, :costo_transporte, :costo_mano_obra, :recargo_altura, :recargo_urgencia, :total, :direccion_instalacion, :referencia, :latitud, :longitud, :preguntas_cliente, :diagnostico_inicial, :observaciones, :estado_pago, :estado_servicio)"
		);

		foreach($datos as $key => $value){
			$stmt->bindValue(":".$key, $value);
		}

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlMostrarServicios($item = null, $valor = null){
		$conexion = Conexion::conectar();
		self::mdlAsegurarColumnasConformidadCampo($conexion);

		if($item != null){
			$stmt = $conexion->prepare("SELECT * FROM servicios_ventas WHERE $item = :$item ORDER BY id DESC");
			$stmt->bindParam(":".$item, $valor, PDO::PARAM_STR);
			$stmt->execute();
			return $stmt->fetch(PDO::FETCH_ASSOC);
		}

		$stmt = $conexion->prepare("SELECT * FROM servicios_ventas ORDER BY fecha DESC, id DESC");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static private function mdlAsegurarTablaPagosServicio($conexion){
		$conexion->exec(
			"CREATE TABLE IF NOT EXISTS servicios_pagos (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_servicio INT NOT NULL,
				id_cajero INT NULL,
				tipo_pago VARCHAR(40) NOT NULL DEFAULT 'servicio',
				monto DECIMAL(12,2) NOT NULL DEFAULT 0,
				metodo_pago VARCHAR(80) NULL,
				cambio DECIMAL(12,2) NOT NULL DEFAULT 0,
				codigo_transaccion VARCHAR(120) NULL,
				saldo_antes DECIMAL(12,2) NOT NULL DEFAULT 0,
				saldo_despues DECIMAL(12,2) NOT NULL DEFAULT 0,
				fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_servicio_fecha (id_servicio, fecha)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci"
		);
	}

	static public function mdlRegistrarPagoServicio($datos){
		$conexion = Conexion::conectar();
		self::mdlAsegurarTablaPagosServicio($conexion);
		$stmt = $conexion->prepare(
			"INSERT INTO servicios_pagos
			 (id_servicio, id_cajero, tipo_pago, monto, metodo_pago, cambio, codigo_transaccion, saldo_antes, saldo_despues)
			 VALUES
			 (:id_servicio, :id_cajero, :tipo_pago, :monto, :metodo_pago, :cambio, :codigo_transaccion, :saldo_antes, :saldo_despues)"
		);
		foreach($datos as $key => $value){
			$stmt->bindValue(":".$key, $value);
		}
		return $stmt->execute() ? (int)$conexion->lastInsertId() : 0;
	}

	static public function mdlMostrarPagosServicio($idServicio){
		$conexion = Conexion::conectar();
		self::mdlAsegurarTablaPagosServicio($conexion);
		$stmt = $conexion->prepare(
			"SELECT p.*, u.nombre AS cajero
			 FROM servicios_pagos p
			 LEFT JOIN usuarios u ON u.id = p.id_cajero
			 WHERE p.id_servicio = :id_servicio
			 ORDER BY p.fecha DESC, p.id DESC"
		);
		$stmt->bindValue(":id_servicio", $idServicio, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarPagoServicio($idPago){
		$conexion = Conexion::conectar();
		self::mdlAsegurarTablaPagosServicio($conexion);
		$stmt = $conexion->prepare(
			"SELECT p.*, u.nombre AS cajero
			 FROM servicios_pagos p
			 LEFT JOIN usuarios u ON u.id = p.id_cajero
			 WHERE p.id = :id
			 LIMIT 1"
		);
		$stmt->bindValue(":id", $idPago, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlEliminarServicioCompleto($idServicio){
		try{
			$conexion = Conexion::conectar();
			$conexion->beginTransaction();

			self::mdlAsegurarTablaPagosServicio($conexion);
			$stmt = $conexion->prepare("SELECT COUNT(*) FROM servicios_pagos WHERE id_servicio = :id");
			$stmt->bindValue(":id", (int)$idServicio, PDO::PARAM_INT);
			$stmt->execute();
			if((int)$stmt->fetchColumn() > 0){
				$conexion->rollBack();
				return "con_movimientos";
			}

			$stmt = $conexion->prepare("DELETE FROM servicio_taller_repuestos WHERE id_servicio = :id");
			$stmt->bindParam(":id", $idServicio, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare("DELETE FROM servicio_taller_equipos WHERE id_servicio = :id");
			$stmt->bindParam(":id", $idServicio, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare(
				"DELETE FROM proyecto_software_avances
				 WHERE id_proyecto IN (SELECT id FROM proyectos_software WHERE id_servicio = :id)"
			);
			$stmt->bindValue(":id", (int)$idServicio, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare(
				"DELETE FROM proyecto_software_documentos
				 WHERE id_proyecto IN (SELECT id FROM proyectos_software WHERE id_servicio = :id)"
			);
			$stmt->bindValue(":id", (int)$idServicio, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare("DELETE FROM proyectos_software WHERE id_servicio = :id");
			$stmt->bindValue(":id", (int)$idServicio, PDO::PARAM_INT);
			$stmt->execute();

			$stmt = $conexion->prepare("DELETE FROM servicios_ventas WHERE id = :id");
			$stmt->bindParam(":id", $idServicio, PDO::PARAM_INT);
			$stmt->execute();

			$conexion->commit();
			return "ok";
		}catch(Exception $e){
			if(isset($conexion) && $conexion->inTransaction()){
				$conexion->rollBack();
			}
			return "error";
		}
	}

	static public function mdlMostrarServiciosPendientesPago(){
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM servicios_ventas
			 WHERE estado_pago = 'pendiente'
			    OR estado_pago IN ('pendiente_adelanto', 'adelanto_pagado', 'pendiente_final')
			    OR (tipo_servicio = 'Soporte tecnico en taller' AND estado_pago = 'pendiente_retiro' AND estado_servicio = 'listo_cobro')
			 ORDER BY fecha DESC, id DESC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarServiciosCobrados(){
		$stmt = Conexion::conectar()->prepare(
			"SELECT * FROM servicios_ventas
			 WHERE estado_pago IN ('aprobado', 'adelanto_pagado')
			 ORDER BY fecha_pago DESC, id DESC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarServiciosTecnico($idTecnico){
		$conexion = Conexion::conectar();
		self::mdlAsegurarColumnasConformidadCampo($conexion);
		$stmt = $conexion->prepare("SELECT * FROM servicios_ventas WHERE id_tecnico = :id_tecnico AND tipo_servicio <> 'Desarrollo de software' AND tipo_servicio NOT LIKE '%software%' AND (estado_pago = 'aprobado' OR tipo_servicio = 'Soporte tecnico en taller') ORDER BY fecha_pago DESC, id DESC");
		$stmt->bindParam(":id_tecnico", $idTecnico, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlBuscarTecnicoLibre(){
		$stmt = Conexion::conectar()->prepare(
			"SELECT u.id, u.nombre, COUNT(s.id) AS servicios_activos
			 FROM usuarios u
			 LEFT JOIN servicios_ventas s ON s.id_tecnico = u.id
			   AND (s.estado_pago = 'aprobado' OR s.tipo_servicio = 'Soporte tecnico en taller')
			   AND s.estado_servicio IN ('asignado', 'en_almacen', 'atendiendo', 'retiro_solicitado', 'en_proceso', 'diagnosticado', 'autorizado', 'rep_solicitado', 'rep_entregado', 'reparado')
			 WHERE u.rol = 'tecnico' AND u.estado = 1
			 GROUP BY u.id, u.nombre
			 ORDER BY servicios_activos ASC, u.id ASC
			 LIMIT 1"
		);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
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

	static public function mdlAprobarPagoServicio($datos){
		$servicio = self::mdlMostrarServicios("id", $datos["id"]);
		if($servicio && ($servicio["tipo_servicio"] ?? "") == "Desarrollo de software"){
			$estadoPago = ($servicio["estado_pago"] ?? "") == "pendiente_final" ? "aprobado" : ($datos["estado_pago"] ?? "adelanto_pagado");
			$estadoServicio = ($servicio["estado_pago"] ?? "") == "pendiente_final" ? "pagado_final" : ($datos["estado_servicio"] ?? "en_desarrollo");
			$stmt = Conexion::conectar()->prepare(
				"UPDATE servicios_ventas
				 SET estado_pago = :estado_pago,
				     id_tecnico = COALESCE(:id_tecnico, id_tecnico),
				     estado_servicio = :estado_servicio,
				     metodo_pago = :metodo_pago,
				     monto_recibido = :monto_recibido,
				     cambio = :cambio,
				     codigo_transaccion = :codigo_transaccion,
				     id_cajero = :id_cajero,
				     fecha_pago = NOW()
				 WHERE id = :id AND estado_pago IN ('pendiente_adelanto', 'adelanto_pagado', 'pendiente_final')"
			);
			$stmt->bindParam(":estado_pago", $estadoPago, PDO::PARAM_STR);
			$stmt->bindParam(":estado_servicio", $estadoServicio, PDO::PARAM_STR);
			$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
			$stmt->bindParam(":monto_recibido", $datos["monto_recibido"], PDO::PARAM_STR);
			$stmt->bindParam(":cambio", $datos["cambio"], PDO::PARAM_STR);
			$stmt->bindParam(":codigo_transaccion", $datos["codigo_transaccion"], PDO::PARAM_STR);
			$stmt->bindParam(":id_cajero", $datos["id_cajero"], PDO::PARAM_INT);
			if(empty($datos["id_tecnico"])){
				$stmt->bindValue(":id_tecnico", null, PDO::PARAM_NULL);
			}else{
				$stmt->bindValue(":id_tecnico", (int)$datos["id_tecnico"], PDO::PARAM_INT);
			}
			$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
			return $stmt->execute() ? "ok" : "error";
		}

		$stmt = Conexion::conectar()->prepare(
			"UPDATE servicios_ventas
			 SET estado_pago = 'aprobado',
			     id_tecnico = :id_tecnico,
			     estado_servicio = CASE WHEN tipo_servicio = 'Soporte tecnico en taller' THEN 'pagado_retiro' ELSE 'asignado' END,
			     metodo_pago = :metodo_pago,
			     monto_recibido = :monto_recibido,
			     cambio = :cambio,
			     codigo_transaccion = :codigo_transaccion,
			     id_cajero = :id_cajero,
			     fecha_pago = NOW()
			 WHERE id = :id AND estado_pago IN ('pendiente', 'pendiente_retiro')"
		);

		$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":monto_recibido", $datos["monto_recibido"], PDO::PARAM_STR);
		$stmt->bindParam(":cambio", $datos["cambio"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo_transaccion", $datos["codigo_transaccion"], PDO::PARAM_STR);
		$stmt->bindParam(":id_cajero", $datos["id_cajero"], PDO::PARAM_INT);
		$stmt->bindParam(":id_tecnico", $datos["id_tecnico"], PDO::PARAM_INT);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlAsignarTecnicoServicio($idServicio, $idTecnico, $estadoServicio = "asignado"){
		$stmt = Conexion::conectar()->prepare(
			"UPDATE servicios_ventas
			 SET id_tecnico = :id_tecnico,
			     estado_servicio = :estado_servicio
			 WHERE id = :id"
		);
		$stmt->bindParam(":id_tecnico", $idTecnico, PDO::PARAM_INT);
		$stmt->bindParam(":estado_servicio", $estadoServicio, PDO::PARAM_STR);
		$stmt->bindParam(":id", $idServicio, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlIngresarEquipoTaller($datos){
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO servicio_taller_equipos
			(id_servicio, codigo_equipo, tipo_equipo, marca, modelo, serie, accesorios, falla_reportada, estado_fisico, foto_equipo)
			VALUES
			(:id_servicio, :codigo_equipo, :tipo_equipo, :marca, :modelo, :serie, :accesorios, :falla_reportada, :estado_fisico, :foto_equipo)"
		);

		foreach($datos as $key => $value){
			$stmt->bindValue(":".$key, $value);
		}

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlMostrarEquiposTaller(){
		$stmt = Conexion::conectar()->prepare(
			"SELECT e.*, s.codigo AS codigo_servicio, s.id_cliente, s.id_vendedor, s.id_tecnico, s.estado_pago, s.estado_servicio, s.total, s.fecha
			 FROM servicio_taller_equipos e
			 INNER JOIN servicios_ventas s ON s.id = e.id_servicio
			 ORDER BY COALESCE(e.fecha_entrega_cliente, e.fecha_reingreso_almacen, e.fecha_retiro_tecnico, e.fecha_recepcion_almacen, e.fecha_registro) DESC, e.id DESC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarEquipoTaller($idServicio){
		$stmt = Conexion::conectar()->prepare("SELECT * FROM servicio_taller_equipos WHERE id_servicio = :id_servicio LIMIT 1");
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarRepuestosTaller($idServicio = null){
		if($idServicio !== null){
			$stmt = Conexion::conectar()->prepare(
				"SELECT r.*, p.codigo, p.descripcion, p.stock, p.codigo_producto_generico
				 FROM servicio_taller_repuestos r
				 INNER JOIN productos p ON p.id = r.id_producto
				 WHERE r.id_servicio = :id_servicio
				 ORDER BY r.id DESC"
			);
			$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		$stmt = Conexion::conectar()->prepare(
			"SELECT r.*, p.codigo, p.descripcion, p.stock, s.codigo AS codigo_servicio, s.id_cliente, s.id_tecnico, e.codigo_equipo, e.tipo_equipo, e.marca, e.modelo
			 FROM servicio_taller_repuestos r
			 INNER JOIN productos p ON p.id = r.id_producto
			 INNER JOIN servicios_ventas s ON s.id = r.id_servicio
			 LEFT JOIN servicio_taller_equipos e ON e.id_servicio = s.id
			 ORDER BY r.fecha_solicitud DESC, r.id DESC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlResolverServicioRepuestoTaller($referencia){
		$idReferencia = (int)$referencia;
		$codigoReferencia = trim((string)$referencia);
		$conexion = Conexion::conectar();

		$stmt = $conexion->prepare(
			"SELECT s.id, s.codigo
			 FROM servicio_taller_repuestos r
			 INNER JOIN servicios_ventas s ON s.id = r.id_servicio
			 WHERE r.id = :id_repuesto AND LOWER(TRIM(r.estado)) = 'solicitado'
			 LIMIT 1"
		);
		$stmt->bindParam(":id_repuesto", $idReferencia, PDO::PARAM_INT);
		$stmt->execute();
		$respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
		if($respuesta){
			return $respuesta;
		}

		$stmt = $conexion->prepare("SELECT id, codigo FROM servicios_ventas WHERE codigo = :codigo LIMIT 1");
		$stmt->bindParam(":codigo", $codigoReferencia, PDO::PARAM_STR);
		$stmt->execute();
		$respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
		if($respuesta){
			return $respuesta;
		}

		$stmt = $conexion->prepare("SELECT id, codigo FROM servicios_ventas WHERE id = :id LIMIT 1");
		$stmt->bindParam(":id", $idReferencia, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlGuardarRepuestosTaller($datos){
		try{
			$conexion = Conexion::conectar();
			$conexion->beginTransaction();

			$stmtCancelar = $conexion->prepare("UPDATE servicio_taller_repuestos SET estado = 'cancelado' WHERE id_servicio = :id_servicio AND estado = 'solicitado'");
			$stmtCancelar->bindParam(":id_servicio", $datos["id_servicio"], PDO::PARAM_INT);
			$stmtCancelar->execute();

			$stmtProducto = $conexion->prepare("SELECT id, precio_venta FROM productos WHERE id = :id AND stock > 0 LIMIT 1");
			$stmtInsertar = $conexion->prepare(
				"INSERT INTO servicio_taller_repuestos
				 (id_servicio, id_producto, cantidad, precio_unitario, subtotal, id_tecnico_solicita)
				 VALUES (:id_servicio, :id_producto, :cantidad, :precio_unitario, :subtotal, :id_tecnico)"
			);

			foreach($datos["productos"] as $productoSolicitado){
				$idProducto = (int)($productoSolicitado["id_producto"] ?? 0);
				$cantidad = max(1, (int)($productoSolicitado["cantidad"] ?? 1));

				$stmtProducto->bindParam(":id", $idProducto, PDO::PARAM_INT);
				$stmtProducto->execute();
				$producto = $stmtProducto->fetch(PDO::FETCH_ASSOC);

				if(!$producto){
					continue;
				}

				$precioUnitario = (float)$producto["precio_venta"];
				$subtotal = $precioUnitario * $cantidad;
				$stmtInsertar->bindParam(":id_servicio", $datos["id_servicio"], PDO::PARAM_INT);
				$stmtInsertar->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
				$stmtInsertar->bindParam(":cantidad", $cantidad, PDO::PARAM_INT);
				$stmtInsertar->bindParam(":precio_unitario", $precioUnitario);
				$stmtInsertar->bindParam(":subtotal", $subtotal);
				$stmtInsertar->bindParam(":id_tecnico", $datos["id_tecnico"], PDO::PARAM_INT);
				$stmtInsertar->execute();
			}

			$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'rep_solicitado' WHERE id = :id");
			$stmtServicio->bindParam(":id", $datos["id_servicio"], PDO::PARAM_INT);
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

	static public function mdlEntregarRepuestosTaller($datos){
		try{
			$conexion = Conexion::conectar();
			$conexion->beginTransaction();
			$idServicio = (int)($datos["id_servicio"] ?? 0);
			$referenciaServicio = trim((string)($datos["id_servicio"] ?? ""));

			$stmtResolverServicio = $conexion->prepare(
				"SELECT s.id, s.codigo
				 FROM servicio_taller_repuestos r
				 INNER JOIN servicios_ventas s ON s.id = r.id_servicio
				 WHERE r.id = :id_repuesto AND LOWER(TRIM(r.estado)) = 'solicitado'
				 LIMIT 1"
			);
			$stmtResolverServicio->bindParam(":id_repuesto", $idServicio, PDO::PARAM_INT);
			$stmtResolverServicio->execute();
			$servicioResuelto = $stmtResolverServicio->fetch(PDO::FETCH_ASSOC);

			if(!$servicioResuelto){
				$stmtResolverServicio = $conexion->prepare("SELECT id, codigo FROM servicios_ventas WHERE codigo = :codigo LIMIT 1");
				$stmtResolverServicio->bindParam(":codigo", $referenciaServicio, PDO::PARAM_STR);
				$stmtResolverServicio->execute();
				$servicioResuelto = $stmtResolverServicio->fetch(PDO::FETCH_ASSOC);
			}

			if(!$servicioResuelto){
				$stmtResolverServicio = $conexion->prepare("SELECT id, codigo FROM servicios_ventas WHERE id = :id LIMIT 1");
				$stmtResolverServicio->bindParam(":id", $idServicio, PDO::PARAM_INT);
				$stmtResolverServicio->execute();
				$servicioResuelto = $stmtResolverServicio->fetch(PDO::FETCH_ASSOC);
			}

			if($servicioResuelto && !empty($servicioResuelto["id"])){
				$idServicio = (int)$servicioResuelto["id"];
			}

			$stmtRepuestos = $conexion->prepare(
				"SELECT r.*, p.stock, s.id AS servicio_id
				 FROM servicio_taller_repuestos r
				 INNER JOIN productos p ON p.id = r.id_producto
				 INNER JOIN servicios_ventas s ON s.id = r.id_servicio
				 WHERE r.id_servicio = :id_servicio
				   AND LOWER(TRIM(r.estado)) = 'solicitado'
				 FOR UPDATE"
			);
			$stmtRepuestos->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);
			$stmtRepuestos->execute();
			$repuestos = $stmtRepuestos->fetchAll(PDO::FETCH_ASSOC);

			if(!$repuestos || count($repuestos) == 0){
				$conexion->rollBack();
				return "error";
			}
			$idServicio = (int)$repuestos[0]["servicio_id"];

			$codigosPorProducto = $datos["codigos_por_producto"] ?? array();
			$stmtCodigosDisponibles = $conexion->prepare(
				"SELECT id
				 FROM productos_detalle
				 WHERE id_producto = :id_producto AND LOWER(TRIM(estado)) = 'disponible'
				 FOR UPDATE"
			);
			$stmtDetalle = $conexion->prepare(
				"SELECT id, id_producto, codigo_barras_unico, estado
				 FROM productos_detalle
				 WHERE LOWER(TRIM(codigo_barras_unico)) = LOWER(:codigo)
				 FOR UPDATE"
			);
			$stmtMarcarDetalle = $conexion->prepare("UPDATE productos_detalle SET estado = 'vendido' WHERE id = :id");
			$stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - :cantidad_stock, ventas = ventas + :cantidad_ventas WHERE id = :id AND stock >= :cantidad_minima");
			$stmtEntregar = $conexion->prepare(
				"UPDATE servicio_taller_repuestos
				 SET estado = 'entregado',
				     id_almacenero_entrega = :id_almacenero,
				     fecha_entrega = NOW(),
				     observacion_entrega = :observacion,
				     codigos_entregados = :codigos_entregados
				 WHERE id = :id"
			);

			foreach($repuestos as $repuesto){
				$cantidad = (int)$repuesto["cantidad"];
				$idProducto = (int)$repuesto["id_producto"];
				$codigos = $codigosPorProducto[$idProducto] ?? array();
				if(!is_array($codigos)){
					$codigos = array();
				}
				$codigos = array_values(array_filter(array_map("trim", $codigos), function($codigo){
					return $codigo !== "";
				}));

				if(count($codigos) !== $cantidad){
					$conexion->rollBack();
					return "codigos_invalidos";
				}

				$stmtCodigosDisponibles->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
				$stmtCodigosDisponibles->execute();
				$codigosDisponibles = $stmtCodigosDisponibles->fetchAll(PDO::FETCH_ASSOC);
				if(count($codigosDisponibles) < $cantidad){
					$conexion->rollBack();
					return "sin_codigos_disponibles";
				}

				$vistos = array();
				foreach($codigos as $codigo){
					$codigo = preg_replace('/\s+/', ' ', trim((string)$codigo));
					$llave = function_exists("mb_strtolower") ? mb_strtolower($codigo, "UTF-8") : strtolower($codigo);
					if(isset($vistos[$llave])){
						$conexion->rollBack();
						return "codigos_invalidos";
					}
					$vistos[$llave] = true;

					$stmtDetalle->bindParam(":codigo", $codigo, PDO::PARAM_STR);
					$stmtDetalle->execute();
					$detalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);
					if(!$detalle){
						$conexion->rollBack();
						return "codigo_no_existe";
					}
					if((int)$detalle["id_producto"] !== $idProducto){
						$conexion->rollBack();
						return "codigo_no_pertenece";
					}
					if(strtolower(trim((string)($detalle["estado"] ?? ""))) !== "disponible"){
						$conexion->rollBack();
						return "codigo_no_disponible";
					}

					$stmtMarcarDetalle->bindParam(":id", $detalle["id"], PDO::PARAM_INT);
					$stmtMarcarDetalle->execute();
				}

				$stmtStock->bindValue(":cantidad_stock", $cantidad, PDO::PARAM_INT);
				$stmtStock->bindValue(":cantidad_ventas", $cantidad, PDO::PARAM_INT);
				$stmtStock->bindValue(":cantidad_minima", $cantidad, PDO::PARAM_INT);
				$stmtStock->bindValue(":id", $idProducto, PDO::PARAM_INT);
				$stmtStock->execute();

				if($stmtStock->rowCount() == 0){
					$conexion->rollBack();
					return "stock_insuficiente";
				}

				$stmtEntregar->bindParam(":id_almacenero", $datos["id_almacenero"], PDO::PARAM_INT);
				$stmtEntregar->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
				$codigosJson = json_encode($codigos);
				$stmtEntregar->bindParam(":codigos_entregados", $codigosJson, PDO::PARAM_STR);
				$stmtEntregar->bindParam(":id", $repuesto["id"], PDO::PARAM_INT);
				$stmtEntregar->execute();
			}

			$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'rep_entregado' WHERE id = :id");
			$stmtServicio->bindParam(":id", $idServicio, PDO::PARAM_INT);
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

	static public function mdlRecepcionarEquipoAlmacen($idServicio, $idAlmacenero){
		$conexion = Conexion::conectar();
		$stmt = $conexion->prepare(
			"UPDATE servicio_taller_equipos
			 SET estado_equipo = 'recibido_almacen',
			     id_almacenero_recepcion = :id_almacenero,
			     fecha_recepcion_almacen = NOW()
			 WHERE id_servicio = :id_servicio AND estado_equipo = 'ingresado'"
		);
		$stmt->bindParam(":id_almacenero", $idAlmacenero, PDO::PARAM_INT);
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);

		if(!$stmt->execute() || $stmt->rowCount() == 0){
			return "error";
		}

		$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'en_almacen' WHERE id = :id");
		$stmtServicio->bindParam(":id", $idServicio, PDO::PARAM_INT);
		return $stmtServicio->execute() ? "ok" : "error";
	}

	static public function mdlSolicitarRetiroEquipoTecnico($idServicio, $idTecnico){
		$conexion = Conexion::conectar();
		$stmt = $conexion->prepare(
			"UPDATE servicio_taller_equipos
			 SET estado_equipo = 'retiro_solicitado',
			     id_tecnico_retiro = :id_tecnico
			 WHERE id_servicio = :id_servicio AND estado_equipo = 'recibido_almacen'"
		);
		$stmt->bindParam(":id_tecnico", $idTecnico, PDO::PARAM_INT);
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);

		if(!$stmt->execute() || $stmt->rowCount() == 0){
			return "error";
		}

		$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'retiro_solicitado' WHERE id = :id");
		$stmtServicio->bindParam(":id", $idServicio, PDO::PARAM_INT);
		return $stmtServicio->execute() ? "ok" : "error";
	}

	static public function mdlRetirarEquipoTecnico($idServicio, $idTecnico, $idAlmacenero = null){
		$conexion = Conexion::conectar();
		$stmt = $conexion->prepare(
			"UPDATE servicio_taller_equipos
			 SET estado_equipo = 'retirado_tecnico',
			     id_tecnico_retiro = :id_tecnico,
			     id_almacenero_retiro = :id_almacenero,
			     fecha_retiro_tecnico = NOW()
			 WHERE id_servicio = :id_servicio
			   AND (
			     estado_equipo IN ('recibido_almacen', 'retiro_solicitado')
			     OR (estado_equipo = 'retirado_tecnico' AND id_almacenero_retiro IS NULL)
			   )"
		);
		$stmt->bindParam(":id_tecnico", $idTecnico, PDO::PARAM_INT);
		$stmt->bindParam(":id_almacenero", $idAlmacenero, PDO::PARAM_INT);
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);

		if(!$stmt->execute() || $stmt->rowCount() == 0){
			return "error";
		}

		$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'en_proceso' WHERE id = :id");
		$stmtServicio->bindParam(":id", $idServicio, PDO::PARAM_INT);
		return $stmtServicio->execute() ? "ok" : "error";
	}

	static public function mdlReingresarEquipoAlmacen($datos){
		$conexion = Conexion::conectar();
		$equipo = self::mdlMostrarEquipoTaller($datos["id_servicio"]);
		$servicio = self::mdlMostrarServicios("id", $datos["id_servicio"]);
		$estadoServicio = (($equipo["respuesta_cliente"] ?? "") == "no_conforme") ? "devolucion_pend" : "listo_cobro";

		$stmt = $conexion->prepare(
			"UPDATE servicio_taller_equipos
			 SET estado_equipo = 'devuelto_almacen',
			     id_almacenero_reingreso = :id_almacenero,
			     fecha_reingreso_almacen = NOW(),
			     observacion_reingreso = :observacion
			 WHERE id_servicio = :id_servicio
			   AND estado_equipo = 'pendiente_reingreso'"
		);
		$stmt->bindParam(":id_almacenero", $datos["id_almacenero"], PDO::PARAM_INT);
		$stmt->bindParam(":observacion", $datos["observacion"], PDO::PARAM_STR);
		$stmt->bindParam(":id_servicio", $datos["id_servicio"], PDO::PARAM_INT);

		if(!$servicio || !$stmt->execute() || $stmt->rowCount() == 0){
			return "error";
		}

		$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = :estado WHERE id = :id");
		$stmtServicio->bindParam(":estado", $estadoServicio, PDO::PARAM_STR);
		$stmtServicio->bindParam(":id", $datos["id_servicio"], PDO::PARAM_INT);
		return $stmtServicio->execute() ? "ok" : "error";
	}

	static public function mdlEnviarEquipoAAlmacenTecnico($idServicio){
		$conexion = Conexion::conectar();
		$stmt = $conexion->prepare(
			"UPDATE servicio_taller_equipos
			 SET estado_equipo = 'pendiente_reingreso'
			 WHERE id_servicio = :id_servicio AND estado_equipo IN ('reparado', 'rechazado')"
		);
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);

		if(!$stmt->execute() || $stmt->rowCount() == 0){
			return "error";
		}

		$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'retorno_almacen' WHERE id = :id");
		$stmtServicio->bindParam(":id", $idServicio, PDO::PARAM_INT);
		return $stmtServicio->execute() ? "ok" : "error";
	}

	static public function mdlEntregarEquipoCliente($idServicio, $idAlmacenero){
		$conexion = Conexion::conectar();
		$stmt = $conexion->prepare(
			"UPDATE servicio_taller_equipos
			 SET estado_equipo = 'entregado_cliente',
			     id_almacenero_entrega_cliente = :id_almacenero,
			     fecha_entrega_cliente = NOW()
			 WHERE id_servicio = :id_servicio AND estado_equipo = 'devuelto_almacen'"
		);
		$stmt->bindParam(":id_servicio", $idServicio, PDO::PARAM_INT);
		$stmt->bindParam(":id_almacenero", $idAlmacenero, PDO::PARAM_INT);

		if(!$stmt->execute() || $stmt->rowCount() == 0){
			return "error";
		}

		$stmtServicio = $conexion->prepare("UPDATE servicios_ventas SET estado_servicio = 'completado' WHERE id = :id AND estado_pago = 'aprobado'");
		$stmtServicio->bindParam(":id", $idServicio, PDO::PARAM_INT);
		return $stmtServicio->execute() ? "ok" : "error";
	}

	static private function mdlAsegurarColumnasTaller($conexion){
		try{
			$conexion->exec("ALTER TABLE servicio_taller_equipos ADD COLUMN evidencias_tecnicas TEXT NULL");
		}catch(Exception $e){
			if(stripos($e->getMessage(), "Duplicate") === false && stripos($e->getMessage(), "existe") === false){
				throw $e;
			}
		}
	}

	static public function mdlGuardarDiagnosticoTaller($datos){
		$conexion = Conexion::conectar();
		self::mdlAsegurarColumnasTaller($conexion);
		$stmt = $conexion->prepare(
			"UPDATE servicio_taller_equipos
			 SET diagnostico_tecnico = :diagnostico_tecnico,
			     fecha_diagnostico = NOW(),
			     notificado_cliente = :notificado_cliente,
			     fecha_notificacion = CASE WHEN :notificado_cliente_fecha = 1 THEN NOW() ELSE fecha_notificacion END,
			     respuesta_cliente = :respuesta_cliente,
			     detalle_notificacion = :detalle_notificacion,
			     reparacion_realizada = :reparacion_realizada,
			     repuestos_detalle = :repuestos_detalle,
			     garantia_detalle = :garantia_detalle,
			     evidencias_tecnicas = :evidencias_tecnicas,
			     fecha_reparacion = CASE WHEN :respuesta_cliente_fecha = 'conforme' AND :reparacion_realizada_fecha <> '' THEN NOW() ELSE fecha_reparacion END,
			     estado_equipo = :estado_equipo
			 WHERE id_servicio = :id_servicio"
		);

		foreach($datos as $key => $value){
			$stmt->bindValue(":".$key, $value);
		}

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlCambiarEstadoServicio($id, $estado){
		$stmt = Conexion::conectar()->prepare("UPDATE servicios_ventas SET estado_servicio = :estado WHERE id = :id");
		$stmt->bindParam(":estado", $estado, PDO::PARAM_STR);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlActualizarTotalServicio($id, $total){
		$stmt = Conexion::conectar()->prepare("UPDATE servicios_ventas SET total = :total WHERE id = :id");
		$stmt->bindParam(":total", $total);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlActualizarCostosTallerServicio($id, $manoObra, $total){
		$stmt = Conexion::conectar()->prepare("UPDATE servicios_ventas SET costo_mano_obra = :mano_obra, total = :total WHERE id = :id AND tipo_servicio = 'Soporte tecnico en taller'");
		$stmt->bindParam(":mano_obra", $manoObra);
		$stmt->bindParam(":total", $total);
		$stmt->bindParam(":id", $id, PDO::PARAM_INT);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlGuardarInformeTecnico($datos){
		$conexion = Conexion::conectar();
		self::mdlAsegurarColumnasConformidadCampo($conexion);
		$stmt = $conexion->prepare(
			"UPDATE servicios_ventas
			 SET hallazgos_tecnicos = :hallazgos_tecnicos,
			     trabajo_realizado = :trabajo_realizado,
			     recomendaciones = :recomendaciones,
			     boleta_conformidad_archivo = :boleta_conformidad_archivo,
			     boleta_conformidad_fecha = NOW(),
			     estado_servicio = :estado_servicio
			 WHERE id = :id"
		);

		$stmt->bindParam(":hallazgos_tecnicos", $datos["hallazgos_tecnicos"], PDO::PARAM_STR);
		$stmt->bindParam(":trabajo_realizado", $datos["trabajo_realizado"], PDO::PARAM_STR);
		$stmt->bindParam(":recomendaciones", $datos["recomendaciones"], PDO::PARAM_STR);
		$stmt->bindParam(":boleta_conformidad_archivo", $datos["boleta_conformidad_archivo"], PDO::PARAM_STR);
		$stmt->bindParam(":estado_servicio", $datos["estado_servicio"], PDO::PARAM_STR);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlMostrarPrecios(){
		$stmt = Conexion::conectar()->prepare("SELECT * FROM servicios_precios ORDER BY tipo_servicio ASC, tipo_instalacion ASC");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarPrecioServicio($tipoServicio, $tipoInstalacion){
		$stmt = Conexion::conectar()->prepare("SELECT * FROM servicios_precios WHERE tipo_servicio = :tipo_servicio AND tipo_instalacion = :tipo_instalacion AND estado = 1 LIMIT 1");
		$stmt->bindParam(":tipo_servicio", $tipoServicio, PDO::PARAM_STR);
		$stmt->bindParam(":tipo_instalacion", $tipoInstalacion, PDO::PARAM_STR);
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlGuardarPrecioServicio($datos){
		$conexion = Conexion::conectar();
		$stmt = $conexion->prepare("SELECT id FROM servicios_precios WHERE tipo_servicio = :tipo_servicio AND tipo_instalacion = :tipo_instalacion LIMIT 1");
		$stmt->bindParam(":tipo_servicio", $datos["tipo_servicio"], PDO::PARAM_STR);
		$stmt->bindParam(":tipo_instalacion", $datos["tipo_instalacion"], PDO::PARAM_STR);
		$stmt->execute();
		$actual = $stmt->fetch(PDO::FETCH_ASSOC);

		if($actual){
			$sql = "UPDATE servicios_precios SET precio_por_metro=:precio_por_metro, precio_canalizacion_metro=:precio_canalizacion_metro, mano_obra_base=:mano_obra_base, precio_por_camara=:precio_por_camara, costo_visita=:costo_visita, costo_diagnostico=:costo_diagnostico, recargo_altura=:recargo_altura, recargo_urgencia=:recargo_urgencia, costo_transporte=:costo_transporte, estado=:estado WHERE id=:id";
			$stmt = $conexion->prepare($sql);
			$stmt->bindValue(":id", $actual["id"], PDO::PARAM_INT);
		}else{
			$sql = "INSERT INTO servicios_precios(tipo_servicio, tipo_instalacion, precio_por_metro, precio_canalizacion_metro, mano_obra_base, precio_por_camara, costo_visita, costo_diagnostico, recargo_altura, recargo_urgencia, costo_transporte, estado) VALUES (:tipo_servicio, :tipo_instalacion, :precio_por_metro, :precio_canalizacion_metro, :mano_obra_base, :precio_por_camara, :costo_visita, :costo_diagnostico, :recargo_altura, :recargo_urgencia, :costo_transporte, :estado)";
			$stmt = $conexion->prepare($sql);
			$stmt->bindValue(":tipo_servicio", $datos["tipo_servicio"], PDO::PARAM_STR);
			$stmt->bindValue(":tipo_instalacion", $datos["tipo_instalacion"], PDO::PARAM_STR);
		}

		foreach(array("precio_por_metro","precio_canalizacion_metro","mano_obra_base","precio_por_camara","costo_visita","costo_diagnostico","recargo_altura","recargo_urgencia","costo_transporte") as $campo){
			$stmt->bindValue(":".$campo, $datos[$campo]);
		}
		$stmt->bindValue(":estado", $datos["estado"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}
}
