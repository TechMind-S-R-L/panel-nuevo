<?php

require_once "conexion.php";

class ModeloCategorias{

	/*=============================================
	CREAR CATEGORIA
	=============================================*/

	static public function mdlIngresarCategoria($tabla, $datos){

		$nombreCategoria = is_array($datos) ? $datos["categoria"] : $datos;
		$idPadre = is_array($datos) ? ($datos["id_padre"] ?? null) : null;

		$stmt = Conexion::conectar()->prepare("INSERT INTO $tabla(id_padre, categoria) VALUES (:id_padre, :categoria)");

		$stmt->bindParam(":categoria", $nombreCategoria, PDO::PARAM_STR);
		if($idPadre === null || $idPadre === ""){
			$stmt->bindValue(":id_padre", null, PDO::PARAM_NULL);
		}else{
			$stmt->bindValue(":id_padre", (int)$idPadre, PDO::PARAM_INT);
		}

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	MOSTRAR CATEGORIAS
	=============================================*/

	static public function mdlMostrarCategorias($tabla, $item, $valor){

		if($item != null){

			$stmt = Conexion::conectar()->prepare(
				"SELECT c.*, p.categoria AS categoria_padre,
				        CASE WHEN p.categoria IS NULL THEN c.categoria ELSE CONCAT(p.categoria, ' > ', c.categoria) END AS ruta_categoria
				 FROM $tabla c
				 LEFT JOIN $tabla p ON p.id = c.id_padre
				 WHERE c.$item = :$item"
			);

			$stmt -> bindParam(":".$item, $valor, PDO::PARAM_STR);

			$stmt -> execute();

			return $stmt -> fetch();

		}else{

			$stmt = Conexion::conectar()->prepare(
				"SELECT c.*, p.categoria AS categoria_padre,
				        CASE WHEN p.categoria IS NULL THEN c.categoria ELSE CONCAT(p.categoria, ' > ', c.categoria) END AS ruta_categoria,
				        (SELECT COUNT(*) FROM categorias h WHERE h.id_padre = c.id) AS total_hijos
				 FROM $tabla c
				 LEFT JOIN $tabla p ON p.id = c.id_padre
				 ORDER BY COALESCE(p.categoria, c.categoria) ASC, c.id_padre IS NOT NULL ASC, c.categoria ASC"
			);

			$stmt -> execute();

			return $stmt -> fetchAll();

		}

		$stmt -> close();

		$stmt = null;

	}

	/*=============================================
	EDITAR CATEGORIA
	=============================================*/

	static public function mdlEditarCategoria($tabla, $datos){

		$stmt = Conexion::conectar()->prepare("UPDATE $tabla SET categoria = :categoria, id_padre = :id_padre WHERE id = :id");

		$stmt -> bindParam(":categoria", $datos["categoria"], PDO::PARAM_STR);
		$stmt -> bindParam(":id", $datos["id"], PDO::PARAM_INT);
		if(!isset($datos["id_padre"]) || $datos["id_padre"] === ""){
			$stmt->bindValue(":id_padre", null, PDO::PARAM_NULL);
		}else{
			$stmt->bindValue(":id_padre", (int)$datos["id_padre"], PDO::PARAM_INT);
		}

		if($stmt->execute()){

			return "ok";

		}else{

			return "error";
		
		}

		$stmt->close();
		$stmt = null;

	}

	/*=============================================
	BORRAR CATEGORIA
	=============================================*/

	static public function mdlBorrarCategoria($tabla, $datos){

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

