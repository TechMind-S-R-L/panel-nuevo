<?php

require_once __DIR__ . "/conexion.php";

class ModeloWebPublicaciones{

	static public function mdlAsegurarTablas(){
		$db = Conexion::conectar();
		$db->exec(
			"CREATE TABLE IF NOT EXISTS web_publicaciones (
				id INT AUTO_INCREMENT PRIMARY KEY,
				titulo VARCHAR(180) NOT NULL,
				resumen TEXT NOT NULL,
				tipo ENUM('novedad','oferta','aviso') NOT NULL DEFAULT 'novedad',
				imagen VARCHAR(255) NULL,
				enlace VARCHAR(255) NULL,
				texto_boton VARCHAR(60) NULL,
				audiencia ENUM('todos','con_compras','con_servicios','con_proyectos') NOT NULL DEFAULT 'todos',
				destacada TINYINT(1) NOT NULL DEFAULT 0,
				estado TINYINT(1) NOT NULL DEFAULT 1,
				fecha_inicio DATETIME NOT NULL,
				fecha_fin DATETIME NULL,
				id_usuario INT NULL,
				fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_web_publicacion_vigencia (estado, fecha_inicio, fecha_fin),
				INDEX idx_web_publicacion_tipo (tipo)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
		$db->exec(
			"CREATE TABLE IF NOT EXISTS web_publicaciones_lecturas (
				id_publicacion INT NOT NULL,
				id_cliente INT NOT NULL,
				fecha_lectura TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id_publicacion, id_cliente),
				INDEX idx_web_lectura_cliente (id_cliente)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
		$db->exec(
			"CREATE TABLE IF NOT EXISTS web_publicidad_modal (
				id INT AUTO_INCREMENT PRIMARY KEY,
				titulo VARCHAR(180) NULL,
				texto TEXT NULL,
				imagen VARCHAR(255) NOT NULL,
				enlace VARCHAR(255) NULL,
				texto_boton VARCHAR(60) NULL,
				estado TINYINT(1) NOT NULL DEFAULT 1,
				fecha_inicio DATETIME NOT NULL,
				fecha_fin DATETIME NULL,
				id_usuario INT NULL,
				fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_web_modal_vigencia (estado, fecha_inicio, fecha_fin)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
		$db->exec(
			"CREATE TABLE IF NOT EXISTS web_configuracion (
				clave VARCHAR(100) NOT NULL PRIMARY KEY,
				valor TEXT NULL,
				id_usuario INT NULL,
				fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('catalogo_mostrar_stock','1')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('catalogo_mostrar_precio','0')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('web_whatsapp','59168693338')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('web_telefono','68693338')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('web_whatsapp_prefijo','591')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('web_telefono_prefijo','591')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('web_correo','techmind.srl.bo@gmail.com')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('boletas_empresa_nombre','TECHMIND S.R.L.')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('boletas_empresa_direccion','Km 6 doble via la guardia, calle paraiso Nro 6387')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('boletas_empresa_telefono','(+591) 75556540 | (+591) 78572656')"
		);
		$db->exec(
			"INSERT IGNORE INTO web_configuracion(clave,valor) VALUES ('boletas_empresa_correo','techmind.srl.bo@gmail.com')"
		);
		self::mdlSincronizarVigencias($db);
	}

	static public function mdlSincronizarVigencias($db = null){
		$db = $db ?: Conexion::conectar();
		$db->exec("UPDATE web_publicidad_modal SET estado=0 WHERE estado=1 AND fecha_fin IS NOT NULL AND fecha_fin<NOW()");
		$db->exec("UPDATE web_publicaciones SET estado=0 WHERE estado=1 AND fecha_fin IS NOT NULL AND fecha_fin<NOW()");
		return "ok";
	}

	static public function mdlEstadoVigencia($item){
		$ahora = time();
		$inicio = !empty($item["fecha_inicio"]) ? strtotime($item["fecha_inicio"]) : 0;
		$fin = !empty($item["fecha_fin"]) ? strtotime($item["fecha_fin"]) : null;
		if($fin !== null && $fin < $ahora){ return "vencida"; }
		if((int)($item["estado"] ?? 0) !== 1){ return "pausada"; }
		if($inicio > $ahora){ return "programada"; }
		return "vigente";
	}

	static public function mdlObtenerConfiguracion($clave, $defecto = null){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("SELECT valor FROM web_configuracion WHERE clave=:clave LIMIT 1");
		$stmt->execute(array(":clave" => $clave));
		$valor = $stmt->fetchColumn();
		return $valor === false ? $defecto : $valor;
	}

	static public function mdlGuardarConfiguracion($clave, $valor, $idUsuario){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO web_configuracion(clave,valor,id_usuario)
			 VALUES(:clave,:valor,:id_usuario)
			 ON DUPLICATE KEY UPDATE valor=VALUES(valor),id_usuario=VALUES(id_usuario)"
		);
		return $stmt->execute(array(":clave"=>$clave,":valor"=>$valor,":id_usuario"=>(int)$idUsuario)) ? "ok" : "error";
	}

	static public function mdlMostrarPublicaciones(){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.*, u.nombre AS autor
			 FROM web_publicaciones p
			 LEFT JOIN usuarios u ON u.id = p.id_usuario
			 ORDER BY p.destacada DESC, p.fecha_inicio DESC, p.id DESC"
		);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlGuardarPublicacion($datos){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"INSERT INTO web_publicaciones
			 (titulo,resumen,tipo,imagen,enlace,texto_boton,audiencia,destacada,estado,fecha_inicio,fecha_fin,id_usuario)
			 VALUES
			 (:titulo,:resumen,:tipo,:imagen,:enlace,:texto_boton,:audiencia,:destacada,:estado,:fecha_inicio,:fecha_fin,:id_usuario)"
		);
		return $stmt->execute($datos) ? "ok" : "error";
	}

	static public function mdlActualizarPublicacion($datos){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"UPDATE web_publicaciones SET
			 titulo=:titulo,resumen=:resumen,tipo=:tipo,imagen=:imagen,enlace=:enlace,
			 texto_boton=:texto_boton,audiencia=:audiencia,destacada=:destacada,
			 estado=:estado,fecha_inicio=:fecha_inicio,fecha_fin=:fecha_fin,id_usuario=:id_usuario
			 WHERE id=:id"
		);
		return $stmt->execute($datos) ? "ok" : "error";
	}

	static public function mdlCambiarEstado($id, $estado){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("UPDATE web_publicaciones SET estado=:estado WHERE id=:id");
		return $stmt->execute(array(":estado" => (int)$estado, ":id" => (int)$id)) ? "ok" : "error";
	}

	static public function mdlEliminarPublicacion($id){
		self::mdlAsegurarTablas();
		$db = Conexion::conectar();
		$db->beginTransaction();
		$stmt = $db->prepare("DELETE FROM web_publicaciones_lecturas WHERE id_publicacion=:id");
		$stmt->execute(array(":id" => (int)$id));
		$stmt = $db->prepare("DELETE FROM web_publicaciones WHERE id=:id");
		$stmt->execute(array(":id" => (int)$id));
		$db->commit();
		return "ok";
	}

	static public function mdlPublicacionesCliente($idCliente, $limite = 20){
		self::mdlAsegurarTablas();
		$limite = max(1, min(50, (int)$limite));
		$stmt = Conexion::conectar()->prepare(
			"SELECT p.*, IF(l.id_cliente IS NULL,0,1) AS leida
			 FROM web_publicaciones p
			 LEFT JOIN web_publicaciones_lecturas l
			   ON l.id_publicacion=p.id AND l.id_cliente=:id_lectura
			 WHERE p.estado=1
			   AND p.fecha_inicio<=NOW()
			   AND (p.fecha_fin IS NULL OR p.fecha_fin>=NOW())
			   AND (
			    p.audiencia='todos'
			    OR (p.audiencia='con_compras' AND EXISTS(SELECT 1 FROM ventas v WHERE v.id_cliente=:id_compras))
			    OR (p.audiencia='con_servicios' AND EXISTS(SELECT 1 FROM servicios_ventas s WHERE s.id_cliente=:id_servicios))
			    OR (p.audiencia='con_proyectos' AND EXISTS(
			      SELECT 1 FROM proyectos_software pr INNER JOIN servicios_ventas sp ON sp.id=pr.id_servicio WHERE sp.id_cliente=:id_proyectos
			    ))
			   )
			 ORDER BY p.destacada DESC, p.fecha_inicio DESC, p.id DESC
			 LIMIT {$limite}"
		);
		$stmt->execute(array(
			":id_lectura" => (int)$idCliente,
			":id_compras" => (int)$idCliente,
			":id_servicios" => (int)$idCliente,
			":id_proyectos" => (int)$idCliente
		));
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMarcarLeidas($idCliente){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare(
			"INSERT IGNORE INTO web_publicaciones_lecturas(id_publicacion,id_cliente)
			 SELECT p.id,:id_cliente
			 FROM web_publicaciones p
			 WHERE p.estado=1 AND p.fecha_inicio<=NOW() AND (p.fecha_fin IS NULL OR p.fecha_fin>=NOW())"
		);
		return $stmt->execute(array(":id_cliente" => (int)$idCliente)) ? "ok" : "error";
	}

	static public function mdlMostrarPublicidadModal(){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("SELECT * FROM web_publicidad_modal ORDER BY id DESC");
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlPublicidadModalActiva(){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("SELECT * FROM web_publicidad_modal WHERE estado=1 AND fecha_inicio<=NOW() AND (fecha_fin IS NULL OR fecha_fin>=NOW()) ORDER BY id DESC LIMIT 1");
		$stmt->execute();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlGuardarPublicidadModal($datos){
		self::mdlAsegurarTablas();
		$db = Conexion::conectar();
		$db->beginTransaction();
		if((int)$datos[":estado"] === 1){ $db->exec("UPDATE web_publicidad_modal SET estado=0"); }
		$stmt = $db->prepare("INSERT INTO web_publicidad_modal(titulo,texto,imagen,enlace,texto_boton,estado,fecha_inicio,fecha_fin,id_usuario) VALUES(:titulo,:texto,:imagen,:enlace,:texto_boton,:estado,:fecha_inicio,:fecha_fin,:id_usuario)");
		$stmt->execute($datos);
		$db->commit();
		return "ok";
	}

	static public function mdlActualizarPublicidadModal($datos){
		self::mdlAsegurarTablas();
		$db = Conexion::conectar();
		$db->beginTransaction();
		if((int)$datos[":estado"] === 1){
			$stmtPausar = $db->prepare("UPDATE web_publicidad_modal SET estado=0 WHERE id<>:id");
			$stmtPausar->execute(array(":id" => (int)$datos[":id"]));
		}
		$stmt = $db->prepare(
			"UPDATE web_publicidad_modal SET
			 titulo=:titulo,texto=:texto,imagen=:imagen,enlace=:enlace,texto_boton=:texto_boton,
			 estado=:estado,fecha_inicio=:fecha_inicio,fecha_fin=:fecha_fin,id_usuario=:id_usuario
			 WHERE id=:id"
		);
		$stmt->execute($datos);
		$db->commit();
		return "ok";
	}

	static public function mdlCambiarEstadoPublicidadModal($id, $estado){
		self::mdlAsegurarTablas();
		$db = Conexion::conectar();
		$db->beginTransaction();
		if((int)$estado === 1){ $db->exec("UPDATE web_publicidad_modal SET estado=0"); }
		$stmt = $db->prepare("UPDATE web_publicidad_modal SET estado=:estado WHERE id=:id");
		$stmt->execute(array(":estado" => (int)$estado, ":id" => (int)$id));
		$db->commit();
		return "ok";
	}

	static public function mdlEliminarPublicidadModal($id){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("DELETE FROM web_publicidad_modal WHERE id=:id");
		return $stmt->execute(array(":id" => (int)$id)) ? "ok" : "error";
	}
}
