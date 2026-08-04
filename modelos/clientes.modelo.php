<?php

require_once "conexion.php";

class ModeloClientes{

	static private function mdlColumnasDisponibles($tabla){
		$conexion = Conexion::conectar();
		$columnas = array();

		try{
			$stmt = $conexion->prepare("SHOW COLUMNS FROM $tabla");
			$stmt->execute();
			foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $columna){
				if(isset($columna["Field"])){
					$columnas[$columna["Field"]] = true;
				}
			}
		}catch(Exception $e){
			return array();
		}

		return $columnas;
	}

	static private function mdlNormalizarFechaCliente($fecha){
		$fecha = trim((string)$fecha);
		if($fecha === ""){
			return null;
		}

		$fecha = str_replace("/", "-", $fecha);
		$partes = explode("-", $fecha);
		if(count($partes) !== 3){
			return null;
		}

		$anio = (int)$partes[0];
		$mes = (int)$partes[1];
		$dia = (int)$partes[2];
		if(!checkdate($mes, $dia, $anio)){
			return null;
		}

		return sprintf("%04d-%02d-%02d", $anio, $mes, $dia);
	}

	static private function mdlAsegurarTablaCrm(){
		try{
			$conexion = Conexion::conectar();
			$conexion->exec(
				"CREATE TABLE IF NOT EXISTS cliente_crm (
					id INT AUTO_INCREMENT PRIMARY KEY,
					id_cliente INT NOT NULL,
					estado VARCHAR(40) NOT NULL DEFAULT 'nuevo',
					prioridad VARCHAR(20) NOT NULL DEFAULT 'media',
					proxima_accion DATE NULL,
					nota TEXT NULL,
					id_usuario INT NULL,
					fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					UNIQUE KEY uk_cliente_crm_cliente (id_cliente),
					KEY idx_cliente_crm_estado (estado),
					KEY idx_cliente_crm_proxima (proxima_accion)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}catch(Exception $e){
			error_log("TechMind clientes CRM: no se pudo asegurar tabla - ".$e->getMessage());
		}
	}

	/*=============================================
	CREAR CLIENTE
	=============================================*/

	static public function mdlIngresarCliente($tabla, $datos){
		$columnasDisponibles = self::mdlColumnasDisponibles($tabla);
		$fechaNacimiento = self::mdlNormalizarFechaCliente($datos["fecha_nacimiento"] ?? null);

		$columnas = array("nombre", "documento");
		$valores = array(":nombre", ":documento");

		foreach(array("email", "telefono", "direccion", "fecha_nacimiento") as $columnaOpcional){
			if(isset($columnasDisponibles[$columnaOpcional])){
				$columnas[] = $columnaOpcional;
				$valores[] = ":".$columnaOpcional;
			}
		}
		if(isset($columnasDisponibles["compras"])){
			$columnas[] = "compras";
			$valores[] = "0";
		}
		if(isset($columnasDisponibles["ultima_compra"])){
			$columnas[] = "ultima_compra";
			$valores[] = "CURDATE()";
		}

		try{
			$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(".implode(", ", $columnas).") VALUES (".implode(", ", $valores).")");

			$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
			$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
			if(isset($columnasDisponibles["email"])){ $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR); }
			if(isset($columnasDisponibles["telefono"])){ $stmt->bindParam(":telefono", $datos["telefono"], PDO::PARAM_STR); }
			if(isset($columnasDisponibles["direccion"])){ $stmt->bindParam(":direccion", $datos["direccion"], PDO::PARAM_STR); }
			if(isset($columnasDisponibles["fecha_nacimiento"])){ $stmt->bindValue(":fecha_nacimiento", $fechaNacimiento, $fechaNacimiento === null ? PDO::PARAM_NULL : PDO::PARAM_STR); }

			if($stmt->execute()){

				return "ok";

			}else{

				return "error";

			}
		}catch(Exception $e){
			error_log("TechMind clientes: no se pudo insertar cliente - ".$e->getMessage());
			return "error";

		}

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	MOSTRAR CLIENTES
	=============================================*/

	static public function mdlMostrarClientes($tabla, $item, $valor){
		self::mdlAsegurarTablaCrm();
		$select = "SELECT c.*,
				          COALESCE(v.total_compras, 0) AS compras,
				          COALESCE(v.cantidad_ventas, 0) AS cantidad_ventas,
				          COALESCE(v.ultima_compra, c.ultima_compra) AS ultima_compra,
				          crm.estado AS estado_crm,
				          crm.prioridad AS prioridad_crm,
				          crm.proxima_accion AS proxima_accion_crm,
				          crm.nota AS nota_crm,
				          crm.fecha_actualizacion AS fecha_crm
				   FROM $tabla c
				   LEFT JOIN (
				   	 SELECT id_cliente,
				   	        SUM(CASE WHEN estado_pago = 'aprobado' THEN total ELSE 0 END) AS total_compras,
				   	        SUM(CASE WHEN estado_pago = 'aprobado' THEN 1 ELSE 0 END) AS cantidad_ventas,
				   	        MAX(CASE WHEN estado_pago = 'aprobado' THEN COALESCE(fecha_pago, fecha) ELSE NULL END) AS ultima_compra
				   	 FROM ventas
				   	 GROUP BY id_cliente
				   ) v ON v.id_cliente = c.id
				   LEFT JOIN cliente_crm crm ON crm.id_cliente = c.id";

		if($item != null){

			$stmt = Conexion::conectar()->prepare($select." WHERE c.$item = :$item LIMIT 1");

			$stmt -> bindParam(":".$item, $valor, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare($select." ORDER BY c.fecha DESC, c.id DESC");

			$stmt -> execute();

			return $stmt -> fetchAll();

		}

		$stmt -> close();

		$stmt = null;

	}

	/*=============================================
	EDITAR CLIENTE
	=============================================*/

	static public function mdlEditarCliente($tabla, $datos){
		$columnasDisponibles = self::mdlColumnasDisponibles($tabla);
		$fechaNacimiento = self::mdlNormalizarFechaCliente($datos["fecha_nacimiento"] ?? null);

		$sets = array("nombre = :nombre", "documento = :documento");
		foreach(array("email", "telefono", "direccion", "fecha_nacimiento") as $columnaOpcional){
			if(isset($columnasDisponibles[$columnaOpcional])){
				$sets[] = $columnaOpcional." = :".$columnaOpcional;
			}
		}

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET ".implode(", ", $sets)." WHERE id = :id");

		$stmt->bindParam(":id", $datos["id"], PDO::PARAM_INT);
		$stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
		$stmt->bindParam(":documento", $datos["documento"], PDO::PARAM_STR);
		if(isset($columnasDisponibles["email"])){ $stmt->bindParam(":email", $datos["email"], PDO::PARAM_STR); }
		if(isset($columnasDisponibles["telefono"])){ $stmt->bindParam(":telefono", $datos["telefono"], PDO::PARAM_STR); }
		if(isset($columnasDisponibles["direccion"])){ $stmt->bindParam(":direccion", $datos["direccion"], PDO::PARAM_STR); }
		if(isset($columnasDisponibles["fecha_nacimiento"])){ $stmt->bindValue(":fecha_nacimiento", $fechaNacimiento, $fechaNacimiento === null ? PDO::PARAM_NULL : PDO::PARAM_STR); }

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	ELIMINAR CLIENTE
	=============================================*/

	static public function mdlEliminarCliente($tabla, $datos){

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
	ACTUALIZAR CLIENTE
	=============================================*/

	static public function mdlActualizarCliente($tabla, $item1, $valor1, $valor){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET $item1 = :$item1 WHERE id = :id");

		$stmt -> bindParam(":".$item1, $valor1, PDO::PARAM_STR);
		$stmt -> bindParam(":id", $valor, PDO::PARAM_STR);

		if($stmt -> execute()){

			return "ok";
		
		}else{

			return "error";	

		}

		$stmt -> close();

		$stmt = null;

	}

	static public function mdlGuardarSeguimientoCliente($datos){
		self::mdlAsegurarTablaCrm();

		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO cliente_crm (id_cliente, estado, prioridad, proxima_accion, nota, id_usuario)
			 VALUES (:id_cliente, :estado, :prioridad, :proxima_accion, :nota, :id_usuario)
			 ON DUPLICATE KEY UPDATE
			 estado = VALUES(estado),
			 prioridad = VALUES(prioridad),
			 proxima_accion = VALUES(proxima_accion),
			 nota = VALUES(nota),
			 id_usuario = VALUES(id_usuario),
			 fecha_actualizacion = NOW()"
		);

		$stmt->bindParam(":id_cliente", $datos["id_cliente"], PDO::PARAM_INT);
		$stmt->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
		$stmt->bindParam(":prioridad", $datos["prioridad"], PDO::PARAM_STR);
		if(empty($datos["proxima_accion"])){
			$stmt->bindValue(":proxima_accion", null, PDO::PARAM_NULL);
		}else{
			$stmt->bindParam(":proxima_accion", $datos["proxima_accion"], PDO::PARAM_STR);
		}
		$stmt->bindParam(":nota", $datos["nota"], PDO::PARAM_STR);
		$stmt->bindParam(":id_usuario", $datos["id_usuario"], PDO::PARAM_INT);

		return $stmt->execute() ? "ok" : "error";
	}

	static public function mdlActualizarPasswordWeb($tabla, $idCliente, $passwordWeb){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET password_web = :password_web WHERE id = :id");

		$stmt -> bindParam(":password_web", $passwordWeb, PDO::PARAM_STR);
		$stmt -> bindParam(":id", $idCliente, PDO::PARAM_INT);

		if($stmt -> execute()){

			return "ok";
		
		}else{

			return "error";	

		}

		$stmt -> close();

		$stmt = null;

	}

	static private function mdlAsegurarTablaPasswordWebTokens(){
		$conexion = Conexion::conectar();
		$conexion->exec(
			"CREATE TABLE IF NOT EXISTS web_cliente_password_tokens (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_cliente INT NOT NULL,
				token_hash VARCHAR(64) NOT NULL,
				expira_en DATETIME NOT NULL,
				usado TINYINT(1) NOT NULL DEFAULT 0,
				creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				UNIQUE KEY uk_web_cliente_token_hash (token_hash),
				KEY idx_web_cliente_password (id_cliente, usado, expira_en)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);
	}

	static public function mdlGuardarTokenPasswordWeb($idCliente, $tokenHash, $expira){
		self::mdlAsegurarTablaPasswordWebTokens();
		$conexion = Conexion::conectar();

		$stmt = $conexion->prepare("UPDATE web_cliente_password_tokens SET usado = 1 WHERE id_cliente = :id_cliente AND usado = 0");
		$stmt->bindParam(":id_cliente", $idCliente, PDO::PARAM_INT);
		$stmt->execute();

		$stmt = $conexion->prepare(
			"INSERT INTO web_cliente_password_tokens(id_cliente, token_hash, expira_en, usado)
			 VALUES(:id_cliente, :token_hash, :expira_en, 0)"
		);
		$stmt->bindParam(":id_cliente", $idCliente, PDO::PARAM_INT);
		$stmt->bindParam(":token_hash", $tokenHash, PDO::PARAM_STR);
		$stmt->bindParam(":expira_en", $expira, PDO::PARAM_STR);

		return $stmt->execute() ? "ok" : "error";
	}

}
