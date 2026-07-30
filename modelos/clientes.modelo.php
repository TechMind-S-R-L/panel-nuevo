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

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	MOSTRAR CLIENTES
	=============================================*/

	static public function mdlMostrarClientes($tabla, $item, $valor){

		if($item != null){

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");

			$stmt -> bindParam(":".$item, $valor, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla");

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

}
