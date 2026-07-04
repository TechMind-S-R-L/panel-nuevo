<?php

require_once __DIR__ . "/conexion.php";

class ModeloWebConsultas{

	static public function mdlAsegurarTablas(){
		$db = Conexion::conectar();
		$db->exec(
			"CREATE TABLE IF NOT EXISTS web_consultas (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_cliente INT NOT NULL,
				asunto VARCHAR(180) NOT NULL DEFAULT 'Consulta general',
				estado ENUM('abierta','en_atencion','cerrada') NOT NULL DEFAULT 'abierta',
				id_asignado INT NULL,
				fecha_ultimo_mensaje DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				fecha_cierre DATETIME NULL,
				INDEX idx_web_consulta_cliente (id_cliente, estado),
				INDEX idx_web_consulta_bandeja (estado, fecha_ultimo_mensaje)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
		$db->exec(
			"CREATE TABLE IF NOT EXISTS web_consulta_mensajes (
				id INT AUTO_INCREMENT PRIMARY KEY,
				id_consulta INT NOT NULL,
				emisor ENUM('cliente','usuario','sistema') NOT NULL,
				id_cliente INT NULL,
				id_usuario INT NULL,
				mensaje TEXT NOT NULL,
				leido_cliente TINYINT(1) NOT NULL DEFAULT 0,
				leido_interno TINYINT(1) NOT NULL DEFAULT 0,
				fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_web_mensaje_consulta (id_consulta, id),
				INDEX idx_web_mensaje_interno (emisor, leido_interno),
				INDEX idx_web_mensaje_cliente (emisor, leido_cliente)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
		$db->exec(
			"CREATE TABLE IF NOT EXISTS web_consulta_respuestas_rapidas (
				id INT AUTO_INCREMENT PRIMARY KEY,
				titulo VARCHAR(100) NOT NULL,
				mensaje VARCHAR(500) NOT NULL,
				icono VARCHAR(50) NOT NULL DEFAULT 'ti-message-circle',
				orden INT NOT NULL DEFAULT 0,
				estado TINYINT(1) NOT NULL DEFAULT 1,
				id_usuario INT NULL,
				fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_web_respuesta_estado (estado, orden)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);
		$total = (int)$db->query("SELECT COUNT(*) FROM web_consulta_respuestas_rapidas")->fetchColumn();
		if($total === 0){
			$db->exec(
				"INSERT INTO web_consulta_respuestas_rapidas(titulo,mensaje,icono,orden) VALUES
				('Consultar producto','Quisiera consultar disponibilidad y precio de un producto.','ti-package',10),
				('Estado de compra','Necesito información sobre el estado de mi compra o entrega.','ti-shopping-cart',20),
				('Consultar servicio','Quisiera recibir información o seguimiento de un servicio técnico.','ti-tool',30),
				('Hablar con ventas','Necesito hablar directamente con un asesor de ventas.','ti-headset',40)"
			);
		}
	}

	static public function mdlRespuestasRapidas($soloActivas = false){
		self::mdlAsegurarTablas();
		$sql = "SELECT * FROM web_consulta_respuestas_rapidas";
		if($soloActivas){ $sql .= " WHERE estado=1"; }
		$sql .= " ORDER BY orden ASC, id ASC";
		return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlGuardarRespuestaRapida($datos){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("INSERT INTO web_consulta_respuestas_rapidas(titulo,mensaje,icono,orden,estado,id_usuario) VALUES(:titulo,:mensaje,:icono,:orden,:estado,:id_usuario)");
		return $stmt->execute($datos) ? "ok" : "error";
	}

	static public function mdlActualizarRespuestaRapida($datos){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("UPDATE web_consulta_respuestas_rapidas SET titulo=:titulo,mensaje=:mensaje,icono=:icono,orden=:orden,estado=:estado,id_usuario=:id_usuario WHERE id=:id");
		return $stmt->execute($datos) ? "ok" : "error";
	}

	static public function mdlCambiarEstadoRespuestaRapida($id, $estado){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("UPDATE web_consulta_respuestas_rapidas SET estado=:estado WHERE id=:id");
		return $stmt->execute(array(":estado"=>(int)$estado,":id"=>(int)$id)) ? "ok" : "error";
	}

	static public function mdlEliminarRespuestaRapida($id){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("DELETE FROM web_consulta_respuestas_rapidas WHERE id=:id");
		return $stmt->execute(array(":id"=>(int)$id)) ? "ok" : "error";
	}

	static public function mdlConsultaActivaCliente($idCliente){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("SELECT c.*,u.nombre AS asesor FROM web_consultas c LEFT JOIN usuarios u ON u.id=c.id_asignado WHERE c.id_cliente=:id_cliente AND c.estado<>'cerrada' ORDER BY c.id DESC LIMIT 1");
		$stmt->execute(array(":id_cliente"=>(int)$idCliente));
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlUltimaConsultaCliente($idCliente){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("SELECT c.*,u.nombre AS asesor FROM web_consultas c LEFT JOIN usuarios u ON u.id=c.id_asignado WHERE c.id_cliente=:id_cliente ORDER BY c.id DESC LIMIT 1");
		$stmt->execute(array(":id_cliente"=>(int)$idCliente));
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlCrearConsulta($idCliente, $asunto){
		self::mdlAsegurarTablas();
		$db = Conexion::conectar();
		$stmt = $db->prepare("INSERT INTO web_consultas(id_cliente,asunto) VALUES(:id_cliente,:asunto)");
		$stmt->execute(array(":id_cliente"=>(int)$idCliente,":asunto"=>$asunto));
		return (int)$db->lastInsertId();
	}

	static public function mdlEnviarMensajeCliente($idCliente, $mensaje, $asunto = "Consulta general"){
		self::mdlAsegurarTablas();
		$db = Conexion::conectar();
		$db->beginTransaction();
		$stmt = $db->prepare("SELECT id FROM web_consultas WHERE id_cliente=:id_cliente AND estado<>'cerrada' ORDER BY id DESC LIMIT 1 FOR UPDATE");
		$stmt->execute(array(":id_cliente"=>(int)$idCliente));
		$idConsulta = (int)$stmt->fetchColumn();
		if($idConsulta <= 0){
			$stmt = $db->prepare("INSERT INTO web_consultas(id_cliente,asunto) VALUES(:id_cliente,:asunto)");
			$stmt->execute(array(":id_cliente"=>(int)$idCliente,":asunto"=>$asunto));
			$idConsulta = (int)$db->lastInsertId();
		}
		$stmt = $db->prepare("INSERT INTO web_consulta_mensajes(id_consulta,emisor,id_cliente,mensaje,leido_cliente,leido_interno) VALUES(:id_consulta,'cliente',:id_cliente,:mensaje,1,0)");
		$stmt->execute(array(":id_consulta"=>$idConsulta,":id_cliente"=>(int)$idCliente,":mensaje"=>$mensaje));
		$stmt = $db->prepare("UPDATE web_consultas SET fecha_ultimo_mensaje=NOW(),estado=IF(estado='cerrada','abierta',estado),fecha_cierre=NULL WHERE id=:id");
		$stmt->execute(array(":id"=>$idConsulta));
		$db->commit();
		return $idConsulta;
	}

	static public function mdlMensajesConsulta($idConsulta, $idCliente = null){
		self::mdlAsegurarTablas();
		$sql = "SELECT m.*,u.nombre AS usuario_nombre,c.nombre AS cliente_nombre FROM web_consulta_mensajes m LEFT JOIN usuarios u ON u.id=m.id_usuario LEFT JOIN clientes c ON c.id=m.id_cliente WHERE m.id_consulta=:id_consulta";
		$params = array(":id_consulta"=>(int)$idConsulta);
		if($idCliente !== null){
			$sql .= " AND EXISTS(SELECT 1 FROM web_consultas wc WHERE wc.id=m.id_consulta AND wc.id_cliente=:id_cliente)";
			$params[":id_cliente"] = (int)$idCliente;
		}
		$sql .= " ORDER BY m.id ASC";
		$stmt = Conexion::conectar()->prepare($sql);
		$stmt->execute($params);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlMarcarLeidosCliente($idConsulta, $idCliente){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("UPDATE web_consulta_mensajes m INNER JOIN web_consultas c ON c.id=m.id_consulta SET m.leido_cliente=1 WHERE m.id_consulta=:id_consulta AND c.id_cliente=:id_cliente AND m.emisor='usuario'");
		return $stmt->execute(array(":id_consulta"=>(int)$idConsulta,":id_cliente"=>(int)$idCliente));
	}

	static public function mdlConsultasBandeja(){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->query(
			"SELECT c.*,cl.nombre AS cliente,cl.email,cl.telefono,u.nombre AS asesor,
			 (SELECT COUNT(*) FROM web_consulta_mensajes m WHERE m.id_consulta=c.id AND m.emisor='cliente' AND m.leido_interno=0) AS no_leidos,
			 (SELECT mensaje FROM web_consulta_mensajes mu WHERE mu.id_consulta=c.id ORDER BY mu.id DESC LIMIT 1) AS ultimo_mensaje
			 FROM web_consultas c
			 INNER JOIN clientes cl ON cl.id=c.id_cliente
			 LEFT JOIN usuarios u ON u.id=c.id_asignado
			 ORDER BY FIELD(c.estado,'abierta','en_atencion','cerrada'),c.fecha_ultimo_mensaje DESC"
		);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	static public function mdlConsultaPorId($id){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("SELECT c.*,cl.nombre AS cliente,cl.email,cl.telefono,u.nombre AS asesor FROM web_consultas c INNER JOIN clientes cl ON cl.id=c.id_cliente LEFT JOIN usuarios u ON u.id=c.id_asignado WHERE c.id=:id LIMIT 1");
		$stmt->execute(array(":id"=>(int)$id));
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	static public function mdlResponder($idConsulta, $idUsuario, $mensaje){
		self::mdlAsegurarTablas();
		$db = Conexion::conectar();
		$db->beginTransaction();
		$stmt = $db->prepare("INSERT INTO web_consulta_mensajes(id_consulta,emisor,id_usuario,mensaje,leido_cliente,leido_interno) VALUES(:id_consulta,'usuario',:id_usuario,:mensaje,0,1)");
		$stmt->execute(array(":id_consulta"=>(int)$idConsulta,":id_usuario"=>(int)$idUsuario,":mensaje"=>$mensaje));
		$stmt = $db->prepare("UPDATE web_consultas SET id_asignado=COALESCE(id_asignado,:id_usuario),estado='en_atencion',fecha_ultimo_mensaje=NOW(),fecha_cierre=NULL WHERE id=:id");
		$stmt->execute(array(":id_usuario"=>(int)$idUsuario,":id"=>(int)$idConsulta));
		$stmt = $db->prepare("UPDATE web_consulta_mensajes SET leido_interno=1 WHERE id_consulta=:id AND emisor='cliente'");
		$stmt->execute(array(":id"=>(int)$idConsulta));
		$db->commit();
		return "ok";
	}

	static public function mdlMarcarLeidosInterno($idConsulta){
		self::mdlAsegurarTablas();
		$stmt = Conexion::conectar()->prepare("UPDATE web_consulta_mensajes SET leido_interno=1 WHERE id_consulta=:id AND emisor='cliente'");
		return $stmt->execute(array(":id"=>(int)$idConsulta));
	}

	static public function mdlCambiarEstadoConsulta($idConsulta, $estado, $idUsuario){
		self::mdlAsegurarTablas();
		if(!in_array($estado,array("abierta","en_atencion","cerrada"),true)){ return "error"; }
		$stmt = Conexion::conectar()->prepare("UPDATE web_consultas SET estado=:estado,id_asignado=COALESCE(id_asignado,:id_usuario),fecha_cierre=IF(:estado_cierre='cerrada',NOW(),NULL) WHERE id=:id");
		return $stmt->execute(array(":estado"=>$estado,":id_usuario"=>(int)$idUsuario,":estado_cierre"=>$estado,":id"=>(int)$idConsulta)) ? "ok" : "error";
	}

	static public function mdlEliminarConsulta($idConsulta){
		self::mdlAsegurarTablas();
		try{
			$db = Conexion::conectar();
			$db->beginTransaction();
			$stmt = $db->prepare("DELETE FROM web_consulta_mensajes WHERE id_consulta=:id");
			$stmt->execute(array(":id"=>(int)$idConsulta));
			$stmt = $db->prepare("DELETE FROM web_consultas WHERE id=:id");
			$stmt->execute(array(":id"=>(int)$idConsulta));
			$db->commit();
			return "ok";
		}catch(Exception $e){
			if(isset($db) && $db->inTransaction()){
				$db->rollBack();
			}
			return "error";
		}
	}
}
