<?php

class Conexion{

	static public function conectar(){

		$host = getenv("DB_HOST") ?: "127.0.0.1";
		$port = getenv("DB_PORT") ?: "3306";
		$name = getenv("DB_NAME") ?: "bd_techmind";
		$user = getenv("DB_USER") ?: "root";
		$pass = getenv("DB_PASS") !== false ? getenv("DB_PASS") : "";

		try{

			$link = new PDO(
				"mysql:host={$host};port={$port};dbname={$name};charset=utf8",
				$user,
				$pass,
				array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false
				)
			);

			return $link;

		}catch(PDOException $e){

			die('<div style="font-family:Arial,sans-serif;max-width:720px;margin:45px auto;padding:20px;border:1px solid #f5c2c7;border-radius:10px;background:#fff5f5;color:#842029">
				<h3 style="margin-top:0">No se pudo conectar a la base de datos</h3>
				<p>Verifica las variables de entorno <b>DB_HOST</b>, <b>DB_PORT</b>, <b>DB_NAME</b>, <b>DB_USER</b>, <b>DB_PASS</b> y que el servicio MySQL este disponible.</p>
				<p style="margin-bottom:0"><b>Detalle:</b> '.htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8").'</p>
			</div>');

		}

	}

}
