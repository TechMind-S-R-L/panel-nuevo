<?php

require_once "conexion.php";

class ModeloCompras{

	/*=============================================
	MOSTRAR VENTAS
	=============================================*/

static public function mdlMostrarCompras($tabla, $item, $valor) {

    if ($item != null) {

        $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item ORDER BY fecha DESC");
        $stmt->bindParam(":".$item, $valor, PDO::PARAM_STR);
        $stmt->execute();

        $resultado = $stmt->fetch();

    } else {

        $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY fecha DESC");
        $stmt->execute();

        $resultado = $stmt->fetchAll();
    }

    $stmt = null; // Cerramos la conexión
    return $resultado;
}

static public function mdlSiguienteCodigoCompra() {
	$stmt = Conexion::conectar()->prepare("SELECT COALESCE(MAX(codigo), 10000) + 1 AS siguiente FROM compra");
	$stmt->execute();
	$respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
	return (int)($respuesta["siguiente"] ?? 10001);
}

static public function mdlMostrarDetalleCompras($idCompra) {
	$stmt = Conexion::conectar()->prepare("SELECT * FROM detalle_compra WHERE id_compra = :id_compra");
	$stmt->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);
	$stmt->execute();

	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


	/*=============================================
	REGISTRO DE COMPRA
	=============================================*/

	static public function mdlIngresarCompras($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(codigo, id_proveedor, id_usuario, productos, total, estado) VALUES (:codigo, :id_proveedor, :id_usuario, :productos, :total, :estado)");
	
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":id_proveedor", $datos["id_proveedor"], PDO::PARAM_INT);
		$stmt->bindParam(":productos", $datos["productos"], PDO::PARAM_STR);
		$stmt->bindParam(":id_usuario", $datos["id_usuario"], PDO::PARAM_INT);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR); // Nuevo parámetro para el estado
	
		if($stmt->execute()){
	
			return "ok";
	
		} else {
	
			return "error";
		
		}
	
		$stmt->close();
		$stmt = null;
	}
	


	static public function mdlActualizarProducto($tabla, $item, $valor, $idProducto) {
		try {
			$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET $item = :$item WHERE id = :id");
			$stmt->bindParam(":$item", $valor, PDO::PARAM_INT);
			$stmt->bindParam(":id", $idProducto, PDO::PARAM_INT);
	
			if ($stmt->execute()) {
				return "ok";
			} else {
				return "error";
			}
		} catch (Exception $e) {
			error_log("Error al actualizar el producto: " . $e->getMessage());
			return "error: " . $e->getMessage();
		} finally {
			$stmt = null;
		}
	}
	

	/*=============================================
	EDITAR Compra
	=============================================*/

	static public function mdlEditarCompra($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET id_proveedor = :id_proveedor, id_vendedor = :id_vendedor, productos = :productos, descuento = :descuento, neto = :neto, total= :total, metodo_pago = :metodo_pago, estado = :estado WHERE codigo = :codigo");
	
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":id_proveedor", $datos["id_proveedor"], PDO::PARAM_INT);
		$stmt->bindParam(":id_vendedor", $datos["id_vendedor"], PDO::PARAM_INT);
		$stmt->bindParam(":productos", $datos["productos"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento", $datos["descuento"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR); // Nuevo parámetro para el estado
	
		if($stmt->execute()){
	
			return "ok";
	
		} else {
	
			return "error";
		
		}
	
		$stmt->close();
		$stmt = null;
	}
	
	// static public function mdlCambiarEstadoCompra($tabla, $idCompra, $nuevoEstado) {
	// 	$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET estado = :estado WHERE id = :id");
	// 	$stmt->bindParam(":estado", $nuevoEstado, PDO::PARAM_STR);
	// 	$stmt->bindParam(":id", $idCompra, PDO::PARAM_INT);
	
	// 	if ($stmt->execute()) {
	// 		return "ok";
	// 	} else {
	// 		error_log("Error SQL: " . $stmt->errorInfo()[2]); // Para depuración
	// 		return "error";
	// 	}
	// }

	static public function mdlCambiarEstadoCompra($tabla, $idCompra, $nuevoEstado) {
		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET estado = :estado WHERE id = :id");
		$stmt->bindParam(":estado", $nuevoEstado, PDO::PARAM_STR);
		$stmt->bindParam(":id", $idCompra, PDO::PARAM_INT);
	
		if ($stmt->execute()) {
			return "ok";
		} else {
			return "error";
		}
	
		$stmt->close();
		$stmt = null;
	}

	static public function mdlActualizarEstadoCompra($idCompra, $nuevoEstado) {
		return self::mdlCambiarEstadoCompra("compra", $idCompra, $nuevoEstado);
	}

	static public function mdlTomarSolicitudMensajero($idCompra, $idMensajero) {
		$stmt = Conexion::conectar()->prepare(
			"UPDATE compra
			 SET estado = 'en_compra',
			     id_mensajero = :id_mensajero,
			     fecha_toma_mensajero = NOW()
			 WHERE id = :id
			   AND estado = 'aprobado'
			   AND (id_mensajero IS NULL OR id_mensajero = 0)"
		);
		$stmt->bindParam(":id", $idCompra, PDO::PARAM_INT);
		$stmt->bindParam(":id_mensajero", $idMensajero, PDO::PARAM_INT);

		return $stmt->execute() && $stmt->rowCount() > 0 ? "ok" : "error";
	}

	static public function mdlRegistrarDesembolsoMensajero($idCompra, $idCajero, $monto) {
		$stmt = Conexion::conectar()->prepare(
			"UPDATE compra
			 SET estado = 'desembolsado',
			     id_cajero_desembolso = :id_cajero,
			     fecha_desembolso = NOW(),
			     monto_desembolsado = :monto
			 WHERE id = :id
			   AND estado = 'en_compra'
			   AND id_mensajero IS NOT NULL"
		);
		$stmt->bindParam(":id", $idCompra, PDO::PARAM_INT);
		$stmt->bindParam(":id_cajero", $idCajero, PDO::PARAM_INT);
		$stmt->bindParam(":monto", $monto);

		return $stmt->execute() && $stmt->rowCount() > 0 ? "ok" : "error";
	}

	static public function mdlConfirmarEntregaAlmacen($idCompra, $idAlmacenero) {
		$stmt = Conexion::conectar()->prepare(
			"UPDATE compra
			 SET estado = 'entregado_almacen',
			     id_almacenero_entrega = :id_almacenero,
			     fecha_entrega_almacen = NOW()
			 WHERE id = :id
			   AND estado = 'compra_rendida'
			   AND id_mensajero IS NOT NULL"
		);
		$stmt->bindParam(":id", $idCompra, PDO::PARAM_INT);
		$stmt->bindParam(":id_almacenero", $idAlmacenero, PDO::PARAM_INT);

		return $stmt->execute() && $stmt->rowCount() > 0 ? "ok" : "error";
	}

	static public function mdlRegistrarRendicionMensajero($idCompra, $idMensajero, $detalles, $factura, $numeroFactura, $observacion) {
		$conexion = Conexion::conectar();
		try {
			$conexion->beginTransaction();

			$stmtCompra = $conexion->prepare(
				"SELECT id, productos, monto_desembolsado
				 FROM compra
				 WHERE id = :id AND estado = 'desembolsado' AND id_mensajero = :id_mensajero
				 FOR UPDATE"
			);
			$stmtCompra->bindValue(":id", (int)$idCompra, PDO::PARAM_INT);
			$stmtCompra->bindValue(":id_mensajero", (int)$idMensajero, PDO::PARAM_INT);
			$stmtCompra->execute();
			$compra = $stmtCompra->fetch(PDO::FETCH_ASSOC);
			if (!$compra) {
				throw new Exception("La compra no esta disponible para rendicion.");
			}

			$solicitados = json_decode($compra["productos"], true);
			if (!is_array($solicitados) || !is_array($detalles)) {
				throw new Exception("El detalle de productos no es valido.");
			}

			$porProducto = array();
			foreach ($detalles as $detalle) {
				$porProducto[(int)($detalle["id_producto"] ?? 0)] = $detalle;
			}

			$stmtBorrar = $conexion->prepare("DELETE FROM compra_rendicion_detalle WHERE id_compra = :id_compra");
			$stmtBorrar->execute(array(":id_compra" => (int)$idCompra));
			$stmtInsertar = $conexion->prepare(
				"INSERT INTO compra_rendicion_detalle
				 (id_compra, id_producto, cantidad, costo_unitario, subtotal)
				 VALUES (:id_compra, :id_producto, :cantidad, :costo_unitario, :subtotal)"
			);

			$totalReal = 0;
			foreach ($solicitados as $producto) {
				$idProducto = (int)($producto["id"] ?? 0);
				$cantidad = (int)($producto["cantidad"] ?? 0);
				$costo = isset($porProducto[$idProducto]) ? (float)($porProducto[$idProducto]["costo_unitario"] ?? 0) : 0;
				if ($idProducto <= 0 || $cantidad <= 0 || $costo <= 0) {
					throw new Exception("Debe registrar el costo real de todos los productos.");
				}
				$subtotal = round($cantidad * $costo, 2);
				$totalReal += $subtotal;
				$stmtInsertar->execute(array(
					":id_compra" => (int)$idCompra,
					":id_producto" => $idProducto,
					":cantidad" => $cantidad,
					":costo_unitario" => $costo,
					":subtotal" => $subtotal
				));
			}

			$totalReal = round($totalReal, 2);
			$desembolsado = round((float)$compra["monto_desembolsado"], 2);
			if ($totalReal > $desembolsado + 0.001) {
				throw new Exception("El costo real supera el monto desembolsado. Caja debe autorizar un monto adicional.");
			}
			$cambio = round($desembolsado - $totalReal, 2);

			$stmtActualizar = $conexion->prepare(
				"UPDATE compra
				 SET estado = 'rendicion_pendiente',
				     factura_compra = :factura,
				     numero_factura = :numero_factura,
				     costo_real_total = :costo_real_total,
				     cambio_calculado = :cambio,
				     observacion_rendicion = :observacion,
				     id_mensajero_rendicion = :id_mensajero,
				     fecha_rendicion = NOW()
				 WHERE id = :id"
			);
			$stmtActualizar->execute(array(
				":factura" => $factura,
				":numero_factura" => trim((string)$numeroFactura),
				":costo_real_total" => $totalReal,
				":cambio" => $cambio,
				":observacion" => trim((string)$observacion),
				":id_mensajero" => (int)$idMensajero,
				":id" => (int)$idCompra
			));

			$conexion->commit();
			return array("status" => "ok", "total" => $totalReal, "cambio" => $cambio);
		} catch (Throwable $e) {
			if ($conexion->inTransaction()) $conexion->rollBack();
			return array("status" => "error", "message" => $e->getMessage());
		}
	}

	static public function mdlConfirmarRendicionCaja($idCompra, $idCajero, $idApertura) {
		$conexion = Conexion::conectar();
		try {
			$conexion->beginTransaction();
			$stmtCompra = $conexion->prepare(
				"SELECT id, codigo, cambio_calculado
				 FROM compra WHERE id = :id AND estado = 'rendicion_pendiente' FOR UPDATE"
			);
			$stmtCompra->execute(array(":id" => (int)$idCompra));
			$compra = $stmtCompra->fetch(PDO::FETCH_ASSOC);
			if (!$compra) throw new Exception("La rendicion ya fue procesada o no existe.");

			$cambio = round((float)$compra["cambio_calculado"], 2);
			if ($cambio > 0) {
				$stmtCaja = $conexion->prepare(
					"SELECT id FROM caja_aperturas
					 WHERE id = :id AND estado = 'abierta' FOR UPDATE"
				);
				$stmtCaja->execute(array(":id" => (int)$idApertura));
				if (!$stmtCaja->fetch()) throw new Exception("Debe existir una caja abierta.");

				$stmtMovimiento = $conexion->prepare(
					"INSERT INTO caja_movimientos
					 (id_apertura,id_usuario,tipo,origen,referencia_tipo,id_referencia,
					  codigo_referencia,metodo_pago,monto,afecta_efectivo,descripcion)
					 VALUES
					 (:id_apertura,:id_usuario,'ingreso','devolucion_compra','compra',:id_referencia,
					  :codigo,'Efectivo',:monto,1,:descripcion)"
				);
				$stmtMovimiento->execute(array(
					":id_apertura" => (int)$idApertura,
					":id_usuario" => (int)$idCajero,
					":id_referencia" => (int)$idCompra,
					":codigo" => (string)$compra["codigo"],
					":monto" => $cambio,
					":descripcion" => "Devolucion de cambio de compra #".(int)$idCompra
				));
			}

			$stmtActualizar = $conexion->prepare(
				"UPDATE compra
				 SET estado='compra_rendida',
				     id_cajero_rendicion=:id_cajero,
				     fecha_confirmacion_rendicion=NOW()
				 WHERE id=:id"
			);
			$stmtActualizar->execute(array(":id_cajero" => (int)$idCajero, ":id" => (int)$idCompra));
			$conexion->commit();
			return "ok";
		} catch (Throwable $e) {
			if ($conexion->inTransaction()) $conexion->rollBack();
			error_log("Error confirmando rendicion de compra: ".$e->getMessage());
			return $e->getMessage() === "Debe existir una caja abierta." ? "sin_apertura" : "error";
		}
	}

	static public function mdlDetalleRendicion($idCompra) {
		$stmt = Conexion::conectar()->prepare(
			"SELECT d.*, p.codigo, p.descripcion
			 FROM compra_rendicion_detalle d
			 INNER JOIN productos p ON p.id=d.id_producto
			 WHERE d.id_compra=:id_compra ORDER BY d.id"
		);
		$stmt->execute(array(":id_compra" => (int)$idCompra));
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlVerificarCompraCompleta($idCompra) {
		$stmt = Conexion::conectar()->prepare("SELECT productos FROM compra WHERE id = :id");
		$stmt->bindParam(":id", $idCompra, PDO::PARAM_INT);
		$stmt->execute();
		$compra = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$compra || empty($compra["productos"])) {
			return false;
		}

		$productos = json_decode($compra["productos"], true);
		if (!is_array($productos)) {
			return false;
		}

		foreach ($productos as $producto) {
			$idProducto = $producto["id"] ?? null;
			$cantidadEsperada = (int)($producto["cantidad"] ?? 0);

			if (!$idProducto || $cantidadEsperada <= 0) {
				return false;
			}

			$stmtDetalle = Conexion::conectar()->prepare(
				"SELECT COALESCE(SUM(unidades), 0) AS total
				 FROM orden_ingreso_materiales
				 WHERE id_compra = :id_compra AND id_producto = :id_producto"
			);
			$stmtDetalle->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);
			$stmtDetalle->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
			$stmtDetalle->execute();
			$detalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);

			if ((int)($detalle["total"] ?? 0) < $cantidadEsperada) {
				return false;
			}
		}

		return true;
	}
	
	
	
	/*=============================================
	ELIMINAR VENTA
	=============================================*/

	static public function mdlEliminarCompra($tabla, $datos){
		if($tabla !== "compra"){
			return "error";
		}

		$conexion = Conexion::conectar();
		try{
			$conexion->beginTransaction();

			$stmtCompra = $conexion->prepare("SELECT id, estado FROM compra WHERE id = :id FOR UPDATE");
			$stmtCompra->bindValue(":id", (int)$datos, PDO::PARAM_INT);
			$stmtCompra->execute();
			$compraActual = $stmtCompra->fetch(PDO::FETCH_ASSOC);
			if(!$compraActual){
				$conexion->rollBack();
				return "no_existe";
			}
			if(!in_array(trim((string)$compraActual["estado"]), array("pendiente", "rechazado"), true)){
				$conexion->rollBack();
				return "con_flujo";
			}

			$stmtIngresos = $conexion->prepare(
				"SELECT COUNT(*) AS total
				 FROM orden_ingreso_materiales
				 WHERE id_compra = :id_compra"
			);
			$stmtIngresos->bindValue(":id_compra", (int)$datos, PDO::PARAM_INT);
			$stmtIngresos->execute();
			if((int)$stmtIngresos->fetch(PDO::FETCH_ASSOC)["total"] > 0){
				$conexion->rollBack();
				return "con_ingresos";
			}

			$stmtDetalle = $conexion->prepare("DELETE FROM detalle_compra WHERE id_compra = :id_compra");
			$stmtDetalle->bindValue(":id_compra", (int)$datos, PDO::PARAM_INT);
			$stmtDetalle->execute();

			$stmtCompra = $conexion->prepare("DELETE FROM compra WHERE id = :id");
			$stmtCompra->bindValue(":id", (int)$datos, PDO::PARAM_INT);
			$stmtCompra->execute();

			$conexion->commit();
			return "ok";
		}catch(Throwable $e){
			if($conexion->inTransaction()){
				$conexion->rollBack();
			}
			error_log("Error al eliminar solicitud de compra ".(int)$datos.": ".$e->getMessage());
			return "error";
		}

	}

	/*=============================================
	RANGO FECHAS
	=============================================*/	

	static public function mdlRangoFechasCompras($tabla, $fechaInicial, $fechaFinal){

		if($fechaInicial == null){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY id ASC");

			$stmt -> execute();

			return $stmt -> fetchAll();	


		}else if($fechaInicial == $fechaFinal){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE fecha like '%$fechaFinal%'");

			$stmt -> bindParam(":fecha", $fechaFinal, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetchAll();

		}else{

			$fechaActual = new DateTime();
			$fechaActual ->add(new DateInterval("P1D"));
			$fechaActualMasUno = $fechaActual->format("Y-m-d");

			$fechaFinal2 = new DateTime($fechaFinal);
			$fechaFinal2 ->add(new DateInterval("P1D"));
			$fechaFinalMasUno = $fechaFinal2->format("Y-m-d");

			if($fechaFinalMasUno == $fechaActualMasUno){

				$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE fecha BETWEEN '$fechaInicial' AND '$fechaFinalMasUno'");

			}else{


				$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE fecha BETWEEN '$fechaInicial' AND '$fechaFinal'");

			}
		
			$stmt -> execute();

			return $stmt -> fetchAll();

		}

	}

	/*=============================================
	SUMAR EL TOTAL DE VENTAS
	=============================================*/

	static public function mdlSumaTotalCompras($tabla){	

		$stmt = Conexion::conectar()->prepare("SELECT SUM(neto) as total FROM $tabla");

		$stmt -> execute();

		return $stmt -> fetch();

		$stmt -> close();

		$stmt = null;

	}
/*=============================================
	REGISTRAR COMPRA CON DETALLE
	=============================================*/
	static public function mdlRegistrarCompra($tabla, $datos) {
		$conexion = Conexion::conectar();
		try {
			$conexion->beginTransaction();

			// Insertar la compra principal
			$stmt = $conexion->prepare("INSERT INTO $tabla(codigo, total, id_usuario, id_proveedor) 
										VALUES (:codigo, :total, :id_usuario, :id_proveedor)");
			$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
			$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
			$stmt->bindParam(":id_usuario", $datos["id_usuario"], PDO::PARAM_INT);
			$stmt->bindParam(":id_proveedor", $datos["id_proveedor"], PDO::PARAM_INT);

			if (!$stmt->execute()) {
				throw new Exception("Error al registrar la compra principal.");
			}

			// Obtener ID de la compra
			$idCompra = $conexion->lastInsertId();

			// Insertar productos en detalle_compra
			$productos = json_decode($datos["productos"], true);

			if (!is_array($productos)) {
				throw new Exception("Error: el JSON de productos no es válido.");
			}

			$stmtDetalle = $conexion->prepare("INSERT INTO detalle_compra(id_compra, id_producto, producto, cantidad, precio_compra, subtotal) 
											   VALUES (:id_compra, :id_producto, :producto, :cantidad, :precio_compra, :subtotal)");

			foreach ($productos as $producto) {
				$stmtDetalle->bindValue(":id_compra", $idCompra, PDO::PARAM_INT);
				$stmtDetalle->bindValue(":id_producto", $producto["id"], PDO::PARAM_INT);
				$stmtDetalle->bindValue(":producto", $producto["descripcion"], PDO::PARAM_STR);
				$stmtDetalle->bindValue(":cantidad", $producto["cantidad"], PDO::PARAM_INT);
				$stmtDetalle->bindValue(":precio_compra", $producto["precio"], PDO::PARAM_STR);
				$stmtDetalle->bindValue(":subtotal", $producto["total"], PDO::PARAM_STR);

				if (!$stmtDetalle->execute()) {
					throw new Exception("Error al registrar el detalle de la compra.");
				}
			}

			$conexion->commit();
			return "ok";
		} catch (Exception $e) {
			$conexion->rollBack();
			return "error: " . $e->getMessage();
		} finally {
			$stmt = null;
			$stmtDetalle = null;
			$conexion = null;
		}
	}
	
	public static function mdlMostrarProductosCompra($idCompra) {
		try {
			$pdo = Conexion::conectar();
			$stmt = $pdo->prepare("SELECT productos FROM compra WHERE id = :idCompra AND TRIM(estado) = 'aprobado'");
	
			$stmt->bindParam(":idCompra", $idCompra, PDO::PARAM_INT);
			$stmt->execute();
	
			$compra = $stmt->fetch(PDO::FETCH_ASSOC);
	
			if ($compra) {
				return json_decode($compra["productos"], true);
			}
	
			return [];
		} catch (PDOException $e) {
			die("Error en la consulta: " . $e->getMessage());
		}
	}
	
	
	public static function mdlMostrarComprasAprobadas() {
		try {
			$pdo = Conexion::conectar();
			$stmt = $pdo->prepare("SELECT id, productos FROM compra WHERE TRIM(estado) = 'aprobado'");
			$stmt->execute();
			
			$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			foreach ($compras as &$compra) {
				$compra['productos'] = json_decode($compra['productos'], true);
			}
			
			return $compras;
		} catch (PDOException $e) {
			die("Error en la consulta: " . $e->getMessage());
		}
	}
	
	
	public static function mdlAgregarOActualizarDetalleCompra($tabla, $datos) {
		$pdo = Conexion::conectar();
	
		// Verificar si el producto ya está en la compra
		$stmt = $pdo->prepare("SELECT cantidad, total FROM $tabla WHERE id_compra = :id_compra AND id_producto = :id_producto");
		$stmt->bindParam(":id_compra", $datos["id_compra"], PDO::PARAM_INT);
		$stmt->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
		$stmt->execute();
		$resultado = $stmt->fetch(PDO::FETCH_ASSOC);
	
		if ($resultado) {
			// Si existe, actualizar cantidad y total sumando los valores nuevos
			$nuevaCantidad = $resultado["cantidad"] + $datos["cantidad"];
			$nuevoTotal = $resultado["total"] + $datos["total"];
	
			$stmt = $pdo->prepare("UPDATE $tabla SET cantidad = :cantidad, total = :total WHERE id_compra = :id_compra AND id_producto = :id_producto");
			$stmt->bindParam(":cantidad", $nuevaCantidad, PDO::PARAM_INT);
			$stmt->bindParam(":total", $nuevoTotal, PDO::PARAM_STR);
		} else {
			// Si no existe, insertar un nuevo registro
			$stmt = $pdo->prepare("INSERT INTO $tabla (id_compra, id_producto, cantidad, precio_unitario, total) VALUES (:id_compra, :id_producto, :cantidad, :precio_unitario, :total)");
			$stmt->bindParam(":cantidad", $datos["cantidad"], PDO::PARAM_INT);
			$stmt->bindParam(":precio_unitario", $datos["precio_unitario"], PDO::PARAM_STR);
			$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		}
	
		$stmt->bindParam(":id_compra", $datos["id_compra"], PDO::PARAM_INT);
		$stmt->bindParam(":id_producto", $datos["id_producto"], PDO::PARAM_INT);
	
		return $stmt->execute() ? "ok" : "error";
	}
	
	

    public static function mdlRestarCantidad($idCompra, $idProducto, $cantidadReducida) {
        $stmt = Conexion::conectar()->prepare("UPDATE detalle_compra SET cantidad = cantidad - :cantidadReducida WHERE id_compra = :id_compra AND id_producto = :id_producto");
        $stmt->bindParam(":cantidadReducida", $cantidadReducida, PDO::PARAM_INT);
        $stmt->bindParam(":id_compra", $idCompra, PDO::PARAM_INT);
        $stmt->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
        
        return $stmt->execute() ? "ok" : "error";
    }
	
	
	
}
