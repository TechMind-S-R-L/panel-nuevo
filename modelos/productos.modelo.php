<?php

require_once "conexion.php";

class ModeloProductos{

	static public function mdlMostrarMarcasActivas(){
		$stmt = Conexion::conectar()->prepare(
			"SELECT id_marca, nombre
			 FROM marcas
			 WHERE estado = 1
			 ORDER BY nombre ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlCrearMarcaRapida($nombre, $descripcion = ""){
		$conexion = Conexion::conectar();
		$stmtExiste = $conexion->prepare(
			"SELECT id_marca, nombre
			 FROM marcas
			 WHERE UPPER(TRIM(nombre)) = UPPER(TRIM(:nombre))
			 LIMIT 1"
		);
		$stmtExiste->bindValue(":nombre", $nombre, PDO::PARAM_STR);
		$stmtExiste->execute();
		$existente = $stmtExiste->fetch(PDO::FETCH_ASSOC);
		if($existente){
			return array("status" => "exists", "marca" => $existente);
		}

		$stmt = $conexion->prepare(
			"INSERT INTO marcas (nombre, descripcion, estado)
			 VALUES (:nombre, :descripcion, 1)"
		);
		$stmt->bindValue(":nombre", $nombre, PDO::PARAM_STR);
		$stmt->bindValue(":descripcion", $descripcion, PDO::PARAM_STR);
		if(!$stmt->execute()){
			return array("status" => "error");
		}

		return array(
			"status" => "ok",
			"marca" => array(
				"id_marca" => (int)$conexion->lastInsertId(),
				"nombre" => $nombre
			)
		);
	}

	static public function mdlNormalizarCodigoTechMind($codigo){
		$codigo = trim((string)$codigo);
		if(strtoupper(substr($codigo, 0, 2)) === "TM"){
			return "TM".substr($codigo, 2);
		}
		return "TM".$codigo;
	}

	static public function mdlNormalizarNombreProducto($nombre){
		$nombre = trim((string)$nombre);
		return function_exists("mb_strtoupper")
			? mb_strtoupper($nombre, "UTF-8")
			: strtoupper($nombre);
	}

	/*=============================================
	MOSTRAR PRODUCTOS
	=============================================*/

	static public function mdlMostrarProductos($tabla, $item, $valor, $orden){

		if($item != null){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item ORDER BY id DESC");

			$stmt -> bindParam(":".$item, $valor, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY $orden DESC");

			$stmt -> execute();

			return $stmt -> fetchAll();

		}

		$stmt -> close();

		$stmt = null;

	}
	static public function mdlMostrarProductosFiltrados($tabla){
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.*,
			        (
			          SELECT d.costo_unitario
			          FROM compra_rendicion_detalle d
			          INNER JOIN compra c ON c.id=d.id_compra
			          WHERE d.id_producto=p.id
			            AND c.estado IN ('compra_rendida','entregado_almacen','completado')
			          ORDER BY c.fecha_confirmacion_rendicion DESC, d.id DESC
			          LIMIT 1
			        ) AS ultimo_costo_facturado,
			        (
			          SELECT c.factura_compra
			          FROM compra_rendicion_detalle d
			          INNER JOIN compra c ON c.id=d.id_compra
			          WHERE d.id_producto=p.id
			            AND c.estado IN ('compra_rendida','entregado_almacen','completado')
			          ORDER BY c.fecha_confirmacion_rendicion DESC, d.id DESC
			          LIMIT 1
			        ) AS ultima_factura_compra
			 FROM $tabla p
			 WHERE p.stock > 0 AND (p.requiere_precio = 1 OR p.precio_compra = 0 OR p.precio_venta = 0)
			 ORDER BY p.id DESC"
		);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		// Retornar los resultados
		return $result;
	}

	static public function mdlMostrarProductosAlmacen(){
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.*,
			        CASE
			          WHEN padre.categoria IS NULL THEN categoria.categoria
			          ELSE CONCAT(padre.categoria, ' > ', categoria.categoria)
			        END AS ruta_categoria
			 FROM productos p
			 LEFT JOIN categorias categoria ON categoria.id = p.id_categoria
			 LEFT JOIN categorias padre ON padre.id = categoria.id_padre
			 ORDER BY (p.stock > 0) DESC, p.stock DESC, p.id DESC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarProductosDisponiblesVenta($tabla){
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.*,
			        COALESCE(det.disponibles, p.stock) AS stock_real
			 FROM $tabla p
			 LEFT JOIN (
			 	SELECT id_producto, COUNT(*) AS disponibles
			 	FROM productos_detalle
			 	WHERE estado = 'disponible'
			 	GROUP BY id_producto
			 ) det ON det.id_producto = p.id
			 ORDER BY COALESCE(det.disponibles, p.stock) DESC, p.descripcion ASC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMostrarCodigosUnicosProducto($idProducto){
		$stmt = Conexion::conectar()->prepare(
			"SELECT codigo_barras_unico, estado, fecha_ingreso
			 FROM productos_detalle
			 WHERE id_producto = :id_producto
			 ORDER BY estado ASC, id DESC"
		);
		$stmt->bindParam(":id_producto", $idProducto, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlSincronizarStockConCodigosUnicos(){
		$stmt = Conexion::conectar()->prepare(
			"UPDATE productos p
			 JOIN (
			 	SELECT id_producto, SUM(estado = 'disponible') AS disponibles
			 	FROM productos_detalle
			 	GROUP BY id_producto
			 ) det ON det.id_producto = p.id
			 SET p.stock = det.disponibles"
		);
		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlMostrarProducto($item, $valor){
		$tabla = "productos";
		$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item LIMIT 1");
		$stmt->bindParam(":".$item, $valor, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
	

	
	/*=============================================
	REGISTRO DE PRODUCTO
	=============================================*/
	static public function mdlIngresarProducto($tabla, $datos){

		$datos["codigo"] = self::mdlNormalizarCodigoTechMind($datos["codigo"]);
		$datos["descripcion"] = self::mdlNormalizarNombreProducto($datos["descripcion"]);
		$datos["detalle"] = trim((string)($datos["detalle"] ?? ""));

		// $stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(id_categoria, codigo, codigo_producto_generico, codigo_barras_unico, descripcion, imagen, stock, precio_compra, precio_venta) VALUES (:id_categoria, :codigo, :codigo_producto_generico, :codigo_barras_unico, :descripcion, :imagen, :stock, :precio_compra, :precio_venta)");
		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(id_categoria, id_marca, codigo, codigo_producto_generico, codigo_barras_unico, descripcion, detalle, imagen, stock, precio_compra, precio_venta) VALUES (:id_categoria, :id_marca, :codigo, :codigo_producto_generico, :codigo_barras_unico, :descripcion, :detalle, :imagen, :stock, :precio_compra, :precio_venta)");

		$stmt->bindParam(":id_categoria", $datos["id_categoria"], PDO::PARAM_INT);
		$stmt->bindValue(":id_marca", !empty($datos["id_marca"]) ? (int)$datos["id_marca"] : null, !empty($datos["id_marca"]) ? PDO::PARAM_INT : PDO::PARAM_NULL);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo_producto_generico", $datos["codigo_producto_generico"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo_barras_unico", $datos["codigo_barras_unico"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":detalle", $datos["detalle"], PDO::PARAM_STR);
		$stmt->bindParam(":imagen", $datos["imagen"], PDO::PARAM_STR);
		$stmt->bindParam(":stock", $datos["stock"], PDO::PARAM_STR);
		$stmt->bindParam(":precio_compra", $datos["precio_compra"], PDO::PARAM_STR);
		$stmt->bindParam(":precio_venta", $datos["precio_venta"], PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	EDITAR PRODUCTO
	=============================================*/
	static public function mdlEditarProducto($tabla, $datos){

		$datos["codigo"] = self::mdlNormalizarCodigoTechMind($datos["codigo"]);
		$datos["descripcion"] = self::mdlNormalizarNombreProducto($datos["descripcion"]);
		$datos["detalle"] = trim((string)($datos["detalle"] ?? ""));

		// $stmt = Conexion::conectar()->prepare("UPDATE $tabla SET id_categoria = :id_categoria, descripcion = :descripcion, imagen = :imagen, stock = :stock, precio_compra = :precio_compra, precio_venta = :precio_venta WHERE codigo = :codigo");
		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET id_categoria = :id_categoria, id_marca = :id_marca, descripcion = :descripcion, detalle = :detalle, imagen = :imagen, stock = :stock, precio_compra = :precio_compra, precio_venta = :precio_venta, codigo_producto_generico = :codigo_producto_generico, codigo_barras_unico = :codigo_barras_unico WHERE codigo = :codigo");

		$stmt->bindParam(":id_categoria", $datos["id_categoria"], PDO::PARAM_INT);
		$stmt->bindValue(":id_marca", !empty($datos["id_marca"]) ? (int)$datos["id_marca"] : null, !empty($datos["id_marca"]) ? PDO::PARAM_INT : PDO::PARAM_NULL);
		$stmt->bindParam(":codigo", $datos["codigo"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo_producto_generico", $datos["codigo_producto_generico"], PDO::PARAM_STR);
		$stmt->bindParam(":codigo_barras_unico", $datos["codigo_barras_unico"], PDO::PARAM_STR);
		$stmt->bindParam(":descripcion", $datos["descripcion"], PDO::PARAM_STR);
		$stmt->bindParam(":detalle", $datos["detalle"], PDO::PARAM_STR);
		$stmt->bindParam(":imagen", $datos["imagen"], PDO::PARAM_STR);
		$stmt->bindParam(":stock", $datos["stock"], PDO::PARAM_STR);
		$stmt->bindParam(":precio_compra", $datos["precio_compra"], PDO::PARAM_STR);
		$stmt->bindParam(":precio_venta", $datos["precio_venta"], PDO::PARAM_STR);

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}
/* =============================================
       Método para obtener la descripción del producto
    ============================================= */
    static public function mdlMostrarDescripcionProducto($tabla, $item, $valor) {

        // Preparar la consulta
        $stmt = Conexion::conectar()->prepare("SELECT descripcion, precio_compra, precio_venta FROM $tabla WHERE $item = :$item");
        $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

        // Ejecutar la consulta
        $stmt->execute();

        // Retornar los datos en forma de array asociativo
        return $stmt->fetch();

        // Cerrar conexión
        $stmt = null;
    }

	/*=============================================
	EDITAR PRODUCTO CAJERO
	=============================================*/
	static public function mdlEditarProductoCajero($tabla, $datos) {
		try {
			$conexion = Conexion::conectar();
			$conexion->beginTransaction();

			$stmtAnterior = $conexion->prepare("SELECT precio_compra, precio_venta FROM $tabla WHERE id = :id");
			$stmtAnterior->bindParam(":id", $datos["id"], PDO::PARAM_INT);
			$stmtAnterior->execute();
			$precioAnterior = $stmtAnterior->fetch(PDO::FETCH_ASSOC);

			if (!$precioAnterior) {
				$conexion->rollBack();
				return "error";
			}

			$stmt = $conexion->prepare(
				"UPDATE $tabla SET precio_compra = :precio_compra, precio_venta = :precio_venta, requiere_precio = 0 WHERE id = :id"
			);
		
			$stmt->bindParam(":precio_compra", $datos["precio_compra"], PDO::PARAM_STR);
			$stmt->bindParam(":precio_venta", $datos["precio_venta"], PDO::PARAM_STR);
			$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
			$stmt->execute();

			$stmtHistorial = $conexion->prepare(
				"INSERT INTO historial_precios (id_producto, precio_compra_anterior, precio_venta_anterior, precio_compra_nuevo, precio_venta_nuevo, id_usuario)
				 VALUES (:id_producto, :precio_compra_anterior, :precio_venta_anterior, :precio_compra_nuevo, :precio_venta_nuevo, :id_usuario)"
			);
			$stmtHistorial->bindParam(":id_producto", $datos["id"], PDO::PARAM_INT);
			$stmtHistorial->bindValue(":precio_compra_anterior", $precioAnterior["precio_compra"] ?? 0, PDO::PARAM_STR);
			$stmtHistorial->bindValue(":precio_venta_anterior", $precioAnterior["precio_venta"] ?? 0, PDO::PARAM_STR);
			$stmtHistorial->bindParam(":precio_compra_nuevo", $datos["precio_compra"], PDO::PARAM_STR);
			$stmtHistorial->bindParam(":precio_venta_nuevo", $datos["precio_venta"], PDO::PARAM_STR);
			$stmtHistorial->bindParam(":id_usuario", $datos["id_usuario"], PDO::PARAM_INT);
			$stmtHistorial->execute();

			$conexion->commit();
			return "ok";

		} catch (Exception $e) {
			if (isset($conexion) && $conexion->inTransaction()) {
				$conexion->rollBack();
			}
			return "error";
		}
	
		$stmt = null;
	}
	

	/*=============================================
	BORRAR PRODUCTO
	=============================================*/

	static public function mdlEliminarProducto($tabla, $datos){

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

	/*=============================================
	ACTUALIZAR PRODUCTO
	=============================================*/

	static public function mdlActualizarProducto($tabla, $item1, $valor1, $valor){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET $item1 = :$item1 WHERE id = :id");

		$stmt -> bindParam(":".$item1, $valor1, PDO::PARAM_STR);
		$stmt -> bindParam(":id", $valor, PDO::PARAM_STR);

		if($stmt -> execute()){
			if ($item1 === "stock" && (int)$valor1 <= 0) {
				$stmtPrecio = Conexion::conectar()->prepare("UPDATE $tabla SET requiere_precio = 1 WHERE id = :id");
				$stmtPrecio->bindParam(":id", $valor, PDO::PARAM_INT);
				$stmtPrecio->execute();
			}

			return "ok";
		
		}else{

			return "error";	

		}

		$stmt -> close();

		$stmt = null;

	}

	/*=============================================
	MOSTRAR SUMA VENTAS
	=============================================*/	

	static public function mdlMostrarSumaVentas($tabla){

		$stmt = Conexion::conectar()->prepare("SELECT SUM(ventas) as total FROM $tabla");

		$stmt -> execute();

		return $stmt -> fetch();

		$stmt -> close();

		$stmt = null;
	}


}
