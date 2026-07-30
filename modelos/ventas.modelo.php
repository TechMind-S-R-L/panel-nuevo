<?php

require_once "conexion.php";

class ModeloVentas{

	private static function mdlNormalizarCodigoUnidadDespacho($codigo){
		$codigo = trim((string)$codigo);
		$codigo = str_replace(array("\xC2\xA0", " ", "\t", "\r", "\n"), "", $codigo);
		$codigo = str_replace(array("'", "`", "´", "’", "‘", "‛", "＇", "_", "–", "—", "−", "‐"), "-", $codigo);
		$codigo = preg_replace('/-+/', '-', $codigo);
		$codigo = strtoupper($codigo);

		if(preg_match('/^(TMU[A-Z0-9]+)-([0-9]{5})$/', $codigo, $partes)){
			return $partes[1]."-".$partes[2];
		}

		if(preg_match('/^(TMU[A-Z0-9]+)([0-9]{5})$/', $codigo, $partes)){
			return $partes[1]."-".$partes[2];
		}

		return $codigo;
	}

	/*=============================================
	MOSTRAR VENTAS
	=============================================*/

	static public function mdlMostrarVentas($tabla, $item, $valor){

		if($item != null){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item ORDER BY id DESC");

			$stmt -> bindParam(":".$item, $valor, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY fecha DESC, id DESC");

			$stmt -> execute();

			return $stmt -> fetchAll();

		}
		
		$stmt -> close();

		$stmt = null;

	}

	/*=============================================
	REGISTRO DE VENTA
	=============================================*/

	static public function mdlIngresarVenta($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(codigo, id_cliente, id_vendedor, productos, descuento, neto, total, metodo_pago, estado_pago, estado_despacho) VALUES (:codigo, :id_cliente, :id_vendedor, :productos, :descuento, :neto, :total, :metodo_pago, 'pendiente', 'pendiente')");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":id_cliente", $datos["id_cliente"], PDO::PARAM_INT);
		$stmt->bindParam(":id_vendedor", $datos["id_vendedor"], PDO::PARAM_INT);
		$stmt->bindParam(":productos", $datos["productos"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento", $datos["descuento"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	EDITAR VENTA
	=============================================*/

	static public function mdlEditarVenta($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET  id_cliente = :id_cliente, id_vendedor = :id_vendedor, productos = :productos, descuento = :descuento, neto = :neto, total= :total, metodo_pago = :metodo_pago WHERE codigo = :codigo");

		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_INT);
		$stmt->bindParam(":id_cliente", $datos["id_cliente"], PDO::PARAM_INT);
		$stmt->bindParam(":id_vendedor", $datos["id_vendedor"], PDO::PARAM_INT);
		$stmt->bindParam(":productos", $datos["productos"], PDO::PARAM_STR);
		$stmt->bindParam(":descuento", $datos["descuento"], PDO::PARAM_STR);
		$stmt->bindParam(":neto", $datos["neto"], PDO::PARAM_STR);
		$stmt->bindParam(":total", $datos["total"], PDO::PARAM_STR);
		$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	ELIMINAR VENTA
	=============================================*/

	static public function mdlEliminarVenta($tabla, $datos){
		if($tabla !== "ventas"){
			return "error";
		}

		$conexion = Conexion::conectar();

		try{
			$conexion->beginTransaction();

			$stmtVenta = $conexion->prepare("SELECT * FROM ventas WHERE id = :id FOR UPDATE");
			$stmtVenta->bindValue(":id", (int)$datos, PDO::PARAM_INT);
			$stmtVenta->execute();
			$venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

			if(!$venta){
				$conexion->rollBack();
				return "no_existe";
			}

			$productos = json_decode($venta["productos"] ?? "[]", true);
			if(!is_array($productos)){
				throw new Exception("La lista de productos no es valida.");
			}

			$stmtCodigos = $conexion->prepare(
				"SELECT id_producto_detalle
				 FROM ventas_despacho_detalle
				 WHERE id_venta = :id_venta
				 FOR UPDATE"
			);
			$stmtCodigos->bindValue(":id_venta", (int)$datos, PDO::PARAM_INT);
			$stmtCodigos->execute();
			$detallesDespachados = $stmtCodigos->fetchAll(PDO::FETCH_ASSOC);

			if(!empty($detallesDespachados)){
				$idsDetalle = array_map(function($detalle){
					return (int)$detalle["id_producto_detalle"];
				}, $detallesDespachados);
				$marcadores = implode(",", array_fill(0, count($idsDetalle), "?"));
				$stmtLiberar = $conexion->prepare(
					"UPDATE productos_detalle
					 SET estado = 'disponible'
					 WHERE id IN (".$marcadores.")"
				);
				foreach($idsDetalle as $indice => $idDetalle){
					$stmtLiberar->bindValue($indice + 1, $idDetalle, PDO::PARAM_INT);
				}
				$stmtLiberar->execute();
			}

			$stmtDespacho = $conexion->prepare("DELETE FROM ventas_despacho_detalle WHERE id_venta = :id_venta");
			$stmtDespacho->bindValue(":id_venta", (int)$datos, PDO::PARAM_INT);
			$stmtDespacho->execute();

			$totalUnidades = 0;
			foreach($productos as $producto){
				$idProducto = (int)($producto["id"] ?? 0);
				$cantidad = (int)($producto["cantidad"] ?? 0);
				if($idProducto <= 0 || $cantidad <= 0){
					continue;
				}
				$totalUnidades += $cantidad;
				$stmtProducto = $conexion->prepare(
					"UPDATE productos
					 SET stock = stock + :cantidad_stock,
					     ventas = GREATEST(0, ventas - :cantidad_ventas)
					 WHERE id = :id"
				);
				$stmtProducto->bindValue(":cantidad_stock", $cantidad, PDO::PARAM_INT);
				$stmtProducto->bindValue(":cantidad_ventas", $cantidad, PDO::PARAM_INT);
				$stmtProducto->bindValue(":id", $idProducto, PDO::PARAM_INT);
				$stmtProducto->execute();
			}

			$stmtEliminar = $conexion->prepare("DELETE FROM ventas WHERE id = :id");
			$stmtEliminar->bindValue(":id", (int)$datos, PDO::PARAM_INT);
			$stmtEliminar->execute();

			$stmtUltimaCompra = $conexion->prepare("SELECT MAX(fecha) AS ultima_compra FROM ventas WHERE id_cliente = :id_cliente");
			$stmtUltimaCompra->bindValue(":id_cliente", (int)$venta["id_cliente"], PDO::PARAM_INT);
			$stmtUltimaCompra->execute();
			$ultimaCompra = $stmtUltimaCompra->fetch(PDO::FETCH_ASSOC);
			$fechaUltimaCompra = !empty($ultimaCompra["ultima_compra"])
				? $ultimaCompra["ultima_compra"]
				: "1970-01-01 00:00:00";

			$stmtCliente = $conexion->prepare(
				"UPDATE clientes
				 SET compras = GREATEST(0, compras - :cantidad),
				     ultima_compra = :ultima_compra
				 WHERE id = :id"
			);
			$stmtCliente->bindValue(":cantidad", $totalUnidades, PDO::PARAM_INT);
			$stmtCliente->bindValue(":ultima_compra", $fechaUltimaCompra, PDO::PARAM_STR);
			$stmtCliente->bindValue(":id", (int)$venta["id_cliente"], PDO::PARAM_INT);
			$stmtCliente->execute();

			$conexion->commit();
			return "ok";
		}catch(Throwable $e){
			if($conexion->inTransaction()){
				$conexion->rollBack();
			}
			error_log("Error al eliminar venta ".(int)$datos.": ".$e->getMessage());
			return "error";
		}

	}

	/*=============================================
	RANGO FECHAS
	=============================================*/	

	static public function mdlRangoFechasVentas($tabla, $fechaInicial, $fechaFinal){

		if($fechaInicial == null){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY fecha DESC, id DESC");

			$stmt -> execute();

			return $stmt -> fetchAll();	


		}else if($fechaInicial == $fechaFinal){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE fecha like '%$fechaFinal%' ORDER BY fecha DESC, id DESC");

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

				$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE fecha BETWEEN '$fechaInicial' AND '$fechaFinalMasUno' ORDER BY fecha DESC, id DESC");

			}else{


				$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE fecha BETWEEN '$fechaInicial' AND '$fechaFinal' ORDER BY fecha DESC, id DESC");

			}
		
			$stmt -> execute();

			return $stmt -> fetchAll();

		}

	}

	/*=============================================
	SUMAR EL TOTAL DE VENTAS
	=============================================*/

	static public function mdlSumaTotalVentas($tabla){	

		$stmt = Conexion::conectar()->prepare("SELECT SUM(neto) as total FROM $tabla");

		$stmt -> execute();

		return $stmt -> fetch();

		$stmt -> close();

		$stmt = null;

	}

	static public function mdlSiguienteCodigoVenta(){
		$stmt = Conexion::conectar()->prepare("SELECT COALESCE(MAX(codigo), 10000) + 1 AS siguiente FROM ventas");
		$stmt->execute();
		$respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
		return (int)($respuesta["siguiente"] ?? 10001);
	}

	static public function mdlCambiarEstadoPago($datos){

		$stmt = Conexion::conectar()->prepare(
			"UPDATE ventas
			 SET estado_pago = 'aprobado',
			     metodo_pago = :metodo_pago,
			     monto_recibido = :monto_recibido,
			     cambio = :cambio,
			     codigo_transaccion = :codigo_transaccion,
			     id_cajero = :id_cajero,
			     fecha_pago = NOW()
			 WHERE id = :id AND estado_pago = 'pendiente'"
		);
		$stmt->bindParam(":metodo_pago", $datos["metodo_pago"], PDO::PARAM_STR);
		$stmt->bindParam(":monto_recibido", $datos["monto_recibido"], PDO::PARAM_STR);
		$stmt->bindParam(":cambio", $datos["cambio"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo_transaccion", $datos["codigo_transaccion"], PDO::PARAM_STR);
		$stmt->bindParam(":id_cajero", $datos["id_cajero"], PDO::PARAM_INT);
		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlCambiarEstadoDespacho($idVenta, $idDespachador){

		$stmt = Conexion::conectar()->prepare("UPDATE ventas SET estado_despacho = 'entregado', id_despachador = :id_despachador, fecha_despacho = NOW() WHERE id = :id AND estado_pago = 'aprobado' AND estado_despacho = 'pendiente'");
		$stmt->bindParam(":id_despachador", $idDespachador, PDO::PARAM_INT);
		$stmt->bindParam(":id", $idVenta, PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlRegistrarDespachoConCodigos($idVenta, $idDespachador, $codigosPorProducto){

		$conexion = Conexion::conectar();

		try {
			$conexion->beginTransaction();

			$stmtVenta = $conexion->prepare(
				"SELECT *
				 FROM ventas
				 WHERE id = :id AND estado_pago = 'aprobado' AND estado_despacho = 'pendiente'
				 FOR UPDATE"
			);
			$stmtVenta->bindParam(":id", $idVenta, PDO::PARAM_INT);
			$stmtVenta->execute();
			$venta = $stmtVenta->fetch(PDO::FETCH_ASSOC);

			if (!$venta) {
				throw new Exception("La venta no existe, no esta cobrada o ya fue despachada.");
			}

			$productos = json_decode($venta["productos"], true);
			if (!is_array($productos) || count($productos) === 0) {
				throw new Exception("La venta no tiene productos validos para despachar.");
			}

			$vistos = [];
			$totalEntregado = 0;

			foreach ($productos as $producto) {
				$idProducto = (int)($producto["id"] ?? 0);
				$cantidadVendida = (int)($producto["cantidad"] ?? 0);
				$descripcion = $producto["descripcion"] ?? "Producto";

				if ($idProducto <= 0 || $cantidadVendida <= 0) {
					throw new Exception("Hay productos invalidos en la venta.");
				}

				$codigos = $codigosPorProducto[$idProducto] ?? [];
				if (!is_array($codigos)) {
					$codigos = [];
				}

				$codigos = array_values(array_filter(array_map(function($codigo){
					return self::mdlNormalizarCodigoUnidadDespacho($codigo);
				}, $codigos), function($codigo){
					return $codigo !== "";
				}));

				if (count($codigos) !== $cantidadVendida) {
					throw new Exception("Debes registrar ".$cantidadVendida." codigo(s) para ".$descripcion.". Registraste ".count($codigos).".");
				}

				foreach ($codigos as $codigo) {
					$llaveCodigo = function_exists("mb_strtolower") ? mb_strtolower($codigo, "UTF-8") : strtolower($codigo);
					if (isset($vistos[$llaveCodigo])) {
						throw new Exception("El codigo ".$codigo." esta repetido en esta entrega.");
					}
					$vistos[$llaveCodigo] = true;

					$stmtDetalle = $conexion->prepare(
						"SELECT id, id_producto, estado
						 FROM productos_detalle
						 WHERE codigo_barras_unico = :codigo
						 LIMIT 1
						 FOR UPDATE"
					);
					$stmtDetalle->bindParam(":codigo", $codigo, PDO::PARAM_STR);
					$stmtDetalle->execute();
					$detalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);

					if (!$detalle) {
						throw new Exception("El codigo ".$codigo." no existe en inventario.");
					}

					if ((int)$detalle["id_producto"] !== $idProducto) {
						throw new Exception("El codigo ".$codigo." no pertenece a ".$descripcion.".");
					}

					if ($detalle["estado"] !== "disponible") {
						throw new Exception("El codigo ".$codigo." no esta disponible para entregar.");
					}

					$stmtInsertar = $conexion->prepare(
						"INSERT INTO ventas_despacho_detalle
						 (id_venta, id_producto, id_producto_detalle, codigo_barras_unico, id_despachador)
						 VALUES
						 (:id_venta, :id_producto, :id_producto_detalle, :codigo_barras_unico, :id_despachador)"
					);
					$stmtInsertar->bindParam(":id_venta", $idVenta, PDO::PARAM_INT);
					$stmtInsertar->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
					$stmtInsertar->bindParam(":id_producto_detalle", $detalle["id"], PDO::PARAM_INT);
					$stmtInsertar->bindParam(":codigo_barras_unico", $codigo, PDO::PARAM_STR);
					$stmtInsertar->bindParam(":id_despachador", $idDespachador, PDO::PARAM_INT);
					$stmtInsertar->execute();

					$stmtActualizarDetalle = $conexion->prepare(
						"UPDATE productos_detalle
						 SET estado = 'vendido'
						 WHERE id = :id"
					);
					$stmtActualizarDetalle->bindParam(":id", $detalle["id"], PDO::PARAM_INT);
					$stmtActualizarDetalle->execute();

					$totalEntregado++;
				}
			}

			$stmtDespacho = $conexion->prepare(
				"UPDATE ventas
				 SET estado_despacho = 'entregado',
				     id_despachador = :id_despachador,
				     fecha_despacho = NOW()
				 WHERE id = :id"
			);
			$stmtDespacho->bindParam(":id_despachador", $idDespachador, PDO::PARAM_INT);
			$stmtDespacho->bindParam(":id", $idVenta, PDO::PARAM_INT);
			$stmtDespacho->execute();

			$conexion->commit();

			return [
				"status" => "ok",
				"venta" => $venta,
				"total_entregado" => $totalEntregado
			];
		} catch (Exception $e) {
			if ($conexion->inTransaction()) {
				$conexion->rollBack();
			}

			return [
				"status" => "error",
				"message" => $e->getMessage()
			];
		}
	}

	static public function mdlMostrarCodigosDespacho($idVenta){

		$stmt = Conexion::conectar()->prepare(
			"SELECT vdd.*, p.descripcion, p.codigo
			 FROM ventas_despacho_detalle vdd
			 INNER JOIN productos p ON p.id = vdd.id_producto
			 WHERE vdd.id_venta = :id_venta
			 ORDER BY p.descripcion ASC, vdd.id ASC"
		);
		$stmt->bindParam(":id_venta", $idVenta, PDO::PARAM_INT);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarVentasPorEstado($estadoPago, $estadoDespacho = null){

		if ($estadoDespacho === null) {
			$stmt = Conexion::conectar()->prepare("SELECT * FROM ventas WHERE estado_pago = :estado_pago ORDER BY fecha DESC");
			$stmt->bindParam(":estado_pago", $estadoPago, PDO::PARAM_STR);
		} else {
			$stmt = Conexion::conectar()->prepare("SELECT * FROM ventas WHERE estado_pago = :estado_pago AND estado_despacho = :estado_despacho ORDER BY fecha DESC");
			$stmt->bindParam(":estado_pago", $estadoPago, PDO::PARAM_STR);
			$stmt->bindParam(":estado_despacho", $estadoDespacho, PDO::PARAM_STR);
		}

		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	
}
